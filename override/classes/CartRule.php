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
     * Override pour retirer automatiquement les promos expirées des paniers
     * 
     * Cette méthode ajoute une vérification de l'expiration avant les autres contrôles
     * pour s'assurer que les promos expirées sont retirées du panier.
     * 
     * @param Context|null $context Contexte PrestaShop
     * @param bool $useOrderPrice Utiliser les prix de commande
     * 
     * @return array Messages d'erreur
     */
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
     * Override pour retirer automatiquement les promos expirées des paniers
     *
     * Cette méthode ajoute une vérification de l'expiration avant les autres contrôles
     * pour s'assurer que les promos expirées sont retirées du panier.
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
        $hasExpiredRules = false;

        // Vérification spécifique pour les promos expirées
        // On parcourt toutes les règles du panier
        foreach ($context->cart->getCartRules(CartRule::FILTER_ACTION_ALL, true, $useOrderPrice) as $cart_rule) {
            $cartRuleObj = $cart_rule['obj'];

            // Vérifier si la promo est expirée
            if ($cartRuleObj->date_to && strtotime($cartRuleObj->date_to) < $currentTime) {
                // Retirer la promo expirée du panier
                $context->cart->removeCartRule($cartRuleObj->id, $useOrderPrice);
                $hasExpiredRules = true;
                // Ajouter le message d'erreur
                $errors[] = $cartRuleObj->trans('This voucher has expired', [], 'Shop.Notifications.Error');
            }
        }

        // Mettre à jour le panier une seule fois après toutes les suppressions
        if ($hasExpiredRules) {
            $context->cart->update();
        }

        // Appeler la méthode parent pour les autres vérifications
        // (montant minimum, transporteur, quantité, etc.)
        $parentErrors = parent::autoRemoveFromCart($context, $useOrderPrice);

        // Fusionner les erreurs
        return array_merge($errors, $parentErrors);
    }
}
}