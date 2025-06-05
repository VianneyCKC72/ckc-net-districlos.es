<?php
require_once(dirname(__FILE__).'/config/config.inc.php');
require_once(dirname(__FILE__).'/init.php');

$sql = 'SELECT id_product, description FROM '._DB_PREFIX_.'product_lang';
$products = Db::getInstance()->executeS($sql);

foreach ($products as $product) {
    $oldDescription = $product['description'];
    $newDescription = str_replace(
        '/themes/auto_store/',
        '/themes/classic/',
        $oldDescription
    );

    if ($oldDescription !== $newDescription) {
        Db::getInstance()->update(
            'product_lang',
            ['description' => pSQL($newDescription, true)],
            'id_product = '.(int)$product['id_product']
        );
        echo "Mise à jour de la description du produit ID: {$product['id_product']}\n";
    }
}

echo "Terminé.";
