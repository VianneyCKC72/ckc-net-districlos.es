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
            && $this->registerHook('displayStockCheckout')
            && $this->registerHook('displayAdminOrder');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addJS($this->_path . 'assets/js/stores_stock.js');
        $this->context->controller->addCSS($this->_path . 'assets/css/stores_stock.css');
        
        // Enregistrer une fonction Smarty personnalisée pour vérifier le statut "victime de son succès"
        $this->context->smarty->registerPlugin('function', 'checkVictimeSucces', array($this, 'smartyCheckVictimeSucces'));
    }
    
    /**
     * Fonction Smarty personnalisée pour vérifier le statut "victime de son succès"
     * Usage dans le template : {checkVictimeSucces id_product=$accessory.id_accessory id_product_attribute=$accessory.default_id_product_attribute assign="victime_succes"}
     */
    public function smartyCheckVictimeSucces($params, &$smarty)
    {
        $id_product = isset($params['id_product']) ? (int)$params['id_product'] : 0;
        $id_product_attribute = isset($params['id_product_attribute']) ? (int)$params['id_product_attribute'] : 0;
        $assign_var = isset($params['assign']) ? $params['assign'] : 'victime_succes';
        
        $victime_succes = false;
        
        if ($id_product > 0) {
            // Si pas de déclinaison, récupérer la déclinaison par défaut
            if ($id_product_attribute == 0) {
                $id_product_attribute = (int)Product::getDefaultAttribute($id_product);
            }
            
            if ($id_product_attribute > 0) {
                require_once _PS_MODULE_DIR_ . 'ckc_stores_stock/classes/StockManager.php';
                $stockManager = new StockManager();
                $stockData = $stockManager->getStockData($id_product, $id_product_attribute, null);
                $victime_succes = isset($stockData['victime_succes']) ? (bool)$stockData['victime_succes'] : false;
            }
        }
        
        // Assigner la variable à Smarty
        $smarty->assign($assign_var, $victime_succes);
        
        return '';
    }

    public function hookDisplayAdminOrder($params)
    {
        $order = new Order((int)$params['id_order']);
        $transferDivalto = false;
        $transfer = false;
        $shopOrder = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = ' . (int)$order->id);
        $shopOrder = $shopOrder['id_store'];
        $shopOrder = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores WHERE id_store = ' . (int)$shopOrder);

        $ignoreDivalto = in_array($order->current_state, [3, 4, 5, 6, 7, 14, 15, 18, 25, 26, 29, 30, 443, 448]);
        $shopList =  Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores');

        if (Tools::isSubmit('submitOrderToDivalto') && !$ignoreDivalto) {
            include _PS_MODULE_DIR_ . "divaltoInfinityConnec/divaltoOrderCtl.php";
            DivaltoCreateOrder((int)$order->id);
            $transferDivalto = true;
        }

        if (Tools::isSubmit('submitOrderTransfer') && Tools::getValue('shop_transfer')) {
            $this->transferOrderShop((int)$order->id, (int)Tools::getValue('shop_transfer'));
            $transfer = true;
        }

        $this->context->smarty->assign(
            array(
                'transferDivalto' => $transferDivalto,
                'ignoreDivalto' => $ignoreDivalto,
                'transfer' => $transfer,
                'shopOrder' => $shopOrder,
                'shopList' => $shopList,
            )
        );

        return $this->fetch('module:ckc_stores_stock/views/templates/hook/admin-order.tpl');
    }

    public function transferOrderShop($id_order, $id_shop)
    {
        $order = new Order((int) $id_order);

        if (Validate::isLoadedObject($order)) {
            Db::getInstance()->update('orders', array('id_store' => (int)$id_shop), 'id_order = ' . (int)$order->id);

            return true;
        } else {
            return false;
        }
    }

    public function hookDisplayStockButton($params)
    {
        $id_product = (int)$params['product']['id_product'];
        $id_product_attribute = (int)Tools::getValue('id_product_attribute', 0);

        if ($id_product_attribute == 0) {
            $id_product_attribute = (int)$params['product']['id_product_attribute'];
        }

        require_once _PS_MODULE_DIR_ . 'ckc_stores_stock/classes/StockManager.php';
        $stockManager = new StockManager();
        $stockData = $stockManager->getStockData($id_product, $id_product_attribute, null);

        $this->context->smarty->assign([
            'total_quantity' => $stockData['total_quantity'],
            'platform_stock' => $stockData['platform_stock'],
            'isKit' => $stockData['isKit'],
            'stores' => $stockData['stores'],
            'seuil' => $stockData['seuil'],
            'fin_de_vie' => $stockData['fin_de_vie'],
            'fin_de_vie_national' => isset($stockData['fin_de_vie_national']) ? $stockData['fin_de_vie_national'] : false,
            'victime_succes' => isset($stockData['victime_succes']) ? $stockData['victime_succes'] : false,
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
