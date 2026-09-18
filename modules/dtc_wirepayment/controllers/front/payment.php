<?php
/**
 * @property Dtc_Wirepayment $module
 */
class Dtc_WirepaymentPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        Tools::redirect($this->context->link->getModuleLink('dtc_wirepayment', 'validation', [], true));
    }
}
