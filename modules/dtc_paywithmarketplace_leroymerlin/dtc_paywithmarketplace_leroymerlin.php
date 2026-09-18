<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class dtc_paywithmarketplace_leroymerlin extends PaymentModule
{
    private const ORDER_STATE_ID = 23;
    private const PAYMENT_LABEL = 'Marketplace Leroy Merlin';

    public function __construct()
    {
        $this->name = 'dtc_paywithmarketplace_leroymerlin';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'DISTRICLOS';
        $this->bootstrap = true;
        $this->controllers = ['validation'];
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->displayName = $this->l('Marketplace Leroy Merlin');
        $this->description = $this->l('Payment method for marketplace orders created by BO employees.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
            && $this->registerHook('displayPaymentReturn');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (empty($params['cart']) || !$this->checkCurrency($params['cart'])) {
            return;
        }

        if (!$this->isBoEmployeeLoggedIn()) {
            return;
        }

        $this->context->smarty->assign([
            'dtc_marketplace_label' => static::PAYMENT_LABEL,
        ]);

        $option = (new PaymentOption())
            ->setCallToActionText(static::PAYMENT_LABEL)
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
            ->setAdditionalInformation(
                $this->context->smarty->fetch(
                    'module:'.$this->name.'/views/templates/hook/dtc_paywithmarketplace_infos.tpl'
                )
            );

        return [$option];
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $this->smarty->assign([
            'dtc_order_reference' => isset($params['order']) ? $params['order']->reference : null,
        ]);

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    public function hookDisplayPaymentReturn($params)
    {
        return $this->hookPaymentReturn($params);
    }

    public function checkCurrency($cart)
    {
        $currencyOrder = new Currency((int) $cart->id_currency);
        $currenciesModule = $this->getCurrency((int) $cart->id_currency);

        if (is_array($currenciesModule)) {
            foreach ($currenciesModule as $currencyModule) {
                if ((int) $currencyOrder->id === (int) $currencyModule['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function getOrderStateId()
    {
        return static::ORDER_STATE_ID;
    }

    public static function getPaymentLabel()
    {
        return static::PAYMENT_LABEL;
    }

    private function isBoEmployeeLoggedIn()
    {
        $cookie = new Cookie('psAdmin', '', (int) Configuration::get('PS_COOKIE_LIFETIME_BO'));
        $employee = new Employee((int) $cookie->id_employee);

        return Validate::isLoadedObject($employee)
            && $employee->checkPassword((int) $cookie->id_employee, $cookie->passwd)
            && (
                !isset($cookie->remote_addr)
                || $cookie->remote_addr == ip2long(Tools::getRemoteAddr())
                || !Configuration::get('PS_COOKIE_CHECKIP')
            );
    }
}

