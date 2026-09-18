<?php
/**
 * Cron de recalcul des paliers professionnels + notifications e-mail.
 * À exécuter quotidiennement.
 *
 * Exemple : php /path/to/modules/dtc_paliers_remise/cron.php
 * Ou via URL : https://districlos.test/modules/dtc_paliers_remise/cron.php?token=VOTRE_TOKEN
 */

// Sécurité : token obligatoire en mode HTTP
if (php_sapi_name() !== 'cli') {
    $expectedToken = 'dtc_palier_7f3K9mQx2WpR8nYvH4sL6uJc';

    if (!isset($_GET['token']) || $_GET['token'] !== $expectedToken) {
        header('HTTP/1.1 403 Forbidden');
        exit('Accès refusé.');
    }
}

// Bootstrap PrestaShop
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/classes/DtcPalierService.php';
require_once dirname(__FILE__) . '/classes/DtcCartRuleManager.php';
require_once dirname(__FILE__) . '/classes/DtcPalierMailer.php';

$module = Module::getInstanceByName('dtc_paliers_remise');
if ($module && method_exists($module, 'ensureMailSchema')) {
    $module->ensureMailSchema();
}

$proGroupIds = DtcPalierService::getProGroupIds();

if (empty($proGroupIds)) {
    echo "Aucun groupe pro configuré. Fin.\n";
    exit(0);
}

// Récupère tous les clients pro ayant un palier enregistré
$sql = 'SELECT DISTINCT cp.id_customer
        FROM `' . _DB_PREFIX_ . 'dtc_customer_palier` cp
        INNER JOIN `' . _DB_PREFIX_ . 'customer` c ON c.id_customer = cp.id_customer
        INNER JOIN `' . _DB_PREFIX_ . 'customer_group` cg ON cg.id_customer = c.id_customer
        WHERE cg.id_group IN (' . implode(',', array_map('intval', $proGroupIds)) . ')
          AND c.active = 1
          AND c.deleted = 0';

$customers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

$updated = 0;
$mails = ['upgrade' => 0, 'downgrade' => 0, 'warning' => 0];

foreach ($customers as $row) {
    $idCustomer = (int) $row['id_customer'];
    $stats = DtcPalierMailer::processCustomer($idCustomer);
    $mails['upgrade'] += (int) $stats['upgrade'];
    $mails['downgrade'] += (int) $stats['downgrade'];
    $mails['warning'] += (int) $stats['warning'];
    $updated++;
}

echo "Recalcul terminé : {$updated} client(s) traité(s).\n";
echo "Mails — upgrade: {$mails['upgrade']}, downgrade: {$mails['downgrade']}, alerte -14j: {$mails['warning']}.\n";
exit(0);
