<?php
/**
 * Nettoyage des anciennes entrées virtuelles (avec abs)
 * et test des nouveaux hash (signés)
 */

require_once('config/config.inc.php');

echo "<h2>🧹 Nettoyage et test nouveaux hash</h2>";
echo "<pre>";

// Charger le mapping
$mappingFile = _PS_MODULE_DIR_ . 'dtc_productendoflife/reference_mapping.json';
$mappingData = json_decode(file_get_contents($mappingFile), true);
$referenceMapping = $mappingData['mappings'] ?? [];
$brutReferences = array_unique(array_values($referenceMapping));

echo "=== ÉTAPE 1 : Comparaison anciens vs nouveaux hash ===\n\n";
echo "Référence BRUT → Ancien hash (abs) → Nouveau hash (signé)\n";
echo "----------------------------------------------------------------\n";

foreach ($brutReferences as $ref) {
    $oldHash = abs(crc32($ref));
    $newHash = crc32($ref);
    $sign = $newHash < 0 ? '(négatif)' : '(positif)';
    echo "$ref\n";
    echo "  Ancien: $oldHash\n";
    echo "  Nouveau: $newHash $sign\n\n";
}

echo "\n=== ÉTAPE 2 : Entrées virtuelles actuelles ===\n";
$sql = "SELECT id_stock, id_product_attribute, quantity 
        FROM " . _DB_PREFIX_ . "ckcstockstores_stocks 
        WHERE id_product = -1 
        ORDER BY quantity DESC";
$currentEntries = Db::getInstance()->executeS($sql);

echo "Nombre d'entrées virtuelles actuelles: " . count($currentEntries) . "\n";
if (count($currentEntries) > 0) {
    echo "Listing:\n";
    foreach ($currentEntries as $entry) {
        echo "  ID: " . $entry['id_stock'] . " | Hash: " . $entry['id_product_attribute'] . " | Qty: " . $entry['quantity'] . "\n";
    }
}

echo "\n=== ÉTAPE 3 : Nettoyage ===\n";
echo "⚠️ Cette étape va SUPPRIMER toutes les entrées virtuelles existantes\n";
echo "Pour continuer, décommentez la ligne de suppression dans ce script.\n";
echo "\n";

// DÉCOMMENTEZ cette ligne pour effectuer le nettoyage :
// Db::getInstance()->execute("DELETE FROM " . _DB_PREFIX_ . "ckcstockstores_stocks WHERE id_product = -1");
// echo "✅ Anciennes entrées virtuelles supprimées\n";

echo "⚠️ NETTOYAGE NON EFFECTUÉ (ligne commentée)\n";
echo "\nPour nettoyer, exécutez cette requête SQL manuellement :\n";
echo "DELETE FROM ps_ckcstockstores_stocks WHERE id_product = -1;\n";

echo "\n</pre>";



