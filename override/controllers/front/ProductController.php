<?php

class ProductController extends ProductControllerCore
{
    /*
    * module: stoverride
    * date: 2017-11-14 22:28:50
    * version: 1.2.0
    */

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

    /**
     * @return void
     */
//    public function initContent()
//    {
//        parent::initContent();
//
//        //current group
//        $id_shop_group = (int)Shop::getContextShopGroupID();
//
//        $arrShop = [];
//
//        $shops = Shop::getShops();
//
//        $promo_pro = null;
//        $reduction = 0;
//        $sql       = 'SELECT * FROM ' . _DB_PREFIX_ . 'group WHERE id_group = 10';
//        $groups    = Db::getInstance()->executeS($sql);
//        if (!empty($groups)) {
//            $reduction = $groups[0]['reduction'];
//        }
//
//        if ($reduction > 0) {
//            $promo_pro = ['type' => 'promo', 'label' => "-".round($reduction, 0)."%"];
//        }
//
//        foreach ($shops as &$shop) {
//
//            if (!empty($shop['active']) && $shop['active'] == 1) {
//                $shopGroup = Shop::getGroupFromShop($shop['id_shop'], false);
//
//                // Constante DISTRICLOS (il semblerait que le reste ne soit pas public (marketplace?)
//                // J'exclue volontairement le shop France
//                if (!empty($shopGroup) && $shopGroup['id'] == $id_shop_group && $shop['id_shop'] != 1) {
//                    $arrShop[$shop['id_shop']] = $shop['name'];
//                }
//            }
//        }
//        // Déterminer si le script s'exécute localement pour vianney et flemming, yan..
//        $isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', 'districlos.local']);
//
//        $basePath = $isLocal ? 'themes/auto_store/assets/' : '/var/www/districlos/themes/auto_store/assets/';
//
//        asort($arrShop, SORT_REGULAR);
//        $id_product           = (int)Tools::getValue('id_product');
//        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
//        $product              = new Product($id_product);
//        $quantity             = Product::getQuantity($id_product, $id_product_attribute);
//        $nom_du_fichier       = $basePath . 'seuilCategories.json';
//        $seuilCategories      = file_get_contents($nom_du_fichier);
//        $seuilCategories      = json_decode($seuilCategories, 1);
//        $seuilOk              = true;
//        $seuil                = 0;
//        $storesComplete       = $this->getTemplateVarStores();
//        foreach ($seuilCategories as $seuilCategory) {
//            if (isset($seuilCategory['category_id']) && $seuilCategory['category_id'] == $product->id_category_default) {
//
//                if ($quantity < $seuilCategory['seuil']) {
//                    $seuilOk = false;
//                }
//                $seuil = $seuilCategory['seuil'];
//            }
//
//        }
//        $totalQte = 0;
//        $seuilOk  = true;
//        $inSock = true;
//        $packIdList = AdvancedPack::getIdsPacks(true);
//        $isPack = (is_array($packIdList) && in_array((int)$id_product, $packIdList));
//
//        foreach ($storesComplete as $key => $store) {
//            if ($store['stock'] < $seuil) {
//                $storesComplete[$key]['seuil'] = false;
//            }
//            else {
//                $storesComplete[$key]['seuil'] = true;
//            }
//            if (in_array($id_product, $this->exIdsStock) || $isPack) {
//                $jsonData                      = file_get_contents($basePath . 'kits.json');
//                $data                          = json_decode($jsonData, true);
//                $storesComplete[$key]['seuil'] = true;
//                $storesComplete[$key]['stock'] = 0;
//                if (!$isPack) {
//                    foreach ($data as $product_kit) {
//                        if ($product_kit['id_kit'] == $id_product && $product_kit['id_attribute'] == $product->getAttributeCombinationsById($id_product_attribute, $this->context->language->id)[2]['id_attribute']) {
//                            $product_composant_attribute_id = Product::getProductAttributesIds($product_kit['id_product'])[0] ?? null;
//                            if ($product_composant_attribute_id) {
//                                $product_composant_attribute_id = $product_composant_attribute_id['id_product_attribute'];
//                            }
//                            $product_composant             = StockAvailable::getQuantityAvailableByProduct($product_kit['id_product'], $product_composant_attribute_id, $store['id_shop']);
//                            $storesComplete[$key]['isKit'] = true;
//                            if ((int)$product_composant < $product_kit['quantity']) {
//                                $storesComplete[$key]['seuil'] = false;
//                                $totalQte                      += $product_kit['quantity'];
//                                $storesComplete[$key]['stock'] = 1;
//                                $seuilOk                       = false;
//                            }
//                        }
//                    }
//                }else{
//                    $packContent = AdvancedPack::getPackContent($id_product);
//                    $storesComplete[$key]['seuil'] = true;
//
//                    foreach ($packContent as $productContent) {
//                        $quantity = StockAvailable::getQuantityAvailableByProduct($productContent['id_product'], $productContent['default_id_product_attribute'], $store['id_shop']);
//                        $storesComplete[$key]['isKit'] = true;
//                        if ($quantity <= 0) {
//                            $storesComplete[$key]['seuil'] = false;
//                        }else{
//                            $storesComplete[$key]['stock'] = 1;
//                            $totalQte += $quantity;
//                        }
//                    }
//                }
//            }
//        }
//        $isKit = false;
//        if (in_array($id_product, $this->exIdsStock) || $isPack) {
//            $quantity = $totalQte;
//            $isKit    = true;
//
//            if ($isPack) {
//                $seuilOk = false;
//                foreach ($storesComplete as $key => $store) {
//                    if ($store['seuil']) {
//                        $seuilOk = true;
//                    }
//                }
//            }
//        }
//
//        // read store actif
//        $this->context->smarty->assign([
//                                           'promo_pro'      => $promo_pro,
//                                           'stores'         => $arrShop,
//                                           'storesComplete' => $storesComplete,
//                                           'quantity'       => $quantity,
//                                           'seuil'          => $seuilOk,
//                                           'isKit'          => $isKit,
//                                       ]);
//
//    }

