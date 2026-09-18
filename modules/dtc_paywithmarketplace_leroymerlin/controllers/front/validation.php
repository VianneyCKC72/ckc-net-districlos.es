<?php

class dtc_paywithmarketplace_leroymerlinValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;

        if (
            (int) $cart->id_customer === 0
            || (int) $cart->id_address_delivery === 0
            || (int) $cart->id_address_invoice === 0
            || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === $this->module->name) {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer((int) $cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $orderStateId = (int) dtc_paywithmarketplace_leroymerlin::getOrderStateId();

        $this->module->validateOrder(
            (int) $cart->id,
            $orderStateId,
            $total,
            dtc_paywithmarketplace_leroymerlin::getPaymentLabel(),
            null,
            [],
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        Tools::redirect(
            'index.php?controller=order-confirmation&id_cart='.(int) $cart->id
            .'&id_module='.(int) $this->module->id
            .'&id_order='.(int) $this->module->currentOrder
            .'&key='.$customer->secure_key
        );
    }
}

