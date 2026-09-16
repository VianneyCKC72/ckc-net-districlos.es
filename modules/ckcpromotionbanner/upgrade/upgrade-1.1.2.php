<?php
/**
 * Upgrade to 1.1.2 — migrate legacy promo texts into multilang rows (no wipe).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_2($module)
{
    if (!$module instanceof Ckcpromotionbanner) {
        return true;
    }

    foreach (array('CKC_BARREPROMO_TEXTE', 'CKC_BARREPROMO_TEXTE_PRO') as $configKey) {
        migratePromoBannerConfigKey($module, $configKey);
    }

    return true;
}

/**
 * @param Ckcpromotionbanner $module
 * @param string $configKey
 */
function migratePromoBannerConfigKey($module, $configKey)
{
    $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');
    if ($defaultLangId <= 0) {
        return;
    }

    $legacyRaw = '';
    $idShop = Shop::isFeatureActive() ? (int) Shop::getContextShopID(true) : 0;
    $idShopGroup = Shop::isFeatureActive() ? (int) Shop::getContextShopGroupID(true) : 0;

    $sql = 'SELECT `value` FROM `' . _DB_PREFIX_ . 'configuration`
        WHERE `name` = \'' . pSQL($configKey) . '\'';
    if ($idShop > 0) {
        $sql .= ' AND `id_shop` = ' . (int) $idShop;
    } elseif ($idShopGroup > 0) {
        $sql .= ' AND `id_shop_group` = ' . (int) $idShopGroup . ' AND (`id_shop` IS NULL OR `id_shop` = 0)';
    } else {
        $sql .= ' AND (`id_shop` IS NULL OR `id_shop` = 0)
            AND (`id_shop_group` IS NULL OR `id_shop_group` = 0)';
    }

    $legacyRaw = (string) Db::getInstance()->getValue($sql);
    if ($legacyRaw === '') {
        $legacyRaw = (string) Db::getInstance()->getValue(
            'SELECT `value` FROM `' . _DB_PREFIX_ . 'configuration`
            WHERE `name` = \'' . pSQL($configKey) . '\'
            ORDER BY `id_shop` DESC, `id_shop_group` DESC'
        );
    }

    $legacyArr = array();
    if ($legacyRaw !== '') {
        $decoded = @unserialize(base64_decode($legacyRaw));
        if (is_array($decoded)) {
            $legacyArr = $decoded;
        }
    }

    if (empty($legacyArr)) {
        return;
    }

    $currentDefault = '';
    if (Configuration::isLangKey($configKey)) {
        $currentDefault = (string) Configuration::get($configKey, $defaultLangId);
    }

    $currentArr = array();
    if ($currentDefault !== '') {
        $decoded = @unserialize(base64_decode($currentDefault));
        if (is_array($decoded)) {
            $currentArr = $decoded;
        }
    }

    if (!empty($currentArr)) {
        return;
    }

    $values = array();
    foreach (Language::getLanguages(false) as $lang) {
        $idLang = (int) $lang['id_lang'];
        if ($idLang === $defaultLangId) {
            $values[$idLang] = base64_encode(serialize($legacyArr));
            continue;
        }

        $raw = Configuration::isLangKey($configKey)
            ? (string) Configuration::get($configKey, $idLang)
            : '';
        $arr = array();
        if ($raw !== '') {
            $decoded = @unserialize(base64_decode($raw));
            if (is_array($decoded)) {
                $arr = $decoded;
            }
        }
        $values[$idLang] = base64_encode(serialize($arr));
    }

    Configuration::updateValue($configKey, $values);
}
