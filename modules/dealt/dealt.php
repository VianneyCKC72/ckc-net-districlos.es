<?php

use Dealt\DealtHookHelper;
use Dealt\Service\ApiService;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dealt extends Module
{
    /**
     * Cache statique pour éviter les vérifications répétées de l'installation de pm_advancedpack
     * Remplace les requêtes SHOW TABLES LIKE coûteuses
     */
    private static $pm_advancedpack_installed = null;

    public function __construct()
    {
        $this->name = 'dealt';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Agence CKC NET';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Dealt - Services associés');
        $this->description = $this->l('Intégration de l\'API DEALT pour proposer des services associés à vos produits.');

        $this->controllers = ['testapi'];


        spl_autoload_register(function ($class) {
            if (strpos($class, 'Dealt\\') === 0) {
                $path = _PS_MODULE_DIR_ . 'dealt/src/' . str_replace('\\', '/', substr($class, 6)) . '.php';
                if (file_exists($path)) {
                    require_once $path;
                }
            }
        });

    }

    public function install()
    {
        // ordre natif : parent d'abord, puis nos actions
        if (!parent::install()) {
            return false;
        }

        if (!$this->installDb()) {
            return false;
        }

        // hooks natifs (facultatif : éviter les doublons avec isRegisteredInHook)
        foreach ([
                     'actionValidateOrder',
                     'actionValidateOrderAfter',
                     'actionOrderStatusPostUpdate',
                     'actionPresentCart',
                     'actionPresentCartProduct',
                     'actionCartSave',
                     'displayProductAdditionalInfo',
                     'displayHeader',
                     'displayShoppingCartFooter',
                     'displayCheckoutSummaryTop',
                     'displayCartProductLine',
                     'displayProductPromoInstallation',
                 ] as $hook) {
            if (!$this->isRegisteredInHook($hook) && !$this->registerHook($hook)) {
                return false;
            }
        }

        // Configs (classique)
        Configuration::deleteByName('DEALT_TAX10_ID'); // si voulu
        Configuration::updateValue('DEALT_SERVICE_URL', '');
        Configuration::updateValue('DEALT_SERVICE_TITLE_DEFAULT', 'Service de montage disponible');
        Configuration::updateValue('DEALT_API_KEY', '');

        return true;
    }

    public function uninstall()
    {
        // ordre natif : parent d'abord pour laisser PrestaShop
        // nettoyer les ACL, hooks, accès, etc.
        if (!parent::uninstall()) {
            return false;
        }

        // on supprime notre table (natif)
        if (!$this->uninstallDb()) {
            return false;
        }

        // on nettoie notre config (natif)
        Configuration::deleteByName('DEALT_SERVICE_URL');
        Configuration::deleteByName('DEALT_API_KEY');
        Configuration::deleteByName('DEALT_SERVICE_TITLE_DEFAULT');

        return true;
    }

    private function installDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dealt_tva_attestation` (
        `id_dealt_tva_attestation` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_customer` INT UNSIGNED NULL,
        `id_cart` INT UNSIGNED NOT NULL,
        `id_main_product` INT UNSIGNED NOT NULL DEFAULT 0,
        `is_validated` TINYINT(1) NOT NULL DEFAULT 0,
        `validated_at` DATETIME NULL,
        `ip_address` VARCHAR(64) NULL,
        `user_agent` VARCHAR(255) NULL,
        `token` VARCHAR(64) NULL,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_dealt_tva_attestation`),
        KEY `idx_id_cart` (`id_cart`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';
        return Db::getInstance()->execute($sql);
    }

    private function uninstallDb()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dealt_tva_attestation`';
        return Db::getInstance()->execute($sql);
    }

    public function hookDisplayCheckoutSummaryTop($params)
    {
        $cart = $this->context->cart;
        if (!$cart || !$cart->id) return '';

        // Attestation requise
        if (!$this->isTvaAttestedForCart((int)$cart->id)) return '';

        $this->context->smarty->assign([
            'dealt_tva_notice_text' => $this->l('Vous bénéficiez de la TVA réduite à 10 %'),
            'dealt_tva_notice_help' => $this->l('Applicable aux prestations éligibles (Art. 279 du CGI).'),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/tva_notice.tpl');
    }

    private function getServiceProductId()
    {
        // ta ref service actuelle :
        $serviceRef = '6846df4980a710f6d4b755c7';
        return (int)Product::getIdByReference($serviceRef);
    }

    private function getEligibleProductIdsForCart($cart)
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $eligible = [];
        $serviceId = $this->getServiceProductId();
        if ($serviceId) $eligible[] = $serviceId;

        $mainId = null;
        foreach ($cart->getProducts() as $p) {
            $pid = (int)$p['id_product'];
            if ($pid !== $serviceId) {
                $mainId = $pid;
                break;
            } // 1er produit “non service”
        }
        if ($mainId) $eligible[] = $mainId;

        $cache = $eligible;
        return $eligible;
    }

    private function isTvaAttestedForCart($idCart)
    {
        // Table dealt_tva_attestation existe car le module est installé
        // Pas besoin de SHOW TABLES LIKE à chaque appel
        $table = _DB_PREFIX_ . 'dealt_tva_attestation';
        $sql = 'SELECT COUNT(*) FROM `' . bqSQL($table) . '`
            WHERE id_cart=' . (int)$idCart . ' AND is_validated=1';
        return (int)Db::getInstance()->getValue($sql) > 0;
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->registerStylesheet(
            'module-dealt-css',
            'modules/' . $this->name . '/views/assets/css/main.css',
            ['media' => 'all', 'priority' => 150]
        );

        $this->context->controller->registerJavascript(
            'module-dealt-tva-attestation',
            'modules/' . $this->name . '/views/assets/js/main.js',
            ['position' => 'bottom', 'priority' => 150]
        );
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDealtModule')) {
            Configuration::updateValue('DEALT_SERVICE_URL', Tools::getValue('DEALT_SERVICE_URL'));
            Configuration::updateValue('DEALT_API_KEY', Tools::getValue('DEALT_API_KEY'));
            Configuration::updateValue('DEALT_SERVICE_TITLE_DEFAULT', Tools::getValue('DEALT_SERVICE_TITLE_DEFAULT'));
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        $orderList = Db::getInstance()->executeS('SELECT cu.firstname, cu.lastname, dva.date_add, dva.id_cart, o.id_order, dva.is_validated FROM `' . _DB_PREFIX_ . 'dealt_tva_attestation` dva 
        LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON o.id_cart = dva.id_cart 
        LEFT JOIN `' . _DB_PREFIX_ . 'cart` c ON c.id_cart = dva.id_cart 
        LEFT JOIN `' . _DB_PREFIX_ . 'customer` cu ON cu.id_customer = c.id_customer');

        $this->context->smarty->assign(array(
            'orderList' => $orderList,
        ));

        $list = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        $output = $output . $this->renderForm();

        return $output . $list;
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDealtModule';
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value['DEALT_API_KEY'] = Configuration::get('DEALT_API_KEY');
        $helper->fields_value['DEALT_SERVICE_URL'] = Configuration::get('DEALT_SERVICE_URL');
        $helper->fields_value['DEALT_SERVICE_TITLE_DEFAULT'] = Configuration::get('DEALT_SERVICE_TITLE_DEFAULT');

        return $helper->generateForm([
            [
                'form' => [
                    'legend' => ['title' => $this->l('Settings')],
                    'input' => [
                        [
                            'type' => 'text',
                            'label' => $this->l('URL des conditions de service'),
                            'name' => 'DEALT_SERVICE_URL',
                            'size' => 60,
                            'required' => true
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Titre par défaut du service'),
                            'name' => 'DEALT_SERVICE_TITLE_DEFAULT',
                            'size' => 60,
                            'required' => true
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Clé API DEALT'),
                            'name' => 'DEALT_API_KEY',
                            'size' => 60,
                            'required' => true
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right'
                    ]
                ]
            ]
        ]);
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        $dealt_product_length = null;
        $combinations = [];

        $checkIfPack = $this->checkIfPack($params['product']['id']);

        if($checkIfPack){
            $mainProduct = Db::getInstance()->executeS('SELECT pap.id_product, pap.default_id_product_attribute FROM `'._DB_PREFIX_.'pm_advancedpack_products` pap WHERE pap.id_pack = '.(int)$params['product']['id'].' AND pap.position = 0');
            if(!empty($mainProduct[0])){
                if(!empty($mainProduct[0]['default_id_product_attribute'])){
                    $combinations = $this->getAttributesInformationsByProduct($mainProduct[0]['id_product'], $mainProduct[0]['default_id_product_attribute']);
                }
            }

            if(count($combinations)){
                foreach($combinations as $combination){
                    if($combination['group'] == 'Longueur du Kit' OR $combination['group'] == 'Longueur' OR $combination['group'] == 'longueur'){
                        if(preg_match('/cm/', $combination['attribute'])){
                            $dealt_product_length = floatval(str_replace([' cm', 'cm'], ['', '.'], $combination['attribute'])) / 100;
                        } else {
                            $dealt_product_length = floatval(str_replace([' ml', ' m', 'ml', 'm'], ['', '', '.', '.'], $combination['attribute']));
                        }

                        break;
                    }
                }
            }

            if(!empty($mainProduct[0]) && empty($dealt_product_length)){
                $mainProdInfo = new Product($mainProduct[0]['id_product']);

                if(!empty($mainProdInfo->width) && $mainProdInfo->width != 0){
                    $dealt_product_length = $mainProdInfo->width/100;
                } else {
                    $mainProdInfoFeatures = Product::getFrontFeaturesStatic(Context::getContext()->language->id, $mainProduct[0]['id_product']);
                    if(count($mainProdInfoFeatures)){
                        foreach ($mainProdInfoFeatures as $feature){
                            if($feature['name'] == 'Longueur'){
                                if(preg_match('/mm/', $feature['value']) OR preg_match('/MM/', $feature['value'])){
                                    $lengthInt = (int)$feature['value'];
                                    $dealt_product_length = $lengthInt / 1000;
                                } else if(preg_match('/ml/', $feature['value'])){
                                    $dealt_product_length = floatval(preg_replace('/[ml]/', '.', $feature['value']).'0');
                                } else if(preg_match('/ML/', $feature['value'])){
                                    $dealt_product_length = floatval(preg_replace('/[ML]/', '.', $feature['value']).'0');
                                }else if(preg_match('/m/', $feature['value']) OR preg_match('/M/', $feature['value'])){
                                    $dealt_product_length = floatval(preg_replace('/[mM]/', '.', $feature['value']).'0');
                                } else if(preg_match('/cm/', $feature['value']) OR preg_match('/CM/', $feature['value'])){
                                    $lengthInt = (int)$feature['value'];
                                    $dealt_product_length = $lengthInt / 100;
                                } else {
                                    $dealt_product_length = (int)$feature['value'];
                                }
                                break;
                            }
                        }
                    }

                }
            }

        } else {
            if(!empty($params['product']['id_product_attribute'])){
                $combinations = $this->getAttributesInformationsByProduct($params['product']['id'], $params['product']['id_product_attribute']);
            }

            if(count($combinations)){
                foreach($combinations as $combination){
                    if($combination['group'] == 'Longueur du Kit' OR $combination['group'] == 'Longueur' OR $combination['group'] == 'longueur'){
                        if(preg_match('/cm/', $combination['attribute'])){
                            $dealt_product_length = floatval(str_replace([' cm', 'cm'], ['', '.'], $combination['attribute'])) / 100;
                        } else {
                            $dealt_product_length = floatval(str_replace([' ml', ' m', 'ml', 'm'], ['', '', '.', '.'], $combination['attribute']));
                        }

                        break;
                    }
                }
            }

            if(empty($dealt_product_length)){
                if(!empty($params['product']['width']) && $params['product']['width'] != 0){
                    $dealt_product_length = $params['product']['width']/100;
                } else {
                    foreach ($params['product']['features'] as $feature){
                        if($feature['name'] == 'Longueur'){
                            if(preg_match('/mm/', $feature['value']) OR preg_match('/MM/', $feature['value'])){
                                $lengthInt = (int)$feature['value'];
                                $dealt_product_length = $lengthInt / 1000;
                            } else if(preg_match('/ml/', $feature['value'])){
                                $dealt_product_length = floatval(preg_replace('/[ml]/', '.', $feature['value']).'0');
                            } else if(preg_match('/ML/', $feature['value'])){
                                $dealt_product_length = floatval(preg_replace('/[ML]/', '.', $feature['value']).'0');
                            }else if(preg_match('/m/', $feature['value']) OR preg_match('/M/', $feature['value'])){
                                $dealt_product_length = floatval(preg_replace('/[mM]/', '.', $feature['value']).'0');
                            } else if(preg_match('/cm/', $feature['value']) OR preg_match('/CM/', $feature['value'])){
                                $lengthInt = (int)$feature['value'];
                                $dealt_product_length = $lengthInt / 100;
                            } else {
                                $dealt_product_length = (int)$feature['value'];
                            }
                            break;
                        }
                    }
                }
            }
        }

        if (empty($params['product']['quantity_wanted'])){
            $params['product']['quantity_wanted'] = 1;
        }
        if(gettype($dealt_product_length) == 'string' OR $dealt_product_length == 0){
            $dealt_product_length = null;
        }

        $id_product = isset($params['product']['id_product'])
            ? (int)$params['product']['id_product']
            : (int)Tools::getValue('id_product');

        if (!$id_product) return '';

        $data = [];

        $data = \Dealt\DealtHookHelper::buildProductAdditionalInfoData($this->context, $id_product,$dealt_product_length * $params['product']['quantity_wanted']);

        $this->context->smarty->assign(["data" => $data,
            'dealt_product_length' => $dealt_product_length,
            'ajaxUrl' => Context::getContext()->link->getModuleLink('dealt', 'eligibility', array('ajax'=>true)),
            'id_product_attribute' =>!empty($params['product']['id_product_attribute']) ? $params['product']['id_product_attribute'] : 0,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product_offer.tpl');
    }

    public function checkIfPack($id_product){
        // Cache statique pour éviter de vérifier l'installation à chaque appel
        if (self::$pm_advancedpack_installed === null) {
            self::$pm_advancedpack_installed = Module::isInstalled('pm_advancedpack');
        }

        if (!self::$pm_advancedpack_installed) {
            return false;
        }

        $check = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'pm_advancedpack` WHERE `id_pack` = '.(int)$id_product.' AND id_shop = '.(int)Context::getContext()->shop->id);
        return !empty($check[0]);
    }

    public function getAttributesInformationsByProduct($id_product, $id_product_attribute)
    {
        $sql = '
            SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`, pac.`id_product_attribute`
            FROM `'._DB_PREFIX_.'attribute` a
            LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
                ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)Context::getContext()->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
                ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)Context::getContext()->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
                ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
            '.Shop::addSqlAssociation('product_attribute', 'pa').'
            '.Shop::addSqlAssociation('attribute', 'pac').'
            WHERE pa.`id_product` = '.(int)$id_product;

        if(!empty($id_product_attribute)){
            $sql .= ' AND pac.`id_product_attribute` = '.(int)$id_product_attribute;
        }

        $result = Db::getInstance()->executeS($sql);

        return $result;
    }

    public function hookActionCartSave($params){
        $idProduct = (int)Tools::getValue('id_product');
        if(isset(Context::getContext()->cookie->cart_packs)){
            $cookiePack = json_decode(Context::getContext()->cookie->cart_packs);
            if(!empty($cookiePack->id_cart) && !empty(Context::getContext()->cart->id)){
                if((int)$cookiePack->id_cart != (int)Context::getContext()->cart->id) {
                    unset(Context::getContext()->cookie->cart_packs);
                }
            }
        }
        if(Tools::getValue('action') == 'update'){
            $cart = Context::getContext()->cart;
            $ifTVAAttested = $this->isTvaAttestedForCart($cart->id);
            if($ifTVAAttested && !empty($idProduct)){
                // check if service
                $product = new Product($idProduct);
                if($product->id_category_default == 369){
                    // check if service is not in Cart
                    if(!$cart->containsProduct($idProduct)){
                        $deleteTVAAttestation = Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'dealt_tva_attestation` WHERE `id_cart` = '.(int)$cart->id.' 
                        AND `is_validated` = 1');
                    }
                }
            }
        }
        if(Tools::getValue('action') && Tools::getValue('action') == 'add-to-cart'){
            if(!empty($idProduct)){
                $ifPack = $this->checkIfPack($idProduct);
                if($ifPack){
                    $cart_pack = [
                        'id_cart' => $params['cart']->id,
                        'id_pack' => $idProduct,
                    ];
                    Context::getContext()->cookie->cart_packs= json_encode($cart_pack);
                }
            }
        } else if (!empty(Tools::getValue('id_product_attribute_list'))) {
            $packInfo = json_decode(Tools::getValue('id_product_attribute_list'));
            $fistProductId = array_key_first((array)$packInfo);
            if(!empty($fistProductId)){
                $id_pack = $this->findPackId($fistProductId);
                if(!empty($id_pack)){
                    $ifPack = $this->checkIfPack((int)$id_pack);
                    if($ifPack){
                        $cart_pack = [
                            'id_cart' => $params['cart']->id,
                            'id_pack' => $id_pack,
                        ];
                        Context::getContext()->cookie->cart_packs= json_encode($cart_pack);
                    }
                }

            }
        }
    }

    public function findPackId($productPackId){
        // Utilise le même cache que checkIfPack
        if (self::$pm_advancedpack_installed === null) {
            self::$pm_advancedpack_installed = Module::isInstalled('pm_advancedpack');
        }

        if (!self::$pm_advancedpack_installed) {
            return false;
        }

        $id_pack = Db::getInstance()->executeS('SELECT id_pack FROM `'._DB_PREFIX_.'pm_advancedpack_products` WHERE `id_product_pack` = '.(int)$productPackId);
        return $id_pack[0]['id_pack'];
    }

    public function hookDisplayShoppingCartFooter($params)
    {
        $data = \Dealt\DealtHookHelper::buildCartFooterData($this->context) ?: [];

        $safe = array_merge([
            'dealt_check_availability_url' => '',
            'dealt_offer_id'               => '',
            'dealt_store_id'               => '',
            'dealt_service_product_id'     => 0,
            'dealt_main_product_id'        => 0,
            'tva_token'                    => '',
            'tva_attestation_url'          => '',
        ], $data);

        Media::addJsDef([
            'dealt_check_availability_url' => (string)$safe['dealt_check_availability_url'],
            'dealt_offer_id'               => (string)$safe['dealt_offer_id'],
            'dealt_store_id'               => (string)$safe['dealt_store_id'],
            'dealt_service_product_id'     => (int)$safe['dealt_service_product_id'],
            'dealt_product_id'             => (int)$safe['dealt_main_product_id'],
            'tva_token'                    => (string)$safe['tva_token'],
            'tva_attestation_url'          => (string)$safe['tva_attestation_url'],
        ]);

        $this->context->smarty->assign($safe);
        $this->context->smarty->assign(['ajaxUrl' => Context::getContext()->link->getModuleLink('dealt', 'eligibility', array('ajax'=>true))]);
        $this->context->smarty->assign(['ajaxUrlUpdateCart' => Context::getContext()->link->getModuleLink('dealt', 'updateServiceCart', array('ajax'=>true))]);
        return $this->display(__FILE__, 'views/templates/hook/cart_footer.tpl');
    }

    public function hookActionValidateOrder($params)
    {
        // À compléter : appeler ApiService::submitMission()
        PrestaShopLogger::addLog('Hook Launched', 1, null, 'dealt', "0", true);

        $orderObject = $params['order'];
        $customerObject = $params['customer'];
        $cartObject = $params['cart'];
        $orderStatus = $params['orderStatus'];
        if((int)$orderStatus->paid){
            $idlang = Context::getContext()->language->id;
            $hasValidParams = Validate::isLoadedObject($orderObject) && Validate::isLoadedObject($customerObject) && Validate::isLoadedObject($cartObject);
            if ($hasValidParams) {
                $orderDetails = $orderObject->getOrderDetailList();
                $servicePrice = 0;
                $offerId = '';
                $storeId = '';

                foreach ($orderDetails as $orderDetail) {
                    $containsService = false;
                    $defaultCategory = Db::getInstance()->executeS('SELECT id_category_default FROM `' . _DB_PREFIX_ . 'product`
                WHERE `id_product` = ' . (int)$orderDetail['product_id']);
                    if ($defaultCategory[0]['id_category_default'] == 369) {
                        $taxPercentage = 100 + (int)$orderDetail['tax_rate'];
                        $servicePrice = $orderDetail['product_price'] / 100 * $taxPercentage;
                        $serviceProduct = new \Product($orderDetail['product_id'], false, $idlang);
                        $offerId = $serviceProduct->reference;
                        $containsService = true;

                        if ($containsService) {
                            $productsPriceTotal = $orderObject->total_products_wt - $servicePrice;

                            $addressSQL = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'address`
                WHERE `id_address` = ' . (int)$cartObject->id_address_delivery);

                            $customer = [
                                'firstName' => $customerObject->firstname,
                                'lastName' => $customerObject->lastname,
                                'emailAddress' => $customerObject->email,
                                'phoneNumber' => (string)$addressSQL[0]['phone'],
                                'customerProductPrice' => $productsPriceTotal,
                                'customerServicePrice' => $servicePrice,
                                'purchaseOrder' => (string)$orderObject->id,
                            ];

                            $countrySql = Db::getInstance()->executeS('SELECT c.name FROM `' . _DB_PREFIX_ . 'country_lang` c
            WHERE `id_country` = ' . (int)$addressSQL[0]['id_country'] . ' AND `id_lang` = ' . $idlang);

                            $address = [
                                'country' => $countrySql[0]['name'],
                                'zipCode' => $addressSQL[0]['postcode'],
                                'city' => $addressSQL[0]['city'],
                                'street1' => $addressSQL[0]['address1'],
                            ];

                            $customer_zip_code = pSQL($addressSQL[0]['postcode']);

                            $stores = Db::getInstance()->executeS('SELECT zip_codes, name FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores');

                            $store_name = null;

                            foreach ($stores as $store) {
                                $zip_codes = explode(',', $store['zip_codes']);

                                foreach ($zip_codes as $zip_code) {
                                    $zip_code = trim($zip_code);

                                    if ($zip_code === $customer_zip_code || (strpos($zip_code, '*') !== false && preg_match('/^' . str_replace('*', '.*', $zip_code) . '$/', $customer_zip_code))) {
                                        $store_name = $store['name'];
                                        break 2;
                                    }
                                }
                            }

                            if(!empty($store_name)){
                                $stores2 = Db::getInstance()->executeS('SELECT offerId, departement FROM ' . _DB_PREFIX_ . 'ckcstorespresentation');
                                foreach ($stores2 as $store) {
                                    $storeName = strtolower(str_replace('ô', 'o', $store['departement']));
                                    $store_name = strtolower($store_name);
                                    if(strpos($store_name, $storeName) !== false){
                                        $storeId = $store['offerId'];
                                        /*PrestaShopLogger::addLog('StoreName :'.$storeName.'. StoreId : '.$storeId, 1, null, 'dealt', $orderObject->id, true);*/
                                        break;
                                    }
                                }
                            }

                            // (!!) StoreId de test : à remplacer par le réel ($storeId) lors de la mise en prod :
                            if(Context::getContext()->shop->id == 1) {
                                $storeIdTest = '6846de0f80a710f6d4b75296';
                            } else {
                                $storeIdTest = '68776d60d3af94cf7663bff9';
                            }

                            $data = [];
                            // (!!) OfferId Réel :
                            $data['offerId'] = $offerId;

                            // (!!) OfferId de Test : à supprimer et décommenter réel lors de la mise en prod :
                            /*$data['offerId'] = '6846df4980a710f6d4b755c7';*/

                            $data['customer'] = $customer;
                            $data['address'] = $address;
                            $data['storeId'] = $storeId;

                            $apiService = new \Dealt\Service\ApiService();

                            $response = $apiService->submitMission($data);

                            if(!empty($response['createdAt'])){
                                $id_mission = $response['id'];
                                $id_order = $orderObject->id;
                                $id_offer = $response['offer']['id'];

                                if(!empty($id_mission) && !empty($id_order) && !empty($id_offer)){
                                    $insertMission = Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'dealt_missions (`id`, `id_mission`, `id_order`, `id_offer`, `date_add`, `date_upd`) 
                                VALUES (NULL, "'.$id_mission.'", '.$id_order.', "'.$id_offer.'", CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP())');

                                    PrestaShopLogger::addLog(json_encode($insertMission), 1, null, 'dealt', $orderObject->id, true);
                                }
                            }

                            PrestaShopLogger::addLog(json_encode($response), 1, null, 'dealt', $orderObject->id, true);
                        }
                    }
                }

                PrestaShopLogger::addLog('Valid Objects', 1, null, 'dealt', $orderObject->id, true);
            } else {
                PrestaShopLogger::addLog('Invalid Objects', 1, null, 'dealt', $orderObject->id, true);
            }
        } else {
            PrestaShopLogger::addLog('Order Not Paid : '.json_encode($orderStatus->paid), 1, null, 'dealt', $orderObject->id, true);
        }
    }

    public function hookActionValidateOrderAfter($params)
    {
        $order_list = $params['orders'];

        // vérifier si une commande contient produit + service avec attestation Tva validée
        $ifTvaAttested = (int)Db::getInstance()->getValue("SELECT dta.is_validated FROM " . _DB_PREFIX_ . "dealt_tva_attestation dta
        WHERE dta.id_cart = " . $params['cart']->id);


        if(!empty($ifTvaAttested)){

            $orders = Db::getInstance()->executeS('SELECT id_order FROM `' . _DB_PREFIX_ . 'orders` WHERE id_cart = ' . $params['cart']->id);

            // retrouver une commande avec un service si attestation TVA = 1
            $order_with_service = 0;
            foreach ($orders as $orderId) {
                $order = new Order((int)$orderId['id_order']);
                $products = $order->getProducts();
                foreach ($products as $key => $product) {
                    if((int)$products[$key]['id_category_default'] == 369){
                        $order_with_service = $order;
                        break;
                    }
                }
            }

            foreach ($orders as $orderId) {
                $is_order_with_service = 0;
                $order = new Order((int)$orderId['id_order']);
                $products = $order->getProducts();
                foreach ($products as $key => $product) {
                    if((int)$products[$key]['id_category_default'] == 369){
                        $is_order_with_service = 1;
                        break;
                    }
                }

                // mise à jour du transporteur de la commande sans service (20%)
                if(!$is_order_with_service){

                    $order->id_carrier = (int)$order_with_service->id_carrier;
                    $order->total_paid = $order->total_products_wt;
                    $order->total_paid_tax_incl = $order->total_products_wt;
                    $order->total_paid_tax_excl = $order->total_products;
                    $order->update();

                    $idOrderCarrier = Db::getInstance()->getValue("SELECT id_order_carrier FROM `" . _DB_PREFIX_ . "order_carrier` 
                    WHERE id_order = ".$order->id);

                    if(!empty($idOrderCarrier)) {

                        $orderCarrier = new OrderCarrier($idOrderCarrier);
                        $orderCarrier->id_order = $order->id;
                        $orderCarrier->id_carrier = $order_with_service->id_carrier;
                        $orderCarrier->id_order_invoice = 0;
                        $orderCarrier->weight = 0;
                        $orderCarrier->shipping_cost_tax_excl = 0;
                        $orderCarrier->shipping_cost_tax_incl = 0;
                        $orderCarrier->tracking_number = NULL;
                        $orderCarrier->date_add = date('Y-m-d H:i:s');
                        $orderCarrier->return_tracking_number = NULL;
                        $orderCarrier->return_carrier = NULL;

                        $orderCarrier->update();
                    } else {
                        $createCarrier = Db::getInstance()->execute("INSERT INTO `" . _DB_PREFIX_ . "order_carrier` 
                        (`id_order_carrier`,`id_order`,`id_carrier`,`id_order_invoice`,`weight`,`shipping_cost_tax_excl`,`shipping_cost_tax_incl`,`tracking_number`,`date_add`,`return_tracking_number`,`return_carrier`) VALUES 
                        (NULL, " . $order->id . ", " . $order_with_service->id_carrier . ", 0, 0, 0, 0, NULL, CURRENT_TIMESTAMP, NULL, NULL)");
                    }
                }
            }

            /*            foreach ($order_list as $order) {
                            $order_with_service = 0;
                            $products = $order->getProducts();
                            foreach ($products as $product) {
                                if($product['id_category_default'] == 369){
                                    $order_with_service = 1;
                                    break;
                                }
                            }

                            // mise à jour du transporteur de la commande sans service (20%)
                            if(!$order_with_service){

                                $updateOrder = Db::getInstance()->execute("UPDATE " . _DB_PREFIX_ . "orders o
                                SET o.total_paid = '".$order->total_products_wt."',
                                o.total_paid_tax_incl = '".$order->total_products_wt."',
                                o.total_paid_tax_excl = '".$order->total_products."',
                                WHERE o.id_order = " . $order->id);

                                $updateCarrier = Db::getInstance()->execute("UPDATE " . _DB_PREFIX_ . "order_carrier oc
                                SET oc.shipping_cost_tax_excl = 0,
                                oc.shipping_cost_tax_incl = 0,
                                WHERE o.id_order = " . $order->id);
                            }
                        }*/
        }
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        $orderStatus = $params['newOrderStatus'];
        $order_id = $params['id_order'];

        $order = new Order($order_id);

        $allProducts = $order->getProducts();
        if($orderStatus->id == 6){
            foreach ($allProducts as $key => $product) {
                if((int)$allProducts[$key]['id_category_default'] == 369){

                    $reference = $allProducts[$key]['reference'];
                    $reference_test = '6846df4980a710f6d4b755c7';

                    // REFERENCE DE TEST !!! A REMPLACER PAR UNE VRAI REFERENCE LORS DE LA MISE EN PROD ($reference)
                    $id_mission = Db::getInstance()->getValue('SELECT id_mission FROM ' . _DB_PREFIX_ . 'dealt_missions dm
                    WHERE dm.id_order LIKE "'.$order_id.'" AND dm.id_offer LIKE "'.$reference.'"');

                    if(!empty($id_mission)){
                        $apiService = new \Dealt\Service\ApiService();

                        $response = $apiService->cancelMission($id_mission);

                        PrestaShopLogger::addLog(json_encode($response), 1, null, 'dealt', $order_id, true);

                    }
                }
            }
        } else if((int)$orderStatus->paid) {
            $ifContainsService = false;
            // Vérifier si la commande contient un service
            foreach ($allProducts as $key => $product) {
                if((int)$allProducts[$key]['id_category_default'] == 369) {
                    $ifContainsService = true;
                    break;
                }
            }
            if($ifContainsService){
                $storeId = '';
                // Vérifier si une mission pour la commande est déjà créé ou pas
                $ifMissionExists = Db::getInstance()->getValue('SELECT id_mission FROM ' . _DB_PREFIX_ . 'dealt_missions dm 
                WHERE dm.id_order LIKE "'.$order_id.'"');

                // si une mission n'est pas créée pour cette commande payée, créer des missions pour chaque service dans la commande
                if(empty($ifMissionExists)){
                    $idlang = Context::getContext()->language->id;
                    foreach ($allProducts as $key => $product) {
                        if((int)$allProducts[$key]['id_category_default'] == 369) {
                            $offerId = $allProducts[$key]['reference'];
                            $productId = $allProducts[$key]['id_product'];

                            $orderDetails = Db::getInstance()->executeS('SELECT product_price, tax_rate FROM ' . _DB_PREFIX_ . 'order_detail 
                            WHERE id_order = '.(int)$order->id.' AND product_id = '.(int)$productId);

                            $taxPercentage = 100 + (int)$orderDetails[0]['tax_rate'];
                            $servicePrice = $orderDetails[0]['product_price'] / 100 * $taxPercentage;

                            $cartObject = new Cart($order->id_cart);
                            $customerObject = new Customer($order->id_customer);

                            $productsPriceTotal = $order->total_products_wt - $servicePrice;

                            $addressSQL = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'address`
                WHERE `id_address` = ' . (int)$cartObject->id_address_delivery);

                            $customer = [
                                'firstName' => $customerObject->firstname,
                                'lastName' => $customerObject->lastname,
                                'emailAddress' => $customerObject->email,
                                'phoneNumber' => (string)$addressSQL[0]['phone'],
                                'customerProductPrice' => $productsPriceTotal,
                                'customerServicePrice' => $servicePrice,
                                'purchaseOrder' => (string)$order->id,
                            ];

                            $countrySql = Db::getInstance()->executeS('SELECT c.name FROM `' . _DB_PREFIX_ . 'country_lang` c
            WHERE `id_country` = ' . (int)$addressSQL[0]['id_country'] . ' AND `id_lang` = ' . $idlang);

                            $address = [
                                'country' => $countrySql[0]['name'],
                                'zipCode' => $addressSQL[0]['postcode'],
                                'city' => $addressSQL[0]['city'],
                                'street1' => $addressSQL[0]['address1'],
                            ];

                            $customer_zip_code = pSQL($addressSQL[0]['postcode']);

                            $stores = Db::getInstance()->executeS('SELECT zip_codes, name FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores');

                            $store_name = null;

                            foreach ($stores as $store) {
                                $zip_codes = explode(',', $store['zip_codes']);

                                foreach ($zip_codes as $zip_code) {
                                    $zip_code = trim($zip_code);

                                    if ($zip_code === $customer_zip_code || (strpos($zip_code, '*') !== false && preg_match('/^' . str_replace('*', '.*', $zip_code) . '$/', $customer_zip_code))) {
                                        $store_name = $store['name'];
                                        break 2;
                                    }
                                }
                            }

                            if(!empty($store_name)){
                                $stores2 = Db::getInstance()->executeS('SELECT offerId, departement FROM ' . _DB_PREFIX_ . 'ckcstorespresentation');
                                foreach ($stores2 as $store) {
                                    $storeName = strtolower(str_replace('ô', 'o', $store['departement']));
                                    $store_name = strtolower($store_name);
                                    if(strpos($store_name, $storeName) !== false){
                                        $storeId = $store['offerId'];
                                        /*PrestaShopLogger::addLog('StoreName :'.$storeName.'. StoreId : '.$storeId, 1, null, 'dealt', $orderObject->id, true);*/
                                        break;
                                    }
                                }
                            }

                            // (!!) StoreId de test : à remplacer par le réel ($storeId) lors de la mise en prod :
                            if(Context::getContext()->shop->id == 1) {
                                $storeIdTest = '6846de0f80a710f6d4b75296';
                            } else {
                                $storeIdTest = '68776d60d3af94cf7663bff9';
                            }

                            $data = [];
                            // (!!) OfferId Réel :
                            $data['offerId'] = $offerId;

                            // (!!) OfferId de Test : à supprimer et décommenter réel lors de la mise en prod :
                            /*$data['offerId'] = '6846df4980a710f6d4b755c7';*/

                            $data['customer'] = $customer;
                            $data['address'] = $address;
                            $data['storeId'] = $storeId;

                            $apiService = new \Dealt\Service\ApiService();

                            $response = $apiService->submitMission($data);

                            if(!empty($response['createdAt'])){
                                $id_mission = $response['id'];
                                $id_order = $order->id;
                                $id_offer = $response['offer']['id'];

                                if(!empty($id_mission) && !empty($id_order) && !empty($id_offer)){
                                    $insertMission = Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'dealt_missions (`id`, `id_mission`, `id_order`, `id_offer`, `date_add`, `date_upd`) 
                                VALUES (NULL, "'.$id_mission.'", '.$id_order.', "'.$id_offer.'", CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP())');

                                    PrestaShopLogger::addLog(json_encode($insertMission), 1, null, 'dealt', $order->id, true);
                                }
                            }

                            PrestaShopLogger::addLog(json_encode($response), 1, null, 'dealt', $order->id, true);
                        }
                    }
                }
            }
        }
    }

    public function hookActionPresentCartProduct(array $params)
    {
        if (empty($params['product']) || !is_array($params['product'])) return;

        $product = &$params['product']; // Référence pour modifier directement

        // S'assurer que id_category_default est présent dans les données du produit
        if (!isset($product['id_category_default']) && isset($product['id_product'])) {
            $product['id_category_default'] = (int)Db::getInstance()->getValue(
                'SELECT id_category_default FROM ' . _DB_PREFIX_ . 'product_shop 
                WHERE id_product = ' . (int)$product['id_product'] . ' 
                AND id_shop = ' . (int)$this->context->shop->id
            );
        }

        DealtHookHelper::applyTvaOnPresentedCartProduct($this->context, $product); // passe par référence
    }

    public function hookActionPresentCart(array $params)
    {
        if(!empty($this->context->cart->id)){
            $isTVAAttested = Db::getInstance()->getValue('SELECT is_validated FROM ' . _DB_PREFIX_ . 'dealt_tva_attestation 
            WHERE id_cart = '.$this->context->cart->id);
        } else {
            $isTVAAttested = null;
        }

        $this->context->smarty->assign('isTVAAttested', $isTVAAttested);
        if (empty($params['presentedCart'])) return;
        $presentedCart =& $params['presentedCart']; // référence
        DealtHookHelper::applyTvaOnPresentedCart($this->context, $presentedCart);
    }

    private function ensureTva10TaxId()
    {
        // taxe active à 10 %
        $idTax = (int)Db::getInstance()->getValue('
        SELECT t.id_tax
        FROM ' . _DB_PREFIX_ . 'tax t
        WHERE t.active = 1
          AND ABS(t.rate - 10.0) < 0.0001
        ORDER BY t.id_tax ASC
    ');

        if ($idTax) {
            return $idTax;
        }

        // Créer la taxe (si absente)
        $tax = new Tax();
        $tax->name = [];
        foreach (Language::getLanguages(false) as $lang) {
            $tax->name[(int)$lang['id_lang']] = 'TVA 10% (Dealt)';
        }
        $tax->rate = 10.0;
        $tax->active = 1;
        if (!$tax->add()) {
            return 0;
        }

        $idCountry = (int)Country::getByIso('FR');
        if ($idCountry) {
            $grp = new TaxRulesGroup();
            $grp->name = 'TVA 10% FR (Dealt)';
            $grp->active = 1;
            if ($grp->add()) {
                $rule = new TaxRule();
                $rule->id_tax_rules_group = (int)$grp->id;
                $rule->id_country = $idCountry;
                $rule->id_state = 0;
                $rule->zipcode_from = 0;
                $rule->zipcode_to = 0;
                $rule->id_tax = (int)$tax->id;
                $rule->behavior = 0;
                $rule->save();
            }
        }

        return (int)$tax->id;
    }

    /**
     * Hook pour personnaliser l'affichage des produits Dealt dans le panier
     * Note: id_category_default doit être enrichi via hookActionPresentCartProduct
     */
    public function hookDisplayCartProductLine($params)
    {
        if (!isset($params['product']['id_category_default'])) {
            return '';
        }

        // Si c'est un produit service (catégorie 369)
        if ((int)$params['product']['id_category_default'] === 369) {
            // N'afficher le bloc TVA qu'une seule fois par page (même si plusieurs lignes "service")
            static $tvaBlockRendered = false;

            // Réutilise les données déjà calculées pour le footer panier (URLs + état attestation)
            $cartFooterData = \Dealt\DealtHookHelper::buildCartFooterData($this->context) ?: [];

            // Assigner les données du produit au template
            $this->context->smarty->assign([
                'product' => $params['product'],
                'pro' => isset($params['pro']) ? $params['pro'] : null,
                'urls' => isset($params['urls']) ? $params['urls'] : $this->context->link,
                // TVA
                'dealt_tva_enabled' => !empty($cartFooterData['dealt_tva_enabled']),
                'tva_modal_url' => $cartFooterData['tva_modal_url'] ?? '',
                'tva_attestation_url' => $cartFooterData['tva_attestation_url'] ?? '',
                // Permet d'afficher le bloc TVA une seule fois (géré ici plutôt que dans Smarty)
                'dealt_show_tva_block' => !$tvaBlockRendered,
                'product_id_dealt' => $params['product']['id_product']
            ]);

            $tvaBlockRendered = true;

            // Retourner le template personnalisé
            return $this->display(__FILE__, 'views/templates/hook/cart_product_line_dealt.tpl');
        }

        // Pour les autres produits, ne rien retourner (affichage normal)
        return '';
    }

    /**
     * Affiche une promo "Besoin d'une pose ?" pour certains produits configurés
     */
    public function hookDisplayProductPromoInstallation($params)
    {
        // Espace PRO : ne jamais afficher la promo "pose"
        if ((int)$this->context->shop->id === 2) {
            return '';
        }

        $idProduct = isset($params['product']['id_product'])
            ? (int)$params['product']['id_product']
            : (int)Tools::getValue('id_product');

        if (!$idProduct) {
            return '';
        }

        // Charger la configuration des mappings
        $configFile = _PS_MODULE_DIR_ . 'dealt/config/promo_mappings.php';
        if (!file_exists($configFile)) {
            return '';
        }

        $mappings = include($configFile);

        // Vérifier si ce produit a une promo configurée
        if (!isset($mappings[$idProduct])) {
            return '';
        }

        $promoConfig = $mappings[$idProduct];

        // Vérifier que le lien existe
        if (empty($promoConfig['link_cta'])) {
            return '';
        }

        $this->context->smarty->assign([
            'promo_kit_link' => $promoConfig['link_cta'],
        ]);

        return $this->display(__FILE__, 'views/templates/hook/promo_installation.tpl');
    }

}