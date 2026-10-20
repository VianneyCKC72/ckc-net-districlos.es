<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Répare titres / metas / slugs EN tronqués à 10 car + realigne le menu.
 */
function upgrade_module_2_17_3($module)
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
            'CKC Translate 2.17.3 — SEO EN réparé (names=%d, meta_title=%d, meta_desc=%d, slugs cat/prod/cms=%d/%d/%d, config=%s).',
            (int) $repair['names'],
            (int) $repair['meta_titles'],
            (int) $repair['meta_descriptions'],
            (int) $repair['categories'],
            (int) $repair['products'],
            (int) $repair['cms'],
            $repair['config_fixed'] ? 'oui' : 'non'
        ),
        1
    );

    return true;
}
