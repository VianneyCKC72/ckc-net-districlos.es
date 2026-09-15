<?php
/**
 * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
 */
class OrderController extends OrderControllerCore
{
    /*
    * module: ets_promotion
    * date: 2025-04-03 10:54:57
    * version: 1.1.6
    */
    public function initContent()
    {
        $victimeSuccesError = $this->getVictimeSuccesCartError();
        if ($victimeSuccesError !== null && !$this->ajax) {
            Tools::redirect($this->context->link->getPageLink('cart', null, null, ['action' => 'show']));
        }

        parent::initContent();
    }

    public function displayAjaxCheckCartStillOrderable(): void
    {
        $victimeSuccesError = $this->getVictimeSuccesCartError();
        if ($victimeSuccesError !== null) {
            header('Content-Type: application/json');
            $this->ajaxRender(json_encode([
                'errors' => true,
                'cartUrl' => $this->context->link->getPageLink('cart', null, null, ['action' => 'show']),
            ]));

            return;
        }

        parent::displayAjaxCheckCartStillOrderable();
    }

    private function getVictimeSuccesCartError(): ?string
    {
        if (!$this->context->cart || !$this->context->cart->id) {
            return null;
        }

        require_once _PS_MODULE_DIR_ . 'ckc_stores_stock/classes/StockManager.php';

        return (new StockManager())->getCartVictimeSuccesError($this->context->cart);
    }

    /*
    * module: ets_promotion
    * date: 2025-04-03 10:54:57
    * version: 1.1.6
    */
    public function displayAjaxselectDeliveryOption()
    {
        $cart = $this->cart_presenter->present(
            $this->context->cart
        );
        ob_end_clean();
        header('Content-Type: application/json');
        $this->ajaxRender(json_encode([
            'preview' => $this->context->smarty->fetch(_PS_MODULE_DIR_.'ets_promotion/views/templates/hook/checkout/cart-summary.tpl',null,null, [
                'cart' => $cart,
                'static_token' => Tools::getToken(false),
            ]),
        ]));
    }
}