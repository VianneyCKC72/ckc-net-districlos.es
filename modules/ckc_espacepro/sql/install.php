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