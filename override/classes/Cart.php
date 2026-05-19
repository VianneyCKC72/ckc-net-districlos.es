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

    /**
     * Invalide le cache des quantités avant d'appeler parent::updateQty
     * pour garantir que la décision UPDATE vs INSERT dans CartCore se base sur l'état réel de la DB.
     */
    public function updateQty(
        $quantity,
        $id_product,
        $id_product_attribute = null,
        $id_customization = false,
        $operation = 'up',
        $id_address_delivery = 0,
        Shop $shop = null,
        $auto_add_cart_rule = true,
        $skipAvailabilityCheckOutOfStock = false,
        $preserveGiftRemoval = true,
        $useOrderPrices = false
    ) {
        $this->invalidateQuantityCache();
        return parent::updateQty(
            $quantity,
            $id_product,
            $id_product_attribute,
            $id_customization,
            $operation,
            $id_address_delivery,
            $shop,
            $auto_add_cart_rule,
            $skipAvailabilityCheckOutOfStock,
            $preserveGiftRemoval,
            $useOrderPrices
        );
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

        // Agrégation "toutes customizations" pour servir les appels avec $idCustomization === false
        $aggregated = [];
        foreach ($map as $k => $data) {
            $parts = explode('_', $k);
            // cartId_idProduct_idAttr_idCustom_idAddr
            $aggKey = $parts[0] . '_' . $parts[1] . '_' . $parts[2] . '_all_' . $parts[4];
            if (!isset($aggregated[$aggKey])) {
                $aggregated[$aggKey] = ['quantity' => 0, 'deep_quantity' => 0];
            }
            $aggregated[$aggKey]['quantity'] += $data['quantity'];
            $aggregated[$aggKey]['deep_quantity'] += $data['deep_quantity'];
        }
        foreach ($aggregated as $k => $data) {
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
        // Préchargement de tout le panier en 2 requêtes (exécuté une seule fois par requête HTTP)
        $this->preloadQuantityCache();

        // $idCustomization === false signifie "toutes customizations confondues"
        $customizationKey = ($idCustomization === false) ? 'all' : (int)$idCustomization;
        // En non-multishipping, le query SQL d'origine ignore id_address_delivery → on force 0 pour matcher le préchargement
        $multishipping = (bool) Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery();
        $addrKey = $multishipping ? (int)$idAddressDelivery : 0;
        $cache_key = $this->id . '_' . (int)$idProduct . '_' . (int)$idProductAttribute . '_' . $customizationKey . '_' . $addrKey;

        if (isset(self::$product_quantity_cache[$cache_key])) {
            return self::$product_quantity_cache[$cache_key];
        }

// Cache miss : fallback parent (produit ajouté après préchargement ou après updateQty)
        return self::$product_quantity_cache[$cache_key] = parent::getProductQuantity($idProduct, $idProductAttribute, $idCustomization, $idAddressDelivery);
    }

    public function updateQty($quantity, $id_product, $id_product_attribute = null, $id_customization = false, $operator = 'up', $id_address_delivery = 0, ?Shop $shop = null, $auto_add_cart_rule = true, $skipAvailabilityCheckOutOfStock = false, bool $preserveGiftRemoval = true, bool $useOrderPrices = false)
    {
        $this->invalidateQuantityCache();
        $result = parent::updateQty($quantity, $id_product, $id_product_attribute, $id_customization, $operator, $id_address_delivery, $shop, $auto_add_cart_rule, $skipAvailabilityCheckOutOfStock, $preserveGiftRemoval, $useOrderPrices);
        $this->invalidateQuantityCache();

        return $result;
    }
}
