<?php
/**
 * Module dtc_stats_stores
 * Dashboard statistiques pour les magasins
 *
 * @author    DISTRICLOS
 * @copyright 2026 DISTRICLOS
 * @license   Propriétaire
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_Stats_Stores extends Module
{
    private $html = '';

    public function __construct()
    {
        $this->name = 'dtc_stats_stores';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = 'DASHBOARD - Magasins';
        $this->description = 'Tableau de bord des statistiques des magasins';
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
        // Récupération du filtre type de client
        $customer_group = Tools::getValue('customer_group', 'tous');
        
        // Calcul de la semaine et année actuelle
        $currentWeekNumber = date('W');
        $currentYear = date('Y');
        
        // Panel heading enrichi
        $this->html = '
        <div class="panel-heading">
            
            <!-- Ligne 1 : Bouton expand, Titre, Informations -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                <!-- Colonne gauche : Bouton expand -->
                <div style="flex-shrink: 0;">

                    <!-- Ligne 2 : Filtres type de client en haut à gauche -->
                    <div style="margin-bottom: 15px;">
                        <div class="customer-filter-tabs" style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                            <a href="' . $this->context->link->getAdminLink('AdminStats') . '&module=' . $this->name . '&customer_group=tous" 
                            class="filter-tab ' . ($customer_group == "tous" ? "active" : "") . '" 
                            style="text-decoration: none; padding: 5px 0; ' . ($customer_group == "tous" ? "color: #026473; font-weight: bold; border-bottom: 2px solid #026473;" : "color: #999;") . '">
                                Tous
                            </a>
                            <span style="color: #026473; font-weight: 300;">|</span>
                            <a href="' . $this->context->link->getAdminLink('AdminStats') . '&module=' . $this->name . '&customer_group=particuliers" 
                            class="filter-tab ' . ($customer_group == "particuliers" ? "active" : "") . '" 
                            style="text-decoration: none; padding: 5px 0; ' . ($customer_group == "particuliers" ? "color: #026473; font-weight: bold; border-bottom: 2px solid #026473;" : "color: #999;") . '">
                                Particuliers
                            </a>
                            <span style="color: #026473; font-weight: 300;">|</span>
                            <a href="' . $this->context->link->getAdminLink('AdminStats') . '&module=' . $this->name . '&customer_group=professionnels" 
                            class="filter-tab ' . ($customer_group == "professionnels" ? "active" : "") . '" 
                            style="text-decoration: none; padding: 5px 0; ' . ($customer_group == "professionnels" ? "color: #026473; font-weight: bold; border-bottom: 2px solid #026473;" : "color: #999;") . '">
                                Professionnels
                            </a>
                        </div>
                    </div>
            
                    <button type="button" class="btn btn-primary" id="expandStats" style="border-radius: 50%; width: 35px; height: 35px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                
                <!-- Colonne centrale : Titre et année -->
                <div style="flex-grow: 1; text-align: center; padding-top: 5px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; border-bottom: none !important; background-color: transparent !important; font-weight: 400;">' . $this->displayName . '</h3>
                    <div style="font-size: 14px;">
                        <span style="color: #BDC1C4;">Année en cours :</span> 
                        <span style="color: #026473; font-weight: 400;">' . $currentYear . '</span>
                    </div>
                </div>
                
                <!-- Colonne droite : Semaine et Export -->
                <div style="flex-shrink: 0; text-align: right;">
                    <div style="margin-bottom: 12px; font-size: 14px;">
                        <span style="color: #000;">Semaine actuelle :</span> 
                        <span style="color: #026473; font-weight: bold;">S' . str_pad($currentWeekNumber, 2, '0', STR_PAD_LEFT) . '</span>
                    </div>
                    
                    <div>
                        <a class="btn btn-default" href="' . $this->context->link->getModuleLink($this->name, 'export', [], true) . '" target="_blank" style="white-space: nowrap; height: 34px; line-height: 20px; padding: 6px 12px;">
                            <i class="icon-download"></i> EXPORT
                        </a>
                    </div>
                </div>
            </div>
        </div>';
        
        // Récupération dynamique des magasins depuis ps_ckcstockstores_stores
        $shopsQuery = 'SELECT id_store, name 
                       FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores 
                       ORDER BY name ASC';
        
        $shopsData = Db::getInstance()->executeS($shopsQuery);
        $shop_list = $shopsData ? array_column($shopsData, 'id_store') : [];
        
        // Calculs des dates - Année en cours
        $dt = time();
        $w = date("W");
        $lastYear = date('Y') - 1;
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        // Dates de la semaine en cours
        $week_start = new DateTime();
        $week_start->setISODate($currentYear, $w);
        $week_end = clone $week_start;
        $week_end->add(new DateInterval("P6D"));
        $week_start_str = $week_start->format('Y-m-d');
        $week_end_str = $week_end->format('Y-m-d');
        
        // Dates du mois en cours
        $first_day_this_month = date('Y-m-01');
        $last_day_this_month = date('Y-m-t');
        
        // ========== DATES N-1 (Année précédente) ==========
        
        // N-1 Jour : même jour de la semaine l'année précédente
        $date = new DateTime();
        $dayName = $date->format('l'); // Nom du jour (Monday, Tuesday, etc.)
        $date->sub(new DateInterval('P1Y'));
        $dayLastYear = $date->modify('next ' . $dayName)->format('Y-m-d');
        
        // N-1 Semaine : même numéro de semaine l'année précédente
        $last_year_week_start = new DateTime();
        $last_year_week_start->setISODate($lastYear, $w);
        $last_year_week_end = clone $last_year_week_start;
        $last_year_week_end->add(new DateInterval("P6D"));
        $weekStartLastYear = $last_year_week_start->format('Y-m-d');
        $weekEndLastYear = $last_year_week_end->format('Y-m-d');
        
        // N-1 Mois : même mois l'année précédente
        $monthStartLastYear = $lastYear . '-' . $currentMonth . '-01';
        $monthEndLastYear = date('Y-m-t', strtotime($monthStartLastYear));
        
        // N-1 Année : année précédente complète
        $yearStartLastYear = $lastYear . '-01-01';
        $yearEndLastYear = $lastYear . '-12-31';
        
        // Requête pour récupérer les statistiques depuis la base de données
        $todayStart = date('Y-m-d') . ' 00:00:00';
        $todayEnd = date('Y-m-d') . ' 23:59:59';
        $weekStartDateTime = $week_start_str . ' 00:00:00';
        $weekEndDateTime = $week_end_str . ' 23:59:59';
        $monthStartDateTime = $first_day_this_month . ' 00:00:00';
        $monthEndDateTime = $last_day_this_month . ' 23:59:59';
        $yearStartDateTime = date('Y') . '-01-01 00:00:00';
        $yearEndDateTime = date('Y') . '-12-31 23:59:59';
        
        // Construction de la requête avec filtre type de client + N-1
        $dayLastYearStart = $dayLastYear . ' 00:00:00';
        $dayLastYearEnd = $dayLastYear . ' 23:59:59';
        $weekStartLastYearDateTime = $weekStartLastYear . ' 00:00:00';
        $weekEndLastYearDateTime = $weekEndLastYear . ' 23:59:59';
        $monthStartLastYearDateTime = $monthStartLastYear . ' 00:00:00';
        $monthEndLastYearDateTime = $monthEndLastYear . ' 23:59:59';
        $yearStartLastYearDateTime = $yearStartLastYear . ' 00:00:00';
        $yearEndLastYearDateTime = $yearEndLastYear . ' 23:59:59';
        
        $sql = 'SELECT o.`id_store`, s.`name`,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "' . pSQL($todayStart) . '" AND "' . pSQL($todayEnd) . '" THEN o.`total_paid` ELSE 0 END), 0) AS ca_jour,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "' . pSQL($weekStartDateTime) . '" AND "' . pSQL($weekEndDateTime) . '" THEN o.`total_paid` ELSE 0 END), 0) AS ca_semaine,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "' . pSQL($monthStartDateTime) . '" AND "' . pSQL($monthEndDateTime) . '" THEN o.`total_paid` ELSE 0 END), 0) AS ca_mois,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "' . pSQL($yearStartDateTime) . '" AND "' . pSQL($yearEndDateTime) . '" THEN o.`total_paid` ELSE 0 END), 0) AS ca_annee,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "' . pSQL($dayLastYearStart) . '" AND "' . pSQL($dayLastYearEnd) . '" THEN o.`total_paid` ELSE 0 END), 0) AS ca_jour_n1,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "' . pSQL($weekStartLastYearDateTime) . '" AND "' . pSQL($weekEndLastYearDateTime) . '" THEN o.`total_paid` ELSE 0 END), 0) AS ca_semaine_n1,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "' . pSQL($monthStartLastYearDateTime) . '" AND "' . pSQL($monthEndLastYearDateTime) . '" THEN o.`total_paid` ELSE 0 END), 0) AS ca_mois_n1,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "' . pSQL($yearStartLastYearDateTime) . '" AND "' . pSQL($yearEndLastYearDateTime) . '" THEN o.`total_paid` ELSE 0 END), 0) AS ca_annee_n1
            FROM `' . _DB_PREFIX_ . 'orders` as o
            LEFT JOIN `' . _DB_PREFIX_ . 'ckcstockstores_stores` as s ON o.id_store = s.id_store';
        
        // Ajouter le filtre type de client si nécessaire
        if ($customer_group == 'particuliers') {
            $sql .= ' JOIN ' . _DB_PREFIX_ . 'customer c ON c.id_customer = o.id_customer AND c.id_default_group != 10';
        } elseif ($customer_group == 'professionnels') {
            $sql .= ' JOIN ' . _DB_PREFIX_ . 'customer c ON c.id_customer = o.id_customer AND c.id_default_group = 10';
        }
        
        $sql .= ' WHERE o.`validated_at` IS NOT NULL AND o.`id_store` IS NOT NULL
            GROUP BY o.`id_store`, s.`name`';
        
        $aggregatedResults = Db::getInstance()->executeS($sql);
        $aggregatedStats = [];
        
        if (is_array($aggregatedResults)) {
            foreach ($aggregatedResults as $row) {
                $shopIdKey = (string) $row['id_store'];
                $aggregatedStats[$shopIdKey] = [
                    'name' => $row['name'],
                    'day' => isset($row['ca_jour']) ? (float) $row['ca_jour'] : 0,
                    'week' => isset($row['ca_semaine']) ? (float) $row['ca_semaine'] : 0,
                    'month' => isset($row['ca_mois']) ? (float) $row['ca_mois'] : 0,
                    'year' => isset($row['ca_annee']) ? (float) $row['ca_annee'] : 0,
                    'day_n1' => isset($row['ca_jour_n1']) ? (float) $row['ca_jour_n1'] : 0,
                    'week_n1' => isset($row['ca_semaine_n1']) ? (float) $row['ca_semaine_n1'] : 0,
                    'month_n1' => isset($row['ca_mois_n1']) ? (float) $row['ca_mois_n1'] : 0,
                    'year_n1' => isset($row['ca_annee_n1']) ? (float) $row['ca_annee_n1'] : 0,
                ];
            }
        }
        
        // ========== LECTURE DES OBJECTIFS DEPUIS LE FICHIER EXCEL ==========
        $shopsObjectives = [];
        
        // Initialisation
        foreach ($shop_list as $shop) {
            $shopsObjectives[$shop] = [
                'year' => 0,
                'month' => 0,
                'week' => 0,
                'day' => 0
            ];
        }
        
        // Chemin du fichier Excel des objectifs
        $excelFile = _PS_MODULE_DIR_ . 'dtc_stats_stores/data/objectives/' . $currentYear . '.xlsx';
        
        if (file_exists($excelFile)) {
            try {
                // Charger le fichier Excel avec PhpSpreadsheet
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFile);
                $sheet = $spreadsheet->getActiveSheet();
                
                // Lire les headers (ligne 1) pour identifier les colonnes des magasins
                $headers = [];
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellValue = $sheet->getCellByColumnAndRow($col, 1)->getValue();
                    $headers[$col] = $cellValue;
                }
                
                // Mapper les colonnes par id_store
                $columnMapping = [];
                foreach ($headers as $colIndex => $header) {
                    if (preg_match('/^id_(\d+)$/', $header, $matches)) {
                        $storeId = $matches[1];
                        $columnMapping[$storeId] = $colIndex;
                    }
                }
                
                // Lire les données (à partir de la ligne 2)
                $highestRow = $sheet->getHighestRow();
                
                for ($row = 2; $row <= $highestRow; $row++) {
                    $dateValue = $sheet->getCellByColumnAndRow(1, $row)->getValue();
                    
                    // Convertir la date si nécessaire
                    if (is_numeric($dateValue)) {
                        // Date Excel (nombre de jours depuis 1900)
                        $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                        $date = $dateObj->format('Y-m-d');
                    } else {
                        $date = $dateValue;
                    }
                    
                    // Pour chaque magasin
                    foreach ($shop_list as $shop) {
                        if (isset($columnMapping[$shop])) {
                            $colIndex = $columnMapping[$shop];
                            $amount = (float) $sheet->getCellByColumnAndRow($colIndex, $row)->getValue();
                            
                            // Calcul année
                            $shopsObjectives[$shop]['year'] += $amount;
                            
                            // Calcul mois (mois en cours uniquement)
                            if (substr($date, 0, 7) == $currentYear . '-' . $currentMonth) {
                                $shopsObjectives[$shop]['month'] += $amount;
                            }
                            
                            // Calcul semaine (semaine en cours)
                            if ($date >= $week_start_str && $date <= $week_end_str) {
                                $shopsObjectives[$shop]['week'] += $amount;
                            }
                            
                            // Calcul jour (aujourd'hui)
                            if ($date == date('Y-m-d')) {
                                $shopsObjectives[$shop]['day'] += $amount;
                            }
                        }
                    }
                }
                
            } catch (Exception $e) {
                // En cas d'erreur, les objectifs restent à 0
                // Log l'erreur si nécessaire
                PrestaShopLogger::addLog('Erreur lecture objectifs Excel: ' . $e->getMessage(), 2);
            }
        }
        
        // Totaux
        $totalDay = 0;
        $totalWeek = 0;
        $totalMonth = 0;
        $totalYear = 0;
        $totalDayN1 = 0;
        $totalWeekN1 = 0;
        $totalMonthN1 = 0;
        $totalYearN1 = 0;
        $totalObjDay = 0;
        $totalObjWeek = 0;
        $totalObjMonth = 0;
        $totalObjYear = 0;
        
        // Construction du tableau HTML moderne
        $this->html .= '
        <div class="panel-body">
            <!-- Header-main séparé du tableau -->
            <div class="header-main-container">
                <div class="header-main-cell header-main-magasin"><strong>MAGASIN</strong></div>
                <div class="header-main-cell header-main-jour"><strong>Jour</strong></div>
                <div class="header-main-cell header-main-semaine"><strong>Semaine</strong></div>
                <div class="header-main-cell header-main-mois"><strong>Mois</strong></div>
                <div class="header-main-cell header-main-annee"><strong>Année</strong></div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr class="header-sub">
                            <th class="text-center header-sub-magasin"></th>
                            <th class="text-center">N-1</th>
                            <th class="text-center">Objectif</th>
                            <th class="text-center">Co</th>
                            <th class="text-center">N-1</th>
                            <th class="text-center">Objectif</th>
                            <th class="text-center">Co</th>
                            <th class="text-center">N-1</th>
                            <th class="text-center">Objectif</th>
                            <th class="text-center">Co</th>
                            <th class="text-center">N-1</th>
                            <th class="text-center">Objectif</th>
                            <th class="text-center">Co</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($shop_list as $shop) {
            $shopAggregated = isset($aggregatedStats[$shop]) ? $aggregatedStats[$shop] : [
                'name' => null, 
                'day' => 0, 
                'week' => 0, 
                'month' => 0, 
                'year' => 0,
                'day_n1' => 0,
                'week_n1' => 0,
                'month_n1' => 0,
                'year_n1' => 0
            ];
            
            $shopObjectives = isset($shopsObjectives[$shop]) ? $shopsObjectives[$shop] : [
                'day' => 0,
                'week' => 0,
                'month' => 0,
                'year' => 0
            ];
            
            $shopName = $shopAggregated['name'] ? $shopAggregated['name'] : 'Magasin #' . $shop;
            
            // CA actuel
            $dayCA = (float) $shopAggregated['day'];
            $weekCA = (float) $shopAggregated['week'];
            $monthCA = (float) $shopAggregated['month'];
            $yearCA = (float) $shopAggregated['year'];
            
            // N-1
            $dayN1 = (float) $shopAggregated['day_n1'];
            $weekN1 = (float) $shopAggregated['week_n1'];
            $monthN1 = (float) $shopAggregated['month_n1'];
            $yearN1 = (float) $shopAggregated['year_n1'];
            
            // Objectifs
            $dayObj = (float) $shopObjectives['day'];
            $weekObj = (float) $shopObjectives['week'];
            $monthObj = (float) $shopObjectives['month'];
            $yearObj = (float) $shopObjectives['year'];
            
            // Cumul des totaux
            $totalDay += $dayCA;
            $totalWeek += $weekCA;
            $totalMonth += $monthCA;
            $totalYear += $yearCA;
            $totalDayN1 += $dayN1;
            $totalWeekN1 += $weekN1;
            $totalMonthN1 += $monthN1;
            $totalYearN1 += $yearN1;
            $totalObjDay += $dayObj;
            $totalObjWeek += $weekObj;
            $totalObjMonth += $monthObj;
            $totalObjYear += $yearObj;
            
            $this->html .= '
                        <tr>
                            <td class="text-left shop-name-cell" data-full-name="' . Tools::htmlentitiesUTF8($shopName) . '" data-short-name="' . Tools::htmlentitiesUTF8($shopNameShort) . '">
                                <strong class="shop-name-full">' . Tools::htmlentitiesUTF8($shopName) . '</strong>
                                <strong class="shop-name-short">' . Tools::htmlentitiesUTF8($shopNameShort) . '</strong>
                            </td>
                            <!-- Jour : N-1, Objectif, Co -->
                            <td class="text-right">' . number_format($dayN1, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($dayObj, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($dayCA, 0, ',', ' ') . ' €</td>
                            <!-- Semaine : N-1, Objectif, Co -->
                            <td class="text-right">' . number_format($weekN1, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($weekObj, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($weekCA, 0, ',', ' ') . ' €</td>
                            <!-- Mois : N-1, Objectif, Head (CA) -->
                            <td class="text-right">' . number_format($monthN1, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($monthObj, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($monthCA, 0, ',', ' ') . ' €</td>
                            <!-- Année : N-1, Objectif, Co -->
                            <td class="text-right">' . number_format($yearN1, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($yearObj, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($yearCA, 0, ',', ' ') . ' €</td>
                        </tr>';
        }
        
        // Ligne de total
        $this->html .= '
                        <tr style="font-weight: bold;">
                            <td class="text-left">TOTAL</td>
                            <!-- Jour : N-1, Objectif, Co -->
                            <td class="text-right">' . number_format($totalDayN1, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($totalObjDay, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($totalDay, 0, ',', ' ') . ' €</td>
                            <!-- Semaine : N-1, Objectif, Co -->
                            <td class="text-right">' . number_format($totalWeekN1, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($totalObjWeek, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($totalWeek, 0, ',', ' ') . ' €</td>
                            <!-- Mois : N-1, Objectif, Head -->
                            <td class="text-right">' . number_format($totalMonthN1, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($totalObjMonth, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($totalMonth, 0, ',', ' ') . ' €</td>
                            <!-- Année : N-1, Objectif, Co -->
                            <td class="text-right">' . number_format($totalYearN1, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($totalObjYear, 0, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($totalYear, 0, ',', ' ') . ' €</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
        
        // JavaScript pour le bouton expand
        $this->html .= '
        <script>
            // Bouton expand/collapse
            $("#expandStats").click(function() {
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
        </script>
        
        <style>
            /* Sidebar et container */
            .sidebar.hide { display: none; }
            #statsContainer.w-100 { width: 100% !important; max-width: 100% !important; }
            #statsContainer > .panel {
                background-color: transparent !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            /* Panel */
            .panel-heading {
                background-color: white !important;
                margin: 0 0 15px 0 !important;
                padding: 20px 25px !important;
                border-radius: 30px !important;
                border: none !important;
            }
            .panel-body { background-color: white !important; border-radius: 30px !important; }
            
            /* Bouton expand */
            #expandStats { border: 2px solid #026473 !important; background-color: transparent !important; }
            #expandStats i { font-size: 12px; color: #026473 !important; }
            
            /* Tabs */
            .customer-filter-tabs .filter-tab { transition: all 0.2s ease; font-weight: 12px !important; }
            .customer-filter-tabs .filter-tab:hover { color: #026473 !important; }
            .customer-filter-tabs .filter-tab.active { pointer-events: none; }
            
            /* Tableau */
            .panel-body .table {
                border-radius: 10px;
                overflow: hidden;
                border-collapse: separate !important;
                border-spacing: 0;
                table-layout: fixed;
                width: 100%;
            }
            .panel-body .table-bordered { border-radius: 10px !important; }
            .panel-body .table thead th { border: 1px solid #666 !important; padding: 10px; font-weight: bold; }
            
            /* Header-main séparé */
            .header-main-container { display: flex; width: 100%; margin-bottom: 20px; overflow: hidden; }
            .header-main-cell {
                padding: 15px 20px;
                text-align: center;
                font-weight: bold;
                background-color: #E9E5E2;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* Header-main : éléments individuels */
            .header-main-cell.header-main-magasin {
                font-size: 16px;
                flex: 2 0 auto;
                min-width: 150px;
                border-radius: 25px 0 0 25px;
                margin-right: 0;
            }
            .header-main-cell.header-main-jour,
            .header-main-cell.header-main-semaine,
            .header-main-cell.header-main-mois {
                font-size: 16px;
                flex: 5 1 0;
                margin-left: 0;
                margin-right: 0;
            }
            .header-main-cell.header-main-annee {
                flex: 5 1 0;
                margin-left: 0;
                border-radius: 0 25px 25px 0;
            }
            
            /* Header-sub */
            .panel-body .table thead tr.header-sub th {
                border: none !important;
                background-color: #ffffff !important;
                padding: 15px !important;
                font-weight: 500 !important;
            }
            .panel-body .table thead tr.header-sub th.header-sub-magasin {
                background-color: #E9E5E2 !important;
                border-top-left-radius: 8px !important;
            }
            
            /* Colonne MAGASIN dans le tableau */
            .panel-body .table thead tr.header-sub th.header-sub-magasin,
            .panel-body .table tbody td:first-child {
                width: 15%;
                min-width: 150px;
                background-color: #E9E5E2 !important;
            }
            .table-responsive { margin-top: 0; }
            
            /* Règles par défaut pour shop-name-cell */
            .shop-name-cell {
                padding: 10px;
                font-size: 13px;
            }
            .shop-name-cell strong {
                font-size: 13px;
                font-weight: 700;
            }
            
            /* Règles communes pour afficher nom court sur tablette et mobile */
            @media screen and (max-width: 991px) {
                .shop-name-cell .shop-name-full { display: none; }
                .shop-name-cell .shop-name-short { display: inline; }
            }
            
            /* Règles communes pour table-responsive scroll */
            @media screen and (max-width: 768px) {
                .table-responsive {
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }
                .panel-body .table { border-spacing: 0; }
            }
            
            /* ============================================
               RESPONSIVE - TABLETTE (769px à 991px)
               ============================================ */
            @media screen and (min-width: 769px) and (max-width: 991px) {
                /* Panel heading */
                .panel-heading {
                    padding: 15px 20px !important;
                    border-radius: 20px !important;
                }
                
                /* Header-main : réduire les tailles */
                .header-main-container { margin-bottom: 15px; }
                .header-main-cell {
                    padding: 12px 15px;
                    font-size: 14px !important;
                }
                .header-main-cell.header-main-magasin { min-width: 120px; }
                
                /* Tableau : scroll horizontal */
                .panel-body .table { min-width: 950px; }
                
                /* Header-sub */
                .panel-body .table thead tr.header-sub th {
                    padding: 12px 12px !important;
                    font-size: 12px;
                }
                
                /* Colonne MAGASIN */
                .panel-body .table thead tr.header-sub th.header-sub-magasin,
                .panel-body .table tbody td:first-child {
                    min-width: 120px;
                    width: 12%;
                }
                
                /* Cellules du tableau */
                .panel-body .table tbody td {
                    padding: 10px 12px !important;
                    font-size: 12px;
                    min-width: 80px;
                }
                /* Colonnes numériques */
                .panel-body .table tbody td.text-right {
                    padding: 10px 15px !important;
                    text-align: right !important;
                    min-width: 90px;
                }
            }
            
            /* ============================================
               RESPONSIVE - MOBILE (max-width: 768px)
               ============================================ */
            @media screen and (max-width: 768px) {
                /* Panel heading */
                .panel-heading {
                    padding: 12px 15px !important;
                    border-radius: 15px !important;
                    margin-bottom: 10px !important;
                }
                
                /* Retirer le display flex en mobile */
                .panel-heading > div {
                    display: block !important;
                    flex-direction: column !important;
                    gap: 15px !important;
                }
                
                .panel-heading > div > div:last-child {
                    text-align: center !important;
                }
                
                .panel-heading h2 {
                    font-size: 18px !important;
                }
                
                /* Panel body */
                .panel-body {
                    border-radius: 15px !important;
                    padding: 10px !important;
                }
                
                /* Header-main : scroll horizontal */
                .header-main-container {
                    flex-wrap: nowrap;
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                    border-radius: 15px;
                    margin-bottom: 10px;
                }
                .header-main-cell {
                    padding: 10px 12px;
                    font-size: 12px !important;
                    flex: 0 0 auto;
                    min-width: 100px;
                    white-space: nowrap;
                }
                .header-main-cell.header-main-magasin {
                    min-width: 120px;
                    border-radius: 15px 0 0 15px;
                }
                .header-main-cell.header-main-annee {
                    border-radius: 0 15px 15px 0;
                    min-width: 100px;
                }
                
                /* Tableau : scroll horizontal obligatoire - plus grand */
                .table-responsive {
                    width: 100%;
                    margin: 0;
                    font-size: 14px;
                }
                .panel-body .table {
                    min-width: 1150px;
                    border-radius: 10px;
                    font-size: 14px;
                }
                
                /* Header-sub */
                .panel-body .table thead tr.header-sub th {
                    padding: 12px 15px !important;
                    font-size: 12px;
                    font-weight: 600 !important;
                }
                .panel-body .table thead tr.header-sub th.header-sub-magasin {
                    min-width: 140px;
                    width: 140px;
                }
                
                /* Colonne MAGASIN - plus grande pour les noms */
                .panel-body .table thead tr.header-sub th.header-sub-magasin,
                .panel-body .table tbody td:first-child {
                    min-width: 215px !important;
                    width: 215px !important;
                    max-width: 140px !important;
                    position: sticky;
                    left: 0;
                    z-index: 2;
                    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
                }
                
                /* Noms de magasins - taille augmentée */
                .panel-body .table tbody td:first-child.shop-name-cell {
                    padding: 12px 10px !important;
                    font-size: 14px !important;
                    line-height: 1.4 !important;
                    min-width: 140px !important;
                    width: 140px !important;
                    max-width: 140px !important;
                }
                .panel-body .table tbody td:first-child.shop-name-cell strong {
                    font-size: 14px !important;
                    font-weight: 700 !important;
                    word-break: break-word;
                    hyphens: auto;
                    display: block;
                    line-height: 1.4 !important;
                }
                
                /* Cellules du tableau */
                .panel-body .table tbody td {
                    padding: 12px 15px !important;
                    font-size: 13px;
                    white-space: nowrap;
                    min-width: 90px;
                }
                /* Colonnes numériques */
                .panel-body .table tbody td.text-right {
                    padding: 12px 18px !important;
                    text-align: right !important;
                    min-width: 100px;
                }
                
                /* Tabs */
                .customer-filter-tabs {
                    flex-wrap: wrap;
                    gap: 5px;
                }
                .customer-filter-tabs .filter-tab {
                    font-size: 11px !important;
                    padding: 8px 12px !important;
                }
            }
            
            /* ============================================
               RESPONSIVE - PETIT MOBILE (max-width: 480px)
               ============================================ */
            @media screen and (max-width: 480px) {
                /* Panel heading */
                .panel-heading {
                    padding: 10px 12px !important;
                }
                .panel-heading h2 {
                    font-size: 16px !important;
                }
                
                /* Panel body */
                .panel-body {
                    padding: 8px !important;
                }
                
                /* Header-main */
                .header-main-cell {
                    padding: 8px 10px;
                    font-size: 11px !important;
                }
                .header-main-cell.header-main-magasin {
                    min-width: 120px;
                }
                
                /* Tableau - plus grand */
                .table-responsive { font-size: 13px; }
                .panel-body .table {
                    min-width: 1150px;
                    font-size: 13px;
                }
                
                /* Header-sub */
                .panel-body .table thead tr.header-sub th {
                    padding: 10px 12px !important;
                    font-size: 11px;
                }
                
                /* Colonne MAGASIN - plus grande */
                .panel-body .table thead tr.header-sub th.header-sub-magasin,
                .panel-body .table tbody td:first-child {
                    min-width: 215px !important;
                    width: 215px !important;
                    max-width: 130px !important;
                }
                
                /* Noms de magasins - taille augmentée */
                .panel-body .table tbody td:first-child.shop-name-cell {
                    padding: 10px 8px !important;
                    font-size: 13px !important;
                    line-height: 1.4 !important;
                    min-width: 130px !important;
                    width: 130px !important;
                    max-width: 130px !important;
                }
                .panel-body .table tbody td:first-child.shop-name-cell strong {
                    font-size: 13px !important;
                    font-weight: 700 !important;
                    word-break: break-word;
                    hyphens: auto;
                    display: block;
                    line-height: 1.4 !important;
                }
                
                /* Cellules */
                .panel-body .table tbody td {
                    padding: 10px 12px !important;
                    font-size: 12px;
                    min-width: 85px;
                }
                /* Colonnes numériques */
                .panel-body .table tbody td.text-right {
                    padding: 10px 15px !important;
                    text-align: right !important;
                    min-width: 95px;
                }
            }
        </style>';
        
        return $this->html;
    }
}
