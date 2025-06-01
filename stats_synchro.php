<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require(__DIR__.'/config/config.inc.php');
require_once(__DIR__.'/init.php');

$startDate = new DateTime('2025-05-19');
$endDate = new DateTime();
$endDate->setTime(23, 59, 59);

// Connexion à cloture-discount.fr (site source)
$remoteDb = new PDO(
    'mysql:host=87.98.141.171;dbname=districlosnew;charset=utf8',
    'districlosnew',
    '11Atb2I20TJ6joDc',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// === COMMANDES ===
// Récupération des commandes distantes
$sqlRemoteOrders = '
    SELECT id_order, DATE(date_add) as date_created
    FROM ps_orders
    WHERE date_add BETWEEN :start AND :end
';
$stmt = $remoteDb->prepare($sqlRemoteOrders);
$stmt->execute([
    ':start' => $startDate->format('Y-m-d 00:00:00'),
    ':end' => $endDate->format('Y-m-d 23:59:59'),
]);
$remoteOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des commandes locales
$sqlLocalOrders = '
    SELECT id_order, DATE(date_add) as date_created
    FROM '._DB_PREFIX_.'orders
    WHERE date_add BETWEEN "'.$startDate->format('Y-m-d').' 00:00:00"
                      AND "'.$endDate->format('Y-m-d').' 23:59:59"
';
$localOrders = Db::getInstance()->executeS($sqlLocalOrders);

// Initialisation tableau commandes
$orderStats = [];
$period = new DatePeriod($startDate, new DateInterval('P1D'), (clone $endDate)->modify('+1 day'));
foreach ($period as $date) {
    $key = $date->format('Y-m-d');
    $orderStats[$key] = [
        'local' => [],
        'remote' => [],
        'missing_from_local' => [],
        'missing_from_remote' => [],
    ];
}

foreach ($localOrders as $o) {
    $orderStats[$o['date_created']]['local'][] = (int)$o['id_order'];
}
foreach ($remoteOrders as $o) {
    $orderStats[$o['date_created']]['remote'][] = (int)$o['id_order'];
}

foreach ($orderStats as $date => &$row) {
    $row['missing_from_local'] = array_diff($row['remote'], $row['local']);
    $row['missing_from_remote'] = array_diff($row['local'], $row['remote']);
}

foreach ($orderStats as $date => &$row) {
    $row['missing_from_local'] = array_diff($row['remote'], $row['local']);
    $row['missing_from_remote'] = array_diff($row['local'], $row['remote']);

    // Exemple de commande manquante
    if (!empty($row['missing_from_local'])) {
        $exampleId = reset($row['missing_from_local']);
        $stmt = $remoteDb->prepare('
            SELECT o.id_order, o.total_paid_tax_incl, o.date_add, o.id_customer,
                   c.firstname, c.lastname
            FROM ps_orders o
            LEFT JOIN ps_customer c ON c.id_customer = o.id_customer
            WHERE o.id_order = ?
        ');
        $stmt->execute([$exampleId]);
        $example = $stmt->fetch(PDO::FETCH_ASSOC);

        // Récupère les produits
        $products = $remoteDb->prepare('
            SELECT product_name, product_quantity
            FROM ps_order_detail
            WHERE id_order = ?
        ');
        $products->execute([$exampleId]);
        $prodList = [];
        foreach ($products->fetchAll() as $p) {
            $prodList[] = $p['product_name'].' x'.$p['product_quantity'];
        }

        $row['example_missing_from_local'] = sprintf(
            "Commande #%d du %s à %s – Client : %s %s – Total TTC : %.2f € – Produits : %s",
            $example['id_order'],
            date('d/m/Y', strtotime($example['date_add'])),
            date('H:i', strtotime($example['date_add'])),
            $example['firstname'],
            $example['lastname'],
            $example['total_paid_tax_incl'],
            implode(', ', $prodList)
        );
    }
}


// === CLIENTS ===
// Récupération des clients distants
$sqlRemoteCustomers = '
    SELECT id_customer, DATE(date_add) as date_created
    FROM ps_customer
    WHERE date_add BETWEEN :start AND :end
';
$stmt = $remoteDb->prepare($sqlRemoteCustomers);
$stmt->execute([
    ':start' => $startDate->format('Y-m-d 00:00:00'),
    ':end' => $endDate->format('Y-m-d 23:59:59'),
]);
$remoteCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des clients locaux
$sqlLocalCustomers = '
    SELECT id_customer, DATE(date_add) as date_created
    FROM '._DB_PREFIX_.'customer
    WHERE date_add BETWEEN "'.$startDate->format('Y-m-d').' 00:00:00"
                      AND "'.$endDate->format('Y-m-d').' 23:59:59"
';
$localCustomers = Db::getInstance()->executeS($sqlLocalCustomers);

// Initialisation tableau clients
$clientStats = [];
foreach ($period as $date) {
    $key = $date->format('Y-m-d');
    $clientStats[$key] = [
        'local' => [],
        'remote' => [],
        'missing_from_local' => [],
        'missing_from_remote' => [],
    ];
}

foreach ($localCustomers as $c) {
    $clientStats[$c['date_created']]['local'][] = (int)$c['id_customer'];
}
foreach ($remoteCustomers as $c) {
    $clientStats[$c['date_created']]['remote'][] = (int)$c['id_customer'];
}

foreach ($clientStats as $date => &$row) {
    $row['missing_from_local'] = array_diff($row['remote'], $row['local']);
    $row['missing_from_remote'] = array_diff($row['local'], $row['remote']);

    // Exemple de client manquant (depuis cloture-discount.fr)
    if (!empty($row['missing_from_local'])) {
        $exampleId = reset($row['missing_from_local']);
        $stmt = $remoteDb->prepare('
            SELECT id_customer, firstname, lastname, email, date_add
            FROM ps_customer
            WHERE id_customer = ?
        ');
        $stmt->execute([$exampleId]);
        $example = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($example) {
            $row['example_missing_from_local'] = sprintf(
                "Client #%d – %s %s – %s – Créé le %s à %s",
                $example['id_customer'],
                $example['firstname'],
                $example['lastname'],
                $example['email'],
                date('d/m/Y', strtotime($example['date_add'])),
                date('H:i', strtotime($example['date_add']))
            );
        }
    }
}


// === PANIERS ===
// Récupération des paniers distants
$sqlRemoteCarts = '
    SELECT id_cart, DATE(date_add) as date_created
    FROM ps_cart
    WHERE date_add BETWEEN :start AND :end
';
$stmt = $remoteDb->prepare($sqlRemoteCarts);
$stmt->execute([
    ':start' => $startDate->format('Y-m-d 00:00:00'),
    ':end' => $endDate->format('Y-m-d 23:59:59'),
]);
$remoteCarts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des paniers locaux
$sqlLocalCarts = '
    SELECT id_cart, DATE(date_add) as date_created
    FROM '._DB_PREFIX_.'cart
    WHERE date_add BETWEEN "'.$startDate->format('Y-m-d').' 00:00:00"
                      AND "'.$endDate->format('Y-m-d').' 23:59:59"
';
$localCarts = Db::getInstance()->executeS($sqlLocalCarts);

// Initialisation tableau paniers
$cartStats = [];
foreach ($period as $date) {
    $key = $date->format('Y-m-d');
    $cartStats[$key] = [
        'local' => [],
        'remote' => [],
        'missing_from_local' => [],
        'missing_from_remote' => [],
    ];
}

foreach ($localCarts as $c) {
    $cartStats[$c['date_created']]['local'][] = (int)$c['id_cart'];
}
foreach ($remoteCarts as $c) {
    $cartStats[$c['date_created']]['remote'][] = (int)$c['id_cart'];
}

foreach ($cartStats as $date => &$row) {
    $row['missing_from_local'] = array_diff($row['remote'], $row['local']);
    $row['missing_from_remote'] = array_diff($row['local'], $row['remote']);
}

// === ADRESSES ===
// Récupération des adresses distantes
$sqlRemoteAddresses = '
    SELECT id_address, DATE(date_add) as date_created
    FROM ps_address
    WHERE date_add BETWEEN :start AND :end
';
$stmt = $remoteDb->prepare($sqlRemoteAddresses);
$stmt->execute([
    ':start' => $startDate->format('Y-m-d 00:00:00'),
    ':end' => $endDate->format('Y-m-d 23:59:59'),
]);
$remoteAddresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des adresses locales
$sqlLocalAddresses = '
    SELECT id_address, DATE(date_add) as date_created
    FROM '._DB_PREFIX_.'address
    WHERE date_add BETWEEN "'.$startDate->format('Y-m-d').' 00:00:00"
                      AND "'.$endDate->format('Y-m-d').' 23:59:59"
';
$localAddresses = Db::getInstance()->executeS($sqlLocalAddresses);

// Initialisation tableau adresses
$addressStats = [];
foreach ($period as $date) {
    $key = $date->format('Y-m-d');
    $addressStats[$key] = [
        'local' => [],
        'remote' => [],
        'missing_from_local' => [],
        'missing_from_remote' => [],
    ];
}

foreach ($localAddresses as $a) {
    $addressStats[$a['date_created']]['local'][] = (int)$a['id_address'];
}
foreach ($remoteAddresses as $a) {
    $addressStats[$a['date_created']]['remote'][] = (int)$a['id_address'];
}

foreach ($addressStats as $date => &$row) {
    $row['missing_from_local'] = array_diff($row['remote'], $row['local']);
    $row['missing_from_remote'] = array_diff($row['local'], $row['remote']);
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques commandes et clients</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 95%; margin-top: 20px; }
        th, td { border: 1px solid #999; padding: 8px 12px; vertical-align: top; text-align: center; }
        th { background-color: #eee; }
        .warn { background-color: #fff2cc; }
    </style>
</head>
<body>

<h1>📦 Comparaison des commandes</h1>
<p>Du <strong><?= $startDate->format('d/m/Y') ?></strong> au <strong><?= $endDate->format('d/m/Y') ?></strong></p>
<table>
    <thead>
    <tr>
        <th>Date</th>
        <th>Commandes nouveau.cloture-discount.fr</th>
        <th>Commandes cloture-discount.fr</th>
        <th class="warn">Présents sur cloture-discount.fr uniquement</th>
        <th class="warn">Présents sur nouveau.cloture-discount.fr uniquement</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($orderStats as $date => $row): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($date)) ?></td>
            <td><?= count($row['local']) ?></td>
            <td><?= count($row['remote']) ?></td>
            <td class="warn"><?= empty($row['missing_from_local']) ? '-' : implode(', ', $row['missing_from_local']) ?></td>
            <td class="warn"><?= empty($row['missing_from_remote']) ? '-' : implode(', ', $row['missing_from_remote']) ?></td>
        </tr>
        <?php if (!empty($row['example_missing_from_local'])): ?>
            <tr>
                <td colspan="5" style="text-align:left; font-size: 0.95em; background-color:#fefae0;">
                    🧩 <strong>Exemple de commande manquante :</strong><br>
                    <?= htmlspecialchars($row['example_missing_from_local']) ?>
                </td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>

    </tbody>
</table>

<h1 style="margin-top:50px;">👥 Comparaison des clients</h1>
<p>Du <strong><?= $startDate->format('d/m/Y') ?></strong> au <strong><?= $endDate->format('d/m/Y') ?></strong></p>
<table>
    <thead>
    <tr>
        <th>Date</th>
        <th>Clients nouveau.cloture-discount.fr</th>
        <th>Clients cloture-discount.fr</th>
        <th class="warn">Présents sur cloture-discount.fr uniquement</th>
        <th class="warn">Présents sur nouveau.cloture-discount.fr uniquement</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($clientStats as $date => $row): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($date)) ?></td>
            <td><?= count($row['local']) ?></td>
            <td><?= count($row['remote']) ?></td>
            <td class="warn"><?= empty($row['missing_from_local']) ? '-' : implode(', ', $row['missing_from_local']) ?></td>
            <td class="warn"><?= empty($row['missing_from_remote']) ? '-' : implode(', ', $row['missing_from_remote']) ?></td>
        </tr>
        <?php if (!empty($row['example_missing_from_local'])): ?>
            <tr>
                <td colspan="5" style="text-align:left; font-size: 0.95em; background-color:#fefae0;">
                    🧩 <strong>Exemple de client manquant :</strong><br>
                    <?= htmlspecialchars($row['example_missing_from_local']) ?>
                </td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>

    </tbody>
</table>

<h1 style="margin-top:50px;">🛒 Comparaison des paniers</h1>
<p>Du <strong><?= $startDate->format('d/m/Y') ?></strong> au <strong><?= $endDate->format('d/m/Y') ?></strong></p>
<table>
    <thead>
    <tr>
        <th>Date</th>
        <th>Paniers nouveau.cloture-discount.fr</th>
        <th>Paniers cloture-discount.fr</th>
        <th class="warn">Présents sur cloture-discount.fr uniquement</th>
        <th class="warn">Présents sur nouveau.cloture-discount.fr uniquement</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($cartStats as $date => $row): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($date)) ?></td>
            <td><?= count($row['local']) ?></td>
            <td><?= count($row['remote']) ?></td>
            <td class="warn"><?= empty($row['missing_from_local']) ? '-' : implode(', ', $row['missing_from_local']) ?></td>
            <td class="warn"><?= empty($row['missing_from_remote']) ? '-' : implode(', ', $row['missing_from_remote']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h1 style="margin-top:50px;">🏠 Comparaison des adresses</h1>
<p>Du <strong><?= $startDate->format('d/m/Y') ?></strong> au <strong><?= $endDate->format('d/m/Y') ?></strong></p>
<table>
    <thead>
    <tr>
        <th>Date</th>
        <th>Adresses nouveau.cloture-discount.fr</th>
        <th>Adresses cloture-discount.fr</th>
        <th class="warn">Manquantes ici</th>
        <th class="warn">Manquantes là-bas</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($addressStats as $date => $row): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($date)) ?></td>
            <td><?= count($row['local']) ?></td>
            <td><?= count($row['remote']) ?></td>
            <td class="warn"><?= empty($row['missing_from_local']) ? '-' : implode(', ', $row['missing_from_local']) ?></td>
            <td class="warn"><?= empty($row['missing_from_remote']) ? '-' : implode(', ', $row['missing_from_remote']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
