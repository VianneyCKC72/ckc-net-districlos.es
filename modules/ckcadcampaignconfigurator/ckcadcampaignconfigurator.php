<?php
/**
 * 2007-2025 PrestaShop
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/helpers/CampaignFormHelper.php';
require_once __DIR__ . '/models/CampaignModel.php';

class Ckcadcampaignconfigurator extends Module
{
    protected $config_form = false;
    protected $_postErrors = [];
    protected $_output = '';

    public function __construct()
    {
        $this->name = 'ckcadcampaignconfigurator';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'ckc-net';
        $this->need_instance = 0;
        $this->ps_version  = (bool)version_compare(_PS_VERSION_, '1.7', '>=');

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Configurateur des campagnes publicitaires');
        $this->description = $this->l('Configurateur des campagnes publicitaires');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        require_once __DIR__ . '/sql/install.php'; // sql querries

        return parent::install() &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayAfterCartDetailedActions') &&
            $this->registerHook('displayPromoProductDescription') &&
            $this->registerHook('displayPromoCategoryDescription') &&
            $this->registerHook('displayPromoMenuImage') &&
            $this->registerHook('displayPromoDevisText') &&
            $this->registerHook('displayPromoPageText') &&
            $this->registerHook('displayPromoImpactFranco') &&
            $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        require_once __DIR__ . '/sql/uninstall.php'; // sql querries

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitCkcadcampaignconfiguratorModule')) == true) {
            $this->postProcess();
        } elseif (((bool)Tools::isSubmit('create_new_campaign')) == true) {

            $page = Tools::getValue('page');
            if (!empty($page)) {
                $currentPage = Tools::getValue('page');
            }
            else $currentPage = 'menuContent';

            $formMain = $this->renderFormMain('main');
            $formMenu = $this->renderFormMain('menu');
            $formHeader = $this->renderFormMain('header');
            $formCategory = $this->renderFormMain('category');
            $formDevis = $this->renderFormMain('devis');
            $formPromoPage = $this->renderFormMain('promopage');

            $allCategories = Db::getInstance()->executeS('SELECT `id_category`, `name` FROM `' . _DB_PREFIX_ . 'category_lang` 
            WHERE id_lang = '.Context::getContext()->language->id.' AND id_shop = '.Context::getContext()->shop->id);

            $selectedClients = "";

            $this->context->smarty->assign(array(
                'idCampaign' => 0,
                'form_main' => $formMain,
                'formMenu' => $formMenu,
                'formHeader' => $formHeader,
                'formCategory' => $formCategory,
                'formDevis' => $formDevis,
                'formPromoPage' => $formPromoPage,
                'cartPromos' => null,
                'productPromos' => null,
                'categorySelection' => (int)0,
                'allCategories' => json_encode($allCategories),
                'selectedClients' => $selectedClients,
                'queryCategories' => (int)0,
                'current_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'isPs17'      => $this->ps_version,
                'currentPage' => $currentPage,
                'ps_base_dir' => Tools::getHttpHost(true),
                'module_name' => $this->name,
                'moduleAdminLink' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'modulePath' => $this->context->link->getAdminLink('AdminAjaxCkcadcampaignconfigurator'),
            ));

            $this->_output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/create_edit_campaign.tpl');

        } elseif (((bool)Tools::isSubmit('copy_campaign')) == true) {
            $idCampaign = Tools::getValue('ID');
            $newId = $this->copyCampaign($idCampaign);
            Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcadcampaignconfigurator', 'edit_campaign' => true, 'ID' => $newId]));

        } elseif (((bool)Tools::isSubmit('edit_campaign')) == true OR ((bool)Tools::isSubmit('ReturnToCampaignPage')) == true) {

            $idCampaign = Tools::getValue('ID');

            $page = Tools::getValue('page');
            if (!empty($page)) {
                $currentPage = Tools::getValue('page');
            }
            else $currentPage = 'menuContent';

            $formMain = $this->renderFormMain('main', $idCampaign);
            $formMenu = $this->renderFormMain('menu', $idCampaign);
            $formHeader = $this->renderFormMain('header', $idCampaign);
            $formCategory = $this->renderFormMain('category', $idCampaign);
            $formDevis = $this->renderFormMain('devis', $idCampaign);
            $formPromoPage = $this->renderFormMain('promopage', $idCampaign);

            $cartPromos = Db::getInstance()->executeS('SELECT `id`, `text`, `is_fixed`, `active` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_cart` WHERE id_campaign = '.$idCampaign);
            $productPromos = Db::getInstance()->executeS('SELECT `id`, `text`, `date_add`, `active` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` WHERE id_campaign = '.$idCampaign);
            $categoryPromos = Db::getInstance()->executeS('SELECT `id`, `text`, `date_add`, `active` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` WHERE id_campaign = '.$idCampaign);

            $allCategories = Db::getInstance()->executeS('SELECT `id_category`, `name` FROM `' . _DB_PREFIX_ . 'category_lang` 
            WHERE id_lang = '.Context::getContext()->language->id.' AND id_shop = '.Context::getContext()->shop->id);

            /*            $queryCategories = Db::getInstance()->executeS('SELECT DISTINCT cccs.id_category, cl.name FROM ' . _DB_PREFIX_ . 'ckccampaign_config_category_show cccs
                        LEFT JOIN  `' . _DB_PREFIX_ . 'category_lang` cl ON cl.id_category = cccs.id_category
                        WHERE cccs.id_campaign = '.$idCampaign);*/

            $selectedClientsSQL = Db::getInstance()->executeS('SELECT id_client_group FROM ' . _DB_PREFIX_ . 'ckccampaign_config_list WHERE id = '.$idCampaign);
            $selectedClients = $selectedClientsSQL[0]['id_client_group'];

            $categorySelection = Db::getInstance()->executeS('SELECT DISTINCT cccs.id_category, cl.name FROM ' . _DB_PREFIX_ . 'ckccampaign_config_category_show cccs
            LEFT JOIN  `' . _DB_PREFIX_ . 'category_lang` cl ON cl.id_category = cccs.id_category
            WHERE cccs.id_campaign = '.$idCampaign.' AND cl.id_lang = '.Context::getContext()->language->id. ' AND cl.id_shop = '.Context::getContext()->shop->id);

            $this->context->smarty->assign(array(
                'idCampaign' => $idCampaign,
                'form_main' => $formMain,
                'formMenu' => $formMenu,
                'formHeader' => $formHeader,
                'formCategory' => $formCategory,
                'formDevis' => $formDevis,
                'formPromoPage' => $formPromoPage,
                'cartPromos' => $cartPromos,
                'productPromos' => $productPromos,
                'categoryPromos' => $categoryPromos,
                'selectedClients' => $selectedClients,
                'categorySelection' => isset($categorySelection) ? json_encode($categorySelection) : (int)0,
                /*                'queryCategories' => json_encode($queryCategories),*/
                'allCategories' => json_encode($allCategories),
                'current_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'isPs17'      => $this->ps_version,
                'currentPage' => $currentPage,
                'ps_base_dir' => Tools::getHttpHost(true),
                'module_name' => $this->name,
                'moduleAdminLink' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'modulePath' => $this->context->link->getAdminLink('AdminAjaxCkcadcampaignconfigurator'),
            ));

            $this->_output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/create_edit_campaign.tpl');


        } elseif(((bool)Tools::isSubmit('create_edit_new_promo')) == true) {

            $idCampaign = Tools::getValue('campaignId');
            $idPromo = Tools::getValue('promoId');

            $formCart = $this->renderFormMain('cart', $idCampaign, $idPromo);

            $this->context->smarty->assign(array(
                'idCampaign' => $idCampaign,
                'formCart' => $formCart,
                'current_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'isPs17'      => $this->ps_version,
                'ps_base_dir' => Tools::getHttpHost(true),
                'module_name' => $this->name,
                'moduleAdminLink' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
            ));

            $this->_output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/tabs/cartContent.tpl');

        } elseif(((bool)Tools::isSubmit('delete_cart_promo')) == true) {

            $idCampaign = Tools::getValue('campaignId');
            $idPromo = Tools::getValue('promoId');

            $deletePromo = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_cart` WHERE `id` = '.$idPromo.' AND `id_campaign` = ' .$idCampaign);
            // Rafraîchir la page
            Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcadcampaignconfigurator', 'edit_campaign' => true, 'ID' => $idCampaign]));

        } elseif (((bool)Tools::isSubmit('create_edit_new_product_promo')) == true) {
            $idCampaign = Tools::getValue('campaignId');
            $idPromo = Tools::getValue('promoId');

            $formProduct = $this->renderFormMain('product', $idCampaign,$idPromo);

            if($idPromo) {
                $productSelection = Db::getInstance()->executeS('SELECT c.`id_product`, pl.`name` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_product_show` c
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.id_product = c.id_product
                WHERE c.id_product_content = '.$idPromo.' AND pl.id_lang = '.Context::getContext()->language->id. ' AND pl.id_shop = '.Context::getContext()->shop->id);
            } else {
                $productSelection = 0;
            }

            $this->context->smarty->assign(array(
                'idCampaign' => $idCampaign,
                'formProduct' => $formProduct,
                'current_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'isPs17'      => $this->ps_version,
                'ps_base_dir' => Tools::getHttpHost(true),
                'module_name' => $this->name,
                'currentPage' => "",
                'productSelection' => json_encode($productSelection),
                'moduleAdminLink' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'modulePath' => $this->context->link->getAdminLink('AdminAjaxCkcadcampaignconfigurator'),
            ));

            $this->_output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/tabs/productContent.tpl');

        } elseif (((bool)Tools::isSubmit('delete_product_promo')) == true) {

            $idCampaign = Tools::getValue('campaignId');
            $idPromo = Tools::getValue('promoId');

            $deletePromo = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` WHERE `id` = '.$idPromo.' AND `id_campaign` = ' .$idCampaign);
            // Rafraîchir la page
            Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcadcampaignconfigurator', 'edit_campaign' => true, 'ID' => $idCampaign]));

        } elseif (((bool)Tools::isSubmit('saveNewCampaignCart')) == true) {

            $this->postValidation();
            if (!count($this->_postErrors)) {

                $idCartPromo = $this->postProcessSaveCampaignCart();
                // Rafraîchir la page
                $idCampaign = Tools::getValue('ID');
                Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcadcampaignconfigurator', 'create_edit_new_promo' => true, 'campaignId' => $idCampaign, 'promoId' => $idCartPromo]));

            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_output .= $this->displayError($err);
                }

                $idCampaign = Tools::getValue('ID');
                $idPromo = Tools::getValue('CART_ID');

                $formCart = $this->renderFormMain('cart', $idCampaign, $idPromo);

                $this->context->smarty->assign(array(
                    'idCampaign' => $idCampaign,
                    'formCart' => $formCart,
                    'current_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                    'isPs17'      => $this->ps_version,
                    'ps_base_dir' => Tools::getHttpHost(true),
                    'module_name' => $this->name,
                    'moduleAdminLink' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                ));

                $this->_output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/tabs/cartContent.tpl');
            }

        } elseif (((bool)Tools::isSubmit('saveNewCampaignProduct')) == true) {
            $this->postValidation();
            if (!count($this->_postErrors)) {
                $idPromo = $this->postProcessSaveCampaignProduct();

                $idCampaign = Tools::getValue('ID');
                Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcadcampaignconfigurator', 'create_edit_new_product_promo' => true, 'campaignId' => $idCampaign, 'promoId' => $idPromo]));
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_output .= $this->displayError($err);
                }
                $idCampaign = Tools::getValue('ID');
                $idPromo = Tools::getValue('promoId');

                $formProduct = $this->renderFormMain('product', $idCampaign, $idPromo);

                if($idPromo) {
                    $productSelection = Db::getInstance()->executeS('SELECT c.`id_product`, pl.`name` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_product_show` c
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.id_product = c.id_product
                    WHERE c.id_product_content = '.$idPromo.' AND pl.id_lang = '.Context::getContext()->language->id. ' AND pl.id_shop = '.Context::getContext()->shop->id);
                } else {
                    $productSelection = 0;
                }

                $this->context->smarty->assign(array(
                    'idCampaign' => $idCampaign,
                    'formProduct' => $formProduct,
                    'current_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                    'isPs17'      => $this->ps_version,
                    'ps_base_dir' => Tools::getHttpHost(true),
                    'module_name' => $this->name,
                    'productSelection' => json_encode($productSelection),
                    'moduleAdminLink' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                    'modulePath' => $this->context->link->getAdminLink('AdminAjaxCkcadcampaignconfigurator'),
                ));

                $this->_output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/tabs/productContent.tpl');
            }

        } elseif (((bool)Tools::isSubmit('ReturnToCampaignList')) == true) {
            Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcadcampaignconfigurator']));
        } elseif (((bool)Tools::isSubmit('deleteCampaignImage')) == true) {
            $idCampaign = Tools::getValue('ID');

            if(isset($idCampaign)){
                $imageDelete = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_list` 
                SET `logo_path` = NULL
                WHERE  `id` = ' .(int)$idCampaign);

                Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcadcampaignconfigurator', 'edit_campaign' => true, 'ID' => $idCampaign]));
            }
        } elseif (((bool)Tools::isSubmit('create_edit_new_category_promo')) == true) {

            $idCampaign = Tools::getValue('campaignId');
            $idPromo = Tools::getValue('promoId');

            $formCategory = $this->renderFormMain('category', $idCampaign ,$idPromo);

            $allCategories = Db::getInstance()->executeS('SELECT `id_category`, `name` FROM `' . _DB_PREFIX_ . 'category_lang` 
            WHERE id_lang = '.Context::getContext()->language->id.' AND id_shop = '.Context::getContext()->shop->id);

            if($idPromo) {
                $categorySelection = Db::getInstance()->executeS('SELECT DISTINCT cccs.id_category, cl.name FROM ' . _DB_PREFIX_ . 'ckccampaign_config_category_show cccs
                LEFT JOIN  `' . _DB_PREFIX_ . 'category_lang` cl ON cl.id_category = cccs.id_category
                WHERE cccs.id_campaign = '.$idPromo.' AND cl.id_lang = '.Context::getContext()->language->id. ' AND cl.id_shop = '.Context::getContext()->shop->id);
            } else {
                $categorySelection = 0;
            }

            $this->context->smarty->assign(array(
                'idCampaign' => $idCampaign,
                'formCategory'=> $formCategory,
                'current_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'isPs17'      => $this->ps_version,
                'ps_base_dir' => Tools::getHttpHost(true),
                'categorySelection' => isset($categorySelection) ? json_encode($categorySelection) : (int)0,
                'allCategories' => json_encode($allCategories),
                'module_name' => $this->name,
                'currentPage' => "",
                'moduleAdminLink' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'modulePath' => $this->context->link->getAdminLink('AdminAjaxCkcadcampaignconfigurator'),
            ));

            $this->_output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/tabs/categoryContent.tpl');

        } elseif (((bool)Tools::isSubmit('saveNewCampaignCategory')) == true) {
            $this->postValidation();
            if (!count($this->_postErrors)) {
                $idPromo = $this->postProcessSaveCampaignCategory();

                $idCampaign = Tools::getValue('ID');
                Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcadcampaignconfigurator', 'create_edit_new_category_promo' => true, 'campaignId' => $idCampaign, 'promoId' => $idPromo]));
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_output .= $this->displayError($err);
                }

                $idCampaign = Tools::getValue('ID');
                $idPromo = Tools::getValue('CATEGORY_ID');

                $formCategory = $this->renderFormMain('category', $idCampaign ,$idPromo);

                $allCategories = Db::getInstance()->executeS('SELECT `id_category`, `name` FROM `' . _DB_PREFIX_ . 'category_lang` 
                WHERE id_lang = '.Context::getContext()->language->id.' AND id_shop = '.Context::getContext()->shop->id);

                if($idPromo) {
                    $categorySelection = Db::getInstance()->executeS('SELECT DISTINCT cccs.id_category, cl.name FROM ' . _DB_PREFIX_ . 'ckccampaign_config_category_show cccs
                    LEFT JOIN  `' . _DB_PREFIX_ . 'category_lang` cl ON cl.id_category = cccs.id_category
                    WHERE cccs.id_campaign = '.$idPromo.' AND cl.id_lang = '.Context::getContext()->language->id. ' AND cl.id_shop = '.Context::getContext()->shop->id);
                } else {
                    $categorySelection = 0;
                }

                $this->context->smarty->assign(array(
                    'idCampaign' => $idCampaign,
                    'formCategory'=> $formCategory,
                    'current_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                    'isPs17'      => $this->ps_version,
                    'ps_base_dir' => Tools::getHttpHost(true),
                    'categorySelection' => isset($categorySelection) ? json_encode($categorySelection) : (int)0,
                    'allCategories' => json_encode($allCategories),
                    'module_name' => $this->name,
                    'currentPage' => "",
                    'moduleAdminLink' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                    'modulePath' => $this->context->link->getAdminLink('AdminAjaxCkcadcampaignconfigurator'),
                ));

                $this->_output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/tabs/categoryContent.tpl');

            }

        } elseif (((bool)Tools::isSubmit('delete_category_promo')) == true) {

            $idCampaign = Tools::getValue('campaignId');
            $idPromo = Tools::getValue('promoId');

            $deletePromo = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` WHERE `id` = '.$idPromo.' AND `id_campaign` = ' .$idCampaign);
            // Rafraîchir la page
            Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcadcampaignconfigurator', 'edit_campaign' => true, 'ID' => $idCampaign]));

        } elseif (((bool)Tools::isSubmit('delete_campaign')) == true) {
            $idCampaign = Tools::getValue('ID');
            if(isset($idCampaign) && !empty($idCampaign)) {
                $this->deleteCampaign($idCampaign);
            }
            Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcadcampaignconfigurator']));
        } else {
            $campaigns = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "ckccampaign_config_list");

            $this->context->smarty->assign(array(
                'moduleAdminLink' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'module_dir' => $this->_path,
                'campaigns' => $campaigns,
                'current_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
            ));


            $this->_output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        }

        return $this->_output;
    }

    public function createNewCampaign(){
        $formHelper = new campaignFormHelper;
        $table = $this->table;
        $identifier = $this->identifier;
        return $formHelper->getContent($table,$identifier);
    }

    protected function postValidation()
    {
        if (Tools::isSubmit('saveNewCampaignCart')) {

            if (Tools::getValue('CART_FIXED_DISCOUNT') !== "" && !is_numeric(Tools::getValue('CART_FIXED_DISCOUNT'))) {
                if(Tools::getValue('CART_FIXED_DISCOUNT') !== "0.00"){
                    $this->_postErrors[] = $this->trans(
                        'Discount value must be a number.',
                        [],
                        'Modules.ckcadcampaignconfigurator.Admin'
                    );
                }
            } elseif (Tools::getValue('CART_FIXED_PRICE') !== "" && !is_numeric(Tools::getValue('CART_FIXED_PRICE'))) {
                if(Tools::getValue('CART_FIXED_PRICE') !== "0.00") {
                    $this->_postErrors[] = $this->trans(
                        'Discount price CART_FIXED_PRICE must be a number.',
                        [],
                        'Modules.ckcadcampaignconfigurator.Admin'
                    );
                }
            } elseif (Tools::getValue('CART_RANGED_DISCOUNT') !== "" && !is_numeric(Tools::getValue('CART_RANGED_DISCOUNT'))) {
                if(Tools::getValue('CART_RANGED_DISCOUNT') !== "0.00") {
                    $this->_postErrors[] = $this->trans(
                        'Discount price CART_RANGED_DISCOUNT must be a number.',
                        [],
                        'Modules.ckcadcampaignconfigurator.Admin'
                    );
                }
            } elseif (Tools::getValue('CART_RANGED_DISCOUNT_MAX') !== "" && !is_numeric(Tools::getValue('CART_RANGED_DISCOUNT_MAX'))) {
                if(Tools::getValue('CART_RANGED_DISCOUNT_MAX') !== "0.00") {
                    $this->_postErrors[] = $this->trans(
                        'Discount price CART_RANGED_DISCOUNT_MAX must be a number.',
                        [],
                        'Modules.ckcadcampaignconfigurator.Admin'
                    );
                }
            }
            if (Tools::getValue('CART_RANGED_PRICE_MIN') !== "" && !is_numeric(Tools::getValue('CART_RANGED_PRICE_MIN'))) {
                if(Tools::getValue('CART_RANGED_PRICE_MIN') !== "0.00") {
                    $this->_postErrors[] = $this->trans(
                        'Discount value CART_RANGED_PRICE_MIN must be a number.',
                        [],
                        'Modules.ckcadcampaignconfigurator.Admin'
                    );
                }
            } elseif (Tools::getValue('CART_RANGED_PRICE_MAX') !== "" && !is_numeric(Tools::getValue('CART_RANGED_PRICE_MAX'))) {
                if(Tools::getValue('CART_RANGED_PRICE_MAX') !== "0.00") {
                    $this->_postErrors[] = $this->trans(
                        'Discount value CART_RANGED_PRICE_MAX must be a number.',
                        [],
                        'Modules.ckcadcampaignconfigurator.Admin'
                    );
                }
            }
        }

        if (Tools::isSubmit('saveNewCampaignProduct')) {
            if (!Tools::getValue('PRODUCT_TEXT')) {
                $this->_postErrors[] = $this->trans(
                    'This field is required.',
                    [],
                    'Modules.ckcadcampaignconfigurator.Admin'
                );
            }
        }

        if (Tools::isSubmit('saveNewCampaignCategory')) {
            if (!Tools::getValue('CATEGORY_TEXT')) {
                $this->_postErrors[] = $this->trans(
                    'This field is required.',
                    [],
                    'Modules.ckcadcampaignconfigurator.Admin'
                );
            }
        }
    }

    protected function postProcessSaveCampaignCart(){

        $type = Tools::getValue('CART_TYPE') == "" ? 0 : 1;
        $cartId = Tools::getValue('CART_ID') == "" ? null : Tools::getValue('CART_ID');
        $CART_ACTIVE = Tools::getValue('CART_ACTIVE') == "" ? 0 : 1;
        $CART_FIXED_DISCOUNT = Tools::getValue('CART_FIXED_DISCOUNT') == "" ? 0 : Tools::getValue('CART_FIXED_DISCOUNT');
        $CART_FIXED_PRICE = Tools::getValue('CART_FIXED_PRICE') == "" ? 0 : Tools::getValue('CART_FIXED_PRICE');
        $CART_RANGED_DISCOUNT = Tools::getValue('CART_RANGED_DISCOUNT') == "" ? 0 : Tools::getValue('CART_RANGED_DISCOUNT');
        $CART_RANGED_DISCOUNT_MAX = Tools::getValue('CART_RANGED_DISCOUNT_MAX') == "" ? 0 : Tools::getValue('CART_RANGED_DISCOUNT_MAX');
        $CART_RANGED_PRICE_MIN = Tools::getValue('CART_RANGED_PRICE_MIN') == "" ? 0 : Tools::getValue('CART_RANGED_PRICE_MIN');
        $CART_RANGED_PRICE_MAX = Tools::getValue('CART_RANGED_PRICE_MAX') == "" ? 0 : Tools::getValue('CART_RANGED_PRICE_MAX');
        $CART_TEXT_COLOR = Tools::getValue('CART_TEXT_COLOR') == "" ? "" : Tools::getValue('CART_TEXT_COLOR');
        $CART_TEXT = Tools::getValue('CART_TEXT') == "" ? null : htmlentities(Tools::getValue('CART_TEXT'));

        $data = array(
            'ID' => Tools::getValue('ID'),
            'CART_ID' => $cartId,
            'CART_ACTIVE' => $CART_ACTIVE,
            'CART_TYPE' => $type,
            'CART_FIXED_DISCOUNT' => $CART_FIXED_DISCOUNT,
            'CART_FIXED_PRICE' => $CART_FIXED_PRICE,
            'CART_RANGED_DISCOUNT' => $CART_RANGED_DISCOUNT,
            'CART_RANGED_DISCOUNT_MAX' => $CART_RANGED_DISCOUNT_MAX,
            'CART_RANGED_PRICE_MIN' => $CART_RANGED_PRICE_MIN,
            'CART_RANGED_PRICE_MAX' => $CART_RANGED_PRICE_MAX,
            "CART_TEXT_COLOR" => $CART_TEXT_COLOR,
            'CART_TEXT' => $CART_TEXT,
        );

        $campaignModel = new CampaignModel;
        $campaignInfo = $campaignModel->saveCartPromo($data);
        return $campaignInfo;
    }

    protected function postProcessSaveCampaignProduct(){
        $PRODUCT_ID = Tools::getValue('PRODUCT_ID') == "" ? null : Tools::getValue('PRODUCT_ID');
        $PRODUCT_ACTIVE = Tools::getValue('PRODUCT_ACTIVE') == "" ? 0 : 1;
        $PRODUCT_SHOW_ALL = Tools::getValue('PRODUCT_SHOW_ALL') == "" ? 0 : 1;

        $data = array(
            'ID' => Tools::getValue('ID'),
            'PRODUCT_ID' => $PRODUCT_ID,
            'PRODUCT_ACTIVE' => $PRODUCT_ACTIVE,
            'PRODUCT_SHOW_ALL' => $PRODUCT_SHOW_ALL,
            'PRODUCT_TEXT' => htmlentities(str_replace("'", "\'", Tools::getValue('PRODUCT_TEXT'))),
            'PRODUCT_SELECTION' => Tools::getValue('PRODUCT_SELECTION')
        );

        $campaignModel = new CampaignModel;
        $campaignInfo = $campaignModel->saveProductPromo($data);
        return $campaignInfo;
    }

    protected function postProcessSaveCampaignCategory(){
        $CATEGORY_ID = Tools::getValue('CATEGORY_ID') == "" ? null : Tools::getValue('CATEGORY_ID');
        $CATEGORY_ACTIVE = Tools::getValue('CATEGORY_ACTIVE') == "" ? 0 : 1;
        $CATEGORY_SHOW_ALL = Tools::getValue('CATEGORY_SHOW_ALL') == "" ? 0 : 1;

        $data = array(
            'ID' => Tools::getValue('ID'),
            'CATEGORY_ID' => $CATEGORY_ID,
            'CATEGORY_ACTIVE' => $CATEGORY_ACTIVE,
            'CATEGORY_SHOW_ALL' => $CATEGORY_SHOW_ALL,
            'CATEGORY_TEXT' => htmlentities(str_replace("'", "\'", Tools::getValue('CATEGORY_TEXT'))),
            'CATEGORY_LIST' => Tools::getValue('CATEGORY_LIST'),
            'CATEGORY_BACKGROUND_COLOR' => Tools::getValue('CATEGORY_BACKGROUND_COLOR'),
        );

        // dump($data);
        $campaignModel = new CampaignModel;
        $campaignInfo = $campaignModel->saveCategoryPromo($data);
        return $campaignInfo;
    }

    public function renderFormMain($typeForm, $id = null, $idElement = null)
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;

        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        if($typeForm !== 'cart' && $typeForm !== 'product' && $typeForm !== 'category') {
            $helper->tpl_vars = array(
                'fields_value' => $this->getConfigFormValuesMain($id), /* Add values for your inputs */
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id,
            );
        }

        if ($typeForm == 'main'){
            $helper->submit_action = 'saveNewCampaign';
            return $helper->generateForm(array($this->getConfigFormMain($id)));

        } elseif ($typeForm == 'menu') {
            $helper->submit_action = 'saveNewCampaignMenu';
            return $helper->generateForm(array($this->getConfigFormMenuContent($id)));

        } elseif ($typeForm == 'header') {
            $helper->submit_action = 'saveNewCampaignHeader';
            return $helper->generateForm(array($this->getConfigFormHeaderContent()));

        } elseif ($typeForm == 'cart'){
            $helper->tpl_vars = array(
                'fields_value' => $this->getConfigFormValuesCart($id, $idElement), /* Add values for your inputs */
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id,
            );
            $helper->submit_action = 'saveNewCampaignCart';
            return $helper->generateForm(array($this->getConfigFormCartContent()));
        } elseif ($typeForm == 'category'){
            $helper->tpl_vars = array(
                'fields_value' => $this->getConfigFormValuesCategory($id, $idElement), /* Add values for your inputs */
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id,
            );

            $helper->submit_action = 'saveNewCampaignCategory';
            return $helper->generateForm(array($this->getConfigFormCategoryContent()));
        } elseif ($typeForm == 'product'){
            $helper->tpl_vars = array(
                'fields_value' => $this->getConfigFormValuesProduct($id, $idElement), /* Add values for your inputs */
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id,
            );
            $helper->submit_action = 'saveNewCampaignProduct';
            return $helper->generateForm(array($this->getConfigFormProductContent()));
        } elseif ($typeForm == 'devis'){
            $helper->submit_action = 'saveNewCampaignDevis';
            return $helper->generateForm(array($this->getConfigFormDevisContent()));
        } elseif ($typeForm == 'promopage'){
            $helper->submit_action = 'saveNewCampaignDevis';
            return $helper->generateForm(array($this->getConfigFormPromoPageContent()));
        }

    }

    protected function getConfigFormMain($id = null)
    {

        $clientGroupSelect = \Db::getInstance()->ExecuteS('SELECT `id_group`, `name` FROM `' . _DB_PREFIX_ . 'group_lang`');

        if(isset($id)) {
            $background_image = Db::getInstance()->executeS('SELECT logo_path FROM ' . _DB_PREFIX_ . 'ckccampaign_config_list WHERE id = '.$id);
            $image_url = isset($background_image[0]["logo_path"]) ? _MODULE_DIR_.'ckcadcampaignconfigurator'. DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'campaigns'. DIRECTORY_SEPARATOR.$background_image[0]["logo_path"] : '';
            $image = '<div class="col-lg-6"><img src="' . $image_url . '" class="img-thumbnail"></div>';
        } else {
            $image = '';
        }

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Campaign Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'buttons' => array(
                    'Return'=> array(
                        'type' => 'submit',
                        'name' => 'ReturnToCampaignList',
                        'title' => $this->l('Return'),
                        'class' => 'btn btn-default pull-left',
                        'id'   => 'mybutton',
                        'icon' => 'icon-foo',
                    ),
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Is Active'),
                        'name' => 'ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Is the campaign enabled'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a campaign title'),
                        'name' => 'TITLE',
                        'label' => $this->l('Campaign title'),
                    ),
                    array(
                        'type'    => 'checkbox',
                        'label'   => $this->l('Select an ID of a client group'),
                        'name'    => 'CLIENT_GROUP',
                        'class' => 'selectionClientGroup',
                        'values' => [
                            'query' => $clientGroupSelect,
                            'id'    => 'id_group',
                            'name'  => 'name',
                        ],
                    ),
                    array(
                        'name' => 'DATE_START',
                        'label' => $this->l('Starting Date'),
                        'desc' => $this->l('Starting Date of the campaign'),
                        'type' => 'datetime',
                    ),
                    array(
                        'name' => 'DATE_END',
                        'label' => $this->l('End Date'),
                        'desc' => $this->l('End Date of the campaign'),
                        'type' => 'datetime',
                    ),
                    array(
                        'type' => 'file',
                        'label' => $this->l('Campaign Logo'),
                        'name' => 'LOGO_PATH',
                        // 'desc' => $this->module->l('Maximum image size: ') . $this->module->upload_file_size_limit_in_mb . ' MB.',
                        'desc' => $this->l('Maximum image size: '),
                        'display_image' => true,
                        'image' => $image  // Afficher l'image
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'FRANCO_IMPACT',
                        'label' => $this->l('Saisissez le franco en €'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'FRANCO_IMPACT_PRO',
                        'label' => $this->l('Saisissez le franco Pro en €'),
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'ID',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormMenuContent($id = null)
    {

        if(isset($id)) {
            $background_image = Db::getInstance()->executeS('SELECT img_path FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_menu WHERE id_campaign = '.$id);
            $image_url = isset($background_image[0]["img_path"]) ? _MODULE_DIR_.'ckcadcampaignconfigurator'. DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'menus'. DIRECTORY_SEPARATOR.$background_image[0]["img_path"] : '';
            $image = '<div class="col-lg-6"><img src="' . $image_url . '" class="img-thumbnail"></div>';
        } else {
            $image = '';
        }

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Menu Content Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Is Active'),
                        'name' => 'MENU_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Is the campaign enabled'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'file',
                        'label' => $this->l('Menu Image'),
                        'name' => 'MENU_IMG',
                        // 'desc' => $this->l('Maximum image size: ') . $this->module->upload_file_size_limit_in_mb . ' MB.',
                        'desc' => $this->l('Maximum image size: '),
                        'display_image' => true,
                        'image' => $image  // Afficher l'image
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a link for a promo image'),
                        'name' => 'MENU_LINK',
                        'label' => $this->l('Image link'),
                    ),
                ),
                // 'submit' => array(
                //     'title' => $this->l('Save'),
                // ),
            ),
        );
    }

    protected function getConfigFormHeaderContent()
    {

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Header Content Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Is Active'),
                        'name' => 'HEADER_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Is the campaign enabled'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a campaign header text'),
                        'name' => 'HEADER_TEXT',
                        'label' => $this->l('Header text'),
                    ),
                    array(
                        'type' => 'color',
                        'desc' => $this->l('Choose a background color'),
                        'name' => 'HEADER_COLOR',
                        'label' => $this->l('Background color'),
                    ),
                ),
                // 'submit' => array(
                //     'title' => $this->l('Save'),
                // ),
            ),
        );
    }

    protected function getConfigFormCartContent()
    {

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Cart Content Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'buttons' => array(
                    'Return'=> array(
                        'type' => 'submit',
                        'name' => 'ReturnToCampaignPage',
                        'title' => $this->l('Return'),
                        'class' => 'btn btn-default pull-left',
                        'id'   => 'mybutton',
                        'icon' => 'icon-foo',
                    ),
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Is Active'),
                        'name' => 'CART_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Is the campaign enabled'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Promo type'),
                        'name' => 'CART_TYPE',
                        'is_bool' => true,
                        'desc' => $this->l('Choose type of promo'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Fixed')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Range')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a discount amount for a fixed promo'),
                        'name' => 'CART_FIXED_DISCOUNT',
                        'label' => $this->l('Discount amount for fixed promo'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a minimal total price value when fixed discount is applied'),
                        'name' => 'CART_FIXED_PRICE',
                        'label' => $this->l('Minimal total price for fixed promo'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a discount amount for a ranged promo'),
                        'name' => 'CART_RANGED_DISCOUNT',
                        'label' => $this->l('Discount amount for ranged promo'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a maximum discount amount for a ranged promo'),
                        'name' => 'CART_RANGED_DISCOUNT_MAX',
                        'label' => $this->l('Maximum discount amount for ranged promo'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a minimal total price value when ranged discount is applied'),
                        'name' => 'CART_RANGED_PRICE_MIN',
                        'label' => $this->l('Minimal total price for ranged promo'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a maximum total price value when ranged discount is applied'),
                        'name' => 'CART_RANGED_PRICE_MAX',
                        'label' => $this->l('Maximum total price for ranged promo'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->trans('Promo text content'),
                        'name' => 'CART_TEXT',
                        'cols' => 40,
                        'rows' => 100,
                        'desc' => $this->l('Enter a promo text content. If the type is fixed, use [total_price] & [discount_fixed] for values, if ranged : [total_price] & [discount_ranged]'),
                        'class' => 'rte',
                        'autoload_rte' => true,
                    ),
                    array(
                        'type' => 'color',
                        'desc' => $this->l('Choose a text color'),
                        'name' => 'CART_TEXT_COLOR',
                        'label' => $this->l('Text color'),
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'ID',
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'CART_ID',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormCategoryContent()
    {

        /*        $allCategories = Db::getInstance()->executeS('SELECT `id_category`, `name` FROM `' . _DB_PREFIX_ . 'category_lang`
                    WHERE id_lang = '.Context::getContext()->language->id.' AND id_shop = '.Context::getContext()->shop->id);*/

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Category Content Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'buttons' => array(
                    'Return'=> array(
                        'type' => 'submit',
                        'name' => 'ReturnToCampaignPage',
                        'title' => $this->l('Return'),
                        'class' => 'btn btn-default pull-left',
                        'id'   => 'mybutton',
                        'icon' => 'icon-foo',
                    ),
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Is Active'),
                        'name' => 'CATEGORY_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Is the campaign part enabled'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->trans('Category text content'),
                        'name' => 'CATEGORY_TEXT',
                        'cols' => 40,
                        'rows' => 100,
                        'desc' => $this->l('Enter a text content.'),
                        'class' => 'rte',
                        'autoload_rte' => true,
                    ),
                    array(
                        'type' => 'color',
                        'desc' => $this->l('Choose a background color'),
                        'name' => 'CATEGORY_BACKGROUND_COLOR',
                        'label' => $this->l('Background color'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Is shown in all categories'),
                        'name' => 'CATEGORY_SHOW_ALL',
                        'is_bool' => true,
                        'desc' => $this->l('Is the campaign part shown in all categories'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'ID',
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'CATEGORY_ID',
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'CATEGORY_LIST',
                        'id' => 'CATEGORY_LIST'
                    ),
                    /*                    array(
                                            'type'    => 'select',
                                            'label'   => $this->l('Select the categories where to show the promo content'),
                                            'name'    => 'CATEGORY_LIST[]',
                                            'multiple' => true,
                                            'options' => [
                                                'query' => $allCategories,
                                                'id'    => 'id_category',
                                                'name'  => 'name',
                                            ],
                                        )*/
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormProductContent()
    {

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Product Content Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'buttons' => array(
                    'Return'=> array(
                        'type' => 'submit',
                        'name' => 'ReturnToCampaignPage',
                        'title' => $this->l('Return'),
                        'class' => 'btn btn-default pull-left',
                        'id'   => 'mybutton',
                        'icon' => 'icon-foo',
                    ),
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Is Active'),
                        'name' => 'PRODUCT_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Is the campaign part enabled'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->trans('Product text content'),
                        'name' => 'PRODUCT_TEXT',
                        'cols' => 40,
                        'rows' => 100,
                        'desc' => $this->l('Enter a text content.'),
                        'class' => 'rte',
                        'autoload_rte' => true,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Is shown in all products'),
                        'name' => 'PRODUCT_SHOW_ALL',
                        'is_bool' => true,
                        'desc' => $this->l('Is the campaign part shown in all products'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'ID',
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'PRODUCT_ID',
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'PRODUCT_SELECTION',
                        'id' => 'PRODUCT_SELECTION'
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormDevisContent()
    {

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Quote Content Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'textarea',
                        'label' => $this->trans('Quote text content'),
                        'name' => 'DEVIS_TEXT',
                        'cols' => 40,
                        'rows' => 100,
                        'desc' => $this->l('Enter a text content.'),
                        'class' => 'rte',
                        'autoload_rte' => true,
                    ),
                    array(
                        'type' => 'color',
                        'desc' => $this->l('Choose a background color'),
                        'name' => 'DEVIS_BACKGROUND_COLOR',
                        'label' => $this->l('Background color'),
                    ),
                ),
                // 'submit' => array(
                //     'title' => $this->l('Save'),
                // ),
            ),
        );
    }

    protected function getConfigFormPromoPageContent()
    {

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Promo Page Content Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'textarea',
                        'label' => $this->trans('Promo Page text content'),
                        'name' => 'PROMO_PAGE_TEXT',
                        'cols' => 40,
                        'rows' => 100,
                        'desc' => $this->l('Enter a text content.'),
                        'class' => 'rte',
                        'autoload_rte' => true,
                    ),
                ),
                // 'submit' => array(
                //     'title' => $this->l('Save'),
                // ),
            ),
        );
    }


    protected function getConfigFormValuesMain($id = null)
    {
        if(null == $id){
            return array(
                'ID' => '',
                'ACTIVE' => '',
                'TITLE' => '',
                'CLIENT_GROUP' => '',
                'DATE_START' => '',
                'DATE_END' => '',
                'LOGO_PATH' => '',
                'FRANCO_IMPACT' => '',
                'FRANCO_IMPACT_PRO' => '',
                'MENU_ACTIVE' => '',
                'MENU_IMG' => '',
                'MENU_LINK' => '',
                'HEADER_ACTIVE' => '',
                'HEADER_TEXT' => '',
                'HEADER_COLOR' => '',
                'CART_TEXT_COLOR' => '',
                /*                'CATEGORY_ACTIVE' => '',
                                'CATEGORY_TEXT' => '',
                                'CATEGORY_BACKGROUND_COLOR' => '',
                                'CATEGORY_SHOW_ALL' => '',*/
                'DEVIS_TEXT' => "",
                'DEVIS_BACKGROUND_COLOR' => "",
                'PROMO_PAGE_TEXT' => "",
                /*                'CATEGORY_LIST[]' => ""*/
            );
        } else {
            $campaignModel = new CampaignModel;

            $campaignInfo = $campaignModel->getCampaignInfo($id);

            return array(
                'ID' =>  $campaignInfo['id'],
                'ACTIVE' => $campaignInfo['active'],
                'TITLE' => $campaignInfo['title'],
                'CLIENT_GROUP' => $campaignInfo['id_client_group'],
                'DATE_START' => $campaignInfo['date_start'],
                'DATE_END' => $campaignInfo['date_end'],
                'LOGO_PATH' => $campaignInfo['logo_path'],
                'FRANCO_IMPACT' => isset($campaignInfo['franco_impact']) ? number_format($campaignInfo['franco_impact'], 2, '.', '') : "",
                'FRANCO_IMPACT_PRO' => isset($campaignInfo['franco_impact_pro']) ? number_format($campaignInfo['franco_impact_pro'], 2, '.', '') : "",
                'MENU_ACTIVE' => $campaignInfo['menuActive'],
                'MENU_IMG' => $campaignInfo['imgMenuPath'],
                'MENU_LINK' => $campaignInfo['menuLink'],
                'HEADER_ACTIVE' => $campaignInfo['headerActive'],
                'HEADER_TEXT' => html_entity_decode($campaignInfo['headerText']),
                'HEADER_COLOR' => $campaignInfo['headerColor'],
                /*                'CATEGORY_ACTIVE' => $campaignInfo['categoryActive'],
                                'CATEGORY_TEXT' => html_entity_decode($campaignInfo['categoryText']),
                                'CATEGORY_BACKGROUND_COLOR' => $campaignInfo['categoryColor'],
                                'CATEGORY_SHOW_ALL' => $campaignInfo['categoryShowAll'],*/
                'DEVIS_TEXT' => html_entity_decode($campaignInfo['textDevis']),
                'DEVIS_BACKGROUND_COLOR' => $campaignInfo['colorDevis'],
                'PROMO_PAGE_TEXT' => html_entity_decode($campaignInfo['promoPageText']),
                /*                'CATEGORY_LIST[]' => ''*/
            );
        }

    }

    protected function getConfigFormValuesCart($id = null, $idElement = null)
    {
        if(null == $idElement){
            return array(
                'ID' => $id,
                'CART_ID' => '',
                'CART_ACTIVE' => '',
                'CART_TYPE' => '',
                'CART_FIXED_DISCOUNT' => '',
                'CART_FIXED_PRICE' => '',
                'CART_RANGED_DISCOUNT' => '',
                'CART_RANGED_DISCOUNT_MAX' => '',
                'CART_RANGED_PRICE_MIN' => '',
                'CART_RANGED_PRICE_MAX' => '',
                "CART_TEXT_COLOR" => '',
                'CART_TEXT' => '',

            );
        } else {
            $campaignModel = new CampaignModel;

            $campaignPromoInfo = $campaignModel->getCampaignInfoCart($id, $idElement);

            return array(
                'ID' => $id,
                'CART_ID' => $campaignPromoInfo['id'],
                'CART_ACTIVE' => $campaignPromoInfo['active'],
                'CART_TYPE' => $campaignPromoInfo['is_fixed'],
                'CART_FIXED_DISCOUNT' => isset($campaignPromoInfo['discount_fixed']) ? number_format($campaignPromoInfo['discount_fixed'], 2, '.', '') : 0,
                'CART_FIXED_PRICE' => isset($campaignPromoInfo['price_fixed']) ? number_format($campaignPromoInfo['price_fixed'], 2, '.', '') : 0,
                'CART_RANGED_DISCOUNT' => isset($campaignPromoInfo['discount_range']) ? number_format($campaignPromoInfo['discount_range'], 2, '.', '') : 0,
                'CART_RANGED_DISCOUNT_MAX' => isset($campaignPromoInfo['discount_range_max']) ? number_format($campaignPromoInfo['discount_range_max'], 2, '.', '') : 0,
                'CART_RANGED_PRICE_MIN' => isset($campaignPromoInfo['price_range_min']) ? number_format($campaignPromoInfo['price_range_min'], 2, '.', '') : 0,
                'CART_RANGED_PRICE_MAX' => isset($campaignPromoInfo['price_range_max']) ? number_format($campaignPromoInfo['price_range_max'], 2, '.', '') : 0,
                "CART_TEXT_COLOR" => $campaignPromoInfo['text_color'],
                'CART_TEXT' => html_entity_decode($campaignPromoInfo['text']),
            );
        }

    }

    protected function getConfigFormValuesProduct($id = null, $idElement = null)
    {
        if(null == $idElement){
            return array(
                'ID' => $id,
                'PRODUCT_ID' => '',
                'PRODUCT_ACTIVE' => '',
                'PRODUCT_TEXT' => '',
                'PRODUCT_SHOW_ALL' => '',
                'PRODUCT_SELECTION' => ''
            );
        } else {
            $campaignModel = new CampaignModel;

            $campaignPromoInfo = $campaignModel->getCampaignInfoProduct($id, $idElement);

            return array(
                'ID' => $id,
                'PRODUCT_ID' => $campaignPromoInfo['id'],
                'PRODUCT_ACTIVE' => $campaignPromoInfo['active'],
                'PRODUCT_TEXT' => html_entity_decode($campaignPromoInfo['text']),
                'PRODUCT_SHOW_ALL' => $campaignPromoInfo['show_all'],
                'PRODUCT_SELECTION' => ''
            );
        }

    }

    protected function getConfigFormValuesCategory($id = null, $idElement = null)
    {
        if(null == $idElement){
            return array(
                'ID' => $id,
                'CATEGORY_ID' => '',
                'CATEGORY_ACTIVE' => '',
                'CATEGORY_TEXT' => '',
                'CATEGORY_BACKGROUND_COLOR' => '',
                'CATEGORY_SHOW_ALL' => '',
                'CATEGORY_LIST[]' => ""
            );
        } else {
            $campaignModel = new CampaignModel;

            $campaignPromoInfo = $campaignModel->getCampaignInfoCategory($id, $idElement);

            return array(
                'ID' => $id,
                'CATEGORY_ID' => $campaignPromoInfo['idCategory'],
                'CATEGORY_ACTIVE' => $campaignPromoInfo['categoryActive'],
                'CATEGORY_TEXT' => html_entity_decode($campaignPromoInfo['categoryText']),
                'CATEGORY_BACKGROUND_COLOR' => $campaignPromoInfo['categoryColor'],
                'CATEGORY_SHOW_ALL' => $campaignPromoInfo['categoryShowAll'],
                'CATEGORY_LIST[]' => ""
            );
        }

    }

    public function copyCampaign($idCampaign){
        $campaignModel = new CampaignModel();

        $originalCampaignMainInfos = $campaignModel->getCampaignInfo($idCampaign);

        $originalCampaignCartInfos = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_cart ccl
            WHERE ccl.id_campaign = '.$idCampaign);

        $originalCampaignProductInfos = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_product ccp
        WHERE ccp.id_campaign = '.$idCampaign);

        $originalCampaignCategoryInfos = Db::getInstance()->executeS('SELECT ccc.id as idCategory, ccc.text as categoryText, ccc.background_color as categoryColor, ccc.active as categoryActive, ccc.show_all as categoryShowAll
        FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_category ccc
        WHERE ccc.id_campaign = '.$idCampaign);

        $categorySelection = Db::getInstance()->executeS('SELECT DISTINCT cccs.id_campaign, cccs.id_category FROM ' . _DB_PREFIX_ . 'ckccampaign_config_category_show cccs
                    LEFT JOIN  `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` ccc ON ccc.id = cccs.id_campaign 
                    WHERE ccc.id_campaign = '.$idCampaign);

        $productSelection =  Db::getInstance()->executeS('SELECT c.`id_product_content`, c.`id_product` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_product_show` c
                    LEFT JOIN `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` cp ON cp.id = c.id_product_content
                    WHERE cp.id_campaign = '.$idCampaign);


        $CampaignCategoryInfos = [];
        foreach ($originalCampaignCategoryInfos as $categoryPromo){
            $categories = [];
            foreach ($categorySelection as $category) {
                if($categoryPromo['idCategory'] == $category['id_campaign']) {
                    $categories[] = $category['id_category'];
                }
            }
            $categoryPromo['categories'] = $categories;
            $CampaignCategoryInfos[] = $categoryPromo;
        }


        $CampaignProductInfos = [];
        foreach ($originalCampaignProductInfos as $productPromo){
            $products = [];
            foreach ($productSelection as $product) {
                if($productPromo['id'] == $product['id_product_content']) {
                    $products[] = $product['id_product'];
                }
            }
            $productPromo['products'] = $products;
            $CampaignProductInfos[] = $productPromo;
        }
        $date = date('Y-m-d H:i:s');
        $createCampaign = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_list` (`id`, `title`, `id_client_group`,`active`,`logo_path`, `franco_impact`,`franco_impact_pro`,`date_start`, `date_end`, `date_add`)
                VALUES (NULL, "'.$originalCampaignMainInfos['title'].' copie '.$date.'", "'.$originalCampaignMainInfos['id_client_group'].'", 0, "'.$originalCampaignMainInfos['logo_path'].'", "'.$originalCampaignMainInfos['franco_impact'].'", "'.$originalCampaignMainInfos['franco_impact_pro'].'", "'.$originalCampaignMainInfos['date_start'].'","'.$originalCampaignMainInfos['date_end'].'", "'.$date.'")');

        $newIdQuery = Db::getInstance()->executeS('SELECT id FROM `' . _DB_PREFIX_ . 'ckccampaign_config_list`
                WHERE `title` LIKE "'.$originalCampaignMainInfos['title'].' copie '.$date.'" AND `id_client_group` LIKE "'.$originalCampaignMainInfos['id_client_group'].'" AND `date_start` = "'.$originalCampaignMainInfos['date_start'].'"');

        $newId = $newIdQuery[0]["id"];

        $createMenu = Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu` (`id`, `id_campaign`, `img_path`, `link`, `active`, `date_add`)
                                VALUES (NULL, '.$newId.', \''.$originalCampaignMainInfos['imgMenuPath'].'\',\''.$originalCampaignMainInfos['menuLink'].'\','.(int)$originalCampaignMainInfos['menuActive'].', CURRENT_TIMESTAMP())'
        );

        $createDevis =  Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_devis` (`id`, `id_campaign`, `text`, `background_color`, `date_add`)
                                VALUES (NULL, '.$newId.', \''.$originalCampaignMainInfos['textDevis'].'\', \''.$originalCampaignMainInfos['colorDevis'].'\', CURRENT_TIMESTAMP())');

        $createPromoPage = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_promopage` (`id`, `id_campaign`, `text`, `date_add`)
                VALUES (NULL, '.$newId.', \''.$originalCampaignMainInfos['promoPageText'].'\', CURRENT_TIMESTAMP())');

        foreach ($originalCampaignCartInfos as $cartPromo){
            $createCartPromo = Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_cart` 
                                    (`id`, `id_campaign`, `is_fixed`, `discount_fixed`, `price_fixed`, `discount_range`, `discount_range_max`, `price_range_min`, `price_range_max`, `text`, `text_color`,`active`, `date_add`)
                            VALUES (NULL, '.$newId.', '.$cartPromo['is_fixed'].', "'.$cartPromo['discount_fixed'].'", "'.$cartPromo['price_fixed'].'",
                            "'.$cartPromo['discount_range'].'","'.$cartPromo['discount_range_max'].'","'.$cartPromo['price_range_min'].'", "'.$cartPromo['price_range_max'].'",\''.$cartPromo['text'].'\', 
                            NULLIF(\''.$cartPromo['text_color'].'\', ""), '.$cartPromo['active'].' ,CURRENT_TIMESTAMP())'
            );
        }

        foreach ($CampaignCategoryInfos as $categoryCampaign){
            $createCategory = Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` (`id`, `id_campaign`, `text`, `background_color`, `active`,`show_all`, `date_add`)
                                VALUES (NULL, '.$newId.', \''.$categoryCampaign["categoryText"].'\', \''.$categoryCampaign["categoryColor"].'\', 
                                '.(int)$categoryCampaign["categoryActive"].', '.(int)$categoryCampaign["categoryShowAll"].', CURRENT_TIMESTAMP())'
            );
            $queryCategory = Db::getInstance()->executeS('SELECT id FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` WHERE `id_campaign` = '.$newId.' AND `text` LIKE \'' .$categoryCampaign["categoryText"].'\'');

            if(count($categoryCampaign['categories']) > 0){
                foreach ($categoryCampaign['categories'] as $category) {
                    $updateNew = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_category_show` (`id`, `id_campaign`, `id_category`)
                        VALUES (NULL, '.$queryCategory[0]["id"].', '.$category.')');
                }
            }
        }

        foreach ($CampaignProductInfos as $productCampaign){
            $createProductPromo = Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` 
                                    (`id`, `id_campaign`, `text`,`active`, `show_all`, `date_add`)
                            VALUES (NULL, '.$newId.',\''.$productCampaign['text'].'\', '.$productCampaign['active'].','.$productCampaign['show_all'].',CURRENT_TIMESTAMP())'
            );
            $selectPromoId = Db::getInstance()->executeS('SELECT id FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` WHERE `id_campaign` = '.$newId.' AND `text` LIKE \'' .$productCampaign['text'].'\'');

            if(count($productCampaign['products']) > 0){
                foreach ($productCampaign['products'] as $product) {
                    $insertProductSelection = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_product_show` 
                        (`id`, `id_product_content`, `id_product`)
                            VALUES (NULL, '.$selectPromoId[0]["id"].','.$product.')');
                }
            }
        }

        return $newId;
    }

    public function deleteCampaign($idCampaign){
        $deleteMainCampaign = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_list` WHERE id = '.$idCampaign);

        $categoryPromos = Db::getInstance()->executeS('SELECT c.id FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` c WHERE c.id_campaign = '.$idCampaign);

        if(count($categoryPromos) > 0 && !empty($categoryPromos[0]['id'])){
            foreach ($categoryPromos as $categoryPromo){
                $deleteCategoryPromosShow = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_category_show` WHERE id_campaign = '.$categoryPromo['id']);
            }
        }

        $deleteCategoryPromos = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` WHERE id_campaign = '.$idCampaign);

        $productPromos = Db::getInstance()->executeS('SELECT c.id FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` c WHERE c.id_campaign = '.$idCampaign);

        if(count($productPromos) > 0 && !empty($productPromos[0]['id'])){
            foreach ($productPromos as $productPromo){
                $deleteProductPromosShow = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_product_show` WHERE id_product_content = '.$productPromo['id']);
            }
        }

        $deleteProductPromos = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` WHERE id_campaign = '.$idCampaign);

        $deleteCartPromos = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_cart` WHERE id_campaign = '.$idCampaign);

        $deleteDevisPromo = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_devis` WHERE id_campaign = '.$idCampaign);

        $deleteMenuPromo = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu` WHERE id_campaign = '.$idCampaign);

        $deletePromoPagePromo = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_promopage` WHERE id_campaign = '.$idCampaign);
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addJS($this->_path.'views/js/menu.js');
            $this->context->controller->addJS($this->_path.'views/js/vue.min.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayAfterCartDetailedActions()
    {
        $customerGroupId = Context::getContext()->customer->id_default_group;

        $cartPromosSql = Db::getInstance()->executeS('SELECT ccc.*, ccl.id_client_group FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_cart ccc
        LEFT JOIN ' . _DB_PREFIX_ . 'ckccampaign_config_list ccl ON ccl.id = ccc.id_campaign');

        $cartPromos = [];

        if (isset($cartPromosSql)){
            foreach ($cartPromosSql as $cartPromo){

                $dateCheck = $this->checkPromoDate($cartPromo['id_campaign']);

                if($cartPromo['active'] && in_array($customerGroupId, explode(',',$cartPromo['id_client_group'])) && $dateCheck){
                    $promoText = $cartPromo['text'];

                    if($cartPromo['is_fixed']){
                        $ttlPricePos = strpos($promoText, '[total_price]');
                        $discountPos = strpos($promoText, '[discount_fixed]');


                        if($ttlPricePos !== false) {

                            $promotion_amount = $cartPromo['price_fixed'];

                            // $total_product = $this->includeTaxes() ? $total_including_tax_cart : $total_excluding_tax_cart;
                            $total_product = Context::getContext()->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
                            if ($total_product < $promotion_amount) {
                                $amountLeftForNextPromotion = $promotion_amount - $total_product;
                            } else {
                                $amountLeftForNextPromotion = 0;
                            }

                            $calcul = round($amountLeftForNextPromotion, 2);
                            if($calcul > 0) {
                                $str1 = str_replace('[total_price]', number_format($calcul, 2), $promoText);
                                if($discountPos !== false) {
                                    $str2 = str_replace('[discount_fixed]', number_format($cartPromo['discount_fixed'], 0), $str1);
                                    $cartPromo['textFinal'] = $str2;
                                } else {
                                    $cartPromo['textFinal'] = $str1;
                                }
                            }
                        }
                    } else {

                        $ttlPricePos = strpos($promoText, '[total_price]');
                        $discountPos = strpos($promoText, '[discount_ranged]');

                        if($ttlPricePos !== false && $discountPos !== false) {

                            $promotion_threshold = $cartPromo['price_range_min'];
                            $discount_below_3000 = $cartPromo['discount_range'];
                            $discount_above_3000 = $cartPromo['discount_range_max'];

                            $total_product = Context::getContext()->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
                            $decimal = fmod($total_product, $promotion_threshold);
                            $modulo = $total_product % $promotion_threshold;

                            if($decimal == 0 && $modulo == 0){
                                $is_round = 1;
                            } else {
                                $is_round = 0;
                            }

                            $next_promotion_target = (ceil($total_product / $promotion_threshold) + ($is_round ? 1 : 0)) * $promotion_threshold;
                            $amount_left_for_next_promotion = $next_promotion_target - $total_product;

                            $coef1000 = ceil($total_product / $promotion_threshold);
                            $coef3000 = 1;

                            if ($total_product <= $cartPromo['price_range_max']) {
                                $promotion_discount = floor($total_product / $promotion_threshold) * $discount_below_3000;

                                $calcul_next_promotion_discount = $discount_below_3000 * ($coef1000 + ($is_round ? 1 : 0));
                                if($calcul_next_promotion_discount <= $cartPromo['discount_range_max']) {
                                    $next_promotion_discount = $calcul_next_promotion_discount;
                                } else {
                                    $next_promotion_discount = $cartPromo['discount_range_max'];
                                }
                            }
                            /*                            else {
                                                            $coef1000 = 2;
                                                            $coef3000 = ceil($total_product / $promotion_threshold) - $coef1000;
                                                            $promotion_discount = floor($total_product / $promotion_threshold) * $discount_above_3000;
                                                            $next_promotion_discount = ($discount_above_3000 * ($coef3000 + ($is_round ? 1 : 0))) + ($discount_below_3000 * $coef1000);
                                                        }*/

                            if (isset($next_promotion_discount) && isset($amount_left_for_next_promotion)){
                                $next_promotion_winter = round($amount_left_for_next_promotion, 2);
                                $next_promotion_winter_discount = $next_promotion_discount;

                                $str1 = str_replace('[total_price]', $next_promotion_winter, $promoText);
                                $str2 = str_replace('[discount_ranged]', number_format($next_promotion_winter_discount, 0), $str1);
                                $cartPromo['textFinal'] = $str2;
                            }
                        }

                    }

                    $cartPromos[] = $cartPromo;
                }
            }
        }

        $this->context->smarty->assign(array(
            'cartPromos' => $cartPromos
        ));

        // Retourner le template avec le lien à afficher
        return $this->display(__FILE__, 'views/templates/front/hook/promo_content_cart.tpl');

    }

    public function hookDisplayPromoProductDescription()
    {

        $customerGroupId = Context::getContext()->customer->id_default_group;

        $product = Context::getContext()->controller->getTemplateVarProduct();

        $productPromosSql = Db::getInstance()->executeS('SELECT ccp.*, ccl.id_client_group FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_product ccp
        LEFT JOIN ' . _DB_PREFIX_ . 'ckccampaign_config_list ccl ON ccl.id = ccp.id_campaign');

        $productPromos = [];

        if(isset($productPromosSql)){
            foreach($productPromosSql as $productPromo){

                $dateCheck = $this->checkPromoDate($productPromo['id_campaign']);

                if($productPromo['active'] && in_array($customerGroupId, explode(',',$productPromo['id_client_group'])) && $dateCheck){
                    if($productPromo['show_all']){
                        $productPromos[] = $productPromo;
                    } else {
                        $productIdsSQL = Db::getInstance()->executeS('SELECT GROUP_CONCAT(id_product) FROM ' . _DB_PREFIX_ . 'ckccampaign_config_product_show WHERE id_product_content = '.$productPromo['id']);
                        $productIds = explode(",", $productIdsSQL[0]['GROUP_CONCAT(id_product)']);

                        if(isset($productIds[0])){
                            if(in_array($product['id_product'], $productIds)){
                                $productPromos[] = $productPromo;
                            }
                        }
                    }
                }
            }
        }

        $this->context->smarty->assign(array(
            'productId' => $product['id_product'],
            'productPromos' => $productPromos
        ));
        // Retourner le template avec le lien à afficher
        return $this->display(__FILE__, 'views/templates/front/hook/promo_content_product.tpl');
    }

    public function hookDisplayPromoCategoryDescription()
    {

        $customerGroupId = Context::getContext()->customer->id_default_group;

        $categoryId = Context::getContext()->controller->getCategory()->id;

        $categoryPromosSql = Db::getInstance()->executeS('SELECT ccc.id, ccc.id_campaign, ccl.id_client_group, ccc.text as categoryText, ccc.background_color as categoryColor, ccc.active as categoryActive,ccc.show_all as categoryShowAll
        FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_category ccc
        LEFT JOIN ' . _DB_PREFIX_ . 'ckccampaign_config_list ccl ON ccl.id = ccc.id_campaign');

        $categoryPromos = [];

        if(isset($categoryPromosSql)){
            foreach($categoryPromosSql as $categoryPromo){

                $dateCheck = $this->checkPromoDate($categoryPromo['id_campaign']);

                if($categoryPromo['categoryActive'] && in_array($customerGroupId, explode(',',$categoryPromo['id_client_group'])) && $dateCheck){
                    if($categoryPromo['categoryShowAll']){
                        $categoryPromos[] = $categoryPromo;
                    } else {
                        $categoryIdsSql = Db::getInstance()->executeS('SELECT GROUP_CONCAT(id_category) FROM ' . _DB_PREFIX_ . 'ckccampaign_config_category_show WHERE id_campaign = '.$categoryPromo['id']);
                        $categoryIds = explode(",", $categoryIdsSql[0]['GROUP_CONCAT(id_category)']);

                        if(isset($categoryIds[0])){
                            if(in_array($categoryId, $categoryIds)){
                                $categoryPromos[] = $categoryPromo;
                            }
                        }

                    }
                }
            }
        }

        $this->context->smarty->assign(array(
            'categoryPromos' => $categoryPromos
        ));

        // Retourner le template avec le lien à afficher
        return $this->display(__FILE__, 'views/templates/front/hook/promo_content_category.tpl');
    }

    public function hookDisplayPromoMenuImage()
    {
        $customerGroupId = Context::getContext()->customer->id_default_group;

        $queryMenu = Db::getInstance()->executeS('SELECT ccm.id as idMenu, ccm.id_campaign, ccl.id_client_group, ccm.img_path as imgMenuPath, ccm.active as menuActive, ccm.link as menuLink FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_menu ccm
        LEFT JOIN  ' . _DB_PREFIX_ . 'ckccampaign_config_list ccl ON ccl.id = ccm.id_campaign');

        $menuPromos = [];

        if(isset($queryMenu)){
            foreach ($queryMenu as $menuPromo){
                $dateCheck = $this->checkPromoDate($menuPromo['id_campaign']);
                if($menuPromo['menuActive'] && in_array($customerGroupId, explode(',',$menuPromo['id_client_group'])) && $dateCheck){
                    $menuPromos[] = $menuPromo;
                }
            }
        }

        $this->context->smarty->assign(array(
            'menuPromos' => $menuPromos,
            'modulePath' =>Tools::getHttpHost(true) . __PS_BASE_URI__ .'modules/ckcadcampaignconfigurator/uploads/menus/',
        ));

        // Retourner le template avec le lien à afficher
        return $this->display(__FILE__, 'views/templates/front/hook/promo_content_menu.tpl');

    }

    public function hookDisplayPromoDevisText()
    {
        $customerGroupId = Context::getContext()->customer->id_default_group;

        $queryDevis = Db::getInstance()->executeS('SELECT ccd.id as idDevis, ccd.text as textDevis, ccd.id_campaign, ccl.id_client_group, ccd.background_color as colorDevis FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_devis ccd
        LEFT JOIN  ' . _DB_PREFIX_ . 'ckccampaign_config_list ccl ON ccl.id = ccd.id_campaign');

        $devisPromos = [];

        if(isset($queryDevis)){
            foreach($queryDevis as $devisPromo){
                $dateCheck = $this->checkPromoDate($devisPromo['id_campaign']);
                if(in_array($customerGroupId, explode(',',$devisPromo['id_client_group'])) && $dateCheck){
                    $devisPromos[] = $devisPromo;
                }
            }
        }

        $this->context->smarty->assign(array(
            'devisPromos' => $devisPromos,
        ));

        // Retourner le template avec le lien à afficher
        return $this->display(__FILE__, 'views/templates/front/hook/promo_content_devis.tpl');

    }

    public function hookDisplayPromoPageText()
    {
        $customerGroupId = Context::getContext()->customer->id_default_group;

        $queryPromoPage = Db::getInstance()->executeS('SELECT ccpp.id as idPromoPage, ccpp.id_campaign, ccl.id_client_group, ccpp.text as promoPageText FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_promopage ccpp
        LEFT JOIN  ' . _DB_PREFIX_ . 'ckccampaign_config_list ccl ON ccl.id = ccpp.id_campaign');

        $promoPagePromos = [];

        if(isset($queryPromoPage)){
            foreach($queryPromoPage as $promoPagePromo){
                $dateCheck = $this->checkPromoDate($promoPagePromo['id_campaign']);
                if(in_array($customerGroupId, explode(',',$promoPagePromo['id_client_group'])) && $dateCheck){
                    $promoPagePromos[] = $promoPagePromo;
                }
            }
        }

        $this->context->smarty->assign(array(
            'promoPagePromos' => $promoPagePromos,
        ));

        // Retourner le template avec le lien à afficher
        return $this->display(__FILE__, 'views/templates/front/hook/promo_content_promo_page.tpl');

    }

    public function checkPromoDate($idCampaign){
        $today = date("Y-m-d H:i:s");

        $getCampaignDates = Db::getInstance()->executeS('SELECT date_start, date_end, active FROM ' . _DB_PREFIX_ . 'ckccampaign_config_list ccl
        WHERE ccl.id = '.$idCampaign);

        if($getCampaignDates[0]['active']){
            if($today > $getCampaignDates[0]['date_start'] && $today < $getCampaignDates[0]['date_end']){
                return true;
            } else{
                return false;
            }
        } else {
            return false;
        }
    }

    public function hookDisplayHeader(){

        $this->context->controller->addCSS($this->_path.'/views/css/front.css');

        $customerGroupId = Context::getContext()->customer->id_default_group;

        $getCampaigns =  Db::getInstance()->executeS('SELECT id, logo_path, id_client_group, active FROM ' . _DB_PREFIX_ . 'ckccampaign_config_list
        ORDER BY id ASC');

        foreach($getCampaigns as $campaign){

            $checkDate = $this->checkPromoDate($campaign['id']);

            if(in_array($customerGroupId, explode(',',$campaign['id_client_group'])) && $checkDate && $campaign['active'] && isset($campaign['logo_path']) && $campaign['logo_path'] != ""){

                Media::addJsDef(
                    array(
                        'campaignLogo' => Tools::getHttpHost(true) . __PS_BASE_URI__ .'modules/ckcadcampaignconfigurator/uploads/campaigns/'.$campaign['logo_path'],
                    )
                );
                $this->context->controller->addJS($this->_path.'/views/js/front.js');
                break;
            }
        }

    }

    public function hookDisplayPromoImpactFranco(){
        $customerGroupId = Context::getContext()->customer->id_default_group;

        $getCampaigns =  Db::getInstance()->executeS('SELECT id, franco_impact, franco_impact_pro, id_client_group, active FROM ' . _DB_PREFIX_ . 'ckccampaign_config_list
        ORDER BY id ASC');

        $promoFrancoImpact = "1 499";

        foreach($getCampaigns as $campaign){

            $checkDate = $this->checkPromoDate($campaign['id']);

            if(in_array($customerGroupId, explode(',',$campaign['id_client_group'])) && $checkDate && $campaign['active'] && isset($campaign['franco_impact'])){

                if(in_array(10, explode(',',$campaign['id_client_group']))){
                    if($customerGroupId == 10 && isset($campaign['franco_impact_pro'])){
                        $promoFrancoImpact = number_format($campaign['franco_impact_pro'], 0, '.', ' ');
                        break;
                    } else {
                        $promoFrancoImpact = number_format($campaign['franco_impact'], 0, '.', ' ');
                        break;
                    }
                } else {
                    $promoFrancoImpact = number_format($campaign['franco_impact'], 0, '.', ' ');
                    break;
                }
            } else if ($customerGroupId == 10 && isset($campaign['franco_impact_pro']) && $checkDate && $campaign['active']){
                $promoFrancoImpact = number_format($campaign['franco_impact_pro'], 0, '.', ' ');
                break;
            }
        }


        $this->context->smarty->assign(array(
            'promoFrancoImpact' => $promoFrancoImpact,
        ));

        // Retourner le template avec le lien à afficher
        return $this->display(__FILE__, 'views/templates/front/hook/promo_content_franco.tpl');
    }
}
