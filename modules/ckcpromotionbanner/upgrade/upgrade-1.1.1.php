<?php
/**
 * Upgrade to 1.1.1 — multilang promo texts: safer save (no wipe of other languages).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_1($module)
{
    return true;
}
