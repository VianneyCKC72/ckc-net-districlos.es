<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    $ok = true;
    $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
    if ($idLangDefault <= 0) {
        $idLangDefault = 1;
    }

    $ok = $ok && Db::getInstance()->execute(
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckc_labels_lang` (
            `id_label` INT(11) NOT NULL,
            `id_lang` INT(11) NOT NULL,
            `text` TEXT NOT NULL,
            PRIMARY KEY (`id_label`, `id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
    );

    $ok = $ok && Db::getInstance()->execute(
        'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'ckc_labels_lang` (`id_label`, `id_lang`, `text`)
         SELECT `id_label`, ' . $idLangDefault . ', `text`
         FROM `' . _DB_PREFIX_ . 'ckc_labels`
         WHERE `text` IS NOT NULL AND TRIM(`text`) != \'\''
    );

    return $ok;
}
