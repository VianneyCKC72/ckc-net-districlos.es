<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Compteur Untranslated : un succès IA compte même si le texte reste égal à la source
 * (délais "48h", codes RAL, etc.). File features/attributes paginée (plafond Softizy).
 */
function upgrade_module_2_17_8($module)
{
    unset($module);

    require_once dirname(__DIR__) . '/classes/CkcTranslateJobRunner.php';
    CkcTranslateJobRunner::resetAllFileQueues();

    return true;
}
