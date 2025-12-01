<?php
/**
 * ckc_espacepro - view_clean_customers.php (v2)
 *
 * Rapport VISUEL allégé pour validation métier.
 *
 * Sections :
 * 1. KPIs globaux
 * 2. CAS SENSIBLES A VALIDER (détails complets)
 * 3. ECHANTILLON DE CAS NON SENSIBLES (20 exemples)
 * 4. RECAP GLOBAL (tableau synthétique, max 200 lignes)
 *
 * SÉCURITÉ :
 * - Lecture seule (aucune écriture DB)
 * - Accès verrouillé par token, optionnellement IP
 *
 * URL :
 *   /modules/ckc_espacepro/scripts/view_clean_customers.php?token=TON_SECRET
 */

// ---------------------------------------------------------------------
// CONFIG SÉCURITÉ
// ---------------------------------------------------------------------

const ACCESS_TOKEN = '123'; // change moi (UUID très long)

if (!isset($_GET['token']) || $_GET['token'] !== ACCESS_TOKEN) {
    http_response_code(403);
    die('Forbidden');
}

// Optionnel : restreindre l'IP
/*
$allowedIps = ['1.2.3.4']; // ton IP bureau
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps, true)) {
    http_response_code(403);
    die('Forbidden IP');
}
*/

// ---------------------------------------------------------------------
// BOOTSTRAP PRESTA
// ---------------------------------------------------------------------

require_once dirname(__FILE__, 4) . '/config/config.inc.php';
require_once dirname(__FILE__, 4) . '/init.php';

$db = Db::getInstance();

// ---------------------------------------------------------------------
// CONFIG MÉTIER
// ---------------------------------------------------------------------

// mêmes IDs groupe PRO qu'utilisés dans le script CLI de merge
const PRIORITY_GROUP_IDS = []; // ex: [3,4] pour les groupes B2B/pro

// combien max de lignes montrer dans le tableau récap global
const MAX_RECAP_ROWS = 200;

// combien de cas non sensibles on montre en exemple détaillé
const SAMPLE_NON_SENSITIVE = 20;

// ---------------------------------------------------------------------
// HELPERS COMMUNS
// ---------------------------------------------------------------------

function q($val) {
    return pSQL($val, true);
}

function getDuplicateEmails(Db $db): array
{
    $sql = 'SELECT email 
            FROM ' . _DB_PREFIX_ . 'customer 
            GROUP BY email 
            HAVING COUNT(*) > 1';

    return $db->executeS($sql) ?: [];
}

function getCustomersByEmail(Db $db, string $email): array
{
    $emailSafe = q($email);

    $customers = $db->executeS(
        'SELECT c.id_customer, c.is_guest, c.active, c.date_add, c.date_upd, 
                c.id_default_group, c.newsletter, c.email
         FROM ' . _DB_PREFIX_ . 'customer c
         WHERE c.email = "' . $emailSafe . '"'
    );

    if (!$customers) {
        return [];
    }

    $byId = [];
    foreach ($customers as $c) {
        $c['last_order_date'] = null;
        $c['last_cart_date'] = null;
        $c['groups'] = [];
        $byId[(int)$c['id_customer']] = $c;
    }

    $ids = implode(',', array_map('intval', array_keys($byId)));
    if ($ids === '') {
        return [];
    }

    // last order
    $orders = $db->executeS(
        'SELECT id_customer, MAX(date_add) AS last_order_date
         FROM ' . _DB_PREFIX_ . 'orders
         WHERE id_customer IN (' . $ids . ')
         GROUP BY id_customer'
    );
    foreach ($orders ?: [] as $o) {
        $cid = (int)$o['id_customer'];
        $byId[$cid]['last_order_date'] = $o['last_order_date'];
    }

    // last cart
    $carts = $db->executeS(
        'SELECT id_customer, MAX(date_upd) AS last_cart_date
         FROM ' . _DB_PREFIX_ . 'cart
         WHERE id_customer IN (' . $ids . ')
         GROUP BY id_customer'
    );
    foreach ($carts ?: [] as $ct) {
        $cid = (int)$ct['id_customer'];
        $byId[$cid]['last_cart_date'] = $ct['last_cart_date'];
    }

    // groups
    $groups = $db->executeS(
        'SELECT id_customer, id_group 
         FROM ' . _DB_PREFIX_ . 'customer_group
         WHERE id_customer IN (' . $ids . ')'
    );
    foreach ($groups ?: [] as $g) {
        $cid = (int)$g['id_customer'];
        $gid = (int)$g['id_group'];
        if (!isset($byId[$cid]['groups'])) {
            $byId[$cid]['groups'] = [];
        }
        $byId[$cid]['groups'][] = $gid;
    }

    return array_values($byId);
}

