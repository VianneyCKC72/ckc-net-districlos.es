<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_16_3($module)
{
    return $module->registerHook('actionFrontControllerInitAfter')
        && $module->registerHook('actionPresentProduct')
        && $module->registerHook('actionPresentProductListing');
}
