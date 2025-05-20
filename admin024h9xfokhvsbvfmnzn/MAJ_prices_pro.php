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

	$csv = readCSV('/var/www/districlos/admin562niy1zl/prices/EDI_TARIF_DV_WEBPRO.csv');

	// Skip header row if it exists
	// fgetcsv($file);

	foreach ( $csv as $key => $value ) {
		if ($key > 0) {
			$value = explode(';', $value[0]);
			if ($value[0] == "" || !isset($value[1]) || $value[2] == "" || !isset($value[3])) {
				continue;
			}
			// Assuming CSV structure is: id_product, id_product_attribute, price
			// Adjust indices based on actual CSV structure
			$final_price_ttc = (float) str_replace(',', '.', $value[3]) * 1.2;
			$final_price_ht = (float) str_replace(',', '.', $value[3]);
			//$reduction = 0;
			$product_price_query = 'SELECT * FROM `ps_product` WHERE `id_product` = :id_product';
			$stmt = $conn->prepare($product_price_query);
			$stmt->execute(['id_product' => $value[1]]);
			$product_price = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$product_price = $product_price[0]['price'];

			$have_pro_specific_price_query = 'SELECT * FROM `ps_specific_price` WHERE `id_product_attribute` = :id_product_attribute AND `id_product` = :id_product AND `id_shop` = 0 AND `id_shop_group` = 0 AND `id_currency` = 0 AND `id_country` = 0 AND `id_group` = 10 AND `id_customer` = 0 LIMIT 1';
			$stmt = $conn->prepare($have_pro_specific_price_query);
			$stmt->execute(['id_product_attribute' => $value[2], 'id_product' => $value[1]]);
			$have_pro_specific_price = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($have_pro_specific_price && sizeof($have_pro_specific_price) > 0) {
				$update_pro_specific_price_query = 'UPDATE `ps_specific_price` SET `price` = :price WHERE `id_specific_price` = :id_specific_price';
				$stmt = $conn->prepare($update_pro_specific_price_query);
				$stmt->execute(['price' => $value[3], 'id_specific_price' => $have_pro_specific_price['id_specific_price']]);
			} else {
				$insert_pro_specific_price_query = 'INSERT INTO `ps_specific_price` (`id_specific_price`, `id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (NULL, 0, 0, :id_product, 0, 0, 0, 0, 10, 0, :id_product_attribute, :price, 1, 0.000000, 1, "amount", "0000-00-00 00:00:00", "0000-00-00 00:00:00")';
				$stmt = $conn->prepare($insert_pro_specific_price_query);
				$stmt->execute(['id_product' => $value[1], 'id_product_attribute' => $value[2], 'price' => $value[3]]);
			}
		}

	}

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
