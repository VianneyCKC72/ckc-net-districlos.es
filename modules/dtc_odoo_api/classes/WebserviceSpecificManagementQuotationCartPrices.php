<?php
/**
 * Endpoint webservice : prix HT des lignes d'un devis (SpecificPrice liés au panier).
 *
 * GET  /api/quotation_cart_prices?id_opartdevis=X
 * PUT  /api/quotation_cart_prices  (POST accepté aussi)
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementQuotationCartPrices extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'quotation_cart_price';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        if (!Module::isEnabled('opartdevis')) {
            throw new WebserviceException('opartdevis module is not enabled', [1800, 503]);
        }
        require_once _PS_MODULE_DIR_ . 'opartdevis/models/OpartQuotation.php';

        $method = Tools::strtoupper($this->wsObject->method);

        if ($method === 'GET') {
            $this->payload = $this->handleGet();

            return;
        }
        if ($method === 'PUT' || $method === 'POST') {
            $this->payload = $this->handleWrite();

            return;
        }

        $this->methodNotAllowed('quotation_cart_prices', 1801);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $input = $this->getParams();
        $id = $this->resolveInt($input, ['id_opartdevis', 'id']);

        if ($id <= 0) {
            return [
                'action' => 'help',
                'description' => 'Read/write negotiated HT prices on quotation cart lines (SpecificPrice)',
                'methods' => [
                    'GET' => 'Return cart lines + specific prices for id_opartdevis',
                    'PUT' => 'Apply specific_price HT on cart lines',
                    'POST' => 'Same as PUT',
                ],
                'example_get' => '/api/quotation_cart_prices?id_opartdevis=42',
                'example_put_json' => '{"id_opartdevis":42,"lines":[{"id_product":793,"id_product_attribute":0,"specific_price":120.50,"specific_qty":1}]}',
            ];
        }

        $quotation = $this->loadQuotation($id);
        $cart = new Cart((int) $quotation->id_cart);
        if (!Validate::isLoadedObject($cart)) {
            throw new WebserviceException('Cart ' . (int) $quotation->id_cart . ' does not exist', [1802, 404]);
        }

        return [
            'action' => 'list',
            'id_opartdevis' => (int) $quotation->id,
            'id_cart' => (int) $cart->id,
            'id_customer' => (int) $quotation->id_customer,
            'lines' => $this->getCartLines($cart),
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
        $id = $this->resolveInt($input, ['id_opartdevis', 'id']);
        if ($id <= 0) {
            throw new WebserviceException('id_opartdevis is required', [1803, 400]);
        }
        if (!array_key_exists('lines', $input) || !is_array($input['lines'])) {
            throw new WebserviceException('lines array is required', [1804, 400]);
        }

        $quotation = $this->loadQuotation($id);
        $cart = new Cart((int) $quotation->id_cart);
        if (!Validate::isLoadedObject($cart)) {
            throw new WebserviceException('Cart ' . (int) $quotation->id_cart . ' does not exist', [1802, 404]);
        }

        $written = 0;
        foreach ($input['lines'] as $i => $line) {
            if (!is_array($line)) {
                throw new WebserviceException('lines[' . $i . '] must be an object', [1805, 400]);
            }
            $idProduct = (int) ($line['id_product'] ?? 0);
            if ($idProduct <= 0) {
                throw new WebserviceException('lines[' . $i . '].id_product is required', [1806, 400]);
            }
            if (!array_key_exists('specific_price', $line)) {
                throw new WebserviceException('lines[' . $i . '].specific_price is required', [1807, 400]);
            }

            $product = [
                'id_product' => $idProduct,
                'id_product_attribute' => (int) ($line['id_product_attribute'] ?? 0),
                'specific_price' => (float) $line['specific_price'],
                'specific_qty' => max(1, (int) ($line['specific_qty'] ?? 1)),
                'ecotax' => (float) ($line['ecotax'] ?? 0),
            ];

            if (!OpartQuotation::addSpecificPrice($product, $cart, (int) $quotation->id_customer)) {
                throw new WebserviceException(
                    'Failed to apply specific price for product ' . $idProduct,
                    [1808, 500]
                );
            }
            ++$written;
        }

        OpartQuotation::$skipSyncReset = true;
        try {
            Db::getInstance()->update(
                'opartdevis',
                ['to_sync' => 1],
                'id_opartdevis = ' . (int) $id
            );
        } finally {
            OpartQuotation::$skipSyncReset = false;
        }

        return [
            'action' => 'update',
            'status' => 'ok',
            'id_opartdevis' => (int) $quotation->id,
            'id_cart' => (int) $cart->id,
            'written' => $written,
            'lines' => $this->getCartLines($cart),
        ];
    }

    /**
     * @param Cart $cart
     *
     * @return array
     */
    protected function getCartLines(Cart $cart)
    {
        $products = $cart->getProducts(true);
        $out = [];

        foreach ($products as $p) {
            $idProduct = (int) $p['id_product'];
            $idAttribute = (int) $p['id_product_attribute'];
            $specific = Db::getInstance()->getRow(
                'SELECT `price`, `from_quantity`
                 FROM `' . _DB_PREFIX_ . 'specific_price`
                 WHERE `id_cart` = ' . (int) $cart->id . '
                   AND `id_product` = ' . $idProduct . '
                   AND `id_product_attribute` = ' . $idAttribute . '
                 ORDER BY `id_specific_price` DESC'
            );

            $out[] = [
                'id_product' => $idProduct,
                'id_product_attribute' => $idAttribute,
                'quantity' => (int) $p['cart_quantity'],
                'price_ht' => (float) $p['price'],
                'specific_price' => $specific ? (float) $specific['price'] : null,
                'specific_qty' => $specific ? (int) $specific['from_quantity'] : null,
                'name' => (string) ($p['name'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param int $id
     *
     * @return OpartQuotation
     *
     * @throws WebserviceException
     */
    protected function loadQuotation($id)
    {
        $q = new OpartQuotation((int) $id);
        if (!Validate::isLoadedObject($q)) {
            throw new WebserviceException('Quotation ' . (int) $id . ' does not exist', [1809, 404]);
        }

        return $q;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            ['id_opartdevis', 'id', 'lines'],
            ['quotation_cart_price', 'quotation_cart_prices']
        );
    }
}
