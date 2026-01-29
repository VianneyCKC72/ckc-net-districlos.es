<?php

use Dealt\DealtHookHelper;

class DealtUpdateServiceCartModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $main_product = Tools::getValue('main_product_id');
        $service = Tools::getValue('service_product_id');
        $qty = Tools::getValue('qty');
        $main_product_id_attribute = Tools::getValue('main_product_id_attribute');
        if(!empty($main_product) && !empty($service) && !empty($qty) && !empty($main_product_id_attribute)){
            $newService = $this->getCorrespondingService($main_product, $service, $qty, $main_product_id_attribute);
            echo json_encode($newService);
            exit();
        }
    }

    public function getCorrespondingService($main_product, $service, $qty, $main_product_id_attribute){
        $lengthPerUnit = null;
        $checkIfPack = false;
        $idPack = null;
        $idPackMainProduct = null;
        $combinations = [];
        $qty = (int)$qty;
        $main   = new \Product($main_product, false, Context::getContext()->language->id);

        $cart = Context::getContext()->cart;

        if(isset(Context::getContext()->cookie->cart_packs)){
            $cookiePack = json_decode(Context::getContext()->cookie->cart_packs);
            if(!empty($cookiePack->id_cart) && !empty($cookiePack->id_pack)){
                if((int)$cookiePack->id_cart == (int)$cart->id){
                    $checkIfPack = \Dealt\DealtHookHelper::checkIfPack($cookiePack->id_pack, $cart);
                    if($checkIfPack){
                        $idPack = $cookiePack->id_pack;
                        $idPackMainProduct = Db::getInstance()->executeS('SELECT pap.id_product, pap.default_id_product_attribute FROM `'._DB_PREFIX_.'pm_advancedpack_products` pap WHERE pap.id_pack = '.(int)$idPack.' AND pap.position = 0');
                    }
                }
            }
        }


        if($checkIfPack && !empty($idPackMainProduct[0]['id_product']) && $main_product == $idPackMainProduct[0]['id_product']){
            if(!empty($idPackMainProduct[0]['default_id_product_attribute'])){
                $combinations = \Dealt\DealtHookHelper::getAttributesInformationsByProduct($idPackMainProduct[0]['id_product'], $idPackMainProduct[0]['default_id_product_attribute']);
            }
        } else {
            if(!empty($main_product_id_attribute)){
                $combinations = \Dealt\DealtHookHelper::getAttributesInformationsByProduct($main_product, (int)$main_product_id_attribute);
            }
        }

        if(count($combinations)){
            foreach($combinations as $combination){
                if($combination['group'] == 'Longueur du Kit' OR $combination['group'] == 'Longueur' OR $combination['group'] == 'longueur'){
                    if(preg_match('/cm/', $combination['attribute'])){
                        $lengthPerUnit = floatval(str_replace([' cm', 'cm'], ['', '.'], $combination['attribute'])) / 100;
                    } else {
                        $lengthPerUnit = floatval(str_replace([' ml', ' m', 'ml', 'm'], ['', '', '.', '.'], $combination['attribute']));
                    }

                    break;
                }
            }
        }

        if(empty($lengthPerUnit)){
            if($main->width != 0){
                $lengthPerUnit = $main->width / 100;
                $length = $lengthPerUnit * $qty;
            } else {
                $features = \Product::getFrontFeaturesStatic(Context::getContext()->language->id, (int)$main_product);
                foreach ($features as $feature){
                    if($feature['name'] == 'Longueur'){
                        if(preg_match('/mm/', $feature['value']) OR preg_match('/MM/', $feature['value'])){
                            $lengthInt = (int)$feature['value'];
                            $lengthPerUnit = $lengthInt / 1000;
                        } else if(preg_match('/ml/', $feature['value'])){
                            $dealt_product_length = floatval(preg_replace('/[ml]/', '.', $feature['value']).'0');
                        } else if(preg_match('/ML/', $feature['value'])){
                            $dealt_product_length = floatval(preg_replace('/[ML]/', '.', $feature['value']).'0');
                        } else if(preg_match('/m/', $feature['value']) OR preg_match('/M/', $feature['value'])){
                            $lengthPerUnit = floatval(preg_replace('/[mM]/', '.', $feature['value']).'0');
                        } else if(preg_match('/cm/', $feature['value']) OR preg_match('/CM/', $feature['value'])){
                            $lengthInt = (int)$feature['value'];
                            $lengthPerUnit = $lengthInt / 100;
                        } else {
                            $lengthPerUnit = (int)$feature['value'];
                        }
                        break;
                    }
                }
                if(!empty($lengthPerUnit)){
                    $length = $lengthPerUnit * $qty;
                } else {
                    $length = 0;
                    return ['status' => 'fail : length is empty', 'newserviceid' => 0];
                }
            }
        } else {
            $length = $lengthPerUnit * $qty;
        }


        $serviceCategory = Db::getInstance()->executeS('SELECT cp.id_category FROM ' . _DB_PREFIX_ . 'category_product cp
                WHERE cp.id_product = '.(int)$service.' AND cp.id_category != 369');

        if(!empty($length) && !empty($serviceCategory[0]['id_category'])){
            $serviceIdSql = Db::getInstance()->executeS('SELECT p.id_product FROM ' . _DB_PREFIX_ . 'product p
                LEFT JOIN ' . _DB_PREFIX_ . 'dealt_service ds ON ds.reference = p.reference 
                LEFT JOIN ' . _DB_PREFIX_ . 'category_product cp ON cp.id_product = p.id_product
		        LEFT JOIN ' . _DB_PREFIX_ . 'category_product cp2 ON cp2.id_category = cp.id_category
                LEFT JOIN ' . _DB_PREFIX_ . 'product p2 ON p2.id_product = cp2.id_product
                WHERE '.$length.' >= ds.longueur_min AND '.$length.' <= ds.longueur_max AND cp.id_category = '.$serviceCategory[0]['id_category'].'
                AND p2.id_product = '.$service);
        } else {
            return ['status' => 'fail : length or id_category are empty', 'newserviceid' => 0];
        }

        if(!empty($serviceIdSql[0]['id_product'])){
            $cart->updateQty(0, $service);
            $cart->updateQty(1, $serviceIdSql[0]['id_product']);
            $cart->update();
            return ['status' => 'success', 'newserviceid' => $serviceIdSql[0]['id_product']];
        } else {
            return ['status' => 'fail : new service not found', 'newserviceid' => 0];
        }
    }

}


