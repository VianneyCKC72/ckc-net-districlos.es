<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CartWarningEvaluator
{
    /** @var Module */
    private $module;

    /** @var array|null */
    private $warningsCache;

    /** @var array<int, array> */
    private $productDataCache = [];

    /** @var bool */
    private $productMessageCssIncluded = false;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * @return array<string, string>
     */
    public function assignToSmarty()
    {
        $messages = $this->getCartWarnings();
        Context::getContext()->smarty->assign('custom_product_message', $messages);

        return $messages;
    }

    /**
     * @return array<int|string, string>
     */
    private function getCartWarnings()
    {
        if ($this->warningsCache !== null) {
            return $this->warningsCache;
        }

        if (!Validate::isLoadedObject(Context::getContext()->cart)) {
            return $this->warningsCache = [];
        }

        $products = Context::getContext()->cart->getProducts();
        if (empty($products)) {
            return $this->warningsCache = [];
        }

        $evaluation = CartWarningRuleRepository::getEvaluationData((int) Context::getContext()->language->id);
        $productIdsFlipped = array_flip(array_column($products, 'id_product'));
        $messages = [];

        foreach ($evaluation['incompatibilities'] as $rule) {
            if (!$this->incompatibilityMatches($rule, $productIdsFlipped, $products)) {
                continue;
            }

            $html = $this->wrapMessage($this->renderTemplate('incompatibility.tpl', [
                'cart_warning_message' => $rule['message'],
            ]));

            foreach ($rule['message_on'] as $idProduct) {
                if (isset($productIdsFlipped[$idProduct])) {
                    $messages[$idProduct] = $html;
                }
            }
            break;
        }

        foreach ($evaluation['missing_simple'] as $rule) {
            $triggerIdsFound = [];
            foreach ($rule['trigger_products'] as $triggerId) {
                if (isset($productIdsFlipped[$triggerId])) {
                    $triggerIdsFound[] = $triggerId;
                }
            }

            if (empty($triggerIdsFound) || isset($productIdsFlipped[$rule['missing_product']])) {
                continue;
            }

            $messageHtml = $this->buildMissingProductMessage(
                $rule['missing_product'],
                $rule['message_main'],
                $rule['message_extra']
            );
            if ($messageHtml === '') {
                continue;
            }

            $messageHtml = $this->wrapMessage($messageHtml);
            foreach ($triggerIdsFound as $triggerId) {
                $messages[$triggerId] = $messageHtml;
            }
        }

        foreach ($evaluation['missing_multiple'] as $rule) {
            $triggerId = $rule['trigger_product'];
            if (!isset($productIdsFlipped[$triggerId]) || isset($messages[$triggerId])) {
                continue;
            }

            $messagesHtml = [];
            foreach ($rule['missing_products'] as $missingProduct) {
                if (isset($productIdsFlipped[$missingProduct['id']])) {
                    continue;
                }
                $html = $this->buildMissingProductMessage(
                    $missingProduct['id'],
                    $missingProduct['message_main'],
                    $missingProduct['message_extra']
                );
                if ($html !== '') {
                    $messagesHtml[] = $this->wrapMessage($html);
                }
            }

            if (!empty($messagesHtml)) {
                $messages[$triggerId] = implode('', $messagesHtml);
            }
        }

        return $this->warningsCache = $this->normalizeMessageKeys($messages);
    }

    /**
     * Clés string pour compatibilité Smarty / ancien CartController.
     *
     * @param array<int|string, string> $messages
     *
     * @return array<string, string>
     */
    private function normalizeMessageKeys(array $messages)
    {
        $normalized = [];
        foreach ($messages as $idProduct => $html) {
            $normalized[(string) (int) $idProduct] = $html;
        }

        return $normalized;
    }

    private function incompatibilityMatches(array $rule, array $productIdsFlipped, array $products)
    {
        if (!empty($rule['all_of'])) {
            foreach ($rule['all_of'] as $idProduct) {
                if (!isset($productIdsFlipped[$idProduct])) {
                    return false;
                }
            }
        }

        if (!empty($rule['any_of'])) {
            $anyFound = false;
            foreach ($rule['any_of'] as $idProduct) {
                if (isset($productIdsFlipped[$idProduct])) {
                    $anyFound = true;
                    break;
                }
            }
            if (!$anyFound) {
                return false;
            }
        }

        if (empty($rule['attribute_contains'])) {
            return true;
        }

        $attributeProduct = (int) $rule['attribute_product'];
        foreach ($products as $product) {
            if ((int) $product['id_product'] !== $attributeProduct) {
                continue;
            }
            if (!empty($product['attributes']) && strpos($product['attributes'], $rule['attribute_contains']) !== false) {
                return true;
            }
        }

        return false;
    }

    private function buildMissingProductMessage($idProductMissing, $messageMain, $messageExtra = '')
    {
        $productData = $this->getProductData((int) $idProductMissing);
        $includeCss = !$this->productMessageCssIncluded;
        if ($includeCss) {
            $this->productMessageCssIncluded = true;
        }

        if (empty($productData)) {
            return $this->renderTemplate('missing-product.tpl', [
                'cart_warning_include_css' => $includeCss,
                'cart_warning' => [
                    'product_url' => '',
                    'product_name' => '',
                    'image_url' => '',
                    'price_html' => '',
                    'combination_html' => '',
                    'add_to_cart_url' => '',
                    'alert_main' => $messageMain,
                    'alert_extra' => $messageExtra,
                ],
            ]);
        }

        return $this->renderTemplate('missing-product.tpl', [
            'cart_warning_include_css' => $includeCss,
            'cart_warning' => [
                'product_url' => $productData['product_url'],
                'product_name' => $productData['name'],
                'image_url' => $productData['image_url'],
                'price_html' => $productData['price_html'],
                'combination_html' => $productData['combination_html'],
                'add_to_cart_url' => $productData['add_to_cart_url'],
                'alert_main' => $messageMain,
                'alert_extra' => $messageExtra,
            ],
        ]);
    }

    private function getProductData($idProduct)
    {
        $idProduct = (int) $idProduct;
        if (isset($this->productDataCache[$idProduct])) {
            return $this->productDataCache[$idProduct];
        }

        $idLang = (int) Context::getContext()->language->id;
        $product = new Product($idProduct, false, $idLang, (int) Context::getContext()->shop->id);
        if (!Validate::isLoadedObject($product)) {
            return $this->productDataCache[$idProduct] = [];
        }

        $productName = is_array($product->name) ? $product->name[$idLang] : $product->name;
        $linkRewrite = is_array($product->link_rewrite) ? $product->link_rewrite[$idLang] : $product->link_rewrite;
        $idProductAttribute = (int) Product::getDefaultAttribute($idProduct);
        $isActive = (bool) $product->active;

        $addToCartUrl = '';
        if ($isActive) {
            $params = [
                'add' => 1,
                'id_product' => $idProduct,
                'id_product_attribute' => $idProductAttribute,
                'qty' => 1,
                'token' => Tools::getToken(false),
            ];
            $addToCartUrl = Context::getContext()->link->getPageLink('cart', true, null, $params, false);
        }

        return $this->productDataCache[$idProduct] = [
            'name' => $productName,
            'product_url' => Context::getContext()->link->getProductLink($idProduct, $linkRewrite),
            'image_url' => $this->getProductImageUrl($idProduct, $linkRewrite),
            'price_html' => $isActive ? $this->getProductPriceHtml($idProduct) : '',
            'combination_html' => $this->getProductCombinationsHtml($idProduct),
            'add_to_cart_url' => $addToCartUrl,
        ];
    }

    private function getProductCombinationsHtml($idProduct)
    {
        if (!Combination::isFeatureActive()) {
            return '';
        }

        $attributesInfo = Product::getAttributesInformationsByProduct($idProduct);
        if (empty($attributesInfo)) {
            return '';
        }

        $attributesByGroup = [];
        foreach ($attributesInfo as $attr) {
            if (empty($attr['group']) || empty($attr['attribute'])) {
                continue;
            }
            $groupName = $attr['group'];
            if (!isset($attributesByGroup[$groupName])) {
                $attributesByGroup[$groupName] = [];
            }
            if (!in_array($attr['attribute'], $attributesByGroup[$groupName], true)) {
                $attributesByGroup[$groupName][] = $attr['attribute'];
            }
        }

        if (empty($attributesByGroup)) {
            return '';
        }

        $combinationText = [];
        foreach ($attributesByGroup as $group => $attributes) {
            $combinationText[] = htmlspecialchars($group, ENT_QUOTES, 'UTF-8')
                . ' : '
                . htmlspecialchars(implode(', ', $attributes), ENT_QUOTES, 'UTF-8');
        }

        return '<div>' . implode(' | ', $combinationText) . '</div>';
    }

    private function getProductPriceHtml($idProduct)
    {
        $idCustomer = !empty(Context::getContext()->cart->id_customer)
            ? (int) Context::getContext()->cart->id_customer
            : null;

        $priceWithTax = Product::getPriceStatic(
            $idProduct,
            true,
            null,
            6,
            null,
            false,
            true,
            1,
            false,
            $idCustomer
        );

        if ($priceWithTax > 0) {
            return Tools::displayPrice($priceWithTax, Context::getContext()->currency);
        }

        return '';
    }

    private function getProductImageUrl($idProduct, $linkRewrite)
    {
        $cover = Product::getCover($idProduct);
        if (empty($cover['id_image'])) {
            return '';
        }

        return (string) Context::getContext()->link->getImageLink($linkRewrite, $cover['id_image'], 'home_default');
    }

    private function wrapMessage($innerHtml)
    {
        return $this->renderTemplate('message-block.tpl', [
            'cart_warning_html' => $innerHtml,
        ]);
    }

    private function renderTemplate($template, array $vars)
    {
        return $this->module->renderHookTemplate($template, $vars);
    }
}
