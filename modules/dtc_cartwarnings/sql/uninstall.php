<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

$tables = [
    'dtc_cart_warning_rule_product',
    'dtc_cart_warning_rule_lang',
    'dtc_cart_warning_rule',
];

foreach ($tables as $table) {
    if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`')) {
        return false;
    }
}

return true;
