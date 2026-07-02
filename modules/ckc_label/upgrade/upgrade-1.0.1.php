<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_1($module)
{
    $columnExists = Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'ckc_labels` LIKE "show_discount_percent"'
    );

    if (empty($columnExists)) {
        return Db::getInstance()->execute(
            'ALTER TABLE `' . _DB_PREFIX_ . 'ckc_labels`
            ADD `show_discount_percent` TINYINT(1) NOT NULL DEFAULT 1 AFTER `text`'
        );
    }

    return true;
}
