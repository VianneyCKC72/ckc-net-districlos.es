<?php
namespace CkcConfigurator\Options;

use CkcConfigurator\ProductFactory;
use CkcConfigurator\Configurator\Helper;

class Soubassement extends ProductFactory {

    static public function get($language_id, $session)
    {
        $category = new \Category(self::SOUBASSEMENTS_ID);
        $products = $category->getProducts($language_id, 1, 1000000, null, null, false, true, false, 1, false);
        $items = [];
        $processed_products = [];

        if(count($products) > 0) {
            foreach ($products as $key => $product) {
                $rigide_types = [self::PROS_ID, self::PROS_PLUS_ID, self::BRICO_PLUS_ID];
                if (!in_array($session->id_type, $rigide_types) && $product['id_product'] == 1257) {
                    continue;
                }
                
                if (in_array($product['id_product'], $processed_products)) {
                    continue;
                }
                $processed_products[] = $product['id_product'];
                
                $combinations = static::getAttributesInformationsByProduct($product['id_product']);
                $variations = [];
                $clips = isset($session->clips) ? (int)$session->clips : null;
                $attributes_by_combination = [];
                foreach ($combinations as $combination) {
                    $id_attr = $combination['id_product_attribute'];
                    if (!isset($attributes_by_combination[$id_attr])) {
                        $attributes_by_combination[$id_attr] = [];
                    }
                    $attributes_by_combination[$id_attr][] = $combination;
                }
                
                foreach ($attributes_by_combination as $id_product_attribute => $combo_attrs) {
                    $has_pole_type = false;
                    $pole_type_matches = false;
                    
                    foreach ($combo_attrs as $combo_attr) {
                        if (strtolower($combo_attr['group']) == 'type de poteau') {
                            $has_pole_type = true;
                            $type = str_replace('-', ' ', strtolower($combo_attr['attribute']));
                            if ($clips === null) {
                                $pole_type_matches = true;
                            } else if ($clips == 1 && strpos($type, 'clip') !== false) {
                                $pole_type_matches = true;
                            } else if ($clips == 0 && (strpos($type, 'encoche') !== false || strpos($type, 'encoches') !== false)) {
                                $pole_type_matches = true;
                            }
                            break;
                        }
                    }
                    
                    if ($clips !== null) {
                        if (!$has_pole_type || !$pole_type_matches) {
                            continue;
                        }
                    }
                    
                    $price = Helper::getPrice($product['id_product'], $id_product_attribute, 1);
                    $product_name = \Product::getProductName($product['id_product'], $id_product_attribute, $language_id);
                    $name_without_title = static::extractNameWithoutTitle($product_name);
                    $image_url = null;
                    foreach ($combo_attrs as $combo_attr) {
                        if (strtolower($combo_attr['group']) == 'couleur') {
                            $colorCode = explode(' ', $combo_attr['attribute'])[1] ?? '';
                            $root_path = '/modules/ckc_configurator/';
                            if ($product['id_product'] != 1257) {
                                $image_url = static::getImageUrl(\Product::getCombinationImageById($id_product_attribute, $language_id));
                            } else {
                                $image_url = $root_path . 'views/images/couleurs/' . $colorCode . '.png';
                            }
                            break;
                        }
                    }
                    
                    if (!$image_url) {
                        $image_url = static::getImageUrl(\Product::getCombinationImageById($id_product_attribute, $language_id));
                    }
                    
                    $variations[] = [
                        "id_product_attribute" => $id_product_attribute,
                        "name"                 => $product_name,
                        "name_without_title"   => $name_without_title,
                        "price"                => $price['total_price'],
                        "price_formatted"      => \Tools::displayPrice($price['total_price']),
                        "image_url"            => $image_url,
                    ];
                }
                
                if (empty($variations)) {
                    continue;
                }
                
                $first_variation = $variations[0];
                $short_name = static::extractShortName($first_variation['name']);
                if (empty($short_name)) {
                    $short_name = $product['name'];
                }
                
                $cover_image = \Product::getCover($product['id_product']);
                $first_image = $cover_image ? static::getImageUrl($cover_image) : $first_variation['image_url'];
                $product_details = static::getProductDetails($product['id_product'], $first_variation['id_product_attribute'], $language_id);
                
                $items[] = [
                    "id"                   => $product['id_product'],
                    "image_url"            => $first_image,
                    "name"                 => $short_name,
                    "short_name"           => $short_name,
                    "variations"           => $variations,
                    "has_select"           => true,
                    "product_details"      => $product_details,
                ];
            }
        }
        
        return $items;
    }

    static public function getType($id_product, $session, $language_id)
    {
        $combinations = static::getAttributesInformationsByProduct($id_product);

        foreach ($combinations as $combination) {
            if (strtolower($combination['group']) == 'type de poteau') {
                $type = str_replace('-', ' ', strtolower($combination['attribute']));
                if ($session->clips == 1 && strpos($type, 'clip') !== false) {
                    return $combination['id_product_attribute'];
                } else if ($session->clips == 0 && (strpos($type, 'encoche') !== false || strpos($type, 'encoches') !== false)) {
                    return $combination['id_product_attribute'];
                }
            }
        }

        return null;
    }

    static public function matchesPoleType($id_product, $id_product_attribute, $clips, $language_id)
    {
        $combinations = static::getAttributesInformationsByProduct($id_product);
        
        foreach ($combinations as $combination) {
            if ($combination['id_product_attribute'] == $id_product_attribute) {
                if (strtolower($combination['group']) == 'type de poteau') {
                    $type = str_replace('-', ' ', strtolower($combination['attribute']));
                    if ($clips == 1 && strpos($type, 'clip') !== false) {
                        return true;
                    } else if ($clips == 0 && (strpos($type, 'encoche') !== false || strpos($type, 'encoches') !== false)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

}
