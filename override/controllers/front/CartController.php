<?php
use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;
class CartController extends CartControllerCore
{
    protected function updateCart()
    {
        if ($this->context->cookie->exists()
            && !$this->errors

        ) {
            if (Tools::getIsset('add') || Tools::getIsset('update')) {
                $this->processChangeProductInCart();
            } elseif (Tools::getIsset('delete')) {
                $this->processDeleteProductInCart();
            } // Vider le panier
            elseif (Tools::getIsset('deleteAll')) {
                $this->context->cart->delete();
                $this->context->cookie->id_cart = 0;
                Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);
            } //
            elseif (CartRule::isFeatureActive()) {
                if (Tools::getIsset('addDiscount')) {
                    if (!($code = trim(Tools::getValue('discount_name')))) {
                        $this->errors[] = $this->trans(
                            'You must enter a voucher code.',
                            [],
                            'Shop.Notifications.Error'
                        );
                    } elseif (!Validate::isCleanHtml($code)) {
                        $this->errors[] = $this->trans(
                            'The voucher code is invalid.',
                            [],
                            'Shop.Notifications.Error'
                        );
                    } else {
                        if (($cartRule = new CartRule(CartRule::getIdByCode($code)))
                            && Validate::isLoadedObject($cartRule)
                        ) {
                            if ($error = $cartRule->checkValidity($this->context, false, true)) {
                                $this->errors[] = $error;
                            } else {
                                $this->context->cart->addCartRule($cartRule->id);
                            }
                        } else {
                            $this->errors[] = $this->trans(
                                'This voucher does not exist.',
                                [],
                                'Shop.Notifications.Error'
                            );
                        }
                    }
                } elseif (($id_cart_rule = (int) Tools::getValue('deleteDiscount'))
                    && Validate::isUnsignedId($id_cart_rule)
                ) {
                    $this->context->cart->removeCartRule($id_cart_rule);
                    CartRule::autoAddToCart($this->context);
                }
            }
        } elseif (!$this->isTokenValid() && Tools::getValue('action') !== 'show' && !Tools::getValue('ajax')) {
            Tools::redirect('index.php');
        }
    }
    public function initContent()
    {
        if (Configuration::isCatalogMode() && Tools::getValue('action') === 'show') {
            Tools::redirect('index.php');
        }
        if (!Tools::getValue('ajax')) {
            $this->checkCartProductsMinimalQuantities();
        }
        $presenter = new CartPresenter();
        $presented_cart = $presenter->present($this->context->cart, $shouldSeparateGifts = true);
        $prices = [];
        $price_ttc = number_format(round(0, 2), 2, ',', ' ').' €';
        foreach ($presented_cart['products'] as $key => $product) {
            $price = $product->total_wt / $product->quantity;
            if ($product->id_customization) {
                $cust_data = Db::getInstance()->getRow('SELECT *
                FROM `' . _DB_PREFIX_ . 'customized_data`
                WHERE `id_customization` = ' . (int) $product->id_customization);
                $price_ttc = $cust_data['price'] * (1 + ($product->rate / 100));
                if ($product->discount_percentage_absolute) {
                    $total_ttc = $price_ttc - ($price_ttc * ((float) str_replace('%', '', $product->discount_percentage_absolute) / 100));
                }else {
                    $total_ttc = $price_ttc;
                }
                if ($total_ttc > 0) {
                    $price_ttc = number_format(round($price_ttc, 2), 2, ',', ' ').' €';
                    $price = $total_ttc;
                }else{
                    $price = $product->regular_price;
                    $total_ttc  = $product->total_wt;
                }
                dump($price);
                die;
                $price = number_format($price, 2, ',', ' ').' €';
            }else{
                $total_ttc = $product->total_wt;
                $price = number_format($price, 2, ',', ' ').' €';
            }
            $prices[$product->id_product][$product->id_product_attribute] = [
                'regular_price' => $product->regular_price,
                'price_ttc' => $price_ttc,
                'price' => $price,
                'total_ttc' => $total_ttc,
            ];
        }
        $this->context->smarty->assign([
            'cart' => $presented_cart,
            'static_token' => Tools::getToken(false),
            'prices' => $prices,
        ]);
        if (count($presented_cart['products']) > 0) {
            $this->setTemplate('checkout/cart');
        } else {
            $this->context->smarty->assign([
                'allProductsLink' => $this->context->link->getCategoryLink(Configuration::get('PS_HOME_CATEGORY')),
            ]);
            $this->setTemplate('checkout/cart-empty');
        }

        $products = array();
        $custom_product_message = [];
        foreach ($this->context->cart->getProducts() as $product) {
            $products[] = $product;
        }

        // tableau des IDs des produits du panier
        $product_ids = array_column($products, 'id_product');

        if (in_array(1215, $product_ids) && in_array(1214, $product_ids)) {
            $custom_product_message['1215'] = 'Attention : Les platines choisies ne sont pas compatibles avec le portillon Jardi.';
        } elseif (in_array(1213, $product_ids) && in_array(332, $product_ids)) {
            $custom_product_message['1213'] = 'Attention : Les platines choisies ne sont pas compatibles avec le portillon Garden.';
        } elseif (in_array(1302, $product_ids) && in_array(416, $product_ids)) {
            $encoches_declination_found = false;
            foreach ($products as $product) {
                if ($product['id_product'] == 416 && strpos($product['attributes'], 'encoches') !== false) {
                    $encoches_declination_found = true;
                    break;
                }
            }
            if ($encoches_declination_found) {
                $custom_product_message['1302'] = 'Attention : Les poteaux à clip ne sont pas compatibles avec les soubassements à encoche';
            }
        } elseif ((in_array(1223, $product_ids) || in_array(1285, $product_ids)) && in_array(416, $product_ids)) {
            $clips_declination_found = false;
            foreach ($products as $product) {
                if ($product['id_product'] == 416 && strpos($product['attributes'], 'clips') !== false) {
                    $clips_declination_found = true;
                    break;
                }
            }
            if ($clips_declination_found) {
                $custom_product_message['1223'] = 'Attention : Les poteaux à encoche ne sont pas compatibles avec le soubassements à clips';
                $custom_product_message['1285'] = 'Attention : Les poteaux à encoche ne sont pas compatibles avec le soubassements à clips';
            }
        }

        $has_1305_or_1306 = in_array(1305, $product_ids) || in_array(1306, $product_ids);
        $has_1130 = in_array(1130, $product_ids);

        if ($has_1305_or_1306 && !$has_1130) {
            if (in_array(1305, $product_ids)) {
                $custom_product_message['1305'] = 'Attention : Les crémaillères ne sont pas incluses dans ce moteur. <a href="/accessoires-portails-portillons/1130-cremaillere-portail-coulissant.html">Cliquer ici pour l\'ajouter au panier</a>';
            }
            if (in_array(1306, $product_ids)) {
                $custom_product_message['1306'] = 'Attention : Les crémaillères ne sont pas incluses dans ce moteur. <a href="/accessoires-portails-portillons/1130-cremaillere-portail-coulissant.html">Cliquer ici pour l\'ajouter au panier</a>';
            }
        }


        $this->context->smarty->assign('custom_product_message', $custom_product_message);

        parent::initContent();
    }
    public function getDeliveryOptionsByPostalCode($postalCode)
    {
        $originalAddressId = $this->context->cart->id_address_delivery;
        $tempAddress = new Address();
        $tempAddress->alias = 'temp';
        $tempAddress->firstname = 'temp';
        $tempAddress->lastname = 'temp';
        $tempAddress->address1 = 'temp';
        $tempAddress->postcode = $postalCode;
        $tempAddress->city = 'temp';
        $tempAddress->id_customer = Context::getContext()->cookie->id_customer;
        $tempAddress->id_country = 8;
        $tempAddress->deleted = 1;
        $tempAddress->save();
        $this->context->cart->updateAddressId($this->context->cart->id_address_delivery, $tempAddress->id);
        if (Context::getContext()->cookie->id_customer) {
            foreach ($this->context->cart->getProducts() as $product) {
                $this->context->cart->setProductAddressDelivery($product['id_product'], $product['id_product_attribute'], $product['id_address_delivery'], $tempAddress->id);
            }
        }
        $delivery_option_list = $this->context->cart->getDeliveryOptionList(null, true);
        $include_taxes = !Product::getTaxCalculationMethod((int) $this->context->cart->id_customer) && (int) Configuration::get('PS_TAX');
        $display_taxes_label = (Configuration::get('PS_TAX') && !Configuration::get('AEUC_LABEL_TAX_INC_EXC'));
        $carriers_available = [];
        $priceFormatter = new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter();
        if (substr($postalCode, 0, 1) == 0) {
            $postalCode = substr($postalCode, 1);
        }
        $sql = 'SELECT *
					FROM `' . _DB_PREFIX_ . 'zip_code_zone` z
					WHERE "'.$postalCode.'" BETWEEN z.`min` AND z.`max`';
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        $id_zone = $result['id_zone'];
        if (isset($delivery_option_list[$this->context->cart->id_address_delivery])) {
            foreach ($delivery_option_list[$this->context->cart->id_address_delivery] as $id_carriers_list => $carriers_list) {
                foreach ($carriers_list as $carriers) {
                    if (is_array($carriers)) {
                        foreach ($carriers as $carrier) {
                            $carrier = array_merge($carrier, $this->objectPresenter->present($carrier['instance']));
                            $delay = $carrier['delay'][$this->context->language->id];
                            unset($carrier['instance'], $carrier['delay']);
                            $carrier['delay'] = $delay;
                            $sql = 'SELECT d.`price`
                                FROM `' . _DB_PREFIX_ . 'delivery` d
                                LEFT JOIN `' . _DB_PREFIX_ . 'range_price` r ON d.`id_range_price` = r.`id_range_price`
                                WHERE d.`id_zone` = ' . (int) $id_zone . '
                                    AND ' . (float) $this->context->cart->getOrderTotal() . ' >= r.`delimiter1`
                                    AND ' . (float) $this->context->cart->getOrderTotal() . ' < r.`delimiter2`
                                    AND d.`id_carrier` = ' . $carrier['id'] . '
                                    ' . Carrier::sqlDeliveryRangeShop('range_price') . '
                                ORDER BY r.`delimiter1` ASC';
                            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                            if (!$result) {
                                $sql = 'SELECT d.`price`
                                FROM `' . _DB_PREFIX_ . 'delivery` d
                                LEFT JOIN `' . _DB_PREFIX_ . 'range_weight` r ON d.`id_range_weight` = r.`id_range_weight`
                                WHERE d.`id_zone` = ' . (int) $id_zone . '
                                    AND ' . (float) $this->context->cart->getTotalWeight() . ' >= r.`delimiter1`
                                    AND ' . (float) $this->context->cart->getTotalWeight() . ' < r.`delimiter2`
                                    AND d.`id_carrier` = ' . $carrier['id'] . '
                                    ' . Carrier::sqlDeliveryRangeShop('range_weight') . '
                                ORDER BY r.`delimiter1` ASC';
                                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
                            }
                            $price = $result['price'];
                            if ($this->isFreeShipping($this->context->cart, $carriers_list) && (!$price || (float) $price == 0)) {
                                $carrier['price'] = $this->translator->trans('Free', [], 'Shop.Theme.Checkout');
                            } else {
                                if ($include_taxes) {
                                    $carrier['reduc'] = $priceFormatter->format(ceil(($carriers_list['total_price_with_tax'] + ($price * (1 +20 / 100))) * 2));
                                    $carrier['price'] = $priceFormatter->format($carriers_list['total_price_with_tax'] + ($price * (1 +20 / 100)));
                                    if ($display_taxes_label) {
                                        $carrier['price'] = $this->translator->trans('%price% tax incl.', ['%price%' => $carrier['price']], 'Shop.Theme.Checkout');
                                    }
                                } else {
                                    $carrier['reduc'] = $priceFormatter->format(ceil(($carriers_list['total_price_without_tax'] + $price) * 2));
                                    $carrier['price'] = $priceFormatter->format($carriers_list['total_price_without_tax'] + $price);
                                    if ($display_taxes_label) {
                                        $carrier['price'] = $this->translator->trans('%price% tax excl.', ['%price%' => $carrier['price']], 'Shop.Theme.Checkout');
                                    }
                                }
                            }
                            if (count($carriers) > 1) {
                                $carrier['label'] = $carrier['price'];
                            } else {
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
        $this->context->cart->updateAddressId($tempAddress->id, $originalAddressId);
        $carriers_available = array_values($carriers_available);
        usort($carriers_available, function ($a, $b) {
            $parsePrice = function ($price) {
                if ($price === 'Free') {
                    return 0;
                }
                $normalizedPrice = floatval(str_replace(['€', ',', ' ', 'TTC', 'HT'], ['', '.', '', '', ''], $price));
                return $normalizedPrice;
            };
            $priceA = $parsePrice($a['price']);
            $priceB = $parsePrice($b['price']);
            return $priceA <=> $priceB;
        });
        return $carriers_available;
    }
    private function isFreeShipping($cart, array $carrier)
    {
        $free_shipping = false;
        $contains_soub = false;
        foreach ($cart->getProducts() as $product) {
            if (stripos($product['name'], 'Soub') !== false) {
                $contains_soub = true;
                break;
            }
        }
        if (!in_array(array_keys($carrier['carrier_list'])[0], DeliveryOptionsFinderCore::CARRIERS) && $contains_soub) {
            return $free_shipping;
        }
        if ($carrier['is_free']) {
            $free_shipping = true;
        } else {
            foreach ($cart->getCartRules() as $rule) {
                if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                    $free_shipping = true;
                    break;
                }
            }
        }
        return $free_shipping;
    }
    private function checkCartProductsMinimalQuantities()
    {
        $productList = $this->context->cart->getProducts();
        foreach ($productList as $product) {
            if ($product['minimal_quantity'] > $product['cart_quantity']) {
                $this->errors[] = $this->trans(
                    'The minimum purchase order quantity for the product %product% is %quantity%.',
                    [
                        '%product%' => $product['name'],
                        '%quantity%' => $product['minimal_quantity'],
                    ],
                    'Shop.Notifications.Error'
                );
            }
        }
    }
    public function displayAjaxGetDeliveries() {
        $postalCode = (int)Tools::getValue('postal_code');
        header('Content-Type: application/json');
        if (!$postalCode) {
            echo json_encode([
                'success' => false,
                'message' => 'Postal code is required'
            ]);
            return;
        }
        $deliveries_options = $this->getDeliveryOptionsByPostalCode($postalCode);
        if (sizeof($deliveries_options) == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'No delivery options found'
            ]);
            return;
        }
        echo json_encode([
            'success' => true,
            'deliveries_options' => array_values($deliveries_options)
        ]);
    }
    /*
    * module: ets_promotion
    * date: 2025-04-03 10:54:57
    * version: 1.1.6
    */
    public function postProcess()
    {
        parent::postProcess();
        if(Tools::getIsset('addDiscount') && $this->errors && ($code = Tools::getValue('discount_name')) && Validate::isCleanHtml($code) && !CartRule::cartRuleExists($code)) {
            Module::getInstanceByName('ets_promotion')->addCartRule($this->errors,$code);
        }
        if(Tools::isSubmit('deletePromotionDiscountRule') && ($id_ets_rule = Tools::getValue('deletePromotionDiscountRule')) && Validate::isUnsignedId($id_ets_rule) )
        {
            Module::getInstanceByName('ets_promotion')->deleteCartRule($id_ets_rule);
        }
    }
    /*
    * module: ets_promotion
    * date: 2025-04-03 10:54:57
    * version: 1.1.6
    */
    public function displayAjaxRefresh()
    {
        if (Configuration::isCatalogMode()) {
            return;
        }
        ob_end_clean();
        header('Content-Type: application/json');

        Hook::exec('displayAfterCartDetailedActions', array());

        $this->ajaxRender(json_encode([
            'cart_detailed' => Module::isEnabled('ets_extraoptions') ? $this->context->smarty->fetch(_PS_MODULE_DIR_.'ets_extraoptions/views/templates/hook/checkout/cart-detailed.tpl'): $this->render('checkout/_partials/cart-detailed'),
            'cart_detailed_totals' => $this->render('checkout/_partials/cart-detailed-totals'),
            'cart_summary_items_subtotal' => $this->render('checkout/_partials/cart-summary-items-subtotal'),
            'cart_summary_subtotals_container' => $this->render('checkout/_partials/cart-summary-subtotals'),
            'cart_summary_totals' => $this->render('checkout/_partials/cart-summary-totals'),
            'cart_detailed_actions' => $this->render('checkout/_partials/cart-detailed-actions'),
            'cart_voucher' => $this->context->smarty->fetch(_PS_MODULE_DIR_.'ets_promotion/views/templates/hook/checkout/cart-voucher.tpl'),
            'cart_sumary_products' => $this->context->smarty->fetch(_PS_MODULE_DIR_.'ets_promotion/views/templates/hook/checkout/cart_sumary_products.tpl'),
            'cart_promos' => $this->context->smarty->fetch(_PS_MODULE_DIR_.'ckcadcampaignconfigurator/views/templates/front/hook/promo_content_cart.tpl'),
        ]));
    }
}
