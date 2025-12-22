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
                // Vérifier si c'est un composite
                $isComposite = $session->id_type === self::COMPOSITE_PLEINES_ID 
                    || in_array($session->id_category, [self::COMPOSITE_ID, self::COMPOSITE_PLEINES_ID], true);
                
                // Longueur par défaut : 1.8 pour composite, 2.09 pour aluminium
                $session->length = $isComposite ? 1.8 : 2.09;
                
                if (!$isComposite) {
                    $session->installation = "scellement-sur-muret";
                    $isPleines = in_array($session->id_type, [self::LAMES_PLEINES_ID, self::COMPOSITE_PLEINES_ID], true);
                    if ($isPleines) {
                        $session->height     = "0m515";
                        $session->espacement = 0;
                    } else {
                        $session->height     = "0m403";
                        $session->espacement = 1;
                    }
                    $session->color = "gris-7016";
                }
            }
            $height                = (float)str_replace("m", ".", $session->height);
            // Pour le composite (alu) avec "scellement", on réutilise les quantitatifs "scellement-sur-muret"
            $installationType = $session->installation;
            $installationKey = ($session->isAluminium() && $installationType === 'scellement')
                ? 'scellement-sur-muret'
                : $installationType;
            $length = $session->length;
            $isPleines = in_array($session->id_type, [self::LAMES_PLEINES_ID, self::COMPOSITE_PLEINES_ID], true);

            // Calcul spécifique pour le composite
            if ($session->id_type === self::COMPOSITE_PLEINES_ID) {
                // Composite : utilise le tableau COMPOSITE_PANEL_QUANTITIES ou COMPOSITE_PANEL_QUANTITIES_WITH_DECO selon si un panneau déco est sélectionné
                $lengthMm = $length * 1000; // Conversion m -> mm
                $hasPanneauDeco = $session->panneau_deco();
                $quantitiesTable = $hasPanneauDeco ? self::COMPOSITE_PANEL_QUANTITIES_WITH_DECO : self::COMPOSITE_PANEL_QUANTITIES;
                
                // Trouver l'index en utilisant le tableau de plages
                // Index 0 = 0-3599mm, Index 1 = 3600-5399mm, Index 2 = 5400-7199mm, etc.
                // Chaque plage fait 1800mm (sauf la première qui va de 0 à 3600)
                $index = 0;
                if ($lengthMm < 3600) {
                    $index = 0; // 0-3599mm
                } else {
                    // Pour les autres plages : 3600-5399, 5400-7199, etc.
                    // range[1] = 3600 → plage 3600-5399 (index 1)
                    // range[2] = 5400 → plage 5400-7199 (index 2)
                    // etc.
                    $found = false;
                    for ($i = 1; $i < count(self::COMPOSITE_LENGTH_RANGES); $i++) {
                        $rangeStart = self::COMPOSITE_LENGTH_RANGES[$i];
                        $rangeEnd = $rangeStart + 1800;
                        if ($lengthMm >= $rangeStart && $lengthMm < $rangeEnd) {
                            $index = $i;
                            $found = true;
                            break;
                        }
                    }
                    // Si la longueur dépasse toutes les plages, utiliser le dernier index
                    if (!$found) {
                        $lastRangeStart = self::COMPOSITE_LENGTH_RANGES[count(self::COMPOSITE_LENGTH_RANGES) - 1];
                        if ($lengthMm >= $lastRangeStart + 1800) {
                            $index = count(self::COMPOSITE_LENGTH_RANGES) - 1;
                        }
                    }
                }
                
                $index = max(0, min($index, count($quantitiesTable[$session->height]) - 1));
                $quantity_factor = $quantitiesTable[$session->height][$index];
            } else {
                // Mapping des hauteurs alu vers les quantitatifs
                $legacyHeight = (string)$height;
                if ($isPleines) {
                    $quantity_factor = array_flip(self::STEPS_HIGHT_ALU_LAMES_PLEINES)[$session->height];
                } else {
                    $quantity_factor = array_flip(self::STEPS_HIGHT_ALU)[$session->height];
                }

                $quantities = ($isPleines)
                    ? self::ALU_QUANTITIES_PLEINES[$installationKey][$legacyHeight]
                    : self::ALU_QUANTITIES_AJOURES[$installationKey][$legacyHeight];

                $lengthKeys = array_keys($quantities);
                $position = array_search($length, $lengthKeys);
                $coefficient = 1;
                if ($position !== false) {
                    $coefficient = $position + 1;
                }

                $quantity_factor *= $coefficient;
            }
            // Pour le composite, la soustraction du panneau déco est gérée dans COMPOSITE_PANEL_QUANTITIES_WITH_DECO
            if($session->panneau_deco() && $session->id_type !== self::COMPOSITE_PLEINES_ID) {
                $quantity_factor -= 1 * $coefficient;
            }
            $quantity = 1;
        }else {
            $quantity = Helper::getNumberOfItem(floatval($session->length), $session);
        }

        $quantity = $quantity * $quantity_factor;

        // Pour le composite, définir l'id_product_attribute selon la couleur
        if ($session->id_type === self::COMPOSITE_PLEINES_ID) {
            if (!empty($session->color)) {
                if ($session->color === 'ton-bois') {
                    $session->id_product_attribute = 11840408;
                    $id_product_attribute = 11840408;
                } else {
                    // Par défaut : gris-7016
                    $session->id_product_attribute = 11840407;
                    $id_product_attribute = 11840407;
                }
            }
        }

        // Get name of grillage with its id_product_attribute or with no specific combination
        if ($id_product == 1324) {
            $session->id_product_attribute = 11780062;
            $id_product_attribute = 11780062;
        }else if ($id_product == 1323) {
            $session->id_product_attribute = 11780060;
            $id_product_attribute = 11780060;
        }
        // Utiliser id_product_attribute de la session si elle n'a pas été définie précédemment
        if ($id_product_attribute === null && !empty($session->id_product_attribute)) {
            $id_product_attribute = $session->id_product_attribute;
        }
        
        $name = \Product::getProductName($id_product, $id_product_attribute ?? $session->id_product_attribute, $language_id);

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
        $price = Helper::getPrice($id_product, $id_product_attribute ?? $session->id_product_attribute, $quantity);

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

    /**
     * Map les nouvelles hauteurs composite pleines vers les hauteurs legacy alu
     * pour réutiliser les tableaux de quantitatifs existants.
     */
    private static function mapCompositePleinesHeight(float $height)
    {
        // Regroupement simple par proximité
        if ($height <= 0.87) {
            return '0.515';
        }
        if ($height <= 1.21) {
            return '0.96';
        }
        return '1.41';
    }
}
