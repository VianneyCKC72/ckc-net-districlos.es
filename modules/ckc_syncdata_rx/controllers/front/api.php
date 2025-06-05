<?php
use Symfony\Component\HttpFoundation\JsonResponse;

class ckc_syncdata_rxapiModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json');

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        if ($token !== Configuration::get('CKC_SYNCDATA_RX_API_TOKEN')) {
            http_response_code(403);
            die(json_encode(['error' => 'Unauthorized']));
        }

        $payload = json_decode(file_get_contents('php://input'), true);

        if (!isset($payload['entity_type'], $payload['entity_id'], $payload['action'], $payload['datas'])) {
            http_response_code(400);
            die(json_encode(['error' => 'Invalid payload']));
        }

        $entity = $payload['entity_type'];
        $action = $payload['action'];
        $datas = $payload['datas'];

        $result = [];

        switch ($entity) {
            case 'customer':
               $id_customer = (int)$payload['entity_id'];
               $customerFields = $datas['customer'];
               $customerFields['id_customer'] = $id_customer;
               $customerFields['id_shop'] = 1;

               $exists = Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'customer WHERE id_customer = '.$id_customer);

               if ($exists) {
                   Db::getInstance()->update('customer', $customerFields, 'id_customer = '.$id_customer);
                   Db::getInstance()->delete('customer_group', 'id_customer = '.$id_customer);
               } else {
                   Db::getInstance()->insert('customer', $customerFields);
               }

               // Groupes du client (si transmis)
               if (!empty($datas['customer_groups']) && is_array($datas['customer_groups'])) {
                   foreach ($datas['customer_groups'] as $id_group) {
                       Db::getInstance()->insert('customer_group', [
                           'id_customer' => $id_customer,
                           'id_group' => (int)$id_group
                       ]);
                   }
               }

               $result = ['status' => $exists ? 'updated' : 'created', 'customer_id' => $id_customer];
               break;

            case 'address':
                $id_address = (int)$payload['entity_id'];

                if ($action === 'delete') {
                    Db::getInstance()->delete('address', 'id_address = '.$id_address);
                    $result = ['status' => 'address_deleted', 'deleted_id' => $id_address];
                    break;
                }

                $exists = Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'address WHERE id_address = '.$id_address);
                $fields = $datas['address'];
                $fields['id_address'] = $id_address;

                if ($exists) {
                    Db::getInstance()->update('address', $fields, 'id_address = '.$id_address);
                    $result = ['status' => 'address_updated', 'updated_id' => $id_address];
                } else {
                    Db::getInstance()->insert('address', $fields);
                    $result = ['status' => 'address_created', 'new_id' => $id_address];
                }
                break;

            case 'order':
                // Récupération ou création du client si nécessaire
                $customer = new Customer((int)$datas['order']['id_customer']);
                if (!Validate::isLoadedObject($customer)) {
                    http_response_code(400);
                    die(json_encode(['error' => 'Invalid customer for order']));
                }

                $id_order = (int)$payload['entity_id'];
                $existing = ((int)Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'orders WHERE id_order = '.(int)$id_order)) > 0;

                if ($existing) {
                    if ($datas['order']['id_shop'] != 1) {
                        switch ($datas['order']['id_shop']) {
                            case 2: // DISTRICLOS LANGUEDOC
                                $datas['order']['id_store'] = 8;
                                break;
                            case 3: // DISTRICLOS MAINE ET LOIRE
                                $datas['order']['id_store'] = 9;
                                break;
                            case 4: // DISTRICLOS VAR
                                $datas['order']['id_store'] = 15;
                                break;
                            case 5: // DISTRICLOS EST
                                $datas['order']['id_store'] = 5;
                                break;
                            case 6: // DISTRICLOS SAVOIE
                                $datas['order']['id_store'] = 13;
                                break;
                            case 7: // DISTRICLOS DROME
                                $datas['order']['id_store'] = 4;
                                break;
                            case 8: // DISTRICLOS BOURGOGNE
                                $datas['order']['id_store'] = 2;
                                break;
                            case 9: // DISTRICLOS NORD
                                $datas['order']['id_store'] = 10;
                                break;
                            case 10: // DISTRICLOS AUVERGNE
                                $datas['order']['id_store'] = 1;
                                break;
                            case 11: // DISTRICLOS EURE
                                $datas['order']['id_store'] = 6;
                                break;
                            case 17: // DISTRICLOS GIRONDE
                                $datas['order']['id_store'] = 7;
                                break;
                            case 21: // DISTRICLOS SEINE ET MARNE
                                $datas['order']['id_store'] = 14;
                                break;
                            case 22: // DISTRICLOS RHONE
                                $datas['order']['id_store'] = 12;
                                break;
                            case 23: // DISTRICLOS OCCITANIE
                                $datas['order']['id_store'] = 11;
                                break;
                            case 24: // DISTRICLOS BRETAGNE
                                $datas['order']['id_store'] = 3;
                                break;
                            case 25: // DISTRICLOS LA GARDE
                                $datas['order']['id_store'] = 16;
                                break;
                        }

                        $datas['order']['id_shop'] = 1;
                    }else if ($datas['order']['id_store']){
                        switch ($datas['order']['id_store']) {
                            case 8:  // DISTRICLOS LANGUEDOC
                                $datas['order']['id_shop'] = 2;
                                break;
                            case 9:  // DISTRICLOS MAINE ET LOIRE
                                $datas['order']['id_shop'] = 3;
                                break;
                            case 15: // DISTRICLOS VAR
                                $datas['order']['id_shop'] = 4;
                                break;
                            case 5:  // DISTRICLOS EST
                                $datas['order']['id_shop'] = 5;
                                break;
                            case 13: // DISTRICLOS SAVOIE
                                $datas['order']['id_shop'] = 6;
                                break;
                            case 4:  // DISTRICLOS DROME
                                $datas['order']['id_shop'] = 7;
                                break;
                            case 2:  // DISTRICLOS BOURGOGNE
                                $datas['order']['id_shop'] = 8;
                                break;
                            case 10: // DISTRICLOS NORD
                                $datas['order']['id_shop'] = 9;
                                break;
                            case 1:  // DISTRICLOS AUVERGNE
                                $datas['order']['id_shop'] = 10;
                                break;
                            case 6:  // DISTRICLOS EURE
                                $datas['order']['id_shop'] = 11;
                                break;
                            case 7:  // DISTRICLOS GIRONDE
                                $datas['order']['id_shop'] = 17;
                                break;
                            case 14: // DISTRICLOS SEINE ET MARNE
                                $datas['order']['id_shop'] = 21;
                                break;
                            case 12: // DISTRICLOS RHONE
                                $datas['order']['id_shop'] = 22;
                                break;
                            case 11: // DISTRICLOS OCCITANIE
                                $datas['order']['id_shop'] = 23;
                                break;
                            case 3:  // DISTRICLOS BRETAGNE
                                $datas['order']['id_shop'] = 24;
                                break;
                            case 16: // DISTRICLOS LA GARDE
                                $datas['order']['id_shop'] = 25;
                                break;
                        }
                    }
                    Db::getInstance()->update('orders', $datas['order'], 'id_order = ' . $id_order);

                    Db::getInstance()->delete('order_detail', 'id_order = ' . $id_order);
                    foreach ($datas['order_details'] as $detail) {
                        $detail['id_order'] = $id_order;
                        Db::getInstance()->insert('order_detail', $detail);
                    }

                    Db::getInstance()->delete('order_history', 'id_order = ' . $id_order);
                    foreach ($datas['order_history'] as $history) {
                        $history['id_order'] = $id_order;
                        Db::getInstance()->insert('order_history', $history);
                    }

                    Db::getInstance()->delete('order_invoice', 'id_order = ' . $id_order);
                    foreach ($datas['order_invoice'] as $invoice) {
                        $invoice['id_order'] = $id_order;
                        Db::getInstance()->insert('order_invoice', $invoice);
                    }

                    Db::getInstance()->delete('order_payment', 'order_reference = "' . pSQL($datas['order']['reference']) . '"');
                    foreach ($datas['order_payment'] as $payment) {
                        Db::getInstance()->insert('order_payment', $payment);
                    }

                    Db::getInstance()->delete('order_carrier', 'id_order = ' . $id_order);
                    foreach ($datas['order_carrier'] as $carrier) {
                        $carrier['id_order'] = $id_order;
                        Db::getInstance()->insert('order_carrier', $carrier);
                    }

                    Db::getInstance()->delete('order_cart_rule', 'id_order = ' . $id_order);
                    foreach ($datas['order_cart_rule'] as $rule) {
                        $rule['id_order'] = $id_order;
                        Db::getInstance()->insert('order_cart_rule', $rule);
                    }

                    $result = ['status' => 'order_updated', 'order_id' => $id_order];
                } else {
                    switch ($datas['order']['id_shop']) {
                        case 2: // DISTRICLOS LANGUEDOC
                            $datas['order']['id_store'] = 8;
                            break;
                        case 3: // DISTRICLOS MAINE ET LOIRE
                            $datas['order']['id_store'] = 9;
                            break;
                        case 4: // DISTRICLOS VAR
                            $datas['order']['id_store'] = 15;
                            break;
                        case 5: // DISTRICLOS EST
                            $datas['order']['id_store'] = 5;
                            break;
                        case 6: // DISTRICLOS SAVOIE
                            $datas['order']['id_store'] = 13;
                            break;
                        case 7: // DISTRICLOS DROME
                            $datas['order']['id_store'] = 4;
                            break;
                        case 8: // DISTRICLOS BOURGOGNE
                            $datas['order']['id_store'] = 2;
                            break;
                        case 9: // DISTRICLOS NORD
                            $datas['order']['id_store'] = 10;
                            break;
                        case 10: // DISTRICLOS AUVERGNE
                            $datas['order']['id_store'] = 1;
                            break;
                        case 11: // DISTRICLOS EURE
                            $datas['order']['id_store'] = 6;
                            break;
                        case 17: // DISTRICLOS GIRONDE
                            $datas['order']['id_store'] = 7;
                            break;
                        case 21: // DISTRICLOS SEINE ET MARNE
                            $datas['order']['id_store'] = 14;
                            break;
                        case 22: // DISTRICLOS RHONE
                            $datas['order']['id_store'] = 12;
                            break;
                        case 23: // DISTRICLOS OCCITANIE
                            $datas['order']['id_store'] = 11;
                            break;
                        case 24: // DISTRICLOS BRETAGNE
                            $datas['order']['id_store'] = 3;
                            break;
                        case 25: // DISTRICLOS LA GARDE
                            $datas['order']['id_store'] = 16;
                            break;
                    }

                    $datas['order']['id_shop'] = 1;

                    Db::getInstance()->insert('orders', $datas['order']);

                    foreach ($datas['order_details'] as $detail) {
                        Db::getInstance()->insert('order_detail', $detail);
                    }
                    foreach ($datas['order_history'] as $history) {
                        Db::getInstance()->insert('order_history', $history);
                    }
                    foreach ($datas['order_invoice'] as $invoice) {
                        Db::getInstance()->insert('order_invoice', $invoice);
                    }
                    foreach ($datas['order_payment'] as $payment) {
                        Db::getInstance()->insert('order_payment', $payment);
                    }

                    Db::getInstance()->delete('order_carrier', 'id_order = ' . $id_order);
                    foreach ($datas['order_carrier'] as $carrier) {
                        Db::getInstance()->insert('order_carrier', $carrier);
                    }
                    foreach ($datas['order_cart_rule'] as $rule) {
                        Db::getInstance()->insert('order_cart_rule', $rule);
                    }

                    $result = ['status' => 'order_created', 'order_id' => $datas['order']['id_order']];
                }
                break;
            case 'order_slip':
                $id_order = (int)$datas['order']['id_order'];
                $existing = new Order($id_order);

                if (Validate::isLoadedObject($existing)) {
                    if ($datas['order']['id_shop'] != 1) {
                        switch ($datas['order']['id_shop']) {
                            case 2: // DISTRICLOS LANGUEDOC
                                $datas['order']['id_store'] = 8;
                                break;
                            case 3: // DISTRICLOS MAINE ET LOIRE
                                $datas['order']['id_store'] = 9;
                                break;
                            case 4: // DISTRICLOS VAR
                                $datas['order']['id_store'] = 15;
                                break;
                            case 5: // DISTRICLOS EST
                                $datas['order']['id_store'] = 5;
                                break;
                            case 6: // DISTRICLOS SAVOIE
                                $datas['order']['id_store'] = 13;
                                break;
                            case 7: // DISTRICLOS DROME
                                $datas['order']['id_store'] = 4;
                                break;
                            case 8: // DISTRICLOS BOURGOGNE
                                $datas['order']['id_store'] = 2;
                                break;
                            case 9: // DISTRICLOS NORD
                                $datas['order']['id_store'] = 10;
                                break;
                            case 10: // DISTRICLOS AUVERGNE
                                $datas['order']['id_store'] = 1;
                                break;
                            case 11: // DISTRICLOS EURE
                                $datas['order']['id_store'] = 6;
                                break;
                            case 17: // DISTRICLOS GIRONDE
                                $datas['order']['id_store'] = 7;
                                break;
                            case 21: // DISTRICLOS SEINE ET MARNE
                                $datas['order']['id_store'] = 14;
                                break;
                            case 22: // DISTRICLOS RHONE
                                $datas['order']['id_store'] = 12;
                                break;
                            case 23: // DISTRICLOS OCCITANIE
                                $datas['order']['id_store'] = 11;
                                break;
                            case 24: // DISTRICLOS BRETAGNE
                                $datas['order']['id_store'] = 3;
                                break;
                            case 25: // DISTRICLOS LA GARDE
                                $datas['order']['id_store'] = 16;
                                break;
                        }

                        $datas['order']['id_shop'] = 1;
                    }else{
                        switch ($datas['order']['id_store']) {
                            case 8:  // DISTRICLOS LANGUEDOC
                                $datas['order']['id_shop'] = 2;
                                break;
                            case 9:  // DISTRICLOS MAINE ET LOIRE
                                $datas['order']['id_shop'] = 3;
                                break;
                            case 15: // DISTRICLOS VAR
                                $datas['order']['id_shop'] = 4;
                                break;
                            case 5:  // DISTRICLOS EST
                                $datas['order']['id_shop'] = 5;
                                break;
                            case 13: // DISTRICLOS SAVOIE
                                $datas['order']['id_shop'] = 6;
                                break;
                            case 4:  // DISTRICLOS DROME
                                $datas['order']['id_shop'] = 7;
                                break;
                            case 2:  // DISTRICLOS BOURGOGNE
                                $datas['order']['id_shop'] = 8;
                                break;
                            case 10: // DISTRICLOS NORD
                                $datas['order']['id_shop'] = 9;
                                break;
                            case 1:  // DISTRICLOS AUVERGNE
                                $datas['order']['id_shop'] = 10;
                                break;
                            case 6:  // DISTRICLOS EURE
                                $datas['order']['id_shop'] = 11;
                                break;
                            case 7:  // DISTRICLOS GIRONDE
                                $datas['order']['id_shop'] = 17;
                                break;
                            case 14: // DISTRICLOS SEINE ET MARNE
                                $datas['order']['id_shop'] = 21;
                                break;
                            case 12: // DISTRICLOS RHONE
                                $datas['order']['id_shop'] = 22;
                                break;
                            case 11: // DISTRICLOS OCCITANIE
                                $datas['order']['id_shop'] = 23;
                                break;
                            case 3:  // DISTRICLOS BRETAGNE
                                $datas['order']['id_shop'] = 24;
                                break;
                            case 16: // DISTRICLOS LA GARDE
                                $datas['order']['id_shop'] = 25;
                                break;
                        }
                    }
                    Db::getInstance()->update('orders', $datas['order'], 'id_order = ' . $id_order);

                    foreach ($datas['order_details'] as $detail) {
                        Db::getInstance()->update('order_detail', $detail, 'id_order_detail = ' . $detail['id_order_detail']);
                    }
                }

                Db::getInstance()->insert('order_slip', $datas['order_slip']);

                foreach ($datas['order_slip_detail'] as $detail) {
                    Db::getInstance()->insert('order_slip_detail', $detail);
                }

                $result = ['status' => 'order_slip_created', 'order_id' => $datas['order']['id_order']];
                break;
            case 'order_payment':
                foreach ($datas['order_payment'] as $detail) {
                    Db::getInstance()->insert('order_payment', $detail);
                }

                $result = ['status' => 'order_payment_created', 'id_order_payment' => $detail['id_order_payment']];
                break;
            case 'order_carrier':
                Db::getInstance()->delete('order_carrier', 'id_order = ' . $datas['order_carrier'][0]['id_order']);

                foreach ($datas['order_carrier'] as $detail) {
                    Db::getInstance()->insert('order_carrier', $detail);
                }

                $result = ['status' => 'order_carrier_created', 'id_order_carrier' => $detail['id_order_carrier']];
                break;
            case 'order_state':
                $id_order = (int)$payload['entity_id'];
                $new_state = (int)$datas['new_order_state'];

                if (!$id_order || !$new_state) {
                    http_response_code(400);
                    die(json_encode(['error' => 'Missing order ID or state']));
                }

                $orderExists = Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'orders WHERE id_order = '.$id_order);

                if (!$orderExists) {
                    http_response_code(404);
                    die(json_encode(['error' => 'Order not found']));
                }

                // On insère dans order_history
                Db::getInstance()->insert('order_history', [
                    'id_order' => $id_order,
                    'id_order_state' => $new_state,
                    'id_employee' => 0,
                    'date_add' => date('Y-m-d H:i:s'),
                ]);

                // On met à jour le champ current_state dans orders
                Db::getInstance()->update('orders', [
                    'current_state' => $new_state
                ], 'id_order = '.$id_order);

                $result = [
                    'status' => 'order_state_updated',
                    'order_id' => $id_order,
                    'new_state' => $new_state
                ];
                break;

            case 'cart':
                $id_cart = (int)$payload['entity_id'];
                $cartExists = Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'cart WHERE id_cart = '.$id_cart);

                $cartFields = $datas;
                unset($cartFields['products'], $cartFields['cart_cart_rule']);

                if ($cartExists) {
                    Db::getInstance()->update('cart', $cartFields, 'id_cart = '.$id_cart);
                    Db::getInstance()->delete('cart_product', 'id_cart = '.$id_cart);
                    Db::getInstance()->delete('cart_cart_rule', 'id_cart = '.$id_cart);
                } else {
                    $cartFields['id_cart'] = $id_cart;
                    Db::getInstance()->insert('cart', $cartFields);
                }

                if (!empty($datas['products']) && is_array($datas['products'])) {
                    foreach ($datas['products'] as $product) {
                        if (!isset($product['id_product']) || !isset($product['quantity'])) {
                            continue;
                        }

                        $row = [
                            'id_cart' => $id_cart,
                            'id_product' => (int)$product['id_product'],
                            'id_product_attribute' => (int)($product['id_product_attribute'] ?? 0),
                            'quantity' => (int)$product['quantity'],
                            'date_add' => date('Y-m-d H:i:s'),
                        ];

                        Db::getInstance()->insert('cart_product', $row);
                    }
                }

                if (!empty($datas['cart_cart_rule']) && is_array($datas['cart_cart_rule'])) {
                    Db::getInstance()->delete('cart_cart_rule', 'id_cart = ' . $id_cart);
                    foreach ($datas['cart_cart_rule'] as $rule) {
                        $rule['id_cart'] = $id_cart;
                        Db::getInstance()->insert('cart_cart_rule', $rule);
                    }
                }

                $result = [
                    'status' => $cartExists ? 'cart_updated' : 'cart_created',
                    'cart_id' => $id_cart
                ];
                break;
            default:
                http_response_code(400);
                die(json_encode(['error' => 'Unsupported entity type']));
        }

        die(json_encode($result));
    }
}
