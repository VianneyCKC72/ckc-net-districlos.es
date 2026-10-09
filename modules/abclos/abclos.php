<?php
/**
 * AbClos — injection du snippet A/B testing.
 *
 * @author    Districlos
 * @copyright Districlos
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Abclos extends Module
{
    /** Clés de config actives (BO). */
    private $configKeys = [
        'ABCLOS_ENABLED',
        'ABCLOS_API_KEY',
        'ABCLOS_ENDPOINT',
        'ABCLOS_DEBUG',
    ];

    /** Anciennes clés à nettoyer à la désinstallation. */
    private $legacyConfigKeys = [
        'ABCLOS_ANTIFLICKER',
        'ABCLOS_TRACK_ADD_TO_CART',
        'ABCLOS_GOAL_ADD_TO_CART',
    ];

    public function __construct()
    {
        $this->name = 'abclos';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('AbClos');
        $this->description = $this->l('Injecte le snippet AbClos pour les expériences A/B (JS/CSS).');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->installDefaults();
    }

    public function uninstall()
    {
        foreach (array_merge($this->configKeys, $this->legacyConfigKeys) as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    private function installDefaults()
    {
        Configuration::updateValue('ABCLOS_ENABLED', 0);
        Configuration::updateValue('ABCLOS_API_KEY', '');
        Configuration::updateValue('ABCLOS_ENDPOINT', 'https://abclos.cloture-discount.fr');
        Configuration::updateValue('ABCLOS_DEBUG', 0);

        return true;
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitAbclosModule')) {
            $this->postProcess();
            if (empty($this->context->controller->errors)) {
                $output .= $this->displayConfirmation($this->l('Paramètres enregistrés.'));
            } else {
                foreach ($this->context->controller->errors as $error) {
                    $output .= $this->displayError($error);
                }
            }
        }

        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAbclosModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => (int) $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        $switchYesNo = [
            [
                'id' => 'active_on',
                'value' => 1,
                'label' => $this->l('Oui'),
            ],
            [
                'id' => 'active_off',
                'value' => 0,
                'label' => $this->l('Non'),
            ],
        ];

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration AbClos'),
                    'icon' => 'icon-flask',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activer'),
                        'name' => 'ABCLOS_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->l('Active le snippet et le tracking sur la boutique.'),
                        'values' => $switchYesNo,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Clé API'),
                        'name' => 'ABCLOS_API_KEY',
                        'desc' => $this->l('Clé du projet (dashboard AbClos).'),
                        'required' => true,
                        'col' => 6,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Endpoint'),
                        'name' => 'ABCLOS_ENDPOINT',
                        'desc' => $this->l('URL AbClos, sans slash final. Ex. https://abclos.cloture-discount.fr'),
                        'required' => true,
                        'col' => 6,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Mode debug'),
                        'name' => 'ABCLOS_DEBUG',
                        'is_bool' => true,
                        'desc' => $this->l('Logs console [AbClos]. À laisser désactivé en production.'),
                        'values' => $switchYesNo,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        return [
            'ABCLOS_ENABLED' => (int) Configuration::get('ABCLOS_ENABLED'),
            'ABCLOS_API_KEY' => (string) Configuration::get('ABCLOS_API_KEY'),
            'ABCLOS_ENDPOINT' => (string) Configuration::get('ABCLOS_ENDPOINT'),
            'ABCLOS_DEBUG' => (int) Configuration::get('ABCLOS_DEBUG'),
        ];
    }

    protected function postProcess()
    {
        $enabled = (int) Tools::getValue('ABCLOS_ENABLED');
        $apiKey = trim((string) Tools::getValue('ABCLOS_API_KEY'));
        $endpoint = rtrim(trim((string) Tools::getValue('ABCLOS_ENDPOINT')), '/');

        if ($enabled && $apiKey === '') {
            $this->context->controller->errors[] = $this->l('La clé API est obligatoire lorsque le module est activé.');
        }

        if ($endpoint === '' || !preg_match('#^https?://#i', $endpoint)) {
            $this->context->controller->errors[] = $this->l('L’endpoint doit commencer par http:// ou https://');
        }

        if (!empty($this->context->controller->errors)) {
            return;
        }

        Configuration::updateValue('ABCLOS_ENABLED', $enabled);
        Configuration::updateValue('ABCLOS_API_KEY', $apiKey);
        Configuration::updateValue('ABCLOS_ENDPOINT', $endpoint);
        Configuration::updateValue('ABCLOS_DEBUG', (int) Tools::getValue('ABCLOS_DEBUG'));
    }

    /**
     * Langue EN du shop : pas de snippet / tracking AbClos.
     * Priorité iso_code Presta ; fallback path /en (sans matcher /energy etc.).
     */
    private function isEnglishFront()
    {
        if (isset($this->context->language->iso_code)
            && Tools::strtolower((string) $this->context->language->iso_code) === 'en'
        ) {
            return true;
        }

        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';

        return $path === '/en' || strpos($path, '/en/') === 0;
    }

    /**
     * Employé BO connecté (cookie psAdmin) : pas de tracking AbClos.
     * Même logique que IS_EMPLOYEE_BO dans FrontController.
     */
    private function isEmployeeFront()
    {
        $cookie = new Cookie('psAdmin', '', (int) Configuration::get('PS_COOKIE_LIFETIME_BO'));

        return !empty($cookie->id_employee);
    }

    /**
     * Injection snippet + JS panier / revenue.
     */
    public function hookDisplayHeader($params)
    {
        if (!(int) Configuration::get('ABCLOS_ENABLED')) {
            return '';
        }

        if ($this->isEnglishFront() || $this->isEmployeeFront()) {
            return '';
        }

        $apiKey = trim((string) Configuration::get('ABCLOS_API_KEY'));
        $endpoint = rtrim(trim((string) Configuration::get('ABCLOS_ENDPOINT')), '/');

        if ($apiKey === '' || $endpoint === '') {
            return '';
        }

        $jsOptions = [
            'position' => 'bottom',
            'priority' => 200,
            'attributes' => 'defer',
        ];

        $this->context->controller->registerJavascript(
            'module-abclos-track-cart',
            'modules/' . $this->name . '/views/js/track-cart.js',
            $jsOptions
        );
        $this->context->controller->registerJavascript(
            'module-abclos-track-order',
            'modules/' . $this->name . '/views/js/track-order.js',
            $jsOptions
        );

        $this->context->smarty->assign([
            'abclos_endpoint' => $endpoint,
            'abclos_snippet_url' => $endpoint . '/snippet.js',
            'abclos_api_key' => $apiKey,
            'abclos_debug' => (int) Configuration::get('ABCLOS_DEBUG'),
        ]);

        return $this->display(__FILE__, 'views/templates/front/hook/snippet.tpl');
    }
}
