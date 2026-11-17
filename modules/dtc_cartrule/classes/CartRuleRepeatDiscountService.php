<?php

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
    public const CFG_EXCLUDE_PRODUCTS = 'DTC_CARTRULE_REPEAT_EXCLUDE_PRODUCTS';
    public const CFG_EXCLUDE_CATEGORIES = 'DTC_CARTRULE_REPEAT_EXCLUDE_CATEGORIES';

    private const RULE_CODE_MARKER = 'DTC_REPEAT';

    /** @var bool */
    private static $syncing = false;

    /** @var array<int, true>|null */
    private static $excludeProductsCache = null;

    /** @var array<int, true>|null */
    private static $excludeCategoriesCache = null;

    /** @var array<int, int> */
    private static $tierCountByCart = [];

    /** @var array<int, float> */
    private static $discountByCart = [];

    public static function getDefaults(): array
    {
        return [
            'active' => 0,
            'step' => 500.0,
            'discount' => 75.0,
            'tax' => 1,
            'exclude_products' => '',
            'exclude_categories' => '',
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

    public static function getTierCount(Cart $cart): int
    {
        if (!self::isEnabled() || !Validate::isLoadedObject($cart) || !(int) $cart->id) {
            return 0;
        }

        $idCart = (int) $cart->id;
        if (isset(self::$tierCountByCart[$idCart])) {
            return self::$tierCountByCart[$idCart];
        }

        $step = self::getStep();
        if ($step <= 0) {
            return self::$tierCountByCart[$idCart] = 0;
        }

        $total = self::getEligibleProductsTotal($cart);

        return self::$tierCountByCart[$idCart] = (int) floor($total / $step);
    }

    public static function getEligibleProductsTotal(Cart $cart): float
    {
        $excludeProducts = self::getExcludedProductIds();
        $excludeCategories = self::getExcludedCategoryIds();

        if (!$excludeProducts && !$excludeCategories) {
            return (float) $cart->getOrderTotal(self::useTax(), Cart::ONLY_PRODUCTS);
        }

        $products = $cart->getProducts(true);
        if (empty($products)) {
            return 0.0;
        }

        $excludedByCategory = [];
        if ($excludeCategories) {
            $cartProductIds = [];
            foreach ($products as $product) {
                $cartProductIds[(int) $product['id_product']] = true;
            }
            $excludedByCategory = self::findProductIdsInCategories(
                array_keys($cartProductIds),
                array_keys($excludeCategories)
            );
        }

        $useTax = self::useTax();
        $total = 0.0;

        foreach ($products as $product) {
            $idProduct = (int) $product['id_product'];
            if (isset($excludeProducts[$idProduct]) || isset($excludedByCategory[$idProduct])) {
                continue;
            }

            $total += $useTax
                ? (float) $product['total_wt']
                : (float) $product['total'];
        }

        return $total;
    }

    /**
     * @return array<int, true>
     */
    public static function getExcludedProductIds(): array
    {
        if (self::$excludeProductsCache !== null) {
            return self::$excludeProductsCache;
        }

        self::$excludeProductsCache = self::parseIdList(
            (string) Configuration::get(self::CFG_EXCLUDE_PRODUCTS)
        );

        return self::$excludeProductsCache;
    }

    /**
     * @return array<int, true>
     */
    public static function getExcludedCategoryIds(): array
    {
        if (self::$excludeCategoriesCache !== null) {
            return self::$excludeCategoriesCache;
        }

        self::$excludeCategoriesCache = self::parseIdList(
            (string) Configuration::get(self::CFG_EXCLUDE_CATEGORIES)
        );

        return self::$excludeCategoriesCache;
    }

    public static function normalizeIdList(string $raw): string
    {
        $ids = self::parseIdList($raw);

        return $ids ? implode(',', array_keys($ids)) : '';
    }

    public static function clearExclusionCache(): void
    {
        self::$excludeProductsCache = null;
        self::$excludeCategoriesCache = null;
        self::clearComputationCache();
    }

    public static function clearComputationCache(?int $idCart = null): void
    {
        if ($idCart === null) {
            self::$tierCountByCart = [];
            self::$discountByCart = [];

            return;
        }

        unset(self::$tierCountByCart[$idCart], self::$discountByCart[$idCart]);
    }

    public static function getDiscountForCart(Cart $cart): float
    {
        if (!Validate::isLoadedObject($cart) || !(int) $cart->id) {
            return 0.0;
        }

        $idCart = (int) $cart->id;
        if (isset(self::$discountByCart[$idCart])) {
            return self::$discountByCart[$idCart];
        }

        $tiers = self::getTierCount($cart);
        if ($tiers <= 0) {
            return self::$discountByCart[$idCart] = 0.0;
        }

        return self::$discountByCart[$idCart] = (float) ($tiers * self::getDiscount());
    }

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
        $cartRule->highlight = false;
        $cartRule->partial_use = false;
        $cartRule->priority = 1;
        $cartRule->active = $active;
        $cartRule->date_from = date('Y-m-d H:i:s', strtotime('-1 day'));
        $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+10 years'));
        $cartRule->quantity = 999999999;
        $cartRule->quantity_per_user = 999999999;
        $cartRule->free_shipping = false;
        $cartRule->minimum_amount = 0;
        $cartRule->minimum_amount_tax = $tax;
        $cartRule->minimum_amount_currency = $idCurrency;
        $cartRule->minimum_amount_shipping = false;
        $cartRule->reduction_percent = 0;
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

    public static function syncToCart(Cart $cart): void
    {
        if (self::$syncing || !Validate::isLoadedObject($cart) || !(int) $cart->id) {
            return;
        }

        self::$syncing = true;
        self::clearComputationCache((int) $cart->id);

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

    /**
     * @return array<int, true>
     */
    private static function parseIdList(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return [];
        }

        $ids = [];
        foreach ($parts as $part) {
            $id = (int) $part;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        return $ids;
    }

    /**
     * @param int[] $productIds
     * @param int[] $categoryIds
     *
     * @return array<int, true>
     */
    private static function findProductIdsInCategories(array $productIds, array $categoryIds): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds)));
        $categoryIds = array_values(array_filter(array_map('intval', $categoryIds)));

        if (!$productIds || !$categoryIds) {
            return [];
        }

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT DISTINCT `id_product`'
            . ' FROM `' . _DB_PREFIX_ . 'category_product`'
            . ' WHERE `id_product` IN (' . implode(',', $productIds) . ')'
            . ' AND `id_category` IN (' . implode(',', $categoryIds) . ')'
        );

        if (!$rows) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id_product']] = true;
        }

        return $result;
    }
}
