<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Home EN : slugs FR en 302 → URLs EN 200 (tuiles + footer CE / contenus).
 */
function upgrade_module_2_17_5($module)
{
    unset($module);

    require_once dirname(__DIR__) . '/classes/CkcMenuCategoryUrls.php';

    Configuration::deleteByName(CkcMenuCategoryUrls::FLAG);
    $result = CkcMenuCategoryUrls::apply(false);
    Configuration::updateGlobalValue(CkcMenuCategoryUrls::FLAG, '1');

    PrestaShopLogger::addLog(
        sprintf(
            'CKC Translate 2.17.5 — liens home EN (slugs=%d, rows=%d, replacements=%d).',
            (int) $result['slugs'],
            (int) $result['rows'],
            (int) $result['replacements']
        ),
        1
    );

    return true;
}
