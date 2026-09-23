<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_15_0($module)
{
    $prompt = 'Translate product FAQ questions and answers for a fencing e-commerce catalog. Preserve all HTML tags, attributes and lists. Keep a clear, helpful customer-support tone. Do not invent technical specs, dimensions, warranties or certifications. Keep brand names and product terms consistent.';

    if (!Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_FAQ)) {
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_PRODUCT_FAQ, $prompt);
    }

    return true;
}
