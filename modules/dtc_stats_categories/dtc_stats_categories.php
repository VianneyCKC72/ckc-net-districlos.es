<?php
/**
 * Module dtc_stats_categories
 * Ventilation des ventes et du CA par catégories de produit (graphiques Pie)
 * Filtres : niveau de catégorie (1/2/3) et groupe de client
 *
 * @author    DISTRICLOS
 * @copyright 2026 DISTRICLOS
 * @license   Propriétaire
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_Stats_Categories extends Module
{
    private $html = '';

    /** Id du groupe "Professionnels" (comme dtc_stats_general) */
    const CUSTOMER_GROUP_PROFESSIONAL_ID = 10;

    /**
     * Correspondance niveau utilisateur → level_depth PrestaShop
     * (level_depth 0=root, 1=home, donc les catégories visibles démarrent à 2)
     */
    private const LEVEL_DEPTH_MAP = [
        1 => 2,
        2 => 3,
        3 => 4,
    ];

    /** Palette harmonisée avec les autres modules DTC */
    private $chartColors = [
        '#026473', '#4ABDAC', '#F8859B', '#B4C7DD', '#F3B58F',
        '#76F383', '#F72D6D', '#FFD166', '#EF476F', '#06D6A0',
        '#118AB2', '#073B4C', '#A8DADC', '#457B9D', '#E63946',
        '#2A9D8F', '#E9C46A', '#F4A261', '#264653', '#E76F51',
    ];

    public function __construct()
    {
        $this->name = 'dtc_stats_categories';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = 'DASHBOARD - Catégories';
        $this->description = 'Ventilation du volume de ventes et du CA par catégories de produit';
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayAdminStatsModules');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            'index.php?controller=AdminStats&module=' . $this->name .
            '&token=' . Tools::getAdminTokenLite('AdminStats')
        );
    }

    // ════════════════════════════════════════════════════════════════════════════
    //  Hook principal
    // ════════════════════════════════════════════════════════════════════════════

    public function hookDisplayAdminStatsModules()
    {
        try {
            $date_from = $this->context->employee->stats_date_from;
            $date_to   = $this->context->employee->stats_date_to;

            // Filtres (même logique que dtc_stats_general pour le groupe)
            $customer_group = Tools::getValue('customer_group', 'tous');
            if (!in_array($customer_group, ['tous', 'particuliers', 'professionnels'])) {
                $customer_group = 'tous';
            }
            $filterLevel = (int)Tools::getValue('dtc_cat_level', 0);
            if (!in_array($filterLevel, [0, 1, 2, 3])) {
                $filterLevel = 0;
            }

            // ── Chargement des données ───────────────────────────────────────────
            $categoryStats = $this->getCategoryStats($date_from, $date_to, $filterLevel, $customer_group);

            // ── Chart.js ─────────────────────────────────────────────────────────
            $this->context->controller->addJS(
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js'
            );

            // ── Panel-heading identique à dtc_stats_general + filtre niveau ───────
            $from_format = (new DateTime($date_from))->format('d/m/Y');
            $to_format  = (new DateTime($date_to))->format('d/m/Y');
            $currentWeekNumber = (int)date('W');
            $baseUrl = $this->context->link->getAdminLink('AdminStats') . '&module=' . $this->name;
            $levelParam = $filterLevel > 0 ? '&dtc_cat_level=' . $filterLevel : '';

            $this->html = '
        <div class="panel-heading">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                <div style="flex-shrink: 0;">
                    <div style="margin-bottom: 15px;">
                        <div class="customer-filter-tabs" style="display: flex; align-items: center; gap: 8px; font-size: 14px; flex-wrap: wrap;">
                            <a href="' . $baseUrl . '&customer_group=tous' . $levelParam . '" class="filter-tab ' . ($customer_group === 'tous' ? 'active' : '') . '"
                                style="text-decoration: none; padding: 5px 0; ' . ($customer_group === 'tous' ? 'color: #026473; font-weight: bold; border-bottom: 2px solid #026473;' : 'color: #999;') . '">Tous</a>
                            <span style="color: #026473; font-weight: 300;">|</span>
                            <a href="' . $baseUrl . '&customer_group=particuliers' . $levelParam . '" class="filter-tab ' . ($customer_group === 'particuliers' ? 'active' : '') . '"
                                style="text-decoration: none; padding: 5px 0; ' . ($customer_group === 'particuliers' ? 'color: #026473; font-weight: bold; border-bottom: 2px solid #026473;' : 'color: #999;') . '">Particuliers</a>
                            <span style="color: #026473; font-weight: 300;">|</span>
                            <a href="' . $baseUrl . '&customer_group=professionnels' . $levelParam . '" class="filter-tab ' . ($customer_group === 'professionnels' ? 'active' : '') . '"
                                style="text-decoration: none; padding: 5px 0; ' . ($customer_group === 'professionnels' ? 'color: #026473; font-weight: bold; border-bottom: 2px solid #026473;' : 'color: #999;') . '">Professionnels</a>
                            <span style="color: #026473; font-weight: 300;">|</span>
                            <span style="color: #666; font-size: 13px;">Niveau catégorie</span>
                            <select id="dtcCatLevelSelect" style="border: 1px solid #ddd; border-radius: 8px; padding: 4px 8px; font-size: 13px; margin-left: 4px;">
                                <option value="0"' . ($filterLevel === 0 ? ' selected' : '') . '>Tous</option>
                                <option value="1"' . ($filterLevel === 1 ? ' selected' : '') . '>Niveau 1</option>
                                <option value="2"' . ($filterLevel === 2 ? ' selected' : '') . '>Niveau 2</option>
                                <option value="3"' . ($filterLevel === 3 ? ' selected' : '') . '>Niveau 3</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" id="expandStatsCat" style="border-radius: 50%; width: 35px; height: 35px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <div style="flex-grow: 1; text-align: center; padding-top: 5px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; border-bottom: none !important; background-color: transparent !important; font-weight: 400;">' . $this->displayName . '</h3>
                    <div class="stats-date-range" style="font-size: 16px; display: flex; flex-direction: column; gap: 4px;">
                        <div><span class="date-label" style="color: #999;">Du :</span> <span class="date-value" style="color: #026473; font-weight: 400;">' . $from_format . '</span></div>
                        <div><span class="date-label" style="color: #999;">Au :</span> <span class="date-value" style="color: #026473; font-weight: 400;">' . $to_format . '</span></div>
                    </div>
                </div>
                <div style="flex-shrink: 0; text-align: right;">
                    <div style="margin-bottom: 12px; font-size: 14px;">
                        <span style="color: #000;">Semaine actuelle :</span> <span style="color: #026473; font-weight: 400;">S' . str_pad($currentWeekNumber, 2, '0', STR_PAD_LEFT) . '</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-body">';

            if (empty($categoryStats)) {
                $this->html .= '
            <div class="alert alert-info" style="border-radius:15px; text-align:center; margin:30px 0;">
                Aucune donnée disponible pour les filtres et la période sélectionnés.
            </div>';
            } else {
                $this->html .= $this->renderCharts($categoryStats, $filterLevel, $customer_group);
            }

            $this->html .= '
        </div>'; // .panel-body

            $this->html .= $this->getStyles();
            $this->html .= $this->getScripts($categoryStats, $customer_group, $baseUrl);

            return $this->html;

        } catch (Exception $e) {
            return '<div class="alert alert-danger">Erreur dans le module DASHBOARD - Catégories : '
                . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    // ════════════════════════════════════════════════════════════════════════════
    //  Rendu HTML
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Construit le fragment SQL JOIN pour le filtre groupe (identique à dtc_stats_general)
     *
     * @param string $customer_group tous|particuliers|professionnels
     * @return string
     */
    private function buildGroupJoin(string $customer_group): string
    {
        if ($customer_group === 'particuliers') {
            return '
            INNER JOIN `' . _DB_PREFIX_ . 'customer` AS cust ON cust.`id_customer` = o.`id_customer`
                AND cust.`id_default_group` != ' . (int)self::CUSTOMER_GROUP_PROFESSIONAL_ID;
        }
        if ($customer_group === 'professionnels') {
            return '
            INNER JOIN `' . _DB_PREFIX_ . 'customer` AS cust ON cust.`id_customer` = o.`id_customer`
                AND cust.`id_default_group` = ' . (int)self::CUSTOMER_GROUP_PROFESSIONAL_ID;
        }
        return '';
    }

    /**
     * Génère le HTML des deux graphiques avec les badges de filtre actifs
     */
    private function renderCharts(
        array $stats,
        int $filterLevel,
        string $customer_group
    ): string {
        $totalQty = array_sum(array_column($stats, 'qty'));
        $totalCa  = array_sum(array_column($stats, 'ca'));

        $levelLabel = $filterLevel > 0 ? 'Niveau ' . $filterLevel : 'Tous niveaux';
        $groupLabels = ['tous' => 'Tous', 'particuliers' => 'Particuliers', 'professionnels' => 'Professionnels'];
        $groupLabel  = $groupLabels[$customer_group] ?? 'Tous';

        $html = '
        <!-- Section header -->
        <div style="margin-bottom:20px; margin-top:10px;">
            <div style="background-color:#E9E5E2; border-radius:30px; padding:10px 20px;
                        display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <h3 style="margin:0; font-size:15px; font-weight:600; color:#333;
                           background-color:transparent !important;">
                    Ventilation par catégories de produit
                </h3>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <span class="dtc-badge"><i class="fas fa-layer-group"></i> ' . $levelLabel . '</span>
                    <span class="dtc-badge"><i class="fas fa-users"></i> ' . $groupLabel . '</span>
                </div>
            </div>
        </div>

        <!-- Ligne des deux graphiques -->
        <div class="row dtc-pie-row">';

        // ── Graphique 1 : Volume (quantités) ────────────────────────────────────
        $html .= '
            <div class="col-lg-6 col-md-12 dtc-pie-col">
                <div class="dtc-pie-card">
                    <h4 class="dtc-pie-title">
                        <i class="fas fa-boxes" style="margin-right:6px;"></i>Volume de ventes (quantités)
                    </h4>
                    <p class="dtc-pie-subtitle">Total&nbsp;: <strong>'
            . number_format($totalQty, 0, ',', '&nbsp;') . '&nbsp;unités</strong></p>
                    <div class="dtc-pie-canvas-wrap">
                        <canvas id="pieChartQty"></canvas>
                    </div>
                    <div class="dtc-legend" id="legendQty"></div>
                </div>
            </div>';

        // ── Graphique 2 : CA (€) ────────────────────────────────────────────────
        $html .= '
            <div class="col-lg-6 col-md-12 dtc-pie-col">
                <div class="dtc-pie-card">
                    <h4 class="dtc-pie-title">
                        <i class="fas fa-euro-sign" style="margin-right:6px;"></i>CO généré par catégorie
                    </h4>
                    <p class="dtc-pie-subtitle">Total&nbsp;: <strong>'
            . number_format($totalCa, 0, ',', '&nbsp;') . '&nbsp;€</strong></p>
                    <div class="dtc-pie-canvas-wrap">
                        <canvas id="pieChartCa"></canvas>
                    </div>
                    <div class="dtc-legend" id="legendCa"></div>
                </div>
            </div>';

        $html .= '
        </div>'; // .row

        return $html;
    }

    // ════════════════════════════════════════════════════════════════════════════
    //  Données
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Récupère les statistiques de ventes agrégées par catégorie pour la période donnée.
     *
     * @param string $date_from      YYYY-MM-DD
     * @param string $date_to        YYYY-MM-DD
     * @param int    $filterLevel    0=tous, 1/2/3 = niveau catégorie
     * @param string $customer_group tous|particuliers|professionnels (comme dtc_stats_general)
     * @return array  [['name'=>string, 'qty'=>int, 'ca'=>float], ...]
     */
    private function getCategoryStats(
        string $date_from,
        string $date_to,
        int $filterLevel,
        string $customer_group
    ): array {
        $cacheKey = 'dtc_stats_categories_v2_' . md5($date_from . '_' . $date_to . '_' . $filterLevel . '_' . $customer_group);

        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }

        $idLang    = (int)$this->context->language->id;
        $groupJoin = $this->buildGroupJoin($customer_group);

        // ── Condition filtre niveau ──────────────────────────────────────────────
        $levelCondition = '';
        if ($filterLevel > 0 && isset(self::LEVEL_DEPTH_MAP[$filterLevel])) {
            $depth          = self::LEVEL_DEPTH_MAP[$filterLevel];
            $levelCondition = ' AND c.`level_depth` = ' . $depth;
        }

        // Total de référence = CO généré (identique à dtc_stats_general)
        $referenceTotal = $this->getReferenceTotalPaid($date_from, $date_to, $groupJoin);

        // Ventilation par catégorie : répartition du total_paid au prorata du HT des lignes
        $sql = '
            SELECT
                cl.`name`                                                                  AS category_name,
                SUM(od.`product_quantity`)                                                 AS total_qty,
                SUM(IF(oht.`order_ht` > 0, o.`total_paid` * (od.`total_price_tax_excl` / oht.`order_ht`), 0)) AS total_ca
            FROM `' . _DB_PREFIX_ . 'orders` AS o
            INNER JOIN (
                SELECT `id_order`, SUM(`total_price_tax_excl`) AS order_ht
                FROM `' . _DB_PREFIX_ . 'order_detail`
                GROUP BY `id_order`
            ) AS oht ON oht.`id_order` = o.`id_order`
            INNER JOIN `' . _DB_PREFIX_ . 'order_detail` AS od
                ON od.`id_order` = o.`id_order`
            INNER JOIN `' . _DB_PREFIX_ . 'product` AS p
                ON p.`id_product` = od.`product_id`
            INNER JOIN `' . _DB_PREFIX_ . 'category` AS c
                ON c.`id_category` = p.`id_category_default`
                AND c.`active` = 1
            INNER JOIN `' . _DB_PREFIX_ . 'category_lang` AS cl
                ON cl.`id_category` = c.`id_category`
                AND cl.`id_lang` = ' . $idLang . $groupJoin . '
            WHERE o.`validated_at` BETWEEN "' . pSQL($date_from) . ' 00:00:00"
                                       AND "' . pSQL($date_to)   . ' 23:59:59"
              AND o.`validated_at` IS NOT NULL'
            . $levelCondition . '
            GROUP BY c.`id_category`, cl.`name`
            HAVING total_ca > 0
            ORDER BY total_ca DESC';

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$rows) {
            Cache::store($cacheKey, [], 1800);
            return [];
        }

        $merged = array_map(function ($row) {
            return [
                'name' => $row['category_name'],
                'qty'  => (int)$row['total_qty'],
                'ca'   => (float)$row['total_ca'],
            ];
        }, $rows);

        // Ramener la somme des CA catégories au total de référence (CO généré)
        $sumCa = array_sum(array_column($merged, 'ca'));
        if ($referenceTotal > 0 && $sumCa > 0) {
            $ratio = $referenceTotal / $sumCa;
            foreach ($merged as &$item) {
                $item['ca'] = round($item['ca'] * $ratio, 2);
            }
            unset($item);
        }

        usort($merged, fn($a, $b) => $b['ca'] <=> $a['ca']);

        $stats  = array_slice($merged, 0, 20);
        $others = array_slice($merged, 20);

        if (!empty($others)) {
            $stats[] = [
                'name' => 'Autres',
                'qty'  => array_sum(array_column($others, 'qty')),
                'ca'   => round(array_sum(array_column($others, 'ca')), 2),
            ];
        }

        Cache::store($cacheKey, $stats, 1800);
        return $stats;
    }

    /**
     * Retourne le CO généré (SUM(orders.total_paid)) pour la période et le filtre groupe.
     * Même logique que dtc_stats_general pour aligner les totaux.
     *
     * @param string $date_from   YYYY-MM-DD
     * @param string $date_to      YYYY-MM-DD
     * @param string $groupJoin    Fragment SQL JOIN pour filtre groupe (vide si tous)
     * @return float
     */
    private function getReferenceTotalPaid(string $date_from, string $date_to, string $groupJoin): float
    {
        $sql = 'SELECT SUM(o.`total_paid`) AS total
                FROM `' . _DB_PREFIX_ . 'orders` AS o' . $groupJoin . '
                WHERE o.`validated_at` BETWEEN "' . pSQL($date_from) . ' 00:00:00"
                                           AND "' . pSQL($date_to) . ' 23:59:59"
                  AND o.`validated_at` IS NOT NULL';
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        return $row && isset($row['total']) ? (float)$row['total'] : 0.0;
    }

    /**
     * Récupère tous les groupes de clients actifs (avec nom localisé)
     *
     * @return array [['id_group'=>int, 'name'=>string], ...]
     */
    private function getCustomerGroups(): array
    {
        $cacheKey = 'dtc_stats_cat_groups_' . (int)$this->context->language->id;

        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }

        $idLang = (int)$this->context->language->id;

        $sql = '
            SELECT g.`id_group`, gl.`name`
            FROM `' . _DB_PREFIX_ . 'group` AS g
            INNER JOIN `' . _DB_PREFIX_ . 'group_lang` AS gl
                ON gl.`id_group` = g.`id_group`
                AND gl.`id_lang` = ' . $idLang . '
            ORDER BY gl.`name` ASC';

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        $groups = $rows ?: [];
        Cache::store($cacheKey, $groups, 3600);
        return $groups;
    }

    // ════════════════════════════════════════════════════════════════════════════
    //  Scripts JS
    // ════════════════════════════════════════════════════════════════════════════

    private function getScripts(array $stats, string $customer_group = 'tous', string $baseUrl = ''): string
    {
        $baseUrlEsc     = addslashes($baseUrl);
        $customerGroupEsc = addslashes($customer_group);

        $chartScript = '';
        if (!empty($stats)) {
            $labels     = array_map(fn($s) => $s['name'], $stats);
            $qtys       = array_column($stats, 'qty');
            $cas        = array_column($stats, 'ca');
            $colors     = array_map(
                fn($i) => $this->chartColors[$i % count($this->chartColors)],
                array_keys($stats)
            );
            $labelsJson = json_encode(array_values($labels), JSON_UNESCAPED_UNICODE);
            $qtysJson   = json_encode(array_values($qtys));
            $casJson    = json_encode(array_values($cas));
            $colorsJson = json_encode(array_values($colors));
            $chartScript = '
    var labels = ' . $labelsJson . ', qtys = ' . $qtysJson . ', cas = ' . $casJson . ', colors = ' . $colorsJson . ';
    function buildPie(canvasId, data, tooltipFn) {
        var el = document.getElementById(canvasId);
        if (!el) return;
        new Chart(el, {
            type: "pie",
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: "#fff",
                    borderWidth: 2,
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: { animateRotate: true, duration: 800 },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: tooltipFn } }
                }
            }
        });
    }

    function buildLegend(containerId, data) {
        var el = document.getElementById(containerId);
        if (!el) return;
        var total = data.reduce(function (a, b) { return a + b; }, 0);
        var html  = "<ul class=\"dtc-legend-list\">";
        labels.forEach(function (label, i) {
            var pct = total > 0 ? ((data[i] / total) * 100).toFixed(1) : "0.0";
            html += "<li class=\"dtc-legend-item\">"
                  + "<span class=\"dtc-legend-dot\" style=\"background:" + colors[i] + "\"></span>"
                  + "<span class=\"dtc-legend-label\" title=\"" + label + "\">" + label + "</span>"
                  + "<span class=\"dtc-legend-pct\">" + pct + "\u00a0%</span>"
                  + "</li>";
        });
        html += "</ul>";
        el.innerHTML = html;
    }';
        }

        return '
