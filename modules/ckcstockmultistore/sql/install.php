<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckcstockmultistore` (
    `id_ckcstockmultistore` int(11) NOT NULL AUTO_INCREMENT,
    PRIMARY KEY  (`id_ckcstockmultistore`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckcstockstores_stores` (
            `id_store` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `zip_codes` varchar(255) NOT NULL,
            `iban` varchar(255) DEFAULT NULL,
            `bic` varchar(255) DEFAULT NULL,
            `carrier` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`id_store`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// create ckcstockstores_stocks table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckcstockstores_stocks` (
            `id_stock` int(11) NOT NULL AUTO_INCREMENT,
            `id_store` int(11) NOT NULL,
            `id_product` int(11) NOT NULL,
            `id_product_attribute` int(11) DEFAULT NULL,
            `quantity` int(11) NOT NULL,
            PRIMARY KEY (`id_stock`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$checkColumn = Db::getInstance()->executeS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'orders LIKE "id_store";');

if (empty($checkColumn)) {
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'orders` ADD COLUMN `id_store` INT(11) NULL AFTER `id_cart`;';
}

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        PrestaShopLogger::addLog('SQL Error: ' . Db::getInstance()->getMsgError(), 3);
        return false;
    }else{
        PrestaShopLogger::addLog('SQL Success: ' . $query, 1);
    }
}
