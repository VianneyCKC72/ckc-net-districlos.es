<?php
class ckc_shipping_estimatorproductshippingestimatorModuleFrontController extends ModuleFrontController
{
    private const TEMP_ADDRESS_ALIAS_PREFIX = 'temp_shipping_estimator';

    public function initContent()
    {
        parent::initContent();

        $postalCode           = (string) Tools::getValue('postal_code');
        $id_product           = (int)Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        $quantity             = (int)Tools::getValue('quantity', 1);
        header('Content-Type: application/json');
        $postalCode = trim($postalCode);
        if ($postalCode === '') {
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

        exit;
    }

    private function getDeliveryOptionsByPostalCode($postalCode, $idProduct, $idProductAttribute, $quantity)
    {
        $savedCart = $this->saveCartContent();
        $this->clearCart();
        if (!$this->context->cart->id) {
            $this->context->cart->add();
        }
        $this->context->cart->updateQty($quantity, $idProduct, $idProductAttribute, false, 'up');

        $originalAddressId = $this->context->cart->id_address_delivery;

        $tempAddress = $this->getOrCreateTempAddress((string) $postalCode);
        if (!Validate::isLoadedObject($tempAddress)) {
            $this->clearCart();
            $this->restoreCartContent($savedCart);

            return [];
        }

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
        $postalCodeNumeric = (int) ltrim((string) $postalCode, '0');
        $sql     = 'SELECT *
					FROM `' . _DB_PREFIX_ . 'zip_code_zone` z
					WHERE "' . (int) $postalCodeNumeric . '" BETWEEN z.`min` AND z.`max`';
        $result  = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        if (isset($delivery_option_list[$this->context->cart->id_address_delivery]) && $result && isset($result['id_zone'])) {
            $id_zone = $result['id_zone'];

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
                            $price = $result ? $result['price'] : 0;
                            if ($this->isFreeShipping($this->context->cart, $carriers_list) && (!$price || (float)$price == 0)) {
                                $carrier['price'] = $this->translator->trans('Free', [], 'Shop.Theme.Checkout');
                            } else {
                                if ($include_taxes) {
                                    $carrier['reduc'] = $priceFormatter->format(ceil(($carriers_list['total_price_with_tax'] + ($price * (1 + 20 / 100))) * 2));
                                    $carrier['price'] = $priceFormatter->format($carriers_list['total_price_with_tax'] + ($price * (1 + 20 / 100)));
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
        $this->clearCart();
        $this->restoreCartContent($savedCart);
        $this->context->cart->updateAddressId($tempAddress->id, $originalAddressId);

        return $carriers_available;
    }

    private function getOrCreateTempAddress(string $postalCode): Address
    {
        $context = Context::getContext();
        $idCustomer = (int) ($context->cookie->id_customer ?? 0);
        $idGuest = (int) ($context->cookie->id_guest ?? 0);
        $alias = self::TEMP_ADDRESS_ALIAS_PREFIX;

        // For guests, scope the alias to the guest id when available.
        if ($idCustomer <= 0 && $idGuest > 0) {
            $alias .= '_' . $idGuest;
        }

        $existingId = (int) Db::getInstance()->getValue(
            'SELECT a.`id_address`
            FROM `' . _DB_PREFIX_ . 'address` a
            WHERE a.`id_customer` = ' . (int) $idCustomer . '
            AND a.`alias` = "' . pSQL($alias) . '"
            ORDER BY a.`id_address` DESC'
        );

        $address = $existingId > 0 ? new Address($existingId) : new Address();

        $address->alias = $alias;
        $address->firstname = 'temp';
        $address->lastname = 'temp';
        $address->address1 = 'temp';
        $address->postcode = $postalCode;
        $address->city = 'temp';
        $address->id_customer = $idCustomer;
        $address->id_country = 8;
        $address->deleted = 1;

        $saved = $existingId > 0 ? $address->update() : $address->add();
        if (!$saved || !Validate::isLoadedObject($address)) {
            return new Address();
        }

        return $address;
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

    private function saveCartContent(): array
    {
        $savedCart = [];
        $products = $this->context->cart->getProducts();
        foreach ($products as $product) {
            $savedCart[] = [
                'id_product' => $product['id_product'],
                'id_product_attribute' => $product['id_product_attribute'],
                'quantity' => $product['cart_quantity'],
            ];
        }

        return $savedCart;
    }

    private function clearCart(): void
    {
        $products = $this->context->cart->getProducts();
        foreach ($products as $product) {
            $this->context->cart->deleteProduct($product['id_product'], $product['id_product_attribute']);
        }
    }

    private function restoreCartContent(array $savedCart): void
    {
        foreach ($savedCart as $product) {
            $this->context->cart->updateQty($product['quantity'], $product['id_product'], $product['id_product_attribute']);
        }
        $this->context->cart->update();
    }
}
