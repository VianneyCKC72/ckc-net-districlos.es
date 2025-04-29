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

class Ckc_yourgpt extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ckc_yourgpt';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'ckc-net';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Your GPT');
        $this->description = $this->l('Your GPT widget from ckc');

        $this->ps_versions_compliancy = array('min' => '1.7.5.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        Configuration::updateValue('CKC_YOURGPT_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayBeforeBodyClosingTag');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CKC_YOURGPT_LIVE_MODE');

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
        if (((bool)Tools::isSubmit('submitCkc_yourgptModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign([
            'module_dir' => $this->_path
        ]);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCkc_yourgptModule';
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

    public function getOptions() {

        $options_week = array(
            array(
              'id_option' => 1,  
              'name' => $this->l('Monday'),
              'value' => 'Monday'
            ),
            array(
              'id_option' => 2,
              'name' => $this->l('Tuesday'),
              'value' => 'Tuesday'
            ),
            array(
                'id_option' => 3,
                'name' => $this->l('Wednesday'),
                'value' => 'Wednesday'
              ),
              array(
                'id_option' => 4,
                'name' => $this->l('Thursday'),
                'value' => 'Thursday'
              ),
              array(
                'id_option' => 5,
                'name' => $this->l('Friday'),
                'value' => 'Friday'
              ),
              array(
                'id_option' => 6,
                'name' => $this->l('Saturday'),
                'value' => 'Saturday'
              ),
              array(
                'id_option' => 7,
                'name' => $this->l('Sunday'),
                'value' => 'Sunday'
              ),
          );
          return $options_week;
    }

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
                        'label' => $this->l('Active'),
                        'name' => 'CKC_YOURGPT_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Enable the module'),
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
                        'desc' => $this->l('Enter a valid ID'),
                        'name' => 'CKC_YOURGPT_ACCOUNT_ID',
                        'label' => $this->l('ID'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Show the chat bot starting from this hour : put an integer from 0 to 24'),
                        'name' => 'CKC_YOURGPT_HOUR_START',
                        'label' => $this->l('Starting hour time for showing the chat bot (from 0 to 24)'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Hide the chat bot after this hour : put an integer from 0 to 24'),
                        'name' => 'CKC_YOURGPT_HOUR_END',
                        'label' => $this->l('Last hour time for showing the chat bot (from 0 to 24)')
                    ),
                    array(
                        'type'    => 'checkbox',                   // This is an <input type="checkbox"> tag.
                        'label'   => $this->l('Show the chat bot these days of the week'),          // The <label> for this <input> tag.
                        'desc'    => $this->l('The chat bot is visible only these days of the week'),  // A help text, displayed right next to the <input> tag.
                        'name'    => 'CKC_YOURGPT_ACTIVE_DAYS',                    // The content of the 'id' attribute of the <input> tag.
                        'values'  => array(
                          'query' => $this->getOptions(),                     // $options contains the data itself.
                          'id'    => 'id_option',                  // The value of the 'id' key must be the same as the key
                                                                   // for the 'value' attribute of the <option> tag in each $options sub-array.
                          'name'  => 'name'                        // The value of the 'name' key must be the same as the key
                                                                   // for the text content of the <option> tag in each $options sub-array.
                        ),
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'CKC_YOURGPT_ACTIVE_DAYS_SELECTED'
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
            'CKC_YOURGPT_ACTIVE' => Configuration::get('CKC_YOURGPT_ACTIVE', true),
            'CKC_YOURGPT_ACCOUNT_ID' => Configuration::get('CKC_YOURGPT_ACCOUNT_ID', null),
            'CKC_YOURGPT_HOUR_START' => Configuration::get('CKC_YOURGPT_HOUR_START', null),
            'CKC_YOURGPT_HOUR_END' => Configuration::get('CKC_YOURGPT_HOUR_END', null),
            'CKC_YOURGPT_ACTIVE_DAYS' => Configuration::get('CKC_YOURGPT_ACTIVE_DAYS', null),
            'CKC_YOURGPT_ACTIVE_DAYS_1' => Configuration::get('CKC_YOURGPT_ACTIVE_DAYS_1', null),
            'CKC_YOURGPT_ACTIVE_DAYS_2' => Configuration::get('CKC_YOURGPT_ACTIVE_DAYS_2', null),
            'CKC_YOURGPT_ACTIVE_DAYS_3' => Configuration::get('CKC_YOURGPT_ACTIVE_DAYS_3', null),
            'CKC_YOURGPT_ACTIVE_DAYS_4' => Configuration::get('CKC_YOURGPT_ACTIVE_DAYS_4', null),
            'CKC_YOURGPT_ACTIVE_DAYS_5' => Configuration::get('CKC_YOURGPT_ACTIVE_DAYS_5', null),
            'CKC_YOURGPT_ACTIVE_DAYS_6' => Configuration::get('CKC_YOURGPT_ACTIVE_DAYS_6', null),
            'CKC_YOURGPT_ACTIVE_DAYS_7' => Configuration::get('CKC_YOURGPT_ACTIVE_DAYS_7', null),
            'CKC_YOURGPT_ACTIVE_DAYS_SELECTED' => Configuration::get('CKC_YOURGPT_ACTIVE_DAYS_SELECTED', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {

        if (!preg_match("/^\d+$/", Tools::getValue('CKC_YOURGPT_HOUR_START')) || Tools::getValue('CKC_YOURGPT_HOUR_START') >= 25 || Tools::getValue('CKC_YOURGPT_HOUR_START') < 0) {
            $error_starting_tracking_hour = true;
        } else $error_starting_tracking_hour = false;

        $error_last_tracking_hour = true ? !preg_match("/^\d+$/", Tools::getValue('CKC_YOURGPT_HOUR_END')) || Tools::getValue('CKC_YOURGPT_HOUR_END') >= 25 || Tools::getValue('CKC_YOURGPT_HOUR_END') < 0 : false;

        $error_diff_tracking_hours = true ? Tools::getValue('CKC_YOURGPT_HOUR_END') <= Tools::getValue('CKC_YOURGPT_HOUR_START') : false;

        $this->context->smarty->assign([
            'error_starting_tracking_hour' => $error_starting_tracking_hour,
            'error_last_tracking_hour' => $error_last_tracking_hour,
            'error_diff_tracking_hours' => $error_diff_tracking_hours
        ]);


        if (!$error_starting_tracking_hour && !$error_last_tracking_hour && !$error_diff_tracking_hours) {

            $form_values = $this->getConfigFormValues();

            foreach (array_keys($form_values) as $key) {
                Configuration::updateValue($key, Tools::getValue($key));
            }

            $all_opts = $this->getOptions();

            $checkbox_options = array();


            foreach ($all_opts as $chbx_options) {

                if (Tools::getValue('CKC_YOURGPT_ACTIVE_DAYS_'.(int)$chbx_options['id_option'])) {
                    $checkbox_options[] = $chbx_options['id_option'];
                }
            }

            Configuration::updateValue('CKC_YOURGPT_ACTIVE_DAYS_SELECTED', implode(",", $checkbox_options));
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
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    private function String_Contains($haystack, $needle): bool {
        if ( is_string($haystack) && is_string($needle) ) {
            return '' === $needle || false !== strpos($haystack, $needle);
        } else {
            return false;
        }
    }

    public function hookDisplayBeforeBodyClosingTag() {
        // Vérifier si le script a déjà été inclus

        $current_hour = date("H", time());

        if ($current_hour >= Configuration::get('CKC_YOURGPT_HOUR_START') && $current_hour < Configuration::get('CKC_YOURGPT_HOUR_END')) {
            $match_schedule = true;
        } else $match_schedule = false;

        $your_gpt_current_day_week = date('N', time());

        $your_gpt_chosen_days = Configuration::get('CKC_YOURGPT_ACTIVE_DAYS_SELECTED');

        $your_gpt_found_match = true ? $this->String_Contains($your_gpt_chosen_days, $your_gpt_current_day_week) : false;

        if (Configuration::get('CKC_YOURGPT_ACTIVE') && $your_gpt_found_match && $match_schedule) {
            $show_your_gpt_link = true;
        } else $show_your_gpt_link = false;

        $gpt_link_id = Configuration::get('CKC_YOURGPT_ACCOUNT_ID');

        $this->context->smarty->assign([
            'show_your_gpt_link' => $show_your_gpt_link,
            'gpt_link_id' => $gpt_link_id,
            'your_gpt_found_match' => $your_gpt_found_match,
            'gptlinkscript' => $this->context->link->getModuleLink('gptlinkscript', 'display')
        ]);

        // Marquer le script comme inclus
        $this->context->cookie->gpt_script_included = true;
        return $this->display(__FILE__, 'gptlinkscript.tpl');
    }
}
