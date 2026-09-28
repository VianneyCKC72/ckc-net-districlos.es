<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Whitelist exacte des 7 URLs home EN (évite la réécriture générique qui cassait les URLs produit).
 */
function upgrade_module_2_17_6($module)
{
    unset($module);

    require_once dirname(__DIR__) . '/classes/CkcMenuCategoryUrls.php';

    Configuration::deleteByName(CkcMenuCategoryUrls::FLAG);
    $result = CkcMenuCategoryUrls::apply(false);
    Configuration::updateGlobalValue(CkcMenuCategoryUrls::FLAG, '1');

    PrestaShopLogger::addLog(
        sprintf(
            'CKC Translate 2.17.6 — whitelist home EN (slugs=%d, rows=%d, replacements=%d).',
            (int) $result['slugs'],
            (int) $result['rows'],
            (int) $result['replacements']
        ),
        1
    );

    return true;
}
