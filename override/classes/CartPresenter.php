<?php

use PrestaShop\PrestaShop\Core\Presenter\Cart\CartPresenterCore;

class CartPresenter extends CartPresenterCore
{
    public function present($cart)
    {
        $presentedCart = parent::present($cart);

        $presentedCart['totals_without_reduction'] = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $presentedCart['discounts'] = $cart->getCartRules();

        return $presentedCart;
    }
}
