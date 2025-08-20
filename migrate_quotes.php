<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require(__DIR__.'/config/config.inc.php');
require_once(__DIR__.'/init.php');

// Connexion à cloture-discount.fr (base distante)
$remoteDb = new PDO(
    'mysql:host=87.98.141.171;dbname=districlosnew;charset=utf8',
    'districlosnew',
    '11Atb2I20TJ6joDc',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 1. Récupérer les devis locaux récents
$localQuotes = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'opartdevis WHERE id_opartdevis > "2025-07-15"');

foreach ($localQuotes as $quote) {
    $old_cart_id = (int)$quote['id_cart'];

    // 2. Chercher le panier dans la base distante
    $stmt = $remoteDb->prepare('SELECT * FROM ps_cart WHERE id_cart = :id_cart');
    $stmt->execute(['id_cart' => $old_cart_id]);
    $remoteCart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$remoteCart) {
        echo "Cart $old_cart_id introuvable dans la base distante.\n";
        continue;
    }

    // 3. Créer un nouveau panier dans la base locale
    $cartFields = $remoteCart;
    unset($cartFields['id_cart']);

    Db::getInstance()->insert('cart', $cartFields);
    $new_cart_id = (int)Db::getInstance()->Insert_ID();

    // 4. Récupérer et insérer les produits
    $products = $remoteDb->prepare('SELECT * FROM ps_cart_product WHERE id_cart = :id_cart');
    $products->execute(['id_cart' => $old_cart_id]);
    foreach ($products->fetchAll(PDO::FETCH_ASSOC) as $product) {
        $row = [
            'id_cart' => $new_cart_id,
            'id_product' => (int)$product['id_product'],
            'id_product_attribute' => (int)$product['id_product_attribute'],
            'quantity' => (int)$product['quantity'],
            'date_add' => $product['date_add'],
        ];
        Db::getInstance()->insert('cart_product', $row);
    }

    // 5. Récupérer et insérer les règles panier
    $cartRules = $remoteDb->prepare('SELECT * FROM ps_cart_cart_rule WHERE id_cart = :id_cart');
    $cartRules->execute(['id_cart' => $old_cart_id]);
    foreach ($cartRules->fetchAll(PDO::FETCH_ASSOC) as $rule) {
        $row = [
            'id_cart' => $new_cart_id,
            'id_cart_rule' => (int)$rule['id_cart_rule'],
        ];
        Db::getInstance()->insert('cart_cart_rule', $row);
    }

    // 6. Mise à jour du devis local avec le nouveau ID de panier
    Db::getInstance()->update(
        'opartdevis',
        ['id_cart' => $new_cart_id],
        'id_opartdevis = '.(int)$quote['id_opartdevis']
    );

    echo "Devis #{$quote['id_opartdevis']} mis à jour avec nouveau panier #$new_cart_id\n";
}
