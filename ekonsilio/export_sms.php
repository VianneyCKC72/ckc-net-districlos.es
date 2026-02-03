<?php
/**
 * Script Ekonsilio - Export CSV des devis non commandés et non expirés à J+8
 *
 * Génère un fichier export_ekonsilio_sms.csv des devis (ps_opartdevis) :
 * - Non commandés (status != ORDERED, id_order vide)
 * - Non expirés (date_add entre J-8 et J-7)
 * - Montant TTC > EKONSILIO_MONTANT_MIN (Configuration ou 500€ par défaut)
 *
 * Colonnes : Nom / Prénom / Tel / Date devis / Nom de l'employee / Montant TTC
 *
 * Prévoir CRON quotidien à 2h :
 * 0 2 * * * php /chemin/ekonsilio/export_sms.php
 *
 * @see OpartQuotation::NOT_VALIDATED = 0, VALIDATED = 1, ORDERED = 2, EXPIRED = 3, DECLINED = 4
 */

// Bootstrap PrestaShop
$baseDir = dirname(__DIR__);
require $baseDir . '/config/config.inc.php';
require_once $baseDir . '/init.php';
require_once _PS_MODULE_DIR_ . 'opartdevis/models/OpartQuotation.php';

// Constantes
define('EKONSILIO_EXPORT_DIR', __DIR__ . '/export');
define('EKONSILIO_ARCHIVES_DIR', EKONSILIO_EXPORT_DIR . '/archives');
define('EKONSILIO_CSV_FILENAME', 'export_ekonsilio_sms.csv');
define('EKONSILIO_MONTANT_MIN_DEFAULT', 500);

/**
 * Retourne le montant minimum configuré (Configuration ou défaut).
 */
function getMontantMin(): int
{
    $value = (int) Configuration::get('EKONSILIO_MONTANT_MIN');
    return $value > 0 ? $value : EKONSILIO_MONTANT_MIN_DEFAULT;
}

/**
 * Vérifie si un numéro de téléphone est valide (non vide, non 0).
 */
function isValidPhone(string $tel): bool
{
    $tel = trim($tel);
    $normalized = preg_replace('/\s+/', '', $tel);
    return $tel !== ''
        && $normalized !== ''
        && (int) $normalized !== 0
        && !preg_match('/^0+$/', $normalized);
}

/**
 * Extrait le prénom (dernier mot) du nom du devis (ex. "Devis Thibaut" -> "Thibaut").
 */
function extractEmployeeFirstname(string $devisName): string
{
    $trimmed = trim($devisName);
    $lastSpace = strrpos($trimmed, ' ');
    return $lastSpace === false ? $trimmed : substr($trimmed, $lastSpace + 1);
}

/**
 * Crée les dossiers si nécessaire
 */
function ensureDirectories(): void
{
    if (!is_dir(EKONSILIO_EXPORT_DIR)) {
        mkdir(EKONSILIO_EXPORT_DIR, 0755, true);
    }
    if (!is_dir(EKONSILIO_ARCHIVES_DIR)) {
        mkdir(EKONSILIO_ARCHIVES_DIR, 0755, true);
    }
}

/**
 * Récupère les devis éligibles : pas commandés, pas expirés, créés entre J-8 et J-7
 * Montant calculé via Cart::getOrderTotal() (natif PrestaShop), filtre selon Configuration
 *
 * @return array
 */
