<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0($module)
{
    $sql = [];

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
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

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
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

    foreach ($sql as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }

    return true;
}
