<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_10_0($module)
{
    $prompt = 'Translate job offer titles and descriptions for a careers page. Keep a professional recruitment tone. Preserve HTML tags. Do not invent benefits, salaries or locations. Keep H/F and similar gender marks when present, adapted to English (M/F).';

    return Configuration::updateValue('CKCTRANSLATE_PROMPT_HELLOWORK_JOBS', $prompt);
}