<script>
(function () {
    "use strict";' . $chartScript . '
    document.addEventListener("DOMContentLoaded", function () {
        var baseUrl = "' . $baseUrlEsc . '", customerGroup = "' . $customerGroupEsc . '";
        if (typeof labels !== "undefined") {
            buildPie("pieChartQty", qtys, function (ctx) {
                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                var val = new Intl.NumberFormat("fr-FR").format(ctx.parsed);
                return " " + ctx.label + ": " + val + " unités (" + pct + "%)";
            });
            buildLegend("legendQty", qtys);
            buildPie("pieChartCa", cas, function (ctx) {
                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                var val = new Intl.NumberFormat("fr-FR", { style: "currency", currency: "EUR", minimumFractionDigits: 0 }).format(ctx.parsed);
                return " " + ctx.label + ": " + val + " (" + pct + "%)";
            });
            buildLegend("legendCa", cas);
        }
        var levelSelect = document.getElementById("dtcCatLevelSelect");
        if (levelSelect) {
            levelSelect.addEventListener("change", function () {
                window.location.href = baseUrl + "&customer_group=" + encodeURIComponent(customerGroup) + "&dtc_cat_level=" + encodeURIComponent(this.value);
            });
        }
        var btn = document.getElementById("expandStatsCat");
        if (btn) {
            btn.addEventListener("click", function () {
                var sidebar = document.querySelector(".sidebar");
                var container = document.getElementById("statsContainer");
                if (sidebar) sidebar.classList.toggle("hide");
                if (container) container.classList.toggle("w-100");
                var icon = btn.querySelector("i");
                if (icon) { icon.classList.toggle("fa-expand"); icon.classList.toggle("fa-compress"); }
            });
        }
    });
}());
</script>';
    }

    // ════════════════════════════════════════════════════════════════════════════
    //  Styles CSS
    // ════════════════════════════════════════════════════════════════════════════

    private function getStyles(): string
    {
        return '
<style>
    /* ── Overrides panel PrestaShop ── */
    .sidebar.hide { display: none; }
    #statsContainer.w-100 { width: 100% !important; max-width: 100% !important; }
    #statsContainer > .panel {
        background-color: transparent !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }
    .panel-heading {
        background-color: white !important;
        margin: 0 0 15px 0 !important;
        padding: 20px 25px !important;
        border-radius: 30px !important;
        border: none !important;
    }
    .panel-body {
        background-color: white !important;
        border-radius: 30px !important;
        padding: 25px !important;
    }

    /* ── Barre de filtres ── */
    .dtc-filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: flex-end;
        background: #f8f8f8;
        border: 1px solid #E9E5E2;
        border-radius: 20px;
        padding: 16px 20px;
        margin-bottom: 24px;
    }
    .dtc-filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
        flex: 1;
        min-width: 180px;
    }
    .dtc-filter-label {
        font-size: 11px;
        font-weight: 600;
        color: #026473;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .dtc-filter-label i { margin-right: 4px; }
    .dtc-filter-select {
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 7px 12px;
        font-size: 13px;
        color: #333;
        background: #fff;
        cursor: pointer;
        outline: none;
        transition: border-color .2s;
    }
    .dtc-filter-select:focus { border-color: #026473; }
    .dtc-filter-submit { flex: 0; min-width: auto; justify-content: flex-end; }
    .dtc-btn-apply {
        background-color: #026473;
        color: #fff;
        border: none;
        border-radius: 20px;
        padding: 9px 22px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color .2s;
        white-space: nowrap;
    }
    .dtc-btn-apply:hover { background-color: #4ABDAC; }
    .dtc-btn-apply i { margin-right: 5px; }

    /* ── Badges filtres actifs ── */
    .dtc-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #026473;
        color: #fff;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 600;
    }

    /* ── Cartes graphiques ── */
    .dtc-pie-row { margin: 0 -10px; }
    .dtc-pie-col { padding: 10px; margin-bottom: 10px; }
    .dtc-pie-card {
        background: #fff;
        border: 1px solid #E9E5E2;
        border-radius: 20px;
        padding: 25px 20px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100%;
    }
    .dtc-pie-title {
        font-size: 15px;
        font-weight: 600;
        color: #026473;
        text-align: center;
        margin: 0 0 4px;
        background: transparent !important;
        border: none !important;
    }
    .dtc-pie-subtitle {
        font-size: 12px;
        color: #777;
        text-align: center;
        margin: 0 0 18px;
    }
    .dtc-pie-canvas-wrap {
        width: 100%;
        max-width: 320px;
        position: relative;
    }
    .dtc-pie-canvas-wrap canvas { width: 100% !important; }

    /* ── Légende ── */
    .dtc-legend { width: 100%; margin-top: 18px; }
    .dtc-legend-list {
        list-style: none;
        margin: 0;
        padding: 0;
        columns: 2;
        column-gap: 12px;
    }
    .dtc-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 3px 0;
        font-size: 11px;
        color: #444;
        break-inside: avoid;
    }
    .dtc-legend-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .dtc-legend-label {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px;
    }
    .dtc-legend-pct {
        font-weight: 600;
        color: #026473;
        white-space: nowrap;
    }

    @media (max-width: 992px) {
        .dtc-filter-group { min-width: 140px; }
        .dtc-legend-list  { columns: 1; }
        .dtc-pie-canvas-wrap { max-width: 260px; }
    }
    @media (max-width: 576px) {
        .dtc-filter-bar { flex-direction: column; }
        .dtc-filter-group { min-width: 100%; }
    }
</style>';
    }
}
