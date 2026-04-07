<?php
/**
 * Stock dispo : évite les 1062 sur product_sqlstock.
 *
 * 1) Lecture master (pas l’esclave) pour coller aux écritures.
 * 2) Si aucune ligne pour la boutique calculée par addSqlShopRestriction mais qu’une ligne
 *    orpheline id_shop=0 / id_shop_group=0 existe déjà, on la retourne — sinon le core retente
 *    un INSERT 0-0 et MySQL lève un doublon.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class StockAvailable extends StockAvailableCore
{
    public static function getStockAvailableIdByProductId($id_product, $id_product_attribute = null, $id_shop = null)
    {
        if (!Validate::isUnsignedId($id_product)) {
            return false;
        }

        $query = new DbQuery();
        $query->select('id_stock_available');
        $query->from('stock_available');
        $query->where('id_product = ' . (int) $id_product);

        if ($id_product_attribute !== null) {
            $query->where('id_product_attribute = ' . (int) $id_product_attribute);
        }

        $query = StockAvailable::addSqlShopRestriction($query, $id_shop);

        $id = (int) Db::getInstance()->getValue($query);
        if ($id > 0) {
            return $id;
        }

        $fallback = new DbQuery();
        $fallback->select('id_stock_available');
        $fallback->from('stock_available');
        $fallback->where('id_product = ' . (int) $id_product);
        if ($id_product_attribute !== null) {
            $fallback->where('id_product_attribute = ' . (int) $id_product_attribute);
        }
        $fallback->where('id_shop = 0 AND id_shop_group = 0');

        return (int) Db::getInstance()->getValue($fallback);
    }
}
