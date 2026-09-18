<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_Stats_General extends Module
{
    private $html = '';
    private $customer_group = 'tous';

    // Constantes pour éviter les magic numbers
    const CUSTOMER_GROUP_PROFESSIONAL_ID = 10;
    const CACHE_LIFETIME = 3600; // 1 heure en secondes
    const SC_NAMES = ['Amelie', 'Vincent', 'Nelly', 'Robin', 'Amel', 'Thibaut'];

    public function __construct()
    {
        $this->name = 'dtc_stats_general';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = 'DASHBOARD - Général';
        $this->description = $this->trans('Enrich your stats, add a summary of all your current statistics on your back office.', array(), 'Modules.Statsforecast.Admin');
        $this->ps_versions_compliancy = array('min' => '1.7.6.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return (parent::install() && $this->registerHook('displayAdminStatsModules') && $this->registerHook('header'));
    }

    public function getContent()
    {
        Tools::redirectAdmin('index.php?controller=AdminStats&module=dtc_stats_general&token='.Tools::getAdminTokenLite('AdminStats'));
    }

    public function hookDisplayAdminStatsModules()
    {
        // get customer group
        $this->customer_group = Tools::getValue('customer_group', 'tous');

        $employee = $this->context->employee;
        $from = $employee->stats_date_from.' 00:00:00';
        $to = $employee->stats_date_to.' 23:59:59';

        // Récupération des données de base avec cache
        $basicStats = $this->getBasicStats($from, $to);
        
        // Récupération des données Service Client avec cache
        $serviceClientStats = $this->getServiceClientStats($from, $to);

        // Calcul des métriques dérivées
        $currentWeekNumber = date('W');
        $generatedCA = ($basicStats['nombredecmd'] > 0) ? round($basicStats['catransaction'] / $basicStats['nombredecmd']) : 0;
        $nbDevisConvSC = ($serviceClientStats['nombrededevisSC'] > 0) ? round($serviceClientStats['nombrededevisSCconv'] / $serviceClientStats['nombrededevisSC'] * 100) : 0;
        $CADevMoySC = ($serviceClientStats['nombrededevisSCconv'] > 0) ? round($serviceClientStats['cadevisSCconv'] / $serviceClientStats['nombrededevisSCconv']) : 0;

        // Calcul des KPIs pour chaque Service Client
        $serviceClientKPIs = [];
        foreach (self::SC_NAMES as $scName) {
            $key = Tools::strtolower($scName);
            $serviceClientKPIs[$key] = $this->calculateKPI(
                $serviceClientStats['quotes'][$key],
                $serviceClientStats['conversions'][$key]['count'],
                $serviceClientStats['conversions'][$key]['total']
            );
        }

        // Calcul des taux de marque par Service Client avec cache
        $tauxMarqueParSC = $this->getTauxMarqueCommercials($from, $to);


        $from_format = new DateTime($from);
        $to_format = new DateTime($to);
        $from_format = $from_format->format('d/m/Y');
        $to_format = $to_format->format('d/m/Y');
        
        // Chargement du CSS et JS via PrestaShop
        $this->context->controller->addCSS($this->_path.'views/css/dtc_stats_general.css');
        $this->context->controller->addJS($this->_path.'views/js/dtc_stats_general.js');
        
        // Header enrichi avec panel-heading moderne
        $this->html .= '
        <div class="panel-heading">
            
            <!-- Ligne 1 : Bouton expand, Titre, Informations -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                <!-- Colonne gauche : Bouton expand -->
                <div style="flex-shrink: 0;">

                    <!-- Ligne 2 : Filtres type de client en haut à gauche -->
                    <div style="margin-bottom: 15px;">
                        <div class="customer-filter-tabs" style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                            <a href="' . $this->context->link->getAdminLink('AdminStats') . '&module=' . $this->name . '&customer_group=tous" 
                            class="filter-tab ' . ($this->customer_group == "tous" ? "active" : "") . '" 
                            style="text-decoration: none; padding: 5px 0; ' . ($this->customer_group == "tous" ? "color: #026473; font-weight: bold; border-bottom: 2px solid #026473;" : "color: #999;") . '">
                                Tous
                            </a>
                            <span style="color: #026473; font-weight: 300;">|</span>
                            <a href="' . $this->context->link->getAdminLink('AdminStats') . '&module=' . $this->name . '&customer_group=particuliers" 
                            class="filter-tab ' . ($this->customer_group == "particuliers" ? "active" : "") . '" 
                            style="text-decoration: none; padding: 5px 0; ' . ($this->customer_group == "particuliers" ? "color: #026473; font-weight: bold; border-bottom: 2px solid #026473;" : "color: #999;") . '">
                                Particuliers
                            </a>
                            <span style="color: #026473; font-weight: 300;">|</span>
                            <a href="' . $this->context->link->getAdminLink('AdminStats') . '&module=' . $this->name . '&customer_group=professionnels" 
                            class="filter-tab ' . ($this->customer_group == "professionnels" ? "active" : "") . '" 
                            style="text-decoration: none; padding: 5px 0; ' . ($this->customer_group == "professionnels" ? "color: #026473; font-weight: bold; border-bottom: 2px solid #026473;" : "color: #999;") . '">
                                Professionnels
                            </a>
                        </div>
                    </div>
            
                    <button type="button" class="btn btn-primary" id="expandStats" style="border-radius: 50%; width: 35px; height: 35px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                
                <!-- Colonne centrale : Titre et dates -->
                <div style="flex-grow: 1; text-align: center; padding-top: 5px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; border-bottom: none !important; background-color: transparent !important; font-weight: 400;">' . $this->displayName . '</h3>
                    <div class="stats-date-range" style="font-size: 16px; display: flex; flex-direction: column; gap: 4px;">
                        <div>
                            <span class="date-label" style="color: #999;">Du :</span> 
                            <span class="date-value" style="color: #026473; font-weight: 400;">' . $from_format . '</span>
                        </div>
                        <div>
                            <span class="date-label" style="color: #999;">Au :</span> 
                            <span class="date-value" style="color: #026473; font-weight: 400;">' . $to_format . '</span>
                        </div>
                    </div>
                </div>
                
                <!-- Colonne droite : Semaine et Export -->
                <div style="flex-shrink: 0; text-align: right;">
                    <div style="margin-bottom: 12px; font-size: 14px;">
                        <span style="color: #000;">Semaine actuelle :</span> 
                        <span style="color: #026473; font-weight: 400;">S' . str_pad($currentWeekNumber, 2, '0', STR_PAD_LEFT) . '</span>
                    </div>
                    
                    <div>
                        <a class="btn btn-default" href="/modules/dtc_stats_general/data/export/kpi.xlsx" target="_blank" style="white-space: nowrap; height: 34px; line-height: 20px; padding: 6px 12px;">
                            <i class="icon-download"></i> EXPORT
                        </a>
                    </div>
                </div>
            </div>
        </div>';

        // KPI
        $this->html .= $this->renderKPICards($basicStats, $serviceClientStats, $generatedCA, $nbDevisConvSC, $CADevMoySC);

        // Préparation des données pour l'export Excel
        $kpiToXlsx = [
            ['Date début', $from_format, 'Date fin', $to_format],
            ['KPI', 'Valeur', '', '']
        ];
        array_push($kpiToXlsx, ['Nombre de devis', $basicStats['nombrededevis']]);
        array_push($kpiToXlsx, ['Nombre de paniers', $basicStats['nombredepanier']]);
        array_push($kpiToXlsx, ['Nombre de commandes', $basicStats['nombredecmd']]);
        array_push($kpiToXlsx, ['CO généré', $basicStats['catransaction']]);
        array_push($kpiToXlsx, ['Nombre de SAV', $basicStats['nombredesav']]);
        array_push($kpiToXlsx, ['Nombre de devis par le service client', $serviceClientStats['nombrededevisSC']]);
        array_push($kpiToXlsx, ['Nombre de devis convertis par le service client', $serviceClientStats['nombrededevisSCconv']]);
        array_push($kpiToXlsx, ['CO généré par les devis convertis par le service client', $serviceClientStats['cadevisSCconv']]);
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($kpiToXlsx);
        foreach ($sheet->getColumnIterator() as $column)
        {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save(_PS_MODULE_DIR_.'dtc_stats_general/data/export/kpi.xlsx');
        
        // Commerciaux
        $this->html .= $this->renderCommercialsTable($serviceClientStats, $serviceClientKPIs, $tauxMarqueParSC);

        // Calcul des nouvelles métriques avec cache
        $advancedMetrics = $this->getAdvancedMetrics($from, $to);
        $this->html .= $this->renderAdvancedMetricsCards($advancedMetrics);
        
        // Fidélisation client
        $dataLayalty = $this->getLoyaltyData($basicStats['catransaction']);
        $this->html .= $this->renderLoyaltyCards($dataLayalty);
        
        return $this->html;
    }

    /**
     * Récupère les statistiques de base avec cache
     */
    private function getBasicStats($from, $to)
    {
        $cacheKey = 'dtc_stats_general_basic_' . md5($from . $to . $this->customer_group);
        
        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }
        
        $db = Db::getInstance();
        
        // Requête devis
        $sql = $this->buildQuery('SELECT COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'opartdevis`', 
            'WHERE `' . _DB_PREFIX_ . 'opartdevis`.`date_add` BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '"', 
            _DB_PREFIX_ . 'opartdevis');
        $nombrededevis = $this->countResults($db, $sql);

        // Requête panier
        $sql = $this->buildQuery('SELECT COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'cart`', 
            'WHERE `' . _DB_PREFIX_ . 'cart`.`date_add` BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '"',
            _DB_PREFIX_ . 'cart');
        $nombredepanier = $this->countResults($db, $sql);

        // Requête commandes
        $sql = $this->buildQuery('SELECT COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'orders`', 
            'WHERE (`' . _DB_PREFIX_ . 'orders`.`validated_at` IS NOT NULL) AND (`' . _DB_PREFIX_ . 'orders`.`validated_at` BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '")', 
            _DB_PREFIX_ . 'orders');
        $nombredecmd = $this->countResults($db, $sql);

        // Requête CO généré (optimisée avec getRow)
        $sql = $this->buildQuery('SELECT SUM(total_paid) as total FROM `' . _DB_PREFIX_ . 'orders`', 
            'WHERE (`validated_at` IS NOT NULL) AND (`validated_at` BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '")', 
            _DB_PREFIX_ . 'orders');
        $result = $db->getRow($sql);
        $catransaction = $result && isset($result['total']) ? (int)round($result['total']) : 0;
        
        $stats = [
            'nombrededevis' => $nombrededevis,
            'nombredepanier' => $nombredepanier,
            'nombredecmd' => $nombredecmd,
            'catransaction' => $catransaction,
            'nombredesav' => 0
        ];
        
        Cache::store($cacheKey, $stats, self::CACHE_LIFETIME);
        return $stats;
    }

    /**
     * Condition SQL : le nom du devis contient le SC (Amel ne doit pas matcher Amelie / Amélie).
     *
     * @param string $tableAlias Alias SQL de la table (ex. P, ca)
     */
    private function sqlConditionScNameInDevisColumn($tableAlias, $scName)
    {
        $col = $tableAlias . '.`name`';
        $cond = $col . ' LIKE "%' . pSQL($scName) . '%"';

        if (Tools::strtolower($scName) === 'amel') {
            $cond .= ' AND ' . $col . ' NOT LIKE "%' . pSQL('Amelie') . '%"';
            $cond .= ' AND ' . $col . ' NOT LIKE "%' . pSQL('Amélie') . '%"';
        }

        return '(' . $cond . ')';
    }

    /**
     * Récupère les statistiques Service Client avec cache
     */
    private function getServiceClientStats($from, $to)
    {
        $cacheKey = 'dtc_stats_general_sc_' . md5($from . $to . $this->customer_group);
        
        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }
        
        $db = Db::getInstance();

        // Requête devis par SC
        $selectScQuotes = [];
        foreach (self::SC_NAMES as $scName) {
            $lowerName = Tools::strtolower($scName);
            $selectScQuotes[] = 'SUM(CASE WHEN ' . $this->sqlConditionScNameInDevisColumn('P', $scName) . ' THEN 1 ELSE 0 END) AS quotes_' . $lowerName;
        }
        $sql = $this->buildQuery('SELECT ' . implode(",\n            ", $selectScQuotes) . ' FROM `' . _DB_PREFIX_ . 'opartdevis` AS P', 
            'WHERE (P.`date_add` BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '")', 
            'P');
        $scQuoteData = $db->getRow($sql);
        $scQuoteData = $scQuoteData ? $scQuoteData : [];

        // Requête devis convertis par SC
        $selectScConversions = [];
        foreach (self::SC_NAMES as $scName) {
            $lowerName = Tools::strtolower($scName);
            $nameMatch = $this->sqlConditionScNameInDevisColumn('P', $scName);
            $selectScConversions[] = 'SUM(CASE WHEN ' . $nameMatch . ' THEN 1 ELSE 0 END) AS conv_count_' . $lowerName;
            $selectScConversions[] = 'SUM(CASE WHEN ' . $nameMatch . ' THEN O.total_paid ELSE 0 END) AS conv_total_' . $lowerName;
        }
        $sql = $this->buildQuery('SELECT ' . implode(",\n            ", $selectScConversions) . '
            FROM `' . _DB_PREFIX_ . 'orders` AS O
            INNER JOIN `' . _DB_PREFIX_ . 'opartdevis` AS P ON O.id_order = P.id_order', 
            'WHERE (O.`validated_at` IS NOT NULL) AND (O.`validated_at` BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '")', 
            'P');
        $scConversionData = $db->getRow($sql);
        $scConversionData = $scConversionData ? $scConversionData : [];

        // Structure de données pour remplacer les variables dynamiques
        $quotes = [];
        $conversions = [];
        $nombrededevisSC = 0;
        $nombrededevisSCconv = 0;
        $cadevisSCconv = 0;
        
        foreach (self::SC_NAMES as $scName) {
            $key = Tools::strtolower($scName);
            $quotes[$key] = isset($scQuoteData['quotes_' . $key]) ? (int)$scQuoteData['quotes_' . $key] : 0;
            $conversions[$key] = [
                'count' => isset($scConversionData['conv_count_' . $key]) ? (int)$scConversionData['conv_count_' . $key] : 0,
                'total' => isset($scConversionData['conv_total_' . $key]) ? (int)round($scConversionData['conv_total_' . $key]) : 0
            ];
            
            $nombrededevisSC += $quotes[$key];
            $nombrededevisSCconv += $conversions[$key]['count'];
            $cadevisSCconv += $conversions[$key]['total'];
        }

        $stats = [
            'quotes' => $quotes,
            'conversions' => $conversions,
            'nombrededevisSC' => $nombrededevisSC,
            'nombrededevisSCconv' => $nombrededevisSCconv,
            'cadevisSCconv' => $cadevisSCconv
        ];
        
        Cache::store($cacheKey, $stats, self::CACHE_LIFETIME);
        return $stats;
    }

    /**
     * Récupère les métriques avancées avec cache
     */
    private function getAdvancedMetrics($from, $to)
    {
        $cacheKey = 'dtc_stats_general_advanced_' . md5($from . $to . $this->customer_group);
        
        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }
        
        $db = Db::getInstance();

        // CO HT sans livraison (optimisé avec getRow)
        $sql = $this->buildQuery('SELECT SUM(total_paid_tax_excl - total_shipping_tax_excl) as total FROM `' . _DB_PREFIX_ . 'orders`', 
            'WHERE (`validated_at` IS NOT NULL) AND (`validated_at` BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '")', 
            _DB_PREFIX_ . 'orders');
        $result = $db->getRow($sql);
        $coSansLivraison = $result && isset($result['total']) ? (int)round($result['total']) : 0;

        // Valeur d'achat (optimisé avec getRow)
        $sql = $this->buildQuery('SELECT SUM(od.purchase_supplier_price * od.product_quantity) as total FROM `' . _DB_PREFIX_ . 'orders` o JOIN `' . _DB_PREFIX_ . 'order_detail` od ON o.id_order = od.id_order', 
            'WHERE (o.`validated_at` IS NOT NULL) AND (o.`validated_at` BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '")', 
            'o');
        $result = $db->getRow($sql);
        $valeurAchat = $result && isset($result['total']) ? (int)round($result['total']) : 0;

        // Valeur total livraison (optimisé avec getRow)
        $sql = $this->buildQuery('SELECT SUM(total_shipping_tax_excl) as total FROM `' . _DB_PREFIX_ . 'orders`', 
            'WHERE (`validated_at` IS NOT NULL) AND (`validated_at` BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '")', 
            _DB_PREFIX_ . 'orders');
        $result = $db->getRow($sql);
        $valeurLivraison = $result && isset($result['total']) ? (int)round($result['total']) : 0;

        // Calcul taux de marque (optimisé avec getRow)
        $sql = 'SELECT
          ROUND(SUM(ca.ca_ht), 2)  AS ca_ht,
          ROUND(SUM(co.cout_ht), 2) AS cout_ht,
          ROUND((SUM(ca.ca_ht) - SUM(co.cout_ht)) / NULLIF(SUM(ca.ca_ht),0) * 100, 2) AS taux_marque_pct
        FROM (
          SELECT o.id_order, (o.total_products - o.total_discounts_tax_excl) AS ca_ht
          FROM ' . _DB_PREFIX_ . 'orders o
          WHERE o.validated_at BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '"
            AND o.validated_at IS NOT NULL
        ) ca
        JOIN (
          SELECT o.id_order,
                 SUM(COALESCE(NULLIF(pa.wholesale_price,0), NULLIF(p.wholesale_price,0), 0)
                     * od.product_quantity) AS cout_ht
          FROM ' . _DB_PREFIX_ . 'orders o
          JOIN ' . _DB_PREFIX_ . 'order_detail od           ON od.id_order = o.id_order
          LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON pa.id_product_attribute = od.product_attribute_id
          JOIN ' . _DB_PREFIX_ . 'product p                 ON p.id_product = od.product_id
          WHERE o.validated_at BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '"
            AND o.validated_at IS NOT NULL
          GROUP BY o.id_order
        ) co ON co.id_order = ca.id_order';
        
        $result = $db->getRow($sql);
        $tauxMarque = $result && isset($result['taux_marque_pct']) ? round($result['taux_marque_pct'], 2) : 0;

        $metrics = [
            'coSansLivraison' => $coSansLivraison,
            'valeurAchat' => $valeurAchat,
            'valeurLivraison' => $valeurLivraison,
            'tauxMarque' => $tauxMarque
        ];
        
        Cache::store($cacheKey, $metrics, self::CACHE_LIFETIME);
        return $metrics;
    }

    /**
     * Récupère les taux de marque par Service Client avec cache
     */
    private function getTauxMarqueCommercials($from, $to)
    {
        $cacheKey = 'dtc_stats_general_taux_marque_sc_' . md5($from . $to . $this->customer_group);
        
        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }
        
        $rates = $this->calculateTauxMarqueCommercials(Db::getInstance(), self::SC_NAMES, $from, $to);
        
        Cache::store($cacheKey, $rates, self::CACHE_LIFETIME);
        return $rates;
    }

    /**
     * Génère le HTML des cartes KPI
     */
    private function renderKPICards($basicStats, $serviceClientStats, $generatedCA, $nbDevisConvSC, $CADevMoySC)
    {
        return '<div class="contener_analyse">
              <div class="analyse_content">
                    <h3>Nombre de devis</h3>
                    <p class="details">Il s\'agit du nombre de devis réalisé durant la période selectionnée.</p>
                    <p class="result">' . $basicStats['nombrededevis'] . '</p>
              </div>
              <div class="analyse_content">
                    <h3>Nombre de paniers</h3>
                    <p class="details">Il s\'agit du nombre de paniers réalisé durant la période selectionnée.</p>
                    <p class="result">' . $basicStats['nombredepanier'] . '</p>
              </div>
              <div class="analyse_content">
                    <h3>Nombre de commandes</h3>
                    <p class="details">Il s\'agit du nombre de commandes valides durant la période selectionnée.</p>
                    <p class="result">' . $basicStats['nombredecmd'] . '</p>
              </div>
              <div class="analyse_content">
                    <h3>CO généré</h3>
                    <p class="details">Il s\'agit du CO généré durant la période selectionnée.</p>
                    <p class="result data_details">Soit un panier moyen de ' . number_format($generatedCA, 0, ',', ' ') . ' €</p>
                    <p class="result">' . number_format($basicStats['catransaction'], 0, ',', ' ') . ' €</p>
              </div>
              <div class="analyse_content service_client">
                    <h3>Nombre de SAV</h3>
                    <p class="details">Il s\'agit du nombre de messages adressés au service client via SAV.</p>
                    <p class="result">' . $basicStats['nombredesav'] . '</p>
              </div>
              <div class="analyse_content service_client">
                    <h3>Nombre de devis par le service client</h3>
                    <p class="details">Il s\'agit du nombre de devis dont le dossier est suivi par un des télé-opérateurs.</p>
                    <p class="result">' . $serviceClientStats['nombrededevisSC'] . '</p>
              </div>
              <div class="analyse_content service_client">
                    <h3>Nombre de devis convertis par le service client</h3>
                    <p class="details">Il s\'agit du nombre de devis Service Client validés.</p>
                    <p class="result data_details">' . $nbDevisConvSC . ' %</p>
                    <p class="result">' . $serviceClientStats['nombrededevisSCconv'] . '</p>
              </div>
              <div class="analyse_content service_client">
                    <h3>CO généré par les devis convertis par le service client</h3>
                    <p class="details">Il s\'agit de la valeur des devis convertis, avec intervention du Service Client.</p>
                    <p class="result data_details">Soit en moyenne ' . number_format($CADevMoySC, 0, ',', ' ') . ' € par devis</p>
                    <p class="result">' . number_format($serviceClientStats['cadevisSCconv'], 0, ',', ' ') . ' €</p>
              </div>
        </div>';
    }

    /**
     * Génère le HTML du tableau des commerciaux
     */
    private function renderCommercialsTable($serviceClientStats, $serviceClientKPIs, $tauxMarqueParSC)
    {
        $html = '<div class="contener_analyse_indiv">
                                          <div class="content_analyse_indiv">
                                                <div class="first_row row">
                                                      <div>&nbsp;</div>
                                                      <div>Nombre de devis</div>
                                                      <div>% de devis convertis</div>
                                                      <div>Nombre de devis convertis</div>
                                                      <div>Taux de marque</div>
                                                      <div>Valeur des devis convertis</div>
                                                      <div>Moyenne de CO / devis</div>
                                    </div>';

        // Mapping des noms d'affichage (pour gérer les accents)
        $displayNames = [
            'nelly' => 'Nelly',
            'vincent' => 'Vincent',
            'amelie' => 'Amelie',
            'robin' => 'Robin',
            'amel' => 'Amel',
            'thibaut' => 'Thibaut'
        ];
        
        $rowClasses = ['two_row', 'third_row', 'fourth_row', 'fifth_row', 'sixth_row', 'seventh_row'];
        $index = 0;
        
        foreach (self::SC_NAMES as $scName) {
            $key = Tools::strtolower($scName);
            $displayName = isset($displayNames[$key]) ? $displayNames[$key] : $scName;
            $rowClass = isset($rowClasses[$index]) ? $rowClasses[$index] : 'row';
            
            $html .= '<div class="' . $rowClass . ' row">
                      <div>' . $displayName . '</div>
                      <div>' . $serviceClientStats['quotes'][$key] . '</div>
                      <div>' . $serviceClientKPIs[$key][0] . ' &nbsp;%</div>
                      <div>' . $serviceClientStats['conversions'][$key]['count'] . '</div>
                      <div>' . (isset($tauxMarqueParSC[$key]) ? $tauxMarqueParSC[$key] : 0) . '&nbsp;%</div>
                      <div>' . number_format($serviceClientStats['conversions'][$key]['total'], 0, ',', ' ') . '&nbsp;€</div>
                      <div>' . number_format($serviceClientKPIs[$key][1], 0, ',', ' ') . '&nbsp;€</div>
                </div>';
            $index++;
        }
        
        $html .= '</div></div>';
        return $html;
    }

    /**
     * Génère le HTML des cartes de métriques avancées
     */
    private function renderAdvancedMetricsCards($advancedMetrics)
    {
        return '<div class="contener_analyse" style="margin-top: 2rem;">
              <div class="analyse_content">
                    <h3>Total CO HT sans livraison</h3>
                    <p class="details">Chiffre d\'affaires total moins les frais de livraison.</p>
                    <p class="result">' . number_format($advancedMetrics['coSansLivraison'], 0, ',', ' ') . ' €</p>
              </div>
              <div class="analyse_content">
                    <h3>Total valeur d\'achat</h3>
                    <p class="details">Prix d\'achat fournisseur total des produits vendus.</p>
                    <p class="result">' . number_format($advancedMetrics['valeurAchat'], 0, ',', ' ') . ' €</p>
                </div>
              <div class="analyse_content">
                    <h3>Taux de marque</h3>
                    <p class="details">((Prix catalogue - Prix d\'achat fournisseur) / Prix catalogue) × 100</p>
                    <p class="result">' . $advancedMetrics['tauxMarque'] . ' %</p>
              </div>
              <div class="analyse_content">
                    <h3>Valeur total HT livraison</h3>
                    <p class="details">Total des frais de livraison facturés.</p>
                    <p class="result">' . number_format($advancedMetrics['valeurLivraison'], 0, ',', ' ') . ' €</p>
              </div>
        </div>';
    }

    /**
     * Génère le HTML des cartes de fidélité client
     */
    private function renderLoyaltyCards($dataLayalty)
    {
        return '<div class="contener_analyse" style="margin-top: 1em">
              <div class="analyse_content" style="background-color: #E8E3DE !important;">
                    <h3>1 commande</h3>
                    <p class="details">Nombre de clients : ' . $dataLayalty["one_order"]["count"] . '</p>
                    <p class="details">' . $dataLayalty["one_order"]["percentage"] . '%</p>
                    <p class="result">CO : ' . number_format($dataLayalty["one_order"]["total_spent"], 0, ',', ' ') . '€</p>
              </div>
              <div class="analyse_content" style="background-color: #E8E3DE !important;">
                    <h3>2 commandes</h3>
                    <p class="details">Nombre de clients : ' . $dataLayalty["two_orders"]["count"] . '</p>
                    <p class="details">' . $dataLayalty["two_orders"]["percentage"] . '%</p>
                    <p class="result">CO : ' . number_format($dataLayalty["two_orders"]["total_spent"], 0, ',', ' ') . '€</p>
              </div>
              <div class="analyse_content" style="background-color: #E8E3DE !important;">
                    <h3>3 commandes</h3>
                    <p class="details">Nombre de clients : ' . $dataLayalty["three_orders"]["count"] . '</p>
                    <p class="details">' . $dataLayalty["three_orders"]["percentage"] . '%</p>
                    <p class="result">CO : ' . number_format($dataLayalty["three_orders"]["total_spent"], 0, ',', ' ') . '€</p>
              </div>
              <div class="analyse_content" style="background-color: #E8E3DE !important;">
                    <h3>4 commandes</h3>
                    <p class="details">Nombre de clients : ' . $dataLayalty["four_orders"]["count"] . '</p>
                    <p class="details">' . $dataLayalty["four_orders"]["percentage"] . '%</p>
                    <p class="result">CO : ' . number_format($dataLayalty["four_orders"]["total_spent"], 0, ',', ' ') . '€</p>
              </div>
        </div>';
    }

    /**
     * Compte les résultats d'une requête SQL de manière optimisée
     */
    private function countResults($db, $sql) {
        $result = $db->getValue($sql);
        return $result ? (int)$result : 0;
    }

    /**
     * Calcule les KPIs pour un Service Client
     */
    private function calculateKPI($nombrededevisSC, $nombrededevisSCconv, $cadevisSCconv) {
        $kpiPercentQuoteOrdered = ($nombrededevisSC > 0) ? round($nombrededevisSCconv / $nombrededevisSC * 100) : 0;
        $kpiMoyCA = ($cadevisSCconv > 0) ? round($cadevisSCconv / $nombrededevisSCconv) : 0;
        return [$kpiPercentQuoteOrdered, $kpiMoyCA];
    }

    /**
     * Calcule les taux de marque pour plusieurs commerciaux
     */
    private function calculateTauxMarqueCommercials($db, $scNames, $from, $to)
    {
        if (empty($scNames)) {
            return array();
        }

        $selects = array();
        foreach ($scNames as $scName) {
            $lowerName = Tools::strtolower($scName);
            $nameMatch = $this->sqlConditionScNameInDevisColumn('ca', $scName);
            $selects[] = 'SUM(CASE WHEN ' . $nameMatch . ' THEN ca.ca_ht ELSE 0 END) AS ca_'.$lowerName;
            $selects[] = 'SUM(CASE WHEN ' . $nameMatch . ' THEN co.cout_ht ELSE 0 END) AS cout_'.$lowerName;
        }

        $sql = 'SELECT
            '.implode(",\n            ", $selects).'
        FROM (
            SELECT
                o.id_order,
                (o.total_products - o.total_discounts_tax_excl) AS ca_ht,
                COALESCE(d.name, "") AS name
            FROM ps_orders o
            LEFT JOIN ps_opartdevis d ON d.id_order = o.id_order
            WHERE o.validated_at IS NOT NULL
              AND o.validated_at BETWEEN "'.$from.'" AND "'.$to.'"
        ) ca
        JOIN (
            SELECT
                o.id_order,
                SUM(COALESCE(NULLIF(pa.wholesale_price,0), NULLIF(p.wholesale_price,0), 0)
                    * od.product_quantity) AS cout_ht
            FROM ps_orders o
            JOIN ps_order_detail od           ON od.id_order = o.id_order
            LEFT JOIN ps_product_attribute pa ON pa.id_product_attribute = od.product_attribute_id
            JOIN ps_product p                 ON p.id_product = od.product_id
            WHERE o.validated_at IS NOT NULL
              AND o.validated_at BETWEEN "'.$from.'" AND "'.$to.'"
            GROUP BY o.id_order
        ) co ON co.id_order = ca.id_order';

        $result = $db->getRow($sql);
        if (!$result) {
            return array();
        }

        $rates = array();
        foreach ($scNames as $scName) {
            $key = Tools::strtolower($scName);
            $caKey = 'ca_'.$key;
            $coutKey = 'cout_'.$key;
            $ca = isset($result[$caKey]) ? (float) $result[$caKey] : 0.0;
            $cout = isset($result[$coutKey]) ? (float) $result[$coutKey] : 0.0;
            $rates[$key] = $ca > 0 ? round((($ca - $cout) / $ca) * 100, 2) : 0;
        }

        return $rates;
    }

    /**
     * Construit une requête SQL en ajoutant le filtre de groupe de clients si nécessaire
     */
    private function buildQuery($request, $requestWhere, $linkedTable) {
        if ($this->customer_group == 'particuliers') {
            $request .= ' JOIN ' . _DB_PREFIX_ . 'customer c ON c.id_customer = ' . $linkedTable . '.id_customer AND c.id_default_group != ' . self::CUSTOMER_GROUP_PROFESSIONAL_ID . ' ' . $requestWhere;
        } elseif ($this->customer_group == 'professionnels') {
            $request .= ' JOIN ' . _DB_PREFIX_ . 'customer c ON c.id_customer = ' . $linkedTable . '.id_customer AND c.id_default_group = ' . self::CUSTOMER_GROUP_PROFESSIONAL_ID . ' ' . $requestWhere;
        } else {
            $request .= ' ' . $requestWhere;
        }
        return $request;
    }

    /**
     * Récupère les données de fidélité client avec cache
     */
    protected function getLoyaltyData($catransaction)
    {
        $employee = $this->context->employee;

        $from = max(_PS_CREATION_DATE_.' 00:00:00', $employee->stats_date_from.' 00:00:00');
        $to = $employee->stats_date_to.' 23:59:59';
        
        $cacheKey = 'dtc_stats_general_loyalty_' . md5($from . $to);
        
        if (Cache::isStored($cacheKey)) {
            $data = Cache::retrieve($cacheKey);
        } else {
            $sql = 'SELECT id_customer, COUNT(id_order) AS orders_count, SUM(total_paid) AS total_spent
            FROM ' . _DB_PREFIX_ . 'orders
                WHERE (`validated_at` IS NOT NULL) AND (validated_at BETWEEN "' . pSQL($from) . '" AND "' . pSQL($to) . '")
             GROUP BY id_customer';

        $results = Db::getInstance()->executeS($sql);
        $data = array(
            'one_order' => array('count' => 0, 'total_spent' => 0),
            'two_orders' => array('count' => 0, 'total_spent' => 0),
            'three_orders' => array('count' => 0, 'total_spent' => 0),
            'four_orders' => array('count' => 0, 'total_spent' => 0),
            );

        foreach ($results as $row) {
            switch ($row['orders_count']) {
                case 1:
                    $data['one_order']['count']++;
                    $data['one_order']['total_spent'] += $row['total_spent'];
                    break;
                case 2:
                    $data['two_orders']['count']++;
                    $data['two_orders']['total_spent'] += $row['total_spent'];
                    break;
                case 3:
                    $data['three_orders']['count']++;
                    $data['three_orders']['total_spent'] += $row['total_spent'];
                    break;
                case 4:
                    $data['four_orders']['count']++;
                    $data['four_orders']['total_spent'] += $row['total_spent'];
                    break;
            }
        }
            
            Cache::store($cacheKey, $data, self::CACHE_LIFETIME);
        }
        
        // Calcul des pourcentages (nécessite catransaction qui peut varier selon le groupe client)
        $data['one_order']['percentage'] = $catransaction > 0 ? round($data['one_order']['total_spent'] / $catransaction * 100, 3) : 0;
        $data['two_orders']['percentage'] = $catransaction > 0 ? round($data['two_orders']['total_spent'] / $catransaction * 100, 3) : 0;
        $data['three_orders']['percentage'] = $catransaction > 0 ? round($data['three_orders']['total_spent'] / $catransaction * 100, 3) : 0;
        $data['four_orders']['percentage'] = $catransaction > 0 ? round($data['four_orders']['total_spent'] / $catransaction * 100, 3) : 0;

        return $data;
    }

}
