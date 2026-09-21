<?php
/**
 * Remise répétitive : -X € tous les Y € de panier (sans plafond).
 *
 * Config module → une CartRule partagée → montant calculé dynamiquement
 * (floor(total / palier) × remise) via l'override CartRule::getContextualValue.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CartRuleRepeatDiscountService
{
    public const CFG_ACTIVE = 'DTC_CARTRULE_REPEAT_ACTIVE';
    public const CFG_STEP = 'DTC_CARTRULE_REPEAT_STEP';
    public const CFG_DISCOUNT = 'DTC_CARTRULE_REPEAT_DISCOUNT';
    public const CFG_TAX = 'DTC_CARTRULE_REPEAT_TAX';
    public const CFG_ID_CART_RULE = 'DTC_CARTRULE_REPEAT_ID';

    private const RULE_CODE_MARKER = 'DTC_REPEAT';

    /** @var bool Évite la récursion actionCartSave → addCartRule → actionCartSave */
    private static $syncing = false;

    public static function getDefaults(): array
    {
        return [
            'active' => 0,
            'step' => 500.0,
            'discount' => 75.0,
            'tax' => 1,
        ];
    }

    public static function isEnabled(): bool
    {
        return (int) Configuration::get(self::CFG_ACTIVE) === 1
            && self::getStep() > 0
            && self::getDiscount() > 0;
    }

    public static function getStep(): float
    {
        return max(0.0, (float) Configuration::get(self::CFG_STEP));
    }

    public static function getDiscount(): float
    {
        return max(0.0, (float) Configuration::get(self::CFG_DISCOUNT));
    }

    public static function useTax(): bool
    {
        return (int) Configuration::get(self::CFG_TAX) === 1;
    }

    public static function getIdCartRule(): int
    {
        return (int) Configuration::get(self::CFG_ID_CART_RULE);
    }

    public static function isRepeatRule(int $idCartRule): bool
    {
        return $idCartRule > 0 && $idCartRule === self::getIdCartRule();
    }

    /**
     * Nombre de paliers atteints (0 si inactif / panier insuffisant).
     */
    public static function getTierCount(Cart $cart): int
    {
        if (!self::isEnabled() || !Validate::isLoadedObject($cart) || !(int) $cart->id) {
            return 0;
        }

        $step = self::getStep();
        if ($step <= 0) {
            return 0;
        }

        $total = (float) $cart->getOrderTotal(self::useTax(), Cart::ONLY_PRODUCTS);

        return (int) floor($total / $step);
    }

    /**
     * Montant de remise à appliquer sur le panier (sans plafond).
     */
    public static function getDiscountForCart(Cart $cart): float
    {
        $tiers = self::getTierCount($cart);
        if ($tiers <= 0) {
            return 0.0;
        }

        return (float) ($tiers * self::getDiscount());
    }

    /**
     * Crée / met à jour la CartRule partagée selon la config.
     */
    public static function ensureCartRule(): int
    {
        $id = self::getIdCartRule();
        $cartRule = $id > 0 ? new CartRule($id) : new CartRule();

        if ($id > 0 && !Validate::isLoadedObject($cartRule)) {
            $cartRule = new CartRule();
            $id = 0;
        }

        $step = self::getStep();
        $discount = self::getDiscount();
        $tax = self::useTax();
        $active = self::isEnabled();
        $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        if ($idCurrency <= 0 && Context::getContext()->currency) {
            $idCurrency = (int) Context::getContext()->currency->id;
        }

        $cartRule->description = self::RULE_CODE_MARKER;
        $cartRule->code = '';
        $cartRule->highlight = true;
        $cartRule->partial_use = false;
        $cartRule->priority = 1;
        $cartRule->active = $active;
        $cartRule->date_from = date('Y-m-d H:i:s', strtotime('-1 day'));
        $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+10 years'));
        $cartRule->quantity = 999999999;
        $cartRule->quantity_per_user = 999999999;
        $cartRule->free_shipping = false;
        $cartRule->minimum_amount = $step;
        $cartRule->minimum_amount_tax = $tax;
        $cartRule->minimum_amount_currency = $idCurrency;
        $cartRule->minimum_amount_shipping = false;
        $cartRule->reduction_percent = 0;
        // Valeur unitaire : le montant réel (× N paliers) est calculé dans getContextualValue.
        $cartRule->reduction_amount = $discount;
        $cartRule->reduction_tax = $tax;
        $cartRule->reduction_currency = $idCurrency;
        $cartRule->reduction_product = 0;
        $cartRule->cart_rule_restriction = false;
        $cartRule->product_restriction = false;
        $cartRule->shop_restriction = false;
        $cartRule->group_restriction = false;
        $cartRule->country_restriction = false;
        $cartRule->carrier_restriction = false;
        $cartRule->id_customer = 0;

        $nameFr = sprintf('-%s € tous les %s €', self::formatAmount($discount), self::formatAmount($step));
        $nameEn = sprintf('-€%s every €%s', self::formatAmount($discount), self::formatAmount($step));

        foreach (Language::getLanguages(false) as $lang) {
            $iso = strtolower((string) $lang['iso_code']);
            $cartRule->name[(int) $lang['id_lang']] = ($iso === 'en') ? $nameEn : $nameFr;
        }

        if ($id > 0) {
            $cartRule->update();
        } else {
            $cartRule->add();
        }

        Configuration::updateValue(self::CFG_ID_CART_RULE, (int) $cartRule->id);

        return (int) $cartRule->id;
    }

    /**
     * Ajoute ou retire la règle du panier selon les paliers atteints.
     */
    public static function syncToCart(Cart $cart): void
    {
        if (self::$syncing || !Validate::isLoadedObject($cart) || !(int) $cart->id) {
            return;
        }

        self::$syncing = true;

        try {
            $idCartRule = self::getIdCartRule();
            if ($idCartRule <= 0 && self::isEnabled()) {
                $idCartRule = self::ensureCartRule();
            }
            if ($idCartRule <= 0) {
                return;
            }

            $hasRule = self::cartHasRule((int) $cart->id, $idCartRule);
            $tiers = self::getTierCount($cart);

            if ($tiers > 0 && self::isEnabled()) {
                if (!$hasRule) {
                    $cart->addCartRule($idCartRule);
                }
            } elseif ($hasRule) {
                $cart->removeCartRule($idCartRule);
            }
        } finally {
            self::$syncing = false;
        }
    }

    public static function deactivateCartRule(): void
    {
        $id = self::getIdCartRule();
        if ($id <= 0) {
            return;
        }

        $cartRule = new CartRule($id);
        if (Validate::isLoadedObject($cartRule)) {
            $cartRule->active = false;
            $cartRule->update();
        }
    }

    private static function cartHasRule(int $idCart, int $idCartRule): bool
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_cart_rule`'
            . ' WHERE `id_cart` = ' . (int) $idCart
            . ' AND `id_cart_rule` = ' . (int) $idCartRule
        );
    }

    private static function formatAmount(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    }
}
