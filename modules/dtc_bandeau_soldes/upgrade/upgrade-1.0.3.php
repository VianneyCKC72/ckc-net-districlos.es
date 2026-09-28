<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Réenregistre displayBandeauSoldes si absent (module actif sans entrée hook_module).
 * Les associations module_group sont complétées en 1.0.4.
 */
function upgrade_module_1_0_3($module)
{
    if (!($module instanceof Module)) {
        return true;
    }

    return (bool) $module->registerHook('displayBandeauSoldes');
}
