<?php
/**
 * Override du module hsmultiaccessoriespro
 * 
 * Ajout de la vérification du statut "victime de son succès" pour les accessoires
 * 
 * @author    CKC-Net
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ckc_stores_stock/classes/StockManager.php';

class HsMultiAccessoriesPro extends HsMultiAccessoriesProCore
{
    /**
     * Override de la méthode renderBlockAccessories pour ajouter la vérification
     * du statut "victime de son succès" pour chaque accessoire
     * 
     * @param array $id_products
     * @param bool $is_product_page
     * @return string HTML
     */
    protected function renderBlockAccessories($id_products, $is_product_page = true)
    {
        if (!$this->isProductPage()) {
            return;
        }
        
        // Appeler la méthode parente pour obtenir les données de base
        // On va intercepter les données avant qu'elles ne soient assignées au template
        $id_groups = HsAccessoriesGroupAbstract::getIdGroups($this->context->language->id, true);
        $include_out_of_stock = Configuration::get('HSMA_SHOW_ACCESSORIES_OFS');
        $accessories_groups = HsAccessoriesGroupAbstract::getAccessoriesByGroups($id_groups, $id_products, true, $this->context->language->id, $include_out_of_stock, $is_product_page);
        
        // Initialiser StockManager pour vérifier le statut "victime de son succès"
        $stockManager = new StockManager();
        
        $accessories_table_price = array();
        $currency_decimals = $this->getDecimals();
        $use_tax = $this->isUsetax();
        $product = $this->context->controller->getProduct();
        $random_main_product_id = $this->getRandomId(self::$main_product_prefix, $product->id);
        
        if ($is_product_page) {
            $accessories_table_price[$random_main_product_id] = $this->formatProduct($product);
        }
        
        $id_products_buy_together = array();
        
        // Parcourir tous les groupes d'accessoires et ajouter la vérification "victime de son succès"
        foreach ($accessories_groups as &$accessories_group) {
            foreach ($accessories_group as &$accessory) {
                $product = new Product((int) $accessory['id_accessory'], false, (int) $this->context->language->id);
                $random_product_accessories_id = $this->getRandomId(self::$accessory_prefix, $accessory['id_accessory_group'] . '_' . $accessory['id_accessory']);
                
                if (!Validate::isLoadedObject($product)) {
                    unset($accessory);
                    continue;
                }
                
                /* customization*/
                $accessory['customization'] = $product->customizable ? $product->getCustomizationFields($this->context->language->id) : false;
                $accessory['customizable'] = $product->customizable;
                $accessory['is_package'] = $this->isProductPackage($product);
                $accessory['is_adding_link'] = $this->isAddingAccessoryUrl($product->visibility);
                $accessory = $this->getCustomizationData($accessory);
                /*End of customization*/
                
                if ($accessory['is_available_buy_together']) {
                    $id_products_buy_together[$accessory['id_accessory_group']] = $accessory['id_accessory'];
                }
                
                if ($is_product_page) {
                    $accessories_table_price[$random_product_accessories_id] = $this->formatAccessory($accessory);
                }
                
                //@todo: Fix the price different with group customer
                $default_id_product_attribute = (int)  $accessories_table_price[$random_product_accessories_id]['default_id_product_attribute'];
                $price = HsMaProduct::getPriceStatic($accessory['id_accessory'], $use_tax, $default_id_product_attribute, $currency_decimals);
                $accessory['price'] = $price;
                $accessory['random_product_accessories_id'] = $random_product_accessories_id;
                $accessory['default_id_product_attribute'] = $default_id_product_attribute;
                
                // *** AJOUT : Vérification du statut "victime de son succès" ***
                $victime_succes = false;
                if ($default_id_product_attribute > 0) {
                    $stockData = $stockManager->getStockData(
                        (int)$accessory['id_accessory'],
                        $default_id_product_attribute,
                        null
                    );
                    $victime_succes = isset($stockData['victime_succes']) ? (bool)$stockData['victime_succes'] : false;
                }
                $accessory['victime_succes'] = $victime_succes;
                // *** FIN AJOUT ***
                
                if ($this->isPrestashop17()) {
                    $accessory['link'] = $this->context->link->getProductLink($product, $product->link_rewrite, $product->category, $product->ean13, (int) $this->context->language->id, (int) $this->context->shop->id, $product->cache_default_attribute);
                } else {
                    $accessory['link'] = $this->context->link->getProductLink($product);
                }
                $accessories_table_price[$random_product_accessories_id]['link'] = $accessory['link'];
                $accessories_table_price[$random_product_accessories_id]['is_package'] = $accessory['is_package'];
                
                if (!empty($accessory['customization'])) {
                    $accessories_table_price[$random_product_accessories_id]['customizations'] = $accessory['customizations'];
                    $accessories_table_price[$random_product_accessories_id]['id_customization'] = $accessory['id_customization'];
                    $accessories_table_price[$random_product_accessories_id]['is_customizable'] = $accessory['is_customizable'];
                    $accessories_table_price[$random_product_accessories_id]['is_enough_customization'] = $accessory['is_enough_customization'];
                }
                $accessory['available_later'] = $this->getMessageAvailableLater($accessory['available_later']);
            }
        }
        
        $setting_buy_together = HsMaProductSetting::getBuyTogetherCurrentValue($id_products[0]);
        
        $this->context->smarty->assign(array(
            'accessory_configuration_keys' => Configuration::getMultiple(array_keys($this->configuration_keys)),
            'accessory_block_title' => Configuration::get('HSMA_TITLE', $this->context->language->id),
            'accessory_image_type' => Configuration::get('HSMA_IMAGE_TYPE'),
            'change_main_price' => Configuration::get('HSMA_CHANGE_MAIN_PRICE'),
            'image_size_fancybox' => Configuration::get('HSMA_IMAGE_SIZE_IN_FANCYBOX'),
            'show_table_price' => Configuration::get('HSMA_SHOW_PRICE_TABLE'),
            'show_combination' => Configuration::get('HSMA_SHOW_COMBINATION'),
            'sync_accessory_quantity' => (int) $this->getOptionAcessoryQuantitySetting(),
            'accessory_groups' => HsAccessoriesGroupAbstract::getGroups($this->context->language->id, true),
            'accessories_table_price' => json_encode($accessories_table_price),
            'js_translate_text' => json_encode($this->getJsTranslateText()),
            'random_main_product_id' => $random_main_product_id,
            'sub_total' => $this->i18n['sub_total'],
            'accessories_groups' => $accessories_groups,
            'static_token' => Tools::getToken(false),
            'is_enabling_cart_ajax' => (int) $this->isEnableBlockCartAjax(),
            'main_product_minimal_quantity' => $is_product_page ? $product->minimal_quantity : 1,
            'buy_main_accessory_together_group' => $setting_buy_together[$id_products[0]],
            'is_product_page' => $is_product_page,
            'isPrestashop17' => $this->isPrestashop17(),
            'is_using_awesome_font' => $this->isUsingAwesomeFont(),
            'id_products_buy_together' => $id_products_buy_together,
            'utilize_block_cart_ajax' => (int) $this->isEnableBlockCartAjax(),
            'path_theme' => $this->isPrestashop17() ? '17/' : '',
        ));
        
        return $this->display($this->name . '.php', 'multi_accessories.tpl');
    }
}
