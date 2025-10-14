<?php
include_once _PS_ROOT_DIR_ . '/modules/pm_advancedpack/AdvancedPack.php';
include_once _PS_ROOT_DIR_ . '/modules/pm_advancedpack/AdvancedPackCoreClass.php';
class StockManager extends Module
{
    protected $exIdsStock = [
        1269,
        1186,
        1164,
        1270,
        1173,
        1172,
        1170,
        1169,
        1166,
        1167,
        1073,
        1079,
    ];
    
    public function getStockData($id_product, $id_product_attribute, $id_store, $packList = [])
    {
        $product = new Product($id_product);
        $quantity_by_store = Db::getInstance()->getValue('SELECT quantity FROM ' . _DB_PREFIX_ . 'ckcstockstores_stocks WHERE id_product = ' . (int)$id_product . ' AND id_product_attribute = ' . (int)$id_product_attribute . ' AND id_store = ' . (int)$id_store);
        $quantity = Db::getInstance()->getValue('SELECT SUM(quantity) FROM ' . _DB_PREFIX_ . 'ckcstockstores_stocks WHERE id_product = ' . (int)$id_product . ' AND id_product_attribute = ' . (int)$id_product_attribute);

        $seuilCategories = json_decode(file_get_contents(_PS_MODULE_DIR_ . 'ckc_stores_stock/assets/js/seuilCategories.json'), true);
        $packIdList = AdvancedPack::getIdsPacks(true);
        $isKit = in_array((int)$id_product, $packIdList);

        $seuilOk = true;
        $seuil = 0;
        foreach ($seuilCategories as $seuilCategory) {
            if (isset($seuilCategory['category_id']) && $seuilCategory['category_id'] == $product->id_category_default) {
                if ($quantity < $seuilCategory['seuil']) {
                    $seuilOk = false;
                }
                $seuil = $seuilCategory['seuil'];
            }
        }

        $stores = $this->getStoresData($id_product, $id_product_attribute);
        $total_quantity = array_sum(array_column($stores, 'stock'));

        // Récupérer le statut "fin de vie" et le seuil de stock de la déclinaison
        $fin_de_vie = false;
        $stock_threshold = null;
        $victime_succes = false;
        $platform_stock = 0;
        
        if ($id_product_attribute > 0) {
            $eol_data = Db::getInstance()->getRow('SELECT end_of_life, stock_threshold FROM ' . _DB_PREFIX_ . 'product_attribute_end_of_life WHERE id_product_attribute = ' . (int)$id_product_attribute);
            if ($eol_data) {
                $fin_de_vie = (bool)$eol_data['end_of_life'];
                $stock_threshold = $eol_data['stock_threshold'] ? (int)$eol_data['stock_threshold'] : null;
                
                // Si fin de vie, récupérer le stock de la plateforme (store 18 = DX)
                if ($fin_de_vie) {
                    $platform_stock = Db::getInstance()->getValue('SELECT quantity FROM ' . _DB_PREFIX_ . 'ckcstockstores_stocks WHERE id_product = ' . (int)$id_product . ' AND id_product_attribute = ' . (int)$id_product_attribute . ' AND id_store = 18');
                }
                
                // Vérifier si on est en "victime de son succès" (stock <= seuil)
                if ($stock_threshold !== null && $total_quantity <= $stock_threshold) {
                    $victime_succes = true;
                }
            }
        }

        return [
            'quantity' => $quantity_by_store,
            'total_quantity' => $total_quantity,
            'platform_stock' => $platform_stock,
            'id_store' => $id_store,
            'store_name' => (new Shop($id_store))->name,
            'seuil' => $seuilOk,
            'isKit' => $isKit,
            'stores' => $stores,
            'fin_de_vie' => $fin_de_vie,
            'stock_threshold' => $stock_threshold,
            'victime_succes' => $victime_succes,
        ];
    }

