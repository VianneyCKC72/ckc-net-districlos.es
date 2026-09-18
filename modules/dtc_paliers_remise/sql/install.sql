CREATE TABLE IF NOT EXISTS `PREFIX_dtc_customer_palier` (
    `id_customer` INT UNSIGNED NOT NULL,
    `current_tier` VARCHAR(30) NOT NULL DEFAULT 'none',
    `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `cumulated_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `id_cart_rule` INT UNSIGNED DEFAULT NULL,
    `last_computed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_notified_tier` VARCHAR(30) DEFAULT NULL,
    `warning_from_tier` VARCHAR(30) DEFAULT NULL,
    `warning_sent_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
