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
        // Charger le mapping de références pour les produits fin de vie
        $mappingFile = _PS_MODULE_DIR_ . 'dtc_productendoflife/reference_mapping.json';
        $referenceMapping = [];
        if (file_exists($mappingFile)) {
            $mappingData = json_decode(file_get_contents($mappingFile), true);
            $referenceMapping = $mappingData['mappings'] ?? [];
        }

        // Récupérer la référence du produit actuel
        $currentReference = Db::getInstance()->getValue('SELECT reference FROM ' . _DB_PREFIX_ . 'product_attribute WHERE id_product_attribute = ' . (int)$id_product_attribute);
        
        // Si ce produit est mappé, utiliser la référence BRUT pour le stock
        $stock_id_product = $id_product;
        $stock_id_product_attribute = $id_product_attribute;
        
        if ($currentReference && isset($referenceMapping[$currentReference])) {
            $mappedReference = $referenceMapping[$currentReference];
            // Chercher si le produit BRUT existe dans PrestaShop (SANS LIMIT car bug SQL sur cette table)
            $mappedProduct = Db::getInstance()->getRow('SELECT id_product, id_product_attribute FROM ' . _DB_PREFIX_ . 'product_attribute WHERE reference = \'' . pSQL($mappedReference) . '\'');
            if ($mappedProduct) {
                // Le produit BRUT existe dans PrestaShop
                $stock_id_product = (int)$mappedProduct['id_product'];
                $stock_id_product_attribute = (int)$mappedProduct['id_product_attribute'];
            } else {
                // Le produit BRUT n'existe pas, utiliser le hash virtuel (avec modulo binaire pour rester dans les limites INT)
                $stock_id_product = -1;
                $stock_id_product_attribute = crc32($mappedReference) & 2147483647;
            }
        }
        
        $product = new Product($id_product);
        // Mapping spécial : Store Essonne (19) utilise le stock du Store Seine et Marne (14)
        $store_id_for_stock = ($id_store == 19) ? 14 : $id_store;
        $quantity_by_store = Db::getInstance()->getValue('SELECT quantity FROM ' . _DB_PREFIX_ . 'ckcstockstores_stocks WHERE id_product = ' . (int)$stock_id_product . ' AND id_product_attribute = ' . (int)$stock_id_product_attribute . ' AND id_store = ' . (int)$store_id_for_stock);
        $quantity = Db::getInstance()->getValue('SELECT SUM(quantity) FROM ' . _DB_PREFIX_ . 'ckcstockstores_stocks WHERE id_product = ' . (int)$stock_id_product . ' AND id_product_attribute = ' . (int)$stock_id_product_attribute);
        
        // Gérer les valeurs null/false
        $quantity_by_store = ($quantity_by_store !== false && $quantity_by_store !== null) ? (int)$quantity_by_store : 0;
        $quantity = ($quantity !== false && $quantity !== null) ? (int)$quantity : 0;

        $seuilCategories = json_decode(file_get_contents(_PS_MODULE_DIR_ . 'ckc_stores_stock/assets/js/seuilCategories.json'), true);
        $packIdList = AdvancedPack::getIdsPacks(true);
        $isKit = in_array((int)$id_product, $packIdList);

        $seuilOk = true;
        $seuil = 0;
        foreach ($seuilCategories as $seuilCategory) {
            if (isset($seuilCategory['id_category']) && $seuilCategory['id_category'] == $product->id_category_default) {
                if ($quantity < $seuilCategory['seuil']) {
                    $seuilOk = false;
                }
                $seuil = $seuilCategory['seuil'];
            }
        }

        $stores = $this->getStoresData($stock_id_product, $stock_id_product_attribute);
        // Calculer le total en excluant le magasin 19 (qui est juste un affichage visuel du 14)
        $total_quantity = array_sum(array_map(function($store) {
            return $store['id'] == 19 ? 0 : $store['stock'];
        }, $stores));

        // Récupérer le statut "fin de vie" et "fin de vie national" et le seuil de stock de la déclinaison
        $fin_de_vie = false;
        $fin_de_vie_national = false;
        $stock_threshold = null;
        $victime_succes = false;
        $platform_stock = 0;

        if ($id_product_attribute > 0) {
            $eol_data = Db::getInstance()->getRow('SELECT end_of_life, end_of_life_national, stock_threshold FROM ' . _DB_PREFIX_ . 'product_attribute_end_of_life WHERE id_product_attribute = ' . (int)$id_product_attribute);
            if ($eol_data) {
                $fin_de_vie = (bool)$eol_data['end_of_life'];
                $fin_de_vie_national = isset($eol_data['end_of_life_national']) ? (bool)$eol_data['end_of_life_national'] : false;
                $stock_threshold = $eol_data['stock_threshold'] ? (int)$eol_data['stock_threshold'] : null;

                // Si fin de vie, récupérer le stock de la plateforme (store 18 = DX)
                if ($fin_de_vie) {
                    $platform_stock = Db::getInstance()->getValue('SELECT quantity FROM ' . _DB_PREFIX_ . 'ckcstockstores_stocks WHERE id_product = ' . (int)$stock_id_product . ' AND id_product_attribute = ' . (int)$stock_id_product_attribute . ' AND id_store = 18');
                    // Gérer les valeurs null/false
                    $platform_stock = ($platform_stock !== false && $platform_stock !== null) ? (int)$platform_stock : 0;

                    // Pour les produits fin de vie, vérifier avec le stock plateforme
                    if ($stock_threshold !== null && $platform_stock <= $stock_threshold) {
                        $victime_succes = true;
                    }
                } elseif ($fin_de_vie_national) {
                    // Si fin de vie national, utiliser le stock total national (même comportement visuel que fin de vie)
                    // Pour les produits fin de vie national, vérifier avec le stock total
                    if ($stock_threshold !== null && $total_quantity <= $stock_threshold) {
                        $victime_succes = true;
                    }
                } else {
                    // Pour les produits normaux, vérifier avec le stock total
                    if ($stock_threshold !== null && $total_quantity <= $stock_threshold) {
                        $victime_succes = true;
                    }
                }
            }
        }

        $response = [
            'quantity' => $quantity_by_store,
            'total_quantity' => $total_quantity,
            'platform_stock' => $platform_stock,
            'id_store' => $id_store,
            'store_name' => (new Shop($id_store))->name,
            'seuil' => $seuilOk,
            'isKit' => $isKit,
            'stores' => $stores,
            'fin_de_vie' => $fin_de_vie,
            'fin_de_vie_national' => $fin_de_vie_national,
            'stock_threshold' => $stock_threshold,
            'victime_succes' => $victime_succes,
        ];

        // Mode "pack détaillé" (test) : renvoyer le stock national par composant
        if (!empty($packList) && is_array($packList)) {
            $packItems = [];
            $packPossible = null;

            foreach ($packList as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $itemProductId = isset($item['id_product']) ? (int) $item['id_product'] : 0;
                if ($itemProductId <= 0) {
                    continue;
                }

                $itemAttrId = isset($item['id_product_attribute']) ? (int) $item['id_product_attribute'] : 0;
                if ($itemAttrId <= 0) {
                    $itemAttrId = (int) Product::getDefaultAttribute($itemProductId);
                }

                $qtyInPack = isset($item['qty_in_pack']) ? (int) $item['qty_in_pack'] : 1;
                if ($qtyInPack <= 0) {
                    $qtyInPack = 1;
                }

                $itemStores = $this->getStoresData($itemProductId, $itemAttrId);
                $itemTotal = array_sum(array_map(function($store) {
                    return $store['id'] == 19 ? 0 : $store['stock'];
                }, $itemStores));

                $possibleForItem = (int) floor($itemTotal / $qtyInPack);
                $packPossible = ($packPossible === null) ? $possibleForItem : min($packPossible, $possibleForItem);

                $packItems[] = [
                    'key' => isset($item['key']) ? (string) $item['key'] : ($itemProductId . ':' . $itemAttrId),
                    'id_product' => $itemProductId,
                    'id_product_attribute' => $itemAttrId,
                    'qty_in_pack' => $qtyInPack,
                    'name' => isset($item['name']) ? (string) $item['name'] : ('Produit #' . $itemProductId),
                    'total_quantity' => (int) $itemTotal,
                    'stores' => array_map(function ($store) {
                        return [
                            'id' => (int) $store['id'],
                            'stock' => (int) $store['stock'],
                            'seuil' => (bool) $store['seuil'],
                        ];
                    }, $itemStores),
                ];
            }

            $response['pack_items'] = $packItems;
            $response['pack_summary'] = [
                'stock_pack_possible_national' => (int) ($packPossible ?? 0),
            ];
        }

        return $response;
    }

    public function getStoresData($id_product, $id_product_attribute)
    {
        $shops = Db::getInstance()->executeS('SELECT id_store, name FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores');
        $stores = [];

        foreach ($shops as $shop) {
            // Mapping spécial : Store Essonne (19) utilise le stock du Store Seine et Marne (14) pour l'affichage dans la modal
            $store_id_for_stock = ($shop['id_store'] == 19) ? 14 : $shop['id_store'];
            $quantity_by_store = Db::getInstance()->getValue('SELECT quantity FROM ' . _DB_PREFIX_ . 'ckcstockstores_stocks WHERE id_product = ' . (int)$id_product . ' AND id_product_attribute = ' . (int)$id_product_attribute . ' AND id_store = ' . (int)$store_id_for_stock);
            // Gérer les valeurs null/false
            $quantity_by_store = ($quantity_by_store !== false && $quantity_by_store !== null) ? (int)$quantity_by_store : 0;

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
            $ville = '';
            $result = Db::getInstance()->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'ckcstorespresentation"');
            if (count($result) > 0) {
                $storePresentation = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ckcstorespresentation WHERE id_multiboutique = ' . (int)$shop['id_store']);
                if ($storePresentation) {
                    $address = $storePresentation['adresse'].'<br>'.$storePresentation['code_postal'].' '.$storePresentation['ville'];
                    $ville = $storePresentation['ville'];
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
                'ville' => $ville,
            ];
        }

        return $stores;
    }

    public function getDelivery($carrier_id, $id_store = null)
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

        if ($id_store) {
            foreach ($shops as $key => $shop) {
                if (!$storesDelivery) {
                    if ($id_store == $shop['id_store']) {
                        $storesDelivery = $shop['id_store'];
                        $store_name = $shop['name'];
                    }
                }
            }
        }else{
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
