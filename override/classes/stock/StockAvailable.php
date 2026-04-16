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
    // Cache statique en mémoire pour éviter même les appels au système de cache PrestaShop
    private static $quantity_cache = [];

    /**
     * Pré-charge les stocks pour plusieurs produits en UNE SEULE requête groupée
     * À appeler avant de charger une liste de produits pour optimiser massivement
     *
     * @param array $product_ids Array de id_product
     * @param int|null $id_shop Shop ID
     */
    public static function preloadQuantitiesForProducts(array $product_ids, $id_shop = null)
    {
        if (empty($product_ids)) {
            return;
        }

        // Normaliser les IDs
        $product_ids = array_map('intval', $product_ids);
        $id_shop = $id_shop ?: (int)Context::getContext()->shop->id;

        // Récupérer TOUS les stocks en une seule requête
        $query = new DbQuery();
        $query->select('id_product, id_product_attribute, SUM(quantity) as quantity');
        $query->from('stock_available');
        $query->where('id_product IN (' . implode(',', $product_ids) . ')');
        $query = self::addSqlShopRestriction($query, $id_shop);
        $query->groupBy('id_product, id_product_attribute');

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        // Pré-remplir le cache statique
        foreach ($results as $row) {
            $static_key = (int)$row['id_product'] . '_' . (int)$row['id_product_attribute'] . '_' . $id_shop;
            self::$quantity_cache[$static_key] = (int)$row['quantity'];

            // Pré-remplir aussi le cache PrestaShop
            $cache_id = 'StockAvailable::getQuantityAvailableByProduct_' . (int)$row['id_product'] . '-' . (int)$row['id_product_attribute'] . '-' . $id_shop;
            Cache::store($cache_id, (int)$row['quantity']);
        }
    }

    /**
     * Override de getQuantityAvailableByProduct avec double cache (statique + PrestaShop)
     * Requête appelée massivement depuis Product::getProductsProperties et modules
     */
    public static function getQuantityAvailableByProduct($id_product = null, $id_product_attribute = null, $id_shop = null)
    {
        // Normaliser id_product_attribute
        if ($id_product_attribute === null) {
            $id_product_attribute = 0;
        }

        // Clé de cache statique
        $static_key = (int)$id_product . '_' . (int)$id_product_attribute . '_' . (int)$id_shop;

        // Vérifier le cache statique en mémoire (ultra-rapide)
        if (isset(self::$quantity_cache[$static_key])) {
            return self::$quantity_cache[$static_key];
        }

        // Appeler la méthode parente qui utilise déjà Cache::store/retrieve
        $result = parent::getQuantityAvailableByProduct($id_product, $id_product_attribute, $id_shop);

        // Stocker dans le cache statique pour les appels suivants dans la même requête HTTP
        self::$quantity_cache[$static_key] = $result;

        return $result;
    }

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
