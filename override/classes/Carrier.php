<?php
/**
 *  Order Fees Shipping
 *
 *  @author    motionSeed <ecommerce@motionseed.com>
 *  @copyright 2017 motionSeed. All rights reserved.
 *  @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
class Carrier extends CarrierCore
{
    public static function getIdTaxRulesGroupByIdCarrier($id_carrier, Context $context = null)
    {
        $ctx  = Context::getContext();
        $cart = $ctx->cart;
        $address = new Address((int) $cart->id_address_delivery, (int) Context::getContext()->language->id);

        if ($cart && $cart->id && self::isFr($address) && self::isAttested((int)$cart->id) && !empty($id_carrier)) {
            return 2;
        } else {
            return parent::getIdTaxRulesGroupByIdCarrier($id_carrier, $context);
        }
    }

    protected static function isFr(Address $addr): bool
    {
        return $addr->id_country && strtoupper(Country::getIsoById((int)$addr->id_country)) === 'FR';
    }

    protected static function isAttested(int $idCart): bool
    {
        $table = _DB_PREFIX_.'dealt_tva_attestation';
        $exists = (bool)Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".pSQL($table)."'"
        );
        if (!$exists) return false;
        return (bool)Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM `$table` WHERE id_cart=".(int)$idCart." AND is_validated=1"
        );
    }
}