<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_9_0($module)
{
    $prompt = 'Translate short product badge labels. Preserve HTML tags (<strong>, <b>, <br>), CSS classes and the ↓ character. Keep the result short, similar in length, suitable for a product card flag. Do not invent promotions.';

    return Configuration::updateValue('CKCTRANSLATE_PROMPT_CKC_LABELS', $prompt);
}
