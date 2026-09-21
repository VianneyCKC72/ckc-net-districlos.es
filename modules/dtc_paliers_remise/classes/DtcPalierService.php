<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (class_exists('DtcPalierService', false)) {
    return;
}

class DtcPalierService
{
    /** @var array|null */
    private static $tiersCache;

    /** @var array|null */
    private static $proGroupIdsCache;

    /** @var array<int, bool> */
    private static $proCustomerCache = [];

    public static function clearCache(): void
    {
        self::$tiersCache = null;
        self::$proGroupIdsCache = null;
        self::$proCustomerCache = [];
    }

    public static function getCumulatedAmount(int $idCustomer): float
    {
        return self::getCumulatedAmountInDays($idCustomer, 0);
    }

    /**
     * Cumul HT tel qu'il sera dans $days jours (commandes qui sortiront de la fenêtre 12 mois exclues).
     */
    public static function getCumulatedAmountInDays(int $idCustomer, int $days): float
    {
        $excluded = implode(',', array_map('intval', self::getExcludedOrderStates()));
        $days = max(0, $days);

        return (float) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT IFNULL(SUM(o.total_paid_tax_excl), 0)
             FROM `' . _DB_PREFIX_ . 'orders` o
             WHERE o.id_customer = ' . (int) $idCustomer . '
               AND o.valid = 1
               AND o.current_state NOT IN (' . $excluded . ')
               AND o.date_add >= DATE_ADD(DATE_SUB(NOW(), INTERVAL 12 MONTH), INTERVAL ' . (int) $days . ' DAY)'
        );
    }

    public static function getTierRankByName(string $tierName): int
    {
        if ($tierName === '' || $tierName === 'none') {
            return 0;
        }

        $level = self::getLevelKey($tierName);
        $rank = ['bronze' => 1, 'argent' => 2, 'or' => 3];

        return $rank[$level] ?? 0;
    }

    public static function getTierShortLabel(string $tierName): string
    {
        if ($tierName === '' || $tierName === 'none') {
            return self::transFo('Aucun');
        }

        $labels = [
            'bronze' => 'Bronze',
            'argent' => self::transFo('Argent'),
            'or' => self::transFo('Or'),
        ];
        $level = self::getLevelKey($tierName);

        return $labels[$level] ?? $tierName;
    }

    public static function getTierForAmount(float $amount): ?array
    {
        $tiers = self::getTiersSortedDesc();
        foreach ($tiers as $tier) {
            if ($amount >= (float) $tier['min']) {
                return $tier;
            }
        }

        return null;
    }

    public static function recompute(int $idCustomer): ?array
    {
        $amount = self::getCumulatedAmount($idCustomer);
        $tier = self::getTierForAmount($amount);

        $data = [
            'current_tier' => pSQL($tier ? $tier['name'] : 'none'),
            'discount_percent' => $tier ? (float) $tier['discount'] : 0,
            'cumulated_amount' => $amount,
            'last_computed_at' => date('Y-m-d H:i:s'),
        ];

        $exists = (bool) Db::getInstance()->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'dtc_customer_palier`
             WHERE id_customer = ' . (int) $idCustomer
        );

        if ($exists) {
            Db::getInstance()->update('dtc_customer_palier', $data, 'id_customer = ' . (int) $idCustomer);
        } else {
            $data['id_customer'] = (int) $idCustomer;
            Db::getInstance()->insert('dtc_customer_palier', $data);
        }

        return $tier;
    }

    public static function getCustomerTier(int $idCustomer): ?array
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'dtc_customer_palier`
             WHERE id_customer = ' . (int) $idCustomer
        );

        return $row ?: null;
    }

    public static function getTiers(): array
    {
        if (self::$tiersCache !== null) {
            return self::$tiersCache;
        }

        $json = Configuration::get('DTC_PALIER_TIERS');
        $tiers = $json ? json_decode($json, true) : null;
        self::$tiersCache = (is_array($tiers) && $tiers) ? $tiers : self::getDefaultTiers();

        return self::$tiersCache;
    }

    public static function getDefaultTiers(): array
    {
        return [
            ['min' => 0, 'discount' => 5, 'name' => 'Membre Bronze'],
            ['min' => 2500, 'discount' => 7, 'name' => 'Membre Argent'],
            ['min' => 5000, 'discount' => 10, 'name' => 'Membre Or'],
        ];
    }

    /**
     * Groupe(s) éligibles Club Pro : uniquement CKC_GROUP_PRO_ID (professionnels acceptés).
     * Ne pas utiliser CKC_GROUP_PRO_IDS (peut inclure proticulier / autres variantes).
     */
    public static function getProGroupIds(): array
    {
        if (self::$proGroupIdsCache !== null) {
            return self::$proGroupIdsCache;
        }

        $id = (int) Configuration::get('CKC_GROUP_PRO_ID');
        if ($id <= 0) {
            $id = 10;
        }

        return self::$proGroupIdsCache = [$id];
    }

    public static function isProCustomer(int $idCustomer): bool
    {
        if (isset(self::$proCustomerCache[$idCustomer])) {
            return self::$proCustomerCache[$idCustomer];
        }

        $proGroups = self::getProGroupIds();
        if (!$proGroups) {
            return self::$proCustomerCache[$idCustomer] = false;
        }

        $customer = new Customer($idCustomer);
        if (!Validate::isLoadedObject($customer)) {
            return self::$proCustomerCache[$idCustomer] = false;
        }

        return self::$proCustomerCache[$idCustomer] = (bool) array_intersect($proGroups, $customer->getGroups());
    }

    public static function getOrdersLast12Months(int $idCustomer): array
    {
        $excluded = implode(',', array_map('intval', self::getExcludedOrderStates()));
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT o.date_add, o.total_paid_tax_excl
             FROM `' . _DB_PREFIX_ . 'orders` o
             WHERE o.id_customer = ' . (int) $idCustomer . '
               AND o.valid = 1
               AND o.current_state NOT IN (' . $excluded . ')
               AND o.date_add >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             ORDER BY o.date_add DESC'
        );

        if (!$rows) {
            return [];
        }

        $orders = [];
        foreach ($rows as $row) {
            $orders[] = [
                'date' => date('d/m/Y', strtotime($row['date_add'])),
                'amount' => (float) $row['total_paid_tax_excl'],
            ];
        }

        return $orders;
    }

    public static function getDisplayTiers(?array $current = null): array
    {
        $tiers = self::getTiersSortedAsc();
        $count = count($tiers);
        $display = [];

        foreach ($tiers as $i => $tier) {
            $display[] = [
                'name' => self::transFo((string) $tier['name']),
                'discount' => (float) $tier['discount'],
                'min' => (float) $tier['min'],
                'max' => ($i < $count - 1) ? (float) $tiers[$i + 1]['min'] : null,
                'is_current' => $current
                    && (string) $current['name'] === (string) $tier['name']
                    && (float) $current['discount'] === (float) $tier['discount'],
                'level' => self::guessLevelKey($tier['name'], $i),
                'position' => $count > 0 ? round((($i + 1) / ($count + 1)) * 100, 2) : 0,
            ];
        }

        return $display;
    }

    public static function getClubProViewData(int $idCustomer): array
    {
        $amount = self::getCumulatedAmount($idCustomer);
        $current = self::getTierForAmount($amount);
        $tiersAsc = self::getTiersSortedAsc();

        return [
            'amount' => $amount,
            'current_name' => $current ? self::transFo((string) $current['name']) : null,
            'current_discount' => $current ? (float) $current['discount'] : 0,
            'tiers' => self::getDisplayTiers($current),
            'progress' => self::computeProgressPercent($amount, $tiersAsc),
            'orders' => self::getOrdersLast12Months($idCustomer),
        ];
    }

    private static function getTiersSortedAsc(): array
    {
        $tiers = self::getTiers();
        usort($tiers, static fn($a, $b) => $a['min'] <=> $b['min']);

        return $tiers;
    }

    private static function getTiersSortedDesc(): array
    {
        $tiers = self::getTiers();
        usort($tiers, static fn($a, $b) => $b['min'] <=> $a['min']);

        return $tiers;
    }

    private static function getExcludedOrderStates(): array
    {
        return [
            (int) Configuration::get('PS_OS_CANCELED'),
            (int) Configuration::get('PS_OS_REFUND'),
            (int) Configuration::get('PS_OS_ERROR'),
        ];
    }

    public static function getLevelKey(string $name, int $index = 0): string
    {
        $lower = Tools::strtolower($name);
        if (preg_match('/\bbronze\b/u', $lower)) {
            return 'bronze';
        }
        if (preg_match('/\bargent\b|\bsilver\b/u', $lower)) {
            return 'argent';
        }
        if (preg_match('/\bor\b|\bgold\b/u', $lower)) {
            return 'or';
        }

        return ['bronze', 'argent', 'or'][$index] ?? 'bronze';
    }

    /**
     * Niveau badge header (bronze|argent|or) ou null.
     * Calcul live (même logique que /reduction), pas la valeur persistée.
     */
    public static function getHeaderBadgeLevel(int $idCustomer): ?string
    {
        if ($idCustomer <= 0 || !self::isProCustomer($idCustomer)) {
            return null;
        }

        $tier = self::getTierForAmount(self::getCumulatedAmount($idCustomer));
        if (!$tier || empty($tier['name']) || (float) ($tier['discount'] ?? 0) <= 0) {
            return null;
        }

        return self::getLevelKey((string) $tier['name']);
    }

    /**
     * Message Club Pro sur la page confirmation de commande.
     * Retourne null si rien à afficher (pas pro, pas de palier après commande, downgrade).
     *
     * @return array{mode: string, label: string, discount: float, level: string}|null
     */
    public static function getOrderConfirmationStatus(int $idCustomer, Order $order): ?array
    {
        if ($idCustomer <= 0 || !self::isProCustomer($idCustomer) || !Validate::isLoadedObject($order)) {
            return null;
        }

        $orderAmount = (float) $order->total_paid_tax_excl;
        if ($orderAmount <= 0) {
            return null;
        }

        $amountNow = self::getCumulatedAmount($idCustomer);
        $orderCountsInCumul = self::orderCountsInCumulatedAmount($order);

        $amountAfter = $orderCountsInCumul ? $amountNow : ($amountNow + $orderAmount);
        $amountBefore = $orderCountsInCumul
            ? max(0.0, $amountNow - $orderAmount)
            : $amountNow;

        $tierBefore = self::getTierForAmount($amountBefore);
        $tierAfter = self::getTierForAmount($amountAfter);

        if (!$tierAfter || (float) ($tierAfter['discount'] ?? 0) <= 0) {
            return null;
        }

        $levelBefore = $tierBefore ? self::getLevelKey((string) $tierBefore['name']) : null;
        $levelAfter = self::getLevelKey((string) $tierAfter['name']);
        $rank = ['bronze' => 1, 'argent' => 2, 'or' => 3];
        $rankBefore = $levelBefore ? ($rank[$levelBefore] ?? 0) : 0;
        $rankAfter = $rank[$levelAfter] ?? 0;

        if ($rankAfter < $rankBefore) {
            return null;
        }

        $labels = [
            'bronze' => 'Bronze',
            'argent' => self::transFo('Argent'),
            'or' => self::transFo('Or'),
        ];

        return [
            'mode' => $rankAfter > $rankBefore ? 'upgrade' : 'keep',
            'label' => $labels[$levelAfter] ?? $levelAfter,
            'discount' => (float) $tierAfter['discount'],
            'level' => $levelAfter,
        ];
    }

    private static function transFo(string $text): string
    {
        $context = Context::getContext();
        if (!$context || !$context->getTranslator()) {
            return $text;
        }

        return $context->getTranslator()->trans($text, [], 'Shop.Theme.Customdtc');
    }

    private static function orderCountsInCumulatedAmount(Order $order): bool
    {
        if (!(int) $order->valid) {
            return false;
        }

        $excluded = self::getExcludedOrderStates();
        if (in_array((int) $order->current_state, $excluded, true)) {
            return false;
        }

        $from = strtotime('-12 months');
        $orderTs = strtotime((string) $order->date_add);

        return $orderTs !== false && $orderTs >= $from;
    }

    private static function guessLevelKey(string $name, int $index): string
    {
        return self::getLevelKey($name, $index);
    }

    private static function computeProgressPercent(float $amount, array $tiersSortedAsc): float
    {
        $count = count($tiersSortedAsc);
        if ($count === 0 || $amount <= 0) {
            return 0;
        }

        $markerPos = static function (int $index) use ($count): float {
            return (($index + 1) / ($count + 1)) * 100;
        };

        $firstMin = (float) $tiersSortedAsc[0]['min'];
        $firstMarker = $markerPos(0);
        $lastMarker = $markerPos($count - 1);

        if ($count === 1) {
            if ($firstMin <= 0 || $amount >= $firstMin) {
                return round($lastMarker, 2);
            }

            return round(($amount / $firstMin) * $firstMarker, 2);
        }

        if ($amount < $firstMin) {
            return $firstMin > 0 ? round(($amount / $firstMin) * $firstMarker, 2) : 0;
        }

        for ($i = 0; $i < $count - 1; $i++) {
            $min = (float) $tiersSortedAsc[$i]['min'];
            $nextMin = (float) $tiersSortedAsc[$i + 1]['min'];
            if ($amount >= $nextMin) {
                continue;
            }

            $span = $nextMin - $min;
            $ratio = $span > 0 ? (($amount - $min) / $span) : 1;

            return round($markerPos($i) + ($ratio * ($markerPos($i + 1) - $markerPos($i))), 2);
        }

        return round($lastMarker, 2);
    }
}
