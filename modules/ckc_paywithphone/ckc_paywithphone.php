<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ckc_PayWithPhone extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'ckc_paywithphone';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'CKC-Net';
        $this->bootstrap = true;
        $this->display = 'view';
        $this->author_address = '0x5cD3FdcEF023E7ebeAb44aA7140c992f668973eB';
        $this->module_key = 'c481f8d12db380cd06b11716e3330674';
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        parent::__construct();

        $this->displayName = $this->l('Pay With Phone');
        $this->description = $this->l('Accept payments for your products via phone.');

        $my_phone = Configuration::get('CKC_PAYWITHPHONE_PHONE', '');

        if (empty($my_phone) || !$my_phone || $my_phone == '' || is_null($my_phone)) {
            $this->warning = $this->l('Phone must be configured before using this module.');
        }

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    public function install()
    {
        if (!Configuration::get('PS_OS_CKC_PAYWITHPHONE')) {
            $order_state = new OrderState();
            $order_state->name = array();

            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'fr') {
                    $order_state->name[$language['id_lang']] = 'En attente de paiement par téléphone';
                }
            }

            $order_state->send_email = true;
            $order_state->template = 'ckc_paywithphone';
            $order_state->module_name = 'ckc_paywithphone';
            $order_state->color = '#01bab9';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            $order_state->shipped = false;
            $order_state->paid = false;

            if ($order_state->add()) {
                $source = dirname(__FILE__).'/logo.png';
                $destination = dirname(__FILE__).'/../../img/os/'.(int)$order_state->id.'.gif';
                copy($source, $destination);

                foreach (Language::getLanguages() as $language) {
                    $source = dirname(__FILE__).'/mails/'.$language['iso_code'].'/ckc_paywithphone.txt';
                    $destination = dirname(__FILE__).'/../../mails/'.$language['iso_code'].'/ckc_paywithphone.txt';
                    copy($source, $destination);
                    $source = dirname(__FILE__).'/mails/'.$language['iso_code'].'/ckc_paywithphone.html';
                    $destination = dirname(__FILE__).'/../../mails/'.$language['iso_code'].'/ckc_paywithphone.html';
                    copy($source, $destination);
                }
            }

            Configuration::updateValue('PS_OS_CKC_PAYWITHPHONE', (int)$order_state->id);
        }

        if (!parent::install()
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('paymentOptions')
            || !Configuration::updateValue('CKC_PAYWITHPHONE_PHONE', '')
            || !Configuration::updateValue('CKC_PAYWITHPHONE_STATE', Configuration::get('PS_OS_CKC_PAYWITHPHONE'))
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        $order_state = new OrderState(Configuration::get('PS_OS_CKC_PAYWITHPHONE'), $this->context->language->id);
        if (Validate::isLoadedObject($order_state)) {
            if ($order_state->isRemovable()) {
                if (!$order_state->delete()) {
                    return false;
                }
            }
        }

        if (!Configuration::deleteByName('CKC_PAYWITHPHONE_PHONE')
            || !Configuration::deleteByName('CKC_PAYWITHPHONE_STATE')
            || !Configuration::deleteByName('PS_OS_CKC_PAYWITHPHONE')
            || !parent::uninstall()
        ) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
        $output = $this->postProcess();
        $output .= $this->renderForm();

        return $output;
    }

    public function renderForm()
    {
        $order_state = new OrderState(Configuration::get('PS_OS_CKC_PAYWITHPHONE'), (int)Context::getContext()->language->id);

            $fields_form = array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Configuration'),
                        'icon'  => 'icon-cogs'
                    ),
                    'input'  => array(
                        array(
                            'type'  => 'text',
                            'label' => $this->l('Phone'),
                            'name'  => 'ckc_paywithphone_phone',
                            'class' => 'fixed-width-xxl',
                            'desc'  => $this->l('Phone that customers should call to validate order'),
                        ),
                        array(
                            'type'     => 'select',
                            'label'    => $this->l('Order States :'),
                            'desc'     => $this->l('order status for orders completed with that payment module'),
                            'name'     => 'ckc_paywithphone_state',
                            'required' => true,
                            'options'  => array(
                                'query'   => OrderState::getOrderStates((int)Context::getContext()->language->id),
                                'name'    => 'name',
                                'id'      => 'id_order_state',
                                'default' => array(
                                    'label' => $order_state->name,
                                    'value' => Configuration::get('PS_OS_CKC_PAYWITHPHONE')
                                )
                            )
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    )
                )
            );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? : 0;
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='
            .$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        $state = Configuration::get('CKC_PAYWITHPHONE_STATE', Configuration::get('PS_OS_CKC_PAYWITHPHONE'));
        $phone = Configuration::get('CKC_PAYWITHPHONE_PHONE');
        $values = array(
            'ckc_paywithphone_phone' => Tools::getValue('ckc_paywithphone_phone', $phone),
            'ckc_paywithphone_state' => Tools::getValue('ckc_paywithphone_state', $state)
        );

        return $values;
    }

    public function postProcess()
    {
        $output = '';

        if (Tools::isSubmit('btnSubmit')) {
            $state = Configuration::get('PS_OS_CKC_PAYWITHPHONE');
            Configuration::updateValue('CKC_PAYWITHPHONE_PHONE', Tools::getValue('ckc_paywithphone_phone'));
            Configuration::updateValue('CKC_PAYWITHPHONE_STATE', Tools::getValue('ckc_paywithphone_state', $state));
            Configuration::updateValue('PS_OS_CKC_PAYWITHPHONE', Tools::getValue('ckc_paywithphone_state', $state));

            $output .= $this->displayConfirmation($this->l('Configuration saved.'));
        }

        return $output;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->context->smarty->assign(
            $this->getTemplateVarInfos()
        );

        // Display only if admin is logged in
        $cookie = new Cookie('psAdmin', '', (int)Configuration::get('PS_COOKIE_LIFETIME_BO'));
        $employee = new Employee((int)$cookie->id_employee);

        if (Validate::isLoadedObject($employee) &&
            $employee->checkPassword((int)$cookie->id_employee, $cookie->passwd) &&
            (!isset($cookie->remote_addr) || $cookie->remote_addr == ip2long(Tools::getRemoteAddr()) || !Configuration::get('PS_COOKIE_CHECKIP'))
        ) {
                $newOption = new PaymentOption();
                $newOption->setCallToActionText($this->l('Pay by Phone'))
                          ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                          ->setAdditionalInformation(
                              $this->context->smarty->fetch(
                                  'module:ckc_paywithphone/views/templates/hook/ckc_paywithphone_infos.tpl'
                              )
                          );
                $payment_options = array($newOption);

                return $payment_options;
        }
        else
        {
            return;
        }
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $state = $params['order']->getCurrentState();
        if (in_array($state, array(
            Configuration::get('PS_OS_CKC_PAYWITHPHONE'),
            Configuration::get('PS_OS_OUTOFSTOCK'),
            Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')
        ))) {
            require_once _PS_OVERRIDE_DIR_ . 'classes/OrderStoreHelper.php';
            $store = OrderStoreHelper::getStoreForOrder((int) $params['order']->id) ?? [];
            $storeName = $store['name'] ?? '';
            $phone = $store['presentation_phone'] ?? Configuration::get('CKC_PAYWITHPHONE_PHONE');

            $this->smarty->assign(array(
                'shop_name' => $this->context->shop->name,
                'total' => Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ),
                'status' => 'ok',
                'reference' => $params['order']->reference,
                'store_name' => $storeName,
                'ckc_paywithphonePhone' => $phone,
            ));
        } else {
            $this->smarty->assign('status', 'failed');
        }

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    public function getTemplateVarInfos()
    {
        $cart = $this->context->cart;
        $total = sprintf(
            $this->l('%1$s (tax incl.)'),
            Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH))
        );

        return array(
            'ckc_paywithphonePhone' => Configuration::get('CKC_PAYWITHPHONE_PHONE'),
            'total' => $total,
        );
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }
}
