<?php
/**
 * Classe ObjectModel pour les participations au concours
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ContestEntry extends ObjectModel
{
    public $id_contest_entry;
    public $last_name;
    public $first_name;
    public $phone;
    public $email;
    public $activity;
    public $postal_code;
    public $guess_code;
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'dtc_contest_entry',
        'primary' => 'id_contest_entry',
        'fields' => [
            'last_name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isName',
                'required' => true,
                'size' => 255
            ],
            'first_name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isName',
                'required' => true,
                'size' => 255
            ],
            'phone' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isPhoneNumber',
                'required' => true,
                'size' => 20
            ],
            'email' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isEmail',
                'required' => true,
                'size' => 255
            ],
            'activity' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => false,
                'size' => 255
            ],
            'postal_code' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isPostCode',
                'required' => true,
                'size' => 10
            ],
            'guess_code' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 255
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'required' => true
            ],
        ],
    ];
}