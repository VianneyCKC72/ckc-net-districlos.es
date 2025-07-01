<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$startDate = new DateTime('2025-06-27 11:30:00');
$endDate = new DateTime();

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

$start = $startDate->format('Y-m-d H:i:s');
$now = $endDate->format('Y-m-d H:i:s');
function getCommandesAvecEcartProduits(PDO $db): array
{
    $stmt = $db->query("
        SELECT 
            o.id_order,
            o.id_cart,
            o.reference,
            ROUND(o.total_products, 2) AS total_products_enregistre_ht,
            ROUND(od.total_ht, 2) AS total_lignes_ht,
            ROUND(o.total_products - od.total_ht, 2) AS ecart_total_products_ht,
            (
                SELECT GROUP_CONCAT(CONCAT(odp.product_name, ' (x', odp.product_quantity, ', ', ROUND(odp.unit_price_tax_excl, 2), '€ HT)') SEPARATOR ' | ')
                FROM ps_order_detail odp
                WHERE odp.id_order = o.id_order
            ) AS details_produits
        FROM ps_orders o
        LEFT JOIN (
            SELECT 
                id_order, 
                SUM(total_price_tax_excl) AS total_ht
            FROM ps_order_detail
            GROUP BY id_order
        ) od ON o.id_order = od.id_order
        WHERE o.date_add >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        HAVING ecart_total_products_ht != 0
        ORDER BY o.date_add DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$commandesAvecEcart = getCommandesAvecEcartProduits($dbB); // ou $dbA selon le site concerné

function getStatsBySynchro(PDO $db, string $start, string $end, int $synchro): array
{
    $stmt = $db->prepare("
        SELECT
            COUNT(o.id_order) AS nb_commandes,
            ROUND(AVG(o.total_paid_tax_incl), 2) AS panier_moyen,
            ROUND(SUM(o.total_paid_tax_incl), 2) AS total_ca
        FROM ps_orders o
        INNER JOIN ps_cart c ON o.id_cart = c.id_cart
        WHERE o.valid = 1
          AND o.date_add BETWEEN :start AND :end
          AND c.synchro = :synchro
    ");
    $stmt->execute([
        'start' => $start,
        'end' => $end,
        'synchro' => $synchro,
    ]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$statsA = getStatsBySynchro($dbA, $start, $now, 0); // site A, synchro = 0
$statsB = getStatsBySynchro($dbB, $start, $now, 1); // site B, synchro = 0 todo : a mettre ensuite 0
$statsSynchro = getStatsBySynchro($dbB, $start, $now, 1); // site B, synchro = 1

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
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F6F2EB;
            padding: 2rem;
            color: #333;
        }

        section {
            margin-bottom: 3rem;
        }

        .card {
            margin-bottom: 2rem;
        }

        h1, h2 {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 45px;
            font-weight: 800;
            line-height: 48px;
            color: #026473;
        }

        h2 {
            font-size: 30px;
            font-weight: 600;
            color: #026473;
        }

        h5 {
            font-size: 18px;
            font-weight: 600;
            color: #026473;
        }

        .card {
            border-radius: 1rem;
            border: 1px solid #dce1e5;
            background-color: white;
            box-shadow: 0 3px 8px rgba(2, 100, 115, 0.05);
        }

        <
        style >

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');

        :root {
            --color-principal: #026473;
            --color-secondaire: #79c046;
            --color-fond: #F6F2EB;
            --color-blanc: #ffffff;
            --color-gris: #777;
            --font-base: 14px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            font-size: var(--font-base);
            background-color: var(--color-fond);
            padding: 2rem;
            color: #333;
        }

        h1, h2, h5 {
            color: var(--color-principal);
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 36px;
            font-weight: 800;
            line-height: 42px;
        }

        h2 {
            font-size: 24px;
            font-weight: 600;
            line-height: 30px;
        }

        h5 {
            font-size: 16px;
            font-weight: 600;
        }

        section {
            margin-bottom: 3rem;
        }

        .card {
            background-color: var(--color-blanc);
            border: 1px solid #dce1e5;
            border-radius: 1rem;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 2rem;
            padding: 1.5rem;
        }

        .card-title {
            color: var(--color-principal);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .table {
            font-size: 13px;
        }

        .table thead {
            background-color: var(--color-principal);
            color: var(--color-blanc);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(2, 100, 115, 0.05);
        }

        .table td, .table th {
            padding: 0.6rem 0.8rem;
            vertical-align: middle;
        }

        .text-muted {
            color: var(--color-gris) !important;
        }

        .chart-container {
            margin-bottom: 2.5rem;
            padding: 1rem;
            background-color: var(--color-blanc);
            border-radius: 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            border: 1px solid #ddd;
        }

        .mb-5 {
            margin-bottom: 3rem !important;
        }

        .btn-primary {
            background-color: var(--color-principal);
            border: none;
        }

        .btn-primary:hover {
            background-color: #014957;
        }

        .extrapolated-section {
            margin-top: 3rem;
        }

        .stat-card {
            background-color: #fff;
            border: 1px solid #dce1e5;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(2, 100, 115, 0.05);
            text-align: center;
            height: 100%;
        }

        .stat-icon {
            font-size: 26px;
            margin-bottom: 0.5rem;
            color: #026473;
        }

        .stat-title {
            font-size: 14px;
            font-weight: 600;
            color: #026473;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 16px;
            font-weight: 500;
            margin: 0.2rem 0;
        }

        .site-label {
            display: inline-block;
            font-size: 12px;
            color: #777;
            margin-left: 4px;
        }

    </style>

</head>

<body class="bg-light">
<div class="container py-5">

    <h1>Comparaison des commandes</h1>
    <p>
        Période sélectionnée : <strong><?= $startDate->format('d/m/Y') ?></strong> au
        <strong><?= $endDate->format('d/m/Y') ?></strong><br>
        <em style="color: #666;">Statistiques fiables disponibles à partir du <strong>27/06/2025 à 11h30</strong>
            (activation de la synchronisation via le champ <code>synchro</code>).</em>
    </p>


    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="card-title h5">Site A : Clôture Discount</h2>
                    <ul class="list-unstyled mb-0">
                        <li><strong>Commandes :</strong> <?= $statsA['nb_commandes'] ?></li>
                        <li><strong>Panier moyen
                                :</strong> <?= number_format($statsA['panier_moyen'] ?? 0, 2, ',', ' ') ?> €
                        </li>
                        <li><strong>Chiffre d'affaires
                                :</strong> <?= number_format($statsA['total_ca'] ?? 0, 2, ',', ' ') ?> €
                        </li>
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
                        <li><strong>Panier moyen
                                :</strong> <?= number_format($statsB['panier_moyen'] ?? 0, 2, ',', ' ') ?> €
                        </li>
                        <li><strong>Chiffre d'affaires
                                :</strong> <?= number_format($statsB['total_ca'] ?? 0, 2, ',', ' ') ?> €
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <section>
        <h2 class="mb-3">Rapport comparatif extrapolé à 100% de trafic</h2>
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
                            Site B 🚀<br><small class="text-muted">+<?= round($commandesB_100 - $commandesA_100, 2) ?>
                                commandes</small>
                        <?php elseif ($commandesA_100 > $commandesB_100): ?>
                            Site A<br><small class="text-muted">+<?= round($commandesA_100 - $commandesB_100, 2) ?>
                                commandes</small>
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
                            Site B 🚀<br><small
                                    class="text-muted">+<?= number_format($caB_100 - $caA_100, 2, ',', ' ') ?> € de
                                CA</small>
                        <?php elseif ($caA_100 > $caB_100): ?>
                            Site A<br><small class="text-muted">+<?= number_format($caA_100 - $caB_100, 2, ',', ' ') ?>
                                € de CA</small>
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
                            Site B ✅<br><small
                                    class="text-muted">+<?= number_format($panierB - $panierA, 2, ',', ' ') ?> €</small>
                        <?php elseif ($panierA > $panierB): ?>
                            Site A<br><small class="text-muted">+<?= number_format($panierA - $panierB, 2, ',', ' ') ?>
                                €</small>
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
                            Site B 💪<br><small
                                    class="text-muted">+<?= round($taux_conversion_B - $taux_conversion_A, 2) ?>
                                pts</small>
                        <?php elseif ($taux_conversion_A > $taux_conversion_B): ?>
                            Site A<br><small
                                    class="text-muted">+<?= round($taux_conversion_A - $taux_conversion_B, 2) ?>
                                pts</small>
                        <?php else: ?>
                            Égalité
                        <?php endif; ?>
                    </td>
                </tr>
                </tbody>

            </table>
        </div>
    </section>

    <section>
        <h2 class="mb-3">Graphique comparatif</h2>
        <canvas id="chart"></canvas>
    </section>

    <section class="extrapolated-section">
        <h2>Indicateurs extrapolés à 100% de trafic</h2>
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon">📄</div>
                    <div class="stat-title">Commandes (100%)</div>
                    <div class="stat-value"><?= $commandesA_100 ?> <span class="site-label">Site A</span></div>
                    <div class="stat-value"><?= $commandesB_100 ?> <span class="site-label">Site B</span></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-title">CA (100%)</div>
                    <div class="stat-value"><?= number_format($caA_100, 2, ',', ' ') ?> € <span class="site-label">Site A</span>
                    </div>
                    <div class="stat-value"><?= number_format($caB_100, 2, ',', ' ') ?> € <span class="site-label">Site B</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-title">Taux de conversion</div>
                    <div class="stat-value"><?= $taux_conversion_A ?> % <span class="site-label">Site A</span></div>
                    <div class="stat-value"><?= $taux_conversion_B ?> % <span class="site-label">Site B</span></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon">💵</div>
                    <div class="stat-title">RPV (€/100 visites)</div>
                    <div class="stat-value"><?= number_format($caA_100 / 100, 2, ',', ' ') ?> € <span
                                class="site-label">Site A</span></div>
                    <div class="stat-value"><?= number_format($caB_100 / 100, 2, ',', ' ') ?> € <span
                                class="site-label">Site B</span></div>
                </div>
            </div>
        </div>
    </section>


    <section>
        <h2 class="mb-3">📊 Graphiques comparatifs extrapolés (100% du trafic)</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <h5>Commandes extrapolées</h5>
                <canvas id="chartOrders" height="200"></canvas>
            </div>
            <div class="col-md-6">
                <h5>Chiffre d'affaires extrapolé (€)</h5>
                <canvas id="chartCA" height="200"></canvas>
            </div>
            <div class="col-md-6">
                <h5>Taux de conversion (%)</h5>
                <canvas id="chartConversion" height="200"></canvas>
            </div>
            <div class="col-md-6">
                <h5>Revenu par visiteur (RPV)</h5>
                <canvas id="chartRPV" height="200"></canvas>
            </div>
        </div>


        <?php if (!empty($commandesAvecEcart)): ?>
            <h2 class="mt-5 mb-3">🚨 Commandes avec incohérence sur les produits (7 derniers jours)</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-warning">
                    <tr>
                        <th>ID</th>
                        <th>Panier</th>
                        <th>Référence</th>
                        <th>Total enregistré (HT)</th>
                        <th>Total réel (HT)</th>
                        <th>Écart</th>
                        <th>Détail des produits</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($commandesAvecEcart as $c): ?>
                        <tr>
                            <td><?= $c['id_order'] ?></td>
                            <td><?= $c['id_cart'] ?></td>
                            <td><?= htmlspecialchars($c['reference']) ?></td>
                            <td><?= number_format($c['total_products_enregistre_ht'], 2, ',', ' ') ?> €</td>
                            <td><?= number_format($c['total_lignes_ht'], 2, ',', ' ') ?> €</td>
                            <td class="text-danger fw-bold"><?= number_format($c['ecart_total_products_ht'], 2, ',', ' ') ?>
                                €
                            </td>
                            <td>
                                <ul class="mb-0">
                                    <?php
                                    $produits = explode('|', $c['details_produits']);
                                    foreach ($produits as $produit):
                                        ?>
                                        <li><?= trim($produit) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>


</div>
</section>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const data = {
        labels: ['Commandes', 'Panier moyen (€)', 'CA (€)'],
        datasets: [
            {
                label: 'Site A (Clôture Discount)',
                data: [<?= $statsA['nb_commandes'] * 100 ?>, <?= $statsA['panier_moyen'] ?? 0 ?>, <?= $statsA['total_ca'] ?? 0 ?>],
                backgroundColor: 'rgba(255, 99, 132, 0.6)'
            },
            {
                label: 'Site B (Nouveau)',
                data: [<?= $statsB['nb_commandes'] * 100 ?>, <?= $statsB['panier_moyen'] ?? 0 ?>, <?= $statsB['total_ca'] ?? 0 ?>],
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
                legend: {position: 'top'},
                title: {display: true, text: 'Performance commerciale Site A vs Site B'}
            }
        }
    });
    const chartConfigs = [
        {
            id: 'chartOrders',
            label: 'Commandes extrapolées',
            dataA: <?= $commandesA_100 ?>,
            dataB: <?= $commandesB_100 ?>
        },
        {
            id: 'chartCA',
            label: 'Chiffre d\'affaires extrapolé (€)',
            dataA: <?= $caA_100 ?>,
            dataB: <?= $caB_100 ?>
        },
        {
            id: 'chartConversion',
            label: 'Taux de conversion (%)',
            dataA: <?= $commandesA_100 ?>,
            dataB: <?= $commandesB_100 ?>
        },
        {
            id: 'chartRPV',
            label: 'Revenu par visiteur (RPV €)',
            dataA: <?= round($caA_100 / 100, 2) ?>,
            dataB: <?= round($caB_100 / 100, 2) ?>
        }
    ];

    chartConfigs.forEach(cfg => {
        new Chart(document.getElementById(cfg.id), {
            type: 'bar',
            data: {
                labels: ['Site A', 'Site B'],
                datasets: [{
                    label: cfg.label,
                    data: [cfg.dataA, cfg.dataB],
                    backgroundColor: ['#026473', '#79c046']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {display: false},
                    title: {display: false}
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>


</body>

</html>
