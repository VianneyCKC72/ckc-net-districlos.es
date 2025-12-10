<?php
/**
 * 2007-2019 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
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
 * @copyright  2007-2019 PrestaShop SA
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 */

use CkcConfigurator\Config;
use CkcConfigurator\Configurator\Preview;
use CkcConfigurator\Configurator\Session;
use CkcConfigurator\Configurator\Submit;
use CkcConfigurator\Configurator\Helper;
use CkcConfigurator\Options\KitMontage;
use CkcConfigurator\Options\Occultation;
use CkcConfigurator\Options\Portillon;
use CkcConfigurator\Options\Soubassement;
use CkcConfigurator\Options\PanneauDeco;
use CkcConfigurator\Product\Grillage;
use CkcConfigurator\Product\Accessory;

require __DIR__ . '/../../vendor/autoload.php';
include_once _PS_MODULE_DIR_.'dealt/src/DealtHookHelper.php';
if (file_exists(_PS_MODULE_DIR_.'dealt/src/DealtHookHelper.php')) {
    include_once _PS_MODULE_DIR_.'dealt/src/DealtHookHelper.php';
}

class Ckc_ConfiguratorConfiguratorModuleFrontController extends ModuleFrontController implements Config
{
    public $success = false;

    private $language_id;
    private $configurator;
    private $session;
    private $data;

    public function init()
    {
//        echo "<br>--InIt() ConfiGuraTeur--</br>";
        $this->page_name    = 'configurator';
        $this->language_id  = $this->context->language->id;
        $this->configurator = new Category(self::CONFIGURATOR_ID, $this->language_id);
        $this->session      = Session::find($this->userKey(), $this->language_id);

        parent::init();
    }

    // Ouverture de la page
    public function initContent()
    {
        parent::initContent();
        
        if ($this->module->isLive()) {
            $this->context->smarty->assign('meta_title', 'Configurez votre clôture et sécurisez votre extérieur avec Districlos');
//            $this->session = Session::init($this->userKey(), $this->language_id);
            $this->context->smarty->assign(["categories" => $this->configurator->getSubCategories($this->language_id, true)]);
            $this->init = true;
            return $this->getDisplay('configurator.tpl');
        }
        else {
            
            return Tools::redirect('/');
        }
    }

