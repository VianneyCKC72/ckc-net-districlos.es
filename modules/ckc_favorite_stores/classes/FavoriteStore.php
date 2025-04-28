<?php
class FavoriteStore extends ObjectModel
{
    public $id_favorite_store;
    public $id_customer;
    public $id_store;
    public $date_add;

    public static $definition = [
        'table' => 'ckc_favorite_stores',
        'primary' => 'id_favorite_store',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'id_store' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public static function getFavoriteStore($id_customer)
    {
        return Db::getInstance()->getValue('
            SELECT id_store FROM ' . _DB_PREFIX_ . 'ckc_favorite_stores
            WHERE id_customer = ' . (int) $id_customer
        );
    }
}
