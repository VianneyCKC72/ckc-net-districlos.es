<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Pages système : titres complets (pas de coupe agressive), reprise des mauvaises traductions.
 */
function upgrade_module_2_17_1($module)
{
    unset($module);

    $prompt = 'Translate PrestaShop system page SEO fields (meta title and meta description: homepage, cart, login, contact, sitemap, etc.). Always keep brand names such as Districlos unchanged and place them naturally (usually at the start for the homepage title). Return a COMPLETE title and description, close in length to the source (do not shorten aggressively, do not cut mid-phrase, never end with &, and, of, the, or a dangling comma). Keep a natural, concise SEO tone. Do not invent promotions or claims. Leave empty source fields empty.';

    Configuration::updateValue(CkcTranslate::CONF_PROMPT_SYSTEM_PAGES, $prompt);

    return true;
}
