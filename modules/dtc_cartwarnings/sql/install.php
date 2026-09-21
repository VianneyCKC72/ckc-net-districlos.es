<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_cart_warning_rule` (
    `id_rule` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_parent` INT UNSIGNED DEFAULT NULL,
    `rule_type` VARCHAR(32) NOT NULL,
    `active` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `position` INT NOT NULL DEFAULT 0,
    `missing_product_id` INT UNSIGNED DEFAULT NULL,
    `attribute_product_id` INT UNSIGNED DEFAULT NULL,
    `attribute_contains` VARCHAR(255) DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_rule`),
    KEY `idx_parent` (`id_parent`),
    KEY `idx_type_active` (`rule_type`, `active`),
    KEY `idx_position` (`position`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_cart_warning_rule_lang` (
    `id_rule` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `message_main` TEXT NOT NULL,
    `message_extra` TEXT DEFAULT NULL,
    PRIMARY KEY (`id_rule`, `id_lang`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_cart_warning_rule_product` (
    `id_rule_product` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_rule` INT UNSIGNED NOT NULL,
    `id_product` INT UNSIGNED NOT NULL,
    `role` VARCHAR(16) NOT NULL,
    PRIMARY KEY (`id_rule_product`),
    KEY `idx_rule` (`id_rule`),
    KEY `idx_role` (`role`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
