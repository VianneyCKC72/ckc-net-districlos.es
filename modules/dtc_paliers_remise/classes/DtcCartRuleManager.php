<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (class_exists('DtcCartRuleManager', false)) {
    return;
}

/**
 * Une CartRule partagée par palier + un groupe client dédié.
 * Plus de règle panier par client.
 */
class DtcCartRuleManager
{
    private const GROUP_PREFIX = 'Club Pro ';
    private const RULE_DESC_PREFIX = 'DTC_PALIER_SHARED_';
    private const OLD_RULE_DESC_PREFIX = 'DTC_PALIER_CUSTOMER_';

    private const LEVELS = ['bronze', 'argent', 'or'];

    private const LEVEL_LABELS = [
        'bronze' => 'Bronze',
        'argent' => 'Argent',
        'or' => 'Or',
    ];

    /**
     * Crée/maj groupes + CartRules partagées, nettoie les anciennes règles par client.
     */
    public static function ensureSharedResources(): void
    {
        if ((int) Configuration::get('DTC_PALIER_SHARED_READY') === 1
            && self::getGroupMap()
            && self::getRuleMap()
        ) {
            self::syncSharedRulesFromTiers();

            return;
        }

        $groupMap = [];
        $ruleMap = [];

        foreach (self::LEVELS as $level) {
            $groupMap[$level] = self::ensureGroup($level);
            $ruleMap[$level] = self::ensureSharedCartRule($level, $groupMap[$level]);
        }

        Configuration::updateValue('DTC_PALIER_GROUPS', json_encode($groupMap));
        Configuration::updateValue('DTC_PALIER_RULES', json_encode($ruleMap));
        Configuration::updateValue('DTC_PALIER_SHARED_READY', 1);

        self::syncSharedRulesFromTiers();
        self::cleanupLegacyCustomerCartRules();
    }

    /**
     * Assigne le client au bon groupe Club Pro (retire les autres).
     */
    public static function syncCartRule(int $idCustomer, ?array $tier): ?int
    {
        self::ensureSharedResources();

        $customer = new Customer($idCustomer);
        if (!Validate::isLoadedObject($customer)) {
            return null;
        }

        $clubGroupIds = array_map('intval', array_values(self::getGroupMap()));
        $groups = array_map('intval', $customer->getGroups());

        $idGroup = 0;
        $idCartRule = null;

        if ($tier && !empty($tier['name']) && (string) $tier['name'] !== 'none') {
            $level = DtcPalierService::getLevelKey((string) $tier['name']);
            $idGroup = (int) (self::getGroupMap()[$level] ?? 0);
            $idCartRule = (int) (self::getRuleMap()[$level] ?? 0) ?: null;
        }

        $currentClubGroups = array_values(array_intersect($groups, $clubGroupIds));
        $alreadyOk = $idGroup > 0
            ? ($currentClubGroups === [$idGroup])
            : ($currentClubGroups === []);

        if (!$alreadyOk) {
            $groups = array_values(array_diff($groups, $clubGroupIds));
            if ($idGroup > 0) {
                $groups[] = $idGroup;
            }
            $groups = array_values(array_unique(array_filter($groups)));
            if (!$groups) {
                $groups = [(int) Configuration::get('PS_CUSTOMER_GROUP')];
            }
            $customer->updateGroup($groups);
            // Invalide le cache statique des groupes (sinon getGroups() renvoie l'ancien set)
            Customer::resetStaticCache();
        }

        Db::getInstance()->update(
            'dtc_customer_palier',
            ['id_cart_rule' => null],
            'id_customer = ' . (int) $idCustomer
        );

        self::deleteLegacyRuleForCustomer($idCustomer);

        return $idCartRule;
    }

