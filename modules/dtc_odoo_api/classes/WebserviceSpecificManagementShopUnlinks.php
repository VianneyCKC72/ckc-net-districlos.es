<?php
/**
 * Endpoint webservice : désassociation boutique unitaire sans cascade.
 *
 * POST /api/shop_unlinks  → unlink resource/id from id_shop
 * GET  /api/shop_unlinks?resource=...&id=... → liste des boutiques associées
 * GET  /api/shop_unlinks → documentation / ressources supportées
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementShopUnlinks extends DtcOdooApiWsEndpoint
{
    /**
     * Ressources API supportées → classe ObjectModel.
     *
     * @var array
     */
    protected static $resourceMap = [
        'categories' => 'Category',
        'category' => 'Category',
        'product_features' => 'Feature',
        'features' => 'Feature',
        'feature' => 'Feature',
        'product_options' => 'AttributeGroup',
        'attribute_groups' => 'AttributeGroup',
        'attribute_group' => 'AttributeGroup',
        'product_option_values' => 'ProductAttribute',
        'attributes' => 'ProductAttribute',
        'attribute' => 'ProductAttribute',
        'products' => 'Product',
        'product' => 'Product',
    ];

    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'shop_unlink';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        $method = Tools::strtoupper($this->wsObject->method);

        if ($method === 'GET') {
            $this->payload = $this->handleGet();

            return;
        }

        if ($method === 'POST') {
            $this->payload = $this->handlePost();

            return;
        }

        $this->methodNotAllowed('shop_unlinks', 100);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $params = $this->getParams();

        if (empty($params['resource']) && empty($params['id'])) {
            return [
                'action' => 'help',
                'description' => 'Unlink an entity from one shop without deleting the entity (no cascade). Equivalent to unchecking a shop in BO.',
                'methods' => [
                    'GET' => 'List shop associations for a resource, or return this help',
                    'POST' => 'Unlink resource/id from id_shop',
                ],
                'post_fields' => ['resource', 'id', 'id_shop'],
                'supported_resources' => array_keys(array_unique(self::$resourceMap)),
                'example_post_json' => '{"resource":"categories","id":42,"id_shop":2}',
            ];
        }

        $resource = $this->normalizeResource(isset($params['resource']) ? $params['resource'] : '');
        $id = (int) (isset($params['id']) ? $params['id'] : 0);

        if ($resource === '' || $id <= 0) {
            throw new WebserviceException(
                'GET shop_unlinks requires both "resource" and "id" parameters',
                [101, 400]
            );
        }

        $meta = $this->getResourceMeta($resource);
        if (!$this->rowExists($meta['table'], $meta['primary'], $id)) {
            throw new WebserviceException(
                'Resource ' . $resource . ' with id ' . $id . ' does not exist',
                [110, 404]
            );
        }

        $shops = $this->getAssociatedShopIds($meta['table'], $meta['primary'], $id);

        return [
            'action' => 'list',
            'resource' => $resource,
            'id' => $id,
            'id_shops' => $shops,
            'count' => count($shops),
        ];
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handlePost()
    {
        $params = $this->getParams();

        $resource = $this->normalizeResource(isset($params['resource']) ? $params['resource'] : '');
        $id = (int) (isset($params['id']) ? $params['id'] : 0);
        $idShop = (int) (isset($params['id_shop']) ? $params['id_shop'] : 0);

        if ($resource === '' || $id <= 0 || $idShop <= 0) {
            throw new WebserviceException(
                'POST shop_unlinks requires "resource", "id" and "id_shop"',
                [102, 400]
            );
        }

        if (!$this->rowExists('shop', 'id_shop', $idShop)) {
            throw new WebserviceException('Shop ' . $idShop . ' does not exist', [103, 404]);
        }

        $meta = $this->getResourceMeta($resource);
        if (!$this->rowExists($meta['table'], $meta['primary'], $id)) {
            throw new WebserviceException(
                'Resource ' . $resource . ' with id ' . $id . ' does not exist',
                [110, 404]
            );
        }

        if ($meta['className'] === 'Category') {
            $this->assertCanUnlinkCategory($id, $idShop);
        }

        $associatedShops = $this->getAssociatedShopIds($meta['table'], $meta['primary'], $id);

        if (!in_array($idShop, $associatedShops, true)) {
            return [
                'action' => 'unlink',
                'status' => 'noop',
                'message' => 'Entity was not associated to this shop',
                'resource' => $resource,
                'id' => $id,
                'id_shop' => $idShop,
                'id_shops' => $associatedShops,
            ];
        }

        if (count($associatedShops) <= 1) {
            throw new WebserviceException(
                'Cannot unlink the last shop association. Keep at least one shop or delete the entity via the standard API.',
                [104, 400]
            );
        }

        $this->unlinkFromShop($meta, $id, $idShop);
        $this->fixDefaultShopIfNeeded($meta, $id, $idShop);
        Cache::clean('objectmodel_shop_' . $meta['className'] . '_' . $id . '-' . $idShop);
        Cache::clean('objectmodel_shop_' . $meta['className'] . '_' . $id . '-*');

        return [
            'action' => 'unlink',
            'status' => 'ok',
            'message' => 'Shop association removed without deleting the entity',
            'resource' => $resource,
            'id' => $id,
            'id_shop' => $idShop,
            'id_shops' => $this->getAssociatedShopIds($meta['table'], $meta['primary'], $id),
        ];
    }

    /**
     * @param int $idCategory
     * @param int $idShop
     *
     * @throws WebserviceException
     */
    protected function assertCanUnlinkCategory($idCategory, $idShop)
    {
        if ((int) $idCategory === (int) Configuration::get('PS_ROOT_CATEGORY')) {
            throw new WebserviceException(
                'Cannot unlink the root category from a shop',
                [105, 400]
            );
        }

        $homeId = (int) Db::getInstance()->getValue(
            'SELECT `id_category` FROM `' . _DB_PREFIX_ . 'shop` WHERE `id_shop` = ' . (int) $idShop
        );
        if ($homeId === (int) $idCategory) {
            throw new WebserviceException(
                'Cannot unlink the home category of shop ' . (int) $idShop,
                [106, 400]
            );
        }
    }

    /**
     * @param array{table:string, primary:string, multilang_shop:bool} $meta
     * @param int $id
     * @param int $idShop
     *
     * @throws WebserviceException
     */
    protected function unlinkFromShop(array $meta, $id, $idShop)
    {
        $ok = Db::getInstance()->delete(
            $meta['table'] . '_shop',
            '`' . bqSQL($meta['primary']) . '` = ' . (int) $id . ' AND `id_shop` = ' . (int) $idShop
        );

        if (!$ok) {
            throw new WebserviceException(
                'Failed to delete shop association from ' . $meta['table'] . '_shop',
                [108, 500]
            );
        }

        if ($meta['multilang_shop']) {
            Db::getInstance()->delete(
                $meta['table'] . '_lang',
                '`' . bqSQL($meta['primary']) . '` = ' . (int) $id . ' AND `id_shop` = ' . (int) $idShop
            );
        }
    }

    /**
     * @param array{table:string, primary:string, has_shop_default:bool} $meta
     * @param int $id
     * @param int $unlinkedShopId
     */
    protected function fixDefaultShopIfNeeded(array $meta, $id, $unlinkedShopId)
    {
        if (empty($meta['has_shop_default'])) {
            return;
        }

        $currentDefault = Db::getInstance()->getValue(
            'SELECT `id_shop_default` FROM `' . _DB_PREFIX_ . bqSQL($meta['table']) . '`
             WHERE `' . bqSQL($meta['primary']) . '` = ' . (int) $id
        );
        if ((int) $currentDefault !== (int) $unlinkedShopId) {
            return;
        }

        $remaining = $this->getAssociatedShopIds($meta['table'], $meta['primary'], $id);
        if (empty($remaining)) {
            return;
        }

        Db::getInstance()->update(
            $meta['table'],
            ['id_shop_default' => (int) $remaining[0]],
            '`' . bqSQL($meta['primary']) . '` = ' . (int) $id
        );
    }

    /**
     * @param string $resource
     *
     * @return array{className:string, table:string, primary:string, multilang_shop:bool, has_shop_default:bool}
     *
     * @throws WebserviceException
     */
    protected function getResourceMeta($resource)
    {
        $className = self::$resourceMap[$resource];
        if (!class_exists($className)) {
            throw new WebserviceException('Class ' . $className . ' not found', [109, 500]);
        }

        $def = $className::$definition;
        $table = isset($def['table']) ? (string) $def['table'] : '';
        $primary = isset($def['primary']) ? (string) $def['primary'] : '';

        if ($table === '' || $primary === '') {
            throw new WebserviceException(
                'Invalid ObjectModel definition for resource ' . $resource,
                [114, 500]
            );
        }
        if (!Shop::isTableAssociated($table)) {
            throw new WebserviceException(
                'Resource ' . $resource . ' does not support shop associations',
                [111, 400]
            );
        }

        return [
            'className' => $className,
            'table' => $table,
            'primary' => $primary,
            'multilang_shop' => !empty($def['multilang']) && !empty($def['multilang_shop']),
            'has_shop_default' => !empty($def['fields']['id_shop_default']),
        ];
    }

    /**
     * @param string $table
     * @param string $primary
     * @param int $id
     *
     * @return int[]
     */
    protected function getAssociatedShopIds($table, $primary, $id)
    {
        $shops = [];
        $rows = Db::getInstance()->executeS(
            'SELECT `id_shop` FROM `' . _DB_PREFIX_ . bqSQL($table) . '_shop`
             WHERE `' . bqSQL($primary) . '` = ' . (int) $id
        );
        foreach ((array) $rows as $row) {
            $shops[] = (int) $row['id_shop'];
        }
        sort($shops);

        return $shops;
    }

    /**
     * @param string $resource
     *
     * @return string
     *
     * @throws WebserviceException
     */
    protected function normalizeResource($resource)
    {
        $resource = Tools::strtolower(trim((string) $resource));

        if ($resource === '' || !isset(self::$resourceMap[$resource])) {
            throw new WebserviceException(
                'Unsupported resource "' . $resource . '". Supported: ' . implode(', ', array_keys(self::$resourceMap)),
                [112, 400]
            );
        }

        $className = self::$resourceMap[$resource];
        foreach (self::$resourceMap as $key => $class) {
            if ($class === $className) {
                return $key;
            }
        }

        return $resource;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            ['resource', 'id', 'id_shop'],
            ['shop_unlink', 'shop_unlinks'],
            [1 => 'resource', 2 => 'id', 3 => 'id_shop']
        );
    }
}
