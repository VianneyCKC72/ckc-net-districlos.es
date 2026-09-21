<?php
/**
 * Endpoint webservice : réglages accessoires par produit (accessory_product_setting).
 *
 * GET  /api/product_accessory_settings?id_product=X
 * PUT  /api/product_accessory_settings  (POST accepté aussi)
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementProductAccessorySettings extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'product_accessory_setting';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        if (!Module::isEnabled('hsmultiaccessoriespro')) {
            throw new WebserviceException('hsmultiaccessoriespro module is not enabled', [1600, 503]);
        }

        $method = Tools::strtoupper($this->wsObject->method);

        if ($method === 'GET') {
            $this->payload = $this->handleGet();

            return;
        }

        if ($method === 'PUT' || $method === 'POST') {
            $this->payload = $this->handleWrite();

            return;
        }

        $this->methodNotAllowed('product_accessory_settings', 1601);
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
                'description' => 'Read/write HSMA per-product settings (accessory_product_setting)',
                'methods' => [
                    'GET' => 'Return settings for id_product',
                    'PUT' => 'Upsert settings for id_product',
                    'POST' => 'Same as PUT',
                ],
                'fields' => [
                    'id_product' => 'Product id (required)',
                    'buy_together' => '0=No, 1=Yes, 2=Use default, 3=Required',
                    'custom_displayed_name' => '0/1',
                ],
                'example_get' => '/api/product_accessory_settings?id_product=35',
                'example_put_json' => '{"id_product":35,"buy_together":3,"custom_displayed_name":1}',
            ];
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 1602);

        return array_merge(['action' => 'list'], $this->loadSettings($idProduct));
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

        if ($idProduct <= 0) {
            throw new WebserviceException('id_product is required', [1603, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 1602);

        $existing = $this->getRow($idProduct);
        $buyTogether = array_key_exists('buy_together', $input)
            ? (int) $input['buy_together']
            : (int) ($existing['buy_together'] ?? 2);
        $customName = array_key_exists('custom_displayed_name', $input)
            ? (int) $input['custom_displayed_name']
            : (int) ($existing['custom_displayed_name'] ?? 0);

        if (!in_array($buyTogether, [0, 1, 2, 3], true)) {
            throw new WebserviceException('buy_together must be 0, 1, 2 or 3', [1604, 400]);
        }

        if ($existing) {
            Db::getInstance()->update(
                'accessory_product_setting',
                [
                    'buy_together' => $buyTogether,
                    'custom_displayed_name' => $customName ? 1 : 0,
                ],
                'id_product = ' . (int) $idProduct
            );
        } else {
            Db::getInstance()->insert('accessory_product_setting', [
                'id_product' => (int) $idProduct,
                'buy_together' => $buyTogether,
                'custom_displayed_name' => $customName ? 1 : 0,
            ]);
        }

        return array_merge([
            'action' => 'update',
            'status' => 'ok',
        ], $this->loadSettings($idProduct));
    }

    /**
     * @param int $idProduct
     *
     * @return array
     */
    protected function loadSettings($idProduct)
    {
        $row = $this->getRow($idProduct);

        return [
            'id_product' => (int) $idProduct,
            'id_accessory_product_setting' => $row ? (int) $row['id_accessory_product_setting'] : null,
            'buy_together' => $row ? (int) $row['buy_together'] : 2,
            'custom_displayed_name' => $row ? (int) $row['custom_displayed_name'] : 0,
            'exists' => (bool) $row,
        ];
    }

    /**
     * @param int $idProduct
     *
     * @return array|null
     */
    protected function getRow($idProduct)
    {
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'accessory_product_setting`
             WHERE `id_product` = ' . (int) $idProduct
        );

        return $row ?: null;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            ['id_product', 'id', 'buy_together', 'custom_displayed_name'],
            ['product_accessory_setting', 'product_accessory_settings']
        );
    }
}
