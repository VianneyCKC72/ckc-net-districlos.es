<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Files d’attente Untranslated : pagination IDs (évite le plafond silencieux à 10).
 */
function upgrade_module_2_17_7($module)
{
    unset($module);

    require_once dirname(__DIR__) . '/classes/CkcTranslateJobRunner.php';
    CkcTranslateJobRunner::resetAllFileQueues();

    return true;
}
