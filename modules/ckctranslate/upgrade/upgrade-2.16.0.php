<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_16_0($module)
{
    $prompt = 'Translate the product measuring guide HTML (how to measure a fence, posts, panels). Preserve all HTML tags, attributes, images, lists and layout. Keep a clear instructional tone. Do not invent dimensions, tools or steps. Keep brand and product names.';

    if (!Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_GUIDE)) {
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_PRODUCT_GUIDE, $prompt);
    }

    return true;
}
