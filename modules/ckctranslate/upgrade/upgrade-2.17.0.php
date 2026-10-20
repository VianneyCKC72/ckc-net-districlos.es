<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Job pages système : meta title / meta description (meta_lang).
 */
function upgrade_module_2_17_0($module)
{
    unset($module);

    $prompt = 'Translate PrestaShop system page SEO fields (meta title and meta description: homepage, cart, login, contact, sitemap, etc.). Keep brand names such as Districlos unchanged. Keep a natural, concise SEO tone. Do not invent promotions or claims. Leave empty source fields empty.';

    if (!Configuration::get(CkcTranslate::CONF_PROMPT_SYSTEM_PAGES)) {
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_SYSTEM_PAGES, $prompt);
    }

    return true;
}
