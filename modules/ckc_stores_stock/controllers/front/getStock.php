<?php
require_once _PS_MODULE_DIR_ . 'ckc_stores_stock/classes/StockManager.php';

class Ckc_Stores_StockGetStockModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->ajax = true;
    }

    public function displayAjax()
    {
        $id_product = (int)Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        $id_store = null;
        if ($this->context->customer->isLogged()) {
            $id_store = $this->context->cookie->__get('favorite_store');
        }else{
            $id_store = Db::getInstance()->getValue('SELECT id_store FROM '._DB_PREFIX_.'ckc_favorite_stores WHERE id_customer = '.(int)$this->context->customer->id);
            if (!$id_store) {
                $id_store = null;
            }
        }

        $packList = Tools::getValue('packList', []);

        $stockManager = new StockManager();
        $response = $stockManager->getStockData($id_product, $id_product_attribute, $id_store, $packList);

        die(json_encode($response));
    }
}
