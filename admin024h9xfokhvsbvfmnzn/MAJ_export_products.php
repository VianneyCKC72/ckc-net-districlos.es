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

	$sql = 'SELECT pa.id_product, pa.id_product_attribute, pa.reference
			FROM `ps_product_attribute` pa';

	$stmt = $conn->prepare($sql);

	$stmt->execute(['date' => date('Y-m-d H:i:s')]);

	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$filename = '/var/www/districlos/admin024h9xfokhvsbvfmnzn/prices/products.csv';
	if (file_exists($filename)) {
		unlink($filename);
	}
	$monfichier = fopen($filename, 'w');
	fprintf($monfichier, chr(0xEF).chr(0xBB).chr(0xBF));

	if($monfichier!=false)
	{
		$entete_fields=array("id_product","id_product_attribute","reference");
		fputcsv($monfichier, $entete_fields, ';');

		$detail = array();
		foreach ($result AS $k => $row) {
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
