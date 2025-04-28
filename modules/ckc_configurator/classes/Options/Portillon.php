<?php

namespace CkcConfigurator\Options;

use CkcConfigurator\Configurator\Helper;
use CkcConfigurator\ProductFactory;

class Portillon extends ProductFactory
{

    // Get all portillon selected for configurator
    static public function get($language_id, $id_product_attribute, $sousbassement_id, $is_souple, $is_aluminium)
    {
        if (!$id_product_attribute) {
            return [];
        }
        $sql   = 'SELECT name FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (al.id_attribute = pac.id_attribute) WHERE id_product_attribute = ' . $id_product_attribute . ' AND id_lang = ' . $language_id;
        $names = \Db::getInstance()->ExecuteS($sql);

        foreach ($names as $name) {
            if (strpos($name['name'], 'Ht ') !== false || strpos($name['name'], '1m') !== false || strpos($name['name'], '2m') !== false) {
                $height = floatval(str_replace('m', '.', str_replace('Ht ', '', $name['name'])));
            }
        }

        $category_configurator = self::PORTILLONS_RIGIDES_ID;
        if ($is_aluminium) {
            $category_configurator = self::PORTILLONS_ALUMINIUM_ID;
        }
        elseif ($is_souple) {
            $category_configurator = self::PORTILLONS_SOUPLES_ID;
        }

        $category = new \Category($category_configurator);
        $products = $category->getProducts($language_id, 1, 1000000, null, null, false, true, false, 1, false);
        $items    = [];

        foreach ($products as $product) {
            $combinations = static::getAttributesInformationsByProduct($product['id_product']);
            $changed      = false;
            $heights      = [];

            foreach ($combinations as $combination) {
                $current_height = floatval(str_replace(['Ht ', 'ht-', 'm'], ['', '', '.'], $combination['attribute']));

                if (($combination['group'] == 'Hauteur' || $combination['group'] == 'hauteur') && !in_array($current_height, $heights)) {
                    $heights[] = $current_height;
                }
            }

            // S'il y a un sousbassement, le portillon doit être plus haut de la hauteur du sousbassement (portillon d'1m => 1,25m si sousbassement de 0,25)
            $sousbassement_height = 0;
            if ($sousbassement_id == 416) {
                $sousbassement_height = 0.25;
            }
            elseif ($sousbassement_id == 806) {
                $sousbassement_height = 0.50;
            }

            sort($heights, SORT_NUMERIC);

            $wantedHeight = self::getClosest($height + $sousbassement_height, $heights);

            foreach ($combinations as $combination) {
                $current_height = floatval(str_replace(['Ht ', 'ht-', 'm'], ['', '', '.'], $combination['attribute']));

                if (($combination['group'] == 'Hauteur' || $combination['group'] == 'hauteur') && ($current_height == $wantedHeight)) {
                    $short_name = explode(' : ', \Product::getProductName($product['id_product'], $combination['id_product_attribute'], $language_id))[0];
                    $description = str_replace($short_name, '', \Product::getProductName($product['id_product'], $combination['id_product_attribute'], $language_id));
                    $description = str_replace(' :', '', $description);
                    $description = explode(' ,', $description);
                    $description = implode('<br>', $description);

                    $items[] = [
                        "id"                   => $product['id_product'],
                        "image_url"            => self::getImageUrl(\Product::getCombinationImageById($combination['id_product_attribute'], $language_id)),
                        "name"                 => \Product::getProductName($product['id_product'], $combination['id_product_attribute'], $language_id),
                        "id_product_attribute" => $combination['id_product_attribute'],
                        "short_name"           => explode(' : ', \Product::getProductName($product['id_product'], $combination['id_product_attribute'], $language_id))[0],
                        "description"          => $description,
                    ];
                }
            }
        }

        return $items;
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

    // Set needed portillon
    static public function set($language_id, $id, $id_product_attribute)
    {
        $price = Helper::getPrice($id, $id_product_attribute, 1);

        return [
            "id"           => $id,
            "name"         => \Product::getProductName($id, $id_product_attribute, $language_id),
            "quantity"     => 1,
            "id_attribute" => $id_product_attribute,
            "price"        => $price['total_price'],
            'unit_price'   => $price['unit_price']
        ];
    }

    static public function getImageUrl($image)
    {
        if (isset($image['id_image'])) {
            $image = new \Image($image['id_image']);
            return _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
        }

        return '';
    }

    static public function getClosest($search, $arr)
    {
        $closest = null;
        foreach ($arr as $item) {
            if ($closest === null || abs($search - $closest) > abs($item - $search)) {
                $closest = $item;
            }
        }
        return $closest;
    }
}
