<?php

class ckc_PayWithPhonePaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $total = sprintf(
            $this->module->l('%1$s (tax incl.)'),
            Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH))
        );

        $this->context->smarty->assign(array(
            'back_url'      => $this->context->link->getPageLink('order', true, null, "step=3"),
            'confirm_url'   => $this->context->link->getModuleLink('ckc_paywithphone', 'validation', array(), true),
            'image_url'     => $this->module->getPathUri().'views/img/ckc_paywithphone.jpg',
            'cust_currency' => $cart->id_currency,
            'currencies'    => $this->module->getCurrency((int)$cart->id_currency),
            'total'         => $total,
            'this_path'     => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
        ));

        $this->setTemplate('payment_execution.tpl');
    }
}
