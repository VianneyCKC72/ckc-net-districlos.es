<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * 1.0.4 → 1.0.5 : dates/couleurs, programmation (bascule), config globale unique.
 */
function upgrade_module_1_0_5($module)
{
    if (!($module instanceof dtc_bandeau_soldes)) {
        return true;
    }

    return method_exists($module, 'upgradeTo105') ? (bool) $module->upgradeTo105() : true;
}
