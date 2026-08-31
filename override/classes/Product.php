<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
class Product extends ProductCore
{
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
    * date: 2026-08-28 14:28:38
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
}