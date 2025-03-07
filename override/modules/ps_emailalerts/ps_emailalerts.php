<?php
/**
 * Advanced Pack 5
 *
 * @author    Presta-Module.com <support@presta-module.com> - https://www.presta-module.com
 * @copyright Presta-Module - https://www.presta-module.com
 * @license   see file: LICENSE.txt
 *
 *           ____     __  __
 *          |  _ \   |  \/  |
 *          | |_) |  | |\/| |
 *          |  __/   | |  | |
 *          |_|      |_|  |_|
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
class Ps_EmailAlertsOverride extends Ps_EmailAlerts
{
    public function hookActionUpdateQuantity($params)
    {
        if (isset($params['id_product']) && class_exists('AdvancedPack') && AdvancedPack::isValidPack((int)$params['id_product'])) {
            return;
        }
        parent::hookActionUpdateQuantity($params);
    }
}
