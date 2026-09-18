<?php
/**
 * Endpoint webservice : conseils supplémentaires produit (table ckc_product_extra).
 *
 * GET  /api/product_extra_advice?id_product=X
 * PUT  /api/product_extra_advice  (POST accepté aussi)
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementProductExtraAdvice extends DtcOdooApiWsEndpoint
{
    /** @var string[] */
    protected static $editableFields = [
        'extra_advice_title',
        'extra_advice_content',
        'maintenance_title',
        'maintenance_content',
    ];

    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'product_extra_advice';
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

        $this->methodNotAllowed('product_extra_advice', 700);
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
                'description' => 'Read/write product extra advice & maintenance fields (table ckc_product_extra)',
                'methods' => [
                    'GET' => 'Return extra advice for id_product',
                    'PUT' => 'Replace extra advice fields for id_product',
                    'POST' => 'Same as PUT',
                ],
                'fields' => [
                    'id_product' => 'Product id (required)',
                    'extra_advice_title' => 'Expert advice title',
                    'extra_advice_content' => 'Expert advice content (HTML)',
                    'maintenance_title' => 'Maintenance title',
                    'maintenance_content' => 'Maintenance content (HTML)',
                ],
                'notes' => [
                    'Omitted fields are left unchanged on PUT/POST',
                    'Send empty string to clear a field',
                ],
                'example_get' => '/api/product_extra_advice?id_product=793',
                'example_put_json' => '{"id_product":793,"extra_advice_title":"Conseils d\'experts","extra_advice_content":"<p>Contenu</p>","maintenance_title":"Entretien","maintenance_content":"<p>Instructions</p>"}',
            ];
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 709);
        $row = $this->loadRow($idProduct);

        return [
            'action' => 'list',
            'id_product' => $idProduct,
            'extra_advice_title' => $row['extra_advice_title'],
            'extra_advice_content' => $row['extra_advice_content'],
            'maintenance_title' => $row['maintenance_title'],
            'maintenance_content' => $row['maintenance_content'],
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

        if ($idProduct <= 0) {
            throw new WebserviceException('id_product is required', [701, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 709);

        $existing = $this->loadRow($idProduct);
        $fields = [];
        foreach (self::$editableFields as $key) {
            if (array_key_exists($key, $input)) {
                $fields[$key] = pSQL((string) $input[$key], true);
            }
        }

        if (empty($fields)) {
            throw new WebserviceException(
                'At least one field is required: ' . implode(', ', self::$editableFields),
                [702, 400]
            );
        }

        $db = Db::getInstance();

        if ($existing !== null) {
            $db->update('ckc_product_extra', $fields, 'id_product = ' . (int) $idProduct);
        } else {
            $fields['id_product'] = (int) $idProduct;
            $db->insert('ckc_product_extra', $fields);
        }

        $row = $this->loadRow($idProduct);

        return [
            'action' => 'update',
            'status' => 'ok',
            'id_product' => $idProduct,
            'extra_advice_title' => $row['extra_advice_title'],
            'extra_advice_content' => $row['extra_advice_content'],
            'maintenance_title' => $row['maintenance_title'],
            'maintenance_content' => $row['maintenance_content'],
        ];
    }

    /**
     * @param int $idProduct
     *
     * @return array|null
     */
    protected function loadRow($idProduct)
    {
        $row = Db::getInstance()->getRow(
            'SELECT extra_advice_title, extra_advice_content, maintenance_title, maintenance_content
             FROM `' . _DB_PREFIX_ . 'ckc_product_extra`
             WHERE id_product = ' . (int) $idProduct
        );

        if (!$row) {
            return [
                'extra_advice_title' => '',
                'extra_advice_content' => '',
                'maintenance_title' => '',
                'maintenance_content' => '',
            ];
        }

        return $row;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            array_merge(['id_product', 'id'], self::$editableFields),
            ['product_extra_advice']
        );
    }
}
