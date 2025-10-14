<?php
// Contrôleur AJAX alternatif pour le module ckc_stores_stock
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/classes/StockManager.php';

header('Content-Type: application/json; charset=utf-8');

while (ob_get_level()) {
    ob_end_clean();
}

try {
    $id_product = (int)$_POST['id_product'] ?? (int)$_GET['id_product'] ?? 0;
    $id_product_attribute = (int)$_POST['id_product_attribute'] ?? (int)$_GET['id_product_attribute'] ?? 0;
    
    $id_store = null;
    if (Context::getContext()->customer->isLogged()) {
        $id_store = Context::getContext()->cookie->__get('favorite_store');
    } else {
        $id_store = Db::getInstance()->getValue('SELECT id_store FROM '._DB_PREFIX_.'ckc_favorite_stores WHERE id_customer = '.(int)Context::getContext()->customer->id);
        if (!$id_store) {
            $id_store = null;
        }
    }
    
    $packList = $_POST['packList'] ?? $_GET['packList'] ?? [];
    
    $stockManager = new StockManager();
    $response = $stockManager->getStockData($id_product, $id_product_attribute, $id_store, $packList);
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'total_quantity' => 0,
        'seuil' => false,
        'stores' => []
    ]);
}

exit;
