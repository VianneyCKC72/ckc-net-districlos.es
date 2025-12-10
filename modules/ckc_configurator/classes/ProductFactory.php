<?php
namespace CkcConfigurator;

class ProductFactory implements Config {

    /**
     * Récupère toutes les informations d'attributs d'un produit
     * @param int $id_product L'ID du produit
     * @return array Tableau des combinaisons d'attributs
     */
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
     * Génère l'URL de l'image d'un produit
     * @param array|int $image Tableau avec 'id_image' ou ID d'image
     * @return string URL de l'image ou chaîne vide
     */
    static public function getImageUrl($image)
    {
        if (isset($image['id_image'])) {
            $image = new \Image($image['id_image']);
            return _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
        }

        return '';
    }

    /**
     * Récupère les caractéristiques du produit pour affichage dans la tooltip
     * @param int $id_product L'ID du produit
     * @param int $id_product_attribute L'ID de la déclinaison
     * @param int $language_id L'ID de la langue
     * @return array Tableau avec les caractéristiques groupées
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

    /**
     * Extrait le nom court d'un nom de produit (partie avant " : ")
     * @param string $product_name Le nom complet du produit
     * @return string Le nom court
     */
    static public function extractShortName($product_name)
    {
        $colon_pos = strpos($product_name, ' : ');
        if ($colon_pos !== false) {
            return substr($product_name, 0, $colon_pos);
        }
        return $product_name;
    }

    /**
     * Extrait le nom sans titre d'un nom de produit (partie après " : ")
     * @param string $product_name Le nom complet du produit
     * @return string Le nom sans titre
     */
    static public function extractNameWithoutTitle($product_name)
    {
        $colon_pos = strpos($product_name, ' : ');
        if ($colon_pos !== false) {
            return substr($product_name, $colon_pos + 3);
        }
        return $product_name;
    }
}