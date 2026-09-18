<?php
/**
 * Endpoint webservice : transporteurs liés aux produits (absents de /api/products).
 *
 * GET  /api/product_carriers?id_product=X&id_shop=Y
 * PUT  /api/product_carriers  (POST accepté aussi)
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementProductCarriers extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'product_carrier';
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

        if ($method === 'PUT' || $method === 'POST') {
            $this->payload = $this->handleWrite();

            return;
        }

        $this->methodNotAllowed('product_carriers', 300);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $input = $this->getParams();
        $idProduct = $this->resolveInt($input, ['id_product', 'id']);
        $idShop = $this->resolveInt($input, ['id_shop'], 2);
        if ($idShop <= 0) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        if ($idProduct <= 0) {
            return [
                'action' => 'help',
                'description' => 'Read/write product carrier restrictions not exposed by /api/products',
                'methods' => [
                    'GET' => 'Return carriers for id_product and id_shop',
                    'PUT' => 'Replace carriers list for id_product and id_shop',
                    'POST' => 'Same as PUT',
                ],
                'fields' => [
                    'id_product' => 'Product id (required)',
                    'id_shop' => 'Shop id (required)',
                    'carriers' => 'id_carrier list from /api/carriers (empty = all carriers available)',
                ],
                'notes' => [
                    'Stored as id_carrier_reference in ps_product_carrier',
                    'Empty carriers list means all carriers are available (BO default)',
                ],
                'example_get' => '/api/product_carriers?id_product=35&id_shop=1',
                'example_put_json' => '{"id_product":35,"id_shop":1,"carriers":[12,18]}',
            ];
        }

        $this->assertProductInShop($idProduct, $idShop);
        $carriers = $this->getCarrierIds($idProduct, $idShop);

        return [
            'action' => 'list',
            'id_product' => $idProduct,
            'id_shop' => $idShop,
            'carriers' => $carriers,
            'all_carriers' => empty($carriers),
        ];
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleWrite()
    {
        $input = $this->getParams();
        $idProduct = $this->resolveInt($input, ['id_product', 'id']);
        $idShop = $this->resolveInt($input, ['id_shop'], 2);
        if ($idShop <= 0) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        if ($idProduct <= 0) {
            throw new WebserviceException('id_product is required', [301, 400]);
        }
        if (!array_key_exists('carriers', $input)) {
            throw new WebserviceException('carriers field is required (use empty array for all carriers)', [303, 400]);
        }

        $this->assertProductInShop($idProduct, $idShop);
        $references = $this->toCarrierReferences($input['carriers'], 307);

        $db = Db::getInstance();
        $db->delete(
            'product_carrier',
            '`id_product` = ' . (int) $idProduct . ' AND `id_shop` = ' . (int) $idShop
        );
        foreach ($references as $ref) {
            $db->insert('product_carrier', [
                'id_product' => (int) $idProduct,
                'id_carrier_reference' => (int) $ref,
                'id_shop' => (int) $idShop,
            ]);
        }

        Cache::clean('Carrier::getAvailableCarrierList_' . (int) $idProduct . '-' . (int) $idShop);
        Cache::clean('Carrier::getAvailableCarrierList_' . (int) $idProduct . '-*');

        $carriers = $this->getCarrierIds($idProduct, $idShop);

        return [
            'action' => 'update',
            'status' => 'ok',
            'id_product' => $idProduct,
            'id_shop' => $idShop,
            'carriers' => $carriers,
            'all_carriers' => empty($carriers),
        ];
    }

    /**
     * @param int $idProduct
     * @param int $idShop
     *
     * @return int[]
     */
    protected function getCarrierIds($idProduct, $idShop)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT pc.`id_carrier_reference`, c.`id_carrier`
             FROM `' . _DB_PREFIX_ . 'product_carrier` pc
             LEFT JOIN `' . _DB_PREFIX_ . 'carrier` c
                ON c.`id_reference` = pc.`id_carrier_reference` AND c.`deleted` = 0
             WHERE pc.`id_product` = ' . (int) $idProduct . '
               AND pc.`id_shop` = ' . (int) $idShop
        );
        if (!$rows) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) ($row['id_carrier'] ? $row['id_carrier'] : $row['id_carrier_reference']);
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param int $idProduct
     * @param int $idShop
     *
     * @throws WebserviceException
     */
    protected function assertProductInShop($idProduct, $idShop)
    {
        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 305);

        if (Shop::isFeatureActive()) {
            $ok = (bool) Db::getInstance()->getValue(
                'SELECT 1 FROM `' . _DB_PREFIX_ . 'product_shop`
                 WHERE `id_product` = ' . (int) $idProduct . ' AND `id_shop` = ' . (int) $idShop
            );
            if (!$ok) {
                throw new WebserviceException(
                    'Product ' . (int) $idProduct . ' is not associated with shop ' . (int) $idShop,
                    [306, 404]
                );
            }
        }
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            ['id_product', 'id', 'id_shop', 'carriers'],
            ['product_carrier', 'product_carriers'],
            [1 => 'id_product', 2 => 'id_shop']
        );
    }
}
