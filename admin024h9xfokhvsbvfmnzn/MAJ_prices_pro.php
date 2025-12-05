<?php
/**
 * Import prix PRO multi-groupes pour PrestaShop 8
 * - Lit un CSV par groupe (un fichier par groupe)
 * - Upsert dans ps_specific_price (prix fixes "amount")
 * - Utilise la BDD du PrestaShop installé (pas de credentials en dur)
 *
 * Pré-requis:
 * - Déposer les CSV dans /admin/prices (par défaut) ou configurer via CKC_PRO_PRICE_DIR
 * - Nommage des fichiers: EDI_TARIF_XXX_G{IDGROUPE}.csv   ex: EDI_TARIF_DV_WEBPRO_G10.csv
 * - La config CKC_GROUP_PRO_IDS contient la liste des id_group pro (JSON "[10,12]" ou "10,12")
 */

define('_PS_ADMIN_DIR_', __DIR__);
require_once dirname(__DIR__) . '/config/config.inc.php';
require_once dirname(__DIR__) . '/init.php';


// Dossier des CSV : config > CKC_PRO_PRICE_DIR sinon /admin/prices
$priceDir = rtrim((string) Configuration::get('CKC_PRO_PRICE_DIR', null, null, null, __DIR__ . '/prices'), '/');

// Pattern de fichier : fonction qui construit le chemin à partir de l'id groupe.
// => Adapte ici si tes fichiers ont un autre schéma de nommage.
$csvPathForGroup = function (string $gid) use ($priceDir): string {
	if($gid == "RED") {
		return $priceDir . '/EDI_TARIF_DV_CLT_' . $gid . '.csv';
	} else if ($gid == "Professionnels") {
		return $priceDir . '/EDI_TARIF_DV_WEBPRO.csv';
	} else {
		return $priceDir . '/EDI_TARIF_DV_WEB' . $gid . '.csv';
	}
	// exemple: EDI_TARIF_DV_WEBPRO{gid}.csv
};

// Délimiteur CSV
$csvDelimiter = ';';

// Colonnage attendu (adapter si besoin) :
// 0: (ignored/code) | 1: id_product | 2: id_product_attribute | 3: price_ht
$COL_ID_PRODUCT           = 1;
$COL_ID_PRODUCT_ATTRIBUTE = 2;
$COL_PRICE_HT             = 3;

// Portée du prix (toutes boutiques)
$ID_SHOP       = 0;
$ID_SHOP_GROUP = 0;
$ID_COUNTRY    = 0;
$ID_CURRENCY   = 0;
$ID_CUSTOMER   = 0;

function parseGroupIds($raw): array {
	if ($raw === null || $raw === '') {
		return [];
	}
	// Si JSON valide
	$decoded = json_decode($raw, true);
    $groupNames = [];
	if (is_array($decoded)) {
		foreach (array_values(array_unique(array_map('intval', $decoded))) as $group) {

			$groupName = Db::getInstance()->getValue('SELECT name FROM ' . _DB_PREFIX_ . 'group_lang WHERE id_group = '.(int)$group);
			$groupInfo['id'] = (int)$group;
			$groupInfo['name'] = (string)$groupName;

			$groupNames[] = $groupInfo;
		}
		return $groupNames;
		/*		return array_values(array_unique(array_map('intval', $decoded)));*/
	}
	// Sinon CSV
	$parts = array_map('trim', explode(',', (string)$raw));
	$ids = [];
	foreach ($parts as $p) {
		if ($p !== '' && is_numeric($p)) {
			$ids[] = (int)$p;
		}
	}
	foreach (array_values(array_unique($ids)) as $id) {
		$groupName = Db::getInstance()->getValue('SELECT name FROM ' . _DB_PREFIX_ . 'group_lang WHERE id_group = '.(int)$id);
		$groupInfo['id'] = (int)$id;
		$groupInfo['name'] = (string)$groupName;

		$groupNames[] = $groupInfo;
	}
	return $groupNames;
}

$rawFromRequest = Tools::getValue('CKC_GROUP_PRO_IDS', null);

$rawFromConfig  = ($rawFromRequest === null)
	? Configuration::get('CKC_GROUP_PRO_IDS', '')
	: null;

$groupIds = parseGroupIds($rawFromRequest ?? $rawFromConfig);

if (empty($groupIds)) {
	echo "[INFO] Aucun groupe PRO à traiter (CKC_GROUP_PRO_IDS vide ou invalide).\n";
	exit(0);
}


function yieldCsvRows(string $filepath, string $delimiter = ';'): Generator {
	if (!is_readable($filepath)) {
		throw new RuntimeException("Fichier illisible: $filepath");
	}
	$h = fopen($filepath, 'r');
	if ($h === false) {
		throw new RuntimeException("Impossible d'ouvrir: $filepath");
	}
	try {
		while (($row = fgetcsv($h, 0, $delimiter)) !== false) {
			// Normalise taille du tableau
			yield array_map(static function($v) {
				return is_string($v) ? trim($v) : $v;
			}, $row);
		}
	} finally {
		fclose($h);
	}
}

function parsePrice($v): ?float {
	if ($v === null) return null;
	$s = str_replace([' ', "\xC2\xA0"], '', (string)$v); // espaces
	// remplace virgule par point
	$s = str_replace(',', '.', $s);
	if ($s === '' || !is_numeric($s)) return null;
	return (float)$s;
}