    /**
     * POST PROCESS METHODS
     */
    public function postProcess()
    {
        if ($this->module->isLive()) {
            if ($this->ajax) {
                if (!$this->session) {
                    $this->session = Session::init($this->userKey(), $this->language_id);
                }

                if (Tools::getValue('id_category', false)) {
                    $this->processSubmitAjaxMainCategory();
                }
                elseif (Tools::isSubmit('changeType')) {
                    $this->processSubmitChangeType();
                }
                elseif (Tools::isSubmit('updateprice') || Tools::isSubmit('changeEspacement')) {
                    $this->processSubmitUpdatePrice();
                }
                elseif (Tools::isSubmit('updateQuantity')) {
                    $this->processSubmitUpdateQuantity();
                }
                elseif (Tools::isSubmit('addToCart')) {
                    if ($this->processSubmit()) {
                        Session::destroy($this->userKey());
                        $this->success = true;
                        $cart = Context::getContext()->cart;
                        $dealt_service_product_id = '';
                        $dealt_product_id = '';
                        $dealt_id_product_attribute = '';
                        $cartProducts = $cart->getProducts();
                        foreach ($cartProducts as $product) {
                            if((int)$product['id_category_default'] == 369){
                                $dealt_service_product_id = $product['id_product'];
                                $dealt_product_id = $cartProducts[0]['id_product'];
                                $dealt_id_product_attribute = $cartProducts[0]['id_product_attribute'];
                                break;
                            }
                        }
                        return $this->ajaxRender(json_encode([
                            "success" => $this->success,
                            'dealt_service_product_id' => $dealt_service_product_id,
                            "dealt_product_id" => $dealt_product_id,
                            'dealt_id_product_attribute' => $dealt_id_product_attribute,
                        ]));
                    }
                }

                // S'assurer que $this->data est initialisé
                if (!isset($this->data)) {
                    $this->data = [];
                }

                $sousbassement_id = $this->session->has('soubassement') ? $this->session->soubassement()['id_product'] : false;
                $selected_soubassement_id = '';
                $selected_soubassement_id_product_attribute = '';
                
                $soubassement_session = $this->session->soubassement();
                if ($soubassement_session) {
                    $selected_soubassement_id = $soubassement_session['id_product'];
                    $selected_soubassement_id_product_attribute = $soubassement_session['id_product_attribute'];
                }
                $selected_portillon_id_product_attribute   = '';
                $selected_portillon_id = '';
                $selected_occultation_id_product_attribute = '';
                $selected_occultation_id = '';
                $selected_panneau_deco_id_product_attribute = '';

                if (!empty(Tools::getValue('panneau_deco')['id_product_attribute'])) {
                    $selected_panneau_deco_id_product_attribute = Tools::getValue('panneau_deco')['id_product_attribute'];
                }

                if(!empty($this->data['heights']) && !$this->session->isAluminium()) {
                    $arrTpmHeight = [];
                    foreach ($this->data['heights'] as $height) {
                        $key = str_replace('m', '', $height['value']);
                        $arrTpmHeight[$key] = $height;
                    }

                    ksort($arrTpmHeight);
                    $this->data['heights'] = $arrTpmHeight;
                }

                $this->data['poteaux'] = null;
                if ($this->session->isRigide()) {
                    $category       = new Category(self::POTEAUX_RIGIDE_ID, $this->language_id);
                    $poteaux   = $category->getProducts($this->language_id, 1, 100, null, null, false, true);
                    foreach ($poteaux as $poteau) {
                        if ($poteau['id_product'] == 1285) {
                            continue;
                        }
                        $this->data['poteaux'][] = [
                            "value"   => $poteau['id_product'],
                            "name" => strpos($poteau['name'], 'clip') ? 'Clips' : 'Encoches',
                            "checked" => $this->session->poteaux == $poteau['id_product']
                        ];
                    }
                }
                $products = $this->session->getProducts($this->language_id, $this->session);
                $portillon_session = $this->session->portillon();
                if ($portillon_session) {
                    $selected_portillon_id_product_attribute = $portillon_session['id_product_attribute'];
                    $selected_portillon_id = $portillon_session['id_product'];
                } elseif (!empty(Tools::getValue('portillon')['id_product_attribute'])) {
                    foreach ($products as $product) {
                        if (strpos($product['name'], 'Portillon') !== false) {
                            $selected_portillon_id_product_attribute = Tools::getValue('portillon')['id_product_attribute'];
                            $selected_portillon_id = Tools::getValue('portillon')['id'];
                        }
                    }
                }

                $occultation_session = $this->session->occultation();
                if ($occultation_session) {
                    $selected_occultation_id_product_attribute = $occultation_session['id_product_attribute'];
                    $selected_occultation_id = $occultation_session['id_product'];
                } elseif (!empty(Tools::getValue('occultation')['id_product_attribute'])) {
                    foreach ($products as $product) {
                        if (strpos($product['name'], 'Occultation') !== false || strpos($product['name'], 'Brise vue') !== false) {
                            $selected_occultation_id_product_attribute = Tools::getValue('occultation')['id_product_attribute'];
                            $selected_occultation_id = Tools::getValue('occultation')['id'];
                        }
                    }
                }

                if (!empty(Tools::getValue('service')['id'])) {
                    $selected_service_id = Tools::getValue('service')['id'];
                    $selectedServiceInfos = Db::getInstance()->executeS('SELECT DISTINCT pl.name, p.price as price_ht FROM `' . _DB_PREFIX_ . 'product_lang` pl
                    LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = pl.id_product
                            WHERE pl.`id_product` = ' . $selected_service_id);

                    $serviceSelectedProduct['id'] = $selected_service_id;
                    $serviceSelectedProduct['name'] = $selectedServiceInfos[0]['name'];
                    $serviceSelectedProduct['quantity'] = "1";
                    $serviceSelectedProduct['price'] = number_format(($selectedServiceInfos[0]['price_ht'] * 1.10), 2, '.', '');

                    $products[] = $serviceSelectedProduct;
                    $this->addServiceEntry($selected_service_id);
                } else {
                    $serviceSelectedProduct = null;
                }

                $price = $this->session->totalPrice();
                $price = number_format($price, 2);

                // complet !
                //                dump($this->data['colors']);
                if(!empty($products[0]["id"]) && !empty($this->session->length) && class_exists('\Dealt\DealtHookHelper')){
                    $serviceTitles = [];
                    $services = \Dealt\DealtHookHelper::buildProductAdditionalInfoData($this->context, (int)$products[0]["id"], (int)$this->session->length);
                    if(count($services)){
                        foreach ($services as $key => $service) {
                            if(!empty($service['dealt_service_product_id'])){
                                $serviceId = $service['dealt_service_product_id'];
                                $serviceTitleSQL = Db::getInstance()->executeS('SELECT pl.name FROM `' . _DB_PREFIX_ . 'product_lang` pl
                            WHERE pl.`id_product` = ' . $serviceId);
                                $serviceTitle = $serviceTitleSQL[0]['name'];
                                $serviceTitles[$key] = $serviceTitle;
                            }
                        }
                    }
                } else {
                    $services = null;
                    $serviceTitles = null;
                }

                // Réponse AJAX
                $this->ajaxRender(json_encode([
                    "second"                                    => $this->getHtml('form.tpl'),
                    "third"                                     => $this->getHtml('options.tpl'),
                    "fourth"                                    => Context::getcontext()->shop->id == 1 ? $this->getHtml('services.tpl') : '',
                    "heights"                                   => $this->data['heights'] ?? [],
                    "colors"                                    => $this->data['colors'] ?? [],
                    "poteaux"                                   => $this->data['poteaux'] ?? [],
                    "sousbassement_id"                          => $sousbassement_id ? (int)$sousbassement_id : null,
                    "sousbassement"                             => Soubassement::get($this->language_id, $this->session),
                    "occultations"                              => Occultation::get($this->language_id, $this->session->id_product_attribute, $this->session->isSouple()),
                    "panneaux_deco"                             => PanneauDeco::get($this->language_id, $this->session->isAluminium(), $this->session),
                    "selected_soubassement_id_product_attribute" => $selected_soubassement_id_product_attribute,
                    "selected_soubassement_id"                   => $selected_soubassement_id,
                    "selected_occultation_id_product_attribute" => $selected_occultation_id_product_attribute,
                    "selected_occultation_id"                   => $selected_occultation_id,
                    "portillons"                                => Portillon::get($this->language_id, $this->session->id_product_attribute, $sousbassement_id, $this->session->isSouple(), $this->session->isAluminium()),
                    "selected_portillon_id_product_attribute"   => $selected_portillon_id_product_attribute,
                    "selected_portillon_id"                     => $selected_portillon_id,
                    "selected_panneau_deco_id_product_attribute" => $selected_panneau_deco_id_product_attribute,
                    "session"                                   => $this->session,
                    "products"                                  => $products,
                    "price"                                     => $price,
                    // "preview"                                   => Preview::preview($this->language_id, $this->session),
                    "preview"                                   => false,
                    "hasError"                                  => (bool)$this->errors,
                    "errors"                                    => $this->errors,
                    "success"                                   => $this->success,
                    "is_rigide"                                 => $this->session->isRigide(),
                    "is_souple"                                 => $this->session->isSouple(),
                    "isAluminium"                               => $this->session->isAluminium(),
                    "services"                                  => $services,
                    'serviceTitles'                             => $serviceTitles,
                ]));
            }

        }
    }

    public function processSubmitAjaxMainCategory()
    {
        $this->session = Session::init($this->userKey(), $this->language_id);
        $id_category   = (int)Tools::getValue('id_category', false);

        $default = $this->session->id_category != $id_category;
        $this->session->change('id_category', $id_category);

        $category = $this->session->category($this->language_id);
        $types = $category->getSubCategories($this->language_id, true);
        $type     = new Category($types[0]["id_category"], $this->language_id);
        $products = $type->getProducts($this->language_id, 1, 100, null, null, false, false);

        if ($default) {
            $this->session->default($this->language_id, $this, $products);
        }

        $data = $this->getAttributes($products);
        $this->data = $data;

        $installations = self::INSTALLATIONS;

        if ($this->session->isSouple()) {
            $installations = [$installations[0]];
        }
        else if ($this->session->isBarreaudage()) {
            unset($installations[2]);
        }
        else if ($this->session->isAluminium()) {
            unset($installations[0]);
        }

        if(!empty($data['heights'])  && !$this->session->isAluminium()) {
            $arrTpmHeight = [];
            foreach ($data['heights'] as $height) {
                $arrTpmHeight[str_replace('m', '', $height['value'])] = $height;
            }

            ksort($arrTpmHeight);
            $data['heights'] = $arrTpmHeight;
        }

        $this->data['poteaux'] = null;
        if ($this->session->isRigide()) {
            $category       = new Category(self::POTEAUX_RIGIDE_ID, $this->language_id);
            $products   = $category->getProducts($this->language_id, 1, 100, null, null, false, true);
            foreach ($products as $product) {
                if ($product['id_product'] == 1285) {
                    continue;
                }
                $this->data['poteaux'][] = [
                    "value"   => $product['id_product'],
                    "name" => strpos($product['name'], 'clip') ? 'Clips' : 'Encoches',
                    "checked" => $this->session->poteaux == $product['id_product']
                ];
                if ($this->session->poteaux == $product['id_product'] && strpos($product['name'], 'clip') !== false) {
                    $this->session->clips = 1;
                }else{
                    $this->session->clips = 0;
                }
            }
        }

        $products = $this->session->getProducts($this->language_id, $this->session);

        if(!empty($products[0]["id"]) && !empty($this->session->length) && class_exists('\Dealt\DealtHookHelper')){

            $services = \Dealt\DealtHookHelper::buildProductAdditionalInfoData($this->context, (int)$products[0]["id"], (int)$this->session->length);
            $serviceTitles = [];
            if(count($services)){
                foreach ($services as $key => $service) {
                    if(!empty($service['dealt_service_product_id'])){
                        $serviceId = $service['dealt_service_product_id'];
                        $serviceTitleSQL = Db::getInstance()->executeS('SELECT pl.name FROM `' . _DB_PREFIX_ . 'product_lang` pl
                            WHERE pl.`id_product` = ' . $serviceId);
                        $serviceTitle = $serviceTitleSQL[0]['name'];
                        $serviceTitles[$key] = $serviceTitle;
                    }
                }
            }
        } else {
            $services = null;
            $serviceTitles = null;
        }


        $this->context->smarty->assign([
            "session"       => $this->session,
            "category"      => $category,
            "types"         => $types,
            "link"          => new Link(),
            "installations" => $installations,
            "heights"       => $data["heights"],
            "poteaux"       => $this->data['poteaux'],
            "colors"        => $data["colors"],
            "is_rigide"     => $this->session->isRigide(),
            "is_souple"     => $this->session->isSouple(),
            "isAluminium"   => $this->session->isAluminium(),
            "espacement"    => $this->session->espacement,
            "sousbassement" => Soubassement::get($this->language_id, $this->session),
            "portillons"    => Portillon::get($this->language_id, $this->session->id_product_attribute, false, $this->session->isSouple(), $this->session->isAluminium()),
            "occultations"  => Occultation::get($this->language_id, $this->session->id_product_attribute, $this->session->isSouple()),
            "panneaux_deco" => PanneauDeco::get($this->language_id, $this->session->isAluminium(), $this->session),
            "services"      => $services,
            'serviceTitles'  => $serviceTitles,
        ]);

        return true;
    }

    // Change gamme of grillage
    public function processSubmitChangeType()
    {

        $forceChange = Tools::isSubmit('forceChange') ? Tools::getValue('forceChange') : null;
        $type        = new Category((int)Tools::getValue('type'), $this->language_id);
        $products    = $type->getProducts($this->language_id, 1, 100, null, null, false, true);

        $this->session->id_type = (int) Tools::getValue('type');

        if(empty($this->session->height) || $this->session->isAluminium()) {
            $this->session->change('id_category', $type->id_parent);
            $this->session->default($this->language_id, $this, $products);
            $this->session->change('id_type', (int)Tools::getValue('type'));
        }
        if(!empty(Tools::getValue('espacement'))) {
            if ((int)Tools::getValue('espacement') != $this->session->espacement) {

                $this->session->espacement = (int)Tools::getValue('espacement');
                $this->session->change('espacement', (int)Tools::getValue('espacement'));
            }
        }

        $this->data  = $this->getAttributes($products);
        $heights     = [];
        $colors      = [];
        foreach ($this->data['heights'] as $height) {
            $heights[] = $height['value'];
        }
        foreach ($this->data['colors'] as $color) {
            $colors[] = $color['value'];
        }

        if((!in_array($this->session->height, $heights) || !in_array($this->session->color, $colors)) && !isset($forceChange)) {
            $this->errors[] = ["type" => "error", "msg" => "Les déclinaisons que vous avez sélectionné ne correspondent pas avec cette gamme de Clôture. Si vous souhaitez de gamme, vous devrez à nouveau sélectionner les déclinaisons", "action" => "changeType"];
        }
        else {
            if (isset($forceChange)) {
                if ($forceChange == 'false') {
                    $type       = new Category($this->session->id_type, $this->language_id);
                    $products   = $type->getProducts($this->language_id, 1, 100, null, null, false, true);
                    $this->data = $this->getAttributes($products);
                    return true;
                }
                else {
                    $this->session->change('id_type', $type->id);
                    $this->session->default($this->language_id, $this, $products);
                }
            }
            $this->session->deleteEntryByType('main');
            $this->session->deleteEntryByType('accessory');
            $this->session->change('id_type', $type->id);
            $this->session->change('id_product_attribute', $this->getIdProductAttribute($products, $this->session->height, $this->session->color));

            $this->data = $this->getAttributes($products);
            $this->data['poteaux'] = null;
            if ($this->session->isRigide()) {
                $category       = new Category(self::POTEAUX_RIGIDE_ID, $this->language_id);
                $products   = $category->getProducts($this->language_id, 1, 100, null, null, false, true);
                foreach ($products as $product) {
                    if ($product['id_product'] == 1285) {
                        continue;
                    }
                    $this->data['poteaux'][] = [
                        "value"   => $product['id_product'],
                        "name" => strpos($product['name'], 'clip') ? 'Clips' : 'Encoches',
                        "checked" => $this->session->poteaux == $product['id_product']
                    ];
                }
            }
            // Get price of Grillage and set products for displaying
            $grillage = Grillage::get($this->session, $this->language_id, $type);

            $this->session->insertEntry([
                'id_configurator_session' => $this->session->id,
                'id_product'              => $grillage['id'],
                'id_product_attribute'    => $this->session->id_product_attribute,
                'quantity'                => $grillage['quantity'],
                'type'                    => 'main'
            ]);
            // Same for accessories
            $accessories = Accessory::get($this->session, $this->language_id, false);

            foreach ($accessories as $accessory) {
                $this->session->insertEntry([
                    'id_configurator_session' => $this->session->id,
                    'id_product'              => $accessory['id'],
                    'id_product_attribute'    => $accessory['id_attribute'],
                    'quantity'                => $accessory['quantity'],
                    'type'                    => 'accessory'
                ]);
            }
        }

        return true;
    }

    // Update price
    public function processSubmitUpdatePrice()
    {
        $grillage             = Tools::getValue('grillage');
        $espacement           = $grillage['espacement'];
        $type                 = new Category($grillage['type'], $this->language_id);

        $products             = $type->getProducts($this->language_id, 1, 100, null, null, false, true, false, 1, false);
        $id_product_attribute = $this->getIdProductAttribute($products, $grillage['height'], $grillage['color']);

        $id_category   = (int)Tools::getValue('id_category', $type->id);
        $clips = 0;

        if (!isset($grillage['poteaux'])) {
            $grillage['poteaux'] = null;
        }else {
            if ($grillage['installation'] == 'scellement-sur-platine' && in_array($grillage['type'], [self::PROS_ID, self::PROS_PLUS_ID, self::BRICO_PLUS_ID])) {
                $grillage['poteaux'] = 1285;
            }else{
                $product = new Product($grillage['poteaux'], false, $this->language_id);

                if (strpos($product->name, 'clip') !== false) {
                    $clips = 1;
                }
            }
        }

        $heightChanged = $grillage['height'] != $this->session->height;

        $this->session->change(
            ['id_type', 'length', 'installation', 'height', 'color', 'id_product_attribute', 'id_category', 'espacement', 'depart_mur', 'nombre_angle', 'poteaux', 'clips'],
            [$type->id, $grillage['length'], $grillage['installation'], $grillage['height'], $grillage['color'], $id_product_attribute, $id_category, $espacement, $grillage['depart_mur'], $grillage['nombre_angle'], $grillage['poteaux'], $clips]
        );

        $this->session->deleteEntryByType('main');

        $sousbassement = Tools::getValue('sousbassement');
        $kitmontage    = Tools::getValue('kitmontage');
        $portillon     = Tools::getValue('portillon');
        $occultation   = Tools::getValue('occultation');
        $panneau_deco   = Tools::getValue('panneau_deco');

        $grillage = Grillage::get($this->session, $this->language_id);
        $this->addOptions($sousbassement, $occultation, $kitmontage, $portillon, $panneau_deco, $grillage['quantity'], $heightChanged);

        $grillage = Grillage::get($this->session, $this->language_id);


        // Unique parametre a mettre pour enregistré un produit
        $entry = [
            'id_configurator_session' => $this->session->id,
            'id_product'              => $grillage['id'],
            'id_product_attribute'    => $grillage['id_attribute'],
            'quantity'                => $grillage['quantity'],
            'type'                    => 'main'
        ];

        $this->session->insertEntry($entry);

        $this->session->deleteEntryByType('accessory');

        $soubassement = $this->session->has('soubassement') ? $this->session->soubassement()['id_product'] : false;
        $accessories  = Accessory::get($this->session, $this->language_id, $soubassement);

        foreach ($accessories as $accessory) {
            if ($heightChanged && ($accessory['id'] == 1182 || $accessory['id'] == 1213)) {

            }else{
                $this->session->insertEntry([
                    'id_configurator_session' => $this->session->id,
                    'id_product'              => $accessory['id'],
                    'id_product_attribute'    => $accessory['id_attribute'],
                    'quantity'                => $accessory['quantity'],
                    'type'                    => 'accessory'
                ]);
            }
        }

        $productsAll = $this->session->getProducts($this->language_id, $this->session);

        if(!empty($productsAll[0]["id"]) && !empty($this->session->length) && class_exists('\Dealt\DealtHookHelper')){
            $this->session->deleteEntryByType('service');
            $service = \Dealt\DealtHookHelper::buildProductAdditionalInfoData($this->context, (int)$productsAll[0]["id"], (int)($this->session->length));

            if(!empty($service)){
                if(!empty($service['dealt_service_product_id'])){
                    $serviceId = $service['dealt_service_product_id'];
                    $serviceTitleSQL = Db::getInstance()->executeS('SELECT pl.name FROM `' . _DB_PREFIX_ . 'product_lang` pl
                            WHERE pl.`id_product` = ' . $serviceId);
                    $serviceTitle = $serviceTitleSQL[0]['name'];
                } else {
                    $serviceTitle = null;
                }
            } else {
            }
        } else {
            $service = null;
            $serviceTitle = null;
            $this->session->deleteEntryByType('service');
        }

        $this->context->smarty->assign([
            "services"      => $service,
            'serviceTitle'  => $serviceTitle,
        ]);

        return true;
    }

    public function addServiceEntry($service_id){
        $ifExist = Db::getInstance()->executeS("SELECT * FROM `"._DB_PREFIX_."configurator_session_entries` 
        WHERE `id_configurator_session` = '".$this->session->id."' AND `id_product` = '".$service_id."'");
        if(!$ifExist){
            $this->session->insertEntry([
                'id_configurator_session' => $this->session->id,
                'id_product'              => $service_id,
                'id_product_attribute'    => 0,
                'quantity'                => 1,
                'type'                    => 'service'
            ]);
        }
    }

    public function processSubmitUpdateQuantity()
    {
        $action     = Tools::getValue('action');
        $id_product = (int)Tools::getValue('id');

        $this->session->updateProductQuantityEntry($id_product, $action);
        return true;
    }

    public function processSubmit()
    {
        $Submit = new Submit($this->userKey());
        return $Submit->submit();
    }

    public function getAttributes($products)
    {

        $heights = [];
        $colors  = [];

        if (count($products) > 0) {
            foreach ($products as $product) {
                $combinations = static::getAttributesInformationsByProduct($product['id_product']);

                foreach ($combinations as $combination) {
                    $combination['attribute'] = str_replace(' ', '-', strtolower($combination['attribute']));
                    switch (strtolower($combination['group'])) {
                        case 'hauteur':
                            $height = explode('-', $combination['attribute']);
                            $height = array_key_exists(1, $height) ? $height[1] : $height[0];

                            if (Helper::valueExists($combination['attribute'], $heights, "value") == false) {
                                $heights[] = [
                                    "value"                => $combination['attribute'],
                                    "name"                 => $height,
                                    "id_product_attribute" => $combination['id_product_attribute'],
                                    "checked"              => $this->session->height == $combination['attribute']
                                ];
                            }
                            break;
                        case 'hauteur-du-poteau':
                            $height = str_replace('m', '.', $combination['attribute']);
                            if (Helper::valueExists($combination['attribute'], $heights, "value") == false) {
                                $heights[] = [
                                    "value"                => $combination['attribute'],
                                    "name"                 => $height,
                                    "id_product_attribute" => $combination['id_product_attribute'],
                                    "checked"              => $this->session->height == $combination['attribute']
                                ];
                            }
                            break;
                        case 'couleur':
                            $color = explode('-', $combination['attribute']);
                            $color = $color[0];
                            if (Helper::valueExists($combination['attribute'], $colors, "value") == false) {
                                $colors[] = [
                                    "value"                => $combination['attribute'],
                                    "name"                 => $color,
                                    "id_product_attribute" => $combination['id_product_attribute'],
                                    "checked"              => $this->session->color == $combination['attribute']
                                ];
                            }
                            break;
                    }
                }
            }
        }

        if ($this->session->isAluminium()) {
            $hauteurs = self::STEPS_HIGHT_ALU;
            if ($this->session->id_type == self::LAMES_PLEINES_ID) {
                $hauteurs = self::STEPS_HIGHT_ALU_LAMES_PLEINES;
            }

            $heights = [];

            foreach ($hauteurs as $step) {
                $heights[] = [
                    'name' => $step,
                    'value' => $step,
                    'checked' => $this->session->height == $step
                ];
            }
        }

        return ["heights" => $heights, "colors" => $colors];
    }

    // Getting id_product_attribute with height, color and products_id
    public function getIdProductAttribute($products, $height, $color)
    {
        $id_product_attribute = null;
        $ids_color            = [];
        $ids_height           = [];

        if (count($products) > 0) {
            foreach ($products as $product) {
                $combinations = static::getAttributesInformationsByProduct($product['id_product']);


                foreach ($combinations as $combination) {
                    $attribute = str_replace(' ', '-', strtolower($combination['attribute']));
                    if ($attribute == $color) {
                        $ids_color[] = $combination['id_product_attribute'];
                    }

                    if ($attribute == $height) {
                        $ids_height[] = $combination['id_product_attribute'];
                    }
                }
            }
        }


        $result = array_intersect($ids_color, $ids_height);
        if (count($result) > 0) {
            foreach ($result as $id_product_attribute) {
                return $id_product_attribute;
            }
        }

        return $id_product_attribute;
    }

    public static function getAttributesInformationsByProduct($id_product)
    {
        $result = Db::getInstance()->executeS('
            SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`, pac.`id_product_attribute`
            FROM `'._DB_PREFIX_.'attribute` a
            LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
                ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)Context::getContext()->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
                ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)Context::getContext()->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
                ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
            '.Shop::addSqlAssociation('product_attribute', 'pa').'
            '.Shop::addSqlAssociation('attribute', 'pac').'
            WHERE pa.`id_product` = '.(int)$id_product);

        return $result;
    }

    // Add selected options
    public function addOptions($sousbassement, $occultation, $kitmontage, $portillon, $panneau_deco, $nbGrillage, $heightChanged)
    {
        $this->session->deleteEntryByType('soubassement');
        $this->session->deleteEntryByType('kitmontage');
        $this->session->deleteEntryByType('occultation');
        $this->session->deleteEntryByType('portillon');
        $this->session->deleteEntryByType('panneau_deco');


        // Soubassement
        // Vérifier d'abord si l'entrée soubassement existante est encore compatible avec le type de poteau actuel
        $existing_soubassement = $this->session->soubassement();
        if ($existing_soubassement) {
            $clips = isset($this->session->clips) ? (int)$this->session->clips : null;
            if ($clips !== null && !Soubassement::matchesPoleType($existing_soubassement['id_product'], $existing_soubassement['id_product_attribute'], $clips, $this->language_id)) {
                $this->session->deleteEntryByType('soubassement');
            }
        }
        
        if (isset($sousbassement['active']) && $sousbassement['active'] == 'true' && !empty($sousbassement['color']) && !empty($sousbassement['height'])) {
            if (is_numeric($sousbassement['color'])) {
                $id_product_attribute = $sousbassement['color'];
            } else {
                $id_product_attribute = Soubassement::getType($sousbassement['height'], $this->session, $this->language_id);
            }
            
            if ($id_product_attribute) {
                $this->session->insertEntry([
                    'id_configurator_session' => $this->session->id,
                    'id_product'              => $sousbassement['height'],
                    'id_product_attribute'    => $id_product_attribute,
                    'quantity'                => $nbGrillage,
                    'type'                    => 'soubassement'
                ]);
            }
        } else if (isset($sousbassement['active']) && $sousbassement['active'] == 'false') {
            $this->session->deleteEntryByType('soubassement');
        }

        if ($kitmontage && $kitmontage == 'true') {
            $kit = KitMontage::getIdKit($this->language_id, $this->session->installation);
            $this->session->insertEntry([
                'id_configurator_session' => $this->session->id,
                'id_product'              => $kit['product'],
                'id_product_attribute'    => $kit['attribute'],
                'quantity'                => 1,
                'type'                    => 'kitmontage'
            ]);
        }

        // Occultations
        if (isset($occultation['active']) && $occultation['active'] == 'true' && !empty($occultation['id']) && !empty($occultation['id_product_attribute'])) {
            $this->session->insertEntry([
                'id_configurator_session' => $this->session->id,
                'id_product'              => $occultation['id'],
                'id_product_attribute'    => $occultation['id_product_attribute'],
                'quantity'                => ($occultation['id'] == 239) ? ceil($this->session->length / 10) : $nbGrillage, // Brise vue STANDARD : autre
                'type'                    => 'occultation'
            ]);
        }

        // Panneaux déco
        if (isset($panneau_deco['active']) && $panneau_deco['active'] == 'true' && !empty($panneau_deco['id']) && !empty($panneau_deco['id_product_attribute'])) {
            $length = $this->session->length;
            $height                = (float)str_replace("m", ".", $this->session->height);
            $quantities = ($this->session->id_type == self::LAMES_PLEINES_ID)
                ? self::ALU_QUANTITIES_PLEINES[$this->session->installation][(string)$height]
                : self::ALU_QUANTITIES_AJOURES[$this->session->installation][(string)$height];

            $lengthKeys = array_keys($quantities);
            $position = array_search($length, $lengthKeys);
            $coefficient = 1;
            if ($position !== false) {
                $coefficient = $position + 1;
            }
            $this->session->insertEntry([
                'id_configurator_session' => $this->session->id,
                'id_product'              => $panneau_deco['id'],
                'id_product_attribute'    => $panneau_deco['id_product_attribute'],
                'quantity'                => $coefficient,
                'type'                    => 'panneau_deco'
            ]);
        }else if ($this->session->panneau_deco()) {
            $this->session->deleteEntryByType('panneau_deco');
        }

        $portillon_active = isset($portillon['active']) && ($portillon['active'] == 'true' || $portillon['active'] === true || $portillon['active'] == 1);
        if ($portillon_active && !empty($portillon['id']) && !empty($portillon['id_product_attribute']) && !$heightChanged) {
            $this->session->deleteEntryByType('portillon');
            $this->session->insertEntry([
                'id_configurator_session' => $this->session->id,
                'id_product'              => $portillon['id'],
                'id_product_attribute'    => $portillon['id_product_attribute'],
                'quantity'                => 1,
                'type'                    => 'portillon'
            ]);
        } else if (!$portillon_active && $this->session->portillon()) {
            $this->session->deleteEntryByType('portillon');
        }
    }

    private function userKey()
    {
        return md5($_SERVER['REMOTE_ADDR']);
    }

    private function getDisplay($template)
    {
        return $this->setTemplate('module:ckc_configurator/views/templates/front/' . $template);
    }

    private function getHtml($template)
    {
        $this->setTemplate('module:ckc_configurator/views/templates/front/' . $template);

        ob_start();
        $this->display();
        $output = ob_get_contents();
        ob_clean();

        return $output;
    }
}