    public function getStoresData($id_product, $id_product_attribute)
    {
        $shops = Db::getInstance()->executeS('SELECT id_store, name FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores');
        $stores = [];

        foreach ($shops as $shop) {
            $quantity_by_store = Db::getInstance()->getValue('SELECT quantity FROM ' . _DB_PREFIX_ . 'ckcstockstores_stocks WHERE id_product = ' . (int)$id_product . ' AND id_product_attribute = ' . (int)$id_product_attribute . ' AND id_store = ' . (int)$shop['id_store']);

            $id_store = null;
            if (!$this->context->customer->isLogged()) {
                $id_store = $this->context->cookie->__get('favorite_store');
            }else{
                $id_store = Db::getInstance()->getValue('SELECT id_store FROM '._DB_PREFIX_.'ckc_favorite_stores WHERE id_customer = '.(int)$this->context->customer->id);
                if (!$id_store) {
                    $id_store = null;
                }
            }

            $selected = $id_store && $id_store == $shop['id_store'];

            $address = '';
            $zip_code = '';
            $result = Db::getInstance()->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'ckcstorespresentation"');
            if (count($result) > 0) {
                $storePresentation = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ckcstorespresentation WHERE id_multiboutique = ' . (int)$shop['id_store']);
                if ($storePresentation) {
                    $address = $storePresentation['adresse'].'<br>'.$storePresentation['code_postal'].' '.$storePresentation['ville'];
                }
            }

            $zip_code = Db::getInstance()->getValue('SELECT zip_codes FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores WHERE id_store = ' . (int)$shop['id_store']);

            $stores[] = [
                'id' => $shop['id_store'],
                'name' => $shop['name'],
                'stock' => $quantity_by_store,
                'seuil' => true,
                'selected' => $selected ? 1 : 0,
                'code' => preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($shop['name'])),
                'address' => $address,
                'zip_codes' => $zip_code,
            ];
        }

        return $stores;
    }
    
    public function getDelivery($carrier_id)
    {
        $stock             = null;
        $store_name        = null;
        $stock_ok          = true;
        $carrier           = new Carrier($carrier_id);
        $carrierZones      = $carrier->getZones();
        $stocks  = [];
        $finSerieStocks = []; // Liste des produits fin de série
        $hasFinSerieProducts = false; // Variable pour détecter les produits fin de série
    
        $isClickAndCollect = strpos($carrier->name, 'Retrait') !== false || strpos($carrier->name, 'Click & Collect') !== false ? true : false;
    
        $shops = Db::getInstance()->executeS('SELECT id_store, name, zip_codes FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores');
    
        $storesDelivery = null;
        $address        = new Address($this->context->cart->id_address_delivery);
    
        foreach ($shops as $key => $shop) {
            $deps = explode(',', $shop['zip_codes']);
            foreach ($deps as $zip_code) {
                if (!$storesDelivery) {
                    $zip_code = trim($zip_code);
    
                    if ($zip_code === $address->postcode || (strpos($zip_code, '*') !== false && preg_match('/^' . str_replace('*', '.*', $zip_code) . '$/', $address->postcode))) {
                        $storesDelivery = $shop['id_store'];
                        $store_name = $shop['name'];
                    }
                }
            }
        }
    
        if ($storesDelivery) {
            $products = $this->context->cart->getProducts();
            foreach ($products as $product) {
                // Vérifier si le produit est en fin de série
                $id_product_attribute = isset($product['id_product_attribute']) ? (int)$product['id_product_attribute'] : 0;
                $isFinSerie = $this->isProductFinSerie($id_product_attribute);
                
                if ($isFinSerie) {
                    $hasFinSerieProducts = true;
                }
                
                if (in_array($product['id_product'], $this->exIdsStock)) {
                    $productArray = [
                        'product_name' => $product['name'],
                        'stock_ok'     => true,
                        'is_fin_serie' => $isFinSerie
                    ];
                }
                else {
                    $stockData = $this->getStockData($product['id_product'], $product['id_product_attribute'], $storesDelivery);
    
                    // Ne pas compter les produits fin de série dans le stock_ok
                    if (!$isFinSerie && $stockData['quantity'] < $product['quantity']) {
                        $stock_ok = false;
                    }
                    
                    $productArray = [
                        'product_name' => $product['name'],
                        'stock_ok'     => $stockData['quantity'] < $product['quantity'] ? false : true,
                        'is_fin_serie' => $isFinSerie
                    ];
    
                }
    
                // Séparer les produits fin de série des produits normaux
                if ($isFinSerie) {
                    array_push($finSerieStocks, $productArray);
                } else {
                    array_push($stocks, $productArray);
                }
            }
        }
    
        if ($store_name && $storesDelivery) {
            $stock = [
                'store_name'        => $store_name,
                'store_id'          => $storesDelivery,
                'stock_ok'          => $stock_ok,
                'isClickAndCollect' => $isClickAndCollect,
                'carrier'           => $carrier->name,
                'hasFinSerieProducts' => $hasFinSerieProducts
            ];
        }
    
        return [
            'stock'  => $stock,
            'stocks' => $stocks,
            'finSerieStocks' => $finSerieStocks,
        ];
    }
    
    private function isProductFinSerie($id_product_attribute)
    {
        if (empty($id_product_attribute) || $id_product_attribute == 0) {
            return false;
        }
        
        // Vérifier dans la table du module dtc_productendoflife
        $sql = 'SELECT end_of_life FROM `' . _DB_PREFIX_ . 'product_attribute_end_of_life` 
                WHERE `id_product_attribute` = ' . (int)$id_product_attribute;
        
        return (bool)Db::getInstance()->getValue($sql);
    }
}
