<?php
/**
 * @property Dtc_Wirepayment $module
 */
class Dtc_WirepaymentValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === 'dtc_wirepayment') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            exit($this->module->getTranslator()->trans('Ce mode de paiement n\'est pas disponible.', [], 'Modules.Dtcwirepayment.Shop'));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $fallback = $this->module->getFallbackWireDetails();
        $wire = OrderStoreHelper::getWireBankDetailsForCart($cart, $fallback);

        $mailVars = [
            '{bankwire_owner}' => $wire['owner'],
            '{bankwire_details}' => Tools::nl2br($wire['details']),
            '{bankwire_address}' => Tools::nl2br($wire['address']),
        ];

        $this->module->validateOrder(
            $cart->id,
            (int) Configuration::get('PS_OS_BANKWIRE'),
            $total,
            $this->module->displayName,
            null,
            $mailVars,
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        Tools::redirect(
            'index.php?controller=order-confirmation&id_cart=' . (int) $cart->id
            . '&id_module=' . (int) $this->module->id
            . '&id_order=' . (int) $this->module->currentOrder
            . '&key=' . $customer->secure_key
        );
    }
}
