<?php

class SpecificPrice extends SpecificPriceCore
{
    public static function getSpecificPrice(
        $id_product,
        $id_shop,
        $id_currency,
        $id_country,
        $id_group,
        $quantity,
        $id_product_attribute = null,
        $id_customer = 0,
        $id_cart = 0,
        $real_quantity = 0
    ) {

        if (!SpecificPriceCore::couldHaveSpecificPrice($id_product)) {
            return [];
        }

        require_once __DIR__ . '/../../modules/ckcspcache/classes/SpecificPriceCache.php';
        require_once __DIR__ . '/../../modules/ckcspcache/classes/CacheStorageFile.php';


        $cache = new SpecificPriceCache(Module::getInstanceByName('ckcspcache'));

        // 1) feature active / couldHaveSpecificPrice : tu peux laisser le core décider
        if (!SpecificPriceCore::isFeatureActive()) {
            return [];
        }

        $keyParts = [
            'id_product' => (int)$id_product,
            'id_shop' => (int)$id_shop,
            'id_currency' => (int)$id_currency,
            'id_country' => (int)$id_country,
            'id_group' => (int)$id_group,
            'quantity' => (int)$quantity,
            'id_product_attribute' => (int)($id_product_attribute ?? 0),
            'id_customer' => (int)$id_customer,
            'id_cart' => (int)$id_cart,
            'real_quantity' => (int)$real_quantity,
        ];

        $cached = $cache->getCachedRow($keyParts);
        if ($cached !== null) {
            return $cached;
        }

        // 2) sinon exécute le core, puis stocke
        $row = SpecificPriceCore::getSpecificPrice(
            $id_product,
            $id_shop,
            $id_currency,
            $id_country,
            $id_group,
            $quantity,
            $id_product_attribute,
            $id_customer,
            $id_cart,
            $real_quantity
        );

        $cache->setCachedRow($keyParts, $row);

        return $row;
    }
}