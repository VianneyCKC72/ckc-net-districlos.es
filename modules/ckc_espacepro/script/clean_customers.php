<?php

ini_set('memory_limit', '512M');
ini_set('max_execution_time', '0');
set_time_limit(0);
ignore_user_abort(true);

/**
 * ckc_espacepro - run_clean_customers.php
 *
 * SÉCURITÉ :
 * - Accès par TOKEN obligatoire (?token=XXX)
 * - Mode par défaut = DRY RUN (ROLLBACK)
 * - Pour écrire en base : &confirm=yes
 *
 * URL DRY RUN :
 *   /modules/ckc_espacepro/scripts/run_clean_customers.php?token=TON_SECRET
 *
 * URL COMMIT :
 *   /modules/ckc_espacepro/scripts/run_clean_customers.php?token=TON_SECRET&confirm=yes
 *
 * RÈGLES MÉTIER :
 * - On regroupe les clients par e-mail
 * - On choisit un "compte maître" :
 *      - un invité (is_guest=1) ne peut JAMAIS être maître s'il existe un non-invité
 *      - sinon : priorité à celui qui a la commande la plus récente
 *      - sinon : panier le plus récent
 *      - sinon : date_upd la plus récente
 *      - bonus si groupe PRO prioritaire
 * - On rattache vers le maître :
 *      - commandes, paniers, adresses, SAV (customer_thread), connections (si table),
 *        groupes, ET les devis (ps_opartdevis)
 * - On complète le maître avec les infos B2B des slaves (tva, rib, adresse, dic_tiers_*, etc.)
 *      RÈGLE D'OR : si le master n’a pas la donnée, on copie celle du slave, sinon on NE TOUCHE PAS
 * - Newsletter : si un slave est newsletter=1 -> master passe newsletter=1
 * - Les slaves sont anonymisés + désactivés :
 *      email = clean2025_{id_slave}_{oldemail}, active = 0
 *
 * TRANSACTION :
 * - 1 transaction par adresse email
 *   - COMMIT si confirm=yes
 *   - sinon ROLLBACK (dry run)
 *
 * ⚠ Lancer COMMIT seulement après backup BDD.
 */

/* ---------------------------------------------------------------------
   CONFIG SÉCURITÉ
   --------------------------------------------------------------------- */

const ACCESS_TOKEN = '123'; // CHANGE-MOI EN PROD

$authorized = (isset($_GET['token']) && $_GET['token'] === ACCESS_TOKEN);
if (!$authorized) {
    http_response_code(403);
    die('Forbidden');
}

// Whitelist IP optionnelle
/*
$allowedIps = ['1.2.3.4'];
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps, true)) {
   http_response_code(403);
   die('Forbidden IP');
}
*/

/* ---------------------------------------------------------------------
   MODE EXÉCUTION
   --------------------------------------------------------------------- */

$DO_COMMIT = (isset($_GET['confirm']) && $_GET['confirm'] === 'yes');

/* ---------------------------------------------------------------------
   BOOTSTRAP PRESTASHOP
   --------------------------------------------------------------------- */

require_once dirname(__FILE__, 4) . '/config/config.inc.php';
require_once dirname(__FILE__, 4) . '/init.php';

$db = Db::getInstance();

/* ---------------------------------------------------------------------
   CONFIG MÉTIER
   --------------------------------------------------------------------- */

// Groupes PRO prioritaires (bonus score). Laisse vide si pas de notion PRO.
const PRIORITY_GROUP_IDS = []; // ex: [3,4]

// Tables custom à réassigner vers le master
// -> ici on ajoute ps_opartdevis avec sa clé id_customer
$EXTRA_CUSTOMER_TABLES = [
    _DB_PREFIX_ . 'opartdevis' => 'id_customer',
];