$db = Db::getInstance();

$tableSpecificPrice = _DB_PREFIX_ . 'specific_price';
$tableProduct       = _DB_PREFIX_ . 'product';

// Select SP existante
$sqlSelectSP = "SELECT id_specific_price
                FROM `$tableSpecificPrice`
                WHERE id_product = %d
                  AND id_product_attribute = %d
                  AND id_group = %d
                  AND id_shop = $ID_SHOP
                  AND id_shop_group = $ID_SHOP_GROUP
                  AND id_currency = $ID_CURRENCY
                  AND id_country = $ID_COUNTRY
                  AND id_customer = $ID_CUSTOMER";
/*                LIMIT 1";*/

// Update SP
$sqlUpdateSP = "UPDATE `$tableSpecificPrice`
                SET price = %s
                WHERE id_specific_price = %d";

// Insert SP
$sqlInsertSP = "INSERT INTO `$tableSpecificPrice`
    (`id_specific_price_rule`,`id_cart`,`id_product`,`id_shop`,`id_shop_group`,
     `id_currency`,`id_country`,`id_group`,`id_customer`,`id_product_attribute`,
     `price`,`from_quantity`,`reduction`,`reduction_tax`,`reduction_type`,`from`,`to`)
    VALUES (0,0,%d,$ID_SHOP,$ID_SHOP_GROUP,
            $ID_CURRENCY,$ID_COUNTRY,%d,$ID_CUSTOMER,%d,
            %s,1,0,1,'amount','0000-00-00 00:00:00','0000-00-00 00:00:00')";

$productExistsCache = [];

$start = microtime(true);
$totalInserts = 0;
$totalUpdates = 0;
$totalSkipped = 0;

echo "[INFO] Dossier CSV: $priceDir\n";
echo "[INFO] Groupes à traiter: " . implode(',', array_map(fn($value): int => $value['id'], $groupIds)) . "\n";

$db->execute('START TRANSACTION');

try {

	foreach ($groupIds as $gid) {

		$csvPath = $csvPathForGroup((string)$gid['name']);
		if (!file_exists($csvPath)) {
			echo "[WARN] Fichier introuvable pour le groupe ".$gid['name']." : $csvPath — on passe.\n";
			continue;
		}

		echo "[INFO] Import CSV groupe ".$gid['name']." : $csvPath\n";

		$lineNo = 0;
		foreach (yieldCsvRows($csvPath, $csvDelimiter) as $row) {
			$lineNo++;

			// Skip header si besoin (détecte si première ligne contient des lettres)
			if ($lineNo === 1 && isset($row[$COL_ID_PRODUCT]) && !is_numeric($row[$COL_ID_PRODUCT])) {
				continue;
			}

			$idProduct  = isset($row[$COL_ID_PRODUCT]) ? (int)$row[$COL_ID_PRODUCT] : 0;
			$idPA       = isset($row[$COL_ID_PRODUCT_ATTRIBUTE]) ? (int)$row[$COL_ID_PRODUCT_ATTRIBUTE] : 0;
			$priceHT    = isset($row[$COL_PRICE_HT]) ? parsePrice($row[$COL_PRICE_HT]) : null;

			if ($idProduct <= 0 || $priceHT === null) {
				$totalSkipped++;
				continue;
			}

			// Vérifier que le produit existe (minimise les requêtes)
			if (!isset($productExistsCache[$idProduct])) {
				$exists = (bool) $db->getValue('SELECT 1 FROM `'.$tableProduct.'` WHERE id_product='.(int)$idProduct);
				/*				$exists = (bool) $db->getValue('SELECT 1 FROM `'.$tableProduct.'` WHERE id_product='.(int)$idProduct.' LIMIT 1');*/
				$productExistsCache[$idProduct] = $exists;
			}
			if (!$productExistsCache[$idProduct]) {
				$totalSkipped++;
				continue;
			}

			// Cherche SP existante
			$idSpecificPrice = (int) $db->getValue(sprintf(
				$sqlSelectSP,
				(int)$idProduct,
				(int)$idPA,
				(int)$gid['id']
			));

			if ($idSpecificPrice > 0) {
				// UPDATE
				$ok = $db->execute(sprintf(
					$sqlUpdateSP,
					pSQL((string)$priceHT),
					(int)$idSpecificPrice
				));
				if ($ok) {
					$totalUpdates++;
				} else {
					echo "[ERR] UPDATE échec (id_specific_price=$idSpecificPrice, line=$lineNo, gid=".$gid['id'].")\n";
				}
			} else {
				// INSERT
				$ok = $db->execute(sprintf(
					$sqlInsertSP,
					(int)$idProduct,
					(int)$gid['id'],
					(int)$idPA,
					pSQL((string)$priceHT)
				));
				if ($ok) {
					$totalInserts++;
				} else {
					echo "[ERR] INSERT échec (product=$idProduct, pa=$idPA, gid=".$gid['id'].", line=$lineNo)\n";
				}
			}
		}
	}

	$db->execute('COMMIT');

} catch (Throwable $e) {
	$db->execute('ROLLBACK');
	echo "[FATAL] " . $e->getMessage() . "\n";
	http_response_code(500);
	exit(1);
}

$dur = round(microtime(true) - $start, 3);
echo "[DONE] Inserts: $totalInserts, Updates: $totalUpdates, Skipped: $totalSkipped, Time: {$dur}s\n";
