<?php
// Contrôleur AJAX pour le changement de transporteur
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/classes/StockManager.php';

header('Content-Type: application/json; charset=utf-8');

while (ob_get_level()) {
    ob_end_clean();
}

try {
    $selectedDelivery = $_POST['selectedDelivery'] ?? $_GET['selectedDelivery'] ?? '';
    $delivery = str_replace('delivery_option_', '', $selectedDelivery);

    $stockManager = new StockManager();
    $id_store = Tools::getValue('id_store');

    $data = $stockManager->getDelivery($delivery, $id_store);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit;
