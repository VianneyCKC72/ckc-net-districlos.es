<?php
/**
 * Cron de synchronisation des références BRUT (mapping fin de vie)
 * À lancer régulièrement via crontab
 */

// Chemin vers PrestaShop
require_once dirname(__FILE__) . '/../../config/config.inc.php';

$startTime = microtime(true);
$log = [];

try {
    // Connexion BDD TOOLS
    $db = new PDO('mysql:host=localhost', 'districlos_refonte', 'TXM1qjk9teu_cfb9wxv', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    ]);
    $log[] = "[OK] Connexion TOOLS établie";

    // Mapping agence-dépôt vers ID boutique
    $agencyDepotMap = [
        '283-83' => 15, '227-27' => 6, '271-71' => 2, '257-57' => 5, '34-34' => 8,
        '262-62' => 10, '273-73' => 13, '249-49' => 9, '235-35' => 3, '263-63' => 1,
        '226-26' => 4, '277-77' => 14, '233-33' => 7, '269-69' => 12, '231-31' => 11,
        '28B-831' => 16, '245-45' => 17, 'DX-DX' => 18
    ];

    // Charger le mapping de références
    $mappingFile = dirname(__FILE__) . '/reference_mapping.json';
    if (!file_exists($mappingFile)) {
        throw new Exception("Fichier mapping introuvable : " . $mappingFile);
    }

    $mappingData = json_decode(file_get_contents($mappingFile), true);
    if (!isset($mappingData['mappings'])) {
        throw new Exception("Clé 'mappings' manquante dans le JSON");
    }

    $targetReferences = array_unique(array_values($mappingData['mappings']));
    $log[] = "[OK] " . count($targetReferences) . " références BRUT à synchroniser";

    $database1 = 'districlos_refonte';
    $database2 = 'districlostools';

    $totalInserted = 0;
    $totalUpdated = 0;

    // Pour chaque référence BRUT
    foreach ($targetReferences as $targetRef) {
        // Chercher dans TOOLS
        $stmt = $db->prepare("SELECT AGENCE, DEPOT, STOCK_DISPONIBLE FROM `" . $database2 . "`.`rep_stock` WHERE REFERENCE = :reference");
        $stmt->bindValue(':reference', $targetRef, PDO::PARAM_STR);
        $stmt->execute();
        $brutStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($brutStocks as $brutStock) {
            $key = $brutStock['AGENCE'] . '-' . $brutStock['DEPOT'];

            if (!isset($agencyDepotMap[$key])) {
                continue;
            }

            $shop = $agencyDepotMap[$key];
            $stockDisponible = (int) $brutStock['STOCK_DISPONIBLE'];
            if ($stockDisponible < 0) {
                $stockDisponible = 0;
            }

            // Hash compatible INT(11) : garder seulement 31 bits (max 2147483647)
            $refHash = crc32($targetRef) & 0x7FFFFFFF;

            // Vérifier si existe déjà
            $stmtCheck = $db->prepare("SELECT * FROM `".$database1."`.`ps_ckcstockstores_stocks` WHERE id_store = :shop AND id_product = -1 AND id_product_attribute = :refHash LIMIT 1");
            $stmtCheck->bindValue(':shop', $shop, PDO::PARAM_INT);
            $stmtCheck->bindValue(':refHash', $refHash, PDO::PARAM_INT);
            $stmtCheck->execute();
            $existingStock = $stmtCheck->fetch();

            if ($existingStock) {
                // UPDATE
                $stmtUpdate = $db->prepare("UPDATE `".$database1."`.`ps_ckcstockstores_stocks` SET quantity = :quantity WHERE id_store = :shop AND id_product = -1 AND id_product_attribute = :refHash");
                $stmtUpdate->bindValue(':quantity', $stockDisponible, PDO::PARAM_INT);
                $stmtUpdate->bindValue(':shop', $shop, PDO::PARAM_INT);
                $stmtUpdate->bindValue(':refHash', $refHash, PDO::PARAM_INT);
                $stmtUpdate->execute();
                $totalUpdated++;
            } else {
                // INSERT
                $stmtInsert = $db->prepare("INSERT INTO `".$database1."`.`ps_ckcstockstores_stocks` (id_store, id_product, id_product_attribute, quantity) VALUES (:shop, -1, :refHash, :quantity)");
                $stmtInsert->bindValue(':shop', $shop, PDO::PARAM_INT);
                $stmtInsert->bindValue(':refHash', $refHash, PDO::PARAM_INT);
                $stmtInsert->bindValue(':quantity', $stockDisponible, PDO::PARAM_INT);
                $stmtInsert->execute();
                $totalInserted++;
            }
        }
    }

    $log[] = "[OK] Synchronisation terminée : " . $totalInserted . " inserts, " . $totalUpdated . " updates";

    // Nettoyer les entrées obsolètes
    if (!empty($targetReferences)) {
        $currentHashes = [];
        foreach ($targetReferences as $targetRef) {
            $currentHashes[] = crc32($targetRef) & 0x7FFFFFFF;
        }
        $hashList = implode(',', $currentHashes);
        $stmtDelete = $db->prepare("DELETE FROM `".$database1."`.`ps_ckcstockstores_stocks` WHERE id_product = -1 AND id_product_attribute NOT IN (".$hashList.")");
        $stmtDelete->execute();
        $deletedCount = $stmtDelete->rowCount();
        if ($deletedCount > 0) {
            $log[] = "[OK] " . $deletedCount . " entrées obsolètes supprimées";
        }
    }

    $duration = round(microtime(true) - $startTime, 2);
    $log[] = "[OK] Durée : " . $duration . " secondes";

    $status = "success";

} catch (Exception $e) {
    $log[] = "[ERREUR] " . $e->getMessage();
    $status = "error";
}

// Afficher le résultat
header('Content-Type: application/json');
echo json_encode([
    'status' => $status,
    'log' => $log,
    'timestamp' => date('Y-m-d H:i:s')
]);

