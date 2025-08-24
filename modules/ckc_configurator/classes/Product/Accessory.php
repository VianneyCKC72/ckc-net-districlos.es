<?php

namespace CkcConfigurator\Product;

use CkcConfigurator\ProductFactory;
use CkcConfigurator\Configurator\Helper;

class Accessory extends ProductFactory
{

    static private $session;

    // Get all accessories of grillage with each accessories combination (if needed) and quantity
    static public function get($session, $language_id, $soubassement = false)
    {
        if (!$session) {
            return [];
        }

        $accessories   = [];
        self::$session = $session;

        // On récupère tous les accessoires de la gamme de grillage
        $accessories_selected = self::getSelected();

        // FIX Remet l'id_category mère en session
        if (self::$session->isAluminium()) {
            $session->id_category = self::ALUMINIUM_ID;
        }
        if (self::$session->isRigide()) {
            $session->id_category = self::RIGIDES_ID;
        }

        if (self::$session->isSouple()) {
            $session->id_category = self::SOUPLES_ID;
        }

        foreach ($accessories_selected as $key => $accessory_index) {
            if (($accessory_index == 'clips' || $accessory_index == 'pince_clips') && self::$session->clips == 0) {
                continue;
            }
            if ($accessory_index == 'poteaux') {
                self::$session->change('deconseil', 0);
            }

            if (self::$session->isAluminium()) {
                self::$session->change('deconseil', 0);
            }

            $id_accessory = self::ACCESSORIES_ID[$session->id_category][$accessory_index];

            // On récupère l'accessoire grâce à l'id de la catégorie
            if (self::$session->isSouple()) {
                $id_product = self::matchProduct($id_accessory, $language_id, $soubassement);
            }
            else {
                if ($accessory_index == 'poteaux' && self::$session->isRigide()) {
                    $id_product = self::$session->poteaux;
                }
                else {
                    $id_product = Helper::getIdMainProduct($id_accessory, $language_id);
                }
            }

            $quantity = self::getNbOfTimeAccessory($accessory_index);

            $id_product_attribute = self::getIdProductAttribute($id_product, $language_id, $soubassement);


            // Get name of accessory with its id_product_attribute or with no specific combination
            $name = \Product::getProductName($id_product, $id_product_attribute, $language_id);

            if (!$name) {
                $name = (new \Product($id_product, $language_id))->name;
            }

            // Get unit_price and total_price of accessory
            $price = Helper::getPrice($id_product, $id_product_attribute, $quantity);

            $accessories_key = $id_product_attribute;
            if (!$accessories_key) {
                $accessories_key = $key;
            }
            if ($quantity > 0) {
                $accessories[$accessories_key] = [
                    "id"           => $id_product,
                    "id_attribute" => $id_product_attribute,
                    "quantity"     => $quantity,
                    "name"         => $name,
                    "price"        => $price['total_price'],
                    "unit_price"   => $price['unit_price']
                ];
            }
        }

        return $accessories;
    }

    static private function matchProduct($id_category, $language_id, $soubassement = false)
    {
        $category                  = new \Category($id_category, $language_id);
        $products                  = $category->getProducts($language_id, 1, 1000000, null, null, false, true, false, 1, false);
        $selected_height           = floatval(str_replace('m', '.', self::$session->height));
        $match_height              = self::matchHeight($selected_height, $soubassement);
        $max_height                = null;
        $higher_product            = null;
        $arrProductAttributeHeight = [];
        $heightEstUnAttribute      = false;

        if (!empty($products[0])) {
            $current_height = Helper::getCurrentHeight($products[0]['name']);
            if (empty($current_height)) {
                $heightEstUnAttribute = true;
                foreach ($products as $product) {
                    // Get all id_product_attribute
                    $attributes = Helper::getAttributesResume($language_id, $product["id_product"]);
                    foreach ($attributes as $key => $attribute) {
                        $name                             = ($attribute['attribute_designation']);
                        $arrProductAttributeHeight[$name] = [
                            'id_product' => $product["id_product"],
                            'id_product_attribute' => $attribute['id_product_attribute'],
                            'height'     => Helper::getCurrentHeight($name)
                        ];
                    }
                }
            }
        }

        if ($heightEstUnAttribute === FALSE) {
            foreach ($products as $product) {
                $current_height = Helper::getCurrentHeight($product['name']);

                if ($max_height < $current_height) {
                    $max_height     = $current_height;
                    $higher_product = $product["id_product"];
                }

                if ($current_height === $match_height) {
                    return $product["id_product"];
                }
            }
        }
        else {
            foreach ($arrProductAttributeHeight as $name => $attribute) {
                $current_height = $attribute['height'];

                if ($max_height < $current_height) {
                    $max_height     = $current_height;
                    $higher_product = $attribute["id_product"];
                }

                if ($current_height === $match_height) {
                    return $attribute["id_product"];
                }
            }
        }
        return $higher_product;
    }