    /**
     * Attache la CartRule partagée du palier au panier.
     */
    public static function ensureAppliedToCart(Cart $cart, int $idCustomer): void
    {
        if (!$cart->id || $idCustomer <= 0) {
            return;
        }

        self::ensureSharedResources();

        $tierData = DtcPalierService::getCustomerTier($idCustomer);
        if (!$tierData || empty($tierData['current_tier']) || $tierData['current_tier'] === 'none') {
            self::removeSharedRulesFromCart($cart);

            return;
        }

        $level = DtcPalierService::getLevelKey((string) $tierData['current_tier']);
        $idCartRule = (int) (self::getRuleMap()[$level] ?? 0);
        if ($idCartRule <= 0) {
            return;
        }

        // Retire les autres règles Club Pro du panier
        foreach (self::getRuleMap() as $ruleLevel => $ruleId) {
            $ruleId = (int) $ruleId;
            if ($ruleId <= 0 || $ruleId === $idCartRule) {
                continue;
            }
            if (self::cartHasRule($cart->id, $ruleId)) {
                $cart->removeCartRule($ruleId);
            }
        }

        $cartRule = new CartRule($idCartRule);
        if (!Validate::isLoadedObject($cartRule) || !(bool) $cartRule->active) {
            return;
        }

        if (!self::cartHasRule($cart->id, $idCartRule)) {
            $cart->addCartRule($idCartRule);
        }
    }

    /**
     * Met à jour les % des 3 règles selon la config paliers BO.
     */
    public static function syncSharedRulesFromTiers(): void
    {
        $ruleMap = self::getRuleMap();
        if (!$ruleMap) {
            return;
        }

        $tiers = DtcPalierService::getTiers();
        usort($tiers, static fn($a, $b) => $a['min'] <=> $b['min']);

        foreach ($tiers as $i => $tier) {
            $level = DtcPalierService::getLevelKey((string) $tier['name'], $i);
            $idCartRule = (int) ($ruleMap[$level] ?? 0);
            if ($idCartRule <= 0) {
                continue;
            }

            $cartRule = new CartRule($idCartRule);
            if (!Validate::isLoadedObject($cartRule)) {
                continue;
            }

            $discount = (float) $tier['discount'];
            $label = self::LEVEL_LABELS[$level] ?? $tier['name'];
            $cartRule->reduction_percent = $discount;
            $cartRule->reduction_tax = false;
            $cartRule->active = true;
            $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+10 years'));

            foreach (Language::getLanguages(false) as $lang) {
                $cartRule->name[$lang['id_lang']] = 'Club Pro ' . $label . ' (-' . rtrim(rtrim(number_format($discount, 2, '.', ''), '0'), '.') . '%)';
            }

            $cartRule->update();
        }
    }

    public static function getGroupMap(): array
    {
        $json = Configuration::get('DTC_PALIER_GROUPS');
        $map = $json ? json_decode($json, true) : null;

        return is_array($map) ? $map : [];
    }

    public static function getRuleMap(): array
    {
        $json = Configuration::get('DTC_PALIER_RULES');
        $map = $json ? json_decode($json, true) : null;

        return is_array($map) ? $map : [];
    }

    private static function ensureGroup(string $level): int
    {
        $existingMap = self::getGroupMap();
        if (!empty($existingMap[$level])) {
            $group = new Group((int) $existingMap[$level]);
            if (Validate::isLoadedObject($group)) {
                return (int) $group->id;
            }
        }

        $label = self::LEVEL_LABELS[$level] ?? $level;
        $name = self::GROUP_PREFIX . $label;

        // Réutilise un groupe existant du même nom si présent
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $idExisting = (int) Db::getInstance()->getValue(
            'SELECT g.id_group
             FROM `' . _DB_PREFIX_ . 'group` g
             INNER JOIN `' . _DB_PREFIX_ . 'group_lang` gl ON gl.id_group = g.id_group
             WHERE gl.id_lang = ' . $idLang . '
               AND gl.name = "' . pSQL($name) . '"'
        );
        if ($idExisting > 0) {
            return $idExisting;
        }

        $group = new Group();
        $group->price_display_method = Group::PRICE_DISPLAY_METHOD_TAX_EXCL;
        $group->show_prices = true;
        foreach (Language::getLanguages(false) as $lang) {
            $group->name[$lang['id_lang']] = $name;
        }
        $group->add();

        return (int) $group->id;
    }

