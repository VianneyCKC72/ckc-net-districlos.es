ALTER TABLE `PREFIX_pm_advancedpack_products`
  ADD COLUMN `height_impact` tinyint(1) unsigned NOT NULL DEFAULT 0;

ALTER TABLE `PREFIX_pm_advancedpack_products_attributes`
  ADD COLUMN `height_value` text NULL DEFAULT NULL;
