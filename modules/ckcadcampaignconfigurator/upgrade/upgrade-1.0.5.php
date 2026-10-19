<?php
/**
 * Upgrade ckcadcampaignconfigurator to 1.0.5.
 * Product promotion countdown configuration.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_5($module)
{
    unset($module);

    $table = _DB_PREFIX_ . 'ckccampaign_config_content_product';
    $db = Db::getInstance();

    $showCountdownExists = (bool)$db->getValue(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = "' . pSQL(_DB_NAME_) . '"
        AND TABLE_NAME = "' . pSQL($table) . '"
        AND COLUMN_NAME = "show_countdown"'
    );

    if (!$showCountdownExists && !$db->execute(
        'ALTER TABLE `' . bqSQL($table) . '`
        ADD `show_countdown` tinyint NOT NULL DEFAULT 0 AFTER `show_above_price`'
    )) {
        return false;
    }

    $countdownEndExists = (bool)$db->getValue(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = "' . pSQL(_DB_NAME_) . '"
        AND TABLE_NAME = "' . pSQL($table) . '"
        AND COLUMN_NAME = "countdown_end"'
    );

    if (!$countdownEndExists && !$db->execute(
        'ALTER TABLE `' . bqSQL($table) . '`
        ADD `countdown_end` datetime NULL DEFAULT NULL AFTER `show_countdown`'
    )) {
        return false;
    }

    return true;
}
