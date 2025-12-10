<?php

namespace CkcConfigurator\Options;

use CkcConfigurator\Configurator\Helper;
use CkcConfigurator\ProductFactory;

class Portillon extends ProductFactory
{

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
        $grouped_product_ids = [1182, 1213];
        $found_ids = array_column($products, 'id_product');
        foreach (array_diff($grouped_product_ids, $found_ids) as $product_id) {
            $product_obj = new \Product($product_id, false, $language_id);
            if (\Validate::isLoadedObject($product_obj) && $product_obj->active) {
                $products[] = ['id_product' => $product_id, 'name' => $product_obj->name];
            }
        }

        $sousbassement_height = 0;
        if ($sousbassement_id == 416) {
            $sousbassement_height = 0.25;
        }
        elseif ($sousbassement_id == 806) {
            $sousbassement_height = 0.50;
        }

        foreach ($products as $product) {
            $combinations = static::getAttributesInformationsByProduct($product['id_product']);
            $heights      = [];
            $variations_by_height = [];
            
            foreach ($combinations as $combination) {
                $current_height = floatval(str_replace(['Ht ', 'ht-', 'm'], ['', '', '.'], $combination['attribute']));
                if (($combination['group'] == 'Hauteur' || $combination['group'] == 'hauteur') && !in_array($current_height, $heights)) {
                    $heights[] = $current_height;
                }
            }
            
            if (empty($heights)) {
                continue;
            }
            
            sort($heights, SORT_NUMERIC);
            $wantedHeight = static::getClosest($height + $sousbassement_height, $heights);
            
            foreach ($combinations as $combination) {
                $current_height = floatval(str_replace(['Ht ', 'ht-', 'm'], ['', '', '.'], $combination['attribute']));
                if (($combination['group'] == 'Hauteur' || $combination['group'] == 'hauteur') && $current_height == $wantedHeight) {
                    $id_product_attribute = $combination['id_product_attribute'];
                    if (!isset($variations_by_height[$id_product_attribute])) {
                        $product_name = \Product::getProductName($product['id_product'], $id_product_attribute, $language_id);
                        $price = Helper::getPrice($product['id_product'], $id_product_attribute, 1);
                        $name_without_title = static::extractNameWithoutTitle($product_name);
                        $variation_image = static::getImageUrl(\Product::getCombinationImageById($id_product_attribute, $language_id));
                        
                        $variations_by_height[$id_product_attribute] = [
                            "id_product_attribute" => $id_product_attribute,
                            "name"                 => $product_name,
                            "name_without_title"   => $name_without_title,
                            "height"               => $current_height,
                            "price"                => $price['total_price'],
                            "price_formatted"      => \Tools::displayPrice($price['total_price']),
                            "image_url"            => $variation_image,
                        ];
                    }
                }
            }
            
            if (empty($variations_by_height)) {
                continue;
            }
            
            $variations = array_values($variations_by_height);
            $first_variation = $variations[0];
            $short_name = static::extractShortName($first_variation['name']);
            if (empty($short_name)) {
                $short_name = $product['name'];
            }
            $first_image = static::getImageUrl(\Product::getCombinationImageById($first_variation['id_product_attribute'], $language_id));
            $product_details = static::getProductDetails($product['id_product'], $first_variation['id_product_attribute'], $language_id);
            
            if (count($variations) > 1 || in_array($product['id_product'], $grouped_product_ids)) {
                $items[] = [
                    "id"                   => $product['id_product'],
                    "image_url"            => $first_image,
                    "name"                 => $short_name,
                    "short_name"           => $short_name,
                    "variations"           => $variations,
                    "has_select"           => true,
                    "product_details"      => $product_details,
                ];
            } else {
                $description = str_replace($short_name, '', $first_variation['name']);
                $description = str_replace(' :', '', $description);
                $description = explode(' ,', $description);
                $description = implode('<br>', $description);
                
                $items[] = [
                    "id"                   => $product['id_product'],
                    "image_url"            => $first_image,
                    "name"                 => $first_variation['name'],
                    "id_product_attribute" => $first_variation['id_product_attribute'],
                    "short_name"           => $short_name,
                    "description"          => $description,
                    "product_details"      => $product_details,
                ];
            }
        }

        return $items;
    }

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
