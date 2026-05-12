<?php
/**
 *  Order Fees Shipping
 *
 *  @author    motionSeed <ecommerce@motionseed.com>
 *  @copyright 2017 motionSeed. All rights reserved.
 *  @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
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

    /**
     * Override de getProductsProperties pour pré-charger les stocks en masse
     * Réduit drastiquement les requêtes SQL stock_available (1000+ → 1)
     */
    public static function getProductsProperties($id_lang, $query_result)
    {
        // Extraire tous les id_product de la liste pour pré-charger les stocks
        if (is_array($query_result) && !empty($query_result)) {
            $product_ids = array_unique(array_column($query_result, 'id_product'));

            // Pré-charger TOUS les stocks en UNE SEULE requête groupée
            StockAvailable::preloadQuantitiesForProducts($product_ids);
        }

        // Appeler la méthode parente qui utilisera le cache pré-rempli
        return parent::getProductsProperties($id_lang, $query_result);
    }
}