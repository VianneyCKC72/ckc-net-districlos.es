<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dtc_product_questions`;';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dtc_product_questions_log`;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

return true;