function getQuotesForExport(): array
{
    $excludedStatuses = [
        OpartQuotation::ORDERED,
        OpartQuotation::EXPIRED,
        OpartQuotation::DECLINED,
    ];

    $query = new DbQuery();
    $query->select('d.id_opartdevis, d.id_cart, d.id_customer, d.name, c.lastname AS nom, c.firstname AS prenom');
    $query->select("COALESCE(NULLIF(TRIM(ad.phone_mobile), ''), NULLIF(TRIM(ad.phone), '')) AS tel");
    $query->select('d.date_add AS date_devis');
    $query->from('opartdevis', 'd');
    $query->innerJoin('cart', 'ct', 'ct.id_cart = d.id_cart');
    $query->innerJoin('customer', 'c', 'c.id_customer = d.id_customer');
    $query->leftJoin(
        'address',
        'ad',
        'ad.id_address = ct.id_address_invoice AND ad.id_customer = d.id_customer'
    );
    $query->where('d.status NOT IN (' . implode(', ', array_map('intval', $excludedStatuses)) . ')');
    $query->where('(d.id_order IS NULL OR d.id_order = 0)');
    $query->where('d.date_add >= DATE_SUB(NOW(), INTERVAL 8 DAY)');
    $query->where('d.date_add < DATE_SUB(NOW(), INTERVAL 7 DAY)');
    $query->orderBy('d.date_add DESC');

    $rows = Db::getInstance()->executeS($query);
    if (!is_array($rows)) {
        return [];
    }

    $context = Context::getContext();
    $montantMin = getMontantMin();
    $result = [];
    $seenIds = [];
    $seenCustomers = [];
    $shopCache = [];
    $currencyCache = [];

    foreach ($rows as $row) {
        $idDevis = (int) $row['id_opartdevis'];
        if (isset($seenIds[$idDevis])) {
            continue;
        }

        $idCustomer = (int) ($row['id_customer'] ?? 0);
        if ($idCustomer > 0 && isset($seenCustomers[$idCustomer])) {
            continue;
        }

        $cart = new Cart((int) $row['id_cart']);
        if (!Validate::isLoadedObject($cart)) {
            continue;
        }

        // Contexte requis pour le calcul des prix (CRON)
        $context->cart = $cart;
        $context->shop = $shopCache[$cart->id_shop] ?? ($shopCache[$cart->id_shop] = new Shop($cart->id_shop));
        $context->currency = $currencyCache[$cart->id_currency]
            ?? ($currencyCache[$cart->id_currency] = new Currency($cart->id_currency));

        $montantTtc = (float) $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
        if ($montantTtc <= $montantMin) {
            continue;
        }

        $tel = isset($row['tel']) ? trim((string) $row['tel']) : '';
        if (!isValidPhone($tel)) {
            continue;
        }

        $seenIds[$idDevis] = true;
        if ($idCustomer > 0) {
            $seenCustomers[$idCustomer] = true;
        }
        $row['nom_employee'] = extractEmployeeFirstname($row['name'] ?? '');
        $row['montant_ttc'] = number_format($montantTtc, 2, ',', ' ');
        unset($row['id_cart'], $row['name'], $row['id_customer']);

        $result[] = $row;
    }

    return $result;
}

/**
 * Écrit les lignes dans un fichier CSV (séparateur ; pour Excel FR)
 */
function writeCsv(string $filepath, array $rows): bool
{
    $handle = fopen($filepath, 'w');
    if (!$handle) {
        return false;
    }

    // BOM UTF-8 pour Excel
    fwrite($handle, "\xEF\xBB\xBF");

    $columns = ['nom', 'prenom', 'tel', 'date_devis', 'nom_employee', 'montant_ttc'];
    $headers = ['Nom', 'Prénom', 'Tel', 'Date devis', 'Nom de l\'employee', 'Montant TTC'];
    fputcsv($handle, $headers, ';');

    foreach ($rows as $row) {
        $line = [];
        foreach ($columns as $col) {
            $line[] = isset($row[$col]) ? (string) $row[$col] : '';
        }
        fputcsv($handle, $line, ';');
    }

    fclose($handle);
    return true;
}

/**
 * Archive le fichier actuel avant de générer le nouveau
 */
function archiveCurrentFile(): void
{
    $currentPath = EKONSILIO_EXPORT_DIR . '/' . EKONSILIO_CSV_FILENAME;
    if (!is_file($currentPath)) {
        return;
    }

    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $archiveName = 'export_ekonsilio_sms_' . $yesterday . '.csv';
    $archivePath = EKONSILIO_ARCHIVES_DIR . '/' . $archiveName;

    rename($currentPath, $archivePath);
}

// --- Exécution ---
try {
    ensureDirectories();

    // 1. Archiver le fichier existant (pour CRON quotidien)
    archiveCurrentFile();

    // 2. Récupérer les devis
    $quotes = getQuotesForExport();

    // 3. Générer le CSV
    $csvPath = EKONSILIO_EXPORT_DIR . '/' . EKONSILIO_CSV_FILENAME;
    $written = writeCsv($csvPath, $quotes);

    if (!$written) {
        throw new RuntimeException('Impossible d\'écrire le fichier CSV.');
    }

    $count = count($quotes);
    if (php_sapi_name() === 'cli') {
        echo "Export terminé : {$count} devis exportés vers " . EKONSILIO_CSV_FILENAME . "\n";
    } else {
        echo "Export terminé : {$count} devis exportés vers " . EKONSILIO_CSV_FILENAME;
    }
} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, 'Erreur : ' . $e->getMessage() . "\n");
    } else {
        echo 'Erreur : ' . htmlspecialchars($e->getMessage());
    }
    exit(1);
}
