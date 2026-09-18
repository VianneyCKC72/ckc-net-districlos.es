<?php
/**
* 2007-2024 PrestaShop
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
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_countdown_setter` (
    `id_dtc_countdown_setter` int(11) NOT NULL AUTO_INCREMENT,
    `label` varchar(255) NOT NULL,
    `bg_color` varchar(7) DEFAULT NULL,
    `txt_color` varchar(7) DEFAULT NULL,
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `cta` tinyint(1) NOT NULL DEFAULT 0,
    `cta_label` varchar(255) DEFAULT NULL,
    `cta_link` varchar(255) DEFAULT NULL,
    `cta_bg_color` varchar(7) DEFAULT NULL,
    `cta_txt_color` varchar(7) DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `selectors` text DEFAULT NULL,
    PRIMARY KEY  (`id_dtc_countdown_setter`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Ajouter la colonne selectors si elle n'existe pas déjà
$sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'dtc_countdown_setter` ADD COLUMN IF NOT EXISTS `selectors` text DEFAULT NULL AFTER `active`;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

return true;

