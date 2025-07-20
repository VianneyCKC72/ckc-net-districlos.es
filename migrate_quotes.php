<?php



require(__DIR__.'/config/config.inc.php');
require_once(__DIR__.'/init.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connexion à cloture-discount.fr (site source)
$remoteDb = new PDO(
    'mysql:host=87.98.141.171;dbname=districlosnew;charset=utf8',
    'districlosnew',
    '11Atb2I20TJ6joDc',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$arrayQuotes = [
    array('id_opartdevis' => '83833'),
    array('id_opartdevis' => '84929'),
    array('id_opartdevis' => '84930'),
    array('id_opartdevis' => '84931'),
    array('id_opartdevis' => '84932'),
    array('id_opartdevis' => '84933'),
    array('id_opartdevis' => '84934'),
    array('id_opartdevis' => '84935'),
    array('id_opartdevis' => '84936'),
    array('id_opartdevis' => '84937'),
    array('id_opartdevis' => '84938'),
    array('id_opartdevis' => '84939'),
    array('id_opartdevis' => '84940'),
    array('id_opartdevis' => '84941'),
    array('id_opartdevis' => '84942'),
    array('id_opartdevis' => '84943'),
    array('id_opartdevis' => '84944'),
    array('id_opartdevis' => '84945'),
    array('id_opartdevis' => '84946'),
    array('id_opartdevis' => '84947'),
    array('id_opartdevis' => '84948'),
    array('id_opartdevis' => '84950'),
    array('id_opartdevis' => '84951'),
    array('id_opartdevis' => '84952'),
    array('id_opartdevis' => '84953'),
    array('id_opartdevis' => '84954'),
    array('id_opartdevis' => '84955'),
    array('id_opartdevis' => '84957'),
    array('id_opartdevis' => '84958'),
    array('id_opartdevis' => '84959'),
    array('id_opartdevis' => '84960'),
    array('id_opartdevis' => '84961'),
    array('id_opartdevis' => '84962'),
    array('id_opartdevis' => '84963'),
    array('id_opartdevis' => '84964'),
    array('id_opartdevis' => '84965'),
    array('id_opartdevis' => '84966'),
    array('id_opartdevis' => '84967'),
    array('id_opartdevis' => '84968'),
    array('id_opartdevis' => '84969'),
    array('id_opartdevis' => '84970'),
    array('id_opartdevis' => '84973'),
    array('id_opartdevis' => '84974'),
    array('id_opartdevis' => '84975'),
    array('id_opartdevis' => '84976'),
    array('id_opartdevis' => '84977')
];

// Récupération des devis

foreach ($arrayQuotes as $quote) {
    // local
    $sql =
        'SELECT *
                    FROM '._DB_PREFIX_.'opartdevis
                    WHERE id_opartdevis = '.(int)$quote['id_opartdevis'];

    $opart_devis = Db::getInstance()->getRow($sql);

    $datas['opartdevis'] = $opart_devis;

    $cart = new Cart($opart_devis['id_cart']);
    if (Validate::isLoadedObject($cart)) {
        $cart_data = $cart->getFields();
        $cart_data['products'] = Db::getInstance()->executeS(
            'SELECT cp.id_product, cp.id_product_attribute, cp.quantity, p.id_tax_rules_group
                                 FROM '._DB_PREFIX_.'cart_product cp
                                 LEFT JOIN '._DB_PREFIX_.'product p ON (p.id_product = cp.id_product)
                                 WHERE cp.id_cart = '.(int)$cart->id
        );
        $cart_data['cart_cart_rule'] = Db::getInstance()->executeS(
            'SELECT * FROM '._DB_PREFIX_.'cart_cart_rule WHERE id_cart = '.(int)$cart->id
        );

        $datas['cart'] = $cart_data;
    }

    // remote
    $cartFields = $datas['cart'];
    unset($cartFields['products'], $cartFields['cart_cart_rule'], $cartFields['id_cart']);

    // Insertion du panier dans la base distante
    $columns = array_keys($cartFields);
    $placeholders = array_map(fn($col) => ':'.$col, $columns);

    $stmt = $remoteDb->prepare(
        'INSERT INTO ps_cart ('.implode(',', $columns).') VALUES ('.implode(',', $placeholders).')'
    );
    $stmt->execute($cartFields);

    $new_cart_id = (int)$remoteDb->lastInsertId();

    // Mise à jour de l'ID du nouveau panier dans le devis
    $datas['opartdevis']['id_cart'] = $new_cart_id;
    unset($datas['opartdevis']['id_opartdevis']);

    // Insertion du devis dans la base distante
    $quoteCols = array_keys($datas['opartdevis']);
    $quotePlaceholders = array_map(fn($col) => ':'.$col, $quoteCols);

    $stmt = $remoteDb->prepare(
        'INSERT INTO ps_opartdevis ('.implode(',', $quoteCols).') VALUES ('.implode(',', $quotePlaceholders).')'
    );
    $stmt->execute($datas['opartdevis']);

    // Produits dans le panier
    if (!empty($datas['cart']['products']) && is_array($datas['cart']['products'])) {
        foreach ($datas['cart']['products'] as $product) {
            if (!isset($product['id_product'], $product['quantity'])) {
                continue;
            }

            $row = [
                'id_cart' => $new_cart_id,
                'id_product' => (int)$product['id_product'],
                'id_product_attribute' => (int)($product['id_product_attribute'] ?? 0),
                'quantity' => (int)$product['quantity'],
                'date_add' => date('Y-m-d H:i:s'),
            ];

            $stmt = $remoteDb->prepare(
                'INSERT INTO ps_cart_product (id_cart, id_product, id_product_attribute, quantity, date_add)
                 VALUES (:id_cart, :id_product, :id_product_attribute, :quantity, :date_add)'
            );
            $stmt->execute($row);
        }
    }

    // Règles panier
    if (!empty($datas['cart']['cart_cart_rule']) && is_array($datas['cart']['cart_cart_rule'])) {
        foreach ($datas['cart']['cart_cart_rule'] as $cart_rule) {
            if (!isset($cart_rule['id_cart_rule'])) {
                continue;
            }

            $row = [
                'id_cart' => $new_cart_id,
                'id_cart_rule' => (int)$cart_rule['id_cart_rule'],
            ];

            $stmt = $remoteDb->prepare(
                'INSERT INTO ps_cart_cart_rule (id_cart, id_cart_rule)
                 VALUES (:id_cart, :id_cart_rule)'
            );
            $stmt->execute($row);
        }
    }

    echo "Devis $quote dupliqué avec nouveau panier $new_cart_id sur la base distante\n";
}
