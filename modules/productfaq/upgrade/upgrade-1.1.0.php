<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    require_once _PS_MODULE_DIR_ . 'productfaq/productfaq.php';

    return ProductFaq::ensureLangTable();
}
