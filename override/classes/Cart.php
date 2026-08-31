<?php
/**
 *  Order Fees Shipping
 *
 *  @author    motionSeed <ecommerce@motionseed.com>
 *  @copyright 2017 motionSeed. All rights reserved.
 *  @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
class Cart extends CartCore
{
    // Cache statique pour éviter les requêtes répétées de getProductQuantity
    private static $product_quantity_cache = [];
    // Marqueur de préchargement par cart ID (évite de relancer le batch)
    private static $quantity_cache_preloaded = [];

    public function __construct($id = null, $id_lang = null, $id_currency = null)
    {
        parent::__construct($id, $id_lang, $id_currency);

        // Injecte le champ new_site dans la définition du modèle pour qu'il soit persisté
        if (!isset(static::$definition['fields']['synchro'])) {
            static::$definition['fields']['synchro'] = [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => false,
            ];
        }
    }

    /*
    * module: orderfees_shipping
    * date: 2025-02-24 17:50:29
    * version: 1.23.11
    */
    public function getPackageShippingCost(
        $id_carrier = null,
        $use_tax = true,
        Country $default_country = null,
        $product_list = null,
        $id_zone = null,
        bool $keepOrderPrices = false,
        bool $soubCheck = true
    ) {
        if ($this->isVirtualCart()) {
            return 0;
        }

        static $cache = [];
        static $module = null;

        if ($module === null) {
            $module = Module::getInstanceByName('orderfees_shipping');
        }

        $cache_key = crc32(json_encode(func_get_args()));

        if (!isset($cache[$cache_key])) {
            $total = 0;
            $return = false;
            $cache[$cache_key] = false;
            Hook::exec('actionCartGetPackageShippingCost', array(
                'object' => &$this,
                'id_carrier' => &$id_carrier,
                'use_tax' => &$use_tax,
                'default_country' => &$default_country,
                'product_list' => &$product_list,
                'id_zone' => &$id_zone,
                'keepOrderPrices' => &$keepOrderPrices,
                'total' => &$total,
                'return' => &$return
            ));
            if ($return) {
                $cache[$cache_key] = ($total !== false ? (float) Tools::ps_round((float) $total, 2) : false);
            } else {
                $shipping_cost = parent::getPackageShippingCost(
                    $id_carrier,
                    $use_tax,
                    $default_country,
                    $product_list,
                    $id_zone,
                    $keepOrderPrices
                );

                $cart = Context::getContext()->cart;
                /*$isTvaAttested = self::isAttested((int)$cart->id);*/
                if ($soubCheck) {
                    Hook::exec('actionSoubShippingCost', [
                        'object' => &$this,
                        'id_carrier' => &$id_carrier,
                        'use_tax' => &$use_tax,
                        'default_country' => &$default_country,
                        'product_list' => &$product_list,
                        'id_zone' => &$id_zone,
                        'keepOrderPrices' => &$keepOrderPrices,
                        'shipping_cost' => &$shipping_cost,
                        'total_extra' => &$total,
                        /*'is_tva_attested' => &$isTvaAttested,*/
                    ]);
                }

                if ($shipping_cost !== false) {
                    $cache[$cache_key] = $shipping_cost + (float) Tools::ps_round((float) $total, 2);
                }
            }
        }

        return $cache[$cache_key];
    }

    public function add($autoDate = true, $nullValues = false)
    {
        // 1. Exécute l'insertion normale
        $result = parent::add($autoDate, $nullValues);

        // 2. Si insertion réussie, fais un UPDATE direct du champ new_site
        if ($result && $this->id) {
            Db::getInstance()->update('cart', [
                'synchro' => 0
            ], 'id_cart = '.(int)$this->id);
        }

        return $result;
    }

    /*
    * module: orderfees_shipping
    * date: 2025-02-24 17:50:29
    * version: 1.23.11
    */
    public function getTotalWeight($products = null)
    {
        $total_weight = 0;
        $return = false;

        Hook::exec('actionCartGetTotalWeight', array(
            'object' => &$this,
            'products' => &$products,
            'total_weight' => &$total_weight,
            'return' => &$return
        ));

        if ($return) {
            return $total_weight;
        }

        return parent::getTotalWeight($products) + $total_weight;
    }

    public function getPackageList($flush = false){

        $cache_key = (int) $this->id . '_' . (int) $this->id_address_delivery;
        static::$cachePackageList[$cache_key] = false;

        $package_list = parent::getPackageList();

        if(!empty($package_list)){
            $id_address_delivery = $this->id_address_delivery;

            $packages = [];

            if(!empty($id_address_delivery) && isset($package_list[$id_address_delivery]) && is_array($package_list[$id_address_delivery])){
                foreach ($package_list[$id_address_delivery] as $package) {
                    $package10 = [];
                    $package20 = [];
                    foreach ($package['product_list'] as $product) {
                        if($product['rate'] == 10){
                            $package10['product_list'][] = $product;
                        } else {
                            $package20['product_list'][] = $product;
                        }
                    }

                    if(!empty($package10['product_list']) && count($package10['product_list']) > 0){
                        $package10['carrier_list'] = $package['carrier_list'];
                        $package10['warehouse_list'] = $package['warehouse_list'];
                        $package10['id_warehouse'] = $package['id_warehouse'];
                        $packages[] = $package10;
                    }

                    if(!empty($package20['product_list']) && count($package20['product_list']) > 0){
                        $package20['carrier_list'] = $package['carrier_list'];
                        $package20['warehouse_list'] = $package['warehouse_list'];
                        $package20['id_warehouse'] = $package['id_warehouse'];
                        $packages[] = $package20;
                    }

                }
                $package_list[$id_address_delivery] = $packages;
            }
            /*        dump($packages);*/
        }

        return $package_list;
    }

    protected static function isAttested(int $idCart): bool
    {
        static $cache = [];

        if (isset($cache[$idCart])) {
            return $cache[$idCart];
        }

        $table = _DB_PREFIX_.'dealt_tva_attestation';

        $cache[$idCart] = (bool)Db::getInstance()->getValue(
            "SELECT 1 FROM `$table` WHERE id_cart=".(int)$idCart." AND is_validated=1"
        );

        return $cache[$idCart];
    }

    /**
     * Précharge en 2 requêtes les quantités de TOUS les produits du panier.
     * Réduit les appels SQL de N×getProductQuantity() à 2 requêtes par requête HTTP.
     */
    private function preloadQuantityCache(): void
    {
        if (!$this->id || isset(self::$quantity_cache_preloaded[$this->id])) {
            return;
        }
        self::$quantity_cache_preloaded[$this->id] = true;

        $cartId = (int)$this->id;
        $defaultPackStockType = Configuration::get('PS_PACK_STOCK_TYPE');
        $packStockTypesAllowed = [Pack::STOCK_TYPE_PRODUCTS_ONLY, Pack::STOCK_TYPE_PACK_BOTH];
        $packStockTypesDefaultSupported = (int) in_array($defaultPackStockType, $packStockTypesAllowed);

        // Multishipping : si actif, on groupe aussi par adresse de livraison
        $multishipping = (bool) Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery();
        $addrField = $multishipping ? ', cp.`id_address_delivery`' : '';
        $addrGroup = $multishipping ? ', cp.`id_address_delivery`' : '';

        // Requête 1 : quantités directes (produits simples)
        $directRows = Db::getInstance()->executeS(
            'SELECT cp.`id_product`, cp.`id_product_attribute`, cp.`id_customization`' . $addrField . ',
                    SUM(cp.`quantity`) AS first_level_quantity
             FROM `' . _DB_PREFIX_ . 'cart_product` cp
             WHERE cp.`id_cart` = ' . $cartId . '
             GROUP BY cp.`id_product`, cp.`id_product_attribute`, cp.`id_customization`' . $addrGroup
        );

        $map = [];
        foreach ($directRows ?: [] as $row) {
            $addr = $multishipping ? (int)$row['id_address_delivery'] : 0;
            $k = $cartId . '_' . (int)$row['id_product'] . '_' . (int)$row['id_product_attribute'] . '_' . (int)$row['id_customization'] . '_' . $addr;
            $map[$k] = ['quantity' => (int)$row['first_level_quantity'], 'deep_quantity' => (int)$row['first_level_quantity']];
        }

        // Requête 2 : quantités issues des packs (produits composants)
        $packRows = Db::getInstance()->executeS(
            'SELECT p.`id_product_item` AS id_product, cp.`id_product_attribute`, cp.`id_customization`' . $addrField . ',
                    SUM(cp.`quantity` * p.`quantity`) AS pack_quantity
             FROM `' . _DB_PREFIX_ . 'cart_product` cp
             JOIN `' . _DB_PREFIX_ . 'pack` p ON cp.`id_product` = p.`id_product_pack`
             JOIN `' . _DB_PREFIX_ . 'product` pr ON p.`id_product_pack` = pr.`id_product`
             WHERE cp.`id_cart` = ' . $cartId . '
               AND (pr.`pack_stock_type` IN (' . implode(',', $packStockTypesAllowed) . ')
                    OR (pr.`pack_stock_type` = ' . Pack::STOCK_TYPE_DEFAULT . ' AND ' . $packStockTypesDefaultSupported . ' = 1))
             GROUP BY p.`id_product_item`, cp.`id_product_attribute`, cp.`id_customization`' . $addrGroup
        );

        foreach ($packRows ?: [] as $row) {
            $addr = $multishipping ? (int)$row['id_address_delivery'] : 0;
            $k = $cartId . '_' . (int)$row['id_product'] . '_' . (int)$row['id_product_attribute'] . '_' . (int)$row['id_customization'] . '_' . $addr;
            if (isset($map[$k])) {
                $map[$k]['deep_quantity'] += (int)$row['pack_quantity'];
            } else {
                $map[$k] = ['quantity' => 0, 'deep_quantity' => (int)$row['pack_quantity']];
            }
        }

        foreach ($map as $k => $data) {
            self::$product_quantity_cache[$k] = $data;
        }
    }

    /**
     * Invalide le cache des quantités (à appeler après modification du panier).
     */
    public function invalidateQuantityCache(): void
    {
        if ($this->id) {
            unset(self::$quantity_cache_preloaded[$this->id]);
            foreach (array_keys(self::$product_quantity_cache) as $k) {
                if (strncmp($k, $this->id . '_', strlen($this->id) + 1) === 0) {
                    unset(self::$product_quantity_cache[$k]);
                }
            }
        }
    }

    /**
     * @param int $idProduct Product ID
     * @param int $idProductAttribute Product Attribute ID
     * @param int|bool $idCustomization Customization ID
     * @param int $idAddressDelivery Delivery Address ID
     *
     * @return array quantity index: number of product in cart without counting those of pack in cart
     *               deep_quantity index: number of product in cart counting those of pack in cart
     */
    public function getProductQuantity($idProduct, $idProductAttribute = 0, $idCustomization = 0, $idAddressDelivery = 0)
    {
        // Le cas idCustomization=false (filtre désactivé) ne peut pas être préchargé — fallback direct
        if ($idCustomization === false) {
            return parent::getProductQuantity($idProduct, $idProductAttribute, $idCustomization, $idAddressDelivery);
        }

        // Préchargement de tout le panier en 2 requêtes (exécuté une seule fois par requête HTTP)
        $this->preloadQuantityCache();

        $cache_key = $this->id . '_' . (int)$idProduct . '_' . (int)$idProductAttribute . '_' . (int)$idCustomization . '_' . (int)$idAddressDelivery;

        if (isset(self::$product_quantity_cache[$cache_key])) {
            return self::$product_quantity_cache[$cache_key];
        }

        // Cache miss : on requête directement (produit ajouté après le préchargement)
        return self::$product_quantity_cache[$cache_key] = parent::getProductQuantity($idProduct, $idProductAttribute, $idCustomization, $idAddressDelivery);
    }

    public function updateQty($quantity, $id_product, $id_product_attribute = null, $id_customization = false, $operator = 'up', $id_address_delivery = 0, ?Shop $shop = null, $auto_add_cart_rule = true, $skipAvailabilityCheckOutOfStock = false, bool $preserveGiftRemoval = true, bool $useOrderPrices = false)
    {
        // Invalider le cache avant ET après la mise à jour pour éviter les doublons
        $this->invalidateQuantityCache();
        $result = parent::updateQty($quantity, $id_product, $id_product_attribute, $id_customization, $operator, $id_address_delivery, $shop, $auto_add_cart_rule, $skipAvailabilityCheckOutOfStock, $preserveGiftRemoval, $useOrderPrices);
        $this->invalidateQuantityCache();
        return $result;
    }

    /**
     * Réinjecte les règles Club Pro filtrées par le core (groupe défaut 10).
     */
    public function getCartRules($filter = CartRule::FILTER_ACTION_ALL, $autoAdd = true, $useOrderPrices = false)
    {
        static $depth = 0;
        ++$depth;

        try {
            $result = parent::getCartRules($filter, $autoAdd, $useOrderPrices);

            // Évite la récursion via getOrderTotal pendant la réinjection
            if ($depth > 1 || !$this->id) {
                return $result;
            }

            $customer = Context::getContext()->customer;
            if (!$customer || !(int) $customer->id) {
                return $result;
            }

            $customerGroupIds = array_map('intval', (array) $customer->getGroups());

            $presentIds = [];
            foreach ($result as $row) {
                $presentIds[(int) ($row['id_cart_rule'] ?? 0)] = true;
            }

            $sqlFilter = '';
            if ($filter == CartRule::FILTER_ACTION_SHIPPING) {
                $sqlFilter = ' AND cr.free_shipping = 1';
            } elseif ($filter == CartRule::FILTER_ACTION_GIFT) {
                $sqlFilter = ' AND cr.gift_product != 0';
            } elseif ($filter == CartRule::FILTER_ACTION_REDUCTION) {
                $sqlFilter = ' AND (cr.reduction_percent != 0 OR cr.reduction_amount != 0)';
            }

            $clubRows = Db::getInstance()->executeS(
                'SELECT cr.*, crl.`id_lang`, crl.`name`, cd.`id_cart`
                 FROM `' . _DB_PREFIX_ . 'cart_cart_rule` cd
                 INNER JOIN `' . _DB_PREFIX_ . 'cart_rule` cr ON cd.`id_cart_rule` = cr.`id_cart_rule`
                 LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_lang` crl ON (
                    cd.`id_cart_rule` = crl.`id_cart_rule`
                    AND crl.id_lang = ' . (int) $this->getAssociatedLanguage()->getId() . '
                 )
                 WHERE cd.`id_cart` = ' . (int) $this->id . '
                   AND cr.description LIKE "DTC_PALIER_SHARED_%"'
                . $sqlFilter
            );

            if (!$clubRows) {
                return $result;
            }

            $virtualContext = Context::getContext()->cloneContext();
            /* @phpstan-ignore-next-line */
            $virtualContext->cart = $this;
            $virtualContext->virtualTotalTaxExcluded = $this->getOrderTotal(false, self::ONLY_PRODUCTS);
            if (Tax::excludeTaxeOption()) {
                $virtualContext->virtualTotalTaxIncluded = $virtualContext->virtualTotalTaxExcluded;
            } else {
                $virtualContext->virtualTotalTaxIncluded = $this->getOrderTotal(true, self::ONLY_PRODUCTS);
            }

            foreach ($clubRows as $row) {
                $idCartRule = (int) $row['id_cart_rule'];
                if ($idCartRule <= 0 || isset($presentIds[$idCartRule])) {
                    continue;
                }

                $cartRuleGroupIds = array_map('intval', array_column(
                    Db::getInstance()->executeS(
                        'SELECT id_group FROM `' . _DB_PREFIX_ . 'cart_rule_group`
                         WHERE id_cart_rule = ' . $idCartRule
                    ) ?: [],
                    'id_group'
                ));

                if (!$cartRuleGroupIds || !array_intersect($cartRuleGroupIds, $customerGroupIds)) {
                    continue;
                }

                $row['obj'] = new CartRule($idCartRule, (int) $this->id_lang);
                if (!Validate::isLoadedObject($row['obj']) || !(bool) $row['obj']->active) {
                    continue;
                }

                $row['value_real'] = $row['obj']->getContextualValue(true, $virtualContext, $filter);
                $row['value_tax_exc'] = $row['obj']->getContextualValue(false, $virtualContext, $filter);
                $row['id_discount'] = $idCartRule;
                $row['description'] = $row['obj']->description;
                $result[] = $row;
                $presentIds[$idCartRule] = true;
            }

            return $result;
        } finally {
            --$depth;
        }
    }

    /**
     * SP panier à prix fixe (pack explosé ML pro) : alimente reduction / specific_prices pour prix barrés et badges.
     */
    protected function applyProductCalculations($row, $shopContext, $productQuantity = null, bool $keepOrderPrices = false)
    {
        $row = parent::applyProductCalculations($row, $shopContext, $productQuantity, $keepOrderPrices);

        $productQuantity = null === $productQuantity ? (int) $row['cart_quantity'] : (int) $productQuantity;
        $displayTaxExcluded = Product::getTaxCalculationMethod((int) Context::getContext()->customer->id) == PS_TAX_EXC;

        if ((int) $this->id <= 0 || $keepOrderPrices) {
            return $this->enrichCartLineReductionDisplay($row, $displayTaxExcluded);
        }

        $cartFixedSp = Db::getInstance()->getRow(
            'SELECT `price`
             FROM `' . _DB_PREFIX_ . 'specific_price`
             WHERE `id_cart` = ' . (int) $this->id . '
               AND `id_product` = ' . (int) $row['id_product'] . '
               AND `id_product_attribute` = ' . (int) $row['id_product_attribute'] . '
               AND `price` >= 0
               AND `reduction` = 0'
        );
        if (!$cartFixedSp) {
            return $this->enrichCartLineReductionDisplay($row, $displayTaxExcluded);
        }

        $addressId = $this->getCartLineTaxAddressId($row);
        $catalogPriceHt = $this->getCatalogUnitPriceWithoutCartSp($row, false, $productQuantity, $shopContext, $addressId);
        $catalogPriceTtc = $this->getCatalogUnitPriceWithoutCartSp($row, true, $productQuantity, $shopContext, $addressId);
        $reductionWithoutTax = $catalogPriceHt - (float) $row['price_with_reduction_without_tax'];
        $reductionWithTax = $catalogPriceTtc - (float) $row['price_with_reduction'];

        if ($reductionWithoutTax <= 0.000001 && $reductionWithTax <= 0.000001) {
            return $row;
        }

        $row['price_without_reduction_without_tax'] = $catalogPriceHt;
        $row['price_without_reduction'] = $catalogPriceTtc;
        $row['reduction_without_tax'] = $reductionWithoutTax;
        $row['reduction'] = $displayTaxExcluded ? $reductionWithoutTax : $reductionWithTax;

        $referencePrice = $displayTaxExcluded ? $catalogPriceHt : $catalogPriceTtc;
        $percentRatio = $referencePrice > 0 ? ($row['reduction'] / $referencePrice) : 0;
        $row['specific_prices'] = [
            'id_cart' => (int) $this->id,
            'price' => (float) $cartFixedSp['price'],
            'reduction_type' => 'percentage',
            'reduction' => (string) (round($percentRatio * 100) / 100),
        ];

        return $row;
    }

    private function getCartLineTaxAddressId(array $row): ?int
    {
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
            $addressId = (int) $this->id_address_invoice;
        } else {
            $addressId = (int) $row['id_address_delivery'];
        }

        return Address::addressExists($addressId, true) ? $addressId : null;
    }

    private function getCatalogUnitPriceWithoutCartSp(array $row, bool $withTax, int $productQuantity, $shopContext, ?int $addressId): float
    {
        $specificPriceOutput = null;

        return (float) Product::getPriceStatic(
            (int) $row['id_product'],
            $withTax,
            (int) $row['id_product_attribute'],
            6,
            null,
            false,
            true,
            $productQuantity,
            false,
            (int) $this->id_customer ?: null,
            0,
            $addressId,
            $specificPriceOutput,
            true,
            true,
            $shopContext,
            true,
            (int) $row['id_customization']
        );
    }

    private function enrichCartLineReductionDisplay(array $row, bool $displayTaxExcluded): array
    {
        $reductionWithoutTax = (float) $row['price_without_reduction_without_tax'] - (float) $row['price_with_reduction_without_tax'];
        $reductionWithTax = (float) $row['price_without_reduction'] - (float) $row['price_with_reduction'];

        if (empty($row['reduction_applies']) && $reductionWithoutTax <= 0.000001 && $reductionWithTax <= 0.000001) {
            return $row;
        }

        $row['reduction_without_tax'] = $reductionWithoutTax;
        $row['reduction'] = $displayTaxExcluded ? $reductionWithoutTax : $reductionWithTax;

        if (empty($row['specific_prices']) || !is_array($row['specific_prices'])) {
            $withoutTaxIncl = (float) $row['price_without_reduction'];
            $row['specific_prices'] = [
                'reduction_type' => (!empty($row['reduction_type']) && $row['reduction_type'] !== 0) ? $row['reduction_type'] : 'percentage',
                'reduction' => (string) ($withoutTaxIncl > 0 ? ($reductionWithTax / $withoutTaxIncl) : 0),
            ];
        } elseif (isset($row['specific_prices']['reduction']) && !is_string($row['specific_prices']['reduction'])) {
            $row['specific_prices']['reduction'] = (string) $row['specific_prices']['reduction'];
        }

        return $row;
    }
}
