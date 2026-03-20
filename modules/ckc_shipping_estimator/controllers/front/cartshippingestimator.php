<?php
class ckc_shipping_estimatorcartshippingestimatorModuleFrontController extends ModuleFrontController
{
    private const TEMP_ADDRESS_ALIAS_PREFIX = 'temp_shipping_estimator';

    CONST CARRIERS = ['1084',
        '1085',
        '1086',
        '1087',
        '1088',
        '1089',
        '1090',
        '1091',
        '1093',
        '1094',
        '1100',
        '1101',
        '1102',
        '1113',
        '1114',
        '1115',
        '1116',
        '1117',
        '1118',
        '1119',
        '1120',
        '1121',
        '1122',
        '1123',
        '1128',
        '1129',
        '1130',
        '1131',
        '1132',
        '1153',
        '1154',
        '1155',
        '1156',
        '1157',
        '1158',
        '1159',
        '1160',
        '1161',
        '1162',
        '1163',
        '1164',
        '1165',
        '1172',
        '1173',
        '1176',
        '1177',
        '1178',
        '1189',
        '1200',
        '1201',
        '1209',
        '1210',
        '1211',
        '1212',
        '1215',
        '1218',
        '1233',
        '1236',
        '1239',
        '1241',
        '1249',
        '1254',
        '1255',
        '1261',
        '1262',
        '1263',
        '1264',
        '1265',
        '1266',
        '1267',
        '1268',
        '1269',
        '1270',
        '1271',
        '1272',
        '1273',
        '1274',
        '1275',
        '1276',
        '1277',
        '1278',
        '1279',
        '1280',
        '1281',
        '1282',
        '1302',
        '1312',
        '1323',
        '1324',
        '1325',
        '1358',
        '1359',
        '1360',
        '1361',
        '1362',
        '1363',
        '1364',
        '1365',
        '1366',
        '1367',
        '1368',
        '1369',
        '1370',
        '1371',
        '1372',
        '1373',
        '1390',
        '1391',
        '1392',
        '1393',
        '1394',
        '1395',
        '1396',
        '1397',
        '1398',
        '1399',
        '1400',
        '1401',
        '1402',
        '1403',
        '1404',
        '1405',
        '1406'];

    public function initContent()
    {
        parent::initContent();

        $postalCode = (string) Tools::getValue('postal_code');
        header('Content-Type: application/json');
        $postalCode = trim($postalCode);
        if ($postalCode === '') {
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

        exit;
    }

    private function getDeliveryOptionsByPostalCode($postalCode)
    {
        $originalAddressId = $this->context->cart->id_address_delivery;
        $tempAddress = $this->getOrCreateTempAddress((string) $postalCode);
        if (!Validate::isLoadedObject($tempAddress)) {
            return [];
        }

        $this->context->cart->updateAddressId($this->context->cart->id_address_delivery, $tempAddress->id);
        $this->context->cart->id_address_delivery = $tempAddress->id;
        $this->context->cart->save();

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
        $postalCodeNumeric = (int) ltrim((string) $postalCode, '0');
        $sql = 'SELECT *
					FROM `' . _DB_PREFIX_ . 'zip_code_zone` z
					WHERE "' . (int) $postalCodeNumeric . '" BETWEEN z.`min` AND z.`max`';
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        $id_zone = isset($result['id_zone']) ? (int) $result['id_zone'] : 0;

        if ($id_zone > 0 && isset($delivery_option_list[$this->context->cart->id_address_delivery])) {
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

                            if ($result) {
                                $price = $result['price'];
                            }else{
                                $price = 0;
                            }

                            if ($this->isFreeShipping($this->context->cart, $carriers_list) && (!$price || (float) $price == 0)) {
                                $carrier['price'] = $this->translator->trans('Free', [], 'Shop.Theme.Checkout');
                            } else {
                                if ($include_taxes) {
                                    $carrier['reduc'] = $priceFormatter->format(ceil(($carrier['price_with_tax'])) * 2);
                                    $carrier['price'] = $priceFormatter->format($carrier['price_with_tax']);
                                    if ($display_taxes_label) {
                                        $carrier['price'] = $this->translator->trans('%price% tax incl.', ['%price%' => $carrier['price']], 'Shop.Theme.Checkout');
                                    }
                                } else {
                                    $carrier['reduc'] = $priceFormatter->format(ceil(($carrier['price_without_tax'])) * 2);
                                    $carrier['price'] = $priceFormatter->format($carrier['price_without_tax']);
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
        $tempAddress->deleted = 1;
        $tempAddress->save();
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

    private function getOrCreateTempAddress(string $postalCode): Address
    {
        $context = Context::getContext();
        $idCustomer = (int) ($context->cookie->id_customer ?? 0);
        $idGuest = (int) ($context->cookie->id_guest ?? 0);
        $alias = self::TEMP_ADDRESS_ALIAS_PREFIX;

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
        $contains_soub = false;

        foreach ($cart->getProducts() as $product) {
            if (stripos($product['name'], 'Soub') !== false) {
                $contains_soub = true;
                break;
            }
        }

        if (!in_array(array_keys($carrier['carrier_list'])[0], static::CARRIERS) && $contains_soub) {
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
}
