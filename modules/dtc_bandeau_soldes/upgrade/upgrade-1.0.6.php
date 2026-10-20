<?php
/**
 * Upgrade to 1.0.6 — safe formatting tags in badge fields.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_6($module)
{
    unset($module);

    return true;
}
