<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckc_Stores_Stock extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_stores_stock';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'CKC-Net';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Stocks par magasin');
        $this->description = $this->l('Affiche les stocks par magasin sur la fiche produit.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayStockButton')
            && $this->registerHook('displayFooterProduct')
            && $this->registerHook('displayStockCheckout');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addJS($this->_path . 'assets/js/stores_stock.js');
        $this->context->controller->addCSS($this->_path . 'assets/css/stores_stock.css');
    }

    public function hookDisplayStockButton($params)
    {
        $id_product = (int)$params['product']['id_product'];
        $id_product_attribute = (int)Tools::getValue('id_product_attribute', 0);

        require_once _PS_MODULE_DIR_ . 'ckc_stores_stock/classes/StockManager.php';
        $stockManager = new StockManager();
        $stockData = $stockManager->getStockData($id_product, $id_product_attribute, null);

        $this->context->smarty->assign([
            'total_quantity' => $stockData['total_quantity'],
            'isKit' => $stockData['isKit'],
            'stores' => $stockData['stores'],
            'seuil' => $stockData['seuil'],
        ]);

        return $this->fetch('module:ckc_stores_stock/views/templates/hook/displayStock.tpl');
    }

    public function hookDisplayFooterProduct($params)
    {
        return $this->display(__FILE__, 'views/templates/hook/modal.tpl');
    }
    
    public function hookDisplayStockCheckout($params)
    {
        require_once _PS_MODULE_DIR_ . 'ckc_stores_stock/classes/StockManager.php';
        $stockManager = new StockManager();
        $stockData = $stockManager->getDelivery($this->context->cart->id_carrier);
    
        $this->context->smarty->assign($stockData);
    
        return $this->fetch('module:ckc_stores_stock/views/templates/hook/displayStockCheckout.tpl');
    }
}
