<?php

namespace CkcConfigurator\Configurator;

use CkcConfigurator\Configurator\Session;
use CkcConfigurator\Product\Accessory;
use CkcConfigurator\Config;

class Preview implements Config
{
    static public function preview($language_id, $session)
    {

        $root_path        = '/var/www/districlos/modules/ckc_configurator/';
        $panneau          = "panneaux/";
        $poteau           = "poteaux/";
        $height_formatted = '';
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            if (strpos($_SERVER['DOCUMENT_ROOT'], 'districlos.local') !== false) {
                $root_path = $_SERVER['DOCUMENT_ROOT'] . "/modules/ckc_configurator/";
            }
            if (strpos($_SERVER['DOCUMENT_ROOT'], 'dev.') !== false) {
                $root_path        = '/var/www/dev.cloture-discount.fr/modules/ckc_configurator/';
            }
        }

        if ($session->isSouple()) {
            $panneau = "rouleaux/";
            $poteau  = "piquet/";
        }

        $return = [
            "base"        => "bases/",
            "panneau"     => $panneau,
            "poteau"      => $poteau,
            "jambe"       => "jdf/",
            "occultation" => "",
            "portillon"   => ""
        ];

        if ($session->id_product_attribute) {
            $sql          = 'SELECT name FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (al.id_attribute = pac.id_attribute) WHERE id_product_attribute = ' . $session->id_product_attribute . ' AND id_lang = ' . $language_id;
            $combinations = \Db::getInstance()->ExecuteS($sql);

            foreach ($combinations as $name) {
                if (strpos($name['name'], 'Ht ') !== false || strpos($name['name'], '0m') !== false || strpos($name['name'], '1m') !== false || strpos($name['name'], '2m') !== false) {
                    $height_formatted = floatval(str_replace('m', '.', str_replace('Ht ', '', $name['name'])));
                    $height           = floatval(str_replace('m', '', str_replace('Ht ', '', $name['name'])));
                }
            }
        }

        $type = self::getTypeName($session->id_type);

        if ($session->isSouple()) {
            $type = ($type === 'soude') ? 'sd' : 'st';
        }
        if (empty($height)) {
            $height_formatted = floatval(str_replace('m', '.', str_replace('Ht ', '', $session->height)));
            $height           = floatval(str_replace('m', '', str_replace('Ht ', '', $session->height)));
        }

        $sousbassement_id   = $session->has('soubassement') ? $session->soubassement()['id_product'] : false;
        $sousbassement      = $session->has('soubassement') ? $session->soubassement() : false;
        $hauteur_accessoire = Accessory::matchHeight($height_formatted, $sousbassement_id, $session);

        $poteau      = str_replace('.', '', number_format($hauteur_accessoire, 2));


        $portillon   = self::getPortillon($session->portillon(), $language_id, $session->installation, $session->color);
        $occultation = self::getOccultation($session->occultation(), $language_id, $sousbassement_id);

        $return["base"] .= $session->installation . self::getSoubassement($sousbassement) . '.png';

        // Couleurs des panneaux
        $couleurs_panneaux = ['vert', 'gris', 'noir', 'blanc'];
        $image_panneaux    = '';

        $espacement = '';
        if ($type == 'ajourees') {
            $espacement = "-" . $session->espacement;
        }
        elseif ($type == 'pleine') {
            $espacement = '-0';
        }


        foreach ($couleurs_panneaux as $couleur_panneaux) {
            $preview_file = $root_path . 'views/images/' . $return["panneau"] . $type . '-' . $height . $espacement . '-' . $couleur_panneaux . '.png';

            if (strpos($session->color, $couleur_panneaux) !== false) {
                $image_panneaux .= $type . '-' . $height . $espacement . '-' . $couleur_panneaux . '.png';
            }
        }

        $return["panneau"] .= $image_panneaux ?? $type . '-' . $height . '.png';

