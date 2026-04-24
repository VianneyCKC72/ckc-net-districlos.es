<?php
/**
* 2007-2024 PrestaShop
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
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckcgrouppricecomparator extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ckcgrouppricecomparator';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'ckc-net';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Comparateur des prix selon des groupes des clients');
        $this->description = $this->l('Ce module permet de comparer des tarifs des produits selon des groupes de clients et afficher des produits pro qui sont plus cher que ceux en particulier');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CKCGROUPPRICECOMPARATOR_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CKCGROUPPRICECOMPARATOR_LIVE_MODE');

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
        if (((bool)Tools::isSubmit('submitCkcgrouppricecomparatorModule')) == true) {
            $this->postProcess();
        }

        if (preg_match("/^1.6/", _PS_VERSION_)) {
            $cron_url_track = Tools::getHttpHost(true) . __PS_BASE_URI__ . 'index.php?fc=module&module=ckcgrouppricecomparator&controller=checkProPrices?token='.substr(Tools::encrypt('ckcgrouppricecomparator/checkProPrices'), 0, 10);
        } else {
            $cron_url_track = Tools::getHttpHost(true) . __PS_BASE_URI__ . 'module/ckcgrouppricecomparator/checkProPrices?token='.substr(Tools::encrypt('ckcgrouppricecomparator/checkProPrices'), 0, 10);
        }

        $id_shop     = Context::getContext()->shop->id;
        $id_currency = Context::getContext()->currency->id;
        $id_country  = Context::getContext()->country->id;
        $id_lang     = Context::getContext()->language->id;

        // Prix et nom récupérés directement — évite new Product() dans la boucle
        $allproducts = Db::getInstance()->executeS(
            'SELECT p.id_product, p.price, pl.name
            FROM ' . _DB_PREFIX_ . 'product p
            LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl
                ON pl.id_product = p.id_product AND pl.id_lang = ' . (int)$id_lang . ' AND pl.id_shop = ' . (int)$id_shop
        );

        $specificPrice = new SpecificPrice(); // Une seule instance hors boucle
        $productsResult = [];

        foreach ($allproducts as $key => $product) {
            $pricePro = $specificPrice->getSpecificPrice($product['id_product'], $id_shop, $id_currency, $id_country, 10, 1, null);
            // dump($pricePro);

            if (isset($pricePro['id_product_attribute']) && $pricePro['id_product_attribute'] != 0) {
                // Prix de la combinaison via requête directe — évite new CombinationCore()
                $combinationPrice = (float)Db::getInstance()->getValue(
                    'SELECT price FROM ' . _DB_PREFIX_ . 'product_attribute WHERE id_product_attribute = ' . (int)$pricePro['id_product_attribute']
                );
                $productVariantPrice = (float)$product['price'] + $combinationPrice;

                if (null != $pricePro && $pricePro['price'] > $productVariantPrice && $pricePro['id_group'] == 10) {
                    $productsResult[$key]['id_product'] = $product['id_product'];
                    $productsResult[$key]['pricePro']   = number_format($pricePro['price'], 2);

                    $attributes = Db::getInstance()->executeS(
                        'SELECT al.name FROM ' . _DB_PREFIX_ . 'attribute_lang al
                        INNER JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac ON pac.id_attribute = al.id_attribute
                        WHERE pac.id_product_attribute = ' . (int)$pricePro['id_product_attribute'] . '
                        AND al.id_lang = ' . (int)$id_lang
                    );
                    $tempName = $product['name'];
                    foreach ($attributes as $attribute) {
                        $tempName .= ' - ' . $attribute['name'];
                    }
                    $productsResult[$key]['name']          = $tempName;
                    $productsResult[$key]['priceStandard'] = number_format($productVariantPrice, 2);
                    $productsResult[$key]['reduction']     = number_format($pricePro['price'] - $productVariantPrice, 2);
                }
            } else {
                if (null != $pricePro && $pricePro['price'] > (float)$product['price'] && $pricePro['id_group'] == 10) {
                    $productsResult[$key]['id_product']    = $product['id_product'];
                    $productsResult[$key]['pricePro']      = number_format($pricePro['price'], 2);
                    $productsResult[$key]['name']          = $product['name'];
                    $productsResult[$key]['priceStandard'] = number_format((float)$product['price'], 2);
                    $productsResult[$key]['reduction']     = number_format($pricePro['price'] - (float)$product['price'], 2);
                }
            }
        }

        // dump($productsResult);

        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
            'productsResult' => $productsResult,
            'cron_url_track' => $cron_url_track,
            'currency' => Context::getContext()->currency->sign,
        ));

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
        $helper->submit_action = 'submitCkcgrouppricecomparatorModule';
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
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Envoyer des alertes email à cette adresse'),
                        'name' => 'CKCGROUPPRICECOMPARATOR_ALERT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Envoyer des alertes email à cette adresse'),
                        'name' => 'CKCGROUPPRICECOMPARATOR_ALERT_EMAIL_2',
                        'label' => $this->l('Email 2'),
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
            'CKCGROUPPRICECOMPARATOR_ALERT_EMAIL' => Configuration::get('CKCGROUPPRICECOMPARATOR_ALERT_EMAIL', null),
            'CKCGROUPPRICECOMPARATOR_ALERT_EMAIL_2' => Configuration::get('CKCGROUPPRICECOMPARATOR_ALERT_EMAIL_2', null),
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
}