    /**
     * @return void
     */
//    public function displayAjaxStock()
//    {
//        header('Content-Type: application/json');
//        $id_store = null;
//        $store    = [
//            'name' => null
//        ];
//        if (!empty($_POST['id_store'])) {
//            $id_store = $_POST['id_store'];
//            $store    = new Shop($id_store);
//        }
//
//        $id_product           = (int)Tools::getValue('id_product');
//        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
//        $packList             = Tools::getValue('packList', []);
//        $product              = new Product($id_product);
//        $quantity_by_store    = StockAvailable::getQuantityAvailableByProduct($id_product, $id_product_attribute, $id_store);
//        $quantity             = Product::getQuantity($id_product, $id_product_attribute);
//        $stores               = array_values(static::getTemplateVarStores());
//        $seuilCategories      = file_get_contents('/var/www/districlos/themes/auto_store/assets/seuilCategories.json');
//        $seuilCategories      = json_decode($seuilCategories, 1);
//        $seuilOk              = true;
//        $seuil                = 0;
//        $reference            = $this->getTemplateVarProduct()->reference_to_display;
//
//        foreach ($seuilCategories as $seuilCategory) {
//            if ($seuilCategory['category_id'] == $product->id_category_default) {
//                if ($quantity < $seuilCategory['seuil']) {
//                    $seuilOk = false;
//                }
//                $seuil = $seuilCategory['seuil'];
//            }
//
//        }
//
//        $totalQte = 0;
//        $isKit = false;
//        $packIdList = AdvancedPack::getIdsPacks(true);
//
//        $isPack = (is_array($packIdList) && in_array((int)$id_product, $packIdList));
//
//        foreach ($stores as $key => $a_store) {
//            if ($a_store['stock'] < $seuil) {
//                $stores[$key]['seuil'] = false;
//            }
//            else {
//                $stores[$key]['seuil'] = true;
//            }
//            if (in_array($id_product, $this->exIdsStock) || $isPack) {
//                $stores[$key]['seuil'] = true;
//                $stores[$key]['stock'] = rand(25, 50);
//                $totalQte              = 0;
//
//                $isKit = true;
//                $isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', 'districlos.local']);
//
//                $basePath = $isLocal ? 'themes/auto_store/assets/' : '/var/www/districlos/themes/auto_store/assets/';
//                $jsonData                      = file_get_contents($basePath . 'kits.json');
//                $data                          = json_decode($jsonData, true);
//                $stores[$key]['seuil'] = true;
//                $stores[$key]['stock'] = 0;
//                if (!$isPack) {
//                    foreach ($data as $product_kit) {
//                        if ($product_kit['id_kit'] == $id_product && $product_kit['id_attribute'] == $product->getAttributeCombinationsById($id_product_attribute, $this->context->language->id)[2]['id_attribute']) {
//                            $product_composant_attribute_id = Product::getProductAttributesIds($product_kit['id_product'])[0] ?? null;
//                            if ($product_composant_attribute_id) {
//                                $product_composant_attribute_id = $product_composant_attribute_id['id_product_attribute'];
//                            }
//                            $product_composant             = StockAvailable::getQuantityAvailableByProduct($product_kit['id_product'], $product_composant_attribute_id, $a_store['id_shop']);
//
//                            $stores[$key]['isKit'] = true;
//                            if ((int)$product_composant < $product_kit['quantity']) {
//                                $stores[$key]['seuil'] = false;
//                                $stores[$key]['stock'] = 1;
//                                $seuilOk                       = false;
//                            }
//                        }
//                    }
//                }else{
//                    $stores[$key]['seuil'] = true;
//                    $packContent = AdvancedPack::getPackContent($id_product);
//                    $stores[$key]['isKit'] = true;
//                    if (sizeof($packList) > 0) {
//                        foreach ($packList as $keyPack => $productContent) {
//                            foreach ($packContent as $pack) {
//                                if($pack['id_product_pack'] == $keyPack) {
//                                    $quantity = StockAvailable::getQuantityAvailableByProduct($pack['id_product'], $productContent, $a_store['id_shop']);
//                                    if ($quantity <= 0) {
//                                        $stores[$key]['seuil'] = false;
//                                    }else{
//                                        $stores[$key]['stock'] = 1;
//                                    }
//                                }
//                            }
//                        }
//                    }else{
//                        foreach ($packContent as $productContent) {
//                            $quantity = StockAvailable::getQuantityAvailableByProduct($productContent['id_product'], $productContent['default_id_product_attribute'], $a_store['id_shop']);
//                            if ($quantity <= 0) {
//                                $stores[$key]['seuil'] = false;
//                            }else{
//                                $stores[$key]['stock'] = 1;
//                            }
//                        }
//                    }
//
//                }
//
//                if ($stores[$key]['seuil'] == true) {
//                    $stores[$key]['stock'] = 1;
//                }
//
//                if ($isPack) {
//                    $seuilOk = AdvancedPack::isInStock($id_product);
//                }
//            }
//        }
//
//        if ($isPack) {
//            $quantity = 0;
//            foreach ($stores as $b_store) {
//                if ($b_store['seuil']) {
//                    $quantity = 1;
//                }
//            }
//        }
//
//        echo json_encode(['quantity' => $quantity_by_store, 'total_quantity' => $quantity, 'id_store' => $id_store, 'store_name' => $store->name, 'seuil' => $seuilOk, 'stores' => $stores, 'id_product_attribute' => $id_product_attribute, 'isKit' => $isKit, 'reference_to_display' => $reference]);
//    }
//
//    public function displayAjaxSetShop()
//    {
//        header('Content-Type: application/json');
//        $id_store = null;
//        if (!empty($_POST['id_store'])) {
//            $id_store = $_POST['id_store'];
//        }
//        $this->context->cookie->__set('defaultStore', $id_store);
//        echo json_encode(['success' => true]);
//    }

//    public function getTemplateVarStores()
//    {
//        $shops  = Shop::getShops();
//        $stores = Store::getStores($this->context->language->id);
//
//        //$this->context->shop->getGroup()->getShopsFromGroup(1) excep 1
//        $imageRetriever = new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever($this->context->link);
//        foreach ($shops as $key => &$store) {
//
//            if (strstr($store['name'], 'DISTRICLOS') && $store['id_shop'] != "1" && $store['id_shop'] != "12") {
//                //unset($store['active']);
//                $store['selected'] = '0';
//                if ($this->context->cookie->defaultStore && $this->context->cookie->defaultStore == $store['id']) {
//                    $store['selected'] = '1';
//                }
//                $store['code'] = str_replace(' ', '', strtolower(str_replace('DISTRICLOS ', '', $store['name'])));
//                $storeByName   = static::getStoreByName($stores, $store['name']);
//                if ($storeByName) {
//                    $address          = new Address();
//                    $store['address'] = [];
//                    $attr             = ['address1', 'address2', 'postcode', 'city', 'id_state'];
//                    foreach ($attr as $a) {
//                        $address->{$a}        = $storeByName[$a];
//                        $store['address'][$a] = $storeByName[$a];
//                        unset($store[$a]);
//                    }
//                    $store['address']['formatted'] = AddressFormat::generateAddress($address, [], '<br />');
//                    /*$store['image'] = $imageRetriever->getImage(new Store($storeByName['id_store']), $storeByName['id_store']);
//
//                    if (is_array($storeByName['image'])) {
//                        $store['image'] = [
//                            'legend' => $store['image']['legend'][$this->context->language->id]
//                        ];
//                    }*/
//                }
//                else {
//                    $store['address'] = false;
//                }
//
//                $id_product           = (int)Tools::getValue('id_product');
//                $id_product_attribute = (int)Tools::getValue('id_product_attribute');
//                $store['id']          = $store['id_shop'];
//                $quantity_by_store    = StockAvailable::getQuantityAvailableByProduct($id_product, $id_product_attribute, $store['id_shop']);
//                $store['stock']       = $quantity_by_store;
//                if ($quantity_by_store <= 8) {
//                    $seuilOk = false;
//                }
//                else {
//                    $seuilOk = true;
//                }
//                $store['seuil'] = $seuilOk;
//            }
//            else {
//                unset($shops[$key]);
//            }
//        }
//        return $shops;
//    }
//
//    private static function getStoreByName($stores, $name)
//    {
//
//        foreach ($stores as $key => $store) {
//            if (strtoupper($store['name']) == strtoupper($name)) {
//                return $store;
//            }
//        }
//        return null;
//    }

