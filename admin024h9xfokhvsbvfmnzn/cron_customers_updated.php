<?php

$servername = "87.98.141.171";
$port = 3306;
$username = "districlos_refonte";
$password = "TXM1qjk9teu_cfb9wxv";
$db = 'districlos_refonte';
$dsn = "mysql:host=$servername;port=$port;dbname=$db;charset=utf8";

try {
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $today = date("Y-m-d");

    $sql = 'SELECT c.id_customer as id, c.firstname, c.lastname, c.email, GROUP_CONCAT(DISTINCT o.id_order) as orders, 
    c.date_upd AS client_update_date, c.date_add as client_creation_date, adresse.address_update_date, adresse.address_creation_date, adresse.id_last_updated_address, adresse.last_updated_address, adresse.phone, adresse.phone_mobile
    FROM ps_customer c 
    LEFT JOIN ps_orders o ON o.id_customer = c.id_customer
    LEFT JOIN ps_address a ON a.id_customer = c.id_customer
    LEFT OUTER JOIN (SELECT a.id_address as id_last_updated_address, a.id_customer, a.date_upd as address_update_date, a.date_add as address_creation_date, CONCAT(IF(a.address1 IS NULL, "", a.address1)," ",IF(a.address2 IS NULL, "", a.address2)," ",IF(a.postcode IS NULL, "", a.postcode)," ",IF(a.city IS NULL, "", a.city)) as last_updated_address, a.phone, a.phone_mobile 
    FROM ps_address a ORDER BY a.date_upd DESC) AS adresse ON adresse.id_customer = c.id_customer
    WHERE c.date_upd LIKE "%'.$today.'%" OR a.date_upd LIKE "%'.$today.'%" GROUP BY c.id_customer';

    $stmt = $conn->prepare($sql);

    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $clients = [];

    foreach ($result as $client){
        $row = [];
        $row["id"] = $client["id"];
        $row["firstname"] = $client["firstname"];
        $row["lastname"] = $client["lastname"];
        $row["email"] = $client["email"];
        $row["orders"] = $client["orders"];
        $row["client_update_date"] = $client["client_update_date"];
        $row["client_creation_date"] = $client["client_creation_date"];
        $row["address_update_date"] = $client["address_update_date"];
        $row["address_creation_date"] = $client["address_creation_date"];
        $row["id_last_updated_address"] = $client['id_last_updated_address'];
        $row["last_updated_address"] = trim($client['last_updated_address']);
        $row["phone"] = $client["phone"];
        $row["phone_mobile"] = $client["phone_mobile"];

        $orders = explode(",", $client['orders']);
        $last_order = $orders[count($orders) - 1];

        $delivery_address = 'SELECT a.id_address, CONCAT(IF(a.address1 IS NULL, "", a.address1), " ", IF(a.address2 IS NULL, "", a.address2), " ", IF(a.postcode IS NULL, "", a.postcode), " ", IF(a.city IS NULL, "", a.city)) as delivery_address FROM ps_address a
        LEFT JOIN ps_orders o ON o.id_address_delivery = a.id_address WHERE a.id_customer = '.$client['id'].' AND o.id_order = '.$last_order;

        $stmt = $conn->prepare($delivery_address);

        $stmt->execute();
        $sql_delivery_address = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $invoice_address = 'SELECT a.id_address, CONCAT(IF(a.address1 IS NULL, "", a.address1), " ", IF(a.address2 IS NULL, "", a.address2), " ", IF(a.postcode IS NULL, "", a.postcode), " ", IF(a.city IS NULL, "", a.city)) as invoice_address FROM ps_address a
        LEFT JOIN ps_orders o ON o.id_address_invoice = a.id_address WHERE a.id_customer = '.$client['id'].' AND o.id_order = '.$last_order;
        $stmt = $conn->prepare($invoice_address);

        $stmt->execute();
        $sql_invoice_address = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $row['id_delivery_address'] = $sql_delivery_address[0]['id_address'];
        $row['delivery_address'] = trim($sql_delivery_address[0]['delivery_address']);
        $row['id_invoice_address'] = $sql_invoice_address[0]['id_address'];
        $row['invoice_address'] = trim($sql_invoice_address[0]['invoice_address']);

        if(strpos("d".$client['client_update_date'], $today) && strpos("d".$client['address_update_date'], $today)){
            $row['source_mise_a_jour'] = 'Mise à jour est faite dans toutes les informations client (client & adresse)';
        } else if (strpos("d".$client['client_update_date'], $today)){
            $row['source_mise_a_jour'] = 'Mise à jour est faite dans les informations client (firstname/lastname/email)';
        } else if (strpos("d".$client['address_update_date'], $today)) {
            $row['source_mise_a_jour'] = 'Mise à jour est faite dans l\'adresse client (last_client_address/phone/phone_mobile)';
        }

        $clients[] = $row;
    }

    $filename = '/var/www/districlos/admin024h9xfokhvsbvfmnzn/clients/'.date("dmY").'_orders_updated_clients.csv';
    if (file_exists($filename)) {
        unlink($filename);
    }
    $monfichier = fopen($filename, 'w');
    fprintf($monfichier, chr(0xEF).chr(0xBB).chr(0xBF));

    if($monfichier!=false)
    {
        $entete_fields=array("id","firstname","lastname", "email", "orders", "client_update_date", "client_creation_date", "address_update_date", "address_creation_date", "id_last_updated_address", "last_updated_address", "phone", "phone_mobile", "id_delivery_address","delivery_address", "id_invoice_address","invoice_address", "source_mise_a_jour");
        fputcsv($monfichier, $entete_fields, ';');

        $detail = array();
        foreach ($clients AS $k => $row) {
            foreach ($row as $l => $champs) {
                $row[$l] = $champs;
            }
            $detail[$k] =  $row ;
        }

        foreach ($detail as $ligne) {
            fputcsv($monfichier, $ligne, ';');
        }
        fclose($monfichier);
    }else{
        echo "Impossible d'ouvrir ou de créer le fichier.";
    }

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>