function computeCustomerScore(array $c): float
{
    $score = 0.0;

    // priorité groupe PRO
    $hasPriorityGroup = false;
    foreach ($c['groups'] as $gid) {
        if (in_array((int)$gid, PRIORITY_GROUP_IDS, true)) {
            $hasPriorityGroup = true;
            break;
        }
    }
    if ($hasPriorityGroup) {
        $score += 2000;
    }

    if (!empty($c['last_order_date'])) {
        $score += 1000;
        $score += (strtotime($c['last_order_date']) ?: 0) / 1000000;
    } elseif (!empty($c['last_cart_date'])) {
        $score += 500;
        $score += (strtotime($c['last_cart_date']) ?: 0) / 1000000;
    } else {
        $score += 100;
        $score += (strtotime($c['date_upd']) ?: 0) / 1000000;
    }

    if (!empty($c['is_guest'])) {
        $score -= 50;
    }

    $score += ((int)$c['id_customer']) / 1000000000;

    return $score;
}

function pickMasterCustomer(array $customers): array
{
    // Séparer invités / non invités
    $nonGuests = [];
    $guests    = [];

    foreach ($customers as $c) {
        if (!empty($c['is_guest'])) {
            $guests[] = $c;
        } else {
            $nonGuests[] = $c;
        }
    }

    // Priorité absolue aux non-invités
    $pool = !empty($nonGuests) ? $nonGuests : $guests;

    $best = null;
    $bestScore = -INF;

    foreach ($pool as $c) {
        $score = computeCustomerScore($c);
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $c;
        }
    }

    return $best;
}

// est-ce que ce groupe email est "sensible" ?
function isSensitiveCase(array $master, array $slaves): bool
{
    // 1. si le master est invité -> red flag
    if (!empty($master['is_guest'])) {
        return true;
    }

    // 2. si le master est inactif -> red flag
    if (empty($master['active'])) {
        return true;
    }

    // 3. si le master n'a pas de commande
    //    MAIS au moins un slave a une commande => conflit métier possible
    $masterHasOrder = !empty($master['last_order_date']);
    if (!$masterHasOrder) {
        foreach ($slaves as $s) {
            if (!empty($s['last_order_date'])) {
                return true;
            }
        }
    }

    // 4. égalité stricte de score entre le master et au moins un slave ?
    $masterScore = computeCustomerScore($master);
    foreach ($slaves as $s) {
        $slaveScore = computeCustomerScore($s);
        if (abs($slaveScore - $masterScore) < 0.0000001) {
            // match quasi parfait => humain doit trancher
            return true;
        }
    }

    return false;
}

// helper pour compter les éléments impactés côté slaves
function getImpactStats(Db $db, array $slaveIds): array
{
    if (!$slaveIds) {
        return ['orders'=>0,'carts'=>0,'addr'=>0];
    }
    $list = implode(',', array_map('intval', $slaveIds));
    $ordersCount = (int)$db->getValue(
        'SELECT COUNT(*) FROM '._DB_PREFIX_.'orders WHERE id_customer IN ('.$list.')'
    );
    $cartsCount = (int)$db->getValue(
        'SELECT COUNT(*) FROM '._DB_PREFIX_.'cart WHERE id_customer IN ('.$list.')'
    );
    $addrCount = (int)$db->getValue(
        'SELECT COUNT(*) FROM '._DB_PREFIX_.'address WHERE id_customer IN ('.$list.') AND deleted = 0'
    );

    return [
        'orders'=>$ordersCount,
        'carts'=>$cartsCount,
        'addr'=>$addrCount,
    ];
}

