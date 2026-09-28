<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Fix slugs EN tronqués à 10 car (SEO_SLUG_MAX forcé à 10) + realigne le menu.
 */
function upgrade_module_2_17_2($module)
{
    unset($module);

    require_once dirname(__DIR__) . '/classes/CkcTranslateSlugRepair.php';
    require_once dirname(__DIR__) . '/classes/CkcMenuCategoryUrls.php';

    $repair = CkcTranslateSlugRepair::repairEnglish();
    Configuration::deleteByName(CkcMenuCategoryUrls::FLAG);
    CkcMenuCategoryUrls::apply(false);
    Configuration::updateGlobalValue(CkcMenuCategoryUrls::FLAG, '1');

    PrestaShopLogger::addLog(
        sprintf(
            'CKC Translate 2.17.2 — slugs EN réparés (cat=%d, prod=%d, cms=%d, config=%s).',
            (int) $repair['categories'],
            (int) $repair['products'],
            (int) $repair['cms'],
            $repair['config_fixed'] ? 'oui' : 'non'
        ),
        1
    );

    return true;
}
