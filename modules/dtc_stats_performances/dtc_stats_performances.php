<?php
/**
 * Module dtc_stats_performances
 * Dashboard des performances et KPIs
 *
 * @author    DISTRICLOS
 * @copyright 2026 DISTRICLOS
 * @license   Propriétaire
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_Stats_Performances extends Module
{
    private $html = '';

    public function __construct()
    {
        $this->name = 'dtc_stats_performances';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = 'DASHBOARD - Performances';
        $this->description = 'Tableau de bord des performances et indicateurs clés (KPIs)';
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    /**
     * Installation du module
     */
    public function install()
    {
        return parent::install() 
            && $this->registerHook('displayAdminStatsModules');
    }

    /**
     * Désinstallation du module
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Redirection vers la page des statistiques
     */
    public function getContent()
    {
        Tools::redirectAdmin(
            'index.php?controller=AdminStats&module=' . $this->name . 
            '&token=' . Tools::getAdminTokenLite('AdminStats')
        );
    }

    /**
     * Hook pour afficher le contenu des statistiques
     */
    public function hookDisplayAdminStatsModules()
    {
        try {
            $date_from = $this->context->employee->stats_date_from;
            $date_to = $this->context->employee->stats_date_to;
            
            // Récupération de la devise par défaut
            $id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
            $currency = new Currency($id_currency);
            
            // Vérification que la devise est bien chargée
            if (!Validate::isLoadedObject($currency)) {
                throw new Exception('Impossible de charger la devise par defaut');
            }
            
            // Calcul de la semaine actuelle
            $currentWeekNumber = date('W');
            $currentYear = date('Y');

            // Panel heading enrichi
            $this->html = '
        <div class="panel-heading">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                <!-- Colonne gauche : Bouton expand -->
                <div style="flex-shrink: 0;">
                    <button type="button" class="btn btn-primary" id="expandStatsPerf" style="border-radius: 50%; width: 35px; height: 35px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                
                <!-- Colonne centrale : Titre -->
                <div style="flex-grow: 1; text-align: center;">
                    <h3 style="margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; border-bottom: none !important; background-color: transparent !important; font-weight: 400;">' . $this->displayName . '</h3>
                </div>
                
                <!-- Colonne droite : Semaine -->
                <div style="flex-shrink: 0; text-align: right;">
                    <div style="font-size: 14px;">
                        <span style="color: #000;">Semaine actuelle :</span> 
                        <span style="color: #026473; font-weight: 400;">S' . str_pad($currentWeekNumber, 2, "0", STR_PAD_LEFT) . '</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel-body">
        
        <!-- Récupération des KPIs -->';
        // Récupération des KPIs
        $kpis = $this->getKPIs($date_from, $date_to);
        
        // Récupération des statistiques pour les jauges
        $gaugeStats = $this->getGaugeStats();

        // **Section WEB - Global**
        $this->html .= '
        <div>
            <!-- Header avec titre et boutons -->
            <div style="background-color: #E9E5E2; border-radius: 30px; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 1;"></div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #333; text-align: center; flex: 1; background-color: transparent !important;">WEB - Global</h3>
                <div style="flex: 1; display: flex; gap: 10px; justify-content: flex-end;">
                    <button style="background-color: #4ABDAC; color: white; border: none; border-radius: 20px; padding: 8px 20px; font-size: 14px; cursor: pointer;">
                        En attentes
                    </button>
                    <button style="background-color: #026473; color: white; border: none; border-radius: 20px; padding: 8px 20px; font-size: 14px; cursor: pointer;">
                        Validés
                    </button>
                </div>
            </div>
        </div>';

        // Affichage des graphiques circulaires de progression avec animation liquide
        $this->html .= '
        <div class="row" style="margin-bottom: 30px;">
            <div class="col-lg-3 col-md-6">
                <div class="liquid-progress-container">
                    <p style="text-align: center; margin-bottom: 5px; font-size: 12px; color: black; font-weight: 400;">Prise de commande</p>
                    <h4 class="text-center" style="margin-top: 0; margin-bottom: 20px; font-weight: bold; color: black; font-size: 20px;">Journée</h4>
                    <div class="liquid-gauge" id="gauge-day" data-validated="' . $gaugeStats['day']['validated'] . '" data-pending="' . $gaugeStats['day']['pending'] . '" data-objective="' . $gaugeStats['day']['objective'] . '"></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="liquid-progress-container">
                    <p style="text-align: center; margin-bottom: 5px; font-size: 12px; color: black; font-weight: 400;">Prise de commande</p>
                    <h4 class="text-center" style="margin-top: 0; margin-bottom: 20px; font-weight: bold; color: black; font-size: 20px;">Semaine</h4>
                    <div class="liquid-gauge" id="gauge-week" data-validated="' . $gaugeStats['week']['validated'] . '" data-pending="' . $gaugeStats['week']['pending'] . '" data-objective="' . $gaugeStats['week']['objective'] . '"></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="liquid-progress-container">
                    <p style="text-align: center; margin-bottom: 5px; font-size: 12px; color: black; font-weight: 400;">Prise de commande</p>
                    <h4 class="text-center" style="margin-top: 0; margin-bottom: 20px; font-weight: bold; color: black; font-size: 20px;">Mois</h4>
                    <div class="liquid-gauge" id="gauge-month" data-validated="' . $gaugeStats['month']['validated'] . '" data-pending="' . $gaugeStats['month']['pending'] . '" data-objective="' . $gaugeStats['month']['objective'] . '"></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="liquid-progress-container">
                    <p style="text-align: center; margin-bottom: 5px; font-size: 12px; color: black; font-weight: 400;">Prise de commande</p>
                    <h4 class="text-center" style="margin-top: 0; margin-bottom: 20px; font-weight: bold; color: black; font-size: 20px;">Année</h4>
                    <div class="liquid-gauge" id="gauge-year" data-validated="' . $gaugeStats['year']['validated'] . '" data-pending="' . $gaugeStats['year']['pending'] . '" data-objective="' . $gaugeStats['year']['objective'] . '"></div>
                </div>
            </div>
        </div>';

        // **Graphique d'évolution du CA annuel par mois**
        $this->html .= '<div style="margin-top: 40px;">
            <canvas id="chartdiv"></canvas>
        </div>';

        // Préparer les données pour le graphique annuel (par mois)
        $currentYear = date('Y');
        $currentMonth = date('n'); // Mois actuel (1-12)
        
        // Noms des mois en français
        $monthNames = [
            'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
        ];
        
        $allMonths = array();
        $graph = array();        // CA année actuelle
        $graphLastYear = array(); // CA année N-1
        $graphObjectives = array(); // Objectifs
        
        $lastYear = $currentYear - 1;
        
        // **Charger les données N-1 depuis la base de données SQL**
        $monthlyLastYearCA = array_fill(1, 12, 0); // Initialiser le CA N-1 pour chaque mois
        
        // Calculer le CA N-1 pour chaque mois via SQL
        for ($month = 1; $month <= 12; $month++) {
            $firstDayOfMonthLastYear = $lastYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
            $lastDayOfMonthLastYear = date('Y-m-t', strtotime($firstDayOfMonthLastYear));
            
            $sql_n1 = 'SELECT ROUND(SUM(o.`total_paid`), 0) AS ca_n1
                       FROM `' . _DB_PREFIX_ . 'orders` as o
                       WHERE o.`validated_at` BETWEEN "' . pSQL($firstDayOfMonthLastYear) . ' 00:00:00" 
                         AND "' . pSQL($lastDayOfMonthLastYear) . ' 23:59:59"
                         AND o.`validated_at` IS NOT NULL';
            
            $result_n1 = Db::getInstance()->executeS($sql_n1);
            
            if ($result_n1 && isset($result_n1[0]['ca_n1'])) {
                $monthlyLastYearCA[$month] = (float)$result_n1[0]['ca_n1'];
            }
        }
        
        // **Charger les objectifs depuis le fichier Excel**
        $monthlyObjectives = array_fill(1, 12, 0); // Initialiser les objectifs pour chaque mois
        
        $excelFile = _PS_MODULE_DIR_ . 'dtc_stats_stores/data/objectives/' . $currentYear . '.xlsx';
        
        if (file_exists($excelFile)) {
            try {
                // Charger le fichier Excel avec PhpSpreadsheet
                require_once _PS_MODULE_DIR_ . 'statsforecast/vendor/autoload.php';
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFile);
                $sheet = $spreadsheet->getActiveSheet();
                
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                
                // Parcourir toutes les lignes du fichier Excel
                for ($row = 2; $row <= $highestRow; $row++) {
                    $dateValue = $sheet->getCellByColumnAndRow(1, $row)->getValue();
                    
                    // Convertir la date si nécessaire
                    if (is_numeric($dateValue)) {
                        $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                        $date = $dateObj->format('Y-m-d');
                    } else {
                        $date = $dateValue;
                    }
                    
                    // Extraire le mois de la date
                    $month = (int)date('n', strtotime($date));
                    
                    // Sommer tous les objectifs des magasins (colonnes 2 à fin) pour ce jour
                    $dailyObjectiveTotal = 0;
                    for ($col = 2; $col <= $highestColumnIndex; $col++) {
                        $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                        if (is_numeric($value)) {
                            $dailyObjectiveTotal += floatval($value);
                        }
                    }
                    
                    // Ajouter au total du mois
                    $monthlyObjectives[$month] += $dailyObjectiveTotal;
                }
            } catch (Exception $e) {
                // En cas d'erreur, on garde les objectifs à 0
            }
        }
        
        // Récupérer le CA pour chaque mois de l'année en cours ET N-1
        for ($month = 1; $month <= 12; $month++) {
            $allMonths[] = $monthNames[$month - 1];
            
            // === CA ANNÉE ACTUELLE ===
            // Si le mois est dans le futur, on met 0
            if ($month > $currentMonth) {
                $graph[] = 0;
            } else {
                $firstDayOfMonth = date('Y-m-01', strtotime($currentYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01'));
                $lastDayOfMonth = date('Y-m-t', strtotime($currentYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01'));
                
                $sql_graph = 'SELECT ROUND(SUM(o.`total_paid`), 0) AS CAMOIS 
                              FROM `' . _DB_PREFIX_ . 'orders` as o
                              WHERE o.`validated_at` BETWEEN "' . pSQL($firstDayOfMonth) . ' 00:00:00" AND "' . pSQL($lastDayOfMonth) . ' 23:59:59"
                                AND o.`validated_at` IS NOT NULL';
                
                $resultGraph = Db::getInstance()->executeS($sql_graph);
                
                if ($resultGraph && isset($resultGraph[0]['CAMOIS'])) {
                    $graph[] = (int)$resultGraph[0]['CAMOIS'];
                } else {
                    $graph[] = 0;
                }
            }
            
            // === CA ANNÉE N-1 depuis les fichiers CSV ===
            $graphLastYear[] = round($monthlyLastYearCA[$month]);
            
            // === OBJECTIFS depuis le fichier Excel ===
            $graphObjectives[] = round($monthlyObjectives[$month]);
        }

        // Ajouter Chart.js
        $this->context->controller->addJS('https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js');

        // Script pour initialiser le graphique
        $this->html .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById("chartdiv");
                const labels = ["' . implode('","', $allMonths) . '"];
                const data = {
                    labels: labels,
                    datasets: [
                        {
                            axis: "y",
                            label: "Co",
                            data: [' . implode(',', $graph) . '],
                            fill: false,
                            borderWidth: 3,
                            borderColor: "#F8859B",
                            backgroundColor: "rgba(248, 133, 155, 0.2)",
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: "#F8859B",
                            pointBorderColor: "#F8859B"
                        },
                        {
                            axis: "y",
                            label: "Co N-1",
                            data: [' . implode(',', $graphLastYear) . '],
                            fill: false,
                            borderWidth: 3,
                            borderColor: "#B4C7DD",
                            backgroundColor: "rgba(180, 199, 221, 0.2)",
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: "#B4C7DD",
                            pointBorderColor: "#B4C7DD"
                        },
                        {
                            axis: "y",
                            label: "Objectif",
                            data: [' . implode(',', $graphObjectives) . '],
                            fill: false,
                            borderWidth: 3,
                            borderColor: "#4ABDAC",
                            backgroundColor: "rgba(74, 189, 172, 0.2)",
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: "#4ABDAC",
                            pointBorderColor: "#4ABDAC"
                        }
                    ]
                };

                const myChart = new Chart(ctx, {
                    type: "line",
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: "top"
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString() + " €";
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>';

        // **Section WEB - Service Client avec jauges liquides**
        // Recuperer les stats specifiques au Service Client (commandes liees a devis suivis par tele-operateurs)
        $serviceClientGaugeStats = $this->getServiceClientGaugeStats();
        
        $this->html .= '
        <div style="margin-top: 50px;">
            <!-- Header avec titre et boutons -->
            <div style="background-color: #E9E5E2; border-radius: 30px; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 1;"></div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #333; text-align: center; flex: 1; background-color: transparent !important;">WEB - Service Client</h3>
                <div style="flex: 1; display: flex; gap: 10px; justify-content: flex-end;">
                    <button style="background-color: #4ABDAC; color: white; border: none; border-radius: 20px; padding: 8px 20px; font-size: 14px; cursor: pointer;">
                        En attentes
                    </button>
                    <button style="background-color: #026473; color: white; border: none; border-radius: 20px; padding: 8px 20px; font-size: 14px; cursor: pointer;">
                        Valides
                    </button>
                </div>
            </div>
            
            <!-- Deux jauges liquides -->
            <div class="row" style="margin-bottom: 30px;">
                <div class="col-lg-6 col-md-6">
                    <div class="liquid-progress-container">
                        <p style="text-align: center; margin-bottom: 5px; font-size: 12px; color: black; font-weight: 400;">Prise de commande</p>
                        <h4 class="text-center" style="margin-top: 0; margin-bottom: 20px; font-weight: bold; color: black; font-size: 20px;">Mois</h4>
                        <div class="liquid-gauge" id="gauge-service-month" data-validated="' . $serviceClientGaugeStats['month']['validated'] . '" data-pending="' . $serviceClientGaugeStats['month']['pending'] . '" data-objective="' . $serviceClientGaugeStats['month']['objective'] . '"></div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="liquid-progress-container">
                        <p style="text-align: center; margin-bottom: 5px; font-size: 12px; color: black; font-weight: 400;">Prise de commande</p>
                        <h4 class="text-center" style="margin-top: 0; margin-bottom: 20px; font-weight: bold; color: black; font-size: 20px;">Annee</h4>
                        <div class="liquid-gauge" id="gauge-service-year" data-validated="' . $serviceClientGaugeStats['year']['validated'] . '" data-pending="' . $serviceClientGaugeStats['year']['pending'] . '" data-objective="' . $serviceClientGaugeStats['year']['objective'] . '"></div>
                    </div>
                </div>
            </div>
        </div>';
        // Fermeture du panel-body
        $this->html .= '
        </div>';
        
        // JavaScript pour le bouton expand et animation liquide
        $this->html .= '
        <script>
            // Bouton expand/collapse
            $("#expandStatsPerf").click(function() {
                $(".sidebar").toggleClass("hide");
                $("#statsContainer").toggleClass("w-100");
                
                // Changer icone
                var icon = $(this).find("i");
                if (icon.hasClass("fa-expand")) {
                    icon.removeClass("fa-expand").addClass("fa-compress");
                } else {
                    icon.removeClass("fa-compress").addClass("fa-expand");
                }
            });
            
            // Anneaux de progression avec remplissage liquide anime
            function createLiquidGauge(elementId, validated, pending, objective) {
                var container = document.getElementById(elementId);
                if (!container) return;
                
                // Configuration
                var width = 200;
                var height = 200;
                var centerX = width / 2;
                var centerY = height / 2;
                var outerRadius = 75;
                var innerRadius = 55;
                var strokeWidth = 18;
                var circumference = 2 * Math.PI * outerRadius;
                
                // Calculer les segments dynamiquement (en pourcentage du cercle)
                var total = validated + pending;
                var totalPercent = objective > 0 ? (total / objective) * 100 : 0;
                var validatedPercent = objective > 0 ? (validated / objective) * 100 : 0;
                var pendingPercent = objective > 0 ? (pending / objective) * 100 : 0;
                var remainingPercent = Math.max(0, 100 - totalPercent);
                
                // Pourcentage affiche : uniquement validated (pas validated + pending)
                var displayPercent = objective > 0 ? (validated / objective) * 100 : 0;
                
                var segments = {
                    validated: Math.min(validatedPercent, 100),
                    pending: Math.min(pendingPercent, 100 - validatedPercent),
                    remaining: remainingPercent
                };
                
                // Creer le SVG
                var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                svg.setAttribute("viewBox", "0 0 " + width + " " + height);
                svg.style.width = "100%";
                svg.style.height = "100%";
                
                // Defs pour gradients et masques
                var defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
                
                // Masque circulaire pour le liquide
                var clipPath = document.createElementNS("http://www.w3.org/2000/svg", "clipPath");
                clipPath.setAttribute("id", "clip-" + elementId);
                var clipCircle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                clipCircle.setAttribute("cx", centerX);
                clipCircle.setAttribute("cy", centerY);
                clipCircle.setAttribute("r", innerRadius);
                clipPath.appendChild(clipCircle);
                defs.appendChild(clipPath);
                
                // Gradient fixe avec 3 stops de couleur
                var gradient = document.createElementNS("http://www.w3.org/2000/svg", "linearGradient");
                gradient.setAttribute("id", "grad-" + elementId);
                gradient.setAttribute("x1", "0%");
                gradient.setAttribute("y1", "0%");
                gradient.setAttribute("x2", "0%");
                gradient.setAttribute("y2", "100%");
                
                // Stop 1: Vert clair (haut - 100% de remplissage)
                var stop1 = document.createElementNS("http://www.w3.org/2000/svg", "stop");
                stop1.setAttribute("offset", "0%");
                stop1.setAttribute("style", "stop-color:#76F383;stop-opacity:1");
                gradient.appendChild(stop1);
                
                // Stop 2: Orange/Peche (milieu - 50% de remplissage)
                var stop2 = document.createElementNS("http://www.w3.org/2000/svg", "stop");
                stop2.setAttribute("offset", "50%");
                stop2.setAttribute("style", "stop-color:#F3B58F;stop-opacity:1");
                gradient.appendChild(stop2);
                
                // Stop 3: Rose/Rouge (bas - 0% de remplissage)
                var stop3 = document.createElementNS("http://www.w3.org/2000/svg", "stop");
                stop3.setAttribute("offset", "100%");
                stop3.setAttribute("style", "stop-color:#F72D6D;stop-opacity:1");
                gradient.appendChild(stop3);
                
                defs.appendChild(gradient);
                svg.appendChild(defs);
                
                // Fonction pour creer un segment d anneau
                function createSegment(startPercent, lengthPercent, color) {
                    var circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                    circle.setAttribute("cx", centerX);
                    circle.setAttribute("cy", centerY);
                    circle.setAttribute("r", outerRadius);
                    circle.setAttribute("fill", "none");
                    circle.setAttribute("stroke", color);
                    circle.setAttribute("stroke-width", strokeWidth);
                    circle.setAttribute("stroke-linecap", "butt");
                    
                    var dashLength = (circumference * lengthPercent) / 100;
                    var dashGap = circumference - dashLength;
                    circle.setAttribute("stroke-dasharray", dashLength + " " + dashGap);
                    
                    var rotateAngle = -90 + (360 * startPercent) / 100;
                    circle.setAttribute("transform", "rotate(" + rotateAngle + " " + centerX + " " + centerY + ")");
                    
                    return circle;
                }
                
                // Creer les 3 segments dans l ordre (validated, pending, remaining)
                var currentPosition = 0;
                
                // Segment 1: Commandes validees (bleu fonce)
                if (segments.validated > 0) {
                    svg.appendChild(createSegment(currentPosition, segments.validated, "#026473"));
                    currentPosition += segments.validated;
                }
                
                // Segment 2: Commandes en attente (vert clair)
                if (segments.pending > 0) {
                    svg.appendChild(createSegment(currentPosition, segments.pending, "#4ABDAC"));
                    currentPosition += segments.pending;
                }
                
                // Segment 3: Objectif restant (beige)
                if (segments.remaining > 0) {
                    svg.appendChild(createSegment(currentPosition, segments.remaining, "#E9E5E2"));
                }
                
                // Groupe pour le liquide avec masque circulaire
                var liquidGroup = document.createElementNS("http://www.w3.org/2000/svg", "g");
                liquidGroup.setAttribute("clip-path", "url(#clip-" + elementId + ")");
                
                // Rectangle de remplissage avec gradient
                var liquidBg = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                liquidBg.setAttribute("x", 0);
                liquidBg.setAttribute("y", 0);
                liquidBg.setAttribute("width", width);
                liquidBg.setAttribute("height", height);
                liquidBg.setAttribute("fill", "url(#grad-" + elementId + ")");
                liquidGroup.appendChild(liquidBg);
                
                svg.appendChild(liquidGroup);
                
                // Texte au centre
                var text = document.createElementNS("http://www.w3.org/2000/svg", "text");
                text.setAttribute("x", centerX);
                text.setAttribute("y", centerY + 8);
                text.setAttribute("text-anchor", "middle");
                text.setAttribute("font-size", "36");
                text.setAttribute("font-weight", "bold");
                text.setAttribute("fill", "#026473");
                text.textContent = "00 %";
                svg.appendChild(text);
                
                container.appendChild(svg);
                
                // Clipper le gradient selon le pourcentage validated uniquement (pas total)
                var displayPercentFinal = Math.min(displayPercent, 100);
                var fillHeight = (displayPercentFinal / 100) * (innerRadius * 2);
                var fillY = centerY + innerRadius - fillHeight;
                
                // Creer un clipPath pour limiter la hauteur visible
                var fillClip = document.createElementNS("http://www.w3.org/2000/svg", "clipPath");
                fillClip.setAttribute("id", "fillClip-" + elementId);
                var fillRect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                fillRect.setAttribute("x", 0);
                fillRect.setAttribute("y", fillY);
                fillRect.setAttribute("width", width);
                fillRect.setAttribute("height", fillHeight);
                fillClip.appendChild(fillRect);
                defs.appendChild(fillClip);
                
                // Appliquer le double clipping (cercle + hauteur)
                var doubleClip = document.createElementNS("http://www.w3.org/2000/svg", "clipPath");
                doubleClip.setAttribute("id", "doubleClip-" + elementId);
                var clipUse1 = document.createElementNS("http://www.w3.org/2000/svg", "use");
                clipUse1.setAttributeNS("http://www.w3.org/1999/xlink", "xlink:href", "#clip-" + elementId);
                doubleClip.appendChild(clipUse1);
                defs.appendChild(doubleClip);
                
                liquidGroup.setAttribute("clip-path", "url(#clip-" + elementId + ")");
                liquidBg.setAttribute("clip-path", "url(#fillClip-" + elementId + ")");
                
                // Afficher le pourcentage validated uniquement
                var displayValue = Math.round(displayPercentFinal);
                var valueStr = displayValue < 10 ? "0" + displayValue : String(displayValue);
                text.textContent = valueStr + " %";
            }
            
            // Initialiser les jauges après le chargement
            $(document).ready(function() {
                var gauges = document.querySelectorAll(".liquid-gauge");
                gauges.forEach(function(gauge) {
                    var validated = parseFloat(gauge.getAttribute("data-validated"));
                    var pending = parseFloat(gauge.getAttribute("data-pending"));
                    var objective = parseFloat(gauge.getAttribute("data-objective"));
                    createLiquidGauge(gauge.id, validated, pending, objective);
                });
            });
        </script>
        
        <style>
            .sidebar.hide {
                display: none;
            }
            #statsContainer.w-100 {
                width: 100% !important;
                max-width: 100% !important;
            }

            #statsContainer > .panel {
                background-color: transparent !important;
                padding: 0 !important;
                border: none !important;
                -webkit-box-shadow: none !important;
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
                padding: 20px !important;
            }
            
            #expandStatsPerf {
                border: 2px solid #026473 !important;
                background-color: transparent !important;
            }
            
            #expandStatsPerf i {
                font-size: 12px;
                color: #026473 !important;
            }
            
            /* Graphiques circulaires de progression liquide */
            .liquid-progress-container {
                text-align: center;
                padding: 20px;
            }
            
            .liquid-progress-container h4 {
                font-size: 14px;
                font-weight: 400;
                color: #026473;
            }
            
            .liquid-gauge {
                width: 200px;
                height: 200px;
                margin: 0 auto;
            }
            
            @media (max-width: 768px) {
                .liquid-gauge {
                    width: 150px;
                    height: 150px;
                }
            }
        </style>';

            return $this->html;
            
        } catch (Exception $e) {
            return '<div class="alert alert-danger">Erreur dans le module DASHBOARD - Performances : ' . $e->getMessage() . '</div>';
        }
    }
    
    /**
     * Récupère les KPIs
     */
    /**
     * Récupère les KPIs principaux
     *
     * @param string $date_from Date de début
     * @param string $date_to Date de fin
     * @return array KPIs
     */
    private function getKPIs($date_from, $date_to)
    {
        // Nombre de commandes et CA
        $sql_orders = 'SELECT 
                        COUNT(DISTINCT o.id_order) as total_orders,
                        IFNULL(SUM(o.total_paid_tax_excl), 0) as total_sales
                    FROM ' . _DB_PREFIX_ . 'orders o
                    WHERE o.date_add BETWEEN "' . pSQL($date_from) . ' 00:00:00" 
                        AND "' . pSQL($date_to) . ' 23:59:59"
                        AND o.valid = 1
                    ' . Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql_orders);

        // Panier moyen
        $average_cart = $result['total_orders'] > 0 
            ? $result['total_sales'] / $result['total_orders'] 
            : 0;

        // Taux de conversion (exemple basé sur des visites - à adapter selon votre besoin)
        $conversion_rate = 0; // À implémenter selon vos besoins
        
        return [
            'total_orders' => (int)$result['total_orders'],
            'total_sales' => (float)$result['total_sales'],
            'average_cart' => $average_cart,
            'conversion_rate' => $conversion_rate,
        ];
    }
    
    /**
     * Charge les objectifs depuis le fichier Excel (avec cache)
     */
    private function loadObjectivesFromExcel()
    {
        $cacheKey = 'dtc_stats_objectives_' . date('Y-m-d');
        
        // Essayer de recuperer depuis le cache
        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }
        
        $objectives = ['day' => 0, 'week' => 0, 'month' => 0, 'year' => 0];
        $currentYear = date('Y');
        $objectivesFile = _PS_MODULE_DIR_ . 'dtc_stats_stores/data/objectives/' . $currentYear . '.xlsx';
        
        if (!file_exists($objectivesFile)) {
            Cache::store($cacheKey, $objectives, 3600); // Cache 1h meme si vide
            return $objectives;
        }
        
        try {
            require_once _PS_MODULE_DIR_ . 'statsforecast/vendor/autoload.php';
            $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($objectivesFile);
            $sheet = $spreadsheet->getActiveSheet();
            
            $today = date('Y-m-d');
            $currentMonthStr = date('Y-m');
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd = date('Y-m-d', strtotime('sunday this week'));
            
            // Obtenir le nombre de colonnes et lignes
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            $highestRow = $sheet->getHighestRow();
            
            // Parcourir chaque ligne (a partir de la ligne 2, ligne 1 = headers)
            for ($row = 2; $row <= $highestRow; $row++) {
                $dateValue = $sheet->getCellByColumnAndRow(1, $row)->getValue();
                
                // Convertir la date si necessaire
                if (is_numeric($dateValue)) {
                    $dateObj = PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                    $date = $dateObj->format('Y-m-d');
                } else {
                    $date = $dateValue;
                }
                
                // Additionner tous les magasins pour cette ligne (colonnes 2+)
                $dailyTotal = 0;
                for ($col = 2; $col <= $highestColumnIndex; $col++) {
                    $dailyTotal += (float)$sheet->getCellByColumnAndRow($col, $row)->getValue();
                }
                
                // Objectif annee (toutes les dates)
                $objectives['year'] += $dailyTotal;
                
                // Objectif mois (mois en cours uniquement)
                if (substr($date, 0, 7) == $currentMonthStr) {
                    $objectives['month'] += $dailyTotal;
                }
                
                // Objectif semaine (semaine en cours)
                if ($date >= $weekStart && $date <= $weekEnd) {
                    $objectives['week'] += $dailyTotal;
                }
                
                // Objectif jour (aujourd hui)
                if ($date == $today) {
                    $objectives['day'] += $dailyTotal;
                }
            }
            
            // Mettre en cache pour 24h
            Cache::store($cacheKey, $objectives, 86400);
            
        } catch (Exception $e) {
            // En cas d erreur, mettre en cache pour 1h seulement
            Cache::store($cacheKey, $objectives, 3600);
        }
        
        return $objectives;
    }
    
    /**
     * Recupere les statistiques pour les jauges (validees, en attente, objectif)
     */
    private function getGaugeStats()
    {
        $cacheKey = 'dtc_stats_gauge_data_' . date('Y-m-d-H');
        
        // Essayer de recuperer depuis le cache (1h)
        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }
        
        // Dates pour chaque periode
        $today = date('Y-m-d');
        $todayStart = $today . ' 00:00:00';
        $todayEnd = $today . ' 23:59:59';
        
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        $weekStartDateTime = $weekStart . ' 00:00:00';
        $weekEndDateTime = $weekEnd . ' 23:59:59';
        
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $monthStartDateTime = $monthStart . ' 00:00:00';
        $monthEndDateTime = $monthEnd . ' 23:59:59';
        
        $yearStart = date('Y-01-01');
        $yearEnd = date('Y-12-31');
        $yearStartDateTime = $yearStart . ' 00:00:00';
        $yearEndDateTime = $yearEnd . ' 23:59:59';
        
        // Requete pour recuperer CA valide et en attente pour toutes les periodes
        $sql = 'SELECT 
                ROUND(SUM(CASE WHEN o.validated_at BETWEEN "' . pSQL($todayStart) . '" AND "' . pSQL($todayEnd) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_jour_valide,
                ROUND(SUM(CASE WHEN o.validated_at IS NULL AND o.date_add BETWEEN "' . pSQL($todayStart) . '" AND "' . pSQL($todayEnd) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_jour_attente,
                
                ROUND(SUM(CASE WHEN o.validated_at BETWEEN "' . pSQL($weekStartDateTime) . '" AND "' . pSQL($weekEndDateTime) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_semaine_valide,
                ROUND(SUM(CASE WHEN o.validated_at IS NULL AND o.date_add BETWEEN "' . pSQL($weekStartDateTime) . '" AND "' . pSQL($weekEndDateTime) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_semaine_attente,
                
                ROUND(SUM(CASE WHEN o.validated_at BETWEEN "' . pSQL($monthStartDateTime) . '" AND "' . pSQL($monthEndDateTime) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_mois_valide,
                ROUND(SUM(CASE WHEN o.validated_at IS NULL AND o.date_add BETWEEN "' . pSQL($monthStartDateTime) . '" AND "' . pSQL($monthEndDateTime) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_mois_attente,
                
                ROUND(SUM(CASE WHEN o.validated_at BETWEEN "' . pSQL($yearStartDateTime) . '" AND "' . pSQL($yearEndDateTime) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_annee_valide,
                ROUND(SUM(CASE WHEN o.validated_at IS NULL AND o.date_add BETWEEN "' . pSQL($yearStartDateTime) . '" AND "' . pSQL($yearEndDateTime) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_annee_attente
            FROM ' . _DB_PREFIX_ . 'orders AS o
            WHERE o.id_store IS NOT NULL';
        
        $result = Db::getInstance()->getRow($sql);
        
        // Charger les objectifs depuis le cache/Excel
        $objectives = $this->loadObjectivesFromExcel();
        
        $stats = [
            'day' => [
                'validated' => (float)$result['ca_jour_valide'],
                'pending' => (float)$result['ca_jour_attente'],
                'objective' => $objectives['day']
            ],
            'week' => [
                'validated' => (float)$result['ca_semaine_valide'],
                'pending' => (float)$result['ca_semaine_attente'],
                'objective' => $objectives['week']
            ],
            'month' => [
                'validated' => (float)$result['ca_mois_valide'],
                'pending' => (float)$result['ca_mois_attente'],
                'objective' => $objectives['month']
            ],
            'year' => [
                'validated' => (float)$result['ca_annee_valide'],
                'pending' => (float)$result['ca_annee_attente'],
                'objective' => $objectives['year']
            ]
        ];
        
        // Mettre en cache pour 1h
        Cache::store($cacheKey, $stats, 3600);
        
        return $stats;
    }
    
    /**
     * Charge les objectifs du Service Client depuis le fichier Excel (avec cache)
     * Fichier : modules/dtc_stats_performances/data/objectives/service_client_{YEAR}.xlsx
     * Structure : Mois | Objectif Mois (12 mois + 1 année)
     * 
     * @return array Objectifs par periode (day=0, week=0, month, year)
     */
    private function loadServiceClientObjectivesFromExcel()
    {
        $cacheKey = 'dtc_stats_service_client_objectives_' . date('Y-m');
        
        // Essayer de recuperer depuis le cache
        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }
        
        $objectives = ['day' => 0, 'week' => 0, 'month' => 0, 'year' => 0];
        $currentYear = date('Y');
        
        // Chemin vers le fichier Excel du Service Client
        $objectivesFile = _PS_MODULE_DIR_ . 'dtc_stats_performances/data/objectives/service_client_' . $currentYear . '.xlsx';
        
        if (!file_exists($objectivesFile)) {
            Cache::store($cacheKey, $objectives, 3600); // Cache 1h meme si vide
            return $objectives;
        }
        
        try {
            require_once _PS_MODULE_DIR_ . 'statsforecast/vendor/autoload.php';
            $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($objectivesFile);
            $sheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $sheet->getHighestRow();
            
            // Noms des mois en français (ordre dans le fichier Excel)
            $months = [
                'Janvier' => 1,
                'Fevrier' => 2,
                'Février' => 2,
                'Mars' => 3,
                'Avril' => 4,
                'Mai' => 5,
                'Juin' => 6,
                'Juillet' => 7,
                'Aout' => 8,
                'Août' => 8,
                'Septembre' => 9,
                'Octobre' => 10,
                'Novembre' => 11,
                'Decembre' => 12,
                'Décembre' => 12,
            ];
            
            $currentMonth = (int)date('n'); // Mois actuel (1-12)
            
            // Parcourir chaque ligne (a partir de la ligne 2, ligne 1 = headers)
            for ($row = 2; $row <= $highestRow; $row++) {
                $monthValue = $sheet->getCellByColumnAndRow(1, $row)->getValue();
                $objectiveValue = (float)$sheet->getCellByColumnAndRow(2, $row)->getValue();
                
                // Verifier si c est l objectif annuel (contient "Année" ou "Annee")
                if (stripos($monthValue, 'Annee') !== false || stripos($monthValue, 'Année') !== false || stripos($monthValue, $currentYear) !== false) {
                    $objectives['year'] = $objectiveValue;
                    continue;
                }
                
                // Chercher le mois correspondant
                foreach ($months as $monthName => $monthNum) {
                    if (stripos($monthValue, $monthName) !== false) {
                        // Si c est le mois actuel, recuperer l objectif
                        if ($monthNum == $currentMonth) {
                            $objectives['month'] = $objectiveValue;
                        }
                        break;
                    }
                }
            }
            
            // Mettre en cache pour 24h
            Cache::store($cacheKey, $objectives, 86400);
            
        } catch (Exception $e) {
            // En cas d erreur, mettre en cache pour 1h seulement
            Cache::store($cacheKey, $objectives, 3600);
        }
        
        return $objectives;
    }
    
    /**
     * Recupere les statistiques pour les jauges du Service Client (Mois et Annee uniquement)
     * Les commandes Service Client sont celles liees a un devis suivi par un tele-operateur
     * (comme dans statsforecast: Amelie, Vincent, Nelly, Robin, Leo, Thibaut)
     * 
     * @return array Stats par periode (day=0, week=0, month, year)
     */
    private function getServiceClientGaugeStats()
    {
        $cacheKey = 'dtc_stats_service_client_gauge_data_' . date('Y-m');
        
        // Essayer de recuperer depuis le cache (1h)
        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }
        
        // Liste des noms des tele-operateurs Service Client (comme dans statsforecast)
        $scNames = ["Amelie", "Vincent", "Nelly", "Robin", "Leo", "Thibaut"];
        
        // Construire la condition WHERE pour filtrer les devis Service Client
        $scConditions = array();
        foreach ($scNames as $scName) {
            $escapedName = pSQL($scName);
            $scConditions[] = 'P.name LIKE "%' . $escapedName . '%"';
        }
        $scWhereCondition = '(' . implode(' OR ', $scConditions) . ')';
        
        // Dates pour mois et annee uniquement
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $monthStartDateTime = $monthStart . ' 00:00:00';
        $monthEndDateTime = $monthEnd . ' 23:59:59';
        
        $yearStart = date('Y-01-01');
        $yearEnd = date('Y-12-31');
        $yearStartDateTime = $yearStart . ' 00:00:00';
        $yearEndDateTime = $yearEnd . ' 23:59:59';
        
        // Requete pour recuperer CA valide et en attente pour mois et annee uniquement
        // Les commandes Service Client sont celles liees a un devis suivi par un tele-operateur
        $sql = 'SELECT 
                ROUND(SUM(CASE WHEN o.validated_at BETWEEN "' . pSQL($monthStartDateTime) . '" AND "' . pSQL($monthEndDateTime) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_mois_valide,
                ROUND(SUM(CASE WHEN o.validated_at IS NULL AND o.date_add BETWEEN "' . pSQL($monthStartDateTime) . '" AND "' . pSQL($monthEndDateTime) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_mois_attente,
                
                ROUND(SUM(CASE WHEN o.validated_at BETWEEN "' . pSQL($yearStartDateTime) . '" AND "' . pSQL($yearEndDateTime) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_annee_valide,
                ROUND(SUM(CASE WHEN o.validated_at IS NULL AND o.date_add BETWEEN "' . pSQL($yearStartDateTime) . '" AND "' . pSQL($yearEndDateTime) . '" THEN o.total_paid ELSE 0 END), 0) AS ca_annee_attente
            FROM ' . _DB_PREFIX_ . 'orders AS o
            INNER JOIN ' . _DB_PREFIX_ . 'opartdevis AS P ON o.id_order = P.id_order
            WHERE ' . $scWhereCondition;
        
        $result = Db::getInstance()->getRow($sql);
        
        // Charger les objectifs specifiques au Service Client
        $objectives = $this->loadServiceClientObjectivesFromExcel();
        
        $stats = [
            'day' => [
                'validated' => 0,
                'pending' => 0,
                'objective' => 0
            ],
            'week' => [
                'validated' => 0,
                'pending' => 0,
                'objective' => 0
            ],
            'month' => [
                'validated' => (float)$result['ca_mois_valide'],
                'pending' => (float)$result['ca_mois_attente'],
                'objective' => $objectives['month']
            ],
            'year' => [
                'validated' => (float)$result['ca_annee_valide'],
                'pending' => (float)$result['ca_annee_attente'],
                'objective' => $objectives['year']
            ]
        ];
        
        // Mettre en cache pour 1h
        Cache::store($cacheKey, $stats, 3600);
        
        return $stats;
    }
    
}
