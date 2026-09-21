<?php
/**
 * Ajoute la configuration du mapping transporteur des commandes marketplace.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Dtc_lengow_marketplace $module
 *
 * @return bool
 */
function upgrade_module_1_2_0($module)
{
    return $module->installCarrierConfiguration();
}
