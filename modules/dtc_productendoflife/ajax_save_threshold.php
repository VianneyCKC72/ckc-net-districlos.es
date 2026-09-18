<?php
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');

$id_product_attribute = (int)Tools::getValue('id_product_attribute');
$stock_threshold = Tools::getValue('stock_threshold');

// Convertir en NULL si vide
if ($stock_threshold === '' || $stock_threshold === null) {
    $stock_threshold = null;
} else {
    $stock_threshold = (int)$stock_threshold;
}

if (!$id_product_attribute) {
    echo json_encode(['success' => false, 'message' => 'ID produit attribute manquant']);
    exit;
}

// Vérifier si l'enregistrement existe
$exists = Db::getInstance()->getValue('
    SELECT id_product_attribute 
    FROM ' . _DB_PREFIX_ . 'product_attribute_end_of_life 
    WHERE id_product_attribute = ' . (int)$id_product_attribute
);

if ($exists) {
    // Mettre à jour le seuil
    $result = Db::getInstance()->update(
        'product_attribute_end_of_life',
        [
            'stock_threshold' => $stock_threshold,
            'date_upd' => date('Y-m-d H:i:s')
        ],
        'id_product_attribute = ' . (int)$id_product_attribute
    );
} else {
    // Créer l'enregistrement avec le seuil (même si end_of_life = 0)
    $result = Db::getInstance()->insert(
        'product_attribute_end_of_life',
        [
            'id_product_attribute' => $id_product_attribute,
            'end_of_life' => 0,
            'stock_threshold' => $stock_threshold,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ]
    );
}

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Seuil sauvegardé']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
}
?>
