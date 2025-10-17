<?php
/*
$sql[] = "ALTER TABLE `ps_customer` 
ADD COLUMN `pays` INT DEFAULT NULL,
ADD COLUMN `tva` TINYINT NOT NULL DEFAULT '0',
ADD COLUMN `adresse` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
ADD COLUMN `adresse2` VARCHAR(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
ADD COLUMN `cp` VARCHAR(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
ADD COLUMN `ville` VARCHAR(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
ADD COLUMN `tel` VARCHAR(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
ADD COLUMN `fileUpload` VARCHAR(255) COLLATE utf8mb4_general_ci DEFAULT NULL;";

foreach ($sql as $update) {
    Db::getInstance()->execute($update);
}*/


    $sql = "
        CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ckc_sections_guides_fiches` (
            `id_section` INT AUTO_INCREMENT PRIMARY KEY,
            `id_category` INT NOT NULL,
            `title_section` VARCHAR(255) NOT NULL,
            `ordering` INT DEFAULT 0,
            `active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;";
    
    return Db::getInstance()->execute($sql);