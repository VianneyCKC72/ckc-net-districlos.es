<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckc_Syncdata_Tx extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_syncdata_tx';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'ckc-net';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Synchronisation de données (TX)');
        $this->description = $this->l('Synchronise clients, commandes, paniers et devis entre deux boutiques.');

        require_once __DIR__.'/classes/CkcSyncQueue.php';
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionObjectCustomerAddAfter')
            && $this->registerHook('actionObjectCustomerUpdateAfter')
            && $this->registerHook('actionObjectCustomerDeleteAfter')
            && $this->registerHook('actionObjectAddressAddAfter')
            && $this->registerHook('actionObjectAddressUpdateAfter')
            && $this->registerHook('actionObjectAddressDeleteAfter')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusUpdate')
            && $this->registerhook('actionOrderSlipAdd')
            && $this->registerHook('actionCartSave')
            && $this->registerHook('actionPaymentCCAdd')
            && $this->installDb()
            && Configuration::updateValue('CKC_SYNCDATA_TX_TARGET_URL', '')
            && Configuration::updateValue('CKC_SYNCDATA_TX_API_TOKEN', '');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDb()
            && Configuration::deleteByName('CKC_SYNCDATA_TX_TARGET_URL')
            && Configuration::deleteByName('CKC_SYNCDATA_TX_API_TOKEN');
    }

    protected function installDb()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sync_logs` (
                `id_sync_log` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `entity_type` VARCHAR(64),
                `entity_id` INT UNSIGNED,
                `action` VARCHAR(32),
                `status` VARCHAR(32),
                `message` TEXT,
                `date_add` DATETIME
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;'
        );
    }

    protected function uninstallDb()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'sync_logs`');
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit_syncdata_config')) {
            Configuration::updateValue('CKC_SYNCDATA_TX_TARGET_URL', Tools::getValue('CKC_SYNCDATA_TX_TARGET_URL'));
            Configuration::updateValue('CKC_SYNCDATA_TX_API_TOKEN', Tools::getValue('CKC_SYNCDATA_TX_API_TOKEN'));
            $output .= $this->displayConfirmation($this->l('Configuration updated'));
        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit_syncdata_config';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value = [
            'CKC_SYNCDATA_TX_TARGET_URL' => Configuration::get('CKC_SYNCDATA_TX_TARGET_URL'),
            'CKC_SYNCDATA_TX_API_TOKEN' => Configuration::get('CKC_SYNCDATA_TX_API_TOKEN'),
        ];

        $form_fields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Paramètres de synchronisation'),
                    'icon' => 'icon-share-alt'
                ],
                'description' => $this->l('Configurez l’URL de la boutique distante et le token d’authentification.'),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('URL de la boutique cible'),
                        'name' => 'CKC_SYNCDATA_TX_TARGET_URL',
                        'required' => true,
                        'desc' => $this->l('Exemple : https://ma-nouvelle-boutique.fr')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Token API'),
                        'name' => 'CKC_SYNCDATA_TX_API_TOKEN',
                        'required' => true,
                        'desc' => $this->l('Token défini dans la boutique distante pour l’autorisation.')
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer la configuration'),
                    'class' => 'btn btn-primary'
                ]
            ]
        ];

        $output .= $helper->generateForm([$form_fields]);

        $logs = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'sync_logs` ORDER BY date_add DESC LIMIT 50');
        $output .= '<div class="panel">';
        $output .= '<h3><i class="icon-random"></i> '.$this->l('Derniers logs de synchronisation').'</h3>';
        $output .= '<div class="table-responsive">';
        $output .= '<table class="table table-bordered table-hover">';
        $output .= '<thead class="thead-default">';
        $output .= '<tr>';
        $output .= '<th>#</th>';
        $output .= '<th>'.$this->l('Type').'</th>';
        $output .= '<th>'.$this->l('ID').'</th>';
        $output .= '<th>'.$this->l('Action').'</th>';
        $output .= '<th>'.$this->l('Statut').'</th>';
        $output .= '<th>'.$this->l('Message').'</th>';
        $output .= '<th>'.$this->l('Date').'</th>';
        $output .= '</tr>';
        $output .= '</thead><tbody>';

        foreach ($logs as $log) {
            $output .= '<tr>';
            $output .= '<td>'.(int)$log['id_sync_log'].'</td>';
            $output .= '<td>'.htmlspecialchars($log['entity_type']).'</td>';
            $output .= '<td>'.(int)$log['entity_id'].'</td>';
            $output .= '<td>'.htmlspecialchars($log['action']).'</td>';
            $output .= '<td><span class="label label-'.($log['status'] == 'success' ? 'success' : 'danger').'">'.htmlspecialchars($log['status']).'</span></td>';
            $output .= '<td>'.htmlspecialchars($log['message']).'</td>';
            $output .= '<td>'.htmlspecialchars($log['date_add']).'</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table></div></div>';

        return $output;
    }

    public function hookActionObjectCustomerAddAfter($params)
    {
        $this->syncToRemote('customer', $params['object']->id, 'create');
    }

    public function hookActionObjectCustomerUpdateAfter($params)
    {
        $this->syncToRemote('customer', $params['object']->id, 'update');
    }

    public function hookActionObjectCustomerDeleteAfter($params)
    {
        $this->syncToRemote('customer', $params['object']->id, 'delete');
    }

    public function hookActionObjectAddressAddAfter($params)
    {
        $this->syncToRemote('address', $params['object']->id, 'create');
    }

    public function hookActionObjectAddressUpdateAfter($params)
    {
        $this->syncToRemote('address', $params['object']->id, 'update');
    }

    public function hookActionObjectAddressDeleteAfter($params)
    {
        $this->syncToRemote('address', $params['object']->id, 'delete');
    }

    public function hookActionValidateOrder($params)
    {
        $this->syncToRemote('order', $params['order']->id, 'create');
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $order = new Order((int)$params['id_order']);
        if (Validate::isLoadedObject($order)) {
            $this->syncToRemote('order_state', $order->id, 'update', $params['newOrderStatus']);
        }
    }

    public function hookActionCartSave($params)
    {
        $this->syncToRemote('cart', $params['cart']->id, 'update');
    }

    public function hookActionOrderSlipAdd($params)
    {
        $this->syncToRemote('order_slip', $params['order']->id, 'create', $params);
    }

    public function hookActionPaymentCCAdd($params)
    {
        $this->syncToRemote('order_payment', $params['paymentCC']->id, 'create', $params);
    }

    public function hookActionObjectOrderCarrierAddAfter($params)
    {
        $this->syncToRemote('order_carrier', $params['object']->id, 'create', $params);
    }

    private function syncToRemote($type, $id, $action, $additionnalData = null)
    {
        $url = rtrim(Configuration::get('CKC_SYNCDATA_TX_TARGET_URL'), '/');
        $token = Configuration::get('CKC_SYNCDATA_TX_API_TOKEN');

        $object = null;
        $datas = [];
        $payload = null;

        switch ($type) {
            case 'customer':
                $object = new Customer($id);
                if (Validate::isLoadedObject($object)) {
                    $datas = [];
                    $datas['customer'] = $object->getFields();

                    // Récupération des groupes associés
                    $groups = Db::getInstance()->executeS('
                        SELECT id_group 
                        FROM '._DB_PREFIX_.'customer_group 
                        WHERE id_customer = '.(int)$object->id
                    );

                    $datas['customer_groups'] = array_map(function ($g) {
                        return (int)$g['id_group'];
                    }, $groups);
                }
                break;
            case 'address':
                $object = new Address($id);
                if (Validate::isLoadedObject($object)) {
                    $datas = [];
                    $datas['address'] = $object->getFields();
                }
                break;
            case 'order':
                $object = new Order($id);
                if (Validate::isLoadedObject($object)) {
                    $datas = [];
                    // Commande principale
                    $datas['order'] = $object->getFields();

                    // Détails de la commande
                    $datas['order_details'] = $object->getOrderDetailList();

                    // Historique
                    $datas['order_history'] = Db::getInstance()->executeS(
                        'SELECT * FROM '._DB_PREFIX_.'order_history WHERE id_order = '.(int)$object->id
                    );

                    // Invoices
                    $datas['order_invoice'] = Db::getInstance()->executeS(
                        'SELECT * FROM '._DB_PREFIX_.'order_invoice WHERE id_order = '.(int)$object->id
                    );

                    // Payments
                    $datas['order_payment'] = Db::getInstance()->executeS(
                        'SELECT * FROM '._DB_PREFIX_.'order_payment WHERE order_reference = "'.pSQL($object->reference).'"'
                    );

                    // Carriers
                    $datas['order_carrier'] = Db::getInstance()->executeS(
                        'SELECT * FROM '._DB_PREFIX_.'order_carrier WHERE id_order = '.(int)$object->id
                    );

                    // Cart rules (réductions)
                    $datas['order_cart_rule'] = Db::getInstance()->executeS(
                        'SELECT * FROM '._DB_PREFIX_.'order_cart_rule WHERE id_order = '.(int)$object->id
                    );
                }
                break;
            case 'order_state':
                $object = new Order($id);
                if (Validate::isLoadedObject($object)) {
                    $datas = $object->getFields();

                    $orderHistory = Db::getInstance()->executeS('
                        SELECT * FROM '._DB_PREFIX_.'order_history
                        WHERE id_order = '.(int)$object->id.'
                        ORDER BY date_add DESC
                    ');

                    $datas['order_history'] = $orderHistory;
                    $datas['new_order_state'] = is_object($additionnalData) ? $additionnalData->id : (int)$additionnalData;
                }
                break;
            case 'order_slip':
                $object = new Order($id);
                if (Validate::isLoadedObject($object)) {
                    $datas = [];
                    // Commande principale
                    $datas['order'] = $object->getFields();

                    // Order slip
                    $datas['order_slip'] = Db::getInstance()->executeS(
                        'SELECT * FROM '._DB_PREFIX_.'order_slip WHERE id_order = "'.pSQL($object->id).'"'
                    );

                    $datas['order_details'] = Db::getInstance()->executeS(
                        'SELECT * FROM '._DB_PREFIX_.'order_detail WHERE id_order = "'.pSQL($object->id).'"'
                    );

                    $orderSlipIdsRaw = Db::getInstance()->executeS(
                        'SELECT id_order_slip FROM '._DB_PREFIX_.'order_slip WHERE id_order = '.(int)$object->id
                    );
                    $orderSlipIds = array_map('intval', array_column($orderSlipIdsRaw, 'id_order_slip'));

                    $datas['order_slip_detail'] = !empty($orderSlipIds)
                        ? Db::getInstance()->executeS(
                            'SELECT * FROM '._DB_PREFIX_.'order_slip_detail WHERE id_order_slip IN ('.implode(',', $orderSlipIds).')'
                        )
                        : [];

                }

                break;
            case 'order_payment':
                $object = new OrderPayment($id);
                if (Validate::isLoadedObject($object)) {
                    $datas = [];
                    // Paiement
                    $datas['order_payment'] = Db::getInstance()->executeS(
                        'SELECT * FROM '._DB_PREFIX_.'order_payment WHERE id_order_payment = "'.pSQL($object->id).'"'
                    );
                }

                break;
            case 'order_carrier':
                $object = new OrderCarrier($id);
                if (Validate::isLoadedObject($object)) {
                    $datas = [];
                    // Paiement
                    $datas['order_carrier'] = Db::getInstance()->executeS(
                        'SELECT * FROM '._DB_PREFIX_.'order_carrier WHERE id_order_carrier = "'.pSQL($object->id).'"'
                    );
                }

                break;
            case 'cart':
                $object = new Cart($id);
                if (Validate::isLoadedObject($object)) {
                    $datas = $object->getFields();
                    $datas['products'] = Db::getInstance()->executeS(
                        'SELECT cp.id_product, cp.id_product_attribute, cp.quantity, p.id_tax_rules_group
                     FROM '._DB_PREFIX_.'cart_product cp
                     LEFT JOIN '._DB_PREFIX_.'product p ON (p.id_product = cp.id_product)
                     WHERE cp.id_cart = '.(int)$object->id
                    );
                    $datas['cart_cart_rule'] = Db::getInstance()->executeS(
                        'SELECT * FROM '._DB_PREFIX_.'cart_cart_rule WHERE id_cart = '.(int)$object->id
                    );

                    $payload = json_encode([
                        'entity_type' => $type,
                        'entity_id' => $id,
                        'action' => $action,
                        'datas' => $datas
                    ]);

                    // Enregistrement dans la queue avec ou sans payload
                    $queue = new CkcSyncQueue();
                    $queue->type = $type;
                    $queue->entity_id = $id;
                    $queue->action = $action;
                    $queue->payload = $payload;
                    $queue->add();
                }
                break;
        }

        if ($type === 'cart') {
            // La synchro du panier est déjà dans la queue, on ne fait pas de cURL ;)
            return;
        }

        if ($object && Validate::isLoadedObject($object) && empty($datas)) {
            $datas = $object->getFields();
        }

        if (empty($datas)) {
            $this->logSync($type, $id, $action, 'error', 'Object not found or data empty');
            return;
        }

        $payload = [
            'entity_type' => $type,
            'entity_id' => $id,
            'action' => $action,
            'datas' => $datas
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $status = $response ? 'success' : 'error';
        $message = $response ?: $error;

        $this->logSync($type, $id, $action, $status, $message);
    }

    private function logSync($type, $id, $action, $status, $message = '')
    {
        Db::getInstance()->insert('sync_logs', [
            'entity_type' => pSQL($type),
            'entity_id' => (int)$id,
            'action' => pSQL($action),
            'status' => pSQL($status),
            'message' => pSQL($message),
            'date_add' => date('Y-m-d H:i:s'),
        ]);
    }
}
