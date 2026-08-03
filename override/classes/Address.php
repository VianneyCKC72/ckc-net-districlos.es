<?php

/**
 * Limite rue et complément d'adresse à 35 caractères (contrainte postale).
 */
class Address extends AddressCore
{
    public const POSTAL_LINE_MAX_LENGTH = 35;

    public function __construct($id_address = null, $id_lang = null)
    {
        self::applyPostalLineLimits();
        parent::__construct($id_address, $id_lang);
    }

    public static function applyPostalLineLimits(): void
    {
        self::$definition['fields']['address1']['size'] = self::POSTAL_LINE_MAX_LENGTH;
        self::$definition['fields']['address2']['size'] = self::POSTAL_LINE_MAX_LENGTH;
    }
}
