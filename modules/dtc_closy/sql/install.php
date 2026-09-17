<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_product_questions` (
    `id_question` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT(10) UNSIGNED NOT NULL,
    `id_lang` INT(10) UNSIGNED NOT NULL,
    `position` TINYINT(2) UNSIGNED NOT NULL DEFAULT 0,
    `question` VARCHAR(255) NOT NULL,
    `hash_source` CHAR(40) DEFAULT NULL,
    `generated_at` DATETIME DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_question`),
    KEY `idx_product_lang` (`id_product`, `id_lang`, `position`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_product_questions_log` (
    `id_log` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT(10) UNSIGNED NOT NULL,
    `id_lang` INT(10) UNSIGNED NOT NULL,
    `status` ENUM(\'success\',\'error\',\'skipped\') NOT NULL DEFAULT \'success\',
    `tokens_input` INT(11) UNSIGNED DEFAULT 0,
    `tokens_output` INT(11) UNSIGNED DEFAULT 0,
    `error_message` TEXT,
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id_log`),
    KEY `idx_product_lang_log` (`id_product`, `id_lang`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

return true;
