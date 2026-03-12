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
            $id_address_delivery = Context::getContext()->cart->id_address_delivery;

            $packages = [];

            if(!empty($id_address_delivery)){
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
}
