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
           OR description LIKE '%descr_couleur%' 
           OR description LIKE '%<!-- Color -->%' 
           OR description LIKE '%<!-- Info -->%'";
$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $idProduct = $row['id_product'];
    $idLang = $row['id_lang'];
    $description = $row['description'];
    $newDescription = $description;

    // Liste des classes à supprimer
    $classesToRemove = [
        'descr_color',
        'descr_conseils',
        'descr_conseil_content',
        'descr_couleur',
    ];

    foreach ($classesToRemove as $class) {
        $pattern = '/<div\b[^>]*class="[^"]*\b' . preg_quote($class, '/') . '\b[^"]*"[^>]*>.*?<\/div>/is';
        $newDescription = preg_replace($pattern, '', $newDescription);
    }

    // Supprimer les blocs <!-- Color --> + </div></div>
    $newDescription = preg_replace('/<!--\s*Color\s*-->\s*<\/div>\s*<\/div>/is', '', $newDescription);

    // Supprimer les blocs <!-- Info --> + </div></div>
    $newDescription = preg_replace('/<!--\s*Info\s*-->\s*<\/div>\s*<\/div>/is', '', $newDescription);

    // Si modif, on update
    if ($description !== $newDescription) {
        $updateStmt = $pdo->prepare("UPDATE ps_product_lang SET description = :desc WHERE id_product = :id AND id_lang = :lang");
        $updateStmt->execute([
            ':desc' => $newDescription,
            ':id' => $idProduct,
            ':lang' => $idLang
        ]);
        echo "✅ Produit ID {$idProduct}, langue {$idLang} nettoyé.\n";
    }
}

echo "🎉 Nettoyage terminé.\n";
