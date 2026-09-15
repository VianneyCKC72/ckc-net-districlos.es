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

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckcstorespresentation` (
    `id_ckcstorespresentation` int(11) NOT NULL AUTO_INCREMENT,
    `departement` varchar(255) NOT NULL,
    `adresse` varchar(255) NOT NULL,
    `ville` varchar(255) NOT NULL,
    `code_postal` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `telephone` varchar(255) NOT NULL,
    `horaires_jours` varchar(255) NOT NULL,
    `horaires_heures` varchar(255) NOT NULL,
    `horaires_samedi` varchar(255) NULL,
    `horaires_info_suppl` varchar(500) NULL,
    `img_path` varchar(255) NOT NULL,
    `latitude` varchar(255) NOT NULL,
    `longitude` varchar(255) NOT NULL,
    `id_multiboutique` int(11) NOT NULL,
    `id_cms_detail` int(11) NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY  (`id_ckcstorespresentation`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckcstorespresentation_favourite` (
    `id_favourite` int(11) NOT NULL AUTO_INCREMENT,
    `id_ckcstorespresentation` int(11) NOT NULL,
    `id_customer` int(11) NOT NULL,
    PRIMARY KEY  (`id_favourite`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'presentation_place` (
    `id_presentation_place` int(11) NOT NULL AUTO_INCREMENT,
    `id_ckcstorespresentation` int(11) NOT NULL,
    `place_id` varchar(255) NOT NULL DEFAULT "",
    `google_rating` decimal(3,1) NULL DEFAULT NULL,
    `google_rating_count` int(11) NULL DEFAULT NULL,
    `google_rating_updated_at` datetime NULL DEFAULT NULL,
    `google_maps_uri` varchar(512) NULL DEFAULT NULL,
    PRIMARY KEY (`id_presentation_place`),
    KEY `id_ckcstorespresentation` (`id_ckcstorespresentation`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'presentation_reviews` (
    `id_review` int(11) NOT NULL AUTO_INCREMENT,
    `id_ckcstorespresentation` int(11) NOT NULL,
    `author_name` varchar(255) NOT NULL DEFAULT "",
    `rating` tinyint(1) NOT NULL DEFAULT 5,
    `review_text` text NULL,
    `review_date` datetime NOT NULL,
    `google_review_id` varchar(255) NULL DEFAULT NULL,
    PRIMARY KEY (`id_review`),
    KEY `id_ckcstorespresentation` (`id_ckcstorespresentation`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
