<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * MODE TEST — AUCUNE ÉCRITURE
 * Filtre : éléments modifiés côté A dans les 30 derniers jours (via ps_product.date_upd / ps_category.date_upd)
 */
const ID_LANG = 1; // même langue A et B
const ID_SHOP = 1; // même shop A et B

$tz = new DateTimeZone('Europe/Paris');
$now = new DateTime('now', $tz);
$startDate = (clone $now)->modify('-30 days');   // <-- fenêtre mobile 30 jours
$previewLimit = 500; // 0 = illimité

// Connexions PDO
$dbA = new PDO(
    'mysql:host=87.98.141.171;dbname=districlosnew;charset=utf8',
    'districlosnew',
    '11Atb2I20TJ6joDc',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$dbB = new PDO(
    'mysql:host=87.98.141.171;dbname=districlos_refonte;charset=utf8',
    'districlos_refonte',
    'TXM1qjk9teu_cfb9wxv',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function ellipsize($t,$l=200){ $t=trim(strip_tags((string)$t)); return mb_strlen($t)<=$l?$t:mb_substr($t,0,$l).'…'; }
function diffChanged($a,$b){
    $na=preg_replace('/\s+/u',' ',trim((string)$a));
    $nb=preg_replace('/\s+/u',' ',trim((string)$b));
    return $na !== $nb;
}

// --- DIFFS PRODUITS (filtrés par pA.date_upd >= :dateFrom) ---
$productDiffs = [];
$sqlA = "SELECT plA.id_product, plA.description, plA.description_short
         FROM ps_product_lang plA
         INNER JOIN ps_product pA ON pA.id_product = plA.id_product AND pA.date_upd >= :dateFrom
         WHERE plA.id_lang = :idLang AND plA.id_shop = :idShop";
$stmtA=$dbA->prepare($sqlA);
$stmtA->execute([
                    ':dateFrom' => $startDate->format('Y-m-d H:i:s'),
                    ':idLang'   => ID_LANG,
                    ':idShop'   => ID_SHOP,
                ]);
$rowsA=$stmtA->fetchAll();

if ($rowsA) {
    $sqlB = "SELECT plB.id_product, plB.description, plB.description_short
             FROM ps_product_lang plB
             WHERE plB.id_lang = :idLang AND plB.id_shop = :idShop";
    $stmtB=$dbB->prepare($sqlB);
    $stmtB->execute([':idLang'=>ID_LANG, ':idShop'=>ID_SHOP]);
    $mapB=[]; foreach($stmtB->fetchAll() as $rB){ $mapB[(int)$rB['id_product']]=$rB; }

    foreach($rowsA as $rA){
        $pid=(int)$rA['id_product'];
        if(!isset($mapB[$pid])) continue; // ignore si manquant côté B
        $rB=$mapB[$pid];

        if (diffChanged($rA['description'], $rB['description'])) {
            $productDiffs[] = ['id'=>$pid,'field'=>'description','from'=>$rB['description'],'to'=>$rA['description']];
        }
        if (diffChanged($rA['description_short'], $rB['description_short'])) {
            $productDiffs[] = ['id'=>$pid,'field'=>'description_short','from'=>$rB['description_short'],'to'=>$rA['description_short']];
        }
    }
}

// --- DIFFS CATÉGORIES (filtrées par cA.date_upd >= :dateFrom) ---
$categoryDiffs = [];
$sqlA = "SELECT clA.id_category, clA.description
         FROM ps_category_lang clA
         INNER JOIN ps_category cA ON cA.id_category = clA.id_category AND cA.date_upd >= :dateFrom
         WHERE clA.id_lang = :idLang AND clA.id_shop = :idShop";
$stmtA=$dbA->prepare($sqlA);
$stmtA->execute([
                    ':dateFrom' => $startDate->format('Y-m-d H:i:s'),
                    ':idLang'   => ID_LANG,
                    ':idShop'   => ID_SHOP,
                ]);
$rowsA=$stmtA->fetchAll();

if ($rowsA) {
    $sqlB = "SELECT clB.id_category, clB.description
             FROM ps_category_lang clB
             WHERE clB.id_lang = :idLang AND clB.id_shop = :idShop";
    $stmtB=$dbB->prepare($sqlB);
    $stmtB->execute([':idLang'=>ID_LANG, ':idShop'=>ID_SHOP]);
    $mapB=[]; foreach($stmtB->fetchAll() as $rB){ $mapB[(int)$rB['id_category']]=$rB; }

    foreach($rowsA as $rA){
        $cid=(int)$rA['id_category'];
        if(!isset($mapB[$cid])) continue;
        $rB=$mapB[$cid];

        if (diffChanged($rA['description'], $rB['description'])) {
            $categoryDiffs[] = ['id'=>$cid,'field'=>'description','from'=>$rB['description'],'to'=>$rA['description']];
        }
    }
}

// --- RENDU HTML ---
header('Content-Type: text/html; charset=utf-8');

function renderTable($rows, $title, $idLabel, $limit){
    echo '<h3>'.h($title).' ('.count($rows).')</h3>';
    if (empty($rows)) { echo '<p>Aucune différence détectée.</p>'; return; }
    $shown = $limit>0 ? min($limit, count($rows)) : count($rows);
    if($limit>0 && $shown<count($rows)){
        echo '<p><i>Affichage limité à '.(int)$shown.' entrées sur '.count($rows).'. Ajuste <code>$previewLimit</code> au besoin.</i></p>';
    }
    echo '<table><tr><th>'.$idLabel.'</th><th>Champ</th><th>Valeur B (avant)</th><th>Valeur A (après)</th></tr>';
    for($i=0;$i<$shown;$i++){
        $d=$rows[$i];
        echo '<tr>';
        echo '<td class="mono">'.h($d['id']).'</td>';
        echo '<td><kbd>'.h($d['field']).'</kbd></td>';
        echo '<td>'.h(ellipsize($d['from'])).'</td>';
        echo '<td>'.h(ellipsize($d['to'])).'</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '<!doctype html><html><head><meta charset="utf-8"><title>TEST – Diff A → B (30 jours)</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Helvetica,Arial,sans-serif;line-height:1.4;margin:20px;}
code,pre{background:#f7f7f9;border:1px solid #eee;padding:2px 6px;border-radius:4px;}
.summary{display:flex;gap:20px;margin-bottom:10px;flex-wrap:wrap}
.card{border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin-bottom:14px;}
.small{color:#6b7280;font-size:12px}
table{border-collapse:collapse;width:100%;table-layout:fixed}
th,td{border:1px solid #e5e7eb;padding:8px;vertical-align:top}
th{background:#fafafa}
kbd{background:#111;color:#fff;border-radius:4px;padding:2px 6px;font-size:12px}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
</style></head><body>';

echo '<h1>Mode TEST — Comparaison A → B (30 derniers jours)</h1>';
echo '<div class="summary">';
echo '<div class="card"><b>Fenêtre</b><div>'.h($startDate->format('Y-m-d H:i:s')).' → '.h($now->format('Y-m-d H:i:s')).' (Europe/Paris)</div></div>';
echo '<div class="card"><b>Produits</b><div>'.count($productDiffs).' champs différents</div></div>';
echo '<div class="card"><b>Catégories</b><div>'.count($categoryDiffs).' champs différents</div></div>';
echo '</div>';
echo '<p><b>Aucun update ne sera effectué</b>. Script en lecture seule.</p>';

renderTable($productDiffs,  'Produits',   'ID Produit',   $previewLimit);
renderTable($categoryDiffs, 'Catégories', 'ID Catégorie', $previewLimit);

echo '</body></html>';
