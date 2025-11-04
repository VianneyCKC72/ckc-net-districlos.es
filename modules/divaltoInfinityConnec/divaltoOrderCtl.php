<?php
// Districlos
// update `ps_orders` set dic_ficcsv = NULL where date_add >'2017-01-01' and date_add<'2017-02-01'
// https://www.cloture-discount.fr/modules/divaltoInfinityConnec/divaltoOrderCtl.php?token=WmrTYUSg&step=01
// https://new.cloture-discount.fr/modules/divaltoInfinityConnec/divaltoOrderCtl.php?token=WmrTYUSg&step=01
error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "Debut traitement des commandes<br>";
require_once dirname(__FILE__) . '/../../config/config.inc.php';

echo "Lancement traitement des commandes<br>";
Context::getContext()->shop->setContext(Shop::CONTEXT_ALL);
//if (substr(_COOKIE_KEY_, 34, 8) != Tools::getValue('token'))
//    die;
$step = Tools::getValue('step');
echo 'popopopo';

echo "Traitement des commandes en cours. Etape ".$step."<br>";

// Initialize DB Connection
$db = \Db::getInstance();

if ($step == '01') {
//    On exclut les commandes en attente de paiement et annulées
    $sql = "SELECT id_order FROM "._DB_PREFIX_."orders o WHERE dic_ficcsv IS NULL AND EXISTS (
    SELECT 1
    FROM "._DB_PREFIX_."order_history oh
    INNER JOIN "._DB_PREFIX_."order_state os ON oh.id_order_state = os.id_order_state
    WHERE os.paid = 1
    AND oh.id_order = o.id_order
)";

    $datasl = $db->executeS($sql);

    foreach ($datasl AS $k => $row) {
        echo 'N° Id order à créer ? '.$row['id_order'].'<br>';
        DivaltoCreateOrder($row['id_order']);
    }
    echo "Fin traitement des commandes";
    return;
}

if ($step == '02') {
    echo "Suppression des fichier<br>";
    $mask = _PS_MODULE_DIR_."divaltoInfinityConnec/divalto/orders/order_*.csv";
    array_map("unlink", glob($mask));
}

