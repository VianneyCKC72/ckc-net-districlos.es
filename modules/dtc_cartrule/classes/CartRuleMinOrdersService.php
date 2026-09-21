<?php
/**
 * Service "Min commandes" du module dtc_cartrule.
 *
 * Centralise la logique liée à la condition "nombre minimum de commandes
 * validées du client" applicable à une règle panier PrestaShop.
 *
 * Donnée stockée dans la table dédiée `PREFIX_dtc_cartrule_minorders`
 * (1 ligne par règle panier) afin de ne pas modifier `ps_cart_rule`.
 */
class CartRuleMinOrdersService
{
    /** Nom de la table dédiée (sans préfixe). */
    const TABLE_NAME = 'dtc_cartrule_minorders';

    /**
     * Retourne le nombre minimum de commandes requis pour une règle panier.
     * 0 = pas de condition.
     */
    public static function getMinimumOrders(int $idCartRule): int
    {
        if ($idCartRule <= 0) {
            return 0;
        }

        $value = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT `minimum_orders` FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_cart_rule` = ' . $idCartRule
        );

        return (int) $value;
    }

    /**
     * Compte les commandes valides (payées) d'un client.
     */
    public static function countValidOrders(int $idCustomer): int
    {
        if ($idCustomer <= 0) {
            return 0;
        }

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'orders`'
            . ' WHERE `id_customer` = ' . $idCustomer
            . ' AND `valid` = 1'
        );
    }

    /**
     * Vérifie si la règle panier est autorisée pour le client donné
     * vis-à-vis de la condition "nombre minimum de commandes".
     */
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

    /**
     * Enregistre / met à jour la valeur pour une règle.
     * Si $minimumOrders = 0, la ligne est supprimée (pas de condition active).
     */
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
    }

    /**
     * Supprime l'entrée associée à une règle panier.
     */
    public static function delete(int $idCartRule): void
    {
        if ($idCartRule <= 0) {
            return;
        }

        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_cart_rule` = ' . $idCartRule
        );
    }
}
