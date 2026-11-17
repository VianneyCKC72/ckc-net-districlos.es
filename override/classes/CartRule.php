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

class CartRule extends CartRuleCore
{
    private static $cart_products_cache = [];

    /*
    * module: ets_promotion
    * date: 2025-04-03 10:54:57
    * version: 1.1.6
    */
    public static function haveCartRuleToday($idCustomer)
    {
        if(!parent::haveCartRuleToday($idCustomer))
        {
            return Module::getInstanceByName('ets_promotion')->haveCartRuleToday();
        }
        else
            return true;
    }
    /*
    * module: ets_promotion
    * date: 2025-04-03 10:54:57
    * version: 1.1.6
    */
    public static function getCustomerCartRules(
        $id_lang,
        $id_customer,
        $active = false,
        $includeGeneric = true,
        $inStock = false,
        CartCore $cart = null,
        $free_shipping_only = false,
        $highlight_only = false
    ) {
        $result = parent::getCustomerCartRules($id_lang,$id_customer,$active,$includeGeneric,$inStock,$cart,$free_shipping_only,$highlight_only);
        Module::getInstanceByName('ets_promotion')->getCustomerCartRules($result,$id_customer,$highlight_only);
        return $result;
    }
    /*
    * module: ets_promotion
    * date: 2025-04-03 10:54:57
    * version: 1.1.6
    */
    public function delete()
    {
        $r = parent::delete();
        if(!Configuration::getGlobalValue('PS_CART_RULE_FEATURE_ACTIVE'))
        {
            Configuration::updateGlobalValue(
                'PS_CART_RULE_FEATURE_ACTIVE',
                CartRule::isCurrentlyUsed('cart_rule', true) || Ets_pr_rule::isCurrentlyUsed('ets_pr_rule', true)
            );
        }
        return $r;
    }

    public static function autoRemoveFromCart(Context $context = null, bool $useOrderPrice = false)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        if (!CartRule::isFeatureActive() || !Validate::isLoadedObject($context->cart)) {
            return [];
        }

        $errors = [];
        $currentTime = time();
        $hasInvalidRules = false;

        foreach ($context->cart->getCartRules(CartRule::FILTER_ACTION_ALL, true, $useOrderPrice) as $cart_rule) {
            /** @var CartRule $cartRuleObj */
            $cartRuleObj = $cart_rule['obj'];

            $invalidReason = null;

            if (!$cartRuleObj->active) {
                $invalidReason = $cartRuleObj->trans('This voucher is disabled', [], 'Shop.Notifications.Error');
            } elseif ($cartRuleObj->date_to && strtotime($cartRuleObj->date_to) < $currentTime) {
                $invalidReason = $cartRuleObj->trans('This voucher has expired', [], 'Shop.Notifications.Error');
            } elseif ($cartRuleObj->date_from && strtotime($cartRuleObj->date_from) > $currentTime) {
                $invalidReason = $cartRuleObj->trans('This voucher is not valid yet', [], 'Shop.Notifications.Error');
            }

            if ($invalidReason !== null) {
                $context->cart->removeCartRule($cartRuleObj->id, $useOrderPrice);
                $hasInvalidRules = true;
                $errors[] = $invalidReason;
            }
        }

        if ($hasInvalidRules) {
            $context->cart->update();
        }

        $parentErrors = parent::autoRemoveFromCart($context, $useOrderPrice);

        return array_merge($errors, $parentErrors);
    }

    public function checkProductRestrictionsFromCart(CartCore $cart, $returnProducts = false, $displayError = true, $alreadyInCart = false)
    {
        $cache_key = $cart->id . '_' . $this->id . '_' . (int)$returnProducts . '_' . (int)$displayError . '_' . (int)$alreadyInCart;

        if (isset(self::$cart_products_cache[$cache_key])) {
            return self::$cart_products_cache[$cache_key];
        }

        $result = parent::checkProductRestrictionsFromCart($cart, $returnProducts, $displayError, $alreadyInCart);
        self::$cart_products_cache[$cache_key] = $result;

        return $result;
    }

    /*
    * module: dtc_cartrule
    */
    public function checkValidity(Context $context, $alreadyInCart = false, $display_error = true, $check_carrier = true, $useOrderPrices = false)
    {
        try {
            $moduleDir = _PS_MODULE_DIR_ . 'dtc_cartrule/classes/';

            $minOrdersServicePath = $moduleDir . 'CartRuleMinOrdersService.php';
            if (file_exists($minOrdersServicePath)) {
                if (!class_exists('CartRuleMinOrdersService', false)) {
                    require_once $minOrdersServicePath;
                }

                if (CartRuleMinOrdersService::hasAnyRestriction()) {
                    $idCustomer = ($context && $context->customer) ? (int) $context->customer->id : 0;

                    if (!CartRuleMinOrdersService::isAllowedForCustomer((int) $this->id, $idCustomer)) {
                        return $display_error
                            ? $this->trans('This voucher requires a minimum number of previous orders.', [], 'Shop.Notifications.Error')
                            : false;
                    }
                }
            }

            $repeatServicePath = $moduleDir . 'CartRuleRepeatDiscountService.php';
            if (file_exists($repeatServicePath)) {
                if (!class_exists('CartRuleRepeatDiscountService', false)) {
                    require_once $repeatServicePath;
                }

                if (CartRuleRepeatDiscountService::isRepeatRule((int) $this->id)) {
                    $cart = ($context && Validate::isLoadedObject($context->cart)) ? $context->cart : null;
                    if (!$cart || CartRuleRepeatDiscountService::getTierCount($cart) <= 0) {
                        return $display_error
                            ? $this->trans('You cannot use this voucher with these products', [], 'Shop.Notifications.Error')
                            : false;
                    }
                }
            }
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog('dtc_cartrule checkValidity failed: ' . $e->getMessage(), 2);
        }

        return parent::checkValidity($context, $alreadyInCart, $display_error, $check_carrier, $useOrderPrices);
    }

    /*
    * module: dtc_cartrule
    */
    public function getContextualValue($use_tax, Context $context = null, $filter = null, $package = null, $use_cache = true)
    {
        try {
            $repeatServicePath = _PS_MODULE_DIR_ . 'dtc_cartrule/classes/CartRuleRepeatDiscountService.php';
            if (file_exists($repeatServicePath)) {
                if (!class_exists('CartRuleRepeatDiscountService', false)) {
                    require_once $repeatServicePath;
                }

                if (CartRuleRepeatDiscountService::isRepeatRule((int) $this->id)) {
                    if (!$context) {
                        $context = Context::getContext();
                    }
                    if ($context && Validate::isLoadedObject($context->cart)) {
                        $this->reduction_amount = CartRuleRepeatDiscountService::getDiscountForCart($context->cart);

                        $idCurrency = !empty($context->currency->id)
                            ? (int) $context->currency->id
                            : (int) Configuration::get('PS_CURRENCY_DEFAULT');
                        if ($idCurrency > 0) {
                            $this->reduction_currency = $idCurrency;
                        }

                        return parent::getContextualValue($use_tax, $context, $filter, $package, false);
                    }
                }
            }
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog('dtc_cartrule getContextualValue failed: ' . $e->getMessage(), 2);
        }

        return parent::getContextualValue($use_tax, $context, $filter, $package, $use_cache);
    }
}