        // Couleurs des poteaux
        $couleurs_poteaux = ['vert', 'gris', 'noir', 'blanc'];
        $image_poteau     = '';
        if ($session->id_category == Config::RIGIDES_ID) {
            $poteau += 3;
        }
        foreach ($couleurs_poteaux as $couleur_poteau) {
            $preview_file = $root_path . 'views/images/' . $return["poteau"] . $session->installation . self::getSoubassement($sousbassement, $dependOfColor = false) . '-' . $poteau . '-' . $couleur_poteau . '.png';

            if (strpos($session->color, $couleur_poteau) !== false) {
                $image_poteau .= $session->installation . self::getSoubassement($sousbassement, $dependOfColor = false) . '-' . $poteau . '-' . $couleur_poteau . '.png';
            }
        }

        $return["poteau"] .= isset($image_poteau) ? $image_poteau : $session->installation . self::getSoubassement($sousbassement, $dependOfColor = false) . '-' . $poteau . '.png';

        // Si type souple, ajout des jambes de force
        if ($session->isSouple()) {
            // Couleurs des jambes
            $couleurs_jambes = ['vert', 'gris', 'noir', 'blanc'];
            $image_jambe     = '';

            foreach ($couleurs_jambes as $couleur_jambe) {
                $preview_file = $root_path . 'views/images/' . $return["jambe"] . $session->installation . self::getSoubassement($sousbassement, $dependOfColor = false) . '-' . $poteau . '-' . $couleur_jambe . '.png';
                if (strpos($session->color, $couleur_jambe) !== false && file_exists($preview_file)) {
                    $image_jambe .= $session->installation . self::getSoubassement($sousbassement, $dependOfColor = false) . '-' . $poteau . '-' . $couleur_jambe . '.png';
                }
            }

            $return["jambe"] .= isset($image_jambe) ? $image_jambe : $session->installation . self::getSoubassement($sousbassement, $dependOfColor = false) . '-' . $poteau . '.png';
        }

        $return["occultation"] .= $session->has('occultation') ? $occultation : '';
        $return["portillon"]   .= $portillon;

