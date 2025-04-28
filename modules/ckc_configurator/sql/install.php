<?php

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `ps_configurator_sessions` (
  `id_configurator_session` int(11) NOT NULL,
  `user_key` varchar(255) NOT NULL,
  `id_category` int(11) DEFAULT NULL,
  `id_type` int(11) DEFAULT NULL,
  `length` float DEFAULT NULL,
  `installation` varchar(255) DEFAULT NULL,
  `height` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `default_session` tinyint(4) NOT NULL DEFAULT 0,
  `deconseil` tinyint(4) NOT NULL DEFAULT 0,
  `id_product_attribute` int(11) NOT NULL,
  `date_add` date NOT NULL,
  `date_upd` date NOT NULL,
  `depart_mur` int(11) DEFAULT NULL,
  `nombre_angle` int(11) DEFAULT NULL,
  `is_aluminium` tinyint(1) NOT NULL DEFAULT 0,
  `espacement` int(11) DEFAULT NULL,
  `poteaux` int(11) DEFAULT NULL,
  `clips` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `ps_configurator_session_entries` (
  `id_configurator_session_entry` int(11) NOT NULL,
  `id_configurator_session` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `id_product_attribute` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `date_add` date NOT NULL,
  `date_upd` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
';


