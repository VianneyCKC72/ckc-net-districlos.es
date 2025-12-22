<?php
/**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author     PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2017 PrestaShop SA
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace CkcConfigurator\Configurator;

use CkcConfigurator\Product\Grillage;
use CkcConfigurator\Product\Accessory;
use CkcConfigurator\Config;
use Product;

class Session extends \ObjectModel implements Config
{
    public $id_configurator_session;
    public $user_key;
    public $id_category;
    public $id_type;
    public $length;
    public $poteaux;
    public $clips;
    public $installation;
    public $espacement;
    public $depart_mur;
    public $nombre_angle;
    public $height;
    public $color;
    public $id_product_attribute;
    public $deconseil;
    public $default_session;

    // FAKE PROPERTIES
    public $type_name;
    public $installation_formatted;
    public $height_formatted;
    public $color_formatted;

    public static $definition = [
        'table'   => 'configurator_sessions',
        'primary' => 'id_configurator_session',
        'fields'  => [
            'user_key'             => ['type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true],
            'id_category'          => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'id_type'              => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'is_aluminium'         => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'espacement'           => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'length'               => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'depart_mur'           => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'nombre_angle'         => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'installation'         => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'height'               => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'poteaux'              => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'clips'                => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'color'                => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'deconseil'            => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'default_session'      => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'date_add'             => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'             => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function __construct($id_configurator_session = null, $language_id)
    {
//        echo "<br>";
//        echo "__construct id_configurator_session = " . $id_configurator_session;
//        echo "<br>";
        parent::__construct($id_configurator_session);
        if (!empty($this->id_type)) {
            $category        = new \Category($this->id_type, $language_id);
            $this->type_name = $category->name;
        }
        if (!empty($this->installation)) {
            foreach (self::INSTALLATIONS as $installation) {
                if ($installation['id'] == $this->installation) {
                    $this->installation_formatted = $installation['name'];
                }
            }
        }

        if (!empty($this->height)) {
            $this->height_formatted = str_replace('ht-', '', $this->height);
        }
        if (!empty($this->color)) {
            $this->color_formatted = ucfirst(explode('-', $this->color)[0]);
        }
    }

    // GETTERS

    public function category($language_id)
    {
        return new \Category($this->id_category, $language_id);
    }

    public function main()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'configurator_session_entries WHERE type = "main" AND id_configurator_session = ' . $this->id;
        return \Db::getInstance()->getRow($sql);
    }

    public function accessories()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'configurator_session_entries WHERE type = "accessory" AND id_configurator_session = ' . $this->id;
        return \Db::getInstance()->ExecuteS($sql);
    }

    public function soubassement()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'configurator_session_entries WHERE type = "soubassement" AND id_configurator_session = ' . $this->id;
        return \Db::getInstance()->getRow($sql);
    }

    public function kitdemontage()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'configurator_session_entries WHERE type = "kitmontage" AND id_configurator_session = ' . $this->id;
        return \Db::getInstance()->getRow($sql);
    }

    public function portillon()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'configurator_session_entries WHERE type = "portillon" AND id_configurator_session = ' . $this->id;
        return \Db::getInstance()->getRow($sql);
    }

    public function occultation()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'configurator_session_entries WHERE type = "occultation" AND id_configurator_session = ' . $this->id;
        return \Db::getInstance()->getRow($sql);
    }

    public function panneau_deco()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'configurator_session_entries WHERE type = "panneau_deco" AND id_configurator_session = ' . $this->id;
        return \Db::getInstance()->getRow($sql);
    }

    // HELPERS

    public function has($key)
    {
        return $this->$key() !== false ? true : false;
    }

    public function isRigide()
    {
        if ($this->id_category == self::RIGIDES_ID)
            return true;
        if ($this->id_category == self::PROS_ID)
            return true;
        if ($this->id_category == self::PROS_PLUS_ID)
            return true;
        if ($this->id_category == self::BRICO_PLUS_ID)
            return true;


        return false;
    }

    public function isSouple()
    {
        if ($this->id_category == self::SOUPLES_ID)
            return true;

        if ($this->id_category == self::SOUDES_ID)
            return true;
        if ($this->id_category == self::SIMPLES_ID)
            return true;
        if ($this->id_category == self::AGRICOLES_ID)
            return true;


        return false;
    }

    public function isAluminium()
    {
        $aluminiumCategories = [
            self::ALUMINIUM_ID,
            self::LAMES_PLEINES_ID,
            self::LAMES_AJOUREES_ID,
            self::COMPOSITE_ID,
            self::COMPOSITE_PLEINES_ID,
        ];

        return in_array($this->id_category, $aluminiumCategories, true);
    }

    public function hasEspacement($type)
    {
        if ($this->isAluminium() && !empty($type)) {
            if (in_array($type, self::CATEG_HAS_ESPACEMENT)) {
                return true;
            }
        }
        return false;
    }

    public function isBarreaudage()
    {
        return $this->id_category == self::BARREAUDAGES_ID;
    }

    public function totalPrice()
    {
        $price = 0;

        foreach ($this->entries() as $entry) {
            $result = Helper::getPrice($entry['id_product'], $entry['id_product_attribute'], $entry['quantity']);
            $price  += $result['total_price'];
        }

        return $price;
    }

    public function getProducts($language_id, $session)
    {
        $data = [];
        foreach ($this->entries() as $entry) {

            if ($session->installation == 'scellement-sur-platine' && $entry['id_product'] == 230) {
                continue;
            }

            $name = \Product::getProductName($entry['id_product'], $entry['id_product_attribute'], $language_id);
            
            if (!$name) {
                $name = (new \Product($entry['id_product']))->name[$language_id];
            }
            
            // Si id_product_attribute est 0, essayer de trouver les attributs depuis les déclinaisons du produit
            $attributes = null;
            if (!$entry['id_product_attribute']) {
                $product = new \Product($entry['id_product'], false, $language_id);
                $combinations = $product->getAttributesResume($language_id);
                
                if (!empty($combinations)) {
                    $selectedCombination = null;
                    $sessionColor = !empty($session->color) ? strtolower($session->color) : null;
                    $sessionHeight = !empty($session->height) ? strtolower($session->height) : null;
                    
                    // Chercher la déclinaison qui correspond à la couleur et hauteur de la session
                    foreach ($combinations as $combination) {
                        if (empty($combination['id_product_attribute'])) {
                            continue;
                        }
                        
                        $comboAttributes = \Product::getAttributesParams($entry['id_product'], $combination['id_product_attribute']);
                        $matchesColor = false;
                        $matchesHeight = false;
                        
                        foreach ($comboAttributes as $attr) {
                            $attrName = strtolower($attr['name']);
                            $attrGroup = strtolower($attr['group']);
                            
                            // Vérifier la couleur
                            if ($sessionColor && $attrGroup == 'couleur') {
                                if ($attrName == $sessionColor || strpos($attrName, $sessionColor) !== false || strpos($sessionColor, $attrName) !== false) {
                                    $matchesColor = true;
                                }
                            }
                            
                            // Vérifier la hauteur
                            if ($sessionHeight && ($attrGroup == 'hauteur' || $attrGroup == 'hauteur-du-poteau')) {
                                $attrHeight = str_replace('m', '.', strtolower($attrName));
                                $sessionHeightFloat = str_replace('m', '.', $sessionHeight);
                                if ($attrHeight == $sessionHeightFloat || strpos($attrHeight, $sessionHeightFloat) !== false) {
                                    $matchesHeight = true;
                                }
                            }
                        }
                        
                        // Si on a une correspondance pour couleur ET hauteur (ou au moins couleur si pas de hauteur dans les attributs)
                        if ($matchesColor && ($matchesHeight || !$sessionHeight)) {
                            $selectedCombination = $combination;
                            break;
                        }
                    }
                    
                    // Si pas de correspondance exacte, prendre la première déclinaison qui correspond à la couleur
                    if (!$selectedCombination && $sessionColor) {
                        foreach ($combinations as $combination) {
                            if (empty($combination['id_product_attribute'])) {
                                continue;
                            }
                            $comboAttributes = \Product::getAttributesParams($entry['id_product'], $combination['id_product_attribute']);
                            foreach ($comboAttributes as $attr) {
                                $attrName = strtolower($attr['name']);
                                $attrGroup = strtolower($attr['group']);
                                if ($attrGroup == 'couleur' && ($attrName == $sessionColor || strpos($attrName, $sessionColor) !== false || strpos($sessionColor, $attrName) !== false)) {
                                    $selectedCombination = $combination;
                                    break 2;
                                }
                            }
                        }
                    }
                    
                    // Si toujours rien, prendre la première déclinaison
                    if (!$selectedCombination) {
                        $selectedCombination = reset($combinations);
                    }
                    
                    if (!empty($selectedCombination['id_product_attribute'])) {
                        $attributes = \Product::getAttributesParams($entry['id_product'], $selectedCombination['id_product_attribute']);
                        
                        // Construire le nom avec les attributs
                        if (!empty($attributes)) {
                            $attributeParts = [];
                            foreach ($attributes as $attr) {
                                if (!empty($attr['group']) && !empty($attr['name'])) {
                                    $attributeParts[] = $attr['group'] . ' - ' . $attr['name'];
                                }
                            }
                            if (!empty($attributeParts)) {
                                $baseName = (new \Product($entry['id_product']))->name[$language_id];
                                $name = $baseName . ' : ' . implode(', ', $attributeParts);
                            }
                        }
                    }
                }
            } else {
                $attributes = \Product::getAttributesParams($entry['id_product'], $entry['id_product_attribute']);
            }

            $price = Helper::getPrice($entry['id_product'], $entry['id_product_attribute'], $entry['quantity']);

            $data[] = [
                "id"       => $entry['id_product'],
                "name"     => $name,
                "quantity" => $entry['quantity'],
                "price"    => (string)number_format($price['total_price'], 2)
            ];

        }

        return $data;
    }

    public function entries()
    {
        $sql     = 'SELECT * FROM ' . _DB_PREFIX_ . 'configurator_session_entries WHERE id_configurator_session = ' . $this->id_configurator_session . ' ORDER BY id_configurator_session_entry';
        $entries = \Db::getInstance()->ExecuteS($sql);

        return count($entries) > 0 ? $entries : null;
    }

    public function insertEntry($data)
    {
        $data = array_merge($data, ['date_add' => date('Y-m-d'), 'date_upd' => date('Y-m-d')]);
//        dump($data);
        \Db::getInstance()->insert('configurator_session_entries', $data);
    }

    public function updateProductQuantityEntry($id_product, $action)
    {
        $currentQuantity = \Db::getInstance()->getValue('SELECT quantity FROM ' . _DB_PREFIX_ . 'configurator_session_entries WHERE id_product = ' . $id_product . ' AND id_configurator_session = ' . $this->id);
        $quantity        = ($action == "more") ? $currentQuantity + 1 : $currentQuantity - 1;

        return \Db::getInstance()->update('configurator_session_entries', [
            'quantity' => $quantity,
        ],                                'id_product = ' . $id_product);
    }

    public function deleteEntries()
    {
        \Db::getInstance()->delete('configurator_session_entries', 'id_configurator_session = ' . $this->id);
    }

    public function deleteEntryByType($type)
    {

        \Db::getInstance()->delete('configurator_session_entries', 'id_configurator_session = ' . $this->id . ' AND type = "' . $type . '"');
    }

    public function update($null_values = false, $default = 0)
    {
//        $sql = new \DbQuery();
//        $sql->select('*');
//        $sql->from('configurator_sessions', 'cs');
//        $sql->where('id_configurator_session = ' . $this->id_configurator_session);
//
//        $result = \Db::getInstance()->executeS($sql);
//        if (empty($result)) {
//            dump($this->id_configurator_session);
//            exit;
//        }

        $values = [
            'user_key'             => pSQL($this->user_key),
            'id_category'          => $this->id_category ?? null,
            'id_type'              => $this->id_type ?? null,
            'length'               => $this->length ?? null,
            'depart_mur'           => $this->depart_mur ?? null,
            'nombre_angle'         => $this->nombre_angle ?? null,
            'is_aluminium'         => $this->is_aluminium ?? null,
            'espacement'           => $this->espacement ?? null,
            'installation'         => $this->installation ?? null,
            'height'               => $this->height ?? null,
            'poteaux'              => $this->poteaux ?? null,
            'clips'                => $this->clips ?? null,
            'color'                => $this->color ?? null,
            'default_session'      => $default,
            'deconseil'            => $this->deconseil,
            'id_product_attribute' => $this->id_product_attribute,
            'date_upd'             => date('Y-m-d H:i:s')
        ];
//        if (empty($this->height)) {
//            // Obtenir la liste des appels de fonction dans la pile d'appels
//            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
//
//            // Parcourir les appels de fonction et afficher le nom de chaque fonction
//            foreach ($backtrace as $call) {
//                if (isset($call['function']) && $call['function'] === 'var_dump') {
//                    break; // Arrêter la boucle lorsque nous atteignons var_dump
//                }
//
//                if (isset($call['function'])) {
//                    echo "Appel de fonction : " . $call['function'] . "()<br>";
//                }
//            }
//        }

        $result = \Db::getInstance()->update('configurator_sessions', $values, 'id_configurator_session = ' . $this->id_configurator_session);

        return $result;
    }

    public function change($column, $value)
    {
        $context = \Context::getContext();

        if (is_array($column)) {
            foreach ($column as $index => $key) {
                $this->$key = $value[$index];

                if ($key == 'height' || $key == 'color') {
                    $this->{$key . '_formatted'} = ucfirst(str_replace('-', ' ', $value[$index]));
                }
                elseif ($key == 'installation') {
                    foreach (self::INSTALLATIONS as $installation) {
                        if ($installation['id'] == $value[$index]) {
                            $this->installation_formatted = $installation['name'];
                        }
                    }
                }
                elseif ($key == 'poteaux') {
                    $product = new Product($value[$index], false, $context->language->id);
                    $this->clips = strpos($product->name, 'Clips') !== false ? 1 : 0;
                }
            }
        }
        else {
            $this->$column = $value;
            if ($column == 'id_type') {
                $category        = new \Category($value, $context->language->id);
                $this->type_name = $category->name;
            }
        }

        $this->update();

        return $this;
    }

    // TODO: complete other
    public function default($language_id, $module, $products)
    {
        $this->default_session = 1;
        $this->is_aluminium    = 0;
        if ($this->isRigide()) {
            $this->id_type = isset($this->id_type) ? $this->id_type : self::PROS_ID;
            if ($this->id_type == self::PROS_ID) {
                $this->length       = 5.00;
                $this->installation = "scellement";
                $this->depart_mur   = 0;
                $this->nombre_angle = 0;
                $this->height       = "1m03";
                $this->color        = "vert-6005";
                $this->clips        = 0;
                $this->poteaux      = 1223;
            }
            else {
                $this->length       = 5.00;
                $this->installation = "scellement";
                $this->height       = "1m03";
                $this->color        = "vert-6005";
                $this->espacement   = 0;
                $this->clips        = 0;
                $this->poteaux      = 1223;
            }
        }
        elseif ($this->isSouple()) {
            $this->id_type      = isset($this->id_type) ? $this->id_type : self::SOUDES_ID;
            $this->length       = 25.00;
            $this->installation = "scellement";
            $this->height       = "1m00";
            $this->color        = "vert-6005";
            $this->espacement   = 0;
            $this->depart_mur   = 0;
            $this->nombre_angle = 0;
        }
        elseif ($this->isAluminium()) {
            $isComposite = in_array($this->id_category, [self::COMPOSITE_ID, self::COMPOSITE_PLEINES_ID], true)
                || in_array($this->id_type, [self::COMPOSITE_PLEINES_ID], true);

            $this->id_type      = isset($this->id_type)
                ? $this->id_type
                : ($isComposite ? self::COMPOSITE_PLEINES_ID : self::LAMES_PLEINES_ID);
            $this->length       = 2.09;
            $this->installation = "scellement-sur-muret";
            $this->color        = "gris-7016";
            $this->is_aluminium = 1;
            $this->depart_mur   = 0;
            $this->nombre_angle = 0;
            $isPleines = in_array($this->id_type, [self::LAMES_PLEINES_ID, self::COMPOSITE_PLEINES_ID], true);
            $isAjour   = in_array($this->id_type, [self::LAMES_AJOUREES_ID], true);

            if ($isAjour) {
                $this->espacement = 1;
                $this->height     = "0m403";
            }
            else {
                $this->espacement = 0;
                // Hauteur par défaut composite pleines : 0m53 (sinon fallback alu 0m515)
                $this->height     = $isComposite ? "0m53" : "0m515";
            }
        }
        elseif ($this->isBarreaudage()) {
            $this->id_type      = isset($this->id_type) ? $this->id_type : self::NATURES_ID;
            $this->length       = 5.00;
            $this->installation = "scellement";
            $this->height       = "1m50";
            $this->color        = "vert-6005";
            $this->espacement   = 0;
            $this->depart_mur   = 0;
            $this->nombre_angle = 0;
        }

        if (isset($this->id_type)) {
            $category        = new \Category($this->id_type, $language_id);
            $this->type_name = $category->name;
        }
        if (!empty($this->installation)) {
            foreach (self::INSTALLATIONS as $installation) {
                if ($installation['id'] == $this->installation) {
                    $this->installation_formatted = $installation['name'];
                }
            }
        }
        if (!empty($this->height)) {
            $this->height_formatted = str_replace('ht-', '', $this->height);
        }
        if (!empty($this->color)) {
            $this->color_formatted = ucfirst(explode('-', $this->color)[0]);
        }

        $this->change('id_product_attribute', $module->getIdProductAttribute($products, $this->height, $this->color));

        // Get price of Grillage and set products for displaying
        $grillage = Grillage::get($this, $language_id);

        $grillageEntry = [
            'id_configurator_session' => $this->id,
            'id_product'              => $grillage['id'],
            'id_product_attribute'    => $this->id_product_attribute,
            'quantity'                => $grillage['quantity'],
            'type'                    => 'main'
        ];

        $this->insertEntry($grillageEntry);

        // Same for accessories
        $accessories = Accessory::get($this, $language_id, false);

        foreach ($accessories as $accessory) {
            $dataEntry = [
                'id_configurator_session' => $this->id,
                'id_product'              => $accessory['id'],
                'id_product_attribute'    => $accessory['id_attribute'],
                'quantity'                => $accessory['quantity'],
                'type'                    => 'accessory'
            ];

            $this->insertEntry($dataEntry);
        }

        return $this->update(false, 1);
    }

    // STATIC HELPERS
    static public function destroy($user_key)
    {
        if ($session = Session::find($user_key)) {
            if($session->id) {
                \Db::getInstance()->delete('configurator_sessions', 'id_configurator_session = ' . $session->id);
                \Db::getInstance()->delete('configurator_session_entries', 'id_configurator_session = ' . $session->id);
            }

            \Db::getInstance()->delete('configurator_sessions', 'date_add < "' . date('Y-m-d') . '"');
            \Db::getInstance()->delete('configurator_session_entries', 'date_add < "' . date('Y-m-d') . '"');
        }

        return true;
    }

    static public function init($user_key, $language_id)
    {
//        echo "<hr>";
//        echo "init()";
//        echo "<br>";
//        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
//
//        foreach ($backtrace as $call) {
//            if (isset($call['function']) && $call['function'] === 'var_dump') {
//                break; // Arrêter la boucle lorsque nous atteignons var_dump
//            }
//
//            if (isset($call['function'])) {
//                $functionName = $call['function'];
//                $fileName     = isset($call['file']) ? $call['file'] : 'N/A';
//                $lineNumber   = isset($call['line']) ? $call['line'] : 'N/A';
//
//                echo "Appel de fonction : $functionName() dans le fichier $fileName à la ligne $lineNumber<br>";
//            }
//        }
//        echo "<hr>";
        self::destroy($user_key);

        \Db::getInstance()->insert('configurator_sessions', [
            'user_key' => pSQL($user_key),
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ]);

        return Session::find($user_key, $language_id);
    }

    static public function find($user_key, $language_id = null)
    {
        $sql = 'SELECT id_configurator_session FROM ' . _DB_PREFIX_ . 'configurator_sessions WHERE user_key = "' . $user_key . '"';

        $id_configurator_session = \Db::getInstance()->getValue($sql);
//        echo "<br>dans Find() => $id_configurator_session<br>";
        return ($id_configurator_session !== false) ? new Session($id_configurator_session, $language_id) : false;
    }
}