// rendu d'un bloc détaillé (master + slaves) réutilisable
function renderDetailBlock(array $emailBlock)
{
    $master = $emailBlock['master'];
    $slaves = $emailBlock['slaves'];
    $all    = $emailBlock['all'];

    $mid = (int)$master['id_customer'];

    $slaveIds = array_map(fn($c)=>(int)$c['id_customer'],$slaves);

    // badge helpers
    $badgeMaster = '<span style="display:inline-block;background:#10b981;color:#fff;font-size:.7rem;font-weight:600;padding:.3rem .5rem;border-radius:.5rem;">MASTER</span>';
    $badgeSlave  = '<span style="display:inline-block;background:#ef4444;color:#fff;font-size:.7rem;font-weight:600;padding:.3rem .5rem;border-radius:.5rem;">SLAVE</span>';
    $badgeGuest  = '<span style="display:inline-block;background:#f59e0b;color:#fff;font-size:.7rem;font-weight:600;padding:.3rem .5rem;border-radius:.5rem;">GUEST</span>';

    echo '<div class="email-block">';
    echo '<h2 style="margin-top:0;">'.htmlspecialchars($emailBlock['email']).'</h2>';

    echo '<div class="meta-line">';
    echo 'Compte maître pressenti : <strong>#'.$mid.'</strong> '.$badgeMaster;
    echo '</div>';

    echo '<div class="meta-line">';
    echo 'Comptes fusionnés (esclaves) : ';
    echo $slaveIds ? implode(', ', $slaveIds).' '.$badgeSlave : '—';
    echo '</div>';

    echo '<div class="meta-line">';
    echo 'Impact estimé : '.$emailBlock['impact']['orders'].' commandes, '.$emailBlock['impact']['carts'].' paniers, '.$emailBlock['impact']['addr'].' adresses à réattribuer vers #'.$mid.'.';
    echo '</div>';

    echo '<table>';
    echo '<thead><tr>
            <th>id_customer</th>
            <th>Type</th>
            <th>Actif</th>
            <th>Dernière commande</th>
            <th>Dernier panier</th>
            <th>date_upd</th>
            <th>Groups</th>
            <th>Newsletter</th>
            <th>Score</th>
            <th>Email futur si SLAVE</th>
        </tr></thead><tbody>';

    foreach ($all as $c) {
        $isMaster = ((int)$c['id_customer'] === $mid);
        $score = computeCustomerScore($c);

        $futureEmail = $isMaster
            ? $c['email']
            : ('clean2025_'.(int)$c['id_customer'].'_'.$c['email']);

        echo '<tr>';
        echo '<td>#'.(int)$c['id_customer'].'<br>';
        echo ($isMaster ? $badgeMaster : $badgeSlave);
        if (!empty($c['is_guest'])) {
            echo ' '.$badgeGuest;
        }
        echo '</td>';

        echo '<td>'.($c['is_guest'] ? 'Invité' : 'Client').'</td>';
        echo '<td>'.($c['active'] ? '✔' : '✘').'</td>';
        echo '<td>'.($c['last_order_date'] ? htmlspecialchars($c['last_order_date']) : '—').'</td>';
        echo '<td>'.($c['last_cart_date'] ? htmlspecialchars($c['last_cart_date']) : '—').'</td>';
        echo '<td>'.htmlspecialchars($c['date_upd']).'</td>';

        echo '<td>';
        if (!empty($c['groups'])) {
            echo implode(', ', array_map('intval',$c['groups']));
        } else {
            echo '—';
        }
        echo '</td>';

        echo '<td>'.($c['newsletter'] ? 'Oui' : 'Non').'</td>';
        echo '<td>'.number_format($score,6,'.','').'</td>';
        echo '<td><code style="font-size:.7rem;background:#f0f0f0;padding:.2rem .4rem;border-radius:4px;">'
             .htmlspecialchars($futureEmail)
             .'</code></td>';

        echo '</tr>';
    }

    echo '</tbody></table>';

    echo '<p class="meta-line">Règle de fusion : toutes les commandes / paniers / adresses / SAV / connexions et tables custom seront repointés sur <strong>#'.$mid.'</strong>, puis les autres comptes seront désactivés et renommés.</p>';

    echo '</div>';
}

