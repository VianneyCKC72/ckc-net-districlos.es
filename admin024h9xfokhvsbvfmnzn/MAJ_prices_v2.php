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

	$csv = readCSV('/var/www/districlos/admin562niy1zl/prices/EDI_TARIF_DV_WEB.csv');

	// fgetcsv($file);

	foreach ( $csv as $value ) {

		$value = explode(';', $value[0]);

		if ($value[0] == "" || !isset($value[1]) || !isset($value[3])) {
			continue;
		}

		$final_price_ttc = (float) str_replace(',', '.', $value[3]) * 1.2;
		$final_price_ht = (float) str_replace(',', '.', $value[3]);
		//$reduction = 0;
		$set_price = $final_price_ht;

		$product_price_query = 'SELECT * FROM `ps_product` WHERE `id_product` = :id_product';
		$stmt = $conn->prepare($product_price_query);
		$stmt->execute(['id_product' => $value[1]]);
		$product_price = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$product_price = $product_price[0]['price'];
		if ($product_price > 0) {
			$set_price = $set_price - $product_price;
		}
		if ($value[0] != "") {
			$sql = 'UPDATE `ps_product_attribute` SET `price` = :price WHERE `ps_product_attribute`.`reference` = :reference';
			$stmt = $conn->prepare($sql);
			$stmt->execute(['price' => $set_price, 'reference' => $value[0]]);

			$sql = 'UPDATE `ps_product_attribute_shop` SET `price` = :price JOIN ps_product_attribute ON ps_product_attribute.id_product_attribute = `ps_product_attribute_shop`.`id_product_attribute` WHERE ps_product_attribute_shop.reference = :reference';
			$stmt = $conn->prepare($sql);
			$stmt->execute(['price' => $set_price, 'reference' => $value[0]]);
		}else{
			$sql = 'UPDATE `ps_product` SET `price` = :price WHERE `ps_product`.`id_product` = :id_product';
			$stmt = $conn->prepare($sql);
			$stmt->execute(['price' => $final_price_ht, 'id_product' => $value[1]]);

			$sql = 'UPDATE `ps_product_shop` SET `price` = :price WHERE `ps_product_shop`.`id_product` = :id_product';
			$stmt = $conn->prepare($sql);
			$stmt->execute(['price' => $final_price_ht, 'id_product' => $value[1]]);
		}

		if ($value[1] == 485) {
			if ($value[2] != "") {
				$sql = 'SELECT * FROM `ps_product_attribute` WHERE (`ps_product_attribute`.`id_product` = 1028 OR `ps_product_attribute`.`id_product` = 1027) AND `ps_product_attribute`.`reference` = :reference';
				$stmt = $conn->prepare($sql);
				$stmt->execute(['reference' => $value[0]]);
				$product_attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);

				foreach ($product_attributes as $product_attribute) {
					$sql = 'UPDATE `ps_product` SET `price` = :price WHERE `ps_product`.`id_product` = :id_product';
					$stmt = $conn->prepare($sql);
					$stmt->execute(['price' => $product_price, 'id_product' => $product_attribute['id_product']]);

					$sql = 'UPDATE `ps_product_shop` SET `price` = :price WHERE `ps_product_shop`.`id_product` = :id_product';
					$stmt = $conn->prepare($sql);
					$stmt->execute(['price' => $product_price, 'id_product' => $product_attribute['id_product']]);

					$sql = 'UPDATE `ps_product_attribute_shop` SET `price` = :price WHERE `ps_product_attribute_shop`.`id_product_attribute` = :id_product_attribute';
					$stmt = $conn->prepare($sql);
					$stmt->execute(['price' => $set_price, 'id_product_attribute' => $product_attribute['id_product_attribute']]);

					$sql = 'UPDATE `ps_product_attribute` SET `price` = :price WHERE `ps_product_attribute`.`id_product_attribute` = :id_product_attribute';
					$stmt = $conn->prepare($sql);
					$stmt->execute(['price' => $set_price, 'id_product_attribute' => $product_attribute['id_product_attribute']]);
				}
			} else {
				$sql = 'UPDATE `ps_product` SET `price` = :price WHERE `ps_product`.`id_product` = 1028 OR `ps_product`.`id_product` = 1027';
				$stmt = $conn->prepare($sql);
				$stmt->execute(['price' => $final_price_ht]);

				$sql = 'UPDATE `ps_product_shop` SET `price` = :price WHERE `ps_product_shop`.`id_product` = 1028 OR `ps_product_shop`.`id_product` = 1027';
				$stmt = $conn->prepare($sql);
				$stmt->execute(['price' => $final_price_ht]);
			}
		}
	}

	echo "Prix mis à jour avec succès";

} catch(PDOException $e) {
	echo "Connection failed: " . $e->getMessage();
}

function readCSV($csvFile){
	$file_handle = fopen($csvFile, 'r');
	while (!feof($file_handle) ) {
		$line_of_text[] = fgetcsv($file_handle, 0);
	}
	fclose($file_handle);
	return $line_of_text;
}

?>
