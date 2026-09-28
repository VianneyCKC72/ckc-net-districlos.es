<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Liens catégories du menu EN : slug canonique, pour éviter les 302.
 */
function upgrade_module_2_16_4($module)
{
    unset($module);
    require_once dirname(__DIR__) . '/classes/CkcMenuCategoryUrls.php';

    CkcMenuCategoryUrls::apply(false);
    Configuration::updateGlobalValue(CkcMenuCategoryUrls::FLAG, '1');

    return true;
}
