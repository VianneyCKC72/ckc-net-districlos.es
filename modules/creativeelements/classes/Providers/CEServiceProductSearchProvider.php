<?php

namespace CE;

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use Symfony\Contracts\Translation\TranslatorInterface;
use Product;
use Context;
use Group;
use FrontController;
use Validate;
use Db;
use Tools;
use SpecificPrice;

class CEServiceProductSearchProvider implements ProductSearchProviderInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Retourne les produits avec prix spécifique sur quantité actif
     */
    public function getQuantityDiscounts(
        $id_lang,
        $page_number = 0,
        $nb_products = 10,
        $order_by = null,
        $order_way = null,
        Context $context = null
    ) {
        if (!$context) {
            $context = Context::getContext();
        }

        if ($page_number < 1) {
            $page_number = 1;
        }
        if ($nb_products < 1) {
            $nb_products = 10;
        }
        if (empty($order_by) || $order_by == 'position') {
            $order_by = 'date_add';
        }
        if (empty($order_way)) {
            $order_way = 'DESC';
        }
        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd') {
            $order_by_prefix = 'product_shop';
        } elseif ($order_by == 'name') {
            $order_by_prefix = 'pl';
        }
        if (!Validate::isOrderBy($order_by) || !Validate::isOrderWay($order_way)) {
            die(Tools::displayError('Invalid sorting parameters provided.'));
        }

        $sql = new \DbQuery();
        $sql->select(
            'DISTINCT p.*, product_shop.*,
            stock.out_of_stock, IFNULL(stock.quantity,0) AS quantity,
            pl.description, pl.description_short, pl.link_rewrite, pl.meta_description, pl.meta_keywords,
            pl.meta_title, pl.name, pl.available_now, pl.available_later,
            image_shop.id_image AS id_image, il.legend,
            m.name AS manufacturer_name'
        );

        $sql->from('product', 'p');
        $sql->join(\Shop::addSqlAssociation('product', 'p'));
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int)$id_lang . \Shop::addSqlRestrictionOnLang('pl'));
        $sql->leftJoin('image_shop', 'image_shop', 'image_shop.id_product = p.id_product AND image_shop.cover = 1 AND image_shop.id_shop=' . (int)$context->shop->id);
        $sql->leftJoin('image_lang', 'il', 'image_shop.id_image = il.id_image AND il.id_lang=' . (int)$id_lang);
        $sql->leftJoin('manufacturer', 'm', 'm.id_manufacturer = p.id_manufacturer');
        $sql->leftJoin('category_product', 'cp', 'cp.id_product = p.id_product');
        $sql->leftJoin('category', 'c', 'c.id_category = cp.id_category');

        $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', 'p.id_product = product_attribute_shop.id_product AND product_attribute_shop.default_on = 1 AND product_attribute_shop.id_shop=' . (int)$context->shop->id);
        $sql->join(\Product::sqlStock('p', 0));

        $sql->where('p.active = 1 AND c.id_parent = 369 AND p.id_category_default != 369');

        if ($order_by !== 'price') {
            $sql->orderBy((isset($order_by_prefix) ? pSQL($order_by_prefix) . '.' : '') . '`' . pSQL($order_by) . '` ' . pSQL($order_way));
            $sql->limit($nb_products, (int)(($page_number - 1) * $nb_products));
        }

        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$result) {
            return false;
        }

        $products_ids = [];
        foreach ($result as $row) {
            $products_ids[] = $row['id_product'];
        }
        \Product::cacheFrontFeatures($products_ids, $id_lang);

        foreach ($result as &$product) {
            $product['discount_qty_slider'] = 1;
            $product['initial_price_ht'] = \Tools::ps_round($product['price'], 2);
            // On calcule le prix HT avec le prix spécifique récupéré directement
            if (isset($product['specific_price_ht'])) {
                $product['specific_price_ht'] = \Tools::ps_round($product['specific_price_ht'], 2);
            }
        }
        unset($product);

        return \Product::getProductsProperties((int)$id_lang, $result);
    }


    private function getProductsOrCount(ProductSearchContext $context, ProductSearchQuery $query, $type = 'products')
    {
        $isTypeProducts = $type === 'products';

        $result = $this->getQuantityDiscounts(
            $context->getIdLang(),
            $query->getPage(),
            $query->getResultsPerPage(),
            $query->getSortOrder() ? $query->getSortOrder()->toLegacyOrderBy() : null,
            $query->getSortOrder() ? $query->getSortOrder()->toLegacyOrderWay() : null,
            Context::getContext()
        );

        return $isTypeProducts ? $result : (int)$result;
    }

    public function runQuery(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $products = $this->getProductsOrCount($context, $query, 'products') ?: [];
        $count = $this->getProductsOrCount($context, $query, 'count');

        $result = new ProductSearchResult();
        $result->setProducts($products)->setTotalProductsCount($count);

        $result->setAvailableSortOrders([
            (new SortOrder('product', 'date_add', 'desc'))->setLabel($this->translator->trans('Date added, newest to oldest', [], 'Shop.Theme.Catalog')),
            (new SortOrder('product', 'date_add', 'asc'))->setLabel($this->translator->trans('Date added, oldest to newest', [], 'Shop.Theme.Catalog')),
            (new SortOrder('product', 'name', 'asc'))->setLabel($this->translator->trans('Name, A to Z', [], 'Shop.Theme.Catalog')),
            (new SortOrder('product', 'name', 'desc'))->setLabel($this->translator->trans('Name, Z to A', [], 'Shop.Theme.Catalog')),
            (new SortOrder('product', 'price', 'asc'))->setLabel($this->translator->trans('Price, low to high', [], 'Shop.Theme.Catalog')),
            (new SortOrder('product', 'price', 'desc'))->setLabel($this->translator->trans('Price, high to low', [], 'Shop.Theme.Catalog')),
        ]);

        return $result;
    }
}
