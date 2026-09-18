<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class dtc_cart_reservation extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_cart_reservation';
        $this->tab = 'front_office_features';
        $this->version = '2.0.1';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Bandeau réservation panier');
        $this->description = $this->l('Affiche un compte à rebours visuel de réservation des produits dans le panier.');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->installConfiguration()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayShoppingCartFooter');
    }

    public function uninstall()
    {
        Configuration::deleteByName('DTC_CART_RESERVATION_ACTIVE');
        Configuration::deleteByName('DTC_CART_RESERVATION_DURATION');

        return parent::uninstall();
    }

    private function installConfiguration()
    {
        return Configuration::updateValue('DTC_CART_RESERVATION_ACTIVE', 1)
            && Configuration::updateValue('DTC_CART_RESERVATION_DURATION', 15);
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDtcCartReservation')) {
            $this->postProcess();
            if (empty($this->context->controller->errors)) {
                $output .= $this->displayConfirmation($this->l('Configuration enregistrée.'));
            }
        }

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
        $helper->submit_action = 'submitDtcCartReservation';
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
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Paramètres'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activer le bandeau'),
                        'name' => 'DTC_CART_RESERVATION_ACTIVE',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Durée du compte à rebours (minutes)'),
                        'name' => 'DTC_CART_RESERVATION_DURATION',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Durée visuelle avant disparition du bandeau (ex : 15).'),
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
            'DTC_CART_RESERVATION_ACTIVE' => (int) Configuration::get('DTC_CART_RESERVATION_ACTIVE', 1),
            'DTC_CART_RESERVATION_DURATION' => (int) Configuration::get('DTC_CART_RESERVATION_DURATION', 15),
        ];
    }

    protected function postProcess()
    {
        $duration = (int) Tools::getValue('DTC_CART_RESERVATION_DURATION', 15);
        if ($duration <= 0) {
            $this->context->controller->errors[] = $this->l('La durée doit être supérieure à 0 minute.');

            return;
        }

        Configuration::updateValue('DTC_CART_RESERVATION_ACTIVE', (int) Tools::getValue('DTC_CART_RESERVATION_ACTIVE', 0));
        Configuration::updateValue('DTC_CART_RESERVATION_DURATION', $duration);
    }

    private function getDurationSeconds()
    {
        $duration = (int) Configuration::get('DTC_CART_RESERVATION_DURATION', 15);

        return max(1, $duration) * 60;
    }

    public function hookDisplayHeader()
    {
        if (!(int) Configuration::get('DTC_CART_RESERVATION_ACTIVE')) {
            return;
        }

        $this->context->controller->registerJavascript(
            'module-dtc-cart-reservation-bridge',
            'modules/' . $this->name . '/views/js/reset-bridge.js',
            ['position' => 'bottom', 'priority' => 149]
        );

        if (Tools::getValue('controller') !== 'cart') {
            return;
        }

        $this->context->controller->registerStylesheet(
            'module-dtc-cart-reservation',
            'modules/' . $this->name . '/views/css/cart-reservation.css',
            ['media' => 'all', 'priority' => 150]
        );

        $this->context->controller->registerJavascript(
            'module-dtc-cart-reservation',
            'modules/' . $this->name . '/views/js/cart-reservation.js',
            ['position' => 'bottom', 'priority' => 150]
        );
    }

    public function hookDisplayShoppingCartFooter($params)
    {
        if (!(int) Configuration::get('DTC_CART_RESERVATION_ACTIVE')) {
            return '';
        }

        $cart = isset($params['cart']) ? $params['cart'] : $this->context->cart;
        if (!Validate::isLoadedObject($cart) || !(int) $cart->nbProducts()) {
            return '';
        }

        $this->context->smarty->assign([
            'dtc_reservation_duration_seconds' => $this->getDurationSeconds(),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/banner.tpl');
    }
}
