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


use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;

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
            $this->registerHook('displayBackOfficeHeader');
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
            $stores = Db::getInstance()->executeS('SELECT id_store, zip_codes, carrier FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores WHERE carrier IS NULL OR carrier = ""');

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
        $searchQueryBuilder = $params['search_query_builder'];
        $searchCriteria = $params['search_criteria'];

        $searchQueryBuilder->addSelect('st.name AS store_name')
            ->leftJoin(
                'o',
                _DB_PREFIX_ . 'ckcstockstores_stores',
                'st',
                'o.id_store = st.id_store'
            );

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('store_name' === $filterName && !empty($filterValue)) {
                $searchQueryBuilder->andWhere('st.name LIKE :store_name');
                $searchQueryBuilder->setParameter('store_name', '%' . pSQL($filterValue) . '%');
            }
        }

        //PrestashopLogger::addLog('searchQueryBuilder: ' . $searchQueryBuilder->getSQL());
    }


    public function hookActionOrderGridDefinitionModifier($params)
    {
        /** @var GridDefinitionInterface $definition */
        $definition = $params['definition'];

        /** @var ColumnCollection */
        $columns = $definition->getColumns();

        $columns
            ->addAfter('date_add', (new DataColumn('store_name'))
                ->setName($this->l('Boutique'))
                ->setOptions([
                    'field' => 'store_name',
                    'sortable' => true,
                ])
            );
    }
}
