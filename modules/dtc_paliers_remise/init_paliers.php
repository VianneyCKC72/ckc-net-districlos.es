<?php
/**
 * Initialisation one-shot des paliers Club Pro pour TOUS les clients professionnels.
 * Recalcule le cumul, synchronise les règles panier, pose last_notified_tier.
 * N'envoie AUCUN e-mail.
 *
 * CLI (recommandé) :
 *   php modules/dtc_paliers_remise/init_paliers.php
 *   php modules/dtc_paliers_remise/init_paliers.php --dry-run
 *
 * HTTP :
 *   /modules/dtc_paliers_remise/init_paliers.php?token=VOTRE_TOKEN
 *   /modules/dtc_paliers_remise/init_paliers.php?token=VOTRE_TOKEN&dry_run=1
 *
 * À lancer une fois à l'activation du programme, puis supprimer ou laisser inutilisé.
 */

if (php_sapi_name() !== 'cli') {
    $expectedToken = 'CHANGEZ_MOI_EN_PRODUCTION';

    if (!isset($_GET['token']) || $_GET['token'] !== $expectedToken) {
        header('HTTP/1.1 403 Forbidden');
        exit('Accès refusé.');
    }
}

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/classes/DtcPalierService.php';
require_once dirname(__FILE__) . '/classes/DtcCartRuleManager.php';

$isCli = (php_sapi_name() === 'cli');
$dryRun = false;

if ($isCli) {
    $dryRun = in_array('--dry-run', $argv ?? [], true);
} else {
    $dryRun = (int) Tools::getValue('dry_run') === 1;
}

function init_log(string $message): void
{
    echo $message . "\n";
    if (php_sapi_name() !== 'cli') {
        @ob_flush();
        @flush();
    }
}

$module = Module::getInstanceByName('dtc_paliers_remise');
if (!$module || !(int) $module->id) {
    init_log('Module dtc_paliers_remise introuvable / non installé.');
    exit(1);
}

if (method_exists($module, 'ensureMailSchema')) {
    $module->ensureMailSchema();
}

DtcCartRuleManager::ensureSharedResources();

$proGroupIds = DtcPalierService::getProGroupIds();
if (empty($proGroupIds)) {
    init_log('Aucun groupe pro configuré (CKC_GROUP_PRO_ID). Fin.');
    exit(0);
}

$inGroups = implode(',', array_map('intval', $proGroupIds));

// Tous les clients actifs dans le(s) groupe(s) Club Pro (pas seulement ceux déjà en table palier)
$sql = 'SELECT DISTINCT c.id_customer
        FROM `' . _DB_PREFIX_ . 'customer` c
        INNER JOIN `' . _DB_PREFIX_ . 'customer_group` cg ON cg.id_customer = c.id_customer
        WHERE cg.id_group IN (' . $inGroups . ')
          AND c.active = 1
          AND c.deleted = 0
        ORDER BY c.id_customer ASC';

$customers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
if (!$customers) {
    init_log('Aucun client pro trouvé. Fin.');
    exit(0);
}

$total = count($customers);
init_log(($dryRun ? '[DRY-RUN] ' : '') . "Initialisation de {$total} client(s) pro — aucun mail ne sera envoyé.");

$ok = 0;
$skipped = 0;
$errors = 0;
$byTier = [];

foreach ($customers as $i => $row) {
    $idCustomer = (int) $row['id_customer'];

    try {
        if (!DtcPalierService::isProCustomer($idCustomer)) {
            $skipped++;
            continue;
        }

        if ($dryRun) {
            $amount = DtcPalierService::getCumulatedAmount($idCustomer);
            $tier = DtcPalierService::getTierForAmount($amount);
            $tierName = $tier ? (string) $tier['name'] : 'none';
            $byTier[$tierName] = ($byTier[$tierName] ?? 0) + 1;
            $ok++;
        } else {
            $tier = DtcPalierService::recompute($idCustomer);
            DtcCartRuleManager::syncCartRule($idCustomer, $tier);

            $after = DtcPalierService::getCustomerTier($idCustomer);
            $tierName = $after ? (string) ($after['current_tier'] ?? 'none') : 'none';

            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'dtc_customer_palier`
                 SET `last_notified_tier` = \'' . pSQL($tierName) . '\',
                     `warning_from_tier` = NULL,
                     `warning_sent_at` = NULL
                 WHERE `id_customer` = ' . (int) $idCustomer
            );

            $byTier[$tierName] = ($byTier[$tierName] ?? 0) + 1;
            $ok++;
        }
    } catch (Exception $e) {
        $errors++;
        init_log("Erreur client #{$idCustomer} : " . $e->getMessage());
    }

    if ((($i + 1) % 100) === 0) {
        init_log('… ' . ($i + 1) . '/' . $total);
    }
}

init_log('');
init_log(($dryRun ? '[DRY-RUN] ' : '') . "Terminé — traités: {$ok}, ignorés: {$skipped}, erreurs: {$errors}.");
foreach ($byTier as $name => $count) {
    init_log("  - {$name}: {$count}");
}
init_log('Aucun e-mail envoyé.');

exit($errors > 0 ? 1 : 0);
