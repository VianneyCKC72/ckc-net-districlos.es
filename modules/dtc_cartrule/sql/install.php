<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_cartrule_minorders` ('
    . ' `id_cart_rule` INT UNSIGNED NOT NULL,'
    . ' `minimum_orders` INT UNSIGNED NOT NULL DEFAULT 0,'
    . ' PRIMARY KEY (`id_cart_rule`),'
    . ' KEY `idx_minimum_orders` (`minimum_orders`)'
    . ') ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
