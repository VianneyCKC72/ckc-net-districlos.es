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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2024 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/controllers/admin/AdminBarrePromoTabController.php';

/**
 * @since 18-01-2022
 * @author Vianney - CKC
 */

class Ckcpromotionbanner extends Module
{
    protected $config_form = false;


    public function __construct()
    {
        $this->name = 'ckcpromotionbanner';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'Agence CKC';
        $this->need_instance = 1;
        $this->className = 'AdminManagesBarrePromo';

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Promotion banner');
        $this->description = $this->l('Generate a customizable promo banner with a timer with the help of this module.');

        $this->confirmUninstall = $this->l('Are you sure ? ');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

    }

    /**
     * @return bool
     */
    public function callInstallTab()
    {
        $this->installTab('AdminBarrePromoTab', 'Promotion Banner', 'AdminParentThemes');

        return true;
    }


    /**
     * Install admin tabs
     *
     * @param string $className
     * @param string $tabName
     * @param bool $tabParentName
     *
     * @return bool
     */
    public function installTab($className, $tabName, $tabParentName = false)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = array();

        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Promotion Banner');
        }

        if ($tabParentName) {
            $tab->id_parent = (int) Tab::getIdFromClassName($tabParentName);
        } else {
            $tab->id_parent = 0;
        }

        $tab->module = $this->name;

        return $tab->add();
    }


    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CKC_BARREPROMO_DISPLAY', false);
        Configuration::updateValue('CKC_BARREPROMO_DISPLAY_BOTTOM', false);
        Configuration::updateValue('CKC_BARREPROMO_TEXTE', false);
        Configuration::updateValue('CKC_BARREPROMO_COLOR', false);
        Configuration::updateValue('CKC_BARREPROMO_TIMER', false);
        Configuration::updateValue('CKC_BARREPROMO_TIMER_COLOR', false);
        Configuration::updateValue('CKC_BARREPROMO_TIMER_DISPLAY', false);

        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->callInstallTab() &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayAfterBodyOpeningTag');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CKC_BARREPROMO_DISPLAY_BOTTOM');
        Configuration::deleteByName('CKC_BARREPROMO_DISPLAY');
        Configuration::deleteByName('CKC_BARREPROMO_TEXTE');
        Configuration::deleteByName('CKC_BARREPROMO_COLOR');
        Configuration::deleteByName('CKC_BARREPROMO_TIMER');
        Configuration::deleteByName('CKC_BARREPROMO_TIMER_COLOR');
        Configuration::deleteByName('CKC_BARREPROMO_TIMER_DISPLAY');

        $this->uninstallTab();

        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Uninstall admin tabs
     *
     * @return bool
     */
    public function uninstallTab()
    {
        $moduleTabs = Tab::getCollectionFromModule($this->name);
        if (!empty($moduleTabs)) {
            foreach ($moduleTabs as $moduleTab) {
                $moduleTab->delete();
            }
        }

        return true;
    }


    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitCkc_barrepromoModule')) == true) {
            $this->postProcess();
        }

        if (((bool)\Tools::isSubmit('id_promotexts')) == true) {
            $this->saveExceptions();

            // Rafraîchir la page
            Tools::redirectAdmin($_SERVER['REQUEST_URI']);
        }

        if (((bool)\Tools::isSubmit('id_promotextsPro')) == true) {
            $this->saveExceptionsPro();

            // Rafraîchir la page
            Tools::redirectAdmin($_SERVER['REQUEST_URI']);
        }

        $moduleAdminLink = $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]);

        $idPromotextsResult = \Configuration::get('CKC_BARREPROMO_TEXTE');


        if(null !== $idPromotextsResult && '' !== $idPromotextsResult ) {
            $idPromotexts = unserialize(base64_decode($idPromotextsResult));
            $promotexts = implode(",", $idPromotexts);
        } else {
            $promotexts = '';
        }

        $idPromotextsResultPro = \Configuration::get('CKC_BARREPROMO_TEXTE_PRO');


        if(null !== $idPromotextsResultPro && '' !== $idPromotextsResultPro ) {
            $idPromotextsPro = unserialize(base64_decode($idPromotextsResultPro));
            $promotextsPro = implode(",", $idPromotextsPro);
        } else {
            $promotextsPro = '';
        }

        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
            'moduleAdminLink' => $moduleAdminLink,
            'promotexts' => $promotexts,
            'promotextsPro' => $promotextsPro
        ));



        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    public function saveExceptions(){
        $exceptions = \Tools::getValue('id_promotexts');
        if(null !== $exceptions) {
            $arr = explode(',', $exceptions);
            $tableauIdsExclure = [];
            foreach ($arr as $exception) {
                if($exception !== ''){
                    $tableauIdsExclure[] = trim($exception);
                }
            }
            $serializedData = base64_encode(serialize($tableauIdsExclure));
            Configuration::updateValue('CKC_BARREPROMO_TEXTE', $serializedData);
        }
    }

    public function saveExceptionsPro(){
        $exceptions = \Tools::getValue('id_promotextsPro');
        if(null !== $exceptions) {
            $arr = explode(',', $exceptions);
            $tableauIdsExclure = [];
            foreach ($arr as $exception) {
                if($exception !== ''){
                    $tableauIdsExclure[] = trim($exception);
                }
            }
            $serializedData = base64_encode(serialize($tableauIdsExclure));
            Configuration::updateValue('CKC_BARREPROMO_TEXTE_PRO', $serializedData);
        }
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
        $helper->submit_action = 'submitCkc_barrepromoModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
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
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show the promotion banner'),
                        'name' => 'CKC_BARREPROMO_DISPLAY',
                        'is_bool' => true,
                        'desc' => $this->l('The promotion banner will be displayed at the top or bottom of the page'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l("Show")
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l("Hide")
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show at bottom of page'),
                        'name' => 'CKC_BARREPROMO_DISPLAY_BOTTOM',
                        'is_bool' => true,
                        'desc' => $this->l('By default, the promotion banner is displayed at the top of the page'),
                        'values' => array(
                            array(
                                'id' => 'bottom_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'bottom_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_COLOR',
                        'label' => $this->l('Background color of the banner'),
                        'desc' => $this->l('Choose the background color of the banner'),
                        'type' => 'color',
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_TIMER',
                        'label' => $this->l('Deadline'),
                        'desc' => $this->l('Promotion expiration date, will be displayed as a timer.'),
                        'type' => 'datetime',
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_TIMER_COLOR',
                        'label' => $this->l('Text color of the timer'),
                        'desc' => $this->l('Choose the Text color of the timer'),
                        'type' => 'color',
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_TIMER_DISPLAY',
                        'label' => $this->l("Display a timer"),
                        'align' => 'text-left',
                        'type' => 'switch',
                        'class' => 'fixed-width-lg',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'timer_on',
                                'value' => true,
                                'label' => $this->l('Show')
                            ),
                            array(
                                'id' => 'timer_off',
                                'value' => false,
                                'label' => $this->l('Hide')
                            )
                        ),
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
            'CKC_BARREPROMO_DISPLAY' => Configuration::get('CKC_BARREPROMO_DISPLAY', false),
            'CKC_BARREPROMO_DISPLAY_BOTTOM' => Configuration::get('CKC_BARREPROMO_DISPLAY_BOTTOM', false),
            'CKC_BARREPROMO_COLOR' => Configuration::get('CKC_BARREPROMO_COLOR',false),
            'CKC_BARREPROMO_TIMER' => Configuration::get('CKC_BARREPROMO_TIMER', false),
            'CKC_BARREPROMO_TIMER_COLOR' => Configuration::get('CKC_BARREPROMO_TIMER_COLOR', false),
            'CKC_BARREPROMO_TIMER_DISPLAY' => Configuration::get('CKC_BARREPROMO_TIMER_DISPLAY', false),
        );
    }
    protected function getConfigFormValuesFront()
    {
        return array(
            'CKC_BARREPROMO_DISPLAY' => Configuration::get('CKC_BARREPROMO_DISPLAY', false),
            'CKC_BARREPROMO_DISPLAY_BOTTOM' => Configuration::get('CKC_BARREPROMO_DISPLAY_BOTTOM', false),
            'CKC_BARREPROMO_COLOR' => Configuration::get('CKC_BARREPROMO_COLOR',false),
            'CKC_BARREPROMO_TIMER' => Configuration::get('CKC_BARREPROMO_TIMER', false),
            'CKC_BARREPROMO_TEXTE' => Configuration::get('CKC_BARREPROMO_TEXTE',false),
            'CKC_BARREPROMO_TEXTE_PRO' => Configuration::get('CKC_BARREPROMO_TEXTE_PRO',false),
            'CKC_BARREPROMO_TIMER_COLOR' => Configuration::get('CKC_BARREPROMO_TIMER_COLOR', false),
            'CKC_BARREPROMO_TIMER_DISPLAY' => Configuration::get('CKC_BARREPROMO_TIMER_DISPLAY', false),
        );
    }
    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        $p_open = '<' . 'p' . '>';
        $p_close = '</' . 'p' . '>';
        foreach (array_keys($form_values) as $key) {
            $value = str_replace([$p_open, $p_close], '', Tools::getValue($key));
            Configuration::updateValue($key, $value, true);
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * @author Vianney
     *         Print all values in the front template
     * @return mixed
     */
    public function hookDisplayAfterBodyOpeningTag()
    {
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
        $this->context->controller->addJS($this->_path . '/views/js/front.js');

        $form_values = $this->getConfigFormValuesFront();
        $addClass = '';
        if(Configuration::get('CKC_BARREPROMO_DISPLAY_BOTTOM') == true) {
            $addClass .= 'bottom';
        }

        foreach (array_keys($form_values) as $key) {
            if($key == 'CKC_BARREPROMO_TEXTE'){
                $idPromotextsResult = Configuration::get($key);

                if(null !== $idPromotextsResult && '' !== $idPromotextsResult ) {
                    $promotexts = unserialize(base64_decode($idPromotextsResult));
                } else {
                    $promotexts = '';
                }


                $this->smarty->assign('ckcpromobarretexts', $promotexts);

            } elseif ($key == 'CKC_BARREPROMO_TEXTE_PRO') {
                $idPromotextsResultPro = Configuration::get($key);

                if(null !== $idPromotextsResultPro && '' !== $idPromotextsResultPro ) {
                    $promotextsPro = unserialize(base64_decode($idPromotextsResultPro));
                } else {
                    $promotextsPro = '';
                }


                $this->smarty->assign('ckcpromobarretextsPro', $promotextsPro);
            } else {
                $this->smarty->assign($key, Configuration::get($key));
            }

        }

        $this->smarty->assign('class', $addClass);

        $today = new DateTime();
        $timer = null;
        if(!empty(Configuration::get('CKC_BARREPROMO_TIMER'))) {
            $timer = new DateTime(Configuration::get('CKC_BARREPROMO_TIMER'));
        }
        if(Configuration::get('CKC_BARREPROMO_DISPLAY') == 1) {
            if(($today < $timer) || $timer == null) {
                return $this->display(__FILE__, 'views/templates/front/alert.tpl');
            } else {
                return $this->display(__FILE__, 'views/templates/front/alert.tpl');
            }
        } else {
            return $this->display(__FILE__, 'views/templates/front/alert.tpl');
        }
    }
}
