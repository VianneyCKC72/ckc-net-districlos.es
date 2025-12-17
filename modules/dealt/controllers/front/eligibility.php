<?php

use Dealt\DealtHookHelper;
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
            $qty = Tools::getValue("qty");
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
            if(Tools::getValue("isCart") == "false"){
                $cart->updateQty($qty, $productId, $id_product_attribute);
            }
            $cart->updateQty(1, $serviceId);
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
}
