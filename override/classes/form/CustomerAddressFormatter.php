<?php

/**
 * Masque société / TVA sur les formulaires d'adresse PART,
 * et limite rue / complément à 35 caractères (PART + PRO).
 */
class CustomerAddressFormatter extends CustomerAddressFormatterCore
{
    public function getFormat()
    {
        Address::applyPostalLineLimits();

        $format = parent::getFormat();

        if (!self::isProFrontOffice()) {
            unset($format['company'], $format['vat_number']);
        }

        foreach (['address1', 'address2'] as $fieldName) {
            if (isset($format[$fieldName])) {
                $format[$fieldName]->setMaxLength(Address::POSTAL_LINE_MAX_LENGTH);
            }
        }

        return $format;
    }

    /**
     * Aligné sur ckc_espacepro::isProEnvironment()
     */
    public static function isProFrontOffice(): bool
    {
        $context = Context::getContext();
        if (!$context || !$context->shop) {
            return false;
        }

        if ((int) $context->shop->id === 2) {
            return true;
        }

        $proUrl = (string) Configuration::get('CKC_PRO_URL_BOUTIQUE');
        if ($proUrl !== '') {
            return stripos(Tools::getShopDomainSsl(true), $proUrl) !== false;
        }

        return false;
    }
}