function DivaltoCreateOrder($id_order)
{
//echo "N° order ".dirname(__FILE__)."<br>";
    echo "SQL ".$id_order."<br>";

    $OrderCsv    = _PS_MODULE_DIR_."divaltoInfinityConnec/divalto/orders/order_".$id_order.".csv";
    $OrderSvgCsv = _PS_MODULE_DIR_."divaltoInfinityConnec/divalto/orders_sauvegarde/order_".$id_order.".csv";
    echo "SQL ".$OrderSvgCsv."<br>";

    if (file_exists($OrderSvgCsv)) {
        $filetime = date("Y-m-d", filemtime($OrderSvgCsv));
        $timeold  = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
        echo "<H3> Commande déja validée le ".$filetime.'</H3><BR>';
        //if ($filetime > $timeold) return; else unlink($OrderSvgCsv);
        // Nico juillet 2020 - on supprime le fichier quoi qu'il arrive pour pouvoir le re-synchroniser plusieurs fois si besoin
        //unlink($OrderSvgCsv);
    }

    if (file_exists($OrderCsv)) unlink($OrderCsv);


    $lang = Configuration::get('PS_LANG_DEFAULT');

    $sql   = "select ord.reference, ord.id_store, ord.date_add, ord.date_upd, ord.payment, car.name as carriername , cus.email as cusemail, cus.lastname, cus.firstname, cus.dic_tiers, cus.dic_tiers_contact, adr.id_address as alias, adr.company as company, adr.firstname as firstliv, adr.lastname as lastliv, adr.address1, adr.address2, adr.postcode, adr.city,
    adr.other as other, adr.phone as phoneliv, adr.phone_mobile as mobileliv, cou.iso_code as paysliv, ord.total_shipping_tax_excl,
    ord.total_discounts_tax_excl, ord.total_wrapping_tax_excl, ord.invoice_number, ord.delivery_number, ord.invoice_date, ord.delivery_date, ord.current_state, grp.price_display_method,
    ord.total_discounts_tax_incl, ord.total_wrapping_tax_incl, ord.total_shipping_tax_incl, ord.total_paid, ord.id_customer, cus.id_default_group, car.Ref_Carrier_Divalto, cus.siret, nf.code
    FROM "._DB_PREFIX_."orders AS ord 
    LEFT JOIN "._DB_PREFIX_."carrier AS car ON (ord.id_carrier = car.id_carrier)
    LEFT JOIN "._DB_PREFIX_."customer AS cus ON (ord.id_customer = cus.id_customer)
    LEFT JOIN "._DB_PREFIX_."group AS grp ON (cus.id_default_group = grp.id_group)
    LEFT JOIN "._DB_PREFIX_."address AS adr ON (ord.id_customer = adr.id_customer and ord.id_address_delivery = adr.id_address)
    LEFT JOIN "._DB_PREFIX_."country AS cou ON (cou.id_country = adr.id_country)
    LEFT JOIN "._DB_PREFIX_."code_naf AS nf ON (cus.naf = nf.code)
    where ord.id_order='$id_order'";
    $datas = Db::getInstance()->getRow($sql);
    //echo "SQL ".$sql."<br>";

// attente 1 minutes pour laisser le temps de valider toutes les lignes de commandes
    $limit_time = time() - (60);
    $timel      = date('Y-m-d H:i:s', $limit_time);
//echo "date upd ".$datas['date_upd']." date limite ".$timel."<br>";
    if ($datas['date_upd'] > $timel) {
        echo "Date limte pas atteinte<br>";
        return;
    }

    if ($datas['dic_tiers']=='')
    {
        $datas['dic_tiers']=Configuration::get('dic_client_particulier_fr');
        $datas['dic_tiers_contact']=Configuration::get('dic_contact_particulier_fr');
    }

    $sql    = "select adr.id_address as aliasi, adr.company as companyi, adr.firstname as firstlivi, adr.lastname as lastlivi, adr.address1, adr.address2, adr.postcode, adr.city,
    adr.other as otheri, adr.phone as phonelivi, adr.phone_mobile as mobilelivi, cou.iso_code as payslivi
FROM "._DB_PREFIX_."orders AS ord 
INNER JOIN "._DB_PREFIX_."address AS adr ON (ord.id_customer = adr.id_customer and ord.id_address_invoice = adr.id_address)
INNER JOIN "._DB_PREFIX_."country AS cou ON (cou.id_country = adr.id_country)
where ord.id_order='$id_order'";
    $datasi = Db::getInstance()->getRow($sql);
//echo "SQL ".$sql."<br>";

    $sql    = "select message FROM "._DB_PREFIX_."message where id_order='$id_order'";
    $datasm = Db::getInstance()->getRow($sql);
//echo "SQL ".$sql."<br>";

// ligne entêtes
// N° Commande Prestashop
// Message du client
// Nom du client
// Prénom du client
// Code client Divalto (Si le client est un client Pro)
// Code contact Divalto (Si le client est un client Pro)
// Code adresse livraison (Alias) le 29 08 18 (id_address)
// Société - Adresse livraison
// Prénom du client livraison
// Nom livraison
// Adresse 1 livraison
// Adresse 2 livraison
// Code postal livraison
// Ville livraison
// Autre informations livraison
// Téléphone livraison
// Mobile livraison
// Pays livraison
// Code adresse facturation (Alias) le 29 08 18 (id_address)
// Société - Adresse facturation
// Prénom facturation
// Nom facturation
// Adresse 1 facturation
// Adresse 2 facturation
// Code postal facturation
// Ville facturation
// Autres informations facturation
// Téléphone facturation
// Mobile facturation
// Pays facturation
// Date de création de commande Prestashop
// Montant port Divalto (pas utilisé en standard)
// Montant frais Divalto (pas utilisé en standard)
// Mode de paiement
// Email
// Total transport HT
// Nom transporteur
// Remise générale HT (Bon de réduction)
// Total frais emballage HT
// Dossier Divalto
// N° de Facture Prestashop
// Date de facture Prestashop
// N° de BL Prestashop
// Date de BL Prestashop
// Id Status de la commande Prestashop
// Total transport TTC
// Remise générale TTC (Bon de reduction)
// Total frais emballage TTC
// Commande enregistrée HT ou TTC (H ou T)
// Reference de commande
// Montant Payé (total_paid)
// Code client Prestashop (id_customer)
// Code Transport Divalto
// Etablissement Divalto
// Dépôt Divalto
// Type du site (B2C ou B2B)
// Siret
// Code NAF


    $datas['other']   = str_replace(CHR(13).CHR(10), " - ", $datas['other']);
    $datas['other']   = str_replace(CHR(13), " - ", $datas['other']);
    $datas['other']   = str_replace(CHR(10), " - ", $datas['other']);
    $datasi['otheri'] = str_replace(CHR(13).CHR(10), " - ", $datasi['otheri']);
    $datasi['otheri'] = str_replace(CHR(13), " - ", $datasi['otheri']);
    $datasi['otheri'] = str_replace(CHR(10), " - ", $datasi['otheri']);

//echo 'avant '.$datasm['message'].'<br>';
    if ($datasm) {
        $datasm['message'] = str_replace(CHR(13).CHR(10), " - ", $datasm['message']);
        $datasm['message'] = str_replace(CHR(13), " - ", $datasm['message']);
        $datasm['message'] = str_replace(CHR(10), " - ", $datasm['message']);
    }



// N° de commande ou référence // paramétrage
    $datas['dic_tiers_contact']=Configuration::get('dic_numero_commande');
    $num_cde=$id_order;

// Commande enregistrée HT
    $cde_ttc='H';

    /* LOIC JESTIN 12/2020
     *
     *
     * Fusion des dossiers Divalto
     * Remplacement du dossier par : Etablissement, Dépôt, type
     *
     * Ces données sont saisies pour chaque boutique dans la rubrique "Gérer Les paramétrages" du module divaltoInfinityConnec
     *
     */

// Dossier Divalto
    $diva_dossier='';
    $couples=explode(' ',Configuration::get('dic_societe_association'));
    foreach ($couples as $couple)
    {
        $soc=explode(',',$couple);
        if ($soc[0]==$datas['id_store']) {
            $diva_dossier = $soc[1];
            echo $soc[1] . ' ' . $datas['id_store'] . '<br>';

            // On récupère l'ETB, le Dépôt et le type
            $diva_etb = $soc[2];
            $diva_depot = $soc[3];
            // Vérifie si le client est un professionnel
            if ($datas['id_default_group'] == 10) {
                // Affiche "B2B" au lieu de "B2C"
                $diva_type = 'B2B';
            }   else {
                $diva_type = $soc[4];
            }
        }
    }

    echo "Dossier Divalto " . $diva_dossier . "<br>";
    if ($diva_dossier=='')
    {
        echo "Dossier Divalto ".$datas['id_store']." inconnu. le fichier ".$num_cde.".csv non créé.<br><br>";
        $sql = "update "._DB_PREFIX_."orders set `dic_ficcsv`=1 where `id_order`=".$id_order;
        //echo "SQL ".$sql."<br>";
        Db::getInstance()->Execute($sql);
        return;
    }
    $total_shipping_tax_excl = $datas['total_shipping_tax_incl'] / (1 + (20 / 100));

    // Suppression du point dans le code NAF
    $code_naf = $datas['code'];
    $code_sans_points = str_replace('.', '', $code_naf);

    // Modification du payment pour MARKETPLACE LEROYMERLIN
    $payment = $datas['payment'];
    $carriername = $datas['carriername'];
    if ($datas['carriername'] == 'MARKETPLACE LEROYMERLIN') {
        $payment = 'Marketplace';
        $carriername = 'Marketplace';
    }

    $code_ldd = ($datas['Ref_Carrier_Divalto'] == 'LAD') ? 'LDD' : '';

    $entete = array($num_cde, 'Temporairement désactivé', $datas['lastname'], $datas['firstname'], $datas['dic_tiers'], $datas['dic_tiers_contact'],
        $datas['alias'], $datas['company'], $datas['firstliv'], $datas['lastliv'], $datas['address1'], $datas['address2'], $datas['postcode'], $datas['city'],
        $datas['other'], $datas['phoneliv'], $datas['mobileliv'], $datas['paysliv'],
        $datasi['aliasi'], $datasi['companyi'], $datasi['firstlivi'], $datasi['lastlivi'], $datasi['address1'], $datasi['address2'], $datasi['postcode'], $datasi['city'],
        $datasi['otheri'], $datasi['phonelivi'], $datasi['mobilelivi'], $datasi['payslivi'],
        $datas['date_add'], "", "", $payment, $datas['cusemail'], $total_shipping_tax_excl, $carriername,
        $datas['total_discounts_tax_excl'], $datas['total_wrapping_tax_excl'],
        $diva_dossier, $datas['invoice_number'], $datas['invoice_date'],
        $datas['delivery_number'], $datas['delivery_date'], $datas['current_state'],
        $datas['total_shipping_tax_incl'], $datas['total_discounts_tax_incl'], $datas['total_wrapping_tax_incl'], $cde_ttc, $datas['reference']
    , $datas['total_paid'], $datas['id_customer'], $datas['Ref_Carrier_Divalto'], $diva_etb, $diva_depot, $diva_type, '2', $datas['siret'], $code_sans_points, $code_ldd
    );

    foreach ($entete as $k => $champs) {
        //echo "champs ".$champs." k ".$k."<br>";
        $champs = strtr($champs, '"', '\'');
        $champs = strtr($champs, ';', ':');
        echo "champs ".$champs." k ".$k."<br>";
        $entete[$k] = utf8_decode($champs);
    }

// ligne produits
// N° Commande Prestashop
// Désignation du produit
// Quantité commandée
// Prix unitaire sans remise HT (pas utilisé en standard)
// % de remise (pas utilisé en standard)
// Montant de remise (pas utilisé en standard)
// Référence du produit
// Taux de taxe
// Eco taxe (Deee)
// Code Déclinaison Prestashop
// Référence fournisseur du produit
// Prix unitaire Net HT
// Prix unitaire Net TTC
// id product Prestashop

    $sql = "SELECT id_order, product_name, product_quantity, product_price, reduction_percent, reduction_amount, 
    product_reference, tax_rate, ecotax, product_attribute_id as sref1, 
    product_supplier_reference as sref2, unit_price_tax_excl, unit_price_tax_incl,reduction_amount_tax_excl, reduction_amount_tax_incl,product_id
    FROM "._DB_PREFIX_."order_detail 
    where id_order = '$id_order' order by id_order_detail";

    $datasl = Db::getInstance()->ExecuteS($sql);


    $detail = array();

    foreach ($datasl AS $k => $row) {

        //ajout produit customisation 09-02-23
        $monProduit = $row["product_id"];
        $sql = "SELECT GROUP_CONCAT(CONCAT(OP.`name`,':', OP.`value`, ' / ', OP.`secondary_value`)) AS CustomValue, PO.`price` AS CustomPrice
        FROM ps_cart_product AS CP 
        INNER JOIN ps_dynamicproduct_input AS PO ON PO.`id_customization` = CP.`id_customization`
        INNER JOIN ps_dynamicproduct_input_field AS OP ON OP.`id_input` = PO.`id_input`
        LEFT JOIN ps_orders AS OA ON OA.`id_cart` = CP.`id_cart`
        WHERE (
            (CP.`id_product` IN (1288, 1289, 1290) AND OP.`type` IN ('1', '12', '16', '8')) 
            OR 
            (CP.`id_product` NOT IN (1288, 1289, 1290) AND OP.`type` IN ('1', '12', '16'))
        )
        AND OP.`id_field` != '0' 
        AND OA.`id_order` = '$id_order'
        AND CP.`id_product` = '$monProduit'
        GROUP BY CP.`id_cart`, PO.`price`";

        $nameProductCustom = Db::getInstance()->getRow($sql);

        if (!$nameProductCustom || $nameProductCustom['CustomValue'] == null) {
            $maCustomisation = "";
            $maCustomisationPrice = $row['unit_price_tax_incl'];
        }
        else {
            $maCustomisation = " | " . $nameProductCustom['CustomValue'];
            $maCustomisationPrice = $nameProductCustom['CustomPrice'];
            if (strpos($nameProductCustom['CustomValue'], 'largeur_sur_mesure') !== false && ($row['product_id'] == 1288 || $row['product_id'] == 1289 || $row['product_id'] == 1290)) {
                $parts = explode(',', $nameProductCustom['CustomValue']);
                foreach ($parts as $part) {
                    if (strpos($part, 'largeur_sur_mesure') !== false) {
                        list(, $value) = explode(':', $part);
                        $largeurSurMesure = explode(' /', $value)[0];
                        break;
                    }
                }
            }
        }

        foreach ($row as $l => $champs) {
            $champs  = strtr($champs, '"', '\'');
            $champs  = strtr($champs, ';', ':');
            // LOIC 01/2021 : Nettoyage du product_name - Suppression des string "Couleur : " et "Hauteur :" et "Avec/sans occultation :"
            if ($l == 'product_name') {
                $champs = str_replace('Couleur : ', '', $champs);
                $champs = str_replace('Hauteur : Ht', 'Ht', $champs);
                $champs = str_replace('Avec/Sans occultation :', '', $champs);

                //nom avec valeur personnalisé
                $champs = $champs . $maCustomisation;

            }
            // Si customisation : prends prix custom
            if ($l == 'product_price' || $l == 'unit_price_tax_excl') {
                $champs = number_format((float)($maCustomisationPrice / 1.2),3, '.', '');
            }
            // SI TTC
            if ($l == 'unit_price_tax_incl') { // SI TTC
                $champs = $maCustomisationPrice;
            }

            // AJOUT DATABASE SUBSTITUTION KEV 2023
            /*$sql2 = "SELECT shop, old_ref, new_ref, new_name FROM "._DB_PREFIX_."newrefshop where shop = '$diva_depot'" ;
            $datas2 = Db::getInstance()->ExecuteS($sql2);

            if ($l == 'product_reference' || $l == 'product_name') {
                foreach ($datas2 as $k2 => $row2) {
                    if ($row["product_reference"] == $row2["old_ref"]) {
                        // echo "reference commune detectée : \n \n ";

                        // print_r("print r de ma référence de base  :  ".$champs."\n");
                        if ($l == "product_name"){
                            $name = str_replace(PHP_EOL, "", $row2["new_name"]);
                            $name = str_replace("\r", "", $name);
                            $champs = $name;
                        }
                        if ($l == "product_reference"){
                            $champs = $row2["new_ref"];
                        }
                    }
                }
            }*/

            $champs  = strtr($champs, '"', '\'');
            $champs  = strtr($champs, ';', ':');

            $row[$l] = utf8_decode($champs);

        }

        //echo '<br>'.$row['sref1'].'<br>';
        if ($row['sref1'] != '0')
        {
            $result1 = Db::getInstance()->getRow('SELECT dic_reference, dic_sref1,dic_sref2 FROM `'._DB_PREFIX_.'product_attribute` 
                where `id_product_attribute` = '.$row['sref1']);
            // Derniere référence déclinaison
            $sql    = "select reference from "._DB_PREFIX_."product_attribute where id_product_attribute ='".$row['sref1']."'";
            //echo "SQL ".$sql."<br>";
            $datasref = Db::getInstance()->getRow($sql);
            // if ($datasref['reference'] != NULL and $datasref['reference']>'') $row['product_reference'] = $datasref['reference'];             TEMPORARY DISABLED KEVIN
        }
        else
        {
            // Derniere référence produit
            $sql    = "select reference from "._DB_PREFIX_."product where id_product='".$row['product_id']."'";
            //echo "SQL ".$sql."<br>";
            $datasref = Db::getInstance()->getRow($sql);
            // if ($datasref['reference'] != NULL and $datasref['reference']>'') $row['product_reference'] = $datasref['reference'];             TEMPORARY DISABLED KEVIN
        }

        if ($result1['dic_reference'] != null) {
            // $row['product_reference'] = trim($result1['dic_reference']);             TEMPORARY DISABLED KEVIN
            $row['sref1']             = $result1['dic_sref1'];
            $row['sref2']             = $result1['dic_sref2'];
        } else {
            $row['sref1'] = "";
            $row['sref2'] = "";
        }
        if (($row['product_id'] == 1288 || $row['product_id'] == 1289 || $row['product_id'] == 1290) && isset($largeurSurMesure)) {
            $sql = "SELECT reference 
            FROM ps_product
            WHERE id_product = " . $row['product_id'];

            $reference = Db::getInstance()->getRow($sql);
            $row['product_reference'] = $reference['reference'].$largeurSurMesure;
        }
        $detail[$k] = $row;
    }

    if ($f = @fopen($OrderCsv, 'w')) {
        fputcsv($f, $entete, ';', chr(0));
        foreach ($detail as $ligne) {
            fputcsv($f, $ligne, ';', chr(0));
        }
        fclose($f);
    }
    chmod($OrderCsv, 0775);
    copy($OrderCsv, $OrderSvgCsv);

    $sql = "update "._DB_PREFIX_."orders set `dic_ficcsv`=1 where `id_order`=".$id_order;
    Db::getInstance()->Execute($sql);
}
