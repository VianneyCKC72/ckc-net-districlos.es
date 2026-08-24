<?php
use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;

class CartController extends CartControllerCore
{
    // Constantes pour les IDs produits
    const PRODUCT_ID_CREMALLIERE = 1130;
    const PRODUCT_ID_MOTEUR_1305 = 1305;
    const PRODUCT_ID_MOTEUR_1306 = 1306;
    const PRODUCT_ID_POTEAU_1223 = 1223;
    const PRODUCT_ID_POTEAU_1224 = 1224;
    const PRODUCT_ID_POTEAU_1285 = 1285;
    const PRODUCT_ID_151 = 151;
    const PRODUCT_ID_422 = 422;
    const PRODUCT_ID_1302 = 1302;
    const PRODUCT_ID_1303 = 1303;
    const PRODUCT_ID_1304 = 1304;
    
    // CSS pour les messages de produits manquants (inclus une seule fois)
    private static $product_message_css_included = false;
    
    // Configuration des règles de produits manquants
    private function getMissingProductRules()
    {
        return [
            [
                'trigger_products' => [self::PRODUCT_ID_MOTEUR_1305, self::PRODUCT_ID_MOTEUR_1306],
                'missing_product' => self::PRODUCT_ID_CREMALLIERE,
                'message' => 'Les crémaillères ne sont pas incluses dans ce moteur.||Portail 3m00 : 4 crémaillères - Portail 3m50 et 4m00 : 5 crémaillères',
            ],
            [
                'trigger_products' => [self::PRODUCT_ID_POTEAU_1223, self::PRODUCT_ID_POTEAU_1285],
                'missing_product' => self::PRODUCT_ID_POTEAU_1224,
                'message' => 'N\'oubliez pas les bouchons compatibles avec votre poteau.',
            ],
            [
                'trigger_products' => [self::PRODUCT_ID_151],
                'missing_product' => self::PRODUCT_ID_422,
                'message' => 'Ce produit nécessite le produit complémentaire suivant.',
            ],
        ];
    }
    
    // Configuration pour produit avec multiples produits manquants
    private function getMultipleMissingProductRules()
    {
        return [
            [
                'trigger_product' => self::PRODUCT_ID_1302,
                'missing_products' => [
                    [
                        'id' => self::PRODUCT_ID_1303,
                        'message' => 'N\'oubliez vos clips pour votre poteau.',
                    ],
                    [
                        'id' => self::PRODUCT_ID_1304,
                        'message' => 'N\'oubliez pas la pince à clips pour votre poteau.',
                    ],
                ],
            ],
        ];
    }
    
