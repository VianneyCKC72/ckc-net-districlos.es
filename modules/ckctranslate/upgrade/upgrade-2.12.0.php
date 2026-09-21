<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_12_0($module)
{
    require_once dirname(__DIR__) . '/classes/CkcTranslateCfuI18n.php';
    CkcTranslateCfuI18n::ensureTable();

    $prompt = 'Translate Contact Form Ultimate (ets_cfu) texts: form titles, URL aliases, SEO, descriptions, field labels, placeholders, help texts, dropdown/radio options, submit buttons, thank-you pages and notification emails. Preserve HTML tags, attributes, links, emails, phone numbers and brand names. If a field is JSON, keep the JSON structure and translate string values only. Keep URL aliases slug-safe. Do not invent legal clauses.';
    Configuration::updateValue('CKCTRANSLATE_PROMPT_ETS_CFU', $prompt);

    return (bool) $module->registerHook('actionOutputHTMLBefore');
}