    static public function getIdAttribute($id_product_attribute, $id_product)
    {
        return \Db::getInstance()->getValue('
        SELECT ps_product_attribute_combination.id_attribute 
        FROM ps_product_attribute_combination
        INNER JOIN ps_product_attribute
        ON ps_product_attribute_combination.id_product_attribute = ps_product_attribute.id_product_attribute
        WHERE ps_product_attribute.id_product = ' . (int)$id_product . '
        AND ps_product_attribute_combination.id_product_attribute = ' . (int)$id_product_attribute);
    }

    // Get accessories depending of type of grillage and type of pose
    static public function getSelected()
    {

        $accessories = [];
        $platines    = ["chevilles", "platines"];
        $espacement  = ["entretoise"];

        if (self::$session->isRigide()) {
            $accessories = ["poteaux", "bouchons", "clips", "pince_clips"];

            if (self::$session->poteaux == 1285 || self::$session->poteaux == 1223) {
                $accessories[] = "cales";
            }
        }
        elseif (self::$session->isSouple()) {
            $accessories = ["poteaux", "jambes", "boulons", "tension", "tendeurs", "agrafes", "agrafeuses"];
            if (self::$session->id_type == self::SIMPLES_ID) {
                $accessories = array_merge($accessories, ['barres']);
            }
        }
        elseif (self::$session->id_category == self::BARREAUDAGES_ID) {
            $accessories = ["poteaux", "colliers"];
        }
        elseif (self::$session->isAluminium()) {
            $accessories = ["poteaux"];
            $platines    = ["platines"]; // Les vis sont fournis
//            if (self::$session->id_type == self::LAMES_AJOUREES_ID) {
//                $accessories = array_merge($accessories, $espacement);
//            }
            if (self::$session->id_type == self::LAMES_PLEINES_ID) {
                $accessories[] = 'profil-finition';
            }else if(self::$session->height == '1m129'){
                $accessories = array_merge($accessories, $platines);
            }
            if (self::$session->depart_mur > 0 || self::$session->nombre_angle) {
                $accessories[] = 'demi-poteaux';
            }

        }

        // Platines
        if (self::$session->installation == 'scellement-sur-platine') {
            if (self::$session->isRigide()) {
                unset($platines[1]);
            }
            $accessories = array_merge($accessories, $platines);
        }

        return array_unique($accessories);
    }

    // Get nb of iteration for each accessories needed
    static public function getNbOfTimeAccessory($accessory)
    {
        $length = self::$session->length;
        $length = (float) str_replace(',', '.', $length);

        $panneau_times = Helper::getNumberOfItem($length, self::$session);

        // Si hauteur inférieure ou égale à 1.25, coefficient = 3
        // Si hauteur est 1.50, coefficient = 4
        // Si hauteur supérieure ou égale à 1.75, coefficient = 5
        $height        = floatval(str_replace('m', '.', self::$session->height));
        $coefficient   = ($height <= 1.25) ? 3 : (($height >= 1.75) ? 5 : 4);
        $depart_du_mur = (int)self::$session->depart_mur;
        $nombre_angle  = (int)self::$session->nombre_angle;
        if ($panneau_times > 0 && self::$session->isRigide()) {
            switch ($accessory) {
                case 'jambes':
                case 'boulons':
                    return intval($length / 20);
                case 'cales':
                    return $panneau_times / 2;
                case 'barres':
                case 'tension':
                    return $panneau_times;
                case 'entretoise' :
                    $quantity_factor = ($height / 20) * 100;
                    $quantity        = $panneau_times * $quantity_factor;
                    return self::calculerLotsEntretoises($quantity);
                case 'profil-finition':
                    return floor($length / 2);
                case "demi-poteaux" :
                    return (int)$depart_du_mur + $nombre_angle;
                case 'poteaux':
                case 'bouchons':
                case 'platines':
                    if (self::$session->isAluminium()) {

                        $nb_poteaux = ceil(($length / 1.5) + 1);
                        if($accessory == 'poteaux' || $accessory == 'bouchons') {
                            $nb_poteaux = ($nb_poteaux - $depart_du_mur - $nombre_angle);

                            if ($nb_poteaux < 0) {
                                $nb_poteaux = 0;
                            }
                        }
                        return $nb_poteaux;
                    }

                    return $panneau_times + 1;
                case 'chevilles':
                    return $panneau_times + 1;
                case 'bouchons-demi-poteaux':
                    if (self::$session->isAluminium()) {
                        return (int)$depart_du_mur + $nombre_angle;
                    }
                case 'tendeurs':
                    return $panneau_times * 5;
                case 'colliers':
                    return $panneau_times * 2;
                case 'clips':
                    if ($height <= 1.53) {
                        return $panneau_times * 6;
                    }
                    else {
                        return $panneau_times * 8;
                    }
                case 'pince_clips':
                case 'agrafes':
                default:
                    return 1;
            }
        }
        elseif ($panneau_times > 0 && self::$session->isAluminium() && self::$session->id_type == self::LAMES_PLEINES_ID) {
            switch ($accessory) {
                case 'profil-finition':
                    return (int) self::ALU_QUANTITIES_PLEINES[self::$session->installation][(string)$height][(string)$length]['profil-finition'];
                case 'poteaux':
                    return (int) self::ALU_QUANTITIES_PLEINES[self::$session->installation][(string)$height][(string)$length]['poteaux'] - (int) $depart_du_mur;
                case 'platines':
                    return isset(self::ALU_QUANTITIES_PLEINES[self::$session->installation][(string)$height][(string)$length]['platines']) ? (int) self::ALU_QUANTITIES_PLEINES[self::$session->installation][(string)$height][(string)$length]['platines'] : 0;
                case 'bouchons-demi-poteaux':
                case 'demi-poteaux' :
                    return (int) $depart_du_mur;
                default:
                    return 1;
            }
        }
        elseif ($panneau_times > 0 && self::$session->isAluminium() && self::$session->id_type == self::LAMES_AJOUREES_ID) {
            switch ($accessory) {
                case 'profil-finition':
                    return (int) self::ALU_QUANTITIES_AJOURES[self::$session->installation][(string)$height][(string)$length]['profil-finition'];
                case 'poteaux':
                    return (int) self::ALU_QUANTITIES_AJOURES[self::$session->installation][(string)$height][(string)$length]['poteaux'];
                case 'platines':
                    return (int) self::ALU_QUANTITIES_AJOURES[self::$session->installation][(string)$height][(string)$length]['platines'];
                case 'bouchons-demi-poteaux':
                case 'demi-poteaux' :
                    return (int) $depart_du_mur;
                default:
                    return 1;
            }
        }
        elseif ($panneau_times > 0 && self::$session->isSouple()) {
            switch ($accessory) {
                case 'jambes': // jambes de force
                    return ceil(($length / 20) * 2) + 1;
                case 'boulons': // boulons de jambes de force
                    return ceil(((($length / 20) * 2) + 1) / 2) + 1;
                case 'colliers':
                case 'tension': // fil de tension
                    return ceil(($length * $coefficient) / 100);
                case 'poteaux': // piquets
                    return ceil($length / 2) + 1;
                case 'platines':
                case 'chevilles':
                case 'bouchons': // bouchons
                    return ceil(($length / 2.5) + 1);
                case 'cales':
                case 'barres':
                    return 2;
                case 'tendeurs': // tendeurs
                    return (ceil(((((($length / 20) * 2) + 1) / 2))) + 1) * $coefficient;
                case 'agrafes': // agrafes
                    return ceil($length / 100);
                case 'agrafeuses':
                default:
                    return 1;
            }
        }

        return 0;
    }

    static public function calculerLotsEntretoises($quantity, $entretoises_par_lot = 100)
    {
        if ($quantity <= 0 || $entretoises_par_lot <= 0) {
            return 0; // Retourne 0 si les valeurs ne sont pas valides
        }

        $nb_entretoises = $quantity * 2;

        $lots_necessaires = ceil($nb_entretoises / $entretoises_par_lot);
        return $lots_necessaires;
    }

    // Get needed height and colors for this height
    static public function getIdProductAttribute($id_product, $language_id, $soubassement = false)
    {
        $colors = [];
        $ids    = [];

        // Cas particulier de l'entretoise
        if ($id_product == 1258) {
            switch (self::$session->espacement) {
                case 1:
                    return 11774974;
                case 2:
                    return 11774975;
                case 3:
                    return 11774976;
            }
        }

        $heightString    = self::$session->height;
        $heightString    = str_replace('m', '.', $heightString);
        $heightFloat     = floatval($heightString);
        $height_selected = intval($heightFloat * 100);

        if ($id_product == 1331 && self::$session->isAluminium()) {
            if ((($heightFloat == 0.515 || $heightFloat == 0.96 || $heightFloat == 0.403 || $heightFloat == 0.766 || $heightFloat == 1.129) && self::$session->installation == 'scellement-sur-muret') || (($heightFloat == 1.41 || $heightFloat == 1.129) && self::$session->installation == 'scellement-sur-platine')) {
                return 11780051;
            }else if (self::$session->installation == 'scellement-sur-platine') {
                return 11780053;
            }else if ( $heightFloat == 1.41 && self::$session->installation == 'scellement-sur-muret') {
                return 11780052;
            }
        }

        if ($id_product == 1329 && self::$session->isAluminium()) {
            return 11780032;
        }

        if ($id_product == 1333 && self::$session->isAluminium()) {
            return 11780036;
        }

        if ($id_product == 1328 && self::$session->isAluminium()) {
            return 11780057;
        }
        if ($id_product == 1135 && self::$session->isAluminium()) {
            return 11768852;
        }

        // Cas particulier pour poteau alu.
        if ($id_product == 1136 && self::$session->isAluminium()) {
            if ($height_selected <= 80) {
                return 11768853;
            }
            else
                return 11768854;
        }

        if (self::$session->isAluminium()) {
            if (!isset(self::$session->id_product_attribut) || !self::$session->id_product_attribut) {
                return null;
            }
        }

        $sql = new \DbQuery();
        $sql->select('name');
        $sql->from('product_attribute_combination', 'pac');
        $sql->leftJoin('attribute_lang', 'al', 'pac.id_attribute = al.id_attribute');
        $sql->where('id_product_attribute = ' . self::$session->id_product_attribute . ' AND id_lang = ' . $language_id);
        $names = \Db::getInstance()->executeS($sql);

        foreach ($names as $name) {
            if (strpos($name['name'], 'Ht ') !== false || strpos($name['name'], '0m') !== false || strpos($name['name'], '1m') !== false || strpos($name['name'], '2m') !== false) {
                $height = floatval(str_replace(['Ht ', 'm'], ['', '.'], $name['name']));
            }
        }

        $needed_height = self::matchHeight($height, $soubassement, null, ($id_product == 158), ($id_product == 1162)); // Barre de tension (ID 158)
        $combinations  = static::getAttributesInformationsByProduct($id_product);

        // Height
        foreach ($combinations as $combination) {
            $current_height = floatval(str_replace('m', '.', $combination['attribute']));
            $group          = strtolower(str_replace([' ', ':'], ['-', ''], $combination['group']));

            if (($group == 'hauteur' || $group == 'hauteur-du-poteau') && $current_height == $needed_height && isset($combination['id_product_attribute'])) {
                $ids[] = $combination['id_product_attribute'];
            }
        }

        // Platines
        if ($id_product == 150) {
            foreach ($combinations as $combination) {
                $ids[] = $combination['id_product_attribute'];
            }
        }

        // Colors
        foreach ($combinations as $combination) {
            $group = strtolower(str_replace([' ', ':'], ['-', ''], $combination['group']));

            if (isset($combination['id_product_attribute']) && $group == 'couleur' && ((count($ids) > 0 && in_array($combination['id_product_attribute'], $ids)) || count($ids) == 0)) {
                $colors[$combination['id_product_attribute']] = $combination['attribute'];
            }
        }

        $id_product_attribute = null;
        foreach ($colors as $id_product_attribute => $color) {
            $color = explode('-', strtolower(str_replace(' ', '-', $color)));
            $count = count($color);
            $color = end($color);

            if ($count > 1) {
                foreach ($names as $name) {
                    $recherche_couleur = strpos($name['name'], $color);
                    if ($recherche_couleur !== false) {
                        return $id_product_attribute;
                    }
                }
            }
            else {
                foreach ($names as $name) {
                    if (strpos(strtolower($name['name']), $color) !== false) {
                        return $id_product_attribute;
                    }
                }
            }
        }

        // Si le produit possède des déclinaisons, alors on sélectionne la première
        if (count($combinations) > 0) {
            $id_product_attribute = $combinations[0]['id_product_attribute'];
        }

        return $id_product_attribute;
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

    // Get height depending on grillage's height and type of pose
    static public function matchHeight($height, $soubassement_id = false, $session = null, $barre = false, $jambes = false)
    {
        if ($session) {
            self::$session = $session;
        }

        if ($soubassement_id) {
            if ($soubassement_id == 416) {
                $soubassement = 0.25;
            }
            else {
                $soubassement = 0.5;
            }
        }
        else {
            $soubassement = 0;
        }

        if (self::$session->id_category == self::RIGIDES_ID) {
            if (self::$session->installation == 'scellement-sur-platine') {
                switch ($height) {
                    case 0.63:
                        return 0.67;
                    case 0.83:
                        return 0.87;
                    case 1.03:
                        return 1.07;
                    case 1.23:
                        return 1.27;
                    case 1.43:
                        return 1.57;
                    case 1.53:
                        self::$session->change('deconseil', 1);
                        return 1.57;
                    case 1.73:
                        self::$session->change('deconseil', 1);
                        return 1.77;
                    case 1.93:
                        self::$session->change('deconseil', 1);
                        return 1.97;
                }
            }
            else {
                switch ($height) {
                    case 0.63:
                        if ($soubassement == 0.25) {
                            return 1.27;
                        }
                        elseif ($soubassement == 0.50) {
                            return 1.57;
                        }
                        return 0.87;
                    case 0.83:
                        if ($soubassement == 0.25) {
                            return 1.57;
                        }
                        elseif ($soubassement == 0.50) {
                            return 1.77;
                        }
                        return 1.07;
                    case 1.03:
                        if ($soubassement == 0.25) {
                            return 1.77;
                        }
                        elseif ($soubassement == 0.50) {
                            return 1.97;
                        }
                        return 1.27;
                    case 1.23:
                        if ($soubassement == 0.25) {
                            return 1.97;
                        }
                        elseif ($soubassement == 0.50) {
                            return 2.17;
                        }
                        return 1.57;
                    case 1.43:
                        if ($soubassement == 0.25) {
                            return 2.17;
                        }
                        elseif ($soubassement == 0.50) {
                            return 2.47;
                        }
                        return 1.77;
                    case 1.53:
                        if ($soubassement == 0.25) {
                            return 2.17;
                        }
                        elseif ($soubassement == 0.50) {
                            return 2.87;
                        }
                        return 1.97;
                    case 1.73:
                        if ($soubassement == 0.25) {
                            return 2.47;
                        }
                        elseif ($soubassement == 0.50) {
                            return 2.87;
                        }
                        return 2.17;
                    case 1.93:
                        if ($soubassement == 0.25 || $soubassement == 0.50) {
                            return 2.87;
                        }
                        return 2.47;
                }
            }
        }
        elseif (self::$session->id_category == self::SOUPLES_ID) {
            if (self::$session->installation == 'scellement-sur-platine') {
                switch ($height) {
                    case 1.00:
                        return 1.25;
                    case 1.20:
                        return 1.25;
                    case 1.50:
                        return 1.50;
                    case 1.75:
                        self::$session->change('deconseil', 1);
                        return 1.75;
                    case 2.00:
                        self::$session->change('deconseil', 1);
                        return 2.00;
                }
            }
            elseif (self::$session->installation == 'scellement-sur-muret') {
                switch ($height) {
                    case 1.00:
                        return 1.25;
                    case 1.20:
                        return 1.50;
                    case 1.50:
                        return 1.75;
                    case 1.75:
                        return 2.00;
                    case 2.00:
                        return 2.25;
                }
            }
            elseif ($barre) { // Barre de tension
                return ($height == 1.20 || $height == 1.75) ? $height + 0.05 : $height;
            }else if($jambes) {
                switch ($height) {
                    case 1.00:
                        return 1.50;
                    case 1.20:
                        return 1.75;
                    case 1.50:
                        return 2.00;
                    case 1.75:
                    case 1.80:
                    case 2.00:
                        return 2.25;
                }
            } else {
                switch ($height) {
                    case 1.00:
                        return 1.50;
                    case 1.20:
                        return 1.75;
                    case 1.50:
                        return 2.00;
                    case 1.75:
                    case 1.80:
                        return 2.25;
                    case 2.00:
                        return 2.50;
                }
            }
        }
        elseif (self::$session->id_category == self::BARREAUDAGES_ID) {
            if (self::$session->installation == 'scellement-sur-platine') {
                switch ($height) {
                    case 1.00:
                        return 1.10;
                    case 1.20:
                        return 1.30;
                    case 1.50:
                        return 1.50;
                    case 1.80:
                        return 1.80;
                    case 2.00:
                        return 2.00;
                }
            }
            else {
                switch ($height) {
                    case 1.00:
                        return 1.30;
                    case 1.20:
                        return 1.50;
                    case 1.50:
                        return 1.80;
                    case 1.80:
                        return 2.20;
                    case 2.00:
                        return 2.50;
                }
            }
        }
        elseif (self::$session->id_category == self::ALUMINIUM_ID) {
            if (self::$session->installation == 'scellement-sur-platine') {
                switch ($height) {
                    case 1.00:
                        return 1.25;
                    case 1.20:
                        return 1.25;
                    case 1.50:
                        return 1.50;
                    case 1.75:
                        self::$session->change('deconseil', 1);
                        return 1.75;
                    case 2.00:
                        self::$session->change('deconseil', 1);
                        return 2.00;
                    default:
                        return $height;
                }
            }
            elseif (self::$session->installation == 'scellement-sur-muret') {
                switch ($height) {
                    case 1.00:
                        return 1.25;
                    case 1.20:
                        return 1.50;
                    case 1.50:
                        return 1.75;
                    case 1.75:
                        return 2.00;
                    case 2.00:
                        return 2.25;
                    default:
                        return $height;
                }
            }
            elseif ($barre) { // Barre de tension
                return ($height == 1.20 || $height == 1.75) ? $height + 0.05 : $height;
            }
            else {
                switch ($height) {
                    case 1.00:
                        return 1.50;
                    case 1.20:
                        return 1.75;
                    case 1.50:
                        return 2.00;
                    case 1.75:
                    case 1.80:
                        return 2.25;
                    case 2.00:
                        return 2.50;
                    default:
                        return $height;
                }
            }
        }
    }
}
