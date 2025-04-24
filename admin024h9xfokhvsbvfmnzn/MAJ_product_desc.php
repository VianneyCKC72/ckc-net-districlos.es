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
        WHERE description LIKE '%descr_color%' 
           OR description LIKE '%descr_conseils%' 
           OR description LIKE '%descr_conseil_content%' 
           OR description LIKE '%descr_couleur%'";
$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $idProduct = $row['id_product'];
    $idLang = $row['id_lang'];
    $description = $row['description'];

    $newDescription = $description;

    // Supprimer tous les blocs HTML avec ces classes
    $classesToRemove = [
        'descr_color',
        'descr_conseils',
        'descr_conseil_content',
        'descr_couleur',
    ];

    foreach ($classesToRemove as $class) {
        $pattern = '/<div class="' . preg_quote($class, '/') . '">.*?<\/div>/is';
        $newDescription = preg_replace($pattern, '', $newDescription);
    }

    if ($description !== $newDescription) {
        $updateStmt = $pdo->prepare("UPDATE ps_product_lang SET description = :desc WHERE id_product = :id AND id_lang = :lang");
        $updateStmt->execute([
            ':desc' => $newDescription,
            ':id' => $idProduct,
            ':lang' => $idLang
        ]);
        echo "✅ Nettoyé : produit ID {$idProduct}, langue {$idLang}\n";
    }
}

echo "🎉 Nettoyage complet terminé.\n";
