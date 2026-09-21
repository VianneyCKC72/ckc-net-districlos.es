<?php
/**
 * DTC_CART_RESERVATION_FIX_3.1.4
 *
 * Upgrade prod : Modules → dtc_cart_reservation → Mettre à jour
 * (ou vérifier que ce fichier existe après git pull).
 *
 * Réalignement hooks :
 * - displayBeforeCartOverview + actionFrontControllerSetMedia
 * - retire displayShoppingCartFooter / displayHeader (v2.x)
 *
 * Vérif : grep -n "DTC_CART_RESERVATION_FIX_3.1.4" modules/dtc_cart_reservation/upgrade/upgrade-3.1.4.php
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * DTC_CART_RESERVATION_FIX_3.1.4 — le thème cart.tpl appelle displayBeforeCartOverview.
 */
function upgrade_module_3_1_4($module)
{
    $ok = true;

    if (!$module->isRegisteredInHook('displayBeforeCartOverview')) {
        $ok = $ok && $module->registerHook('displayBeforeCartOverview');
    }

    if (!$module->isRegisteredInHook('actionFrontControllerSetMedia')) {
        $ok = $ok && $module->registerHook('actionFrontControllerSetMedia');
    }

    // Anciens hooks v2.x (prod) — plus utilisés
    if ($module->isRegisteredInHook('displayShoppingCartFooter')) {
        $ok = $ok && $module->unregisterHook('displayShoppingCartFooter');
    }

    if ($module->isRegisteredInHook('displayHeader')) {
        $ok = $ok && $module->unregisterHook('displayHeader');
    }

    return $ok;
}
