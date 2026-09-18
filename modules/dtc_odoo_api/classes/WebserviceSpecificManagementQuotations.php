<?php
/**
 * Endpoint webservice : devis Op'art (opartdevis) pour sync Odoo.
 *
 * GET    /api/quotations
 * GET    /api/quotations?id_opartdevis=X  (ou /api/quotations/X)
 * PUT    /api/quotations  (POST accepté aussi)
 * DELETE /api/quotations?id_opartdevis=X → soft status=-1
 *
 * Query: to_sync / odoo_imported, status, sendemail=1
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementQuotations extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'quotation';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        $this->bootModule();

        $method = Tools::strtoupper($this->wsObject->method);

        if ($method === 'GET') {
            $this->payload = $this->handleGet();

            return;
        }
        if ($method === 'PUT' || $method === 'POST') {
            $this->payload = $this->handleWrite();

            return;
        }
        if ($method === 'DELETE') {
            $this->payload = $this->handleDelete();

            return;
        }

        $this->methodNotAllowed('quotations', 1700);
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

        if ($id > 0) {
            return array_merge(['action' => 'list'], $this->formatQuotation($this->loadQuotation($id)));
        }

        $toSync = null;
        if (array_key_exists('to_sync', $input) || array_key_exists('odoo_imported', $input)) {
            // Doc Odoo uses odoo_imported=0 for "à sync" (= to_sync=0)
            if (array_key_exists('to_sync', $input)) {
                $toSync = (int) $input['to_sync'];
            } else {
                $toSync = (int) $input['odoo_imported'];
            }
        }

        $status = array_key_exists('status', $input) ? (int) $input['status'] : null;
        $idCustomer = $this->resolveInt($input, ['id_customer']);
        $idShop = $this->resolveInt($input, ['id_shop']);
        $limit = isset($input['limit']) ? max(1, (int) $input['limit']) : 100;
        $offset = isset($input['offset']) ? max(0, (int) $input['offset']) : 0;

        $where = ['1'];
        if ($toSync !== null) {
            $where[] = '`to_sync` = ' . (int) $toSync;
        }
        if ($status !== null) {
            $where[] = '`status` = ' . (int) $status;
        } else {
            // Hide soft-deleted by default
            $where[] = '`status` != ' . (int) OpartQuotation::DELETED;
        }
        if ($idCustomer > 0) {
            $where[] = '`id_customer` = ' . (int) $idCustomer;
        }
        if ($idShop > 0) {
            $where[] = '`id_shop` = ' . (int) $idShop;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'opartdevis`
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY `id_opartdevis` ASC
             LIMIT ' . (int) $offset . ', ' . (int) $limit
        );

        $list = [];
        if ($rows) {
            foreach ($rows as $row) {
                $list[] = $this->formatRow($row);
            }
        }

        return [
            'action' => 'list',
            'count' => count($list),
            'quotations' => $list,
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
            throw new WebserviceException('id_opartdevis is required', [1701, 400]);
        }

        $quotation = $this->loadQuotation($id);
        $updated = [];
        $sendEmail = !empty($input['sendemail'])
            || !empty($this->wsObject->urlFragments['sendemail'])
            || (string) Tools::getValue('sendemail') === '1';

        OpartQuotation::$skipSyncReset = true;

        try {
            if (array_key_exists('status', $input)) {
                $status = (int) $input['status'];
                if (!in_array($status, [-1, 0, 1, 2, 3, 4], true)) {
                    throw new WebserviceException('Invalid status (expected -1..4)', [1702, 400]);
                }

                if ($status === OpartQuotation::VALIDATED) {
                    $quotation->validate();
                } elseif ($status === OpartQuotation::DECLINED) {
                    $quotation->decline();
                } else {
                    $quotation->status = $status;
                    $quotation->save();
                }
                $updated[] = 'status';
            }

            if (array_key_exists('shipping_cost', $input) || array_key_exists('manual_transport', $input)) {
                if (array_key_exists('shipping_cost', $input)) {
                    $quotation->shipping_cost = (float) $input['shipping_cost'];
                    $updated[] = 'shipping_cost';
                }
                if (array_key_exists('manual_transport', $input)) {
                    $quotation->manual_transport = (int) $input['manual_transport'];
                    $updated[] = 'manual_transport';
                } elseif (array_key_exists('shipping_cost', $input)) {
                    $quotation->manual_transport = 1;
                    $updated[] = 'manual_transport';
                }
                $quotation->save();
            }

            if (array_key_exists('name', $input)) {
                $quotation->name = (string) $input['name'];
                $quotation->save();
                $updated[] = 'name';
            }
            if (array_key_exists('message_visible', $input)) {
                $quotation->message_visible = (string) $input['message_visible'];
                $quotation->save();
                $updated[] = 'message_visible';
            }
            if (array_key_exists('legal_information', $input)) {
                $quotation->legal_information = (string) $input['legal_information'];
                $quotation->save();
                $updated[] = 'legal_information';
            }

            // Sync meta (SQL to be sure even if ObjectModel cache stale)
            $syncFields = [];
            if (array_key_exists('odoo_id', $input)) {
                $odooId = $input['odoo_id'];
                $syncFields['odoo_id'] = ($odooId === '' || $odooId === null) ? null : (int) $odooId;
                $updated[] = 'odoo_id';
            }
            if (array_key_exists('to_sync', $input)) {
                $syncFields['to_sync'] = (int) $input['to_sync'];
                $updated[] = 'to_sync';
            } elseif ($updated) {
                // After successful Odoo write, mark synced unless explicitly provided
                $syncFields['to_sync'] = 1;
                $updated[] = 'to_sync';
            }

            if ($syncFields) {
                Db::getInstance()->update(
                    'opartdevis',
                    $syncFields,
                    'id_opartdevis = ' . (int) $id,
                    0,
                    true
                );
            }

            if ($sendEmail) {
                $quotation = $this->loadQuotation($id);
                $quotation->sendToCustomer();
                $updated[] = 'sendemail';
            }
        } finally {
            OpartQuotation::$skipSyncReset = false;
        }

        if (!$updated) {
            throw new WebserviceException(
                'Provide at least one field: status, shipping_cost, manual_transport, odoo_id, to_sync, name, message_visible',
                [1703, 400]
            );
        }

        $quotation = $this->loadQuotation($id);

        return array_merge([
            'action' => 'update',
            'status' => 'ok',
            'updated' => $updated,
            'email_sent' => $sendEmail,
        ], $this->formatQuotation($quotation));
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleDelete()
    {
        $id = $this->resolveInt($this->getParams(), ['id_opartdevis', 'id']);
        if ($id <= 0) {
            throw new WebserviceException('id_opartdevis is required for DELETE', [1704, 400]);
        }

        $quotation = $this->loadQuotation($id);

        OpartQuotation::$skipSyncReset = true;
        try {
            $quotation->status = OpartQuotation::DELETED;
            $quotation->save();
            Db::getInstance()->update(
                'opartdevis',
                ['to_sync' => 0, 'status' => (int) OpartQuotation::DELETED],
                'id_opartdevis = ' . (int) $id
            );
        } finally {
            OpartQuotation::$skipSyncReset = false;
        }

        return [
            'action' => 'delete',
            'status' => 'ok',
            'id_opartdevis' => $id,
            'quotation_status' => (int) OpartQuotation::DELETED,
            'to_sync' => 0,
        ];
    }

    /**
     * @param OpartQuotation $q
     *
     * @return array
     */
    protected function formatQuotation($q)
    {
        return [
            'id_opartdevis' => (int) $q->id,
            'id_shop' => (int) $q->id_shop,
            'id_cart' => (int) $q->id_cart,
            'id_customer' => (int) $q->id_customer,
            'id_lang' => (int) $q->id_lang,
            'name' => (string) $q->name,
            'status' => (int) $q->status,
            'id_order' => $q->id_order ? (int) $q->id_order : null,
            'id_ordered_cart' => $q->id_ordered_cart ? (int) $q->id_ordered_cart : null,
            'shipping_cost' => (float) $q->shipping_cost,
            'manual_transport' => (int) $q->manual_transport,
            'message_visible' => (string) $q->message_visible,
            'legal_information' => (string) $q->legal_information,
            'type' => (int) $q->type,
            'date_add' => $q->date_add,
            'to_sync' => (int) $q->to_sync,
            'odoo_id' => $q->odoo_id !== null && $q->odoo_id !== '' ? (int) $q->odoo_id : null,
        ];
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function formatRow(array $row)
    {
        return [
            'id_opartdevis' => (int) $row['id_opartdevis'],
            'id_shop' => (int) $row['id_shop'],
            'id_cart' => (int) $row['id_cart'],
            'id_customer' => (int) $row['id_customer'],
            'id_lang' => (int) $row['id_lang'],
            'name' => (string) $row['name'],
            'status' => (int) $row['status'],
            'id_order' => !empty($row['id_order']) ? (int) $row['id_order'] : null,
            'id_ordered_cart' => !empty($row['id_ordered_cart']) ? (int) $row['id_ordered_cart'] : null,
            'shipping_cost' => (float) $row['shipping_cost'],
            'manual_transport' => (int) $row['manual_transport'],
            'message_visible' => (string) $row['message_visible'],
            'type' => (int) $row['type'],
            'date_add' => $row['date_add'],
            'to_sync' => (int) ($row['to_sync'] ?? 0),
            'odoo_id' => isset($row['odoo_id']) && $row['odoo_id'] !== null && $row['odoo_id'] !== ''
                ? (int) $row['odoo_id']
                : null,
        ];
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
            throw new WebserviceException('Quotation ' . (int) $id . ' does not exist', [1705, 404]);
        }

        return $q;
    }

    /**
     * @throws WebserviceException
     */
    protected function bootModule()
    {
        if (!Module::isEnabled('opartdevis')) {
            throw new WebserviceException('opartdevis module is not enabled', [1706, 503]);
        }

        require_once _PS_MODULE_DIR_ . 'opartdevis/models/OpartQuotation.php';
        $this->ensureSyncColumns();
    }

    /**
     * Best-effort schema ensure (if opartdevis upgrade not yet run).
     */
    protected function ensureSyncColumns()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $db = Db::getInstance();
        $cols = $db->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'opartdevis`');
        $existing = [];
        if ($cols) {
            foreach ($cols as $col) {
                $existing[$col['Field']] = true;
            }
        }
        if (empty($existing['to_sync'])) {
            $db->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'opartdevis`
                 ADD `to_sync` tinyint(1) NOT NULL DEFAULT 0'
            );
        }
        if (empty($existing['odoo_id'])) {
            $db->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'opartdevis`
                 ADD `odoo_id` int(10) UNSIGNED NULL DEFAULT NULL'
            );
        }
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            [
                'id_opartdevis', 'id', 'to_sync', 'odoo_imported', 'status', 'id_customer', 'id_shop',
                'limit', 'offset', 'shipping_cost', 'manual_transport', 'odoo_id', 'name',
                'message_visible', 'legal_information', 'sendemail',
            ],
            ['quotation', 'quotations']
        );
    }
}
