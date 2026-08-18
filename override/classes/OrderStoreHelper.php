<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Magasin de référence lié à une commande (colonne orders.id_store).
 */
class OrderStoreHelper
{
    /**
     * Magasin + téléphone présentation en une seule requête.
     *
     * @return array{id_store?: int, name?: string, iban?: string, bic?: string, presentation_phone?: string}|null
     */
    public static function getStoreForOrder(int $idOrder): ?array
    {
        if ($idOrder <= 0) {
            return null;
        }

        $row = Db::getInstance()->getRow(
            'SELECT o.`id_store`, s.*, p.`telephone` AS `presentation_phone`
            FROM `' . _DB_PREFIX_ . 'orders` o
            LEFT JOIN `' . _DB_PREFIX_ . 'ckcstockstores_stores` s ON s.`id_store` = o.`id_store`
            LEFT JOIN `' . _DB_PREFIX_ . 'ckcstorespresentation` p ON p.`id_multiboutique` = o.`id_store`
            WHERE o.`id_order` = ' . $idOrder
        );

        return $row ?: null;
    }
}
