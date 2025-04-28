<?php
namespace CkcConfigurator\Options;

use CkcConfigurator\ProductFactory;

class KitMontage extends ProductFactory {

	// Get kit de montage depending on type of pose
    static public function getIdKit($language_id, $installation)
    {
        $installation = ($installation == 'scellement' || $installation == 'scellement-sur-muret') ?
            ["id_category" => self::KITSCELLEMENT_ID] :
            ["id_category" => self::KITSPLATINE_ID];
        $kit        = new \Category(self::KITSDEMONTAGE_ID, $language_id);
        $products   = $kit->getProducts($language_id, 1, 1000000, null, null, false, true, false, 1, false);

        if(count($products) > 0) {
            foreach($products as $product) {
                if(\Product::idIsOnCategoryId($product["id_product"], [$installation])) {
                    $combinations = static::getAttributesInformationsByProduct($product["id_product"]);
                    return array(
                        'product' => $product["id_product"],
                        'attribute' => $combinations[0]['id_product_attribute']
                    );
                }
            }
        }

        return false;
    }
    
    public static function getAttributesInformationsByProduct($id_product)
    {
        $result = \Db::getInstance()->executeS('
            SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`, pac.`id_product_attribute`
            FROM `'._DB_PREFIX_.'attribute` a
            LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
                ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)\Context::getContext()->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
                ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)\Context::getContext()->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
                ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
            '.\Shop::addSqlAssociation('product_attribute', 'pa').'
            '.\Shop::addSqlAssociation('attribute', 'pac').'
            WHERE pa.`id_product` = '.(int)$id_product);
    
        return $result;
    }
}