<?php

namespace CkcConfigurator\Product;

use CkcConfigurator\Configurator\Session;
use CkcConfigurator\ProductFactory;
use CkcConfigurator\Configurator\Helper;

class Grillage extends ProductFactory
{

    // Get price, quantity & combination of the grillage selected
    static public function get(Session $session, $language_id, $categorie=null)
    {
        $id_product_attribute = null;
        // On récupère le grillage grâce à l'id de la catégorie
        // todo a voir pour alu
        if ($session->isSouple()) {
            [$id_product, $id_product_attribute] = self::matchProduct($session->id_type, $language_id, $session->height, $session->id_product_attribute);
        }
        else {
            $id_product = Helper::getIdMainProduct($session->id_type, $language_id);

        }
        $quantity_factor = 1;

        // On prend la première catégorie qui équivaut à la première gamme de grillage
        if($session->isAluminium()) {
            if(empty($session->length)) {
                $session->length       = 2.09;
                $session->installation = "scellement-sur-muret";
                if ($session->id_type == self::LAMES_PLEINES_ID) {
                    $session->height       = "0m403";
                }else{
                    $session->height       = "0m515";
                }
                $session->color        = "gris-7016";
                $session->espacement   = 1;
            }
            $height                = (float)str_replace("m", ".", $session->height);
            $installationType = $session->installation;
            $length = $session->length;
            if ($session->id_type == self::LAMES_PLEINES_ID) {
                $quantity_factor = array_flip(self::STEPS_HIGHT_ALU_LAMES_PLEINES)[$session->height];
            }else{
                $quantity_factor = array_flip(self::STEPS_HIGHT_ALU)[$session->height];
            }

            $quantities = ($session->id_type == self::LAMES_PLEINES_ID)
                ? self::ALU_QUANTITIES_PLEINES[$installationType][(string)$height]
                : self::ALU_QUANTITIES_AJOURES[$installationType][(string)$height];

            $lengthKeys = array_keys($quantities);
            $position = array_search($length, $lengthKeys);
            $coefficient = 1;
            if ($position !== false) {
                $coefficient = $position + 1;
            }

            $quantity_factor *= $coefficient;
            if($session->panneau_deco()) {
                $quantity_factor -= 1 * $coefficient;
            }
            $quantity = 1;
        }else {
            $quantity = Helper::getNumberOfItem(floatval($session->length), $session);
        }

        $quantity = $quantity * $quantity_factor;

        // Get name of grillage with its id_product_attribute or with no specific combination
        if ($id_product == 1324) {
            $session->id_product_attribute = 11780062;
        }else if ($id_product == 1323) {
            $session->id_product_attribute = 11780060;
        }
        $name = \Product::getProductName($id_product, $session->id_product_attribute, $language_id);

        if (!$name) {
            $name = (new \Product($id_product))->name[$language_id];
        }

        if ($id_product_attribute != null) {
            if (!empty($session->id_product_attribute)) {
                if ($session->id_product_attribute != $id_product_attribute) {
                    $session->change('id_product_attribute', $id_product_attribute);
                }
            }
            $session->id_product_attribute = $id_product_attribute;
        }

        // Get unit_price and total_price of accessory
        $price = Helper::getPrice($id_product, $id_product_attribute, $quantity);

        $data = [
            "id"           => $id_product,
            "id_attribute" => $session->id_product_attribute,
            "quantity"     => $quantity,
            "name"         => $name,
            'price'        => $price['total_price'],
            'unit_price'   => $price['unit_price']
        ];

        return $data;
    }


    static private function matchProduct($id_category, $language_id, $height, $session_id_product_attribute = null)
    {
        $category        = new \Category($id_category, $language_id);
        $products        = $category->getProducts($language_id, 1, 1000000, null, null, false, true, false, 1, false);
        $selected_height = floatval(str_replace('m', '.', $height));
//        echo "selected_height = > " . $selected_height;

        foreach ($products as $product) {
            $AttributesInformations = static::getAttributesInformationsByProduct($product['id_product']);
            foreach ($AttributesInformations as $key => $attribute) {
                if (str_replace('m', '.', $attribute['attribute']) == $selected_height) {
                    if (empty($session_id_product_attribute))
                        return [$product["id_product"], $attribute['id_product_attribute']];
                }
            }
            if (Helper::getCurrentHeight($product['name']) === $selected_height) {
                return [$product["id_product"], null];
            }
        }

        return [$products[0]["id_product"], null];
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
