<?php
/**
 * Résolution d’URLs → fiches produit pour le carrousel Closy (panneau).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DtcClosyProductResolver
{
    /** @var Context */
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param list<string> $urls
     *
     * @return list<array<string, mixed>>
     */
    public function resolveProductsFromUrls(array $urls, int $max = null): array
    {
        if ($max === null) {
            $max = DtcClosyConfig::CAROUSEL_MAX_PRODUCTS;
        }

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $seen = [];
        $products = [];

        foreach ($urls as $url) {
            if (count($products) >= $max) {
                break;
            }
            $url = $this->normalizeShopProductUrl(trim((string) $url));
            if ($url === '' || (!$this->isShopUrl($url) && !$this->urlPathLooksLikeProductPage($url))) {
                continue;
            }

            $idProduct = $this->resolveIdProductFromUrl($url, $idLang, $idShop);
            if ($idProduct <= 0 || isset($seen[$idProduct])) {
                continue;
            }

            $card = $this->buildProductCard($idProduct, $idLang);
            if ($card === null) {
                continue;
            }

            $seen[$idProduct] = true;
            $products[] = $card;
        }

        return $products;
    }

    public static function urlLooksLikeProductPage(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return $path !== '' && (bool) preg_match('~/\d+-[^/.]+(?:\.html)?(?:[/?#]|$)~i', $path);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buildProductCard(int $idProduct, int $idLang): ?array
    {
        if ($idProduct <= 0) {
            return null;
        }

        $product = new Product($idProduct, false, $idLang);
        if (!Validate::isLoadedObject($product)) {
            return null;
        }

        if (!(bool) Db::getInstance()->getValue(
            'SELECT product_shop.`id_product`
            FROM `' . _DB_PREFIX_ . 'product_shop` product_shop
            WHERE product_shop.`id_product` = ' . (int) $idProduct . '
            AND product_shop.`id_shop` = ' . (int) $this->context->shop->id
        )) {
            return null;
        }

        $idProductAttribute = (int) Product::getDefaultAttribute($idProduct);
        $priceTaxIncl = Product::getPriceStatic(
            $idProduct,
            true,
            $idProductAttribute > 0 ? $idProductAttribute : null,
            6,
            null,
            false,
            true,
            1
        );

        $linkRewrite = $this->getProductLangField($product, 'link_rewrite', $idLang);
        $imageUrl = '';
        $cover = Product::getCover($idProduct);
        $imageType = class_exists('ImageType') ? ImageType::getFormattedName('home') : 'home_default';
        if (!empty($cover['id_image'])) {
            $imageUrl = $this->context->link->getImageLink(
                $linkRewrite,
                (int) $cover['id_image'],
                $imageType
            );
        }

        return [
            'id_product' => $idProduct,
            'id_product_attribute' => $idProductAttribute,
            'name' => $this->getProductLangField($product, 'name', $idLang),
            'url' => $this->context->link->getProductLink($product),
            'image_url' => $imageUrl,
            'price' => Tools::displayPrice($priceTaxIncl),
        ];
    }

    private function normalizeShopProductUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        if (preg_match('#^//#', $url)) {
            $url = 'https:' . $url;
        } elseif ($url[0] === '/') {
            $url = rtrim($this->context->link->getBaseLink(), '/') . $url;
        } elseif (!preg_match('#^https?://#i', $url)) {
            return '';
        }

        return $url;
    }

    private function isShopUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null || $host === false || $host === '') {
            return false;
        }

        $host = strtolower($host);
        foreach ($this->getShopHosts() as $shopHost) {
            if ($host === $shopHost || $this->hostEndsWith($host, '.' . $shopHost)) {
                return true;
            }
        }

        return false;
    }

    private function urlPathLooksLikeProductPage(string $url): bool
    {
        return self::urlLooksLikeProductPage($url);
    }

    /**
     * @return list<string>
     */
    private function getShopHosts(): array
    {
        $hosts = [];
        foreach ([
            (string) parse_url($this->context->link->getBaseLink(), PHP_URL_HOST),
            (string) Configuration::get('PS_SHOP_DOMAIN'),
            (string) Configuration::get('PS_SHOP_DOMAIN_SSL'),
        ] as $host) {
            $host = strtolower(trim($host));
            if ($host !== '' && !in_array($host, $hosts, true)) {
                $hosts[] = $host;
            }
        }

        return $hosts;
    }

    private function resolveIdProductFromUrl(string $url, int $idLang, int $idShop): int
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $path = rawurldecode($path);
        $path = rtrim($path, '/');

        if ($path === '' || $this->pathLooksLikeCategoryPage($path, $idLang)) {
            return 0;
        }

        $pairs = [];
        if (preg_match_all('~/(\d+)-([^/]+?)(?:\.html)?(?=/|$)~i', $path, $segments, PREG_SET_ORDER)) {
            foreach ($segments as $segment) {
                $pairs[] = [(int) $segment[1], (string) $segment[2]];
            }
        }

        for ($i = count($pairs) - 1; $i >= 0; --$i) {
            [$idFromUrl, $slug] = $pairs[$i];
            $resolved = $this->validateProductId($idFromUrl, $idLang, $idShop);
            if ($resolved > 0) {
                return $resolved;
            }
            if ($slug !== '') {
                $bySlug = $this->resolveIdByLinkRewrite($slug, $idLang, $idShop);
                if ($bySlug > 0) {
                    return $bySlug;
                }
            }
        }

        if (preg_match('#/([^/]+)\.html?$#i', $path, $matches)) {
            $slug = $matches[1];
            if (preg_match('#^(\d+)-(.+)$#', $slug, $parts)) {
                $resolved = $this->validateProductId((int) $parts[1], $idLang, $idShop);
                if ($resolved > 0) {
                    return $resolved;
                }
                $slug = $parts[2];
            }

            return $this->resolveIdByLinkRewrite($slug, $idLang, $idShop);
        }

        return 0;
    }

    private function pathLooksLikeCategoryPage(string $path, int $idLang): bool
    {
        if (!preg_match('#^/(\d+)-([^/]+)$#', $path, $matches)) {
            return false;
        }

        $category = new Category((int) $matches[1], $idLang);

        return Validate::isLoadedObject($category);
    }

    private function validateProductId(int $idProduct, int $idLang, int $idShop): int
    {
        if ($idProduct <= 0) {
            return 0;
        }

        $exists = (bool) Db::getInstance()->getValue(
            'SELECT p.`id_product`
            FROM `' . _DB_PREFIX_ . 'product` p
            ' . Shop::addSqlAssociation('product', 'p') . '
            WHERE p.`id_product` = ' . (int) $idProduct
        );

        if (!$exists) {
            return 0;
        }

        $product = new Product($idProduct, false, $idLang, $idShop);

        return Validate::isLoadedObject($product) ? $idProduct : 0;
    }

    private function resolveIdByLinkRewrite(string $linkRewrite, int $idLang, int $idShop): int
    {
        $linkRewrite = Tools::str2url($linkRewrite);
        if ($linkRewrite === '') {
            return 0;
        }

        $idProduct = (int) Db::getInstance()->getValue(
            'SELECT p.`id_product`
            FROM `' . _DB_PREFIX_ . 'product_lang` pl
            INNER JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = pl.`id_product`
            ' . Shop::addSqlAssociation('product', 'p') . '
            WHERE pl.`link_rewrite` = \'' . pSQL($linkRewrite) . '\'
            AND pl.`id_lang` = ' . (int) $idLang . '
            LIMIT 1'
        );

        return $idProduct > 0 ? $this->validateProductId($idProduct, $idLang, $idShop) : 0;
    }

    private function hostEndsWith(string $host, string $suffix): bool
    {
        $suffixLength = strlen($suffix);

        return $suffixLength > 0 && substr($host, -$suffixLength) === $suffix;
    }

    private function getProductLangField(Product $product, string $field, int $idLang): string
    {
        $value = $product->{$field};
        if (is_array($value)) {
            return (string) ($value[$idLang] ?? reset($value) ?: '');
        }

        return (string) $value;
    }
}