    private static function ensureSharedCartRule(string $level, int $idGroup): int
    {
        $existingMap = self::getRuleMap();
        if (!empty($existingMap[$level])) {
            $rule = new CartRule((int) $existingMap[$level]);
            if (Validate::isLoadedObject($rule)) {
                self::bindRuleToGroup((int) $rule->id, $idGroup);

                return (int) $rule->id;
            }
        }

        $idExisting = (int) Db::getInstance()->getValue(
            'SELECT id_cart_rule FROM `' . _DB_PREFIX_ . 'cart_rule`
             WHERE description = "' . pSQL(self::RULE_DESC_PREFIX . $level) . '"'
        );
        if ($idExisting > 0) {
            self::bindRuleToGroup($idExisting, $idGroup);

            return $idExisting;
        }

        $label = self::LEVEL_LABELS[$level] ?? $level;
        $cartRule = new CartRule();
        $cartRule->id_customer = 0;
        $cartRule->code = '';
        $cartRule->description = self::RULE_DESC_PREFIX . $level;
        $cartRule->reduction_percent = 0;
        $cartRule->reduction_tax = false;
        $cartRule->active = true;
        $cartRule->quantity = 999999;
        $cartRule->quantity_per_user = 999999;
        $cartRule->date_from = date('Y-m-d H:i:s');
        $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+10 years'));
        $cartRule->cart_rule_restriction = 0;
        $cartRule->group_restriction = 1;
        $cartRule->minimum_amount = 0;
        $cartRule->free_shipping = false;
        $cartRule->partial_use = false;
        $cartRule->reduction_exclude_special = false;

        foreach (Language::getLanguages(false) as $lang) {
            $cartRule->name[$lang['id_lang']] = 'Club Pro ' . $label;
        }

        $cartRule->add();
        self::bindRuleToGroup((int) $cartRule->id, $idGroup);

        return (int) $cartRule->id;
    }

    private static function bindRuleToGroup(int $idCartRule, int $idGroup): void
    {
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'cart_rule`
             SET group_restriction = 1
             WHERE id_cart_rule = ' . (int) $idCartRule
        );
        Db::getInstance()->delete('cart_rule_group', 'id_cart_rule = ' . (int) $idCartRule);
        Db::getInstance()->insert('cart_rule_group', [
            'id_cart_rule' => (int) $idCartRule,
            'id_group' => (int) $idGroup,
        ]);
    }

    private static function cleanupLegacyCustomerCartRules(): void
    {
        $rows = Db::getInstance()->executeS(
            'SELECT id_cart_rule FROM `' . _DB_PREFIX_ . 'cart_rule`
             WHERE description LIKE "' . pSQL(self::OLD_RULE_DESC_PREFIX) . '%"'
        );

        if ($rows) {
            foreach ($rows as $row) {
                $rule = new CartRule((int) $row['id_cart_rule']);
                if (Validate::isLoadedObject($rule)) {
                    $rule->delete();
                }
            }
        }

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'dtc_customer_palier` SET id_cart_rule = NULL'
        );
    }

    private static function deleteLegacyRuleForCustomer(int $idCustomer): void
    {
        $id = (int) Db::getInstance()->getValue(
            'SELECT id_cart_rule FROM `' . _DB_PREFIX_ . 'cart_rule`
             WHERE description = "' . pSQL(self::OLD_RULE_DESC_PREFIX . $idCustomer) . '"'
        );
        if ($id <= 0) {
            return;
        }

        $rule = new CartRule($id);
        if (Validate::isLoadedObject($rule)) {
            $rule->delete();
        }
    }

    private static function removeSharedRulesFromCart(Cart $cart): void
    {
        foreach (self::getRuleMap() as $ruleId) {
            $ruleId = (int) $ruleId;
            if ($ruleId > 0 && self::cartHasRule($cart->id, $ruleId)) {
                $cart->removeCartRule($ruleId);
            }
        }
    }

    private static function cartHasRule(int $idCart, int $idCartRule): bool
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_cart_rule`
             WHERE id_cart = ' . (int) $idCart . '
               AND id_cart_rule = ' . (int) $idCartRule
        );
    }
}
