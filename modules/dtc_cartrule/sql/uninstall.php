<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dtc_cartrule_minorders`;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
