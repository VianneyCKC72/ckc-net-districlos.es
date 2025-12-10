<?php
namespace CkcConfigurator\Options;

use CkcConfigurator\ProductFactory;
use CkcConfigurator\Configurator\Helper;

class Soubassement extends ProductFactory {

    // Get all soubassement products with their color variations
    static public function get($language_id, $session)
    {
        $category = new \Category(self::SOUBASSEMENTS_ID);
        $products = $category->getProducts($language_id, 1, 1000000, null, null, false, true, false, 1, false);
        $items = [];
        $processed_products = [];

        if(count($products) > 0) {
            foreach ($products as $key => $product) {
                if ($session->id_type != 177 && $product['id_product'] == 1257) {
                    continue;
                }
                
                // Éviter les doublons
                if (in_array($product['id_product'], $processed_products)) {
                    continue;
                }
                $processed_products[] = $product['id_product'];
                
                // Récupérer toutes les couleurs (variations) pour ce produit
                $colors_data = self::getColors($product['id_product'], $language_id);
                $variations = [];
                
                // Récupérer le type de poteau sélectionné (clips ou encoches)
                $clips = isset($session->clips) ? (int)$session->clips : null;
                
                foreach ($colors_data['colors'] as $color) {
                    // Filtrer les variations selon le type de poteau sélectionné
                    // Si clips est défini, ne garder que les déclinaisons correspondantes
                    if ($clips !== null && !self::matchesPoleType($product['id_product'], $color['id_product_attribute'], $clips, $language_id)) {
                        continue;
                    }
                    
                    // Récupérer le prix pour cette déclinaison
                    $price = Helper::getPrice($product['id_product'], $color['id_product_attribute'], 1);
                    
                    // Retirer le titre du produit du nom si nécessaire
                    $product_name = \Product::getProductName($product['id_product'], $color['id_product_attribute'], $language_id);
                    $name_without_title = $product_name;
                    $colon_pos = strpos($product_name, ' : ');
                    if ($colon_pos !== false) {
                        $name_without_title = substr($product_name, $colon_pos + 3);
                    }
                    
                    $variations[] = [
                        "id_product_attribute" => $color['id_product_attribute'],
                        "name"                 => $product_name,
                        "name_without_title"   => $name_without_title,
                        "price"                => $price['total_price'],
                        "price_formatted"      => \Tools::displayPrice($price['total_price']),
                        "image_url"            => $color['image_url'],
                    ];
                }
                
                if (empty($variations)) {
                    continue; // Pas de couleurs, on skip
                }
                
                // Récupérer l'image et le nom du produit principal
                $first_variation = $variations[0];
                $short_name = explode(' : ', $first_variation['name'])[0];
                if (empty($short_name)) {
                    $short_name = $product['name'];
                }
                
                // Récupérer l'image de couverture du produit principal, avec fallback vers l'image de la première variation
                $cover_image = \Product::getCover($product['id_product']);
                $first_image = $cover_image ? self::getImageUrl($cover_image) : $first_variation['image_url'];
                
                // Récupérer les détails du produit pour la tooltip
                $product_details = self::getProductDetails($product['id_product'], $first_variation['id_product_attribute'], $language_id);
                
                // Toujours créer un item avec select pour un rendu uniforme
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
                    $colorCode = explode(' ', $combination['attribute'])[1] ?? '';
                    $root_path        = '/modules/ckc_configurator/';

                    $colors[] = [
                        "value" => $combination['attribute'],
                        "name" => $color,
                        "image_url" => $id_product != 1257 ? self::getImageUrl(\Product::getCombinationImageById($combination['id_product_attribute'], $language_id)) : $root_path. 'views/images/couleurs/' . $colorCode . '.png',
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

    /**
     * Vérifie si une déclinaison correspond au type de poteau sélectionné (clips ou encoches)
     * Utilise la même logique que getType() mais pour vérifier une déclinaison spécifique
     * @param int $id_product L'ID du produit
     * @param int $id_product_attribute L'ID de la déclinaison à vérifier
     * @param int $clips 1 pour clips, 0 pour encoches
     * @param int $language_id L'ID de la langue
     * @return bool
     */
    static public function matchesPoleType($id_product, $id_product_attribute, $clips, $language_id)
    {
        $combinations = static::getAttributesInformationsByProduct($id_product);
        
        foreach ($combinations as $combination) {
            // Vérifier si cette combinaison appartient à la déclinaison recherchée
            if ($combination['id_product_attribute'] == $id_product_attribute) {
                if (strtolower($combination['group']) == 'type de poteau') {
                    $type = str_replace('-', ' ', strtolower($combination['attribute']));
                    if ($clips == 1 && strpos($type, 'clip') !== false) {
                        return true;
                    } else if ($clips == 0 && strpos($type, 'encoche') !== false) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    static public function getImageUrl($image)
    {
        if(isset($image['id_image'])) {
            $image = new \Image($image['id_image']);
            return _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg";
        }

        return '';
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
