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

class Ckcabtest extends Module
{
    protected $config_form = false;
    private const TIME_PATTERN = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';

    public function __construct()
    {
        $this->name = 'ckcabtest';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'ckc-net';
        $this->logo_path     = $this->_path . 'logo.png';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ckc Activation AB Test');
        $this->description = $this->l('Active un test A/B et injecte un script externe (ABlyft).');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Install module + valeurs par défaut + hooks
     */
    public function install()
    {
        Configuration::updateValue('CKCABTEST_ACTIVE', false);
        Configuration::updateValue('CKCABTEST_SCRIPT', '');
        Configuration::updateValue('CKCABTEST_ABLYFT_ID', '50855186.js'); // valeur par défaut
        Configuration::updateValue('CKCABTEST_DATE_START', '');          // vide = pas de restriction
        Configuration::updateValue('CKCABTEST_DATE_END', '');            // vide = pas de restriction
        Configuration::updateValue('CKCABTEST_TIME_START', '00:00');     // HH:MM
        Configuration::updateValue('CKCABTEST_TIME_END', '23:59');       // HH:MM

        return parent::install()
               && $this->registerHook('displayHeader')
               && $this->registerHook('displayBackOfficeHeader');
    }

    /**
     * Uninstall + cleanup
     */
    public function uninstall()
    {
        Configuration::deleteByName('CKCABTEST_ACTIVE');
        Configuration::deleteByName('CKCABTEST_SCRIPT');
        Configuration::deleteByName('CKCABTEST_ABLYFT_ID');
        Configuration::deleteByName('CKCABTEST_DATE_START');
        Configuration::deleteByName('CKCABTEST_DATE_END');
        Configuration::deleteByName('CKCABTEST_TIME_START');
        Configuration::deleteByName('CKCABTEST_TIME_END');

        return parent::uninstall();
    }

    /**
     * BO config page
     */
    public function getContent()
    {
        if (Tools::isSubmit('submitCkcabtestModule')) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * HelperForm builder
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int)$this->context->language->id;
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCkcabtestModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                                .'&configure='.$this->name
                                .'&tab_module='.$this->tab
                                .'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => (int)$this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Champs BO
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Paramètres A/B test'),
                    'icon'  => 'icon-cogs',
                ),
                'input' => array(

                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Activer le test'),
                        'name'    => 'CKCABTEST_ACTIVE',
                        'is_bool' => true,
                        'desc'    => $this->l('Module activé ou désactivé'),
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Oui'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Non'),
                            ),
                        ),
                    ),

                    array(
                        'type'  => 'text',
                        'label' => $this->l('ID du script ABlyft'),
                        'name'  => 'CKCABTEST_ABLYFT_ID',
                        'desc'  => $this->l('Exemple : 50855186.js. Si vide, utilisera 50855186.js par défaut.'),
                        'col'   => 4,
                    ),

                    array(
                        'type'  => 'date',
                        'label' => $this->l('Date de début'),
                        'name'  => 'CKCABTEST_DATE_START',
                        'desc'  => $this->l('Format AAAA-MM-JJ. Laisser vide = actif immédiatement.'),
                        'col'   => 3,
                    ),

                    array(
                        'type'        => 'text',
                        'label'       => $this->l('Heure de début'),
                        'name'        => 'CKCABTEST_TIME_START',
                        'placeholder' => 'HH:MM',
                        'desc'        => $this->l('Format HH:MM (ex : 08:30). Utilisé uniquement si une date de début est renseignée.'),
                        'col'         => 2,
                    ),

                    array(
                        'type'  => 'date',
                        'label' => $this->l('Date de fin'),
                        'name'  => 'CKCABTEST_DATE_END',
                        'desc'  => $this->l('Format AAAA-MM-JJ. Laisser vide = pas de fin.'),
                        'col'   => 3,
                    ),

                    array(
                        'type'        => 'text',
                        'label'       => $this->l('Heure de fin'),
                        'name'        => 'CKCABTEST_TIME_END',
                        'placeholder' => 'HH:MM',
                        'desc'        => $this->l('Format HH:MM (ex : 18:00). Utilisé uniquement si une date de fin est renseignée.'),
                        'col'         => 2,
                    ),

                    array(
                        'col'   => 7,
                        'type'  => 'textarea',
                        'rows'  => 6,
                        'desc'  => $this->l('JS personnalisé à injecter (optionnel).'),
                        'name'  => 'CKCABTEST_SCRIPT',
                        'label' => $this->l('Script JS personnalisé'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Enregistrer'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {
        return array(
            'CKCABTEST_ACTIVE'      => (int)Configuration::get('CKCABTEST_ACTIVE', 0),
            'CKCABTEST_SCRIPT'      => (string)Configuration::get('CKCABTEST_SCRIPT', ''),
            'CKCABTEST_ABLYFT_ID'   => (string)Configuration::get('CKCABTEST_ABLYFT_ID', '50855186.js'),
            'CKCABTEST_DATE_START'  => (string)Configuration::get('CKCABTEST_DATE_START', ''),
            'CKCABTEST_DATE_END'    => (string)Configuration::get('CKCABTEST_DATE_END', ''),
            'CKCABTEST_TIME_START'  => (string)Configuration::get('CKCABTEST_TIME_START', '00:00'),
            'CKCABTEST_TIME_END'    => (string)Configuration::get('CKCABTEST_TIME_END', '23:59'),
        );
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        $timeStart = trim((string) Tools::getValue('CKCABTEST_TIME_START', '00:00'));
        $timeEnd = trim((string) Tools::getValue('CKCABTEST_TIME_END', '23:59'));

        if ($timeStart === '') {
            $timeStart = '00:00';
        }
        if ($timeEnd === '') {
            $timeEnd = '23:59';
        }

        if (!preg_match(self::TIME_PATTERN, $timeStart)) {
            $this->context->controller->errors[] = $this->l('Le format de "Heure de début" est invalide. Utilisez HH:MM (ex : 08:30).');
        }
        if (!preg_match(self::TIME_PATTERN, $timeEnd)) {
            $this->context->controller->errors[] = $this->l('Le format de "Heure de fin" est invalide. Utilisez HH:MM (ex : 18:00).');
        }

        if (!empty($this->context->controller->errors)) {
            return;
        }

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        Configuration::updateValue('CKCABTEST_TIME_START', $timeStart);
        Configuration::updateValue('CKCABTEST_TIME_END', $timeEnd);
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') === $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    public function hookDisplayHeader()
    {
        if (!Configuration::get('CKCABTEST_ACTIVE')) {
            return;
        }

        $this->context->controller->addJS($this->_path.'views/js/front.js');
        // $this->context->controller->addCSS($this->_path.'views/css/front.css');

        // Récup conf
        $idRaw      = trim(Configuration::get('CKCABTEST_ABLYFT_ID'));
        $dateStart  = trim(Configuration::get('CKCABTEST_DATE_START'));
        $dateEnd    = trim(Configuration::get('CKCABTEST_DATE_END'));
        $timeStart  = trim(Configuration::get('CKCABTEST_TIME_START', '00:00'));
        $timeEnd    = trim(Configuration::get('CKCABTEST_TIME_END', '23:59'));
        $customJs   = Configuration::get('CKCABTEST_SCRIPT');

        if ($idRaw === '') {
            $idRaw = '50855186.js';
        }

        $scriptUrl = 'https://cdn.ablyft.com/s/'.$idRaw;

        $inDateWindow = true;

        if ($dateStart !== '') {
            $startTime = preg_match(self::TIME_PATTERN, $timeStart) ? $timeStart : '00:00';
            $startDt = \DateTime::createFromFormat('Y-m-d H:i', $dateStart . ' ' . $startTime);
            if ($startDt instanceof \DateTime) {
                $now = new \DateTime('now');
                if ($now->getTimestamp() < $startDt->getTimestamp()) {
                    $inDateWindow = false;
                }
            }
        }

        if ($dateEnd !== '') {
            $endTime = preg_match(self::TIME_PATTERN, $timeEnd) ? $timeEnd : '23:59';
            $endDt = \DateTime::createFromFormat('Y-m-d H:i', $dateEnd . ' ' . $endTime);
            if ($endDt instanceof \DateTime) {
                $now = isset($now) && $now instanceof \DateTime ? $now : new \DateTime('now');
                if ($now->getTimestamp() > $endDt->getTimestamp()) {
                    $inDateWindow = false;
                }
            }
        }

        $abtest_active = $inDateWindow;

        $this->context->smarty->assign(array(
                                           'abtest_active'      => $abtest_active,
                                           'abtest_script_url'  => $scriptUrl,
                                           'abtest_id'          => $idRaw,
                                           'abtest_date_start'  => $dateStart,
                                           'abtest_date_end'    => $dateEnd,
                                       ));

        if ($abtest_active && !empty($customJs)) {
            $this->context->smarty->assign('CKCABTEST_SCRIPT', $customJs);

            return $this->display(__FILE__, 'views/templates/front/hook/script.tpl');
        }
        return;
    }
}
