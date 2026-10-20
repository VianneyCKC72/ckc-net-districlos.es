<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_14_0($module)
{
    $prompt = 'Translate Responsive Gallery page title, descriptions and gallery titles (product family names like Grillage rigide). Keep labels short, catalog-like and consistent with existing fence vocabulary. Preserve HTML in descriptions. Do not invent product lines.';

    if (!Configuration::get(CkcTranslate::CONF_PROMPT_RESPONSIVEGALLERY)) {
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_RESPONSIVEGALLERY, $prompt);
    }

    return true;
}
