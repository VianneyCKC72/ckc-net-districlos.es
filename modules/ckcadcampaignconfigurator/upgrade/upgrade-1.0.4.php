<?php
/**
 * Upgrade ckcadcampaignconfigurator to 1.0.4
 * i18n panier : phrases promo + entités HTML (bénéficier).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_4($module)
{
    unset($module);

    return true;
}
