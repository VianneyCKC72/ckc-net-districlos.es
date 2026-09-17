<?php
/**
 * CKC — titres de filtres Advanced Search (colonne gauche) en anglais.
 *
 * Dry-run (défaut) :
 *   php modules/ckccustomfilterselector/script/set_en_filter_labels.php
 *
 * Écriture :
 *   php modules/ckccustomfilterselector/script/set_en_filter_labels.php --apply
 *
 * Options :
 *   --shops=1,2     boutiques (défaut : 1 et 2)
 *   --iso=en        langue cible
 *   --from-iso=fr   langue source pour matcher les libellés
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__FILE__, 4) . '/config/config.inc.php';

$apply = in_array('--apply', $argv, true);
$shops = [1, 2];
$isoTo = 'en';
$isoFrom = 'fr';

foreach ($argv as $arg) {
    if (preg_match('/^--shops=([0-9,]+)$/', $arg, $m)) {
        $shops = array_values(array_unique(array_map('intval', explode(',', $m[1]))));
        $shops = array_filter($shops);
    } elseif (preg_match('/^--iso=([a-z]{2})$/i', $arg, $m)) {
        $isoTo = strtolower($m[1]);
    } elseif (preg_match('/^--from-iso=([a-z]{2})$/i', $arg, $m)) {
        $isoFrom = strtolower($m[1]);
    }
}

$map = [
    'sous-catégories' => 'Subcategories',
    'sous-categories' => 'Subcategories',
    'catégories' => 'Categories',
    'categories' => 'Categories',
    'couleur' => 'Color',
    'hauteur' => 'Height',
    'pose' => 'Installation',
    'passage' => 'Opening width',
    'largeur' => 'Width',
    'avec/sans occultation' => 'With / without privacy',
    'prix' => 'Price',
    'marque' => 'Brand',
    'matière' => 'Material',
    'matiere' => 'Material',
    'type' => 'Type',
    'épaisseur' => 'Thickness',
    'epaisseur' => 'Thickness',
    'longueur' => 'Length',
    'diamètre' => 'Diameter',
    'diametre' => 'Diameter',
    'finition' => 'Finish',
    'usage' => 'Use',
    'forme' => 'Shape',
    'style' => 'Style',
    'fournisseur' => 'Supplier',
    'disponibilité' => 'Availability',
    'disponibilite' => 'Availability',
    'en stock' => 'In stock',
    'nouveautés' => 'New products',
    'nouveautes' => 'New products',
    'promotions' => 'Promotions',
    'conditionnement' => 'Packaging',
];

$db = Db::getInstance();
$prefix = _DB_PREFIX_;

$idLangFrom = (int) $db->getValue(
    'SELECT `id_lang` FROM `' . $prefix . 'lang` WHERE `iso_code` = \'' . pSQL($isoFrom) . '\''
);
$idLangTo = (int) $db->getValue(
    'SELECT `id_lang` FROM `' . $prefix . 'lang`
     WHERE `iso_code` = \'' . pSQL($isoTo) . '\'
        OR `iso_code` = \'gb\'
     ORDER BY CASE WHEN `iso_code` = \'' . pSQL($isoTo) . '\' THEN 0 ELSE 1 END'
);

if ($idLangFrom <= 0 || $idLangTo <= 0) {
    fwrite(STDERR, "Langue introuvable (from={$isoFrom} / to={$isoTo}).\n");
    exit(1);
}

$langTable = ckcAs4FindTable($db, $prefix, [
    'pm_advancedsearch_criterion_group_lang',
    'pm_advancedsearch_criteria_group_lang',
]);
if (!$langTable) {
    fwrite(STDERR, "Table *_criterion_group_lang introuvable.\n");
    exit(1);
}

$groupTable = preg_replace('/_lang$/', '', $langTable);
$pk = ckcAs4FindPk($db, $prefix . $groupTable);
$nameCol = ckcAs4FindNameColumn($db, $prefix . $langTable);
$langCols = ckcAs4ColumnNames($db, $prefix . $langTable);
$hasLangShop = in_array('id_shop', $langCols, true);
$shopTable = ckcAs4TableExists($db, $prefix . 'pm_advancedsearch_shop')
    ? $prefix . 'pm_advancedsearch_shop'
    : '';

echo "Table : {$prefix}{$langTable} (colonne {$nameCol}, pk {$pk})\n";
echo "Langues : {$isoFrom}={$idLangFrom} → {$isoTo}={$idLangTo}\n";
echo 'Boutiques : ' . implode(',', $shops) . "\n";
echo $apply ? "Mode : APPLY\n\n" : "Mode : DRY-RUN (ajouter --apply pour écrire)\n\n";

$shopList = implode(',', array_map('intval', $shops));
$joinShop = '';
$whereShop = '';
$selectShop = '0 AS id_shop, \'-\' AS shop_name';

if ($hasLangShop) {
    $selectShop = 'gl.`id_shop`, COALESCE(s.`name`, CONCAT(\'shop \', gl.`id_shop`)) AS shop_name';
    $joinShop = 'LEFT JOIN `' . $prefix . 'shop` s ON s.`id_shop` = gl.`id_shop`';
    $whereShop = 'AND gl.`id_shop` IN (' . $shopList . ')';
} elseif ($shopTable && ckcAs4TableExists($db, $prefix . $groupTable)) {
    $selectShop = 'ss.`id_shop`, COALESCE(s.`name`, CONCAT(\'shop \', ss.`id_shop`)) AS shop_name';
    $joinShop = 'INNER JOIN `' . $shopTable . '` ss ON ss.`id_search` = g.`id_search`
        LEFT JOIN `' . $prefix . 'shop` s ON s.`id_shop` = ss.`id_shop`';
    $whereShop = 'AND ss.`id_shop` IN (' . $shopList . ')';
}

$sql = 'SELECT g.`' . $pk . '` AS id_group, gl.`' . $nameCol . '` AS fr_name, ' . $selectShop . '
    FROM `' . $prefix . $groupTable . '` g
    INNER JOIN `' . $prefix . $langTable . '` gl
        ON gl.`' . $pk . '` = g.`' . $pk . '` AND gl.`id_lang` = ' . (int) $idLangFrom . '
    ' . $joinShop . '
    WHERE 1 ' . $whereShop . '
    ORDER BY id_shop, id_group';

$rows = $db->executeS($sql);
if (!$rows) {
    echo "Aucun groupe trouvé. Vérifier les id_shop / tables.\n";
    exit(1);
}

$updated = 0;
$inserted = 0;
$skipped = 0;
$unmatched = [];

foreach ($rows as $row) {
    $idGroup = (int) $row['id_group'];
    $idShop = (int) $row['id_shop'];
    $frName = (string) $row['fr_name'];
    $key = ckcAs4Norm($frName);
    $shopLabel = $row['shop_name'] . ($idShop ? ' #' . $idShop : '');

    if (!isset($map[$key])) {
        $unmatched[$frName] = true;
        $skipped++;
        continue;
    }

    $enName = $map[$key];
    $existing = ckcAs4GetLangName($db, $prefix . $langTable, $pk, $nameCol, $idGroup, $idLangTo, $hasLangShop ? $idShop : null);

    if ($existing === $enName) {
        echo "[OK]     {$shopLabel} #{$idGroup} « {$frName} » déjà « {$enName} »\n";
        $skipped++;
        continue;
    }

    $action = ($existing === null) ? 'INSERT' : 'UPDATE';
    echo "[{$action}] {$shopLabel} #{$idGroup} « {$frName} » → « {$enName} »" . ($existing !== null && $existing !== '' ? " (était « {$existing} »)" : '') . "\n";

    if (!$apply) {
        continue;
    }

    if ($existing === null) {
        ckcAs4InsertLang($db, $prefix . $langTable, $pk, $nameCol, $langCols, $idGroup, $idLangFrom, $idLangTo, $enName, $hasLangShop ? $idShop : null);
        $inserted++;
    } else {
        ckcAs4UpdateLang($db, $prefix . $langTable, $pk, $nameCol, $idGroup, $idLangTo, $enName, $hasLangShop ? $idShop : null);
        $updated++;
    }
}

echo "\nRésumé : update={$updated} insert={$inserted} skip={$skipped}" . ($apply ? '' : ' (dry-run)') . "\n";
if ($unmatched) {
    echo "Libellés FR non mappés (à ajouter dans le script si besoin) :\n";
    foreach (array_keys($unmatched) as $label) {
        echo '  - ' . $label . "\n";
    }
}

if ($apply) {
    echo "Penser à vider le cache Smarty / Symfony (et le cache Advanced Search / page cache).\n";
}

function ckcAs4Norm($value)
{
    $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', trim($value));
    $value = str_replace(['’', '`'], "'", $value);

    return mb_strtolower($value, 'UTF-8');
}

function ckcAs4TableExists($db, $table)
{
    $row = $db->executeS('SHOW TABLES LIKE \'' . pSQL($table) . '\'');

    return !empty($row);
}

function ckcAs4FindTable($db, $prefix, array $candidates)
{
    foreach ($candidates as $name) {
        if (ckcAs4TableExists($db, $prefix . $name)) {
            return $name;
        }
    }
    $like = $db->executeS('SHOW TABLES LIKE \'' . pSQL($prefix) . '%advancedsearch%group%lang%\'');
    if (!empty($like)) {
        $full = array_values($like[0]);

        return preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $full[0]);
    }

    return '';
}

function ckcAs4ColumnNames($db, $table)
{
    $cols = $db->executeS('SHOW COLUMNS FROM `' . bqSQL($table) . '`');
    $names = [];
    foreach ($cols as $col) {
        $names[] = $col['Field'];
    }

    return $names;
}

function ckcAs4FindPk($db, $table)
{
    $cols = ckcAs4ColumnNames($db, $table);
    foreach (['id_criterion_group', 'id_criteria_group', 'id_group'] as $pk) {
        if (in_array($pk, $cols, true)) {
            return $pk;
        }
    }

    return $cols[0];
}

function ckcAs4FindNameColumn($db, $table)
{
    $cols = ckcAs4ColumnNames($db, $table);
    foreach (['name', 'public_name', 'label'] as $col) {
        if (in_array($col, $cols, true)) {
            return $col;
        }
    }

    fwrite(STDERR, "Colonne nom introuvable dans {$table}.\n");
    exit(1);
}

function ckcAs4GetLangName($db, $table, $pk, $nameCol, $idGroup, $idLang, $idShop)
{
    $sql = 'SELECT `' . $nameCol . '` FROM `' . bqSQL($table) . '`
        WHERE `' . $pk . '` = ' . (int) $idGroup . '
          AND `id_lang` = ' . (int) $idLang;
    if ($idShop !== null) {
        $sql .= ' AND `id_shop` = ' . (int) $idShop;
    }
    $value = $db->getValue($sql);

    return $value === false ? null : (string) $value;
}

function ckcAs4UpdateLang($db, $table, $pk, $nameCol, $idGroup, $idLang, $enName, $idShop)
{
    $where = '`' . $pk . '` = ' . (int) $idGroup . ' AND `id_lang` = ' . (int) $idLang;
    if ($idShop !== null) {
        $where .= ' AND `id_shop` = ' . (int) $idShop;
    }
    $db->update(
        str_replace(_DB_PREFIX_, '', $table),
        [$nameCol => $enName],
        $where
    );
}

function ckcAs4InsertLang($db, $table, $pk, $nameCol, array $langCols, $idGroup, $idLangFrom, $idLangTo, $enName, $idShop)
{
    $sql = 'SELECT * FROM `' . bqSQL($table) . '`
        WHERE `' . $pk . '` = ' . (int) $idGroup . '
          AND `id_lang` = ' . (int) $idLangFrom;
    if ($idShop !== null) {
        $sql .= ' AND `id_shop` = ' . (int) $idShop;
    }
    $source = $db->getRow($sql);
    if (!$source) {
        $source = [$pk => $idGroup];
        if ($idShop !== null && in_array('id_shop', $langCols, true)) {
            $source['id_shop'] = $idShop;
        }
    }

    $insert = [];
    foreach ($langCols as $col) {
        if ($col === 'id_lang') {
            $insert[$col] = (int) $idLangTo;
        } elseif ($col === $nameCol) {
            $insert[$col] = $enName;
        } elseif ($col === $pk) {
            $insert[$col] = (int) $idGroup;
        } elseif ($col === 'id_shop' && $idShop !== null) {
            $insert[$col] = (int) $idShop;
        } elseif (isset($source[$col])) {
            $insert[$col] = $source[$col];
        }
    }

    $db->insert(str_replace(_DB_PREFIX_, '', $table), $insert);
}
