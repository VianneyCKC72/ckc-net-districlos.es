<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    require_once dirname(__DIR__) . '/classes/CkcHelloworkI18nHelper.php';
    CkcHelloworkI18nHelper::ensureSql();

    return true;
}
