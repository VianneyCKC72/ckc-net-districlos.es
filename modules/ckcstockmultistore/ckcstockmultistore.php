<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */


use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DataColumn;
use PrestaShop\PrestaShop\Core\Exception\NotFoundException;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckcstockmultistore extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ckcstockmultistore';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'ckc-net';
        $this->need_instance = 0;
        $this->ps_version  = (bool)version_compare(_PS_VERSION_, '1.7', '>=');
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('CKC Stock Multiboutique');
        $this->description = $this->l('Simplifiez la gestion multi-boutiques et des stocks sur Prestashop. Créez, modifiez et rattachez vos boutiques tout en optimisant le suivi des stocks. ');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    private function installModuleTab($tabClass, $parent, $tabName)
    {
        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'tab WHERE module = "' . pSQL($this->name) . '"');

        $tab             = new Tab();
        $tab->active     = 1;
        $tab->class_name = $tabClass;
        $tab->id_parent  = (int)Tab::getIdFromClassName($parent);
        $tab->position   = Tab::getNewLastPosition($tab->id_parent);
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $this->trans($tabName, [], 'Modules.Ckcstockmultistore.Admin', $lang['locale']);
        }
        // $tab->icon   = 'icon-AdminCkcstockmultistoreTab';
        $tab->module = $this->name;


        $tab->add();
        $tab_id = $tab->id;

        $tabvalue = [];

        $stores = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores');

        $tabs = Tab::getTabs(1, $tab->id);
        foreach ($tabs as $tab) {
            if ($tab['module'] == 'ckcstockmultistore') {
                $tab = new Tab($tab['id_tab']);
                $tab->delete();
            }
        }

        foreach ($stores as $key => $store) {
            $tabvalue[$key]['class_name'] = 'AdminCkcstockmultistoreSubTab';
            $tabvalue[$key]['id_parent'] = $tab_id;
            $tabvalue[$key]['module'] = 'ckcstockmultistore';
            $tabvalue[$key]['name'] = $store['name'];
            $tabvalue[$key]['idStore'] =  $store['id_store'];
        }

        foreach ($tabvalue as $tab) {
            $newtab = new Tab();
            $newtab->active     = 1;
            $newtab->class_name = $tab['class_name'];
            $newtab->id_parent = $tab['id_parent'];
            $newtab->position   = Tab::getNewLastPosition($newtab->id_parent);
            $newtab->module = $tab['module'];
            foreach (Language::getLanguages() as $lang) {
                $newtab->name[$lang['id_lang']] = $this->trans($tab['name'], [], 'Modules.Ckcstockmultistore.Admin', $lang['locale']);
            }
            $newtab->save();
        }

        return true;
    }

    private function uninstallModuleTab($tabClass)
    {
        $tab = new Tab((int)Tab::getIdFromClassName($tabClass));

        return $tab->delete();
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CKCSTOCKMULTISTORE_LIVE_MODE', false);

        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayAdminOrderMain') &&
            $this->registerHook('actionOrderGridDefinitionModifier') &&
            $this->registerHook('actionOrderGridQueryBuilderModifier') &&
            $this->installModuleTab('AdminCkcstockmultistoreTab', 'SELL', 'CKC Stock Multiboutique') &&
            $this->registerHook('actionOrderStatusUpdate') &&
            $this->registerHook('displayAdminProductsMainStepLeftColumnBottom') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionEmployeeFormBuilderModifier') &&
            $this->registerHook('actionBeforeCreateEmployeeFormHandler') &&
            $this->registerHook('actionAfterCreateEmployeeFormHandler') &&
            $this->registerHook('actionAfterUpdateEmployeeFormHandler') &&
            $this->registerHook('displayAdminCustomers') &&
            $this->registerHook('actionCustomerGridDefinitionModifier') &&
            $this->registerHook('actionCustomerGridQueryBuilderModifier') &&
            $this->registerHook('actionCustomerThreadsGridDefinitionModifier') &&
            $this->registerHook('actionCustomerThreadsGridQueryBuilderModifier');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CKCSTOCKMULTISTORE_LIVE_MODE');

        return parent::uninstall()
            && $this->uninstallModuleTab('AdminCkcstockmultistoreTab');
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $moduleAdminLink = $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]);
        $searchLink      = $moduleAdminLink . '&page=store1&';

        $page = Tools::getValue('page');
        if (!empty($page)) {
            $currentPage = Tools::getValue('page');
        }
        else $currentPage = 'productLinks';
        /**
         * If values have been submitted in the form, process.
         */

        if (((bool)Tools::isSubmit('submitCkcstockmultistoreModule')) == true || ((bool)Tools::isSubmit('submitCkcStockMultistoreStore')) == true) {
            $this->postProcess();
        }

        $boutiquesSelected = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores');

        $boutiques = [];

        foreach ($boutiquesSelected as $key => $boutique) {
            $products = Db::getInstance()->ExecuteS('SELECT id_product, id_product_attribute, id_store, quantity  FROM ' . _DB_PREFIX_ . 'ckcstockstores_stocks WHERE id_store = '.$boutique['id_store']);
            $boutiques[$key] = $boutique;
            $boutiques[$key]['products'] = $products;
        }

        $this->context->smarty->assign( array(
            'module_dir' => $this->_path,
            'boutiques' => $boutiques,
            'currentPage' => $currentPage,
            'module_name' => $this->name,
            'moduleAdminLink' => $moduleAdminLink,
            'isPs17' => $this->ps_version,
        ));


        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        $output .= $this->renderStoreForm();
        $output .= $this->renderForm();

        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCkcstockmultistoreModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => [
                'CKCSTOCKMULTISTORE_ORDER_STATUS[]' => explode(';', Configuration::get('CKCSTOCKMULTISTORE_ORDER_STATUS')),
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Paramètres'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Statut de commande pour la déduction du stock'),
                        'name' => 'CKCSTOCKMULTISTORE_ORDER_STATUS[]',
                        'multiple' => true,
                        'options' => array(
                            'query' => OrderState::getOrderStates($this->context->language->id),
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ),
                        'desc' => $this->l('Choisissez le statut de commande déclenchant la mise à jour du stock.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Enregistrer'),
                ),
            ),
        );
    }

    protected function renderStoreForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = 'ckcstockstores_stores';
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = 'id_store';
        $helper->submit_action = 'submitCkcStockMultistoreStore';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $store_id = (int) Tools::getValue('id_store');
        $store = $this->getStore($store_id);

        $helper->tpl_vars = array(
            'fields_value' => array(
                'id_store' => $store['id_store'] ?? '',
                'name' => $store['name'] ?? '',
                'zip_codes' => $store['zip_codes'] ?? '',
                'iban' => $store['iban'] ?? '',
                'bic' => $store['bic'] ?? '',
                'carrier' => $store['carrier'] ?? '',
            ),
        );

        return $helper->generateForm(array($this->getStoreFormConfig()));
    }

    protected function getStore($id_store)
    {
        if (!$id_store) {
            return null;
        }

        return Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ckcstockstores_stores` WHERE `id_store` = ' . (int) $id_store);
    }


    protected function getStoreFormConfig()
    {
        $carriers = Carrier::getCarriers(Configuration::get('PS_LANG_DEFAULT'), true);

        $descCarrier = '';

        foreach ($carriers as $carrier) {
            $descCarrier .= $carrier['name'] .' - <b>'.$carrier['id_reference'].'</b><br/>';
        }

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Ajouter / Modifier une boutique'),
                    'icon' => 'icon-store',
                ),
                'input' => array(
                    array(
                        'type' => 'hidden',
                        'name' => 'id_store',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Nom de la boutique'),
                        'name' => 'name',
                        'size' => 50,
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Codes postaux'),
                        'name' => 'zip_codes',
                        'desc' => $this->l('Liste des codes postaux séparés par une virgule'),
                        'size' => 50,
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('IBAN'),
                        'name' => 'iban',
                        'size' => 50,
                        'required' => false,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('BIC'),
                        'name' => 'bic',
                        'size' => 50,
                        'required' => false,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Transporteur'),
                        'name' => 'carrier',
                        'size' => 50,
                        'required' => false,
                        'desc' => $descCarrier,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Enregistrer'),
                ),
            ),
        );
    }


    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CKCSTOCKMULTISTORE_ORDER_STATUS' => Configuration::get('CKCSTOCKMULTISTORE_ORDER_STATUS', []),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('submitCkcStockMultistoreStore')) {
            $id_store = (int) Tools::getValue('id_store');
            $name = Tools::getValue('name');
            $zip_codes = Tools::getValue('zip_codes');

            if ($id_store) {
                Db::getInstance()->update('ckcstockstores_stores', array(
                    'name' => pSQL($name),
                    'zip_codes' => pSQL($zip_codes),
                    'iban' => pSQL(Tools::getValue('iban')),
                    'bic' => pSQL(Tools::getValue('bic')),
                    'carrier' => pSQL(Tools::getValue('carrier')),
                ), 'id_store = ' . (int) $id_store);
            } else {
                Db::getInstance()->insert('ckcstockstores_stores', array(
                    'name' => pSQL($name),
                    'zip_codes' => pSQL($zip_codes),
                    'iban' => pSQL(Tools::getValue('iban')),
                    'bic' => pSQL(Tools::getValue('bic')),
                    'carrier' => pSQL(Tools::getValue('carrier')),
                ));

                $this->installModuleTab('AdminCkcstockmultistoreTab', 'SELL', 'CKC Stock Multiboutique');
            }
        }else{
            $orderStatuses = Tools::getValue('CKCSTOCKMULTISTORE_ORDER_STATUS');
            if (is_array($orderStatuses)) {
                Configuration::updateValue('CKCSTOCKMULTISTORE_ORDER_STATUS', implode(';', array_map('intval', $orderStatuses)));
            } else {
                Configuration::updateValue('CKCSTOCKMULTISTORE_ORDER_STATUS', '');
            }
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {

        $boutiquesSelected = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores');

        $boutiques = [];

        foreach ($boutiquesSelected as $key => $boutique) {
            $linkName = Db::getInstance()->executeS('SELECT `id_tab` FROM ' . _DB_PREFIX_ . 'tab_lang WHERE `name` LIKE "%'.$boutique['name'].'%"');
            $boutiques[$key] = $boutique;
            $boutiques[$key]['idTab'] = $linkName;
        }

        $this->context->smarty->assign( array(
            'boutiques' => $boutiques
        ));
        $this->context->controller->addJS($this->_path.'views/js/back.js');

        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/vue.min.js');
            $this->context->controller->addJS($this->_path.'views/js/menu.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
        $this->context->controller->addCSS($this->_path.'views/css/admin-orders.css');

    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayAdminProductsMainStepLeftColumnBottom($params)
    {
        $id_product = (int) $params['id_product'];

        $stocks = Db::getInstance()->executeS(
            'SELECT s.id_store, s.name, stock.id_product_attribute, stock.quantity 
        FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores s
        LEFT JOIN ' . _DB_PREFIX_ . 'ckcstockstores_stocks stock
        ON s.id_store = stock.id_store AND stock.id_product = ' . (int) $id_product
        );

        $this->context->smarty->assign(array(
            'stocks' => $stocks,
            'module_dir' => $this->_path
        ));

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/product_stock.tpl');
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $id_order = (int) $params['id_order'];
        $new_status = (int) $params['newOrderStatus']->id;

        $trigger_statuses = explode(';', Configuration::get('CKCSTOCKMULTISTORE_ORDER_STATUS'));

        if (!in_array((string) $new_status, $trigger_statuses)) {
            PrestaShopLogger::addLog('Order status is not a trigger status: '.$new_status.' - Allowed: '.implode(', ', $trigger_statuses), 3, null, 'Ckcstockmultistore', $id_order, true);
            return;
        }

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            PrestaShopLogger::addLog('Order not found', 3, null, 'Ckcstockmultistore', $id_order, true);
            return;
        }

        // Récupérer l'adresse de livraison
        $address = new Address($order->id_address_delivery);
        if (!Validate::isLoadedObject($address)) {
            PrestaShopLogger::addLog('Address not found', 3, null, 'Ckcstockmultistore', $id_order, true);
            return;
        }

        $customer_zip_code = pSQL($address->postcode);

        $stores = Db::getInstance()->executeS('SELECT id_store, zip_codes, carrier FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores WHERE carrier IS NOT NULL OR carrier != ""');

        $id_store = null;

        $carrier = new Carrier((int)$order->id_carrier);

        foreach ($stores as $store) {
            $carrierStore = explode(',', $store['carrier']);

            if (in_array($carrier->id_reference, $carrierStore)) {
                $id_store = $store['id_store'];
            }
        }

        if (!$id_store) {
            $id_store = null;

            foreach ($stores as $store) {
                $zip_codes = explode(',', $store['zip_codes']);

                foreach ($zip_codes as $zip_code) {
                    $zip_code = trim($zip_code);

                    if ($zip_code === $customer_zip_code || (strpos($zip_code, '*') !== false && preg_match('/^' . str_replace('*', '.*', $zip_code) . '$/', $customer_zip_code))) {
                        $id_store = (int) $store['id_store'];
                        break 2;
                    }
                }
            }

        }

        if (!$id_store) {
            PrestaShopLogger::addLog('No store found for zip code ' . $customer_zip_code, 3, null, 'Ckcstockmultistore', $id_order, true);
            return;
        }

        Db::getInstance()->execute(
            'UPDATE ' . _DB_PREFIX_ . 'orders 
            SET id_store = ' . (int) $id_store . ' 
            WHERE id_order = ' . (int) $id_order
        );


        $products = $order->getProducts();

        foreach ($products as $product) {
            $id_product = (int) $product['product_id'];
            $id_product_attribute = (int) $product['product_attribute_id'];
            $quantity = (int) $product['product_quantity'];

            $stockExists = Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'ckcstockstores_stocks 
            WHERE id_store = ' . $id_store . ' 
            AND id_product = ' . $id_product . ' 
            AND id_product_attribute = ' . $id_product_attribute
            );

            if ($stockExists) {
                Db::getInstance()->execute(
                    'UPDATE ' . _DB_PREFIX_ . 'ckcstockstores_stocks 
                SET quantity = GREATEST(quantity - ' . $quantity . ', 0) 
                WHERE id_store = ' . $id_store . ' 
                AND id_product = ' . $id_product . ' 
                AND id_product_attribute = ' . $id_product_attribute
                );
            } else {
                $psStock = StockAvailable::getQuantityAvailableByProduct($id_product, $id_product_attribute);

                Db::getInstance()->insert('ckcstockstores_stocks', array(
                    'id_store' => $id_store,
                    'id_product' => $id_product,
                    'id_product_attribute' => $id_product_attribute,
                    'quantity' => max(0, $psStock - $quantity),
                ));
            }

            StockAvailable::updateQuantity($id_product, $id_product_attribute, -$quantity);
        }
    }

    public function hookDisplayAdminOrderMain($params)
    {
        $id_order = (int) $params['id_order'];
        $order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = ' . (int) $id_order);

        if (!$order['id_store']) {
            return;
        }

        $store = Db::getInstance()->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores WHERE id_store = ' . (int) $order['id_store']
        );

        if (!$store) {
            return;
        }

        $this->context->smarty->assign(array(
            'store_name' => $store['name'],
            'store_id' => $store['id_store']
        ));

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/order_store.tpl');
    }

    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        /** @var QueryBuilder $qb */
        $qb = $params['search_query_builder'];
        /** @var QueryBuilder $qbCount */
        $qbCount = $params['count_query_builder'];
        /** @var OrderFilters $filters */
        $filters = $params['search_criteria'];

        $employeeId = (int) $this->context->employee->id;
        $allowedStoreIds = $this->employeeStoreIds($employeeId);

        // Jointure pour récupérer le nom (utile pour la colonne et tris)
        $aliasStore = 'ss';
        $qb->leftJoin('o', _DB_PREFIX_.'ckcstockstores_stores', $aliasStore, "$aliasStore.id_store = o.id_store");
        $qbCount->leftJoin('o', _DB_PREFIX_.'ckcstockstores_stores', $aliasStore, "$aliasStore.id_store = o.id_store");

        // Sélectionner le nom pour la DataColumn/tri
        $qb->addSelect("$aliasStore.name AS store_name");

        // Sécurité : restreindre aux boutiques autorisées de l’employé connecté
        if (!empty($allowedStoreIds)) {
            $qb->andWhere('o.id_store IN (:allowedStores)')
                ->setParameter('allowedStores', $allowedStoreIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

            $qbCount->andWhere('o.id_store IN (:allowedStores)')
                ->setParameter('allowedStores', $allowedStoreIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        } else {
            // Aucune boutique autorisée => aucune commande
            $qb->andWhere('1=0');
            $qbCount->andWhere('1=0');
            return;
        }

        // Filtre saisi dans l’UI
        $allFilters = $filters->getFilters();
        if (!empty($allFilters['ckc_stores'])) {
            $selected = array_map('intval', (array) $allFilters['ckc_stores']);
            // Ne garder que celles autorisées
            $selected = array_values(array_intersect($selected, $allowedStoreIds));

            if (!empty($selected)) {
                $qb->andWhere('o.id_store IN (:selectedStores)')
                    ->setParameter('selectedStores', $selected, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

                $qbCount->andWhere('o.id_store IN (:selectedStores)')
                    ->setParameter('selectedStores', $selected, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
            }
        }

        if (!empty($allFilters['group'])) {

            $selectedGroups = array_map('intval', (array) $allFilters['group']);

            if (!empty($selectedGroups)) {
                $qb->andWhere('cu.id_default_group IN (:selectedGroups)')
                    ->setParameter('selectedGroups', $selectedGroups, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

                $qbCount->andWhere('cu.id_default_group IN (:selectedGroups)')
                    ->setParameter('selectedGroups', $selectedGroups, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
            }
        }
    }

    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        $definition = $params['definition'];

        // ===== COLONNE "Boutique" =====
        $columns = $definition->getColumns();
        $storeColExists = false;
        foreach ($columns as $col) {
            if (method_exists($col, 'getId') && $col->getId() === 'store_name') {
                $storeColExists = true;
                break;
            }
        }

        if (!$storeColExists) {
            $columns->addAfter('date_add',
                (new DataColumn('store_name'))
                    ->setName($this->l('Boutique'))
                    ->setOptions([
                        'field' => 'store_name',
                        'sortable' => true,
                    ])
            );
        }

        // ===== FILTRE "Boutiques" (multiple) =====
        $employeeId = (int) $this->context->employee->id;
        $allowedStoreIds = $this->employeeStoreIds($employeeId);

        $where = !empty($allowedStoreIds)
            ? ' WHERE id_store IN ('.implode(',', array_map('intval', $allowedStoreIds)).')'
            : ' WHERE 1=0';

        $rows = \Db::getInstance()->executeS('
        SELECT id_store, name
        FROM '._DB_PREFIX_.'ckcstockstores_stores
        '.$where.'
        ORDER BY name ASC
    ');

        $choices = [];
        foreach ($rows as $r) {
            $choices[$r['name']] = (int) $r['id_store'];
        }

        $filters = $definition->getFilters();
        $storeFilterExists = false;
        foreach ($filters as $filter) {
            // Sur certaines versions, l'ID du filtre est porté par getName()
            if (method_exists($filter, 'getName') && $filter->getName() === 'ckc_stores') {
                $storeFilterExists = true;
                break;
            }
        }

        if (!$storeFilterExists) {
            $filters->add(
                (new Filter('ckc_stores', ChoiceType::class))
                    ->setTypeOptions([
                        'choices' => $choices,
                        'multiple' => true,
                        'expanded' => false,
                        'required' => false,
                        'choice_translation_domain' => false,
                    ])
                    ->setAssociatedColumn('store_name')
            );

            $choicesGroup = ['Défaut' => 3, 'Professionnels' => 10];

            $filters->add(
                (new Filter('group', ChoiceType::class))
                    ->setTypeOptions([
                        'choices' => $choicesGroup,
                        'multiple' => true,
                        'expanded' => false,
                        'required' => false,
                        'choice_translation_domain' => false,
                    ])
                    ->setAssociatedColumn('group')
            );
        }
    }

    public function hookActionEmployeeFormBuilderModifier(array $params)
    {
        /** @var \Symfony\Component\Form\FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];
        $employeeId = (int) $params['id'];

        $stores = \Db::getInstance()->executeS('
        SELECT id_store, name
        FROM '._DB_PREFIX_.'ckcstockstores_stores
        ORDER BY name ASC
    ');

        $choices = [];
        foreach ($stores as $s) {
            $choices[$s['name']] = (int) $s['id_store'];
        }

        $selected = [];
        if ($employeeId) {
            $rows = \Db::getInstance()->executeS('
            SELECT id_store
            FROM '._DB_PREFIX_.'ckcstockstores_employee_store
            WHERE id_employee='.(int)$employeeId
            );
            $selected = array_map('intval', array_column($rows, 'id_store'));
        }

        $params['data']['ckc_stores'] = $selected;

        $formBuilder->add('ckc_stores', ChoiceType::class, [
            'label' => $this->l('Magasins autorisés'),
            'choices' => $choices,
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'mapped' => false,
            'placeholder' => false,
            'data' => $selected,
            'help' => $this->l('Sélectionnez les stores auxquels cet employé a accès.'),
        ]);
    }

    private function saveEmployeeStores(int $employeeId, array $storeIds): void
    {
        $db = \Db::getInstance();
        $db->delete('ckcstockstores_employee_store', 'id_employee='.(int)$employeeId);

        $values = [];
        foreach (array_unique(array_map('intval', $storeIds)) as $idStore) {
            $values[] = '('.(int)$employeeId.','.(int)$idStore.')';
        }

        if ($values) {
            $sql = 'INSERT INTO '._DB_PREFIX_.'ckcstockstores_employee_store (id_employee, id_store) VALUES '.implode(',', $values);
            $db->execute($sql);
        }
    }

    private function extractSubmittedStoreIds(array $params): array
    {
        if (isset($params['form_data']['ckc_stores'])) {
            $ids = (array) $params['form_data']['ckc_stores'];
            return array_values(array_unique(array_map('intval', $ids)));
        }

        $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        $request = $container->get('request_stack')->getCurrentRequest();

        $root = 'employee';

        $payload = $request->request->all();
        $ids = $payload[$root]['ckc_stores'] ?? [];

        return array_values(array_unique(array_map('intval', (array) $ids)));
    }

    public function hookActionAfterCreateEmployeeFormHandler(array $params)
    {
        $employeeId = (int) $params['id'];
        $storeIds = $this->extractSubmittedStoreIds($params);
        $this->saveEmployeeStores($employeeId, $storeIds);
    }

    public function hookActionAfterUpdateEmployeeFormHandler(array $params)
    {
        $employeeId = (int) $params['id'];
        $storeIds = $this->extractSubmittedStoreIds($params);
        $this->saveEmployeeStores($employeeId, $storeIds);
    }

    public function employeeStoreIds(int $employeeId): array
    {
        $rows = \Db::getInstance()->executeS('
            SELECT id_store
            FROM '._DB_PREFIX_.'ckcstockstores_employee_store
            WHERE id_employee='.(int)$employeeId);
        return array_map('intval', array_column($rows, 'id_store'));
    }

    public function hookDisplayAdminCustomers(array $params)
    {
        // ID client courant (vue BO client)

        $idCustomer = null;
        if (isset($params['id_customer']) && $params['id_customer']) {
            $idCustomer = (int) $params['id_customer'];
        }

        if (!$idCustomer) {
            return '';
        }

        // Dernière commande du client (+ boutique)
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
            SELECT o.id_order, o.id_store, ss.name AS store_name, o.date_add
            FROM '._DB_PREFIX_.'orders o
            LEFT JOIN '._DB_PREFIX_.'ckcstockstores_stores ss
                   ON ss.id_store = o.id_store
            WHERE o.id_customer = '.(int)$idCustomer.'
            ORDER BY o.date_add DESC
        ');

        if (!$row) {
            // Aucun historique
            $this->context->smarty->assign([
                'has_store'   => false,
                'store_name'  => null,
                'id_store'    => null,
                'id_order'    => null,
                'date_add'    => null,
                'denied'      => false,
            ]);
            return $this->display(__FILE__, 'views/templates/admin/customer_last_store.tpl');
        }

        // Sécurité : l’employé doit être autorisé à voir cette boutique
        $employeeId = (int) $this->context->employee->id;
        $allowed = [];
        if (method_exists($this, 'employeeStoreIds')) {
            $allowed = $this->employeeStoreIds($employeeId);
        }
        $isAllowed = empty($allowed) || in_array((int)$row['id_store'], $allowed, true);

        $this->context->smarty->assign([
            'has_store'   => !empty($row['id_store']),
            'store_name'  => $row['store_name'] ?: '',
            'id_store'    => (int) $row['id_store'],
            'id_order'    => (int) $row['id_order'],
            'date_add'    => $row['date_add'],
            'denied'      => !$isAllowed,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/customer_last_store.tpl');
    }

    public function hookActionCustomerGridDefinitionModifier(array $params)
    {
        $definition = $params['definition'];

        $columns = $definition->getColumns();
        $exists = false;
        foreach ($columns as $col) {
            if (method_exists($col, 'getId') && $col->getId() === 'ckc_store_name') { $exists = true; break; }
        }
        if (!$exists) {
            $columns->addAfter(
                'email',
                (new DataColumn('ckc_store_name'))
                    ->setName($this->l('Boutique'))
                    ->setOptions(['field' => 'ckc_store_name'])
            );
        }

        $employeeId = (int) $this->context->employee->id;
        $allowed = $this->employeeStoreIds($employeeId);

        $choices = [];
        if (!empty($allowed)) {
            $rows = \Db::getInstance()->executeS('
                SELECT id_store, name
                FROM '._DB_PREFIX_.'ckcstockstores_stores
                WHERE id_store IN ('.implode(',', array_map('intval', $allowed)).')
                ORDER BY name ASC
            ');
            foreach ($rows as $r) {
                $choices[$r['name']] = (int) $r['id_store'];
            }
        }

        $filters = $definition->getFilters();
        $has = false;
        foreach ($filters as $f) {
            if (method_exists($f, 'getName') && $f->getName() === 'ckc_stores') { $has = true; break; }
        }
        if (!$has) {
            $filters->add(
                (new Filter('ckc_stores', ChoiceType::class))
                    ->setTypeOptions([
                        'choices' => $choices,
                        'multiple' => true,
                        'expanded' => false,
                        'required' => false,
                        'choice_translation_domain' => false,
                    ])
                    ->setAssociatedColumn('ckc_store_name')
            );
        }
    }

    public function hookActionCustomerGridQueryBuilderModifier(array $params)
    {
        $qb = $params['search_query_builder'];
        $qbCount = $params['count_query_builder'];
        $filters = $params['search_criteria'];

        $allowed = $this->employeeStoreIds((int)$this->context->employee->id);

        if (empty($allowed)) {
            // Aucun droit boutique => pas de clients visibles
            $qb->andWhere('1=0');
            $qbCount->andWhere('1=0');
            return;
        }

        $aliasStore = 'ss';
        $aliasOrd   = 'o_last';

        // 1) Récupérer la DERNIÈRE commande du client (MySQL: sous-requête corrélée)
        // o_last.id_order = (SELECT id_order ... ORDER BY date_add DESC LIMIT 1)
        $sub = '(
            SELECT o2.id_order
            FROM '._DB_PREFIX_.'orders o2
            WHERE o2.id_customer = c.id_customer
            ORDER BY o2.date_add DESC
            LIMIT 1
        )';

        // Jointure sur la dernière commande
        $qb->leftJoin('c', _DB_PREFIX_.'orders', $aliasOrd, $aliasOrd.'.id_order = '.$sub);
        $qbCount->leftJoin('c', _DB_PREFIX_.'orders', $aliasOrd, $aliasOrd.'.id_order = '.$sub);

        // Jointure sur ta table boutiques
        $qb->leftJoin($aliasOrd, _DB_PREFIX_.'ckcstockstores_stores', $aliasStore, $aliasStore.'.id_store = '.$aliasOrd.'.id_store');
        $qbCount->leftJoin($aliasOrd, _DB_PREFIX_.'ckcstockstores_stores', $aliasStore, $aliasStore.'.id_store = '.$aliasOrd.'.id_store');

        // 2) Exposer le nom de boutique pour la colonne
        $qb->addSelect($aliasStore.'.name AS ckc_store_name');

        // 3) Restriction sécurité: ne voir que des clients ayant au moins une dernière commande dans les boutiques autorisées
        // (si tu veux afficher aussi les clients sans jamais commander, commente cette partie)
        $qb->andWhere('('.$aliasOrd.'.id_store IS NULL OR '.$aliasOrd.'.id_store IN (:allowedStores))');
        $qb->setParameter('allowedStores', $allowed, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        $qbCount->andWhere('('.$aliasOrd.'.id_store IS NULL OR '.$aliasOrd.'.id_store IN (:allowedStores))');
        $qbCount->setParameter('allowedStores', $allowed, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        // 4) Appliquer le filtre multiple saisi
        $f = $filters->getFilters();

        if (!empty($f['ckc_stores'])) {
            $selected = array_values(array_intersect(array_map('intval', (array)$f['ckc_stores']), $allowed));
            if (!empty($selected)) {
                $qb->andWhere($aliasOrd.'.id_store IN (:selectedStores)');
                $qb->setParameter('selectedStores', $selected, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

                $qbCount->andWhere($aliasOrd.'.id_store IN (:selectedStores)');
                $qbCount->setParameter('selectedStores', $selected, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
            }
        }
    }

    public function hookActionCustomerThreadsGridDefinitionModifier(array $params)
    {
        $definition = $params['definition'];

        // 1) Colonne "Boutique" si absente
        $columns = $definition->getColumns();
        $exists = false;
        foreach ($columns as $col) {
            if (method_exists($col, 'getId') && $col->getId() === 'ckc_store_name') {
                $exists = true; break;
            }
        }
        if (!$exists) {
            // Place après l'email si présent, sinon en fin
            $after = $columns->get('email') ? 'email' : null;

            $col = (new DataColumn('ckc_store_name'))
                ->setName($this->l('Boutique'))
                ->setOptions(['field' => 'ckc_store_name']);

            if ($after) {
                $columns->addAfter($after, $col);
            } else {
                $columns->add($col);
            }
        }

        // 2) Prépare les choix de filtre selon les droits boutique de l’employé
        $employeeId = (int) $this->context->employee->id;
        $allowed = $this->employeeStoreIds($employeeId);

        $choices = [];
        if (!empty($allowed)) {
            $rows = \Db::getInstance()->executeS('
                SELECT id_store, name
                FROM '._DB_PREFIX_.'ckcstockstores_stores
                WHERE id_store IN ('.implode(',', array_map('intval', $allowed)).')
                ORDER BY name ASC
            ');
            foreach ($rows as $r) {
                // FormType Choice attend "label" => value
                $choices[$r['name']] = (int) $r['id_store'];
            }
        }

        // 3) Filtre multi-sélection si absent
        $filters = $definition->getFilters();
        $has = false;
        foreach ($filters as $f) {
            if (method_exists($f, 'getName') && $f->getName() === 'ckc_stores') { $has = true; break; }
        }
        if (!$has) {
            $filters->add(
                (new Filter('ckc_stores', ChoiceType::class))
                    ->setTypeOptions([
                        'choices' => $choices,
                        'multiple' => true,
                        'expanded' => false,
                        'required' => false,
                        'choice_translation_domain' => false,
                    ])
                    ->setAssociatedColumn('ckc_store_name')
            );
        }
    }

    /**
     * Jointures + sécurité + filtre pour exposer "ckc_store_name"
     * sur la base de la DERNIÈRE commande du client lié au thread.
     */
    public function hookActionCustomerThreadsGridQueryBuilderModifier(array $params)
    {
        $qb      = $params['search_query_builder'];
        $qbCount = $params['count_query_builder'];
        $filters = $params['search_criteria'];

        // Droits boutique
        $allowed = $this->employeeStoreIds((int)$this->context->employee->id);

        if (empty($allowed)) {
            // Aucun droit => pas de threads visibles
            $qb->andWhere('1 = 0');
            $qbCount->andWhere('1 = 0');
            return;
        }

        // Aliases
        // Par défaut, le grid "customer_threads" aliasse la table principale en "ct".
        $aliasThread = 'ct';
        $aliasOrd    = 'o_last';
        $aliasStore  = 'ss';

        // Sous-requête : dernière commande du client du thread
        // Attention : customer_thread contient id_customer (et parfois id_order).
        // On se base sur id_customer pour rester robuste.
        $sub = '(
            SELECT o2.id_order
            FROM '._DB_PREFIX_.'orders o2
            WHERE o2.id_customer = '.$aliasThread.'.id_customer
            ORDER BY o2.date_add DESC
            LIMIT 1
        )';

        // LEFT JOIN orders ON dernière commande
        $qb->leftJoin($aliasThread, _DB_PREFIX_.'orders', $aliasOrd, $aliasOrd.'.id_order = '.$sub);
        $qbCount->leftJoin($aliasThread, _DB_PREFIX_.'orders', $aliasOrd, $aliasOrd.'.id_order = '.$sub);

        // LEFT JOIN vers la table des boutiques (ckcstockstores_stores)
        $qb->leftJoin($aliasOrd, _DB_PREFIX_.'ckcstockstores_stores', $aliasStore, $aliasStore.'.id_store = '.$aliasOrd.'.id_store');
        $qbCount->leftJoin($aliasOrd, _DB_PREFIX_.'ckcstockstores_stores', $aliasStore, $aliasStore.'.id_store = '.$aliasOrd.'.id_store');

        // Exposer le SELECT pour la colonne
        $qb->addSelect($aliasStore.'.name AS ckc_store_name');

        // Sécurité : limiter aux boutiques autorisées (ou threads dont le client n'a jamais commandé)
        $qb->andWhere('('.$aliasOrd.'.id_store IS NULL OR '.$aliasOrd.'.id_store IN (:allowedStores))');
        $qb->setParameter('allowedStores', $allowed, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        $qbCount->andWhere('('.$aliasOrd.'.id_store IS NULL OR '.$aliasOrd.'.id_store IN (:allowedStores))');
        $qbCount->setParameter('allowedStores', $allowed, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        // Appliquer le filtre multi-boutiques s'il est saisi
        $f = $filters->getFilters();
        if (!empty($f['ckc_stores'])) {
            $selected = array_values(array_intersect(array_map('intval', (array)$f['ckc_stores']), $allowed));
            if (!empty($selected)) {
                $qb->andWhere($aliasOrd.'.id_store IN (:selectedStores)');
                $qb->setParameter('selectedStores', $selected, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

                $qbCount->andWhere($aliasOrd.'.id_store IN (:selectedStores)');
                $qbCount->setParameter('selectedStores', $selected, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
            }
        }
    }
}
