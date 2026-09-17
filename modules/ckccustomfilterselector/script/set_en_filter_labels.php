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
    'largeur du portail/largeur de passage' => 'Gate width / Opening width',
    'largeur du portail / largeur de passage' => 'Gate width / Opening width',
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

$langTables = ckcAs4FindAllLangTables($db, $prefix);
if (!$langTables) {
    fwrite(STDERR, "Table *_criterion_group_*_lang introuvable.\n");
    exit(1);
}

$shopTable = ckcAs4TableExists($db, $prefix . 'pm_advancedsearch_shop')
    ? $prefix . 'pm_advancedsearch_shop'
    : '';
$shopList = implode(',', array_map('intval', $shops));

echo 'Tables : ' . count($langTables) . ' moteur(s) de recherche' . "\n";
echo "Langues : {$isoFrom}={$idLangFrom} → {$isoTo}={$idLangTo}\n";
echo 'Boutiques : ' . implode(',', $shops) . "\n";
echo $apply ? "Mode : APPLY\n\n" : "Mode : DRY-RUN (ajouter --apply pour écrire)\n\n";

$updated = 0;
$inserted = 0;
$skipped = 0;
$unmatched = [];
$anyRow = false;

foreach ($langTables as $langTable) {
    $groupTable = preg_replace('/_lang$/', '', $langTable);
    if (!ckcAs4TableExists($db, $prefix . $groupTable)) {
        continue;
    }

    $pk = ckcAs4FindPk($db, $prefix . $groupTable);
    $nameCol = ckcAs4FindNameColumn($db, $prefix . $langTable);
    $langCols = ckcAs4ColumnNames($db, $prefix . $langTable);
    $groupCols = ckcAs4ColumnNames($db, $prefix . $groupTable);
    $hasLangShop = in_array('id_shop', $langCols, true);
    $hasGroupSearch = in_array('id_search', $groupCols, true);
    $idSearchFromName = 0;
    if (preg_match('/_(\d+)$/', $groupTable, $m)) {
        $idSearchFromName = (int) $m[1];
    }

    $joinShop = '';
    $whereShop = '';
    $selectShop = '0 AS id_shop, \'-\' AS shop_name';

    if ($hasLangShop) {
        $selectShop = 'gl.`id_shop`, COALESCE(s.`name`, CONCAT(\'shop \', gl.`id_shop`)) AS shop_name';
        $joinShop = 'LEFT JOIN `' . $prefix . 'shop` s ON s.`id_shop` = gl.`id_shop`';
        $whereShop = 'AND gl.`id_shop` IN (' . $shopList . ')';
    } elseif ($shopTable && $hasGroupSearch) {
        $selectShop = 'ss.`id_shop`, COALESCE(s.`name`, CONCAT(\'shop \', ss.`id_shop`)) AS shop_name';
        $joinShop = 'INNER JOIN `' . $shopTable . '` ss ON ss.`id_search` = g.`id_search`
            LEFT JOIN `' . $prefix . 'shop` s ON s.`id_shop` = ss.`id_shop`';
        $whereShop = 'AND ss.`id_shop` IN (' . $shopList . ')';
    } elseif ($shopTable && $idSearchFromName > 0) {
        $selectShop = 'ss.`id_shop`, COALESCE(s.`name`, CONCAT(\'shop \', ss.`id_shop`)) AS shop_name';
        $joinShop = 'INNER JOIN `' . $shopTable . '` ss ON ss.`id_search` = ' . $idSearchFromName . '
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
    if (!$rows && $whereShop !== '') {
        $sql = 'SELECT g.`' . $pk . '` AS id_group, gl.`' . $nameCol . '` AS fr_name,
                ' . ($idSearchFromName ?: 0) . ' AS id_shop, CONCAT(\'id_search \', ' . ($idSearchFromName ?: 0) . ') AS shop_name
            FROM `' . $prefix . $groupTable . '` g
            INNER JOIN `' . $prefix . $langTable . '` gl
                ON gl.`' . $pk . '` = g.`' . $pk . '` AND gl.`id_lang` = ' . (int) $idLangFrom . '
            ORDER BY id_group';
        $rows = $db->executeS($sql);
    }
    if (!$rows) {
        continue;
    }

    $anyRow = true;
    echo "--- {$prefix}{$langTable}" . ($idSearchFromName ? " (id_search={$idSearchFromName})" : '') . " ---\n";

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
            if ($action === 'INSERT') {
                $inserted++;
            } else {
                $updated++;
            }
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

    echo "\n";
}

if (!$anyRow) {
    echo "Aucun groupe trouvé pour les boutiques " . $shopList . ".\n";
    exit(1);
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

function ckcAs4FindAllLangTables($db, $prefix)
{
    $safePrefix = str_replace(['%', '_'], ['\%', '\_'], $prefix);
    $pattern = $safePrefix . 'pm\_advancedsearch\_criterion\_group%\_lang';
    $rows = $db->executeS('SHOW TABLES LIKE \'' . $pattern . '\'');
    $tables = [];
    foreach ($rows as $row) {
        $full = array_values($row);
        $tables[] = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', (string) $full[0]);
    }
    $tables = array_values(array_unique($tables));
    sort($tables);

    return $tables;
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
