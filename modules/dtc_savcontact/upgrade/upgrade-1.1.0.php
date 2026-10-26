<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param dtc_savcontact $module
 *
 * @return bool
 */
function upgrade_module_1_1_0($module)
{
    $module->unregisterHook('actionAdminCustomerThreadsControllerListingFieldsModifier');

    return $module->registerHook('actionObjectCustomerMessageAddBefore')
        && $module->registerHook('actionAdminCustomerThreadsListingFieldsModifier')
        && $module->migrateAutoMessagesToPrivate();
}
