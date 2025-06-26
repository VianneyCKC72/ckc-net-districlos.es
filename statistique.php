<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ⚙️ Config base de données
$dbA = new PDO(
    'mysql:host=87.98.141.171;dbname=districlosnew;charset=utf8',
    'districlosnew',
    '11Atb2I20TJ6joDc',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$dbB = new PDO(
    'mysql:host=87.98.141.171;dbname=districlos_refonte;charset=utf8',
    'districlos_refonte',
    'TXM1qjk9teu_cfb9wxv',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$start = date('Y-m-d', strtotime('-1 day')) . ' 09:00:00';
$now = date('Y-m-d H:i:s');

// 🔍 Stats site A (ancien site)
function getStatsFromDBA(PDO $db, string $start, string $end): array {
    $stmt = $db->prepare("
        SELECT
            COUNT(o.id_order) AS nb_commandes,
            ROUND(AVG(o.total_paid_tax_incl), 2) AS panier_moyen,
            ROUND(SUM(o.total_paid_tax_incl), 2) AS total_ca
        FROM ps_orders o
        INNER JOIN ps_cart c ON o.id_cart = c.id_cart
        WHERE o.valid = 1
          AND o.date_add BETWEEN :start AND :end
          AND c.synchro = 0
    ");
    $stmt->execute(['start' => $start, 'end' => $end]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 🔍 Stats site B (nouveau site) — uniquement commandes dont le panier est tagué `synchro= 1`
function getStatsFromDBB(PDO $db, string $start, string $end): array {
    $stmt = $db->prepare("
        SELECT
            COUNT(o.id_order) AS nb_commandes,
            ROUND(AVG(o.total_paid_tax_incl), 2) AS panier_moyen,
            ROUND(SUM(o.total_paid_tax_incl), 2) AS total_ca
        FROM ps_orders o
        INNER JOIN ps_cart c ON o.id_cart = c.id_cart
        WHERE o.valid = 1
          AND o.date_add BETWEEN :start AND :end
          AND c.synchro = 1
    ");
    $stmt->execute(['start' => $start, 'end' => $end]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$statsA = getStatsFromDBA($dbA, $start, $now);
$statsB = getStatsFromDBB($dbB, $start, $now);

// 🧠 Trafic réel
$traficA = 70;
$traficB = 30;

// 📈 Extrapolation du site A et B à 100% de trafic
$commandesA_100 = $traficA > 0 ? round($statsA['nb_commandes'] / $traficA * 100, 2) : 0;
$commandesB_100 = $traficB > 0 ? round($statsB['nb_commandes'] / $traficB * 100, 2) : 0;

$caA_100 = $traficA > 0 ? round($statsA['total_ca'] / $traficA * 100, 2) : 0;
$caB_100 = $traficB > 0 ? round($statsB['total_ca'] / $traficB * 100, 2) : 0;

// 💡 Panier moyen inchangé (déjà en moyenne unitaire)
$panierA = $statsA['panier_moyen'] ?? 0;
$panierB = $statsB['panier_moyen'] ?? 0;

// 🧮 Taux de transformation estimés (% des visiteurs qui commandent)
$taux_conversion_A = round($commandesA_100, 2);
$taux_conversion_B = round($commandesB_100, 2);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Comparatif Statistiques A/B</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 2rem;
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 1rem;
        }
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<body class="bg-light">
<div class="container py-5">

    <h1 class="mb-4">📈 Statistiques des sites depuis hier à 9h</h1>
    <p class="text-muted">Période analysée : <strong><?= $start ?> → <?= $now ?></strong></p>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="card-title h5">Site A : Clôture Discount</h2>
                    <ul class="list-unstyled mb-0">
                        <li><strong>Commandes :</strong> <?= $statsA['nb_commandes'] ?></li>
                        <li><strong>Panier moyen :</strong> <?= number_format($statsA['panier_moyen'] ?? 0, 2, ',', ' ') ?> €</li>
                        <li><strong>Chiffre d'affaires :</strong> <?= number_format($statsA['total_ca'] ?? 0, 2, ',', ' ') ?> €</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="card-title h5">Site B : Nouveau Clôture Discount</h2>
                    <ul class="list-unstyled mb-0">
                        <li><strong>Commandes :</strong> <?= $statsB['nb_commandes'] ?></li>
                        <li><strong>Panier moyen :</strong> <?= number_format($statsB['panier_moyen'] ?? 0, 2, ',', ' ') ?> €</li>
                        <li><strong>Chiffre d'affaires :</strong> <?= number_format($statsB['total_ca'] ?? 0, 2, ',', ' ') ?> €</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <h2 class="mb-3">📊 Rapport comparatif extrapolé à 100% de trafic</h2>
    <div class="table-responsive mb-5">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
            <tr>
                <th>Indicateur</th>
                <th>Site A</th>
                <th>Site B</th>
                <th>Plus performant</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Commandes extrapolées (100%)</td>
                <td><?= $commandesA_100 ?></td>
                <td><?= $commandesB_100 ?></td>
                <td>
                    <?php if ($commandesB_100 > $commandesA_100): ?>
                        Site B 🚀<br><small class="text-muted">+<?= round($commandesB_100 - $commandesA_100, 2) ?> commandes</small>
                    <?php elseif ($commandesA_100 > $commandesB_100): ?>
                        Site A<br><small class="text-muted">+<?= round($commandesA_100 - $commandesB_100, 2) ?> commandes</small>
                    <?php else: ?>
                        Égalité
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>CA extrapolé (100%)</td>
                <td><?= number_format($caA_100, 2, ',', ' ') ?> €</td>
                <td><?= number_format($caB_100, 2, ',', ' ') ?> €</td>
                <td>
                    <?php if ($caB_100 > $caA_100): ?>
                        Site B 🚀<br><small class="text-muted">+<?= number_format($caB_100 - $caA_100, 2, ',', ' ') ?> € de CA</small>
                    <?php elseif ($caA_100 > $caB_100): ?>
                        Site A<br><small class="text-muted">+<?= number_format($caA_100 - $caB_100, 2, ',', ' ') ?> € de CA</small>
                    <?php else: ?>
                        Égalité
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Panier moyen</td>
                <td><?= number_format($panierA, 2, ',', ' ') ?> €</td>
                <td><?= number_format($panierB, 2, ',', ' ') ?> €</td>
                <td>
                    <?php if ($panierB > $panierA): ?>
                        Site B ✅<br><small class="text-muted">+<?= number_format($panierB - $panierA, 2, ',', ' ') ?> €</small>
                    <?php elseif ($panierA > $panierB): ?>
                        Site A<br><small class="text-muted">+<?= number_format($panierA - $panierB, 2, ',', ' ') ?> €</small>
                    <?php else: ?>
                        Égalité
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Taux de transformation estimé</td>
                <td><?= $taux_conversion_A ?> %</td>
                <td><?= $taux_conversion_B ?> %</td>
                <td>
                    <?php if ($taux_conversion_B > $taux_conversion_A): ?>
                        Site B 💪<br><small class="text-muted">+<?= round($taux_conversion_B - $taux_conversion_A, 2) ?> pts</small>
                    <?php elseif ($taux_conversion_A > $taux_conversion_B): ?>
                        Site A<br><small class="text-muted">+<?= round($taux_conversion_A - $taux_conversion_B, 2) ?> pts</small>
                    <?php else: ?>
                        Égalité
                    <?php endif; ?>
                </td>
            </tr>
            </tbody>

        </table>
    </div>

    <h2 class="mb-3">📊 Graphique comparatif</h2>
    <canvas id="chart" height="100"></canvas>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const data = {
        labels: ['Commandes', 'Panier moyen (€)', 'CA (€)'],
        datasets: [
            {
                label: 'Site A (Clôture Discount)',
                data: [<?= $statsA['nb_commandes'] ?>, <?= $statsA['panier_moyen'] ?? 0 ?>, <?= $statsA['total_ca'] ?? 0 ?>],
                backgroundColor: 'rgba(255, 99, 132, 0.6)'
            },
            {
                label: 'Site B (Nouveau)',
                data: [<?= $statsB['nb_commandes'] ?>, <?= $statsB['panier_moyen'] ?? 0 ?>, <?= $statsB['total_ca'] ?? 0 ?>],
                backgroundColor: 'rgba(75, 192, 192, 0.6)'
            }
        ]
    };

    new Chart(document.getElementById('chart'), {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'Performance commerciale Site A vs Site B' }
            }
        }
    });
</script>
</body>

</html>
