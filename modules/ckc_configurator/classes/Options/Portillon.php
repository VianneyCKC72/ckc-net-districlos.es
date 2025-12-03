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
        
        // Produits à toujours regrouper par déclinaisons
        $grouped_product_ids = [1182, 1213];
        
        // Ajouter les produits manquants s'ils existent et sont actifs
        $found_ids = array_column($products, 'id_product');
        foreach (array_diff($grouped_product_ids, $found_ids) as $product_id) {
            $product_obj = new \Product($product_id, false, $language_id);
            if (\Validate::isLoadedObject($product_obj) && $product_obj->active) {
                $products[] = ['id_product' => $product_id, 'name' => $product_obj->name];
            }
        }

        // S'il y a un sousbassement, calculer la hauteur ajustée
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
            
            // Récupérer toutes les hauteurs disponibles
            foreach ($combinations as $combination) {
                $current_height = floatval(str_replace(['Ht ', 'ht-', 'm'], ['', '', '.'], $combination['attribute']));
                if (($combination['group'] == 'Hauteur' || $combination['group'] == 'hauteur') && !in_array($current_height, $heights)) {
                    $heights[] = $current_height;
                }
            }
            
            if (empty($heights)) {
                continue; // Pas de hauteur, on skip
            }
            
            sort($heights, SORT_NUMERIC);
            $wantedHeight = self::getClosest($height + $sousbassement_height, $heights);
            
            // Récupérer toutes les déclinaisons à la hauteur voulue
            foreach ($combinations as $combination) {
                $current_height = floatval(str_replace(['Ht ', 'ht-', 'm'], ['', '', '.'], $combination['attribute']));
                if (($combination['group'] == 'Hauteur' || $combination['group'] == 'hauteur') && $current_height == $wantedHeight) {
                    $id_product_attribute = $combination['id_product_attribute'];
                    if (!isset($variations_by_height[$id_product_attribute])) {
                        $product_name = \Product::getProductName($product['id_product'], $id_product_attribute, $language_id);
                        $price = Helper::getPrice($product['id_product'], $id_product_attribute, 1);
                        
                        // Retirer le titre du produit du nom (partie avant le premier " : ")
                        $name_without_title = $product_name;
                        $colon_pos = strpos($product_name, ' : ');
                        if ($colon_pos !== false) {
                            $name_without_title = substr($product_name, $colon_pos + 3);
                        }
                        
                        $variations_by_height[$id_product_attribute] = [
                            "id_product_attribute" => $id_product_attribute,
                            "name"                 => $product_name,
                            "name_without_title"   => $name_without_title,
                            "height"               => $current_height,
                            "price"                => $price['total_price'],
                            "price_formatted"      => \Tools::displayPrice($price['total_price']),
                        ];
                    }
                }
            }
            
            if (empty($variations_by_height)) {
                continue; // Pas de déclinaisons à cette hauteur
            }
            
            $variations = array_values($variations_by_height);
            $first_variation = $variations[0];
            $short_name = explode(' : ', $first_variation['name'])[0];
            $first_image = self::getImageUrl(\Product::getCombinationImageById($first_variation['id_product_attribute'], $language_id));
            
            // Récupérer les détails du produit pour la tooltip
            $product_details = self::getProductDetails($product['id_product'], $first_variation['id_product_attribute'], $language_id);
            
            // Si plusieurs déclinaisons OU produit à regrouper, créer un item avec select
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
                // Une seule déclinaison, comportement normal - générer la description pour l'affichage
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

    /**
     * Récupère les caractéristiques du produit pour affichage dans la tooltip
     */
    static public function getProductDetails($id_product, $id_product_attribute, $language_id)
    {
        $product_obj = new \Product($id_product, false, $language_id);
        
        if (!\Validate::isLoadedObject($product_obj)) {
            return [];
        }

        $details = [];
        
        // Caractéristiques (features)
        $features = $product_obj->getFrontFeatures($language_id);
        if (!empty($features)) {
            $grouped_features = [];
            foreach ($features as $feature) {
                $grouped_features[] = [
                    'name' => $feature['name'],
                    'value' => $feature['value']
                ];
            }
            $details['grouped_features'] = $grouped_features;
        }
        
        return $details;
    }
}
