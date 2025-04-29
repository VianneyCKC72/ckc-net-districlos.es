<?php
namespace CkcConfigurator\Options;

use CkcConfigurator\Configurator\Helper;
use CkcConfigurator\ProductFactory;

class PanneauDeco extends ProductFactory {

    static public function get($language_id, $isAluminium, $session)
    {
        if (!$isAluminium) {
            return [];
        }

        $height = floatval(str_replace(['Ht ', 'ht-', 'm'], ['', '', '.'], $session->height));

        $category = new \Category(self::PANNEAUX_DECO_ALUMINIUM_ID);
        $products = $category->getProducts($language_id, 1, 1000000, null, null, false, true, false, 1, false);
        $items = [];

        foreach ($products as $product) {
            $items[] = [
                "id" => $product['id_product'],
                "image_url" => self::getImageUrl(\Product::getCombinationImageById($product['id_product_attribute'], $language_id)),
                "name" => \Product::getProductName($product['id_product'], $product['id_product_attribute'], $language_id),
                "id_product_attribute" => $product['id_product_attribute']
            ];
        }

        return $items;
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
