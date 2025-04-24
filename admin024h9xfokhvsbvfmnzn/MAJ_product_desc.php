<?php

$servername = "87.98.141.171";
$port = 3306;
$username = "districlos_refonte";
$password = "TXM1qjk9teu_cfb9wxv";
$db = 'districlos_refonte';
$dsn = "mysql:host=$servername;port=$port;dbname=$db;charset=utf8";

$pdo = new PDO($dsn, $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "SELECT id_product, id_lang, description FROM ps_product_lang 
        WHERE description LIKE '%descr_color%' OR description LIKE '%descr_conseils%'";
$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $idProduct = $row['id_product'];
    $idLang = $row['id_lang'];
    $description = $row['description'];

    $newDescription = $description;

    $newDescription = preg_replace('/<div class="descr_color">.*?<\/div>/is', '', $newDescription);

    $newDescription = preg_replace('/<div class="descr_conseils">.*?<\/div>/is', '', $newDescription);

    if ($description !== $newDescription) {
        $updateStmt = $pdo->prepare("UPDATE ps_product_lang SET description = :desc WHERE id_product = :id AND id_lang = :lang");
        $updateStmt->execute([
            ':desc' => $newDescription,
            ':id' => $idProduct,
            ':lang' => $idLang
        ]);
        echo "Nettoyé produit ID {$idProduct} / Lang {$idLang}\n";
    }
}

echo "Nettoyage terminé.\n";
