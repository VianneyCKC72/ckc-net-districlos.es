<?php
namespace CkcConfigurator\Options;

use CkcConfigurator\ProductFactory;
use CkcConfigurator\Configurator\Helper;

class Soubassement extends ProductFactory {

    // Get all heights existing for Soubassement
    // Getting colors of the first product selected by default
    static public function get($language_id)
    {
        $category = new \Category(self::SOUBASSEMENTS_ID);
        $products = $category->getProducts($language_id, 1, 1000000, null, null, false, true, false, 1, false);
        $heights = [];
        $colors = [];
        $types = [];

        if(count($products) > 0) {
            foreach ($products as $key => $product) {
                $combinations = static::getAttributesInformationsByProduct($product['id_product']);
                // Get Types
                foreach ($combinations as $combination) {
                    $combination['attribute'] = str_replace(' ', '-', strtolower($combination['attribute']));
                    if(strtolower($combination['group']) == 'hauteur') {
                        $height = explode('-', $combination['attribute']);
                        $height = array_key_exists(1, $height) ? $height[1] : $height[0];
                        if(Helper::valueExists($product['id_product'], $heights, "value") == false) {
                            $heights[] = [
                                "value" => $product['id_product'],
                                "name" => $product['name'],
                                "id_product_attribute" => $combination['id_product_attribute']
                            ];
                        }
                    }
                    if($key == 0 && strtolower($combination['group']) == 'couleur') {
                        $color = str_replace('-', ' ', $combination['attribute']);
                        if(Helper::valueExists($combination['attribute'], $colors, "value") == false) {
                            $colors[] = [
                                "value" => $combination['attribute'],
                                "name" => $color,
                                "image_url" => self::getImageUrl(\Product::getCombinationImageById($combination['id_product_attribute'], $language_id)),
                                "id_product_attribute" => $combination['id_product_attribute']
                            ];
                        }
                    }
                    if($key == 0 && strtolower($combination['group']) == 'type de poteau') {
                        $type = str_replace('-', ' ', $combination['attribute']);
                        if(Helper::valueExists($combination['attribute'], $colors, "value") == false) {
                            $types[] = [
                                "value" => $combination['attribute'],
                                "name" => $type,
                                "id_product_attribute" => $combination['id_product_attribute']
                            ];
                        }
                    }
                }
            }
        }
        // Add Types
        return ["heights" => $heights, "colors" => $colors, 'types' => $types];
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

    // Get all colors of the soubassement selected
    static public function getColors($id_product, $language_id)
    {
        $colors = [];

        $combinations = static::getAttributesInformationsByProduct($id_product);
        foreach ($combinations as $combination) {
            if (strtolower($combination['group']) == 'couleur') {
                $color = str_replace('-', ' ', $combination['attribute']);
                if(Helper::valueExists($combination['attribute'], $colors, "value") == false) {
                    $colors[] = [
                        "value" => $combination['attribute'],
                        "name" => $color,
                        "image_url" => self::getImageUrl(\Product::getCombinationImageById($combination['id_product_attribute'], $language_id)),
                        "id_product_attribute" => $combination['id_product_attribute'],
                    ];
                }
            }
        }

        return ["colors" => $colors];
    }

    static public function getType($id_product, $session, $language_id)
    {
        $combinations = static::getAttributesInformationsByProduct($id_product);

        foreach ($combinations as $combination) {
            if (strtolower($combination['group']) == 'type de poteau') {
                $type = str_replace('-', ' ', $combination['attribute']);
                if ($session->clips == 1 && strpos($type, 'clip') !== false) {
                    return $combination['id_product_attribute'];
                }else if ($session->clips == 0 && strpos($type, 'encoche') !== false) {
                    return $combination['id_product_attribute'];
                }
            }
        }

        return null;
    }

    static public function getImageUrl($image)
    {
        if(isset($image['id_image'])) {
            $image = new \Image($image['id_image']);
            return _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg";
        }

        return '';
    }
}
