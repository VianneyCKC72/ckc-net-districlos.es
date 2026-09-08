<?php
class ProductController extends ProductControllerCore
{
    private function getCampaignProductDiscount($product)
    {
        $defaultDiscount = ['active' => false];
        if (!Module::isEnabled('ckcadcampaignconfigurator')) {
            return $defaultDiscount;
        }
        $module = Module::getInstanceByName('ckcadcampaignconfigurator');
        if (!$module || !method_exists($module, 'getCampaignProductDiscountForProduct')) {
            return $defaultDiscount;
        }
        return $module->getCampaignProductDiscountForProduct($product);
    }
    protected function assignAttributesGroups($product_for_template = null)
    {
        parent::assignAttributesGroups($product_for_template);
        $groups = $this->context->smarty->getTemplateVars('groups');
        if (empty($groups) || empty($this->combinations)) {
            return;
        }
        foreach ($this->combinations as $combo) {
            if (empty($combo['attributes_values']) || empty($combo['attributes'])) {
                continue;
            }
            $index = 0;
            foreach ($combo['attributes_values'] as $groupId => $name) {
                $attributeId = (int) ($combo['attributes'][$index++] ?? 0);
                if (!$attributeId || !isset($groups[$groupId])) {
                    continue;
                }
                if (isset($groups[$groupId]['attributes'][$attributeId])) {
                    continue;
                }
                $groups[$groupId]['attributes'][$attributeId] = [
                    'name' => $name,
                    'html_color_code' => '',
                    'texture' => '',
                    'selected' => false,
                ];
            }
        }
        foreach ($groups as &$group) {
            if (!empty($group['attributes'])) {
                ksort($group['attributes'], SORT_NUMERIC);
            }
        }
        unset($group);
        $this->context->smarty->assign('groups', $groups);
    }
    public function initContent()
    {
        $product = $this->getTemplateVarProduct();
        $campaignProductDiscount = $this->getCampaignProductDiscount($product);
        $from_europe = true;
        foreach ($product["features"] as $feature) {
            if ($feature['name'] == 'Provenance') {
                if ($feature['value'] == 'France') {
                    $from_europe = false;
                }
            }
        }
        $product->from_europe = $from_europe;
        $this->context->smarty->assign([
            'from_europe'      => $from_europe,
            'campaignProductDiscount' => $campaignProductDiscount,
        ]);
        parent::initContent();
    }
    public function displayAjaxRefresh()
    {
        $product = $this->getTemplateVarProduct();
        $product['price_amount']         = round($product['price_amount'] * $product['quantity_wanted'], 2);
        $product['price']           = number_format($product['price_amount'], 2, ',', ' ') . '€';
        $product['regular_price_amount'] = round((float)$product['regular_price_amount'] * $product['quantity_wanted'], 2);
        $product['regular_price']        = number_format($product['regular_price_amount'], 2, ',', ' ') . '€';
        $product['unit_price_full']     = number_format(round((float)$product['price_amount'] / $product['quantity_wanted'], 2), 2, ',', ' ') . '€';
        $campaignProductDiscount = $this->getCampaignProductDiscount($product);
        $minimalProductQuantity = $this->getProductMinimalQuantity($product);
        if ($product['cart_quantity'] >= $minimalProductQuantity) {
            $minimalProductQuantity = 1;
        }
        ob_end_clean();
        header('Content-Type: application/json');
        $this->ajaxRender(json_encode([
            'product_prices' => $this->render('catalog/_partials/product-prices', [
                'product' => $product,
                'campaignProductDiscount' => $campaignProductDiscount,
            ]),
            'product_cover_thumbnails' => $this->render('catalog/_partials/product-cover-thumbnails'),
            'product_customization' => $this->render(
                'catalog/_partials/product-customization',
                [
                    'customizations' => $product['customizations'],
                ]
            ),
            'product_details' => $this->render('catalog/_partials/product-details'),
            'product_variants' => $this->render('catalog/_partials/product-variants'),
            'product_discounts' => $this->render('catalog/_partials/product-discounts'),
            'product_add_to_cart' => $this->render('catalog/_partials/product-add-to-cart'),
            'product_additional_info' => $this->render('catalog/_partials/product-additional-info'),
            'product_images_modal' => $this->render('catalog/_partials/product-images-modal'),
            'product_flags' => $this->render('catalog/_partials/product-flags'),
            'product_url' => $this->context->link->getProductLink(
                $product['id_product'],
                null,
                null,
                null,
                $this->context->language->id,
                null,
                $product['id_product_attribute'],
                false,
                false,
                true,
                $this->isPreview() ? ['preview' => '1'] : []
            ),
            'product_minimal_quantity' => $minimalProductQuantity,
            'product_has_combinations' => !empty($this->combinations),
            'id_product_attribute' => $product['id_product_attribute'],
            'id_customization' => $product['id_customization'],
            'product_title' => $this->getTemplateVarPage()['meta']['title'],
            'is_quick_view' => $this->isQuickView(),
            'product_reference' => $product['reference_to_display'],
        ]));
    }
    
    
    
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    protected $redirectionExtraExcludedKeys = ['category', 'rewrite', 'id_product_attribute'];
}
