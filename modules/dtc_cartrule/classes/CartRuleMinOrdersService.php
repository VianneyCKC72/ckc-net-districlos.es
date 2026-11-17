<?php

class CartRuleMinOrdersService
{
    const TABLE_NAME = 'dtc_cartrule_minorders';

    /** @var array<int, int>|null */
    private static $minimumOrdersMap = null;

    /** @var array<int, int> */
    private static $validOrdersCount = [];

    public static function getMinimumOrders(int $idCartRule): int
    {
        if ($idCartRule <= 0) {
            return 0;
        }

        $map = self::getMinimumOrdersMap();

        return isset($map[$idCartRule]) ? (int) $map[$idCartRule] : 0;
    }

    public static function hasAnyRestriction(): bool
    {
        return self::getMinimumOrdersMap() !== [];
    }

    public static function countValidOrders(int $idCustomer): int
    {
        if ($idCustomer <= 0) {
            return 0;
        }

        if (isset(self::$validOrdersCount[$idCustomer])) {
            return self::$validOrdersCount[$idCustomer];
        }

        self::$validOrdersCount[$idCustomer] = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'orders`'
            . ' WHERE `id_customer` = ' . $idCustomer
            . ' AND `valid` = 1'
        );

        return self::$validOrdersCount[$idCustomer];
    }

    public static function isAllowedForCustomer(int $idCartRule, int $idCustomer): bool
    {
        $required = self::getMinimumOrders($idCartRule);
        if ($required <= 0) {
            return true;
        }
        if ($idCustomer <= 0) {
            return false;
        }

        return self::countValidOrders($idCustomer) >= $required;
    }

    public static function save(int $idCartRule, int $minimumOrders): void
    {
        if ($idCartRule <= 0) {
            return;
        }

        $minimumOrders = max(0, $minimumOrders);

        if ($minimumOrders === 0) {
            self::delete($idCartRule);
            return;
        }

        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' (`id_cart_rule`, `minimum_orders`)'
            . ' VALUES (' . $idCartRule . ', ' . $minimumOrders . ')'
            . ' ON DUPLICATE KEY UPDATE `minimum_orders` = ' . $minimumOrders
        );

        self::clearCache();
    }

    public static function delete(int $idCartRule): void
    {
        if ($idCartRule <= 0) {
            return;
        }

        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_cart_rule` = ' . $idCartRule
        );

        self::clearCache();
    }

    public static function clearCache(): void
    {
        self::$minimumOrdersMap = null;
        self::$validOrdersCount = [];
    }

    /**
     * @return array<int, int>
     */
    private static function getMinimumOrdersMap(): array
    {
        if (self::$minimumOrdersMap !== null) {
            return self::$minimumOrdersMap;
        }

        self::$minimumOrdersMap = [];

        try {
            $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT `id_cart_rule`, `minimum_orders`'
                . ' FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
                . ' WHERE `minimum_orders` > 0'
            );
        } catch (Throwable $e) {
            return self::$minimumOrdersMap;
        }

        if (!$rows) {
            return self::$minimumOrdersMap;
        }

        foreach ($rows as $row) {
            $id = (int) $row['id_cart_rule'];
            $min = (int) $row['minimum_orders'];
            if ($id > 0 && $min > 0) {
                self::$minimumOrdersMap[$id] = $min;
            }
        }

        return self::$minimumOrdersMap;
    }
}
