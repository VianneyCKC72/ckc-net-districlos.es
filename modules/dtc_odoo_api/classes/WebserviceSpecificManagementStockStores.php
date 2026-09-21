<?php
/**
 * Endpoint webservice : magasins CKC Stock Multiboutique (ckcstockstores_stores).
 *
 * GET    /api/stock_stores
 * GET    /api/stock_stores?id_store=X
 * POST   /api/stock_stores
 * PUT    /api/stock_stores
 * DELETE /api/stock_stores?id_store=X
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementStockStores extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'stock_store';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        $this->assertModuleEnabled();

        $method = Tools::strtoupper($this->wsObject->method);

        if ($method === 'GET') {
            $this->payload = $this->handleGet();

            return;
        }
        if ($method === 'POST') {
            $this->payload = $this->handleCreate();

            return;
        }
        if ($method === 'PUT') {
            $this->payload = $this->handleUpdate();

            return;
        }
        if ($method === 'DELETE') {
            $this->payload = $this->handleDelete();

            return;
        }

        $this->methodNotAllowed('stock_stores', 1200);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $input = $this->getParams();
        $idStore = $this->resolveInt($input, ['id_store', 'id']);

        if ($idStore <= 0) {
            $rows = Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'ckcstockstores_stores`
                 ORDER BY `id_store` ASC'
            );

            $stores = [];
            if ($rows) {
                foreach ($rows as $row) {
                    $stores[] = $this->formatStore($row);
                }
            }

            return [
                'action' => 'list',
                'count' => count($stores),
                'stores' => $stores,
            ];
        }

        return array_merge(['action' => 'list'], $this->loadStore($idStore));
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleCreate()
    {
        $input = $this->getParams();
        $fields = $this->buildFields($input, true);

        Db::getInstance()->insert('ckcstockstores_stores', $fields);
        $idStore = (int) Db::getInstance()->Insert_ID();

        return array_merge([
            'action' => 'create',
            'status' => 'ok',
        ], $this->loadStore($idStore));
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleUpdate()
    {
        $input = $this->getParams();
        $idStore = $this->resolveInt($input, ['id_store', 'id']);

        if ($idStore <= 0) {
            throw new WebserviceException('id_store is required for PUT', [1201, 400]);
        }

        $this->assertStoreExists($idStore);
        $fields = $this->buildFields($input, false);

        if (empty($fields)) {
            throw new WebserviceException(
                'Provide at least one field: name, zip_codes, country_codes, iban, bic, carriers',
                [1202, 400]
            );
        }

        Db::getInstance()->update(
            'ckcstockstores_stores',
            $fields,
            'id_store = ' . (int) $idStore
        );

        return array_merge([
            'action' => 'update',
            'status' => 'ok',
        ], $this->loadStore($idStore));
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleDelete()
    {
        $input = $this->getParams();
        $idStore = $this->resolveInt($input, ['id_store', 'id']);

        if ($idStore <= 0) {
            throw new WebserviceException('id_store is required for DELETE', [1203, 400]);
        }

        $this->assertStoreExists($idStore);

        $db = Db::getInstance();
        $db->delete('ckcstockstores_stocks', 'id_store = ' . (int) $idStore);
        $db->delete('ckcstockstores_employee_store', 'id_store = ' . (int) $idStore);
        $db->delete('ckcstockstores_stores', 'id_store = ' . (int) $idStore);
        $db->execute(
            'UPDATE `' . _DB_PREFIX_ . 'orders`
             SET `id_store` = NULL
             WHERE `id_store` = ' . (int) $idStore
        );

        return [
            'action' => 'delete',
            'status' => 'ok',
            'id_store' => $idStore,
        ];
    }

    /**
     * @param array $input
     * @param bool  $isCreate
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function buildFields(array $input, $isCreate)
    {
        $fields = [];

        if ($isCreate || array_key_exists('name', $input)) {
            $name = trim((string) ($input['name'] ?? ''));
            if ($name === '') {
                throw new WebserviceException('name is required', [1204, 400]);
            }
            $fields['name'] = pSQL($name);
        }

        if ($isCreate || array_key_exists('zip_codes', $input)) {
            $fields['zip_codes'] = pSQL((string) ($input['zip_codes'] ?? ''));
        }

        if ($isCreate || array_key_exists('country_codes', $input) || array_key_exists('countries', $input)) {
            $codes = $input['country_codes'] ?? ($input['countries'] ?? ['FR']);
            $fields['country_codes'] = pSQL($this->normalizeCountryCodes($codes));
        } elseif ($isCreate) {
            $fields['country_codes'] = 'FR';
        }

        if (array_key_exists('iban', $input) || $isCreate) {
            $fields['iban'] = pSQL((string) ($input['iban'] ?? ''));
        }
        if (array_key_exists('bic', $input) || $isCreate) {
            $fields['bic'] = pSQL((string) ($input['bic'] ?? ''));
        }

        if (array_key_exists('carriers', $input) || array_key_exists('carrier', $input) || $isCreate) {
            $raw = $input['carriers'] ?? ($input['carrier'] ?? []);
            $fields['carrier'] = pSQL($this->carriersToCsv($raw));
        }

        return $fields;
    }

    /**
     * @param mixed $codes
     *
     * @return string
     */
    protected function normalizeCountryCodes($codes)
    {
        if (is_string($codes)) {
            $codes = preg_split('/\s*,\s*/', $codes, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (!is_array($codes) || empty($codes)) {
            return 'FR';
        }

        $out = [];
        foreach ($codes as $code) {
            $code = Tools::strtoupper(trim((string) $code));
            if ($code !== '') {
                $out[$code] = $code;
            }
        }

        return $out ? implode(',', $out) : 'FR';
    }

    /**
     * @param mixed $raw
     *
     * @return string
     *
     * @throws WebserviceException
     */
    protected function carriersToCsv($raw)
    {
        if (is_string($raw) && $raw !== '') {
            // Already CSV of references, or list of id_carrier
            if (preg_match('/^\d+(,\d+)*$/', trim($raw))) {
                $raw = explode(',', $raw);
            } else {
                return trim($raw);
            }
        }

        $references = $this->toCarrierReferences($raw, 1205);

        return implode(',', $references);
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function formatStore(array $row)
    {
        $countryCodes = [];
        if (!empty($row['country_codes'])) {
            $countryCodes = array_values(array_filter(array_map('trim', explode(',', $row['country_codes']))));
        }

        $carrierRefs = [];
        if (!empty($row['carrier'])) {
            foreach (explode(',', $row['carrier']) as $ref) {
                $ref = (int) trim($ref);
                if ($ref > 0) {
                    $carrierRefs[] = $ref;
                }
            }
        }

        return [
            'id_store' => (int) $row['id_store'],
            'name' => (string) $row['name'],
            'zip_codes' => (string) $row['zip_codes'],
            'country_codes' => $countryCodes,
            'iban' => (string) ($row['iban'] ?? ''),
            'bic' => (string) ($row['bic'] ?? ''),
            'carriers' => $this->referencesToCarrierIds($carrierRefs),
            'carrier_references' => $carrierRefs,
        ];
    }

    /**
     * @param int[] $references
     *
     * @return int[]
     */
    protected function referencesToCarrierIds(array $references)
    {
        if (!$references) {
            return [];
        }

        $ids = [];
        foreach ($references as $ref) {
            $idCarrier = (int) Db::getInstance()->getValue(
                'SELECT `id_carrier` FROM `' . _DB_PREFIX_ . 'carrier`
                 WHERE `id_reference` = ' . (int) $ref . ' AND `deleted` = 0
                 ORDER BY `id_carrier` DESC'
            );
            $ids[] = $idCarrier > 0 ? $idCarrier : (int) $ref;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param int $idStore
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function loadStore($idStore)
    {
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ckcstockstores_stores`
             WHERE `id_store` = ' . (int) $idStore
        );
        if (!$row) {
            throw new WebserviceException('Store ' . (int) $idStore . ' does not exist', [1206, 404]);
        }

        return $this->formatStore($row);
    }

    /**
     * @param int $idStore
     *
     * @throws WebserviceException
     */
    protected function assertStoreExists($idStore)
    {
        if (!$this->rowExists('ckcstockstores_stores', 'id_store', $idStore)) {
            throw new WebserviceException('Store ' . (int) $idStore . ' does not exist', [1206, 404]);
        }
    }

    /**
     * @throws WebserviceException
     */
    protected function assertModuleEnabled()
    {
        if (!Module::isEnabled('ckcstockmultistore')) {
            throw new WebserviceException('ckcstockmultistore module is not enabled', [1207, 503]);
        }
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            ['id_store', 'id', 'name', 'zip_codes', 'country_codes', 'countries', 'iban', 'bic', 'carriers', 'carrier'],
            ['stock_store', 'stock_stores']
        );
    }
}