        return $return;
    }

    static public function getTypeName($type_id)
    {

        switch ($type_id) {
            case self::PROS_ID:
                return 'pro';
            case self::PROS_PLUS_ID:
                return 'pro-plus';
            case self::SOUDES_ID:
                return 'soude';
            case self::SIMPLES_ID:
                return 'simple';
            case self::AGRICOLES_ID:
                return 'agricole';
            case self::INDUSTRIELS_ID:
                return 'industriel';
            case self::DECORATIFS_ID:
                return 'decoratif';
            case self::NATURES_ID:
                return 'nature';
            case self::LAMES_PLEINES_ID:
                return 'pleine';
            case self::LAMES_AJOUREES_ID:
                return 'ajourees';
        }
    }

    static public function getSoubassement($sousbassement, $dependOfColor = true)
    {
        if (!$sousbassement) {
            return '';
        }
        if ($sousbassement['id_product'] == 416) {

            if (!$dependOfColor) {
                return '-soubassement-25';
            }

            if ($sousbassement['id_product_attribute'] == 7508) {
                return '-soubassement-25-pierre';
            }
            elseif ($sousbassement['id_product_attribute'] == 7509) {
                return '-soubassement-25-beton';
            }
            else {
                return '-soubassement-25';
            }
        }
        elseif ($sousbassement['id_product'] == 806) {

            if (!$dependOfColor) {
                return '-soubassement-50';
            }

            if ($sousbassement['id_product_attribute'] == 11299355) {
                return '-soubassement-50';
            }
            else {
                return '-soubassement-50';
            }
        }
        else {
            return '';
        }
    }

    static public function getOccultation($occultation, $language_id, $soubassement_id)
    {
        $root_path = '/var/www/districlos/modules/ckc_configurator/';
        if ($occultation !== false) {
            $sql        = 'SELECT name FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (al.id_attribute = pac.id_attribute) WHERE id_product_attribute = ' . $occultation['id_product_attribute'] . ' AND id_lang = ' . $language_id;
            $attributes = \Db::getInstance()->ExecuteS($sql);
            foreach ($attributes as $attribute) {
                if (strpos($attribute['name'], 'Ht ') !== false || strpos($attribute['name'], 'ht-') !== false || strpos($attribute['name'], '0m') !== false || strpos($attribute['name'], '1m') !== false || strpos($attribute['name'], '2m') !== false) {
                    $height = str_replace('.', '', floatval(str_replace('m', '.', str_replace('Ht ', '', $attribute['name']))));
                }
            }

            $couleurs_occultations = [
                'vert'   => 'Vert',
                'blanc'  => 'Blanc',
                'noir'   => 'Noir',
                'gris'   => 'Gris',
                'quartz' => 'Quartz',
                'chene'  => 'Chêne',
                'perle'  => 'Gris perle',
                'marron' => 'Marron Ecorce'
            ];

            $filename = '';

            // Color
            foreach ($attributes as $attribute) {

                foreach ($couleurs_occultations as $couleur_occultation_key => $couleur_occultation) {
                    if (stripos($attribute['name'], $couleur_occultation) !== false) {
                        $filename = 'occultations/' . $occultation['id_product'] . '-' . $height . '-' . $couleur_occultation_key;
                    }
                }
                if (empty($filename)) {
                    $filename = 'occultations/' . $occultation['id_product'] . '-' . $height;
                }
            }

            // Soubassement
            if ($soubassement_id) {
                if ($soubassement_id == 416 && file_exists($root_path . $filename . '-soub025.png')) {
                    $filename .= '-soub025';
                }
                elseif ($soubassement_id == 806 && file_exists($root_path . $filename . '-soub050.png')) {
                    $filename .= '-soub050';
                }
            }

            return $filename . '.png';
        }

        return false;
    }

    static public function getPortillon($portillon, $language_id, $scellement, $color)
    {
        $root_path  = '/var/www/districlos/modules/ckc_configurator/';
        $attributes = [];

        if ($portillon !== false) {
            $sql        = 'SELECT name FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (al.id_attribute = pac.id_attribute) WHERE id_product_attribute = ' . $portillon['id_product_attribute'] . ' AND id_lang = ' . $language_id;
            $attributes = \Db::getInstance()->ExecuteS($sql);

            foreach ($attributes as $attribute) {
                if (strpos($attribute['name'], 'Ht ') !== false || strpos($attribute['name'], 'ht-') !== false || strpos($attribute['name'], '0m') !== false || strpos($attribute['name'], '1m') !== false || strpos($attribute['name'], '2m') !== false) {
                    $height = str_replace('.', '', floatval(str_replace('m', '.', str_replace('Ht ', '', $attribute['name']))));
                }
            }
            $filename = '' . $portillon['id_product'] . '-' . $height;
        }
        else {
            $filename = 'none';
        }

        // Prise en compte du type de scellement
        $typesScellement = [
            'scellement',
            'scellement-sur-muret',
            'scellement-sur-platine'
        ];

        // Prise en compte de la couleur
        $couleursPortillons = ['vert', 'gris', 'noir', 'blanc'];

        foreach ($typesScellement as $typeScellement) {

            if ($scellement != $typeScellement) {
                continue;
            }
            foreach ($couleursPortillons as $couleurPortillon) {
                foreach ($attributes as $attribute) {
                    if (stripos($attribute['name'], $couleurPortillon) !== false && file_exists($root_path . 'views/images/portillons/' . $filename . '-' . $typeScellement . '-' . $couleurPortillon . '.png')) {
                        $filename .= '-' . $typeScellement . '-' . $couleurPortillon;
                    }
                }
            }
        }

        return 'portillons/' . $filename . '.png';
    }
}
