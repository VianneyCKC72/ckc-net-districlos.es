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

class statsforecast extends Module
{
    private $html = '';
    private $customer_group = 'tous';

    public function __construct()
    {
        $this->name = 'statsforecast';
        $this->tab = 'administration';
        $this->version = '2.0.4';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->trans('Stats Dashboard', array(), 'Modules.Statsforecast.Admin');
        $this->description = $this->trans('Enrich your stats, add a summary of all your current statistics on your back office.', array(), 'Modules.Statsforecast.Admin');
        $this->ps_versions_compliancy = array('min' => '1.7.6.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return (parent::install() && $this->registerHook('displayAdminStatsModules') && $this->registerHook('header'));
    }

    public function getContent()
    {
        Tools::redirectAdmin('index.php?controller=AdminStats&module=statsforecast&token='.Tools::getAdminTokenLite('AdminStats'));
    }

    public function hookDisplayAdminStatsModules()
    {

        $db = Db::getInstance();

        if (!isset($this->context->cookie->stats_granularity)) {
            $this->context->cookie->stats_granularity = 10;
        }
        if (Tools::isSubmit('submitIdZone')) {
            $this->context->cookie->stats_id_zone = (int)Tools::getValue('stats_id_zone');
        }
        if (Tools::isSubmit('submitGranularity')) {
            $this->context->cookie->stats_granularity = Tools::getValue('stats_granularity');
        }

        // get customer group
        $this->customer_group = Tools::getValue('customer_group', 'tous');

        $employee = $this->context->employee;

        $from = $employee->stats_date_from.' 00:00:00';
        $to = $employee->stats_date_to.' 23:59:59';

        /*************** requête devis *************/
        $sql = $this->buildQuery('SELECT COUNT(*) AS total FROM `ps_opartdevis`', 'WHERE `ps_opartdevis`.`date_add` BETWEEN "'.$from.'" AND "'.$to.'"', 'ps_opartdevis');
        $nombrededevis = $this->countResults($db, $sql);

        /*************** requête panier *************/
        $sql = $this->buildQuery('SELECT COUNT(*) AS total FROM `ps_cart`', 'WHERE `ps_cart`.`date_add` BETWEEN "'.$from.'" AND "'.$to.'"','ps_cart');
        $nombredepanier = $this->countResults($db, $sql);

        /*************** requête panier *************/
        $sql = $this->buildQuery('SELECT COUNT(*) AS total FROM `ps_orders`', 'WHERE (`ps_orders`.`validated_at` IS NOT NULL) AND (`ps_orders`.`validated_at` BETWEEN "'.$from.'" AND "'.$to.'")', 'ps_orders');
        $nombredecmd = $this->countResults($db, $sql);

        /*************** requête SAV *************/
        //$sql = 'SELECT * FROM `ps_contact_form_request` WHERE T.id_customer_thread = M.id_customer_thread AND T.`date_add` BETWEEN "'.str_replace(' 00:00:00', '', $from).'" AND "'.str_replace(' 23:59:59', '', $to).'" AND M.`message` NOT LIKE "%HiPay Notification%" AND M.`message` NOT LIKE "%succès (00)%" AND M.`message` NOT LIKE "%transaction HiPay%" AND M.`message` NOT LIKE "%BackOffice HiPay%" AND M.`id_employee` = "0" GROUP BY M.id_customer_thread) AS c GROUP BY c.`message`';

        //$sql = 'SELECT * FROM `ps_contact_form_request` WHERE `date_add` BETWEEN "'.$from.'" AND "'.$to.'" AND id_contact = 4';
        //$nombredesav = $this->countResults($db, $sql);
	    $nombredesav = 0;
        /*************** requête CO générée par transactions *************/
        $sql = $this->buildQuery('SELECT SUM(total_paid) as total FROM `ps_orders`', 'WHERE (`validated_at` IS NOT NULL) AND (`validated_at` BETWEEN "'.$from.'" AND "'.$to.'")', 'ps_orders');
        $result = $db->executeS($sql);
        $catransaction = $result[0]['total'] ? round($result[0]['total']) : 0;

        /*************** Liste des noms des SC à rechercher *************/
        $scNames = ["Amelie", "Vincent", "Nelly", "Robin", "Leo", "Thibaut"];

        /*************** requête devis par SC *************/
        $selectScQuotes = array();
        foreach ($scNames as $scName) {
            $lowerName = Tools::strtolower($scName);
            $escapedName = pSQL($scName);
            $selectScQuotes[] = 'SUM(CASE WHEN P.name LIKE "%'.$escapedName.'%" THEN 1 ELSE 0 END) AS quotes_'.$lowerName;
        }
        $sql = $this->buildQuery('SELECT '.implode(",\n            ", $selectScQuotes).' FROM `ps_opartdevis` AS P', 'WHERE (P.`date_add` BETWEEN "'.$from.'" AND "'.$to.'")', 'P');
        $scQuoteData = $db->executeS($sql);
        $scQuoteData = $scQuoteData ? $scQuoteData[0] : array();
        foreach ($scNames as $scName) {
            $key = Tools::strtolower($scName);
            ${"nombrededevisSC" . $key} = isset($scQuoteData['quotes_'.$key]) ? (int) $scQuoteData['quotes_'.$key] : 0;
        }

        /*************** requête devis convertis par SC *************/
        $selectScConversions = array();
        foreach ($scNames as $scName) {
            $lowerName = Tools::strtolower($scName);
            $escapedName = pSQL($scName);
            $selectScConversions[] = 'SUM(CASE WHEN P.name LIKE "%'.$escapedName.'%" THEN 1 ELSE 0 END) AS conv_count_'.$lowerName;
            $selectScConversions[] = 'SUM(CASE WHEN P.name LIKE "%'.$escapedName.'%" THEN O.total_paid ELSE 0 END) AS conv_total_'.$lowerName;
        }
        $sql = $this->buildQuery('SELECT '.implode(",\n            ", $selectScConversions).'
            FROM `ps_orders` AS O
            INNER JOIN `ps_opartdevis` AS P ON O.id_order = P.id_order', 'WHERE (O.`validated_at` IS NOT NULL) AND (O.`validated_at` BETWEEN "'.$from.'" AND "'.$to.'")', 'P');
        $scConversionData = $db->executeS($sql);
        $scConversionData = $scConversionData ? $scConversionData[0] : array();
        foreach ($scNames as $scName) {
            $key = Tools::strtolower($scName);
            ${"nombrededevisSCconv" . $key} = isset($scConversionData['conv_count_'.$key]) ? (int) $scConversionData['conv_count_'.$key] : 0;
            ${"cadevisSCconv" . $key} = isset($scConversionData['conv_total_'.$key]) ? (int) round($scConversionData['conv_total_'.$key]) : 0;
        }

        $nombrededevisSC = 0;
        $nombrededevisSCconv = 0;
        $cadevisSCconv = 0;
        foreach ($scNames as $scName) {
            $key = Tools::strtolower($scName);
            $nombrededevisSC += ${"nombrededevisSC" . $key};
            $nombrededevisSCconv += ${"nombrededevisSCconv" . $key};
            $cadevisSCconv += ${"cadevisSCconv" . $key};
        }

        /************** Calcul de la semaine ******************/
        $currentWeekNumber = date('W');
        $nombreConvTotalSC = array_sum([$cadevisSCconvnelly, $cadevisSCconvvincent, $cadevisSCconvamelie, $cadevisSCconvrobin, $cadevisSCconvleo, $cadevisSCconvthibaut]);
        $nombrededevisSCconv = array_sum([$nombrededevisSCconvnelly, $nombrededevisSCconvvincent, $nombrededevisSCconvamelie, $nombrededevisSCconvrobin, $nombrededevisSCconvleo, $nombrededevisSCconvthibaut]);
        $generatedCA = ($nombredecmd > 0) ? round($catransaction / $nombredecmd) : 0;
        $nbDevisConvSC = ($nombrededevisSC > 0) ? round($nombrededevisSCconv / $nombrededevisSC * 100) : 0;
        $CADevMoySC = ($nombrededevisSCconv > 0) ? round($nombreConvTotalSC / $nombrededevisSCconv) : 0;

        // KPI Nelly
        list($kpiPercentQuoteOrderedNelly, $kpiMoyCANelly) = $this->calculateKPI($nombrededevisSCnelly, $nombrededevisSCconvnelly, $cadevisSCconvnelly);

        // KPI Vincent
        list($kpiPercentQuoteOrderedVincent, $kpiMoyCAVincent) = $this->calculateKPI($nombrededevisSCvincent, $nombrededevisSCconvvincent, $cadevisSCconvvincent);

        // KPI Amelie
        list($kpiPercentQuoteOrderedAmelie, $kpiMoyCAAmelie) = $this->calculateKPI($nombrededevisSCamelie, $nombrededevisSCconvamelie, $cadevisSCconvamelie);

        // KPI Robin
        list($kpiPercentQuoteOrderedRobin, $kpiMoyCARobin) = $this->calculateKPI($nombrededevisSCrobin, $nombrededevisSCconvrobin, $cadevisSCconvrobin);

        // KPI Leo
        list($kpiPercentQuoteOrderedLeo, $kpiMoyCALeo) = $this->calculateKPI($nombrededevisSCleo, $nombrededevisSCconvleo, $cadevisSCconvleo);

        // KPI Thibaut
        list($kpiPercentQuoteOrderedThibaut, $kpiMoyCAThibaut) = $this->calculateKPI($nombrededevisSCthibaut, $nombrededevisSCconvthibaut, $cadevisSCconvthibaut);

        $tauxMarqueParSC = $this->calculateTauxMarqueCommercials($db, $scNames, $from, $to);
        $tauxMarqueNelly = isset($tauxMarqueParSC['nelly']) ? $tauxMarqueParSC['nelly'] : 0;
        $tauxMarqueVincent = isset($tauxMarqueParSC['vincent']) ? $tauxMarqueParSC['vincent'] : 0;
        $tauxMarqueAmelie = isset($tauxMarqueParSC['amelie']) ? $tauxMarqueParSC['amelie'] : 0;
        $tauxMarqueRobin = isset($tauxMarqueParSC['robin']) ? $tauxMarqueParSC['robin'] : 0;
        $tauxMarqueLeo = isset($tauxMarqueParSC['leo']) ? $tauxMarqueParSC['leo'] : 0;
        $tauxMarqueThibaut = isset($tauxMarqueParSC['thibaut']) ? $tauxMarqueParSC['thibaut'] : 0;


        $this->html .= '<link rel="stylesheet" type="text/css" href="/modules/statsforecast/views/css/statsforecast.css">';
        $from_format = new DateTime($from);
        $to_format = new DateTime($to);
        $from_format = $from_format->format('d/m/Y');
        $to_format = $to_format->format('d/m/Y');
        $kpiToXlsx = [
            ['Date début', $from_format, 'Date fin', $to_format],
            ['KPI', 'Valeur', '', '']
        ];
        // Header
        $this->html .= '
                    <div class="contener">
                            <button type="button" class="btn btn-primary" id="expandStats" style="position: absolute;left: 20px;"><i class="fas fa-expand"></i></button>
                            <div class="absolute_info"> <br/><span>Semaine actuelle : <br/>s</span>'.$currentWeekNumber.'</div>
                            <div class="wrapper">
                                <div class="header">
                                    <h1 class="h1" id="titreprincipal">KPI</h1>
                                    <div style="width: 200px;">
                                        <p>Type de client</p>
                                        <select name="customer_group" id="customer_group">
                                            <option value="particuliers" '. ($this->customer_group == "particuliers" ? "selected" : "") .'>Particuliers</option>
                                            <option value="professionnels" '. ($this->customer_group == "professionnels" ? "selected" : "") .'>Professionnels</option>
                                            <option value="tous" '. ($this->customer_group == "tous" ? "selected" : "") .'>Tous</option>
                                        </select>
                                    </div>
                                    <div class="date_print">
                                        <span>du : </span>'.$from_format.'
                                        <a class="btn btn-primary buttonExport" style="margin-top: 10px" href="/modules/statsforecast/data/export/kpi.xlsx">Export XLSX KPI</a>
                                        <br>
                                        <span>jusqu\'au : </span>'.$to_format.'
                                    </div>
                                </div>';

        // KPI

        $this->html .= '<div class="contener_analyse">
              <div class="analyse_content">
                    <h3>Nombre de devis</h3>
                    <p class="details">Il s\'agit du nombre de devis réalisé durant la période selectionnée.</p>
                    <p class="result">'.$nombrededevis.'</p>
              </div>
              <div class="analyse_content">
                    <h3>Nombre de paniers</h3>
                    <p class="details">Il s\'agit du nombre de paniers réalisé durant la période selectionnée.</p>
                    <p class="result">'.$nombredepanier.'</p>
              </div>
              <div class="analyse_content">
                    <h3>Nombre de commandes</h3>
                    <p class="details">Il s\'agit du nombre de commandes valides durant la période selectionnée.</p>
                    <p class="result">'.$nombredecmd.'</p>
              </div>
              <div class="analyse_content">
                    <h3>CO généré</h3>
                    <p class="details">Il s\'agit du CO généré durant la période selectionnée.</p>
                    <p class="result data_details">Soit un panier moyen de '.number_format($generatedCA, 0, ',', ' ').' €</p>

                    <p class="result">'.number_format($catransaction, 0, ',', ' ').' €</p>
              </div>
              <div class="analyse_content">
                    <h3>Nombre de SAV</h3>
                    <p class="details">Il s\'agit du nombre messages adressés au service client via SAV.</p>
                    <p class="result">'.$nombredesav.'</p>
              </div>
              <div class="analyse_content service_client">
                    <h3>Nombre de devis par le service client</h3>
                    <p class="details">Il s\'agit du nombre de devis dont le dossier est suivi par un des télé-opérateurs.</p>
                    <p class="result">'.$nombrededevisSC.'</p>
              </div>
              <div class="analyse_content service_client">
                    <h3>Nombre de devis convertis par le service client</h3>
                    <p class="details">Il s\'agit du nombre de devis Service Client validés.</p>
                    <p class="result data_details">'.$nbDevisConvSC.' %</p>
                    <p class="result">'.$nombrededevisSCconv.'</p>
              </div>
              <div class="analyse_content service_client">
                    <h3>CO généré par les devis convertis par le service client</h3>
                    <p class="details">Il s\'agit de la valeur des devis convertis, avec intervention du Service Client.</p>
                    <p class="result data_details">Soit en moyenne '.number_format($CADevMoySC, 0, ',', ' ').' € par devis</p>
                    <p class="result">'.number_format($cadevisSCconv, 0, ',', ' ').' €</p>
              </div>
        </div>';

        array_push($kpiToXlsx, ['Nombre de devis', $nombrededevis]);
        array_push($kpiToXlsx, ['Nombre de paniers', $nombredepanier]);
        array_push($kpiToXlsx, ['Nombre de commandes', $nombredecmd]);
        array_push($kpiToXlsx, ['CO généré', $catransaction]);
        array_push($kpiToXlsx, ['Nombre de SAV', $nombredesav]);
        array_push($kpiToXlsx, ['Nombre de devis par le service client', $nombrededevisSC]);
        array_push($kpiToXlsx, ['Nombre de devis convertis par le service client', $nombrededevisSCconv]);
        array_push($kpiToXlsx, ['CO généré par les devis convertis par le service client', $cadevisSCconv]);
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($kpiToXlsx);
        foreach ($sheet->getColumnIterator() as $column)
        {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save(_PS_MODULE_DIR_.'statsforecast/data/export/kpi.xlsx');
        // Commerciaux
        $this->html .= '<div class="contener_analyse_indiv">
                                          <div class="content_analyse_indiv">
                                                <div class="first_row row">
                                                      <div>
                                                            &nbsp;
                                                      </div>
                                                      <div>
                                                            Nombre de devis
                                                      </div>
                                                      <div>
                                                            % de devis convertis
                                                      </div>
                                                      <div>
                                                            Nombre de devis convertis
                                                      </div>
                                                      <div>
                                                            Taux de marque
                                                      </div>
                                                      <div>
                                                            Valeur des devis convertis
                                                      </div>
                                                      <div>
                                                            Moyenne de CO / devis
                                                      </div>
                                                </div>
                                                <div class="two_row row">
                                                      <div>
                                                            Nelly
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCnelly.'
                                                      </div>
                                                      <div>
                                                            '.$kpiPercentQuoteOrderedNelly .' &nbsp;%
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCconvnelly.'
                                                      </div>
                                                      <div>
                                                            '.$tauxMarqueNelly.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.number_format($cadevisSCconvnelly, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                      <div>
                                                            '.number_format($kpiMoyCANelly, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                </div>
                                                <div class="third_row row">
                                                      <div>
                                                            Vincent
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCvincent.'
                                                      </div>
                                                      <div>
                                                            '.$kpiPercentQuoteOrderedVincent.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCconvvincent.'
                                                      </div>
                                                      <div>
                                                            '.$tauxMarqueVincent.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.number_format($cadevisSCconvvincent, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                      <div>
                                                            '.number_format($kpiMoyCAVincent, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                </div>
                                                <div class="fourth_row row">
                                                      <div>
                                                            Amelie
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCamelie.'
                                                      </div>
                                                      <div>
                                                            '.$kpiPercentQuoteOrderedAmelie.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCconvamelie.'
                                                      </div>
                                                      <div>
                                                            '.$tauxMarqueAmelie.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.number_format($cadevisSCconvamelie, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                      <div>
                                                            '.number_format($kpiMoyCAAmelie, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                </div>
                                                <div class="fifth_row row">
                                                      <div>
                                                            Robin
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCrobin.'
                                                      </div>
                                                      <div>
                                                            '.$kpiPercentQuoteOrderedRobin.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCconvrobin.'
                                                      </div>
                                                      <div>
                                                            '.$tauxMarqueRobin.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.number_format($cadevisSCconvrobin, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                      <div>
                                                            '.number_format($kpiMoyCARobin, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                </div>
                                                <div class="sixth_row row">
                                                      <div>
                                                            Léo
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCleo.'
                                                      </div>
                                                      <div>
                                                            '.$kpiPercentQuoteOrderedLeo.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCconvleo.'
                                                      </div>
                                                      <div>
                                                            '.$tauxMarqueLeo.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.number_format($cadevisSCconvleo, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                      <div>
                                                            '.number_format($kpiMoyCALeo, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                </div>
                                                <div class="seventh_row row">
                                                      <div>
                                                            Thibaut
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCthibaut.'
                                                      </div>
                                                      <div>
                                                            '.$kpiPercentQuoteOrderedThibaut.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.$nombrededevisSCconvthibaut.'
                                                      </div>
                                                      <div>
                                                            '.$tauxMarqueThibaut.'&nbsp;%
                                                      </div>
                                                      <div>
                                                            '.number_format($cadevisSCconvthibaut, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                      <div>
                                                            '.number_format($kpiMoyCAThibaut, 0, ',', ' ').'&nbsp;€
                                                      </div>
                                                </div>
                                          </div>
                                    </div>';

        $this->html .= '    </div>
                        </div>';

        // Calcul des nouvelles métriques Yan 07/08/2025
        /*************** requête CO HT sans livraison *************/
        $sql = $this->buildQuery('SELECT SUM(total_paid_tax_excl - total_shipping_tax_excl) as total FROM `ps_orders`', 'WHERE (`validated_at` IS NOT NULL) AND (`validated_at` BETWEEN "'.$from.'" AND "'.$to.'")', 'ps_orders');
        $result = $db->executeS($sql);
        $coSansLivraison = $result[0]['total'] ? round($result[0]['total']) : 0;

        /*************** requête valeur d'achat *************/
        $sql = $this->buildQuery('SELECT SUM(od.purchase_supplier_price * od.product_quantity) as total FROM `ps_orders` o JOIN `ps_order_detail` od ON o.id_order = od.id_order', 'WHERE (o.`validated_at` IS NOT NULL) AND (o.`validated_at` BETWEEN "'.$from.'" AND "'.$to.'")', 'o');
        $result = $db->executeS($sql);
        $valeurAchat = $result[0]['total'] ? round($result[0]['total']) : 0;

        /*************** requête valeur total livraison *************/
        $sql = $this->buildQuery('SELECT SUM(total_shipping_tax_excl) as total FROM `ps_orders`', 'WHERE (`validated_at` IS NOT NULL) AND (`validated_at` BETWEEN "'.$from.'" AND "'.$to.'")', 'ps_orders');
        $result = $db->executeS($sql);
        $valeurLivraison = $result[0]['total'] ? round($result[0]['total']) : 0;

        /*************** Calcul taux de marque avec requête précise *************/
        $sql = 'SELECT
          ROUND(SUM(ca.ca_ht), 2)  AS ca_ht,
          ROUND(SUM(co.cout_ht), 2) AS cout_ht,
          ROUND((SUM(ca.ca_ht) - SUM(co.cout_ht)) / NULLIF(SUM(ca.ca_ht),0) * 100, 2) AS taux_marque_pct
        FROM (
          SELECT o.id_order, (o.total_products - o.total_discounts_tax_excl) AS ca_ht
          FROM ps_orders o
          WHERE o.validated_at BETWEEN "'.$from.'" AND "'.$to.'"
            AND o.validated_at IS NOT NULL
        ) ca
        JOIN (
          SELECT o.id_order,
                 SUM(COALESCE(NULLIF(pa.wholesale_price,0), NULLIF(p.wholesale_price,0), 0)
                     * od.product_quantity) AS cout_ht
          FROM ps_orders o
          JOIN ps_order_detail od           ON od.id_order = o.id_order
          LEFT JOIN ps_product_attribute pa ON pa.id_product_attribute = od.product_attribute_id
          JOIN ps_product p                 ON p.id_product = od.product_id
          WHERE o.validated_at BETWEEN "'.$from.'" AND "'.$to.'"
            AND o.validated_at IS NOT NULL
          GROUP BY o.id_order
        ) co ON co.id_order = ca.id_order';
        
        $result = $db->executeS($sql);
        $tauxMarque = $result[0]['taux_marque_pct'] ? round($result[0]['taux_marque_pct'], 2) : 0;


        // Nouveaux blocs de métriques
        $this->html .= '<div class="contener_analyse" style="margin-top: 2rem;">
              <div class="analyse_content">
                    <h3>Total CO HT sans livraison</h3>
                    <p class="details">Chiffre d\'affaires total moins les frais de livraison.</p>
                    <p class="result">'.number_format($coSansLivraison, 0, ',', ' ').' €</p>
              </div>
              <div class="analyse_content">
                    <h3>Total valeur d\'achat</h3>
                    <p class="details">Prix d\'achat fournisseur total des produits vendus.</p>
                    <p class="result">'.number_format($valeurAchat, 0, ',', ' ').' €</p>
                </div>
              <div class="analyse_content">
                    <h3>Taux de marque</h3>
                    <p class="details">((Prix catalogue - Prix d\'achat fournisseur) / Prix catalogue) × 100</p>
                    <p class="result">'.$tauxMarque.' %</p>
              </div>
              <div class="analyse_content">
                    <h3>Valeur total HT livraison</h3>
                    <p class="details">Total des frais de livraison facturés.</p>
                    <p class="result">'.number_format($valeurLivraison, 0, ',', ' ').' €</p>
              </div>
        </div>';

        // Tableau de bord
        $this->html .= '<section id="header">
                <div class="flex space-between">
                    <h1 style="margin: auto;">Tableau de bord</h1>
            </section>';
        $this->html .= '<a class="btn btn-primary buttonExport" href="/modules/statsforecast/data/export/stats.xlsx">Export XLSX</a>';
        $this->html .= '<section class="section section-active" id="tab_button1">';
        $this->html .= '<table align="center" bgcolor="#FFFFFF" class="stats">';
        $this->html .= '<thead>';
        $this->html .= '<th align="center" style="color:white" bgcolor="#7d7d7d"><b><u>MAGASIN</u></b></th>';
        $this->html .= '<th align="center" colspan="3" style="color:white" bgcolor="#7d7d7d"><b><u>Jour</u></b></th>';
        $this->html .= '<th align="center" colspan="3" style="color:white" bgcolor="#7d7d7d"><b><u>Semaine</u></b></th>';
        $this->html .= '<th align="center" colspan="3" style="color:white" bgcolor="#7d7d7d"><b><u>Mois</u></b></th>';
        $this->html .= '<th align="center" colspan="3" style="color:white" bgcolor="#7d7d7d"><b><u>Année</u></b></th>';
        $this->html .= '</thead>';
        $this->html .= '<thead>';
        $this->html .= '<th align="center" style="color:white" bgcolor="#7d7d7d"><b><u></u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>Y - 1</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>CO</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>Objectif</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>Y - 1</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>CO</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>Objectif</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>Y - 1</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>CO</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>Objectif</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>Y - 1</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>CO</u></b></th>';
        $this->html .= '<th align="center" colspan="1" style="color:white" bgcolor="#7d7d7d"><b><u>Objectif</u></b></th>';
        $this->html .= '</thead>';
        $shop_list = array("10", "8", "24", "7", "5","11","17","25","2","3","9","23","22","6","21","4");
        $resultdtotal = 0;
        $resultmtotal = 0;
        $resultytotal = 0;
        $resultwtotal = 0;
        $dt = time();
        $dateWeek = array();
        $dateWeekEnd = array();
        $w = date("W");
        $lastYear = date('Y') - 1;
        for($i = 1; $i <= 365; $i++) {
            $week = date("W", mktime(0, 0, 0, 1, $i, date("Y")));
            if($week == $w) {
                for($d = 0; $d < 7; $d++) {
                    $day = date("Y-m-d 00:00:01", mktime(0, 0, 0, 1, $i+$d, date("Y")));
                    $dayEnd = date("Y-m-d 23:59:59", mktime(0, 0, 0, 1, $i+$d, date("Y")));
                    $dateWeek[] = $day;
                    $dateWeekEnd[] = $dayEnd;
                }
                break;
            }
        }
        $shopsLastYear = array();
        $currentMonth = date('m');
        // Last year week start and end
        $last_year_week_start = new DateTime();
        $last_year_week_start->setISODate($lastYear, $w);
        $last_year_week_end = clone $last_year_week_start;
        $last_year_week_end->add(new DateInterval("P6D"));
        $last_year_week_start = $last_year_week_start->format('Y-m-d');
        $last_year_week_end = $last_year_week_end->format('Y-m-d');

        // Current week start and end
        $week_start = new DateTime();
        $week_start->setISODate(date('Y'), $w);
        $week_end = clone $week_start;
        $week_end->add(new DateInterval("P6D"));
        $week_start = $week_start->format('Y-m-d');
        $week_end = $week_end->format('Y-m-d');

        $first_day_this_month = date('Y-m-01');
        $last_day_this_month  = date('Y-m-t');


        $date = new DateTime();
        $day = $date->format('l');
        $date->sub(new DateInterval('P1Y'));
        $dayLastYear = $date->modify('next ' . $day)->format('Y-m-d');
        $totalStats = [
            'lastYear' => [
                'year' => 0,
                'month' => 0,
                'week' => 0,
                'day' => 0
            ],
            'objectives' => [
                'year' => 0,
                'month' => 0,
                'week' => 0,
                'day' => 0
            ]
        ];
        $graphLastYear = [];
        $graphObjectives = [];
        foreach ($shop_list as $shop) {
            $shopsLastYear[$shop] = [
                'year' => 0,
                'month' => 0,
                'week' => 0,
                'day' => 0
            ];
            $shopsObjectives[$shop] = [
                'year' => 0,
                'month' => 0,
                'week' => 0,
                'day' => 0
            ];
            $file = fopen(_PS_MODULE_DIR_.'statsforecast/data/'.$lastYear.'/'.$shop.'.csv', 'r');
            $i = 0;

            while ($line = fgetcsv($file, null, ';')) {
                $shopsLastYear[$shop]['year'] += floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                if (substr($line[0], 0, 7) == $lastYear.'-'.$currentMonth ) {
                    $shopsLastYear[$shop]['month'] += floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                    if ($line[0] <= $lastYear.'-'.date('m-d')) {
                        if (isset($graphLastYear[$i])) {
                            $graphLastYear[$i] += floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                        }else{
                            $graphLastYear[$i] = floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                        }
                        $i++;
                    }
                }
                if ($line[0] >= $last_year_week_start && $line[0] <= $last_year_week_end) {
                    $shopsLastYear[$shop]['week'] += floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                }
                if ($line[0] == $dayLastYear) {
                    $shopsLastYear[$shop]['day'] += floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                }
            }
            $totalStats['lastYear']['day'] += $shopsLastYear[$shop]['day'];
            $totalStats['lastYear']['week'] += $shopsLastYear[$shop]['week'];
            $totalStats['lastYear']['month'] += $shopsLastYear[$shop]['month'];
            $totalStats['lastYear']['year'] += $shopsLastYear[$shop]['year'];

            $file = fopen(_PS_MODULE_DIR_.'statsforecast/data/'.date('Y').'/'.$shop.'.csv', 'r');
            $i = 0;
            while ($line = fgetcsv($file, null, ';')) {
                $shopsObjectives[$shop]['year'] += floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                if (substr($line[0], 0, 7) == date('Y').'-'.$currentMonth ) {
                    $shopsObjectives[$shop]['month'] += floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                    if ($line[0] <= date('Y-m-d')) {
                        if (isset($graphObjectives[$i])) {
                            $graphObjectives[$i] += floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                        }else{
                            $graphObjectives[$i] = floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                        }
                        $i++;
                    }
                }
                if ($line[0] >= $week_start && $line[0] <= $week_end) {
                    $shopsObjectives[$shop]['week'] += floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                }
                if ($line[0] == date('Y-m-d')) {
                    $shopsObjectives[$shop]['day'] += floatval(str_replace(',', '.', str_replace('.', '', $line[1])));
                }

            }
            $totalStats['objectives']['day'] += round($shopsObjectives[$shop]['day']);
            $totalStats['objectives']['week'] += round($shopsObjectives[$shop]['week']);
            $totalStats['objectives']['month'] += round($shopsObjectives[$shop]['month']);
            $totalStats['objectives']['year'] += round($shopsObjectives[$shop]['year']);
        }
        $todayStart = date('Y-m-d').' 00:00:00';
        $todayEnd = date('Y-m-d').' 23:59:59';
        $weekStartDateTime = $week_start.' 00:00:00';
        $weekEndDateTime = $week_end.' 00:00:00';
        $monthStartDateTime = $first_day_this_month.' 00:00:00';
        $monthEndDateTime = $last_day_this_month.' 00:00:00';
        $yearStartDateTime = date('Y').'-01-01 00:00:00';
        $yearEndDateTime = date('Y').'-12-31 00:00:00';
        $rangeStart = min(strtotime($yearStartDateTime), strtotime($weekStartDateTime), strtotime($monthStartDateTime), strtotime($todayStart));
        $rangeEnd = max(strtotime($yearEndDateTime), strtotime($weekEndDateTime), strtotime($monthEndDateTime), strtotime($todayEnd));
        $rangeStartDateTime = date('Y-m-d H:i:s', $rangeStart);
        $rangeEndDateTime = date('Y-m-d H:i:s', $rangeEnd);

        $aggregatedQuery = $this->buildQuery(
            'SELECT o.`id_shop`, s.`name`,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "'.$todayStart.'" AND "'.$todayEnd.'" THEN o.`total_paid` ELSE 0 END), 0) AS CAJOUR,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "'.$weekStartDateTime.'" AND "'.$weekEndDateTime.'" THEN o.`total_paid` ELSE 0 END), 0) AS CASEMAINE,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "'.$monthStartDateTime.'" AND "'.$monthEndDateTime.'" THEN o.`total_paid` ELSE 0 END), 0) AS CAMOIS,
                ROUND(SUM(CASE WHEN o.`validated_at` BETWEEN "'.$yearStartDateTime.'" AND "'.$yearEndDateTime.'" THEN o.`total_paid` ELSE 0 END), 0) AS CAANNEE
            FROM `ps_orders` as o
            LEFT JOIN `ps_shop` as s ON o.id_shop = s.id_shop',
            'WHERE (o.`validated_at` IS NOT NULL) AND (o.`validated_at` BETWEEN "'.$rangeStartDateTime.'" AND "'.$rangeEndDateTime.'")
            GROUP BY o.`id_shop`, s.`name`',
            'o'
        );
        $aggregatedResults = $db->executeS($aggregatedQuery);
        $aggregatedStats = array();
        if (is_array($aggregatedResults)) {
            foreach ($aggregatedResults as $row) {
                $shopIdKey = (string) $row['id_shop'];
                $aggregatedStats[$shopIdKey] = array(
                    'name' => $row['name'],
                    'day' => isset($row['CAJOUR']) ? (float) $row['CAJOUR'] : 0,
                    'week' => isset($row['CASEMAINE']) ? (float) $row['CASEMAINE'] : 0,
                    'month' => isset($row['CAMOIS']) ? (float) $row['CAMOIS'] : 0,
                    'year' => isset($row['CAANNEE']) ? (float) $row['CAANNEE'] : 0,
                );
            }
        }

        $dataToXlsx = [
            ['Date', date('d/m/Y'), 'Heure', date('H:i:s'), '', '', '', '', '', '', '', '', ''],
            ['Magasin', 'Jour CO Y - 1', 'Jour CO', 'Jour Objectif', 'Semaine CO Y - 1', 'Semaine CO', 'Semaine Objectif', 'Mois Y - 1', 'Mois CO', 'Mois Objectif', 'Année Y - 1', 'Année CO', 'Année Objectif']
        ];
        foreach ($shop_list as $shop) {
            $shopAggregated = isset($aggregatedStats[$shop]) ? $aggregatedStats[$shop] : array('name' => null, 'day' => 0, 'week' => 0, 'month' => 0, 'year' => 0);
            $shopName = $shopAggregated['name'] ? $shopAggregated['name'] : 'N/A';
            $dayCA = (float) $shopAggregated['day'];
            $weekCA = (float) $shopAggregated['week'];
            $monthCA = (float) $shopAggregated['month'];
            $yearCA = (float) $shopAggregated['year'];

            $resultdtotal += $dayCA;
            $resultwtotal += $weekCA;
            $resultmtotal += $monthCA;
            $resultytotal += $yearCA;

            $dataToXlsx[] = [
                $shopName,
                $shopsLastYear[$shop]['day'],
                $dayCA,
                $shopsObjectives[$shop]['day'],
                $shopsLastYear[$shop]['week'],
                $weekCA,
                $shopsObjectives[$shop]['week'],
                $shopsLastYear[$shop]['month'],
                $monthCA,
                $shopsObjectives[$shop]['month'],
                $shopsLastYear[$shop]['year'],
                $yearCA,
                $shopsObjectives[$shop]['year']
            ];

            $this->html .= '<tbody>';
            $this->html .= '<tr>';
            $this->html .= '<td align="right" class="boutique"  valign="center">'.$shopName.'</td>';
            // Y - 1
            $this->html .= '<td align="center" valign="center">'.number_format($shopsLastYear[$shop]['day'], 0, ',', ' ').' €</td>';
            // CO
            $this->html .= '<td align="center" valign="center">'.number_format($dayCA, 0, ',', ' ').' €</td>';
            // Objectif
            $this->html .= '<td align="center" valign="center">'.number_format($shopsObjectives[$shop]['day'], 0, ',', ' ').' €</td>';

            // Y - 1
            $this->html .= '<td align="center" valign="center">'.number_format($shopsLastYear[$shop]['week'], 0, ',', ' ').' €</td>';
            // CO
            $this->html .= '<td align="center" valign="center">'.number_format($weekCA, 0, ',', ' ').' €</td>';
            // Objectif
            $this->html .= '<td align="center" valign="center">'.number_format($shopsObjectives[$shop]['week'], 0, ',', ' ').' €</td>';


            // Y - 1
            $this->html .= '<td align="center" valign="center">'.number_format($shopsLastYear[$shop]['month'], 0, ',', ' ').' €</td>';
            // CO
            $this->html .= '<td align="center" valign="center">'.number_format($monthCA, 0, ',', ' ').' €</td>';
            // Objectif
            $this->html .= '<td align="center" valign="center">'.number_format($shopsObjectives[$shop]['month'], 0, ',', ' ').' €</td>';

            // Y - 1
            $this->html .= '<td align="center" valign="center">'.number_format($shopsLastYear[$shop]['year'], 0, ',', ' ').' €</td>';
            // CO
            $this->html .= '<td align="center" valign="center">'.number_format($yearCA, 0, ',', ' ').' €</td>';
            // Objectif
            $this->html .= '<td align="center" valign="center">'.number_format($shopsObjectives[$shop]['year'], 0, ',', ' ').' €</td>';

            $this->html .= '</tr>';
        }

        $this->html .= '<tr style="font-weight:bolder; background: #98e76f;">';
        $this->html .= '<td align="right" valign="center">TOTAL</td>';
        $this->html .= '<td align="center" valign="center">'.number_format($totalStats['lastYear']['day'], 0, ',', ' ').' €</td>';
        $this->html .= '<td align="center" valign="center">'.number_format($resultdtotal, 0, ',', ' ').' €</td>';
        $this->html .= '<td align="center" valign="center">'.number_format($totalStats['objectives']['day'], 0, ',', ' ').' €</td>';

        $this->html .= '<td align="center" valign="center">'.number_format($totalStats['lastYear']['week'], 0, ',', ' ').' €</td>';
        $this->html .= '<td align="center" valign="center">'.number_format($resultwtotal, 0, ',', ' ').' €</td>';
        $this->html .= '<td align="center" valign="center">'.number_format($totalStats['objectives']['week'], 0, ',', ' ').' €</td>';


        $this->html .= '<td align="center" valign="center">'.number_format($totalStats['lastYear']['month'], 0, ',', ' ').' €</td>';
        $this->html .= '<td align="center" valign="center">'.number_format($resultmtotal, 0, ',', ' ').' €</td>';
        $this->html .= '<td align="center" valign="center">'.number_format($totalStats['objectives']['month'], 0, ',', ' ').' €</td>';

        $this->html .= '<td align="center" valign="center">'.number_format($totalStats['lastYear']['year'], 0, ',', ' ').' €</td>';
        $this->html .= '<td align="center" valign="center">'.number_format($resultytotal, 0, ',', ' ').' €</td>';
        $this->html .= '<td align="center" valign="center">'.number_format($totalStats['objectives']['year'], 0, ',', ' ').' €</td>';
        $this->html .= '</tbody>';
        $this->html .= '</table>';
        $this->html .= '</section>';
        array_push($dataToXlsx, [
            'TOTAL',
            $totalStats['lastYear']['day'],
            $resultdtotal,
            $totalStats['objectives']['day'],
            $totalStats['lastYear']['week'],
            $resultwtotal,
            $totalStats['objectives']['week'],
            $totalStats['lastYear']['month'],
            $resultmtotal,
            $totalStats['objectives']['month'],
            $totalStats['lastYear']['year'],
            $resultytotal,
            $totalStats['objectives']['year']
        ]);
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($dataToXlsx);
        foreach ($sheet->getColumnIterator() as $column)
        {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $writer->save(_PS_MODULE_DIR_.'statsforecast/data/export/stats.xlsx');
        $this->html .= '<script src="/modules/statsforecast/views/js/gauge.js"></script>';
        $this->html .= '<div class="gaugesContainer">';
        $this->html .= '<div class="gauge">
                            <canvas id="daily"></canvas>
                            <span style="margin: auto; font-size: 18px">Journalier</span>
                        </div>';

        $this->html .= '<script>
                        var opts = {
                            angle: 0,
                            lineWidth: 0.10,
                            radiusScale: 1,
                            pointer: {
                                length: 0.5,
                                strokeWidth: 0.035,
                                color: "#000000"
                            },
                            limitMax: false,
                            limitMin: false,
                            generateGradient: true,
                            highDpiSupport: true,
                            staticLabels: {
                            font: "16px sans-serif",
                            labels: [0, '.$totalStats['lastYear']['day'].', '.$totalStats['objectives']['day'].'],
                            color: "#000000",
                            fractionDigits: 0
                            },
                            staticZones: [
                                        {strokeStyle: "rgb(255,0,0)", min: 0, max: '.($totalStats['lastYear']['day'] >= $totalStats['objectives']['day'] ? $totalStats['objectives']['day'] * 0.85 : $totalStats['lastYear']['day']).'},
                                        {strokeStyle: "rgb(255,165,0)", min: '.($totalStats['lastYear']['day'] >= $totalStats['objectives']['day'] ? $totalStats['objectives']['day'] * 0.85 : $totalStats['lastYear']['day']).', max: '.$totalStats['objectives']['day'].'},
                                        {strokeStyle: "rgb(0,255,0)", min: '.$totalStats['objectives']['day'].', max: '.$totalStats['objectives']['day'] * 1.15 .'}
                                    ],
                                    percentColors: [[0.0, "#a9d70b" ], [80, "#ff0000"]],
                                    renderTicks: {
                                divisions: 5,
                                        divWidth: 1.1,
                                        divLength: 0.7,
                                        divColor: "#333333",
                                        subDivisions: 3,
                                        subLength: 0.5,
                                        subWidth: 0.6,
                                        subColor: "#666666"
                                    }
                                };
                                var target = document.getElementById("daily");
                                var gaugeDaily = new Gauge(target).setOptions(opts);
                                gaugeDaily.maxValue = '.$totalStats['objectives']['day'] * 1.15 .';
                                gaugeDaily.setMinValue(0);
                                gaugeDaily.animationSpeed = 47;
                                gaugeDaily.set('.$resultdtotal.');
                                target.title = gaugeDaily.value;
                            </script>';
        $this->html .= '<div class="gauge">
                            <canvas id="weekly"></canvas>
                            <span style="margin: auto; font-size: 18px">Hebdomadaire</span>
                        </div>';
        $this->html .= '<script>
                        var opts = {
                            angle: 0,
                            lineWidth: 0.10,
                            radiusScale: 1,
                            pointer: {
                                length: 0.5,
                                strokeWidth: 0.035,
                                color: "#000000"
                            },
                            limitMax: false,
                            limitMin: false,
                            generateGradient: true,
                            highDpiSupport: true,
                            staticLabels: {
                            font: "16px sans-serif",
                            labels: [0, '.$totalStats['lastYear']['week'].', '.$totalStats['objectives']['week'].'],
                            color: "#000000",
                            fractionDigits: 0
                            },
                            staticZones: [
                                        {strokeStyle: "rgb(255,0,0)", min: 0, max: '.($totalStats['lastYear']['week'] >= $totalStats['objectives']['week'] ? $totalStats['objectives']['week'] * 0.85 : $totalStats['lastYear']['week']).'},
                                        {strokeStyle: "rgb(255,165,0)", min: '.($totalStats['lastYear']['week'] >= $totalStats['objectives']['week'] ? $totalStats['objectives']['week'] * 0.85 : $totalStats['lastYear']['week']).', max: '.$totalStats['objectives']['week'].'},
                                        {strokeStyle: "rgb(0,255,0)", min: '.$totalStats['objectives']['week'].', max: '.$totalStats['objectives']['week'] * 1.15 .'}
                                    ],
                                    percentColors: [[0.0, "#a9d70b" ], [80, "#ff0000"]],
                                    renderTicks: {
                                divisions: 5,
                                        divWidth: 1.1,
                                        divLength: 0.7,
                                        divColor: "#333333",
                                        subDivisions: 3,
                                        subLength: 0.5,
                                        subWidth: 0.6,
                                        subColor: "#666666"
                                    }
                                };
                                var target = document.getElementById("weekly");
                                var gaugeHebdo = new Gauge(target).setOptions(opts);
                                gaugeHebdo.maxValue = '.$totalStats['objectives']['week'] * 1.15 .';
                                gaugeHebdo.setMinValue(0);
                                gaugeHebdo.animationSpeed = 47;
                                gaugeHebdo.set('.$resultwtotal.');
                                target.title = gaugeHebdo.value;
                            </script>';

        $this->html .= '</div>';

        $this->html .= '<div>
                          <canvas id="chartdiv"></canvas>
                        </div>';
        $startDate = new DateTime('first day of this month');
        $endDate = new DateTime('today');
        $endDate->modify('+1 day');
        $currentDate = clone $startDate;
        $allDates = array();

        while ($currentDate <= $endDate) {
            $allDates[] = $currentDate->format('d');
            $currentDate->modify('+1 day');
        }
        //Array CO Current Month
        $sql = $this->buildQuery('SELECT ROUND(SUM(o.`total_paid`),0) AS CAMOIS FROM `ps_orders` as o', 'WHERE (o.`validated_at` IS NOT NULL) AND (o.`validated_at` BETWEEN "'.$first_day_this_month.'" AND "'.date('Y-m-d').' 23:59:59'.'") GROUP BY DAY(o.`validated_at`)', 'o');
        $resultGraph = $db->executeS($sql);
        $graph = array();
        foreach ($resultGraph as $value) {
            $graph[] = $value['CAMOIS'];
        }

        $this->context->controller->addJS('https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js');

        $this->html .= '<script>
                document.getElementById("customer_group").addEventListener("change", function() {
                    var selectedValue = document.getElementById("customer_group").value;
                
                    var currentURL = window.location.href;
                
                    var separator = currentURL.indexOf("?") !== -1 ? "&" : "?";
                
                    var customerGroupIndex = currentURL.indexOf("customer_group=");
                
                    if (customerGroupIndex !== -1) {
                        var ampersandIndex = currentURL.indexOf("&", customerGroupIndex);
                        if (ampersandIndex !== -1) {
                            currentURL = currentURL.substring(0, customerGroupIndex) + "customer_group=" + selectedValue + currentURL.substring(ampersandIndex);
                        } else {
                            currentURL = currentURL.substring(0, customerGroupIndex) + "customer_group=" + selectedValue;
                        }
                    } else {
                        currentURL += separator + "customer_group=" + selectedValue;
                    }
                
                    window.location.href = currentURL;
                });


                document.addEventListener("DOMContentLoaded", function() {
                    const ctx = document.getElementById("chartdiv");
                    const labels = ["'.implode('","', $allDates).'"];
                    const data = {
                        labels: labels,
                        datasets: [{
                            axis: "y",
                            label: "CO",
                            data: ["'.implode('","', $graph).'"],
                            fill: false,
                            borderWidth: 1,
                            borderColor: "rgb(255, 205, 86)",
                            backgroundColor: "rgba(255, 205, 86, 0.2)"
                        },
                        {
                            axis: "y",
                            label: "N - 1",
                            data: ["'.implode('","', $graphLastYear).'"],
                            fill: false,
                            borderWidth: 1,
                            borderColor: "rgb(255, 99, 132)",
                            backgroundColor: "rgba(255, 99, 132, 0.2)"
                        },
                        {
                            axis: "y",
                            label: "Objectif",
                            data: ["'.implode('","', $graphObjectives).'"],
                            fill: false,
                            borderWidth: 1,
                            borderColor: "rgb(75, 192, 192)",
                            backgroundColor: "rgba(75, 192, 192, 0.2)"
                        }]
                    };

                    const myChart = new Chart(ctx, {
                        type: "line",
                        data: data
                    });
                });
                // on mobile update after page load
                
            </script>';

        $this->html .= '<script>
            $("#expandStats").click(function() {
                $(".sidebar").toggleClass("hide");
                $("#statsContainer").toggleClass("w-100");
            });
        </script>';
        $dataLayalty = $this->getLoyaltyData($catransaction);
        $this->html .= '<section id="header">
                <div class="flex space-between">
                    <h1 style="margin: auto;">Fidélisation client</h1>
            </div></section>';
        $this->html .= '<div class="contener_analyse" style="margin-top: 1rem">
              <div class="analyse_content">
                    <h3>1 commande</h3>
                    <p class="details">Nombre de clients : '.$dataLayalty["one_order"]["count"].'</p>
                    <p class="details">'.$dataLayalty["one_order"]["percentage"].'%</p>
                    <p class="result">CO : '. number_format($dataLayalty["one_order"]["total_spent"], 0, ',', ' ') .'€</p>
              </div>
              <div class="analyse_content">
                    <h3>2 commandes</h3>
                    <p class="details">Nombre de clients : '.$dataLayalty["two_orders"]["count"].'</p>
                    <p class="details">'.$dataLayalty["two_orders"]["percentage"].'%</p>
                    <p class="result">CO : '. number_format($dataLayalty["two_orders"]["total_spent"], 0, ',', ' ') .'€</p>
              </div>
              <div class="analyse_content">
                    <h3>3 commandes</h3>
                    <p class="details">Nombre de clients : '.$dataLayalty["three_orders"]["count"].'</p>
                    <p class="details">'.$dataLayalty["three_orders"]["percentage"].'%</p>
                    <p class="result">CO : '. number_format($dataLayalty["three_orders"]["total_spent"], 0, ',', ' ') .'€</p>
              </div>
              <div class="analyse_content">
                    <h3>4 commandes</h3>
                    <p class="details">Nombre de clients : '. $dataLayalty["four_orders"]["count"] .'</p>
                    <p class="details">'.$dataLayalty["four_orders"]["percentage"].'%</p>
                    <p class="result">CO : '. number_format($dataLayalty["four_orders"]["total_spent"], 0, ',', ' ') .'€</p>
              </div>
        </div>';
        return $this->html;
    }

    private function countResults($db, $sql) {
        $result = $db->executeS($sql);
        if (!$result) {
            return 0;
        }

        if (isset($result[0]['total'])) {
            return (int) $result[0]['total'];
        }

        return count($result);
    }

    /*************** Fonction générique pour exécuter une requête SQL et obtenir une valeur numérique *************/
    private function executeScalar($db, $sql) {
        $result = $db->executeS($sql);
        return $result ? round($result[0]['total']) : 0;
    }

    // Fonction pour calculer les KPIs
    function calculateKPI($nombrededevisSC, $nombrededevisSCconv, $cadevisSCconv) {
        $kpiPercentQuoteOrdered = ($nombrededevisSC > 0) ? round($nombrededevisSCconv / $nombrededevisSC * 100) : 0;
        $kpiMoyCA = ($cadevisSCconv > 0) ? round($cadevisSCconv / $nombrededevisSCconv) : 0;
        return [$kpiPercentQuoteOrdered, $kpiMoyCA];
    }

    // Fonction pour calculer les taux de marque pour plusieurs commerciaux
    protected function calculateTauxMarqueCommercials($db, $scNames, $from, $to)
    {
        if (empty($scNames)) {
            return array();
        }

        $selects = array();
        foreach ($scNames as $scName) {
            $lowerName = Tools::strtolower($scName);
            $escapedName = pSQL($scName);
            $selects[] = 'SUM(CASE WHEN ca.name LIKE "%'.$escapedName.'%" THEN ca.ca_ht ELSE 0 END) AS ca_'.$lowerName;
            $selects[] = 'SUM(CASE WHEN ca.name LIKE "%'.$escapedName.'%" THEN co.cout_ht ELSE 0 END) AS cout_'.$lowerName;
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

        $result = $db->executeS($sql);
        if (!$result) {
            return array();
        }

        $result = $result[0];
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

    function buildQuery($request, $requestWhere, $linkedTable) {
        if ($this->customer_group == 'particuliers') {
            $request .= ' JOIN '._DB_PREFIX_.'customer c ON c.id_customer = '.$linkedTable.'.id_customer AND c.id_default_group != 10 '.$requestWhere;
        }else if ($this->customer_group == 'professionnels') {
            $request .= ' JOIN '._DB_PREFIX_.'customer c ON c.id_customer = '.$linkedTable.'.id_customer AND c.id_default_group = 10 '.$requestWhere;
        } else {
            $request .= ' '.$requestWhere;
        }
        return $request;
    }

    protected function getLoyaltyData($catransaction)
    {
        $employee = $this->context->employee;

        $from = max(_PS_CREATION_DATE_.' 00:00:00', $employee->stats_date_from.' 00:00:00');
        $to = $employee->stats_date_to.' 23:59:59';
        $sql = 'SELECT id_customer, COUNT(id_order) AS orders_count, SUM(total_paid) AS total_spent,
                   MAX(validated_at) AS last_order_date, MIN(validated_at) AS first_order_date, validated_at
            FROM ' . _DB_PREFIX_ . 'orders
            WHERE (`validated_at` IS NOT NULL) AND (validated_at BETWEEN "' . $from . '" AND "' . $to . '")
             GROUP BY id_customer';

        $results = Db::getInstance()->executeS($sql);
        $data = array(
            'one_order' => array('count' => 0, 'total_spent' => 0),
            'two_orders' => array('count' => 0, 'total_spent' => 0),
            'three_orders' => array('count' => 0, 'total_spent' => 0),
            'four_orders' => array('count' => 0, 'total_spent' => 0),
            'avg_time_between_orders' => 0,
        );

        // count all customers
        $sql = 'SELECT COUNT(DISTINCT c.id_customer) as total_customers
        FROM ' . _DB_PREFIX_ . 'customer c
        INNER JOIN ' . _DB_PREFIX_ . 'orders o
        ON c.id_customer = o.id_customer WHERE o.`validated_at` IS NOT NULL';

        $time_between_orders = array();

        foreach ($results as $row) {
            switch ($row['orders_count']) {
                case 1:
                    $data['one_order']['count']++;
                    $data['one_order']['total_spent'] += $row['total_spent'];
                    break;
                case 2:
                    $data['two_orders']['count']++;
                    $data['two_orders']['total_spent'] += $row['total_spent'];
                    $time_between_orders[] = strtotime($row['last_order_date']) - strtotime($row['first_order_date']);
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
        $data['one_order']['percentage'] = $catransaction > 0 ? round($data['one_order']['total_spent'] / $catransaction * 100, 3) : 0;
        $data['two_orders']['percentage'] = $catransaction > 0 ? round($data['two_orders']['total_spent'] / $catransaction * 100, 3) : 0;
        $data['three_orders']['percentage'] = $catransaction > 0 ? round($data['three_orders']['total_spent'] / $catransaction * 100, 3) : 0;
        $data['four_orders']['percentage'] = $catransaction > 0 ? round($data['four_orders']['total_spent'] / $catransaction * 100, 3) : 0;

        if (count($time_between_orders) > 0) {
            $data['avg_time_between_orders'] = array_sum($time_between_orders) / count($time_between_orders) / 86400; // Convert to days
        }

        return $data;
    }

}