    public function displayAjaxGetDeliveries()
    {
        $postalCode           = (int)Tools::getValue('postal_code');
        $id_product           = (int)Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        $quantity             = (int)Tools::getValue('quantity', 1);
        header('Content-Type: application/json');
        if (!$postalCode) {
            echo json_encode([
                                 'success' => false,
                                 'message' => 'Postal code is required'
                             ]);
            return;
        }
        if (!$id_product) {
            echo json_encode([
                                 'success' => false,
                                 'message' => 'Product is required'
                             ]);
            return;
        }
        $deliveries_options = $this->getDeliveryOptionsByPostalCode($postalCode, $id_product, $id_product_attribute, $quantity);
        if (sizeof($deliveries_options) == 0) {
            echo json_encode([
                                 'success' => false,
                                 'message' => 'No delivery options found'
                             ]);
            return;
        }
        echo json_encode([
                             'success'            => true,
                             'deliveries_options' => array_values($deliveries_options)
                         ]);
    }

    public function getDeliveryOptionsByPostalCode($postalCode, $idProduct, $idProductAttribute, $quantity)
    {
        $savedCart = $this->context->cart->saveCartContent();
        $this->context->cart->clearCart();
        if (!$this->context->cart->id) {
            $this->context->cart->add();
        }
        $this->context->cart->updateQty($quantity, $idProduct, $idProductAttribute, false, 'up');

        $originalAddressId = $this->context->cart->id_address_delivery;

        $tempAddress              = new Address();
        $tempAddress->alias       = 'temp';
        $tempAddress->firstname   = 'temp';
        $tempAddress->lastname    = 'temp';
        $tempAddress->address1    = 'temp';
        $tempAddress->postcode    = $postalCode;
        $tempAddress->city        = 'temp';
        $tempAddress->id_customer = Context::getContext()->cookie->id_customer;
        $tempAddress->id_country  = 8;
        $tempAddress->deleted     = 1;
        $tempAddress->save();

        $this->context->cart->updateAddressId($this->context->cart->id_address_delivery, $tempAddress->id);

        if (Context::getContext()->cookie->id_customer) {
            foreach ($this->context->cart->getProducts() as $product) {
                $this->context->cart->setProductAddressDelivery($product['id_product'], $product['id_product_attribute'], $product['id_address_delivery'], $tempAddress->id);
            }
        }

        $delivery_option_list = $this->context->cart->getDeliveryOptionList(null, true);

        $include_taxes       = !Product::getTaxCalculationMethod((int)$this->context->cart->id_customer) && (int)Configuration::get('PS_TAX');
        $display_taxes_label = (Configuration::get('PS_TAX') && !Configuration::get('AEUC_LABEL_TAX_INC_EXC'));

        $carriers_available = [];
        $priceFormatter     = new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter();
        if (substr($postalCode, 0, 1) == 0) {
            $postalCode = substr($postalCode, 1);
        }
        $sql     = 'SELECT *
					FROM `' . _DB_PREFIX_ . 'zip_code_zone` z
					WHERE "' . $postalCode . '" BETWEEN z.`min` AND z.`max`';
        $result  = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        $id_zone = $result['id_zone'];
        if (isset($delivery_option_list[$this->context->cart->id_address_delivery])) {
            foreach ($delivery_option_list[$this->context->cart->id_address_delivery] as $id_carriers_list => $carriers_list) {
                foreach ($carriers_list as $carriers) {
                    if (is_array($carriers)) {
                        foreach ($carriers as $carrier) {
                            $carrier = array_merge($carrier, $this->objectPresenter->present($carrier['instance']));
                            $delay   = $carrier['delay'][$this->context->language->id];
                            unset($carrier['instance'], $carrier['delay']);
                            $carrier['delay'] = $delay;
                            $sql              = 'SELECT d.`price`
                                FROM `' . _DB_PREFIX_ . 'delivery` d
                                LEFT JOIN `' . _DB_PREFIX_ . 'range_price` r ON d.`id_range_price` = r.`id_range_price`
                                WHERE d.`id_zone` = ' . (int)$id_zone . '
                                    AND ' . (float)$this->context->cart->getOrderTotal() . ' >= r.`delimiter1`
                                    AND ' . (float)$this->context->cart->getOrderTotal() . ' < r.`delimiter2`
                                    AND d.`id_carrier` = ' . $carrier['id'] . '
                                    ' . Carrier::sqlDeliveryRangeShop('range_price') . '
                                ORDER BY r.`delimiter1` ASC';
                            $result           = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                            if (!$result) {
                                $sql    = 'SELECT d.`price`
                                FROM `' . _DB_PREFIX_ . 'delivery` d
                                LEFT JOIN `' . _DB_PREFIX_ . 'range_weight` r ON d.`id_range_weight` = r.`id_range_weight`
                                WHERE d.`id_zone` = ' . (int)$id_zone . '
                                    AND ' . (float)$this->context->cart->getTotalWeight() . ' >= r.`delimiter1`
                                    AND ' . (float)$this->context->cart->getTotalWeight() . ' < r.`delimiter2`
                                    AND d.`id_carrier` = ' . $carrier['id'] . '
                                    ' . Carrier::sqlDeliveryRangeShop('range_weight') . '
                                ORDER BY r.`delimiter1` ASC';
                                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                            }
                            if ($result) {
                                $price = $result['price'];
                                if ($this->isFreeShipping($this->context->cart, $carriers_list) && (!$price || (float)$price == 0)) {
                                    $carrier['price'] = $this->translator->trans('Free', [], 'Shop.Theme.Checkout');
                                }
                                else {
                                    if ($include_taxes) {
                                        $carrier['reduc'] = $priceFormatter->format(ceil(($carriers_list['total_price_with_tax'] + ($price * (1 + 20 / 100))) * 2));
                                        $carrier['price'] = $priceFormatter->format($carriers_list['total_price_with_tax'] + ($price * (1 + 20 / 100)));
                                        if ($display_taxes_label) {
                                            $carrier['price'] = $this->translator->trans('%price% tax incl.', ['%price%' => $carrier['price']], 'Shop.Theme.Checkout');
                                        }
                                    }
                                    else {
                                        $carrier['reduc'] = $priceFormatter->format(ceil(($carriers_list['total_price_without_tax'] + $price) * 2));
                                        $carrier['price'] = $priceFormatter->format($carriers_list['total_price_without_tax'] + $price);
                                        if ($display_taxes_label) {
                                            $carrier['price'] = $this->translator->trans('%price% tax excl.', ['%price%' => $carrier['price']], 'Shop.Theme.Checkout');
                                        }
                                    }
                                }
                                if (count($carriers) > 1) {
                                    $carrier['label'] = $carrier['price'];
                                }
                                else {
                                    $carrier['label'] = $carrier['name'] . ' - ' . $carrier['delay'] . ' - ' . $carrier['price'];
                                }
                                if ($carrier['is_module']) {
                                    if ($moduleId = Module::getModuleIdByName($carrier['external_module_name'])) {
                                        $carrier['extraContent'] = Hook::exec('displayCarrierExtraContent', ['carrier' => $carrier], $moduleId);
                                    }
                                }
                                $carriers_available[$id_carriers_list] = $carrier;
                            }
                        }
                    }
                }
            }
        }
        $this->context->cart->clearCart();
        $this->context->cart->restoreCartContent($savedCart);
        $this->context->cart->updateAddressId($tempAddress->id, $originalAddressId);

        return $carriers_available;
    }

    private function isFreeShipping($cart, array $carrier)
    {
        $free_shipping = false;

        if ($carrier['is_free']) {
            $free_shipping = true;
        }
        else {
            foreach ($cart->getCartRules() as $rule) {
                if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                    $free_shipping = true;

                    break;
                }
            }
        }

        return $free_shipping;
    }

}
