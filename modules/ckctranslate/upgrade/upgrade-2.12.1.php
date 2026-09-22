<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_12_1($module)
{
    require_once dirname(__DIR__) . '/classes/CkcTranslateCfuI18n.php';
    CkcTranslateCfuI18n::ensureTable();

    return (bool) $module->registerHook('actionOutputHTMLBefore');
}
