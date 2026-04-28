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
    // Cache statique pour les requêtes cart_product répétées
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

    /**
     * Override pour retirer automatiquement les promos désactivées, expirées
     * ou dont la période de validité n'a pas commencé.
     *
     * Le core PrestaShop ignore volontairement ces contrôles lorsque la règle est
     * déjà présente dans le panier (cf. CartRuleCore::checkValidity, $alreadyInCart=true).
     * Cet override force la vérification afin que les règles désactivées par l'admin
     * ou expirées soient retirées des paniers existants.
     *
     * @param Context|null $context Contexte PrestaShop
     * @param bool $useOrderPrice Utiliser les prix de commande
     *
     * @return array Messages d'erreur
     */
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

    /**
     * Override de checkProductRestrictionsFromCart avec cache pour optimiser les requêtes cart_product
     * Cette méthode est appelée plusieurs fois avec les mêmes paramètres pendant le checkout
     */
    public function checkProductRestrictionsFromCart(CartCore $cart, $returnProducts = false, $displayError = true, $alreadyInCart = false)
    {
        // Clé de cache basée sur les paramètres de la méthode
        $cache_key = $cart->id . '_' . $this->id . '_' . (int)$returnProducts . '_' . (int)$displayError . '_' . (int)$alreadyInCart;

        // Vérifier si le résultat est déjà en cache
        if (isset(self::$cart_products_cache[$cache_key])) {
            return self::$cart_products_cache[$cache_key];
        }

        // Appeler la méthode parente qui contient les requêtes SQL
        $result = parent::checkProductRestrictionsFromCart($cart, $returnProducts, $displayError, $alreadyInCart);

        // Mettre en cache le résultat
        self::$cart_products_cache[$cache_key] = $result;

        return $result;
    }
}
