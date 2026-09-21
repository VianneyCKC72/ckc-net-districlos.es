<?php
/**
 * Endpoint webservice : stocks par magasin (ckcstockstores_stocks).
 *
 * GET  /api/stock_store_stocks?id_store=X
 * GET  /api/stock_store_stocks?id_product=Y
 * PUT  /api/stock_store_stocks  (POST accepté aussi) — upsert 1 ligne ou liste
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementStockStoreStocks extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'stock_store_stock';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        if (!Module::isEnabled('ckcstockmultistore')) {
            throw new WebserviceException('ckcstockmultistore module is not enabled', [1300, 503]);
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

        $this->methodNotAllowed('stock_store_stocks', 1301);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $input = $this->getParams();
        $idStore = $this->resolveInt($input, ['id_store']);
        $idProduct = $this->resolveInt($input, ['id_product', 'id']);

        if ($idStore <= 0 && $idProduct <= 0) {
            return [
                'action' => 'help',
                'description' => 'Read/write store stocks (ckcstockstores_stocks)',
                'methods' => [
                    'GET' => 'List by id_store and/or id_product',
                    'PUT' => 'Upsert one stock line or a stocks[] list',
                    'POST' => 'Same as PUT',
                ],
                'fields' => [
                    'id_store' => 'Store id (required for write)',
                    'id_product' => 'Product id',
                    'id_product_attribute' => 'Combination id (0 = default)',
                    'quantity' => 'Stock quantity',
                    'stocks' => '[{id_product, id_product_attribute, quantity}] bulk upsert for id_store',
                ],
                'example_get' => '/api/stock_store_stocks?id_store=2',
                'example_put_json' => '{"id_store":2,"id_product":35,"id_product_attribute":0,"quantity":12}',
                'example_bulk_json' => '{"id_store":2,"stocks":[{"id_product":35,"id_product_attribute":0,"quantity":12}]}',
            ];
        }

        if ($idStore > 0) {
            $this->assertStoreExists($idStore);
        }
        if ($idProduct > 0) {
            $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 1302);
        }

        $stocks = $this->loadStocks($idStore, $idProduct);

        return [
            'action' => 'list',
            'id_store' => $idStore > 0 ? $idStore : null,
            'id_product' => $idProduct > 0 ? $idProduct : null,
            'count' => count($stocks),
            'stocks' => $stocks,
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
        $idStore = $this->resolveInt($input, ['id_store']);

        if ($idStore <= 0) {
            throw new WebserviceException('id_store is required', [1303, 400]);
        }

        $this->assertStoreExists($idStore);

        $lines = [];
        if (array_key_exists('stocks', $input)) {
            if (!is_array($input['stocks'])) {
                throw new WebserviceException('stocks must be an array', [1304, 400]);
            }
            foreach ($input['stocks'] as $i => $line) {
                if (!is_array($line)) {
                    throw new WebserviceException('stocks[' . $i . '] must be an object', [1305, 400]);
                }
                $lines[] = $this->normalizeStockLine($line, $i);
            }
        } else {
            $lines[] = $this->normalizeStockLine($input, null);
        }

        if (!$lines) {
            throw new WebserviceException('No stock lines to write', [1306, 400]);
        }

        $written = 0;
        foreach ($lines as $line) {
            $this->assertRowExists('product', 'id_product', $line['id_product'], 'Product', 1302);
            $this->upsertStock($idStore, $line['id_product'], $line['id_product_attribute'], $line['quantity']);
            ++$written;
        }

        $stocks = $this->loadStocks($idStore, 0);

        return [
            'action' => 'update',
            'status' => 'ok',
            'id_store' => $idStore,
            'written' => $written,
            'count' => count($stocks),
            'stocks' => $stocks,
        ];
    }

    /**
     * @param array    $line
     * @param int|null $index
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function normalizeStockLine(array $line, $index)
    {
        $prefix = $index !== null ? 'stocks[' . $index . '].' : '';
        $idProduct = (int) ($line['id_product'] ?? ($line['id'] ?? 0));
        if ($idProduct <= 0) {
            throw new WebserviceException($prefix . 'id_product is required', [1307, 400]);
        }
        if (!array_key_exists('quantity', $line)) {
            throw new WebserviceException($prefix . 'quantity is required', [1308, 400]);
        }

        return [
            'id_product' => $idProduct,
            'id_product_attribute' => (int) ($line['id_product_attribute'] ?? 0),
            'quantity' => max(0, (int) $line['quantity']),
        ];
    }

    /**
     * @param int $idStore
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $quantity
     */
    protected function upsertStock($idStore, $idProduct, $idProductAttribute, $quantity)
    {
        $db = Db::getInstance();
        $idStock = (int) $db->getValue(
            'SELECT `id_stock` FROM `' . _DB_PREFIX_ . 'ckcstockstores_stocks`
             WHERE `id_store` = ' . (int) $idStore . '
               AND `id_product` = ' . (int) $idProduct . '
               AND `id_product_attribute` = ' . (int) $idProductAttribute
        );

        if ($idStock > 0) {
            $db->update(
                'ckcstockstores_stocks',
                ['quantity' => (int) $quantity],
                'id_stock = ' . (int) $idStock
            );

            return;
        }

        $db->insert('ckcstockstores_stocks', [
            'id_store' => (int) $idStore,
            'id_product' => (int) $idProduct,
            'id_product_attribute' => (int) $idProductAttribute,
            'quantity' => (int) $quantity,
        ]);
    }

    /**
     * @param int $idStore
     * @param int $idProduct
     *
     * @return array
     */
    protected function loadStocks($idStore, $idProduct)
    {
        $sql = 'SELECT `id_stock`, `id_store`, `id_product`, `id_product_attribute`, `quantity`
                FROM `' . _DB_PREFIX_ . 'ckcstockstores_stocks`
                WHERE 1';

        if ($idStore > 0) {
            $sql .= ' AND `id_store` = ' . (int) $idStore;
        }
        if ($idProduct > 0) {
            $sql .= ' AND `id_product` = ' . (int) $idProduct;
        }

        $sql .= ' ORDER BY `id_store` ASC, `id_product` ASC, `id_product_attribute` ASC';

        $rows = Db::getInstance()->executeS($sql);
        if (!$rows) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id_stock' => (int) $row['id_stock'],
                'id_store' => (int) $row['id_store'],
                'id_product' => (int) $row['id_product'],
                'id_product_attribute' => (int) $row['id_product_attribute'],
                'quantity' => (int) $row['quantity'],
            ];
        }

        return $out;
    }

    /**
     * @param int $idStore
     *
     * @throws WebserviceException
     */
    protected function assertStoreExists($idStore)
    {
        if (!$this->rowExists('ckcstockstores_stores', 'id_store', $idStore)) {
            throw new WebserviceException('Store ' . (int) $idStore . ' does not exist', [1309, 404]);
        }
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            ['id_store', 'id_product', 'id', 'id_product_attribute', 'quantity', 'stocks'],
            ['stock_store_stock', 'stock_store_stocks']
        );
    }
}
