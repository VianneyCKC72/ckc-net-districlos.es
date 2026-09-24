<?php
/**
 * Upgrade to 1.1.0 — multilang badge / text / link for EN storefront.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    if (!($module instanceof dtc_bandeau_soldes)) {
        return true;
    }

    // Migration is also triggered on FO/BO via migrateToMultilangIfNeeded().
    // Force it once here so EN is filled after module upgrade.
    if (method_exists($module, 'hookDisplayBandeauSoldes')) {
        // Touch config page path: call private migrate through reflection-safe public save path.
        // Re-run install multilang seeding only if keys are not yet lang keys.
        if (!Configuration::isLangKey('DTC_BANDEAU_SOLDES_BADGE')) {
            $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');
            $enLangId = (int) Language::getIdByIso('en');

            $legacyBadge = (string) Configuration::get('DTC_BANDEAU_SOLDES_BADGE');
            $legacyText = (string) Configuration::get('DTC_BANDEAU_SOLDES_TEXT');
            $legacyLink = (string) Configuration::get('DTC_BANDEAU_SOLDES_LINK');

            if ($legacyBadge === '') {
                $legacyBadge = 'BONS PLANS';
            }
            if ($legacyText === '') {
                $legacyText = 'Jusqu\'à -17% sur nos kits !';
            }
            if ($legacyLink === '') {
                $legacyLink = '/400-bons-plans';
            }

            $enFallbacks = [
                'BONS PLANS' => 'GREAT DEALS',
                'Jusqu\'à -17% sur nos kits !' => 'Up to -17% on our kits!',
                'Jusqu\'à <strong>-12%</strong> offerts dès 2000€ d\'achat !' => 'Up to <strong>-12%</strong> off from €2,000 spent!',
            ];

            $badges = [];
            $texts = [];
            $links = [];

            foreach (Language::getLanguages(false) as $lang) {
                $idLang = (int) $lang['id_lang'];
                $badges[$idLang] = $legacyBadge;
                $texts[$idLang] = $legacyText;
                $links[$idLang] = $legacyLink;

                if ($enLangId > 0 && $idLang === $enLangId) {
                    $badges[$idLang] = isset($enFallbacks[$legacyBadge]) ? $enFallbacks[$legacyBadge] : $legacyBadge;
                    $texts[$idLang] = isset($enFallbacks[$legacyText]) ? $enFallbacks[$legacyText] : $legacyText;
                }
            }

            $badges[$defaultLangId] = $legacyBadge;
            $texts[$defaultLangId] = $legacyText;
            $links[$defaultLangId] = $legacyLink;

            Configuration::updateValue('DTC_BANDEAU_SOLDES_BADGE', $badges, true);
            Configuration::updateValue('DTC_BANDEAU_SOLDES_TEXT', $texts, true);
            Configuration::updateValue('DTC_BANDEAU_SOLDES_LINK', $links, true);
        }
    }

    return true;
}
