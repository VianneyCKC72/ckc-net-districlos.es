<?php
/**
 * GcPayWithPhone
 *
 * @author    Grégory Chartier <hello@gregorychartier.fr>
 * @copyright 2017 Grégory Chartier (https://www.gregorychartier.fr)
 * @license   Commercial license see license.txt
 * @category  Prestashop
 * @category  Module
 */

class ckc_PayWithPhoneValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0
            || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'ckc_paywithphone') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $this->module->validateOrder(
            $cart->id,
            Configuration::get('PS_OS_CKC_PAYWITHPHONE'),
            $total,
            $this->module->displayName,
            null,
            array('telephone'   => Configuration::get('CKC_PAYWITHPHONE_PHONE', '')),
            (int)$currency->id,
            false
        );

        Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id
            .'&id_order='.$this->module->currentOrder
            .'&key='.$customer->secure_key);
    }
}
