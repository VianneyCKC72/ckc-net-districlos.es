CREATE TABLE IF NOT EXISTS `ps_ckc_realisation` (
                                                    `id_realisation` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                                    `id_shop` INT UNSIGNED NOT NULL,
                                                    `id_store` INT UNSIGNED NOT NULL,
                                                    `id_customer` INT UNSIGNED NULL,
                                                    `filename` VARCHAR(255) NOT NULL,
    `mime` VARCHAR(64) NOT NULL,
    `filesize` INT UNSIGNED NOT NULL DEFAULT 0,
    `source` ENUM('admin','client') NOT NULL DEFAULT 'client',
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `date_add` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_upd` DATETIME NULL,
    INDEX (`id_shop`),
    INDEX (`id_store`),
    INDEX (`id_customer`),
    INDEX (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ps_ckc_realisation_lang` (
                                                         `id_realisation` INT UNSIGNED NOT NULL,
                                                         `id_lang` INT UNSIGNED NOT NULL,
                                                         `title` VARCHAR(255) NULL,
    `alt` VARCHAR(255) NULL,
    PRIMARY KEY (`id_realisation`,`id_lang`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
