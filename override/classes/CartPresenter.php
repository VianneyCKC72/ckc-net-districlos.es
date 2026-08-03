<?php

use PrestaShop\PrestaShop\Core\Presenter\Cart\CartPresenterCore;

class CartPresenter extends CartPresenterCore
{
    public function present($cart)
    {
        $presentedCart = parent::present($cart);

        $presentedCart['totals_without_reduction'] = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);

        $cartRules = $cart->getCartRules(CartRule::FILTER_ACTION_ALL, false);
        $presentedCart['discounts'] = $cartRules;

        // Force la liste des promos appliquées (ex. Club Pro sans code)
        if (is_array($cartRules) && $cartRules) {
            $added = [];
            $includeTax = method_exists($this, 'includeTaxes') ? (bool) $this->includeTaxes() : false;
            $context = Context::getContext();
            $locale = Tools::getContextLocale($context);

            foreach ($cartRules as $cartVoucher) {
                $idCartRule = (int) ($cartVoucher['id_cart_rule'] ?? 0);
                if ($idCartRule <= 0) {
                    continue;
                }

                $value = $includeTax
                    ? (float) ($cartVoucher['value_real'] ?? 0)
                    : (float) ($cartVoucher['value_tax_exc'] ?? 0);

                $added[$idCartRule] = [
                    'id_cart_rule' => $idCartRule,
                    'name' => $cartVoucher['name'] ?? '',
                    'code' => $cartVoucher['code'] ?? '',
                    'reduction_percent' => $cartVoucher['reduction_percent'] ?? 0,
                    'reduction_amount' => $cartVoucher['reduction_amount'] ?? 0,
                    'reduction_currency' => $cartVoucher['reduction_currency'] ?? 0,
                    'free_shipping' => (bool) ($cartVoucher['free_shipping'] ?? false),
                    'reduction_formatted' => '-' . $locale->formatPrice(
                        $value,
                        $context->currency->iso_code
                    ),
                    'delete_url' => $context->link->getPageLink(
                        'cart',
                        null,
                        null,
                        [
                            'deleteDiscount' => $idCartRule,
                            'token' => Tools::getToken(false),
                        ]
                    ),
                ];
            }

            if (!isset($presentedCart['vouchers']) || !is_array($presentedCart['vouchers'])) {
                $presentedCart['vouchers'] = [
                    'allowed' => (int) CartRule::isFeatureActive(),
                    'added' => [],
                ];
            }
            $presentedCart['vouchers']['added'] = $added;
        }

        return $presentedCart;
    }
}
