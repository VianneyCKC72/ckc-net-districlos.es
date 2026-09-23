CREATE TABLE IF NOT EXISTS `PREFIX_product_guide` (
  `id_product_guide` int(11) NOT NULL AUTO_INCREMENT,
  `id_product` int(11) NOT NULL,
  `guide_content` text,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_product_guide`),
  UNIQUE KEY `id_product` (`id_product`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

