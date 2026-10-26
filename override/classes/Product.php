<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Product extends ProductCore
{
    /** @var array Buffer POST webservice (pas d'id encore) */
    protected $dtcOdooExtraAdvicePending = [];

    /** @var string[] */
    protected static $dtcOdooExtraAdviceFields = [
        'extra_advice_title',
        'extra_advice_content',
        'maintenance_title',
        'maintenance_content',
    ];

    public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, Context $context = null)
    {
        parent::__construct($id_product, $full, $id_lang, $id_shop, $context);

        foreach (self::$dtcOdooExtraAdviceFields as $field) {
            $suffix = Tools::toCamelCase($field, true);
            $this->webserviceParameters['fields'][$field] = [
                'getter' => 'getWs' . $suffix,
                'setter' => 'setWs' . $suffix,
            ];
        }
    }

    public function getWsExtraAdviceTitle()
    {
        return $this->dtcOdooGetExtraAdvice('extra_advice_title');
    }

    public function setWsExtraAdviceTitle($value)
    {
        return $this->dtcOdooSetExtraAdvice('extra_advice_title', $value);
    }

    public function getWsExtraAdviceContent()
    {
        return $this->dtcOdooGetExtraAdvice('extra_advice_content');
    }

    public function setWsExtraAdviceContent($value)
    {
        return $this->dtcOdooSetExtraAdvice('extra_advice_content', $value);
    }

    public function getWsMaintenanceTitle()
    {
        return $this->dtcOdooGetExtraAdvice('maintenance_title');
    }

    public function setWsMaintenanceTitle($value)
    {
        return $this->dtcOdooSetExtraAdvice('maintenance_title', $value);
    }

    public function getWsMaintenanceContent()
    {
        return $this->dtcOdooGetExtraAdvice('maintenance_content');
    }

    public function setWsMaintenanceContent($value)
    {
        return $this->dtcOdooSetExtraAdvice('maintenance_content', $value);
    }

    /**
     * @param string $field
     *
     * @return string
     */
    protected function dtcOdooGetExtraAdvice($field)
    {
        if ((int) $this->id <= 0 || !in_array($field, self::$dtcOdooExtraAdviceFields, true)) {
            return '';
        }

        $value = Db::getInstance()->getValue(
            'SELECT `' . bqSQL($field) . '` FROM `' . _DB_PREFIX_ . 'ckc_product_extra`
             WHERE `id_product` = ' . (int) $this->id
        );

        return $value !== false && $value !== null ? (string) $value : '';
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return bool
     */
    protected function dtcOdooSetExtraAdvice($field, $value)
    {
        if (!in_array($field, self::$dtcOdooExtraAdviceFields, true)) {
            return false;
        }

        $this->dtcOdooExtraAdvicePending[$field] = (string) $value;

        if ((int) $this->id > 0) {
            return $this->dtcOdooSaveExtraAdvice([$field => $value]);
        }

        return true;
    }

    /**
     * @param array $fields
     *
     * @return bool
     */
    protected function dtcOdooSaveExtraAdvice(array $fields)
    {
        $idProduct = (int) $this->id;
        if ($idProduct <= 0) {
            return false;
        }

        $data = [];
        foreach ($fields as $key => $value) {
            if (in_array($key, self::$dtcOdooExtraAdviceFields, true)) {
                $data[$key] = pSQL((string) $value, true);
            }
        }
        if (!$data) {
            return true;
        }

        $exists = (bool) Db::getInstance()->getValue(
            'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'ckc_product_extra` WHERE `id_product` = ' . $idProduct
        );

        if ($exists) {
            return Db::getInstance()->update('ckc_product_extra', $data, 'id_product = ' . $idProduct);
        }

        foreach (self::$dtcOdooExtraAdviceFields as $field) {
            if (!isset($data[$field])) {
                $data[$field] = '';
            }
        }
        $data['id_product'] = $idProduct;

        return Db::getInstance()->insert('ckc_product_extra', $data);
    }

    public function addWs($autodate = true, $null_values = false)
    {
        $result = parent::addWs($autodate, $null_values);
        if ($result && (int) $this->id > 0 && !empty($this->dtcOdooExtraAdvicePending)) {
            $this->dtcOdooSaveExtraAdvice($this->dtcOdooExtraAdvicePending);
            $this->dtcOdooExtraAdvicePending = [];
        }

        return $result;
    }

    public static function getIdTaxRulesGroupByIdProduct($id_product, Context $context = null)
    {
        $ctx  = Context::getContext();
        $cart = $ctx->cart;
        if ($cart && $cart->id) {
            $address = new Address((int) $cart->id_address_delivery, (int) $ctx->language->id);
        }
        if ($cart && $cart->id && isset($address) && self::isFr($address) && self::isAttested((int)$cart->id)) {
            $id_main_product = Db::getInstance()->getValue('SELECT id_main_product FROM '._DB_PREFIX_.'dealt_tva_attestation 
            WHERE id_cart = '.$cart->id);
            $serviceId = Db::getInstance()->getValue('SELECT cp.id_product FROM '._DB_PREFIX_.'cart_product cp
            LEFT JOIN '._DB_PREFIX_.'product p ON p.id_product = cp.id_product
            WHERE p.id_category_default = 369 AND cp.id_cart = '.Context::getContext()->cart->id);
            $ifPack = self::checkIfPack($id_main_product);
            if($ifPack){
                $packProducts = array_map(fn($value) => (int)$value['id_product'], self::getPackProducts($id_main_product));
                if(in_array($id_product, $packProducts) OR $id_product == $serviceId OR $id_product == $id_main_product){
                    return 2;
                } else {
                    return parent::getIdTaxRulesGroupByIdProduct($id_product, $context);
                }
            } else {
                if($id_product == $id_main_product OR $id_product == $serviceId) {
                    return 2;
                } else {
                    return parent::getIdTaxRulesGroupByIdProduct($id_product, $context);
                }
            }
        } else {
            return parent::getIdTaxRulesGroupByIdProduct($id_product, $context);
        }
    }
    protected static function isFr(Address $addr): bool
    {
        return $addr->id_country && strtoupper(Country::getIsoById((int)$addr->id_country)) === 'FR';
    }
    protected static function isAttested(int $idCart): bool
    {
        static $cache = [];
        if (isset($cache[$idCart])) {
            return $cache[$idCart];
        }
        $table = _DB_PREFIX_.'dealt_tva_attestation';
        $cache[$idCart] = (bool)Db::getInstance()->getValue(
            "SELECT 1 FROM `$table` WHERE id_cart=".(int)$idCart." AND is_validated=1"
        );
        return $cache[$idCart];
    }
    public static function checkIfPack($id_product){
        $ifTable = Db::getInstance()->execute('SHOW TABLES LIKE "'._DB_PREFIX_.'pm_advancedpack"');
        if(!$ifTable){
            return false;
        } else {
            $check = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'pm_advancedpack` WHERE `id_pack` = '.(int)$id_product.' AND id_shop = '.(int)Context::getContext()->shop->id);
            return !empty($check[0]);
        }
    }
    public static function getPackProducts($id_pack){
        $ifTable = Db::getInstance()->execute('SHOW TABLES LIKE "'._DB_PREFIX_.'pm_advancedpack_products"');
        if(!$ifTable){
            return false;
        } else {
            $packProducts = Db::getInstance()->executeS('SELECT pap.id_product FROM `'._DB_PREFIX_.'pm_advancedpack_products` pap WHERE pap.id_pack = '.(int)$id_pack);
            return $packProducts;
        }
    }
    public function add($autoDate = true, $nullValues = false)
    {
        $db = Db::getInstance();
        $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'product_shop` WHERE `id_product` = 0');
        $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'product_lang` WHERE `id_product` = 0');
        $result = parent::add($autoDate, $nullValues);
        if ($result && (empty($this->id) || (int)$this->id === 0)) {
            $realId = (int)$db->getValue(
                'SELECT MAX(`id_product`) FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` > 0'
            );
            if ($realId > 0) {
                $this->id = $realId;
                $db->execute('UPDATE `' . _DB_PREFIX_ . 'product_shop` SET `id_product` = ' . $realId . ' WHERE `id_product` = 0');
                $db->execute('UPDATE `' . _DB_PREFIX_ . 'product_lang` SET `id_product` = ' . $realId . ' WHERE `id_product` = 0');
            }
        }
        return $result;
    }
    
    public static function getProductsProperties($id_lang, $query_result)
    {
        if (is_array($query_result) && !empty($query_result)) {
            $product_ids = array_unique(array_column($query_result, 'id_product'));
            StockAvailable::preloadQuantitiesForProducts($product_ids);
        }
        return parent::getProductsProperties($id_lang, $query_result);
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function getAnchor($id_product_attribute, $with_id = false)
    {
        if (Module::isEnabled('ets_awesomeurl') && (bool) Configuration::get('ETS_AWU_ENABLE_REMOVE_ID_IN_URL')) {
            if ((bool) Configuration::get('ETS_AWU_ENABLE_REMOVE_ATTR_ALIAS')) {
                return '';
            }
            if ((bool) Configuration::get('ETS_AWU_ENABLE_REMOVE_ID_ATTR_ALIAS')) {
                return parent::getAnchor($id_product_attribute, false);
            }
        }
        return parent::getAnchor($id_product_attribute, $with_id);
    }

    /**
     * CKC Vianney — évite le die PS quand Context n'a ni cart ni employee (API, hooks, id_cart=0).
     * Ne change pas le calcul : pose juste un Cart vide si besoin, puis délègue au core.
     */
    public static function getPriceStatic(
        $id_product,
        $usetax = true,
        $id_product_attribute = null,
        $decimals = 6,
        $divisor = null,
        $only_reduc = false,
        $usereduc = true,
        $quantity = 1,
        $force_associated_tax = false,
        $id_customer = null,
        $id_cart = null,
        $id_address = null,
        &$specific_price_output = null,
        $with_ecotax = true,
        $use_group_reduction = true,
        Context $context = null,
        $use_customer_price = true,
        $id_customization = null
    ) {
        if (!$context) {
            $context = Context::getContext();
        }

        if (!is_object($context->cart) && empty($id_cart) && !isset($context->employee)) {
            $context->cart = new Cart();
        }

        return parent::getPriceStatic(
            $id_product,
            $usetax,
            $id_product_attribute,
            $decimals,
            $divisor,
            $only_reduc,
            $usereduc,
            $quantity,
            $force_associated_tax,
            $id_customer,
            $id_cart,
            $id_address,
            $specific_price_output,
            $with_ecotax,
            $use_group_reduction,
            $context,
            $use_customer_price,
            $id_customization
        );
    }
}
