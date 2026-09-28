<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Titles/metas EN restants + descriptions trop longues / marque / phrases collées.
 */
function upgrade_module_2_17_4($module)
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
            'CKC Translate 2.17.4 — SEO EN (names=%d, titles=%d, descs=%d, force=%d, slugs=%d/%d/%d, config=%s).',
            (int) $repair['names'],
            (int) $repair['meta_titles'],
            (int) $repair['meta_descriptions'],
            (int) $repair['force_reset'],
            (int) $repair['categories'],
            (int) $repair['products'],
            (int) $repair['cms'],
            $repair['config_fixed'] ? 'oui' : 'non'
        ),
        1
    );

    return true;
}
