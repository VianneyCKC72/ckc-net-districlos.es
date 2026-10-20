<?php
require_once _PS_MODULE_DIR_ . 'ckc_stores_stock/classes/StockManager.php';

class Ckc_Stores_StockGetStockCheckoutModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->ajax = true;
    }

    public function displayAjax()
    {
        $delivery = str_replace('delivery_option_', '', Tools::getValue('selectedDelivery'));
        $stockManager = new StockManager();

        $id_store = Tools::getValue('id_store');

        die(json_encode(['success' => true, 'data' => $stockManager->getDelivery($delivery, $id_store)]));
    }
}
