<?php

use Dealt\DealtHookHelper;

if (file_exists(_PS_MODULE_DIR_.'pm_advancedpack/AdvancedPack.php')) {
    include_once _PS_MODULE_DIR_.'pm_advancedpack/AdvancedPack.php';
}
class DealtEligibilityModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json');

        if(Tools::getValue("changeLengthDealt")){

            $id_product = Tools::getValue("id_product");
            $dealt_product_length = Tools::getValue("totalLength");
            $data = \Dealt\DealtHookHelper::buildProductAdditionalInfoData($this->context, $id_product,$dealt_product_length);
            $this->context->smarty->assign(["data" => $data]);
            echo json_encode($data);

        } else if(Tools::getValue("addToCart")){

            try {
                $productId = Tools::getValue("productId");
                $serviceId = Tools::getValue("serviceId");
                $serviceOnly = Tools::getValue("serviceOnly"); // Flag pour ajouter seulement le service
                $qty = Tools::getValue("qty");
                $packQtyML = Tools::getValue("packQtyML");
                $packQty  = Tools::getValue("packQty");
                $id_product_attribute = !empty(Tools::getValue("id_product_attribute")) ? Tools::getValue("id_product_attribute") : 0;

                $cart = Context::getContext()->cart;

                if(empty($cart->id)){
                    if(Context::getContext()->cookie->id_guest) {
                        $guest = new \Guest(Context::getContext()->cookie->id_guest);
                        Context::getContext()->cart->mobile_theme = $guest->mobile_theme;
                    }
                    Context::getContext()->cart->add();
                    if(Context::getContext()->cart->id) {
                        Context::getContext()->cookie->id_cart = (int)Context::getContext()->cart->id;
                    }
                }

                // Si serviceOnly est activé, ajouter seulement le service sans le produit principal
                if($serviceOnly && $serviceId){
                    $cart->updateQty(1, $serviceId);
                    $cart->update();
                    echo json_encode("success");
                    return;
                }

                if(Tools::getValue("isCart") == "false"){
                    $checkIfPack = $this->checkIfPack($productId);
                    if($checkIfPack && file_exists(_PS_MODULE_DIR_.'pm_advancedpack/AdvancedPack.php')){
                        $packHasEditedQuantity = false;
                        $productPackExclude = [];
                        $productPackCustomizationList = [];
                        $idProductAttributeList = [];
                        $productPackQuantityList = [];

                        $idProductAttributeListJSON = Tools::getValue('id_product_attribute_list');
                        $idProductAttributeListJSON = (array)json_decode($idProductAttributeListJSON);
                        if (is_array($idProductAttributeListJSON)) {
                            foreach ($idProductAttributeListJSON as $idProductPack => $idProductAttribute) {
                                if (empty($idProductPack) || empty($idProductAttribute) || !is_numeric($idProductPack) || !is_numeric($idProductAttribute)) {
                                    continue;
                                } else {
                                    $idProductAttributeList[(int)$idProductPack] = (int)$idProductAttribute;
                                }
                            }
                        }

                        if (Tools::getIsset('productPackExclude')) {
                            $productPackExclude = array_unique(array_map('intval', (array)Tools::getValue('productPackExclude')));
                        }

                        if (Tools::getIsset('productPackQuantityList')) {
                            $productPackQuantityList = (array)Tools::getValue('productPackQuantityList');
                            $tmp_productPackQuantityList = (array)Tools::getValue('productPackQuantityList');
                            if (is_array($tmp_productPackQuantityList) && count($tmp_productPackQuantityList)) {
                                $packProducts = AdvancedPack::getPackContent($productId, null, false, [], $productPackQuantityList);
                                foreach ($tmp_productPackQuantityList as $packChoiceRow) {
                                    if (!is_numeric($packChoiceRow['quantity']) || (int)$packChoiceRow['quantity'] <= 0) {
                                        $packChoiceRow['quantity'] = 1;
                                    }
                                    $productPackQuantityList[(int)$packChoiceRow['idProductPack']] = (int)$packChoiceRow['quantity'];
                                }

                                foreach ($packProducts as $packProduct) {
                                    if ($packQtyML && $packQtyML > 0) {
                                        $formula = $packProduct['formula'];
                                        if ($formula) {
                                            $quantity = (int)$packProduct['quantity'];
                                            $ml = $packQtyML;
                                            $formula = str_replace('[ML]', $ml, $formula);
                                            $formula = str_replace('[QUANTITY]', $quantity, $formula);
                                            $newQuantity = AdvancedPack::evaluateMathExpression($formula);
                                            $productPackQuantityList[(int)$packProduct['id_product_pack']] = (int)$newQuantity;
                                        }
                                    }
                                    if (isset($productPackQuantityList[(int)$packProduct['id_product_pack']]) && $productPackQuantityList[(int)$packProduct['id_product_pack']] != (int)$packProduct['quantity']) {
                                        $packHasEditedQuantity = true;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($packQtyML && $packQtyML > 0) {
                            $packProducts = AdvancedPack::getPackContent($productId, null, false, [], $productPackQuantityList);
                            foreach ($packProducts as $packProduct) {
                                $formula = $packProduct['formula'];
                                if ($formula) {
                                    $quantity = (int)$packProduct['quantity'];
                                    $ml = $packQtyML;
                                    $formula = str_replace('[ML]', $ml, $formula);
                                    $formula = str_replace('[QUANTITY]', $quantity, $formula);
                                    $newQuantity = AdvancedPack::evaluateMathExpression($formula);
                                    $productPackQuantityList[(int)$packProduct['id_product_pack']] = (int)$newQuantity;
                                    $packHasEditedQuantity = true;
                                }else{
                                    $productPackQuantityList[(int)$packProduct['id_product_pack']] = (int)$packProduct['quantity'];
                                }
                            }
                        }

                        if (Tools::getIsset('productPackCustomizationList')) {
                            $tmp_productPackCustomizationList = (array)Tools::getValue('productPackCustomizationList');
                            if (is_array($tmp_productPackCustomizationList) && count($tmp_productPackCustomizationList)) {
                                $packProducts = AdvancedPack::getPackContent($productId);
                                foreach ($tmp_productPackCustomizationList as $packChoiceRow) {
                                    if (!in_array((int)$packChoiceRow['idProductPack'], $this->productPackExclude)) {
                                        $productPackCustomizationList[(int)$packChoiceRow['idProductPack']][(int)$packChoiceRow['idCustomizationField']] = $packChoiceRow['value'];
                                    }
                                }
                            }
                        }
                        if (AdvancedPack::isInStock($productId, $packQty, $idProductAttributeList, true, $id_product_attribute)) {
                            $cart->updateQty(1, $serviceId);
                            AdvancedPack::addExplodedPackToCart($productId, $packQty, $idProductAttributeList, $productPackCustomizationList, $productPackQuantityList, $productPackExclude);
                        }
                    } else {
                        $cart->updateQty($qty, $productId, $id_product_attribute);
                        $cart->updateQty(1, $serviceId);
                    }
                } else {
                    $cart->updateQty(1, $serviceId);
                }

                $cart->update();
            } catch(\Exception $e) {
                echo $e->getMessage();
                return;
            }

            echo json_encode("success");

        } else {

            $zipcode = Tools::getValue("zipcode");
            $offerId = Tools::getValue("offer_id");
            $api = new \Dealt\Service\ApiService();
            $result = $api->checkAvailability($zipcode, $offerId); // à remplacer par un $_GET['zip']

            die(json_encode($result));

        }
    }
    public function checkIfPack($id_product){
        $ifTable = Db::getInstance()->execute('SHOW TABLES LIKE "'._DB_PREFIX_.'pm_advancedpack"');
        if(!$ifTable){
            return false;
        } else {
            $check = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'pm_advancedpack` WHERE `id_pack` = '.(int)$id_product.' AND id_shop = '.(int)Context::getContext()->shop->id);
            return !empty($check[0]);
        }
    }
}
