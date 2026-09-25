<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Réenregistre le hook + associations module_group (requis pour Hook::exec en FO).
 */
function upgrade_module_1_0_4($module)
{
    if (!($module instanceof dtc_bandeau_soldes)) {
        return true;
    }

    $ok = (bool) $module->registerHook('displayBandeauSoldes');

    if (method_exists($module, 'repairModuleGroupRestrictions')) {
        $ok = $ok && $module->repairModuleGroupRestrictions();
    }

    return $ok;
}