// ---------------------------------------------------------------------
// CALCUL GLOBAL
// ---------------------------------------------------------------------

$dupes = getDuplicateEmails($db);

$totalEmails = count($dupes);
$totalSlaves = 0;
$totalOrdersToMove = 0;
$totalCartsToMove = 0;
$totalAddrToMove = 0;

$sensitiveBlocks = [];
$cleanBlocks = [];
$recapRows = [];

foreach ($dupes as $row) {
    $email = $row['email'];
    $customers = getCustomersByEmail($db, $email);
    if (count($customers) < 2) {
        continue;
    }

    $master = pickMasterCustomer($customers);

    $slaves = [];
    foreach ($customers as $c) {
        if ((int)$c['id_customer'] !== (int)$master['id_customer']) {
            $slaves[] = $c;
        }
    }

    $slaveIds = array_map(fn($c)=>(int)$c['id_customer'],$slaves);
    $impact = getImpactStats($db, $slaveIds);

    $totalSlaves += count($slaves);
    $totalOrdersToMove += $impact['orders'];
    $totalCartsToMove  += $impact['carts'];
    $totalAddrToMove   += $impact['addr'];

    $block = [
        'email'  => $email,
        'master' => $master,
        'slaves' => $slaves,
        'all'    => $customers,
        'impact' => $impact,
    ];

    if (isSensitiveCase($master, $slaves)) {
        $sensitiveBlocks[] = $block;
    } else {
        $cleanBlocks[] = $block;
    }

    // pour le tableau récap global
    $recapRows[] = [
        'email'         => $email,
        'master_id'     => (int)$master['id_customer'],
        'nb_slaves'     => count($slaves),
        'orders_move'   => $impact['orders'],
        'carts_move'    => $impact['carts'],
        'addr_move'     => $impact['addr'],
    ];
}

// on limite l'échantillon clean
$cleanBlocksSample = array_slice($cleanBlocks, 0, SAMPLE_NON_SENSITIVE);

// on limite le recap global
$recapRowsLimited = array_slice($recapRows, 0, MAX_RECAP_ROWS);

// ---------------------------------------------------------------------
// RENDER HTML
// ---------------------------------------------------------------------
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>CKC EspacePro - Rapport fusion clients (light)</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background:#f5f6fa;
            color:#1a1a1a;
            margin:0;
            padding:2rem;
        }
        h1,h2,h3 {
            font-weight:600;
            margin:0 0 .5rem;
            color:#111;
        }
        .header-grid{
            max-width:1200px;
            margin:0 auto 2rem;
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
            gap:1rem;
        }
        .summary-card{
            background:#fff;
            border-radius:12px;
            padding:1rem 1.25rem;
            border:1px solid #ececec;
            box-shadow:0 10px 24px rgba(0,0,0,.05);
        }
        .small-label{
            font-size:.7rem;
            color:#666;
            font-weight:500;
            text-transform:uppercase;
            letter-spacing:.03em;
            margin-bottom:.25rem;
        }
        .big-number{
            font-size:1.5rem;
            line-height:1.2;
            font-weight:600;
            color:#111;
            margin-bottom:.25rem;
        }
        .meta-line{
            font-size:.8rem;
            color:#555;
            margin-bottom:.4rem;
        }
        .section-block{
            max-width:1200px;
            margin:2rem auto;
        }
        .email-block{
            background:#fff;
            border-radius:12px;
            box-shadow:0 10px 24px rgba(0,0,0,.05);
            margin:1.5rem auto;
            padding:1rem 1.5rem 1.5rem;
            border:1px solid #ececec;
        }
        table {
            border-collapse:collapse;
            width:100%;
            font-size:.8rem;
            margin-top:1rem;
            border-radius:8px;
            border:1px solid #ddd;
            background:#fff;
            overflow:hidden;
        }
        th{
            background:#fafafa;
            text-align:left;
            font-weight:600;
            font-size:.75rem;
            color:#444;
            border-bottom:1px solid #ddd;
            padding:.5rem .75rem;
            white-space:nowrap;
        }
        td{
            border-bottom:1px solid #eee;
            padding:.5rem .75rem;
            vertical-align:top;
        }
        tr:last-child td{
            border-bottom:none;
        }
        code{
            font-size:.7rem;
            background:#f0f0f0;
            padding:.2rem .4rem;
            border-radius:4px;
        }
        .hint{
            font-size:.75rem;
            color:#777;
            font-style:italic;
            margin-top:.5rem;
        }
    </style>