// Champs business/ERP à compléter sur le master si vide
$BUSINESS_FIELDS = [
    'company',
    'siret',
    'ape',
    'tva',
    'naf',
    'rib',
    'firstname',
    'lastname',
    'tel',
    'adresse',
    'adresse2',
    'cp',
    'ville',
    'pays',
    'fileUpload',
    'website',
    'dic_tiers',
    'dic_tiers_contact',
    'dic_tiers_passwd',
    'dic_tiers_franco',
    'dic_tiers_port',
    'dic_tiers_frais',
    'dic_tiers_remise',
    'dic_tiers_encours',
    'dic_tiers_plafond',
    'dic_tiers_transporteur',
    'dic_tiers_passwd_magasin',
    'dic_tiers_paiement_comptant',
    'note',
    'outstanding_allow_amount',
    'max_payment_days',
];

/* ---------------------------------------------------------------------
   HELPERS GÉNÉRAUX
   --------------------------------------------------------------------- */

// safe quote
function q($val) { return pSQL($val, true); }

// escape backticks

// log helper
function log_line(&$logArr, $msg) {
    $line = '['.date('Y-m-d H:i:s').'] '.$msg;
    $logArr[] = $line;
}

/**
 * Vérifie si une table existe sans provoquer de "LIMIT 1" automatique.
 * On utilise executeS() au lieu de getValue() pour éviter l'ajout de LIMIT.
 */
function tableExists(Db $db, $tableName): bool {
    $rows = $db->executeS(
        'SHOW TABLES LIKE "'.pSQL($tableName).'"'
    );
    return !empty($rows);
}

/**
 * Vérifie si une colonne existe dans une table.
 * Même principe : executeS() pour éviter LIMIT 1 auto.
 */
function columnExists(Db $db, $tableName, $colName): bool {
    $rows = $db->executeS(
        'SHOW COLUMNS FROM `'.pSQL($tableName).'` LIKE "'.pSQL($colName).'"'
    );
    return !empty($rows);
}

/* ---------------------------------------------------------------------
   RÉCUP LISTE EMAILS DOUBLONS
   --------------------------------------------------------------------- */

function getDuplicateEmails(Db $db): array
{
    $sql = 'SELECT email 
            FROM ' . _DB_PREFIX_ . 'customer 
            GROUP BY email 
            HAVING COUNT(*) > 1';

    return $db->executeS($sql) ?: [];
}

/* ---------------------------------------------------------------------
   RÉCUP CLIENTS PAR EMAIL + ENRICH
   --------------------------------------------------------------------- */

function getCustomersByEmail(Db $db, string $email): array
{
    $emailSafe = q($email);

    $customers = $db->executeS(
        'SELECT c.id_customer, c.is_guest, c.active, c.date_add, c.date_upd,
                c.id_default_group, c.newsletter, c.email,
                c.company, c.siret, c.ape, c.firstname, c.lastname,
                c.tva, c.naf, c.rib, c.tel,
                c.adresse, c.adresse2, c.cp, c.ville, c.pays,
                c.fileUpload, c.website,
                c.dic_tiers, c.dic_tiers_contact, c.dic_tiers_passwd,
                c.dic_tiers_franco, c.dic_tiers_port, c.dic_tiers_frais,
                c.dic_tiers_remise, c.dic_tiers_encours, c.dic_tiers_plafond,
                c.dic_tiers_transporteur, c.dic_tiers_passwd_magasin,
                c.dic_tiers_paiement_comptant,
                c.note, c.outstanding_allow_amount, c.max_payment_days
         FROM ' . _DB_PREFIX_ . 'customer c
         WHERE c.email = "' . $emailSafe . '"'
    );

    if (!$customers) {
        return [];
    }

    $byId = [];
    foreach ($customers as $c) {
        $c['last_order_date'] = null;
        $c['last_cart_date']  = null;
        $c['groups']          = [];
        $byId[(int)$c['id_customer']] = $c;
    }

    $ids = implode(',', array_map('intval', array_keys($byId)));
    if ($ids === '') {
        return [];
    }

    // Dernière commande par compte
    if (tableExists($db, _DB_PREFIX_.'orders') && columnExists($db, _DB_PREFIX_.'orders','id_customer')) {
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
    }

    // Dernier panier par compte
    if (tableExists($db, _DB_PREFIX_.'cart') && columnExists($db, _DB_PREFIX_.'cart','id_customer')) {
        // suppose qu'on a cart.date_upd ; sinon adapter en date_add
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
    }

    // Groupes client
    if (tableExists($db, _DB_PREFIX_.'customer_group')
        && columnExists($db, _DB_PREFIX_.'customer_group','id_customer')
        && columnExists($db, _DB_PREFIX_.'customer_group','id_group')
    ) {
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
    }

    return array_values($byId);
}

