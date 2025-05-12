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
            $category_configurator = self::PORTILLONS_SOUPLES_ID;
        }
        elseif ($is_souple) {
            $category_configurator = self::PORTILLONS_ALUMINIUM_ID;
        }

        $category = new \Category($category_configurator);
        $products = $category->getProducts($language_id, 1, 1000000);
        $items    = [];

        foreach ($products as $product) {
            $combinations = \Product::getAttributesInformationsByProduct($product['id_product']);
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
                    $items[] = [
                        "id"                   => $product['id_product'],
                        "image_url"            => self::getImageUrl(\Product::getCombinationImageById($combination['id_product_attribute'], $language_id)),
                        "name"                 => \Product::getProductName($product['id_product'], $combination['id_product_attribute'], $language_id),
                        "id_product_attribute" => $combination['id_product_attribute']
                    ];
                }
            }
        }

        return $items;
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
            if ($search == 1.03 && $item == 1.00) {
                $closest = $item;
            }else if ($search == 1.23 && $item == 1.25) {
                $closest = $item;
            } else if ($search == 1.53 && $item == 1.50) {
                $closest = $item;
            } else if ($search == 1.73 && $item == 1.75) {
                $closest = $item;
            } else if ($search == 1.93 && $item == 1.90) {
                $closest = $item;
            }
        }
        return $closest;
    }
}
