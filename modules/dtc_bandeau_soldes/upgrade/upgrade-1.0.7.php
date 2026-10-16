<?php
/**
 * Upgrade to 1.0.7 — configurable badge colors.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_7($module)
{
    if (!($module instanceof dtc_bandeau_soldes)) {
        return true;
    }

    return method_exists($module, 'upgradeTo107') ? (bool) $module->upgradeTo107() : true;
}