    protected function updateCart()
    {
        if ($this->context->cookie->exists()
            && !$this->errors

        ) {
            if (Tools::getIsset('add') || Tools::getIsset('update')) {
                // Blocage serveur instantané pour "victime de son succès"
                // (évite la fenêtre de clic avant le retour AJAX du module ckc_stores_stock).
                if ($this->shouldBlockAddToCartForVictimeSucces()) {
                    return;
                }
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

    private function shouldBlockAddToCartForVictimeSucces(): bool
    {
        // Ne bloquer que les opérations "up" (ajout/augmentation) — jamais les suppressions.
        $op = (string) Tools::getValue('op', 'up');
        if ($op !== 'up') {
            return false;
        }

        $idProduct = (int) $this->id_product;
        if ($idProduct <= 0) {
            return false;
        }

        // Déterminer rapidement une déclinaison si nécessaire.
        $idProductAttribute = (int) $this->id_product_attribute;
        if ($idProductAttribute === 0) {
            $idProductAttribute = (int) Tools::getValue('id_product_attribute', Tools::getValue('ipa', 0));
        }
        if ($idProductAttribute === 0) {
            $idProductAttribute = (int) Product::getDefaultAttribute($idProduct);
        }
        if ($idProductAttribute <= 0) {
            // Produit sans déclinaison ou déclinaison introuvable -> on laisse le core gérer.
            return false;
        }

        // Vérifier l'état côté module (source de vérité métier pour "victime de succès").
        $moduleFile = _PS_MODULE_DIR_ . 'ckc_stores_stock/classes/StockManager.php';
        if (!is_file($moduleFile)) {
            return false;
        }
        require_once $moduleFile;

        try {
            $stockManager = new StockManager();
            $stockData = $stockManager->getStockData($idProduct, $idProductAttribute, null);
        } catch (\Throwable $e) {
            // Ne jamais bloquer le panier sur une erreur de calcul stock.
            return false;
        }

        $isVictimeSucces = !empty($stockData['victime_succes']);

        if (!$isVictimeSucces) {
            return false;
        }

        $message = null;
        if (!empty($stockData['i18n']['not_available'])) {
            $message = (string) $stockData['i18n']['not_available'];
        } elseif (!empty($stockData['i18n']['victime_succes'])) {
            $message = (string) $stockData['i18n']['victime_succes'];
        } else {
            $message = 'Ce produit n\'est plus disponible';
        }

        $this->errors[] = $this->trans($message, [], 'Shop.Notifications.Error');
        return true;
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
        // Batch-load toutes les données de customisation en une seule requête
        $custIds = [];
        foreach ($presented_cart['products'] as $product) {
            if ($product->id_customization) {
                $custIds[] = (int)$product->id_customization;
            }
        }
        $custDataMap = [];
        if ($custIds) {
            $custRows = Db::getInstance()->executeS(
                'SELECT `id_customization`, `price` FROM `' . _DB_PREFIX_ . 'customized_data`
                WHERE `id_customization` IN (' . implode(',', $custIds) . ')'
            );
            foreach ($custRows as $row) {
                $custDataMap[(int)$row['id_customization']] = $row;
            }
        }

        foreach ($presented_cart['products'] as $key => $product) {
            $price = $product->quantity > 0 ? $product->total_wt / $product->quantity : 0;
            if ($product->id_customization) {
                $cust_data = $custDataMap[(int)$product->id_customization] ?? null;
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
                if (is_string($price)) {
                    $price = (float) str_replace(['€', ',', ' '], ['', '.', ''], $price);
                }
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

        $products = $this->context->cart->getProducts();
        $custom_product_message = [];
        $product_ids = array_column($products, 'id_product');
        $product_ids_flipped = array_flip($product_ids);

        // Vérifications d'incompatibilités
        $has_1215 = isset($product_ids_flipped[1215]);
        $has_1214 = isset($product_ids_flipped[1214]);
        $has_1213 = isset($product_ids_flipped[1213]);
        $has_332 = isset($product_ids_flipped[332]);
        $has_1302 = isset($product_ids_flipped[1302]);
        $has_416 = isset($product_ids_flipped[416]);
        $has_1223 = isset($product_ids_flipped[1223]);
        $has_1285 = isset($product_ids_flipped[1285]);
        
        if ($has_1215 && $has_1214) {
            $custom_product_message['1215'] = $this->wrapMessageInBlock('<span class="text-red">Attention : Les platines choisies ne sont pas compatibles avec le portillon Jardi.</span>');
        } elseif ($has_1213 && $has_332) {
            $custom_product_message['1213'] = $this->wrapMessageInBlock('<span class="text-red">Attention : Les platines choisies ne sont pas compatibles avec le portillon Garden.</span>');
        } elseif ($has_1302 && $has_416) {
            foreach ($products as $product) {
                if ($product['id_product'] == 416 && strpos($product['attributes'], 'encoches') !== false) {
                    $custom_product_message['1302'] = $this->wrapMessageInBlock('<span class="text-red">Attention : Les poteaux à clip ne sont pas compatibles avec les soubassements à encoche</span>');
                    break;
                }
            }
        } elseif (($has_1223 || $has_1285) && $has_416) {
            foreach ($products as $product) {
                if ($product['id_product'] == 416 && strpos($product['attributes'], 'clips') !== false) {
                    $message = $this->wrapMessageInBlock('<span class="text-red">Attention : Les poteaux à encoche ne sont pas compatibles avec le soubassements à clips</span>');
                    if ($has_1223) {
                        $custom_product_message['1223'] = $message;
                    }
                    if ($has_1285) {
                        $custom_product_message['1285'] = $message;
                    }
                    break;
                }
            }
        }

        // Traitement des règles de produits manquants simples
        foreach ($this->getMissingProductRules() as $rule) {
            $has_trigger = false;
            $trigger_ids_found = [];
            foreach ($rule['trigger_products'] as $trigger_id) {
                if (isset($product_ids_flipped[$trigger_id])) {
                    $has_trigger = true;
                    $trigger_ids_found[] = $trigger_id;
                }
            }
            
            if ($has_trigger && !isset($product_ids_flipped[$rule['missing_product']])) {
                $message_html = $this->buildMissingProductMessage(
                    $rule['missing_product'],
                    $rule['message']
                );
                $message_html = $this->wrapMessageInBlock($message_html);
                
                foreach ($trigger_ids_found as $trigger_id) {
                    $custom_product_message[$trigger_id] = $message_html;
                }
            }
        }
        
        // Traitement des règles avec multiples produits manquants
        foreach ($this->getMultipleMissingProductRules() as $rule) {
            $trigger_id = $rule['trigger_product'];
            if (!isset($product_ids_flipped[$trigger_id]) || isset($custom_product_message[$trigger_id])) {
                continue;
            }
            
            $messages_html = [];
            foreach ($rule['missing_products'] as $missing_product) {
                if (!isset($product_ids_flipped[$missing_product['id']])) {
                    $messages_html[] = $this->buildMissingProductMessage(
                        $missing_product['id'],
                        $missing_product['message']
                    );
                }
            }
            
            if (!empty($messages_html)) {
                $wrapped_messages = array_map([$this, 'wrapMessageInBlock'], $messages_html);
                $custom_product_message[$trigger_id] = implode('', $wrapped_messages);
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
            'cart_voucher' => $this->render('checkout/_partials/cart-voucher'),
            'cart_sumary_products' => Module::isEnabled('ets_promotion')
                ? $this->context->smarty->fetch(_PS_MODULE_DIR_.'ets_promotion/views/templates/hook/checkout/cart_sumary_products.tpl')
                : '',
            'cart_promos' => $this->context->smarty->fetch(_PS_MODULE_DIR_.'ckcadcampaignconfigurator/views/templates/front/hook/promo_content_cart.tpl'),
            'cart_footer_dealt' => Hook::exec('displayShoppingCartFooter', [], Module::getModuleIdByName('dealt')),
        ]));
    }

    /**
     * Enveloppe un message dans un bloc comme dans le template
     * 
     * @param string $message Message HTML à envelopper
     * @return string Message enveloppé dans un bloc
     */
    private function wrapMessageInBlock($message)
    {
        return '<div class="d-flex" style="justify-content: space-between;align-items: center;padding: 10px;background-color: #f6f2eb;">' 
            . $message 
            . '</div>';
    }

    /**
     * Construit un message HTML enrichi pour informer qu'un produit manquant est requis
     * 
     * @param int $id_product_missing ID du produit manquant à afficher
     * @param string $alert_message Message d'alerte personnalisé
     * @return string Message HTML structuré avec image, texte, déclinaisons, prix et bouton
     */
    private function buildMissingProductMessage($id_product_missing, $alert_message)
    {
        $product_data = $this->getProductData($id_product_missing);
        
        if (empty($product_data)) {
            return '';
        }

        $product_url = $this->context->link->getProductLink(
            $id_product_missing,
            $product_data['link_rewrite']
        );
        
        $id_product_attribute = Product::getDefaultAttribute($id_product_missing);
        if (!$id_product_attribute) {
            $id_product_attribute = 0;
        }
        
        $params = [
            'add' => 1,
            'id_product' => $id_product_missing,
            'id_product_attribute' => $id_product_attribute,
            'qty' => 1,
        ];
        
        if (Configuration::get('PS_TOKEN_ENABLE')) {
            $params['token'] = Tools::getToken(false);
        }
        
        $add_to_cart_url = $this->context->link->getPageLink('cart', true, null, $params, false);
        
        $image_with_link = $product_data['image_html'] 
            ? '<a href="' . htmlspecialchars($product_url, ENT_QUOTES, 'UTF-8') . '" class="product-image media-middle" title="' . strip_tags($product_data['name_html']) . '" style="display: block; cursor: pointer;">' . $product_data['image_html'] . '</a>'
            : '';
        
        $name_with_link = $product_data['name_html'] 
            ? '<a href="' . htmlspecialchars($product_url, ENT_QUOTES, 'UTF-8') . '" class="h4 text-black mb-2 d-block" style="text-decoration: none; cursor: pointer;">' . $product_data['name_html'] . '</a>'
            : '';
        
        $div_image = '<div class="product-message-img" style="border: 2px solid #dbe6e8;">' . $image_with_link . '</div>';
        
        $alert_parts = explode('||', $alert_message, 2);
        $alert_html = htmlspecialchars($alert_parts[0], ENT_QUOTES, 'UTF-8');
        if (isset($alert_parts[1]) && $alert_parts[1] !== '') {
            $alert_html .= '<br/><em>' . htmlspecialchars($alert_parts[1], ENT_QUOTES, 'UTF-8') . '</em>';
        }

        $div_text = '<div class="product-message-txt" style="width: 60%;">
            <span class="text-red" style="font-size: 14px;">Attention : ' . $alert_html . '</span><br/>
            ' . $name_with_link . '
            ' . $product_data['combination_html'] . '
        </div>';
        
        $div_price_button = '<div class="product-message-price text-xs-right d-flex flex_cln" style="gap: 5px;">
            ' . ($product_data['price_html'] ? 'Prix unitaire : ' . $product_data['price_html'] . ' ' : '')
            . '<a href="' . htmlspecialchars($add_to_cart_url, ENT_QUOTES, 'UTF-8') . '" class="btn btn-secondary" style="padding: 10px 20px;">Ajouter au panier</a>
        </div>';

        // Inclure le CSS une seule fois
        $css = '';
        if (!self::$product_message_css_included) {
            $css = '<style>
                .product-message-container {
                    display: contents;
                }
                .product-message-row {
                    display: contents;
                }
                @media (max-width: 767px) {
                    .product-message-container {
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                    }
                    .product-message-row {
                        display: flex;
                        flex-direction: row;
                        gap: 10px;
                        align-items: flex-start;
                    }
                    .product-message-txt {
                        width: auto !important;
                        flex: 1;
                        min-width: 0;
                    }
                    .product-message-price {
                        display: flex !important;
                        justify-content: space-between !important;
                        flex-direction: row;
                        align-items: center;
                        gap: 5px;
                        width: 100%;
                        flex-wrap: wrap;
                        margin-top: 10px;
                    }
                }
            </style>';
            self::$product_message_css_included = true;
        }
        
        $html = $css . '<div class="product-message-container">
            <div class="product-message-row">
                ' . $div_image . '
                ' . $div_text . '
            </div>
            ' . $div_price_button . '
        </div>';

        return $html;
    }


    /**
     * Récupère toutes les données d'un produit nécessaires pour l'affichage
     * 
     * @param int $id_product ID du produit
     * @return array Tableau contenant : name_html, image_html, price_html, combination_html, link_rewrite
     */
    private function getProductData($id_product)
    {
        $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
        
        if (!Validate::isLoadedObject($product)) {
            return [];
        }

        $data = [
            'name_html' => '',
            'image_html' => '',
            'price_html' => '',
            'combination_html' => '',
            'link_rewrite' => '',
        ];

        // Récupération du nom du produit
        $product_name = is_array($product->name) 
            ? $product->name[$this->context->language->id] 
            : $product->name;
        $data['name_html'] = '<strong>' . htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8') . '</strong>';

        // Récupération du link_rewrite pour générer l'URL
        $data['link_rewrite'] = is_array($product->link_rewrite) 
            ? $product->link_rewrite[$this->context->language->id] 
            : $product->link_rewrite;

        // Récupération des déclinaisons
        $data['combination_html'] = $this->getProductCombinationsHtml($id_product);

        // Récupération du prix
        $data['price_html'] = $this->getProductPriceHtml($id_product);

        // Récupération de l'image
        $data['image_html'] = $this->getProductImageHtml($id_product, $data['link_rewrite'], $product_name);

        return $data;
    }

    /**
     * Récupère le HTML des déclinaisons d'un produit
     * 
     * @param int $id_product ID du produit
     * @return string HTML des déclinaisons ou chaîne vide
     */
    private function getProductCombinationsHtml($id_product)
    {
        if (!Combination::isFeatureActive()) {
            return '';
        }

        $attributes_info = Product::getAttributesInformationsByProduct($id_product);
        
        if (empty($attributes_info)) {
            return '';
        }

        // Regrouper les attributs par groupe
        $attributes_by_group = [];
        foreach ($attributes_info as $attr) {
            if (!empty($attr['group']) && !empty($attr['attribute'])) {
                $group_name = $attr['group'];
                if (!isset($attributes_by_group[$group_name])) {
                    $attributes_by_group[$group_name] = [];
                }
                if (!in_array($attr['attribute'], $attributes_by_group[$group_name])) {
                    $attributes_by_group[$group_name][] = $attr['attribute'];
                }
            }
        }

        if (empty($attributes_by_group)) {
            return '';
        }

        // Construire le HTML des déclinaisons
        $combination_text = [];
        foreach ($attributes_by_group as $group => $attributes) {
            $combination_text[] = htmlspecialchars($group, ENT_QUOTES, 'UTF-8') 
                . ' : ' 
                . htmlspecialchars(implode(', ', $attributes), ENT_QUOTES, 'UTF-8');
        }

        return '<div>' . implode(' | ', $combination_text) . '</div>';
    }

    /**
     * Récupère le HTML du prix d'un produit
     * 
     * @param int $id_product ID du produit
     * @return string Prix formaté ou chaîne vide en cas d'erreur
     */
    private function getProductPriceHtml($id_product)
    {
        try {
            $id_customer = ($this->context->cart->id_customer && $this->context->cart->id_customer > 0) 
                ? (int) $this->context->cart->id_customer 
                : null;

            $price_with_tax = Product::getPriceStatic(
                $id_product,
                true,  // avec taxes
                null,  // pas d'attribut spécifique
                6,     // décimales
                null,  // pas de diviseur
                false, // pas seulement la réduction
                true,  // avec réduction
                1,     // quantité 1
                false, // pas de taxe associée forcée
                $id_customer,
                null,  // pas d'id_cart pour éviter les problèmes d'adresse
                null   // pas d'id_address spécifique
            );

            if ($price_with_tax && $price_with_tax > 0) {
                return Tools::displayPrice($price_with_tax, $this->context->currency);
            }
        } catch (Exception $e) {
            // En cas d'erreur, on n'affiche pas le prix
        }

        return '';
    }

    /**
     * Récupère le HTML de l'image d'un produit
     * 
     * @param int $id_product ID du produit
     * @param string $link_rewrite Link rewrite du produit
     * @param string $product_name Nom du produit pour l'attribut alt
     * @return string Balise img HTML ou chaîne vide
     */
    private function getProductImageHtml($id_product, $link_rewrite, $product_name)
    {
        $cover = Product::getCover($id_product);
        
        if (!$cover || !isset($cover['id_image'])) {
            return '';
        }

        $image_url = $this->context->link->getImageLink($link_rewrite, $cover['id_image'], 'home_default');
        
        if (!$image_url) {
            return '';
        }

        return '<img src="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') 
            . '" alt="' . htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8') 
            . '" style="max-width: 100px;" />';
    }
}
