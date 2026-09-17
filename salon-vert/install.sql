CREATE TABLE IF NOT EXISTS `ps_salon_vert_inscription` (
  `id_salon_vert_inscription` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `telephone` VARCHAR(30) NOT NULL,
  `societe` VARCHAR(150) NOT NULL,
  `accept_jeu` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `accept_com` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `date_add` DATETIME NOT NULL,
  PRIMARY KEY (`id_salon_vert_inscription`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
