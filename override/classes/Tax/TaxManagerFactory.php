<?php
if (!defined('_PS_VERSION_')) exit;

class TaxManagerFactory extends TaxManagerFactoryCore
{
    public static function getManager(Address $address, $type)
    {
        $ctx  = Context::getContext();
        $cart = $ctx->cart;

        // Conditions globales : panier + FR + attestation
        if ($cart && $cart->id && self::isFr($address) && self::isAttested((int)$cart->id)) {

            // --- 1) PRODUIT SERVICE => 10 %
            $serviceId = self::getServiceProductId();
            if ($serviceId) {
                $serviceTRG = (int)Product::getIdTaxRulesGroupByIdProduct($serviceId, $ctx);
                if ($serviceTRG && (int)$type === $serviceTRG && self::isProductInCart($cart, $serviceId)) {
                    if ($mgr = self::buildFixedRateManager(10.0)) return $mgr;
                }
            }

            // --- 2) PRODUIT PRINCIPAL (premier non-service) => 10 %
            $mainId = self::getMainProductId($cart, $serviceId);
            if ($mainId) {
                $mainTRG = (int)Product::getIdTaxRulesGroupByIdProduct($mainId, $ctx);
                if ($mainTRG && (int)$type === $mainTRG) {
                    if ($mgr = self::buildFixedRateManager(10.0)) return $mgr;
                }
            }

            // --- 3) TRANSPORTEUR => 10 % si service présent
            if ($serviceId && self::isProductInCart($cart, $serviceId)) {
                /*                $carrierId = self::getSelectedCarrierId($cart);*/
                $carrierId = $cart->id_carrier;
                if ($carrierId) {
                    $carrierTRG = (int)Carrier::getIdTaxRulesGroupByIdCarrier($carrierId);
                    if ($carrierTRG && (int)$type === $carrierTRG) {
                        if ($mgr = self::buildFixedRateManager(10.0)) return $mgr;
                    }
                }
            }
        }

        // Sinon : comportement PrestaShop standard
        return parent::getManager($address, $type);
    }

    protected static function isFr(Address $addr): bool
    {
        return $addr->id_country && strtoupper(Country::getIsoById((int)$addr->id_country)) === 'FR';
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

    protected static function getServiceProductId(): int
    {
        $serviceId = Db::getInstance()->executeS('SELECT cp.id_product FROM '._DB_PREFIX_.'cart_product cp
        LEFT JOIN '._DB_PREFIX_.'product p ON p.id_product = cp.id_product
        WHERE p.id_category_default = 369 AND cp.id_cart = '.Context::getContext()->cart->id);
        /*        // Idéalement: référence configurable en BO
                $ref = '6846df4980a710f6d4b755c7';
                return (int)Product::getIdByReference($ref);*/
        if(!empty($serviceId[0]['id_product'])){
            return $serviceId[0]['id_product'];
        } else {
            return 0;
        }
    }

    protected static function isProductInCart(Cart $cart, int $idProduct): bool
    {
        foreach ($cart->getProducts() as $p) {
            if ((int)$p['id_product'] === $idProduct) return true;
        }
        return false;
    }

    protected static function getMainProductId(Cart $cart, int $serviceId): int
    {
        /*        foreach ($cart->getProducts() as $p) {
                    $pid = (int)$p['id_product'];
                    if ($pid && $pid !== $serviceId) return $pid; // premier non-service
                }
                return 0;*/
        $id_main_product = Db::getInstance()->executeS('SELECT id_main_product FROM '._DB_PREFIX_.'dealt_tva_attestation 
        WHERE id_cart = '.$cart->id);
        return $id_main_product[0]['id_main_product'];
    }

    protected static function getSelectedCarrierId(Cart $cart): int
    {
        if ((int)$cart->id_carrier) return (int)$cart->id_carrier;

        $opts = $cart->getDeliveryOption();
        if (is_array($opts)) {
            $idAddr = (int)$cart->id_address_delivery ?: (int)$cart->id_address_invoice;
            if (!empty($opts[$idAddr]) && preg_match('/\d+/', (string)$opts[$idAddr], $m)) {
                return (int)$m[0];
            }
        }
        return 0;
    }

    /**
     * Construit un manager "taux fixe" basé sur une taxe existante (crée 10 % si besoin)
     */
    protected static function buildFixedRateManager(float $rate)
    {
        // Charger la classe du manager du module si nécessaire
        if (!class_exists('\Dealt\Tax\DealtFixedRateTaxManager')) {
            $path = _PS_MODULE_DIR_.'dealt/src/Tax/DealtFixedRateTaxManager.php';
            if (file_exists($path)) require_once $path;
        }
        if (!class_exists('\Dealt\Tax\DealtFixedRateTaxManager')) {
            return null;
        }

        $idTax = (int)Db::getInstance()->getValue(
            'SELECT id_tax FROM '._DB_PREFIX_.'tax
             WHERE active=1 AND ABS(rate-'.(float)$rate.')<0.0001
             ORDER BY id_tax ASC'
        );

        // Si pas de taxe 10 %, on la crée (FR uniquement)
        if (!$idTax && (int)$rate === 10) {
            $idTax = self::ensureTva10TaxId();
        }
        if ($idTax <= 0) return null;

        $calc = new TaxCalculator([new Tax((int)$idTax)]);
        return new \Dealt\Tax\DealtFixedRateTaxManager($calc);
    }

    /**
     * Création (une fois) de la taxe 10 % + règle FR
     */
    protected static function ensureTva10TaxId(): int
    {
        $idTax = (int)Db::getInstance()->getValue(
            'SELECT id_tax FROM '._DB_PREFIX_.'tax
             WHERE active=1 AND ABS(rate-10.0)<0.0001
             ORDER BY id_tax ASC'
        );
        if ($idTax) return $idTax;

        $tax = new Tax();
        $tax->name = [];
        foreach (Language::getLanguages(false) as $lang) {
            $tax->name[(int)$lang['id_lang']] = 'TVA 10% (Dealt)';
        }
        $tax->rate = 10.0;
        $tax->active = 1;
        if (!$tax->add()) return 0;

        $idCountry = (int)Country::getByIso('FR');
        if ($idCountry) {
            $grp = new TaxRulesGroup();
            $grp->name = 'TVA 10% FR (Dealt)';
            $grp->active = 1;
            $grp->add();

            $rule = new TaxRule();
            $rule->id_tax_rules_group = (int)$grp->id;
            $rule->id_country         = $idCountry;
            $rule->id_state           = 0;
            $rule->zipcode_from       = 0;
            $rule->zipcode_to         = 0;
            $rule->id_tax             = (int)$tax->id;
            $rule->behavior           = 0;
            $rule->save();
        }

        return (int)$tax->id;
    }
}
