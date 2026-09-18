<?php
/**
 * Endpoint webservice : accessoires d'un produit (accessory_group_product).
 *
 * GET  /api/product_accessories?id_product=X
 * PUT  /api/product_accessories  → remplace toutes les liaisons du produit
 * POST /api/product_accessories  → ajoute une ou plusieurs liaisons
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementProductAccessories extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'product_accessory';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        if (!Module::isEnabled('hsmultiaccessoriespro')) {
            throw new WebserviceException('hsmultiaccessoriespro module is not enabled', [1500, 503]);
        }

        $method = Tools::strtoupper($this->wsObject->method);

        if ($method === 'GET') {
            $this->payload = $this->handleGet();

            return;
        }
        if ($method === 'PUT') {
            $this->payload = $this->handleReplace();

            return;
        }
        if ($method === 'POST') {
            $this->payload = $this->handleAdd();

            return;
        }

        $this->methodNotAllowed('product_accessories', 1501);
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

        if ($idProduct <= 0) {
            return [
                'action' => 'help',
                'description' => 'Read/write HS Multi Accessories Pro product links (accessory_group_product)',
                'methods' => [
                    'GET' => 'List accessories for id_product',
                    'PUT' => 'Replace all accessories for id_product',
                    'POST' => 'Add accessories without removing existing ones',
                ],
                'fields' => [
                    'id_product' => 'Main product id',
                    'accessories' => '[{id_accessory_group, id_accessory, id_product_attribute, default_quantity, min_quantity, required, position, name}]',
                ],
                'example_get' => '/api/product_accessories?id_product=35',
                'example_put_json' => '{"id_product":35,"accessories":[{"id_accessory_group":4,"id_accessory":88,"default_quantity":1,"required":0}]}',
            ];
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 1502);
        $accessories = $this->loadAccessories($idProduct);

        return [
            'action' => 'list',
            'id_product' => $idProduct,
            'count' => count($accessories),
            'accessories' => $accessories,
        ];
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleReplace()
    {
        $input = $this->getParams();
        $idProduct = $this->resolveInt($input, ['id_product', 'id']);
        if ($idProduct <= 0) {
            throw new WebserviceException('id_product is required', [1503, 400]);
        }
        if (!array_key_exists('accessories', $input)) {
            throw new WebserviceException('accessories array is required', [1504, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 1502);
        $lines = $this->validateAccessoryList($input['accessories']);

        $this->deleteProductLinks($idProduct);
        foreach ($lines as $line) {
            $this->insertLink($idProduct, $line);
        }

        $accessories = $this->loadAccessories($idProduct);

        return [
            'action' => 'replace',
            'status' => 'ok',
            'id_product' => $idProduct,
            'count' => count($accessories),
            'accessories' => $accessories,
        ];
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleAdd()
    {
        $input = $this->getParams();
        $idProduct = $this->resolveInt($input, ['id_product', 'id']);
        if ($idProduct <= 0) {
            throw new WebserviceException('id_product is required', [1503, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 1502);

        if (!empty($input['accessories'])) {
            $lines = $this->validateAccessoryList($input['accessories']);
        } else {
            $lines = [$this->validateAccessoryEntry($input)];
        }

        foreach ($lines as $line) {
            $this->insertLink($idProduct, $line);
        }

        $accessories = $this->loadAccessories($idProduct);

        return [
            'action' => 'add',
            'status' => 'ok',
            'id_product' => $idProduct,
            'added' => count($lines),
            'count' => count($accessories),
            'accessories' => $accessories,
        ];
    }

    /**
     * @param mixed $list
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function validateAccessoryList($list)
    {
        if (!is_array($list)) {
            throw new WebserviceException('accessories must be an array', [1505, 400]);
        }

        $out = [];
        foreach ($list as $i => $entry) {
            if (!is_array($entry)) {
                throw new WebserviceException('accessories[' . $i . '] must be an object', [1506, 400]);
            }
            $out[] = $this->validateAccessoryEntry($entry, $i);
        }

        return $out;
    }

    /**
     * @param array    $entry
     * @param int|null $index
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function validateAccessoryEntry(array $entry, $index = null)
    {
        $prefix = $index !== null ? 'accessories[' . $index . '].' : '';
        $idGroup = (int) ($entry['id_accessory_group'] ?? 0);
        $idAccessory = (int) ($entry['id_accessory'] ?? 0);

        if ($idGroup <= 0) {
            throw new WebserviceException($prefix . 'id_accessory_group is required', [1507, 400]);
        }
        if ($idAccessory <= 0) {
            throw new WebserviceException($prefix . 'id_accessory is required', [1508, 400]);
        }
        if (!$this->rowExists('accessory_group', 'id_accessory_group', $idGroup)) {
            throw new WebserviceException($prefix . 'Accessory group ' . $idGroup . ' does not exist', [1509, 404]);
        }
        $this->assertRowExists('product', 'id_product', $idAccessory, 'Accessory product', 1510);

        return [
            'id_accessory_group' => $idGroup,
            'id_accessory' => $idAccessory,
            'id_product_attribute' => (int) ($entry['id_product_attribute'] ?? 0),
            'default_quantity' => max(1, (int) ($entry['default_quantity'] ?? 1)),
            'min_quantity' => max(1, (int) ($entry['min_quantity'] ?? 1)),
            'required' => !empty($entry['required']) ? 1 : 0,
            'position' => (int) ($entry['position'] ?? 0),
            'name' => $entry['name'] ?? null,
        ];
    }

    /**
     * @param int   $idProduct
     * @param array $line
     */
    protected function insertLink($idProduct, array $line)
    {
        $names = $this->resolveLinkNames($line['id_accessory'], $line['name']);

        Db::getInstance()->insert('accessory_group_product', [
            'id_accessory_group' => (int) $line['id_accessory_group'],
            'id_product' => (int) $idProduct,
            'id_accessory' => (int) $line['id_accessory'],
            'id_product_attribute' => (int) $line['id_product_attribute'],
            'default_quantity' => (int) $line['default_quantity'],
            'min_quantity' => (int) $line['min_quantity'],
            'required' => (int) $line['required'],
            'position' => (int) $line['position'],
        ]);
        $idLink = (int) Db::getInstance()->Insert_ID();

        foreach ($names as $idLang => $name) {
            Db::getInstance()->insert('accessory_group_product_lang', [
                'id_accessory_group_product' => $idLink,
                'id_lang' => (int) $idLang,
                'name' => pSQL($name),
            ]);
        }
    }

    /**
     * @param int   $idAccessory
     * @param mixed $rawName
     *
     * @return array
     */
    protected function resolveLinkNames($idAccessory, $rawName)
    {
        $langs = Language::getLanguages(false);
        $names = [];

        if (is_string($rawName) && $rawName !== '') {
            foreach ($langs as $lang) {
                $names[(int) $lang['id_lang']] = $rawName;
            }
        } elseif (is_array($rawName)) {
            foreach ($rawName as $idLang => $name) {
                if ((string) $name !== '') {
                    $names[(int) $idLang] = (string) $name;
                }
            }
        }

        if (!$names) {
            foreach ($langs as $lang) {
                $productName = (string) Db::getInstance()->getValue(
                    'SELECT `name` FROM `' . _DB_PREFIX_ . 'product_lang`
                     WHERE `id_product` = ' . (int) $idAccessory . '
                       AND `id_lang` = ' . (int) $lang['id_lang']
                );
                $names[(int) $lang['id_lang']] = $productName !== '' ? $productName : ('Accessory ' . (int) $idAccessory);
            }
        } else {
            $default = reset($names);
            foreach ($langs as $lang) {
                $idLang = (int) $lang['id_lang'];
                if (!isset($names[$idLang]) || $names[$idLang] === '') {
                    $names[$idLang] = $default;
                }
            }
        }

        return $names;
    }

    /**
     * @param int $idProduct
     */
    protected function deleteProductLinks($idProduct)
    {
        $linkIds = Db::getInstance()->executeS(
            'SELECT `id_accessory_group_product` FROM `' . _DB_PREFIX_ . 'accessory_group_product`
             WHERE `id_product` = ' . (int) $idProduct
        );
        if ($linkIds) {
            $in = [];
            foreach ($linkIds as $row) {
                $in[] = (int) $row['id_accessory_group_product'];
            }
            Db::getInstance()->delete(
                'accessory_group_product_lang',
                '`id_accessory_group_product` IN (' . implode(',', $in) . ')'
            );
        }
        Db::getInstance()->delete('accessory_group_product', '`id_product` = ' . (int) $idProduct);
    }

    /**
     * @param int $idProduct
     *
     * @return array
     */
    protected function loadAccessories($idProduct)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'accessory_group_product`
             WHERE `id_product` = ' . (int) $idProduct . '
             ORDER BY `position` ASC, `id_accessory_group_product` ASC'
        );
        if (!$rows) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id_accessory_group_product' => (int) $row['id_accessory_group_product'],
                'id_accessory_group' => (int) $row['id_accessory_group'],
                'id_accessory' => (int) $row['id_accessory'],
                'id_product_attribute' => (int) $row['id_product_attribute'],
                'default_quantity' => (int) $row['default_quantity'],
                'min_quantity' => (int) $row['min_quantity'],
                'required' => (int) $row['required'],
                'position' => (int) $row['position'],
                'name' => $this->getLinkNames((int) $row['id_accessory_group_product']),
            ];
        }

        return $out;
    }

    /**
     * @param int $idLink
     *
     * @return array
     */
    protected function getLinkNames($idLink)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_lang`, `name` FROM `' . _DB_PREFIX_ . 'accessory_group_product_lang`
             WHERE `id_accessory_group_product` = ' . (int) $idLink
        );
        $names = [];
        if ($rows) {
            foreach ($rows as $row) {
                $names[(string) (int) $row['id_lang']] = (string) $row['name'];
            }
        }

        return $names;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            [
                'id_product', 'id', 'accessories',
                'id_accessory_group', 'id_accessory', 'id_product_attribute',
                'default_quantity', 'min_quantity', 'required', 'position', 'name',
            ],
            ['product_accessory', 'product_accessories']
        );
    }
}
