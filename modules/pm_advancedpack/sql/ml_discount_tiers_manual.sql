-- Remise par tranches de ML (JSON: [{"from":0,"to":15,"amount":0.2},...])
-- Si ml_discount_step existe déjà (ancienne version), on ajoute quand même ml_discount_tiers.

ALTER TABLE `PREFIX_pm_advancedpack`
  ADD COLUMN `ml_discount_tiers` text NULL DEFAULT NULL AFTER `ml_choose`;
