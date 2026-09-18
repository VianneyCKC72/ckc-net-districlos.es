<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Réalignement hooks : displayBeforeCartOverview + actionFrontControllerSetMedia
 * (le thème cart.tpl appelle displayBeforeCartOverview).
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
