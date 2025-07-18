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

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckccampaign_config_list` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `id_client_group` varchar(255) NULL,
    `active` tinyint NOT NULL,
    `logo_path` varchar(500) NULL,
    `franco_impact` decimal(20,6) NULL,
    `franco_impact_pro` decimal(20,6) NULL,
    `date_start` datetime NOT NULL,
    `date_end` datetime NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_campaign` int(11) NOT NULL,
    `img_path` varchar(500) NULL,
    `link` varchar(500) NULL,
    `active` tinyint NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckccampaign_config_content_header` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_campaign` int(11) NOT NULL,
    `active` tinyint NOT NULL,
    `text` varchar(5000) NOT NULL,
    `background_color` varchar(20) NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_campaign` int(11) NOT NULL,
    `text` varchar(20000) NOT NULL,
    `background_color` varchar(20) NULL,
    `active` tinyint NOT NULL,
    `show_all` tinyint NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckccampaign_config_content_cart` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_campaign` int(11) NOT NULL,
    `is_fixed` tinyint NOT NULL,
    `discount_fixed` decimal(20,6) NULL,
    `price_fixed` decimal(20,6) NULL,
    `discount_range` decimal(20,6) NULL,
    `discount_range_max` decimal(20,6) NULL,
    `price_range_min` decimal(20,6) NULL,
    `price_range_max` decimal(20,6) NULL,
    `text` varchar(20000) NOT NULL,
    `text_color` varchar(20) NULL,
    `active` tinyint NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckccampaign_config_category_show` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_campaign` int(11) NOT NULL,
    `id_category` int(11) NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_campaign` int(11) NOT NULL,
    `text` varchar(20000) NOT NULL,
    `active` tinyint NOT NULL,
    `show_all` tinyint NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckccampaign_config_product_show` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_product_content` int(11) NOT NULL,
    `id_product` int(11) NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckccampaign_config_content_devis` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_campaign` int(11) NOT NULL,
    `text` varchar(20000) NOT NULL,
    `background_color` varchar(20) NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckccampaign_config_content_promopage` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_campaign` int(11) NOT NULL,
    `text` varchar(20000) NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY  (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