/* ---------------------------------------------------------------------
   MASTER SCORE / PICK MASTER
   --------------------------------------------------------------------- */

function computeCustomerScore(array $c): float
{
    $score = 0.0;

    // bonus groupe PRO
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

    // commandes > panier > date_upd
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

    // malus invité
    if (!empty($c['is_guest'])) {
        $score -= 50;
    }

    // tie-breaker doux
    $score += ((int)$c['id_customer']) / 1000000000;

    return $score;
}

/**
 * Règle métier :
 * - Un invité ne peut pas être maître si un non-invité existe
 */
function pickMasterCustomer(array $customers): array
{
    $nonGuests = [];
    $guests    = [];

    foreach ($customers as $c) {
        if (!empty($c['is_guest'])) {
            $guests[] = $c;
        } else {
            $nonGuests[] = $c;
        }
    }

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

/* ---------------------------------------------------------------------
   MERGE INFOS BUSINESS DU SLAVE -> MASTER SI MASTER VIDE
   --------------------------------------------------------------------- */

function isEmptyBusinessVal($v): bool {
    // ce qu'on considère comme "vide"
    if ($v === null) return true;
    if ($v === '') return true;
    if ($v === '0') return true; // cas "0" placeholder
    return false;
}

function copyBusinessFieldsIfEmpty(Db $db, $mid, $masterRow, $sid, $slaveRow, $BUSINESS_FIELDS, array &$logArr)
{
    if (!$BUSINESS_FIELDS) {
        return;
    }

    $customerTable = _DB_PREFIX_ . 'customer';

    if (!tableExists($db, $customerTable)) {
        log_line($logArr, "SKIP copy business (table customer absente ?)");
        return;
    }

    $updates = [];
    foreach ($BUSINESS_FIELDS as $field) {
        // on le fait seulement si le champ existe dans master+slave
        if (!array_key_exists($field, $masterRow) || !array_key_exists($field, $slaveRow)) {
            continue;
        }

        $mVal = $masterRow[$field];
        $sVal = $slaveRow[$field];

        if (isEmptyBusinessVal($mVal) && !isEmptyBusinessVal($sVal)) {
            $updates[$field] = $sVal;
        }
    }

    if (!$updates) {
        return;
    }

    $setParts = [];
    foreach ($updates as $col => $val) {
        $setParts[] = '`'.bqSQL($col).'` = "'.pSQL($val, true).'"';
    }

    $sql = 'UPDATE `'.$customerTable.'`
            SET '.implode(", ", $setParts).'
            WHERE id_customer = '.(int)$mid.'
            LIMIT 1';

    $db->execute($sql);

    log_line($logArr, "master $mid complété avec champs business depuis $sid : ".implode(', ', array_keys($updates)));
}

/* ---------------------------------------------------------------------
   MERGE SLAVE -> MASTER
   --------------------------------------------------------------------- */

function mergeCustomerIntoMaster(
    Db $db,
    array $master,
    array $slave,
    array $EXTRA_CUSTOMER_TABLES,
    array $BUSINESS_FIELDS,
    array &$logArr
){
    $mid = (int)$master['id_customer'];
    $sid = (int)$slave['id_customer'];
    if ($mid === $sid) {
        return;
    }

    $prefix = _DB_PREFIX_;

    // 1. Commandes
    if (tableExists($db, $prefix.'orders') && columnExists($db, $prefix.'orders','id_customer')) {
        $db->execute(
            'UPDATE ' . $prefix . 'orders
             SET id_customer = ' . (int)$mid . '
             WHERE id_customer = ' . (int)$sid
        );
        log_line($logArr, "orders: $sid -> $mid");
    } else {
        log_line($logArr, "SKIP orders (table/col manquante)");
    }

    // 2. Paniers
    if (tableExists($db, $prefix.'cart') && columnExists($db, $prefix.'cart','id_customer')) {
        $db->execute(
            'UPDATE ' . $prefix . 'cart
             SET id_customer = ' . (int)$mid . '
             WHERE id_customer = ' . (int)$sid
        );
        log_line($logArr, "cart: $sid -> $mid");
    } else {
        log_line($logArr, "SKIP cart (table/col manquante)");
    }

    // 3. Adresses
    if (tableExists($db, $prefix.'address') && columnExists($db, $prefix.'address','id_customer')) {
        $db->execute(
            'UPDATE ' . $prefix . 'address
             SET id_customer = ' . (int)$mid . '
             WHERE id_customer = ' . (int)$sid
        );
        log_line($logArr, "address: $sid -> $mid");
    } else {
        log_line($logArr, "SKIP address (table/col manquante)");
    }

    // 4. SAV / threads client
    if (tableExists($db, $prefix.'customer_thread') && columnExists($db, $prefix.'customer_thread','id_customer')) {
        $db->execute(
            'UPDATE ' . $prefix . 'customer_thread
             SET id_customer = ' . (int)$mid . '
             WHERE id_customer = ' . (int)$sid
        );
        log_line($logArr, "customer_thread: $sid -> $mid");
    } else {
        log_line($logArr, "SKIP customer_thread (table/col manquante)");
    }

    // 5. Connexions
    if (tableExists($db, $prefix.'connections') && columnExists($db, $prefix.'connections','id_customer')) {
        $db->execute(
            'UPDATE ' . $prefix . 'connections
             SET id_customer = ' . (int)$mid . '
             WHERE id_customer = ' . (int)$sid
        );
        log_line($logArr, "connections: $sid -> $mid");
    } else {
        log_line($logArr, "SKIP connections (table/col manquante)");
    }

    // 6. Tables custom (ex: devis ps_opartdevis)
    foreach ($EXTRA_CUSTOMER_TABLES as $table => $col) {
        if (tableExists($db, $table) && columnExists($db, $table, $col)) {
            $db->execute(
                'UPDATE ' . pSQL($table) . '
                 SET ' . bqSQL($col) . ' = ' . (int)$mid . '
                 WHERE ' . bqSQL($col) . ' = ' . (int)$sid
            );
            log_line($logArr, "$table.$col: $sid -> $mid");
        } else {
            log_line($logArr, "SKIP $table (table/col manquante)");
        }
    }

    // 7. Fusion des groupes (INSERT IGNORE pour éviter les doublons)
    if (tableExists($db, $prefix . 'customer_group')
        && columnExists($db, $prefix . 'customer_group', 'id_customer')
        && columnExists($db, $prefix . 'customer_group', 'id_group')
    ) {
        $slaveGroups = $db->executeS(
            'SELECT id_group
             FROM ' . $prefix . 'customer_group
             WHERE id_customer = ' . (int)$sid
        );

        foreach ($slaveGroups ?: [] as $rowG) {
            $gid = (int)$rowG['id_group'];

            $db->execute(
                'INSERT IGNORE INTO ' . $prefix . 'customer_group (id_customer, id_group)
                 VALUES (' . (int)$mid . ', ' . (int)$gid . ')'
            );

            log_line($logArr, "group $gid copié vers master $mid (depuis $sid)");
        }
    } else {
        log_line($logArr, "SKIP fusion groupes (table/col manquante)");
    }

    // 8. Compléter les infos business sur le master
    copyBusinessFieldsIfEmpty($db, $mid, $master, $sid, $slave, $BUSINESS_FIELDS, $logArr);

    // 9. Newsletter : si le slave est inscrit et que le master ne l'est pas
    if (!empty($slave['newsletter'])) {
        if (empty($master['newsletter'])) {
            if (tableExists($db, $prefix.'customer') && columnExists($db, $prefix.'customer','newsletter')) {
                $db->execute(
                    'UPDATE ' . $prefix . 'customer
                     SET newsletter = 1
                     WHERE id_customer = ' . (int)$mid . ' LIMIT 1'
                );
                log_line($logArr, "master $mid newsletter=1 (hérité de $sid)");
            } else {
                log_line($logArr, "SKIP newsletter (colonne manquante)");
            }
        }
    }

    // 10. Anonymiser & désactiver le slave
    if (tableExists($db, $prefix.'customer')
        && columnExists($db, $prefix.'customer','email')
        && columnExists($db, $prefix.'customer','active')
    ) {
        $newEmail = 'clean2025_'.$sid.'_'.$slave['email'];

        $db->execute(
            'UPDATE ' . $prefix . 'customer
             SET email = "' . q($newEmail) . '",
                 active = 0
             WHERE id_customer = ' . (int)$sid . ' LIMIT 1'
        );
        log_line($logArr, "slave $sid anonymisé ($newEmail) + désactivé");
    } else {
        log_line($logArr, "SKIP anonymisation slave $sid (table/col manquante)");
    }
}

/* ---------------------------------------------------------------------
   TRAITE UN EMAIL
   --------------------------------------------------------------------- */

function processEmailGroup(
    Db $db,
    string $email,
    array $EXTRA_CUSTOMER_TABLES,
    array $BUSINESS_FIELDS,
    bool $DO_COMMIT,
    array &$globalRows,
    array &$logArr
) {
    $customers = getCustomersByEmail($db, $email);
    if (count($customers) < 2) {
        log_line($logArr, "SKIP $email : moins de 2 comptes (race condition)");
        return;
    }

    $master = pickMasterCustomer($customers);
    $mid = (int)$master['id_customer'];

    // Slaves
    $slaves = [];
    foreach ($customers as $c) {
        if ((int)$c['id_customer'] !== $mid) {
            $slaves[] = $c;
        }
    }

    // Stats (combien on va potentiellement déplacer)
    $slaveIds = array_map(fn($c)=>(int)$c['id_customer'], $slaves);
    $list = $slaveIds ? implode(',', $slaveIds) : '0';

    $ordersCount = 0;
    if (tableExists($db, _DB_PREFIX_.'orders') && columnExists($db, _DB_PREFIX_.'orders','id_customer')) {
        $ordersCount = (int)$db->getValue(
            'SELECT COUNT(*) FROM '._DB_PREFIX_.'orders WHERE id_customer IN ('.$list.')'
        );
    }

    $cartsCount = 0;
    if (tableExists($db, _DB_PREFIX_.'cart') && columnExists($db, _DB_PREFIX_.'cart','id_customer')) {
        $cartsCount = (int)$db->getValue(
            'SELECT COUNT(*) FROM '._DB_PREFIX_.'cart WHERE id_customer IN ('.$list.')'
        );
    }

    $addrCount = 0;
    if (tableExists($db, _DB_PREFIX_.'address') && columnExists($db, _DB_PREFIX_.'address','id_customer')) {
        $deletedFilter = columnExists($db, _DB_PREFIX_.'address', 'deleted')
            ? ' AND deleted = 0'
            : '';
        $addrCount = (int)$db->getValue(
            'SELECT COUNT(*) FROM '._DB_PREFIX_.'address WHERE id_customer IN ('.$list.')'.$deletedFilter
        );
    }

    $quotesCount = 0;
    if (tableExists($db, _DB_PREFIX_.'opartdevis') && columnExists($db, _DB_PREFIX_.'opartdevis','id_customer')) {
        $quotesCount = (int)$db->getValue(
            'SELECT COUNT(*) FROM '._DB_PREFIX_.'opartdevis WHERE id_customer IN ('.$list.')'
        );
    }

    $globalRows[] = [
        'email'       => $email,
        'master_id'   => $mid,
        'slaves_ids'  => $slaveIds,
        'orders_move' => $ordersCount,
        'carts_move'  => $cartsCount,
        'addr_move'   => $addrCount,
        'quotes_move' => $quotesCount,
    ];

    if (!$slaves) {
        log_line($logArr, "SKIP $email : rien à fusionner (déjà unique après master=$mid)");
        return;
    }

    log_line($logArr, "----");
    log_line($logArr, "Email $email");
    log_line($logArr, "Master = $mid ; Slaves = ".implode(',', $slaveIds));

    // Transaction par email
    $db->execute('START TRANSACTION');

    try {
        foreach ($slaves as $slave) {
            mergeCustomerIntoMaster($db, $master, $slave, $EXTRA_CUSTOMER_TABLES, $BUSINESS_FIELDS, $logArr);
        }

        if ($DO_COMMIT) {
            $db->execute('COMMIT');
            log_line($logArr, "COMMIT pour $email");
        } else {
            $db->execute('ROLLBACK');
            log_line($logArr, "ROLLBACK (DRY RUN) pour $email");
        }

    } catch (Exception $e) {
        $db->execute('ROLLBACK');
        log_line($logArr, "ERREUR pour $email -> ROLLBACK : ".$e->getMessage());
    }
}

/* ---------------------------------------------------------------------
   MAIN
   --------------------------------------------------------------------- */

$logArr = [];
$globalRows = [];

log_line($logArr, "======== LANCEMENT clean clients ========");
log_line($logArr, "Mode: ".($DO_COMMIT ? "COMMIT (écriture BDD)" : "DRY RUN (aucune écriture)"));

$dupes = getDuplicateEmails($db);
$totalEmails = count($dupes);
log_line($logArr, "Emails en doublon détectés : $totalEmails");

foreach ($dupes as $row) {
    $email = $row['email'];
    processEmailGroup($db, $email, $EXTRA_CUSTOMER_TABLES, $BUSINESS_FIELDS, $DO_COMMIT, $globalRows, $logArr);
}

log_line($logArr, "======== FIN ========");

// Stats globales
$totalSlaves        = 0;
$totalOrdersToMove  = 0;
$totalCartsToMove   = 0;
$totalAddrToMove    = 0;
$totalQuotesToMove  = 0;

foreach ($globalRows as $r) {
    $totalSlaves        += count($r['slaves_ids']);
    $totalOrdersToMove  += $r['orders_move'];
    $totalCartsToMove   += $r['carts_move'];
    $totalAddrToMove    += $r['addr_move'];
    $totalQuotesToMove  += $r['quotes_move'];
}

/* ---------------------------------------------------------------------
   RENDU HTML
   --------------------------------------------------------------------- */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>CKC EspacePro - EXEC fusion clients (<?php echo $DO_COMMIT ? 'LIVE' : 'DRY RUN'; ?>)</title>
    <style>
        body {
            font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
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
        .grid{
            max-width:1200px;
            margin:0 auto 2rem;
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
            gap:1rem;
        }
        .card{
            background:#fff;
            border-radius:12px;
            padding:1rem 1.25rem;
            border:1px solid #ececec;
            box-shadow:0 10px 24px rgba(0,0,0,.05);
        }
        .label{
            font-size:.7rem;
            color:#666;
            font-weight:500;
            text-transform:uppercase;
            letter-spacing:.03em;
            margin-bottom:.25rem;
        }
        .big{
            font-size:1.4rem;
            line-height:1.2;
            font-weight:600;
            color:#111;
            margin-bottom:.25rem;
            word-break: break-word;
        }
        .meta{
            font-size:.8rem;
            color:#555;
            margin-bottom:.4rem;
        }

        table {
            border-collapse:collapse;
            width:100%;
            font-size:.8rem;
            margin:2rem auto;
            max-width:1200px;
            border-radius:8px;
            border:1px solid #ddd;
            background:#fff;
            overflow:hidden;
            box-shadow:0 10px 24px rgba(0,0,0,.05);
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
        code, textarea{
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size:.75rem;
            line-height:1.4;
        }
        .logbox{
            max-width:1200px;
            margin:2rem auto;
        }
        textarea{
            width:100%;
            min-height:300px;
            background:#0a0a0a;
            color:#e5e5e5;
            border-radius:8px;
            border:1px solid #444;
            padding:1rem;
            box-shadow:0 10px 24px rgba(0,0,0,.5);
            white-space:pre;
        }
        .warn{
            color:#b91c1c;
            font-weight:600;
            font-size:.8rem;
            background:#fee2e2;
            border:1px solid #fecaca;
            padding:.5rem .75rem;
            border-radius:.5rem;
            max-width:1200px;
            margin:0 auto 1rem;
        }
    </style>
</head>
<body>

<div class="warn">
    Mode actuel :
    <strong><?php echo $DO_COMMIT ? 'COMMIT (ÉCRITURE EN BASE)' : 'DRY RUN (PAS D\'ÉCRITURE, ROLLBACK)'; ?></strong><br>
    <?php if ($DO_COMMIT): ?>
        Les modifications ont été appliquées compte par compte, dans des transactions individuelles.
    <?php else: ?>
        Aucune modification réelle : tout a été annulé par ROLLBACK. Pour appliquer vraiment,
        relance avec <code>&confirm=yes</code>.
    <?php endif; ?>
</div>

<div class="grid">
    <div class="card">
        <div class="label">Emails en doublon traités</div>
        <div class="big"><?php echo (int)$totalEmails; ?></div>
        <div class="meta">Adresses email contenant ≥ 2 comptes</div>
    </div>

    <div class="card">
        <div class="label">Comptes désactivés potentiels</div>
        <div class="big"><?php echo (int)$totalSlaves; ?></div>
        <div class="meta">Seront renommés en clean2025_* et mis inactifs</div>
    </div>

    <div class="card">
        <div class="label">Objets rattachés aux maîtres</div>
        <div class="big">
            <?php echo (int)$totalOrdersToMove; ?> cmd /
            <?php echo (int)$totalCartsToMove; ?> paniers /
            <?php echo (int)$totalQuotesToMove; ?> devis
        </div>
        <div class="meta">+ <?php echo (int)$totalAddrToMove; ?> adresses déplacées</div>
    </div>

    <div class="card">
        <div class="label">Règle invité</div>
        <div class="big">Invité jamais maître*</div>
        <div class="meta">
            *Sauf si tous les comptes du même email sont invités.
        </div>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>Email</th>
        <th>id_master choisi</th>
        <th>ids_slaves fusionnés</th>
        <th>Commandes transférées</th>
        <th>Paniers transférés</th>
        <th>Adresses transférées</th>
        <th>Devis transférés</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($globalRows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['email']); ?></td>
            <td>#<?php echo (int)$r['master_id']; ?></td>
            <td>
                <?php
                if (!empty($r['slaves_ids'])) {
                    echo implode(', ', array_map('intval',$r['slaves_ids']));
                } else {
                    echo '—';
                }
                ?>
            </td>
            <td><?php echo (int)$r['orders_move']; ?></td>
            <td><?php echo (int)$r['carts_move']; ?></td>
            <td><?php echo (int)$r['addr_move']; ?></td>
            <td><?php echo (int)$r['quotes_move']; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="logbox">
    <h2>Log d'exécution (copier/coller pour envoi client / ERP)</h2>
    <textarea readonly><?php echo htmlspecialchars(implode("\n",$logArr)); ?></textarea>
</div>

</body>
</html>
