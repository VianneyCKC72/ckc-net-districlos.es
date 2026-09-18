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

class Ckccustomfilterselector extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ckccustomfilterselector';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'ckc-net';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ckc Custom Filter Selector');
        $this->description = $this->l('Ckc Custom Filter Selector');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '9.0');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CKCCUSTOMFILTERSELECTOR_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayCustomFilterSelector') &&
            $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CKCCUSTOMFILTERSELECTOR_LIVE_MODE');

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
        if (((bool)Tools::isSubmit('submitCkccustomfilterselectorModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCkccustomfilterselectorModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'CKCCUSTOMFILTERSELECTOR_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'CKCCUSTOMFILTERSELECTOR_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'CKCCUSTOMFILTERSELECTOR_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CKCCUSTOMFILTERSELECTOR_LIVE_MODE' => Configuration::get('CKCCUSTOMFILTERSELECTOR_LIVE_MODE', true),
            'CKCCUSTOMFILTERSELECTOR_ACCOUNT_EMAIL' => Configuration::get('CKCCUSTOMFILTERSELECTOR_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'CKCCUSTOMFILTERSELECTOR_ACCOUNT_PASSWORD' => Configuration::get('CKCCUSTOMFILTERSELECTOR_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayCustomFilterSelector()
    {
        $heightOptions = Db::getInstance()->executeS('SELECT DISTINCT al.name FROM `' . _DB_PREFIX_ . 'attribute_lang` al LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON al.id_attribute = a.id_attribute LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON agl.id_attribute_group = a.id_attribute_group LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON al.id_attribute = pac.id_attribute LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas ON pas.id_product_attribute = pac.id_product_attribute LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON cp.id_product = pas.id_product LEFT JOIN `' . _DB_PREFIX_ . 'product` pr ON pr.id_product = pas.id_product WHERE agl.name LIKE "hauteur" AND (cp.id_category = 3 OR cp.id_category = 2 OR cp.id_category = 128) AND pr.active = 1');
        $heightOptions3 = Db::getInstance()->executeS('SELECT DISTINCT al.name FROM `' . _DB_PREFIX_ . 'attribute_lang` al LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON al.id_attribute = a.id_attribute LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON agl.id_attribute_group = a.id_attribute_group LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON al.id_attribute = pac.id_attribute LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas ON pas.id_product_attribute = pac.id_product_attribute LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON cp.id_product = pas.id_product LEFT JOIN `' . _DB_PREFIX_ . 'product` pr ON pr.id_product = pas.id_product WHERE agl.name LIKE "hauteur" AND cp.id_category = 3 AND pr.active = 1');
        $heightOptions2 = Db::getInstance()->executeS('SELECT DISTINCT al.name FROM `' . _DB_PREFIX_ . 'attribute_lang` al LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON al.id_attribute = a.id_attribute LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON agl.id_attribute_group = a.id_attribute_group LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON al.id_attribute = pac.id_attribute LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas ON pas.id_product_attribute = pac.id_product_attribute LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON cp.id_product = pas.id_product LEFT JOIN `' . _DB_PREFIX_ . 'product` pr ON pr.id_product = pas.id_product WHERE agl.name LIKE "hauteur" AND cp.id_category = 2 AND pr.active = 1');
        $heightOptions128 = Db::getInstance()->executeS('SELECT DISTINCT al.name FROM `' . _DB_PREFIX_ . 'attribute_lang` al LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON al.id_attribute = a.id_attribute LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON agl.id_attribute_group = a.id_attribute_group LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON al.id_attribute = pac.id_attribute LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas ON pas.id_product_attribute = pac.id_product_attribute LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON cp.id_product = pas.id_product LEFT JOIN `' . _DB_PREFIX_ . 'product` pr ON pr.id_product = pas.id_product WHERE agl.name LIKE "hauteur" AND cp.id_category = 128 AND pr.active = 1');

        sort($heightOptions);
        sort($heightOptions3);
        sort($heightOptions2);
        sort($heightOptions128);

        $id2 = Db::getInstance()->getValue('SELECT id_search FROM ' . _DB_PREFIX_ . 'pm_advancedsearch_category WHERE id_category = 2 ORDER BY id_search DESC');
        $id3 = Db::getInstance()->getValue('SELECT id_search FROM ' . _DB_PREFIX_ . 'pm_advancedsearch_category WHERE id_category = 3 ORDER BY id_search DESC');
        $id128 = Db::getInstance()->getValue('SELECT id_search FROM ' . _DB_PREFIX_ . 'pm_advancedsearch_category WHERE id_category = 128 ORDER BY id_search DESC');

/*        $this->context->smarty->assign([
            'heights'=> $heightOptions,
        ]);*/

        Media::addJsDef([
            'heightOptions'=> $heightOptions,
            'heightOptions3' => $heightOptions3,
            'heightOptions2' => $heightOptions2,
            'heightOptions128' => $heightOptions128,
            'base_url' => Tools::getHttpHost(true),
            'id2_customfilter' => $id2,
            'id3_customfilter' => $id3,
            'id128_customfilter' => $id128,
        ]);

        return $this->fetch('module:ckccustomfilterselector/views/templates/hook/customfilterselector.tpl');
    }
}
