<?php

class ProductController extends ProductControllerCore
{
    public function initContent()
    {
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
/*        $product['rounded_display_price'] = round((float)$product['rounded_display_price'] * $product['quantity_wanted'], 2);
        $product['price_without_reduction'] = round((float)$product['price_without_reduction'] * $product['quantity_wanted'], 2);*/

        $minimalProductQuantity = $this->getProductMinimalQuantity($product);

        // If the product is already in the cart, we can set the minimal quantity to 1
        if ($product['cart_quantity'] >= $minimalProductQuantity) {
            $minimalProductQuantity = 1;
        }

        ob_end_clean();
        header('Content-Type: application/json');
        $this->ajaxRender(json_encode([
            'product_prices' => $this->render('catalog/_partials/product-prices', [
                'product' => $product
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
        ]));
    }
}