</head>
<body>

<div class="header-grid">

    <div class="summary-card">
        <div class="small-label">Emails en doublon</div>
        <div class="big-number"><?php echo (int)$totalEmails; ?></div>
        <div class="meta-line">adresses email concernées (≥ 2 comptes)</div>
    </div>

    <div class="summary-card">
        <div class="small-label">Comptes à désactiver</div>
        <div class="big-number"><?php echo (int)$totalSlaves; ?></div>
        <div class="meta-line">seront renommés en "clean2025_*"</div>
    </div>

    <div class="summary-card">
        <div class="small-label">Impact global (slaves)</div>
        <div class="big-number">
            <?php echo (int)$totalOrdersToMove; ?> cmd /
            <?php echo (int)$totalCartsToMove; ?> paniers
        </div>
        <div class="meta-line">
            + <?php echo (int)$totalAddrToMove; ?> adresses à réattribuer
        </div>
    </div>

    <div class="summary-card">
        <div class="small-label">Groupes PRO prioritaires</div>
        <div class="big-number">
            <?php echo (empty(PRIORITY_GROUP_IDS) ? 'Aucun' : implode(', ', PRIORITY_GROUP_IDS)); ?>
        </div>
        <div class="meta-line">Ces groupes gagnent même si pas de commande</div>
    </div>

</div>

<div class="section-block">
    <h2>1. Cas sensibles à valider (décision humaine avant merge)</h2>
    <p class="meta-line">
        On affiche intégralement uniquement les cas où le choix du compte maître n'est pas "safe" :
        maître invité/inactif, conflit commande, ou égalité de priorité.
    </p>

    <?php
    if (!$sensitiveBlocks) {
        echo '<p class="meta-line">Aucun cas sensible détecté 🎉</p>';
    } else {
        foreach ($sensitiveBlocks as $block) {
            renderDetailBlock($block);
        }
    }
    ?>
</div>

<div class="section-block">
    <h2>2. Exemples propres (échantillon de <?php echo SAMPLE_NON_SENSITIVE; ?> cas non sensibles)</h2>
    <p class="meta-line">
        Ceci est juste un aperçu de ce que fera le script sur les cas "propres".
        Le reste (plusieurs milliers d\'emails) suivra exactement la même logique.
    </p>

    <?php
    if (!$cleanBlocksSample) {
        echo '<p class="meta-line">Pas d\'exemples non sensibles disponibles (tout est sensible ? 👀)</p>';
    } else {
        foreach ($cleanBlocksSample as $block) {
            renderDetailBlock($block);
        }
    }
    ?>
</div>

<div class="section-block">
    <h2>3. Récap global (tronqué à <?php echo MAX_RECAP_ROWS; ?> lignes)</h2>
    <p class="meta-line">
        Vue synthétique par email : qui devient maître, combien de comptes fusionnés,
        et combien d'objets (commandes/paniers/adresses) seront repointés.
    </p>

    <table>
        <thead>
        <tr>
            <th>Email</th>
            <th>id_master</th>
            <th>nb_slaves</th>
            <th>cmd transférées</th>
            <th>paniers transférés</th>
            <th>adresses transférées</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($recapRowsLimited as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['email']); ?></td>
                <td>#<?php echo (int)$r['master_id']; ?></td>
                <td><?php echo (int)$r['nb_slaves']; ?></td>
                <td><?php echo (int)$r['orders_move']; ?></td>
                <td><?php echo (int)$r['carts_move']; ?></td>
                <td><?php echo (int)$r['addr_move']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p class="hint">
        Le tableau complet contient <?php echo count($recapRows); ?> lignes (emails en doublon).
        Ici on limite à <?php echo MAX_RECAP_ROWS; ?> lignes pour ne pas exploser le navigateur.
        Pour audit complet offline, tu peux générer un export CSV serveur-side (ex: bouton ?export=csv)
        et le donner à la compta / Divalto.
    </p>
</div>

</body>
</html>
