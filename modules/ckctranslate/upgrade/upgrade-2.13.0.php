<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_13_0($module)
{
    $prompt = 'Translate customer project photo titles and image alt texts (ckc_realisation). Keep them short, natural and descriptive. Do not invent product names, locations or materials. Leave empty fields empty.';

    if (!Configuration::get(CkcTranslate::CONF_PROMPT_CKC_REALISATION)) {
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_CKC_REALISATION, $prompt);
    }

    return true;
}
