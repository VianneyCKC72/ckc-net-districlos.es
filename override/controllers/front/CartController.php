<?php
use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;
class CartController extends CartControllerCore
{
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
}
