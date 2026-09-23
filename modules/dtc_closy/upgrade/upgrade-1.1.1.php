<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_1($module)
{
    require_once _PS_MODULE_DIR_ . 'dtc_closy/classes/DtcClosyConfig.php';

    return DtcClosyConfig::migrateToMultilang();
}
