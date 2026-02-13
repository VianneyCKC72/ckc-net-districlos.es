<?php

namespace Dealt;

use Context;
use Configuration;
use Db;
use Language;
use Product;
use Address;
use Country;
use Tax;
use TaxCalculator;
use TaxRule;
use TaxRulesGroup;
use Tools;

class DealtHookHelper
{

    private const SERVICES_PARENT_CATEGORY_ID = 369;

    /* ============ PUBLIC: appelés par dealt.php ============ */

    public static function applyTvaOnPresentedCartProduct(Context $ctx, array &$p): void
    {
        $cart = $ctx->cart;
        if (!$cart || !$cart->id) return;
        if (!self::isTvaAttestedForCart((int)$cart->id)) return;
        if (!self::isFranceCart($ctx)) return;

        $idP = (int)($p['id_product'] ?? 0);
        if (!$idP) return;

        /*        $id_main_productSQl = Db::getInstance()->getValue('SELECT id_main_product FROM '._DB_PREFIX_.'dealt_tva_attestation WHERE id_cart = '.(int)$cart->id);
                $id_main_product = !empty($id_main_productSQl) ? $id_main_productSQl : 0;*/

        $eligible = self::getEligibleProductIdsForCart($ctx, $cart);
        if (!in_array($idP, $eligible, true)) return;

        $calc = self::getTax10Calculator();
        if (!$calc) return;

        $qty = (int)($p['quantity'] ?? 1);
        $priceTE = isset($p['price']) ? (float)$p['price'] : 0.0;
        if ($priceTE <= 0 && !empty($p['price_with_tax'])) {
            $priceTE = (float)$p['price_with_tax'] / 1.20;
        }

        $priceWT = (float)$calc->addTaxes($priceTE);
        $totalWT = $priceWT * $qty;
        $totalTE = $priceTE * $qty;

        // mises à jour (HT/TTC + formats)
        $p['price'] = $priceTE;
        $p['unit_price'] = $priceTE;
        $p['price_with_tax'] = $priceWT;
        $p['price_wt'] = $priceWT;
        $p['unit_price_wt'] = $priceWT;
        $p['unit_price_tax_included'] = $priceWT;
        $p['unit_price_tax_excluded'] = $priceTE;

        $p['total'] = $totalTE;
        $p['total_with_tax'] = $totalWT;
        $p['total_wt'] = $totalWT;
        $p['price_with_reduction'] = $priceWT;
        $p['price_with_reduction_without_tax'] = $priceTE;

        $p['price_amount'] = $priceWT;
        $p['price_wt_amount'] = $priceWT;
        $p['total_price'] = $totalWT;
        $p['total_price_wt'] = $totalWT;

        if (class_exists(Tools::class)) {
            $p['price_with_tax_formatted'] = self::formatPrice($ctx, $priceWT);
            $p['price_wt_formatted'] = self::formatPrice($ctx, $priceWT);
            $p['total_with_tax_formatted'] = self::formatPrice($ctx, $totalWT);
            $p['total_wt_formatted'] = self::formatPrice($ctx, $totalWT);
            $p['unit_price_full'] = self::formatPrice($ctx, $priceWT);
            $p['unit_price_full_with_tax'] = self::formatPrice($ctx, $priceWT);
            $p['unit_price_full_without_tax'] = self::formatPrice($ctx, $priceTE);
        }
    }

    public static function applyTvaOnPresentedCart(Context $ctx, &$presentedCart): void
    {
        $cart = $ctx->cart;
        if (!$cart || !$cart->id) {
            return;
        }
        if (!self::isTvaAttestedForCart((int)$cart->id)) {
            return;
        }
        if (!self::isFranceCart($ctx)) {
            return;
        }

        // Récupère la liste des produits, quelle que soit la nature (array ou CartLazyArray)
        if (is_array($presentedCart)) {
            if (empty($presentedCart['products'])) {
                return;
            }
            $products = $presentedCart['products'];
        } else { // CartLazyArray (ArrayAccess)
            if (!isset($presentedCart['products']) || empty($presentedCart['products'])) {
                return;
            }
            $products = $presentedCart['products'];
        }

        $calc = self::getTax10Calculator();
        if (!$calc) {
            return;
        }

        $eligible = self::getEligibleProductIdsForCart($ctx, $cart);

        foreach ($products as $idx => $prod) {
            // Convertit l’élément produit en array modifiable
            if (is_array($prod)) {
                $p = $prod;
            } elseif (is_object($prod) && method_exists($prod, 'getArrayCopy')) {
                $p = $prod->getArrayCopy();
            } else {
                $p = (array)$prod; // fallback doux pour ArrayAccess
            }

            $idProduct = (int)($p['id_product'] ?? 0);

            if (!$idProduct || !in_array($idProduct, $eligible, true)) {
                continue;
            }

            $qty = (int)($p['quantity'] ?? 1);
            /*$priceTE = isset($p['price']) ? (float)$p['price'] : 0.0;*/
            $priceTE = isset($p['price']) ? (float)str_replace(',', '.',preg_replace('/[^0-9,]/', '', $p['price'])) : 0.0;
            // Si on n’a que le TTC, approx puis correction via le calc
            if ($priceTE <= 0 && !empty($p['price_with_tax'])) {
                $priceTE = (float)$p['price_with_tax'] / 1.20;
            }

            $priceWT = (float)$calc->addTaxes($priceTE);
            $totalWT = $priceWT * $qty;
            $totalTE = $priceTE * $qty;

            // MàJ des clés courantes (unitaires + totaux)
            $p['price'] = (string)number_format($priceTE, 2, ",", " "). ' €';
            $p['unit_price'] = $priceTE;
            $p['price_with_tax'] = $priceWT;
            $p['price_wt'] = $priceWT;
            $p['unit_price_wt'] = $priceWT;
            $p['unit_price_tax_included'] = $priceWT;
            $p['unit_price_tax_excluded'] = $priceTE;

            $p['total'] = (string)number_format($totalTE, 2, ",", " "). ' €';
            $p['total_with_tax'] = $totalWT;
            $p['total_wt'] = $totalWT;

            $p['price_with_reduction'] = $priceWT;
            $p['price_with_reduction_without_tax'] = $priceTE;

            $p['price_amount'] = $priceWT;
            $p['price_wt_amount'] = $priceWT;
            $p['total_price'] = $totalWT;
            $p['total_price_wt'] = $totalWT;

            if (class_exists(\Tools::class)) {
                $p['price_with_tax_formatted'] = self::formatPrice($ctx, $priceWT);
                $p['price_wt_formatted'] = self::formatPrice($ctx, $priceWT);
                $p['total_with_tax_formatted'] = self::formatPrice($ctx, $totalWT);
                $p['total_wt_formatted'] = self::formatPrice($ctx, $totalWT);
            }

            // Réinjecte l’élément modifié
            $products[$idx] = $p;
        }

        // Écrit le tableau de produits modifié dans le container (array ou CartLazyArray)
        if (is_array($presentedCart)) {
            $presentedCart['products'] = $products;
        } else { // ArrayAccess
            $presentedCart['products'] = $products;
        }
    }

    private static function formatPrice(Context $ctx, float $amount, $currency = null): string
    {
        // devise du contexte si rien de passé
        $cur = $currency ?: ($ctx->currency ?? null);

        // Récupère le code ISO (ex: "EUR")
        $iso = 'EUR';
        if (is_object($cur) && !empty($cur->iso_code)) {
            $iso = (string)$cur->iso_code;
        }

        // PS >= 1.7.6 : Locale::formatPrice()
        if (method_exists($ctx, 'getCurrentLocale') && $ctx->getCurrentLocale()) {
            return $ctx->getCurrentLocale()->formatPrice($amount, $iso);
        }

        // Fallback legacy (évite les warnings dans vieux contextes)
        return \Tools::displayPrice($amount, $cur);
    }


    /* ============ PRIVÉ: petits utilitaires ============ */

    private static function isFranceCart(Context $ctx): bool
    {
        $cart = $ctx->cart;
        $addr = new Address((int)$cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $iso = $addr->id_country ? strtoupper(Country::getIsoById((int)$addr->id_country)) : null;
        return !$iso || $iso === 'FR';
    }

    private static function isTvaAttestedForCart(int $idCart): bool
    {
        $table = _DB_PREFIX_ . 'dealt_tva_attestation';
        $exists = Db::getInstance()->executeS("SHOW TABLES LIKE '" . pSQL($table) . "'");
        if (!$exists) return false;

        $sql = 'SELECT COUNT(*) FROM `' . bqSQL($table) . '` WHERE id_cart=' . (int)$idCart . ' AND is_validated=1';
        return (int)Db::getInstance()->getValue($sql) > 0;
    }

    private static function getServiceRowByProductId(int $serviceProductId): ?array
    {
        // 0) Récupérer la référence depuis l'objet Product (pas de JOIN SQL)
        $prod = new \Product($serviceProductId);
        $ref = trim((string)$prod->reference);
        if ($ref === '') {
            return null;
        }

        $db = \Db::getInstance();

        // 1) Colonnes existantes (optionnel, garde l'auto-détection/alias propre)
        $colsRows = $db->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'dealt_service`');
        $cols = $colsRows ? array_map(static function ($r) {
            return (string)$r['Field'];
        }, $colsRows) : [];

        $wanted = [
            'eligibility_conditions' => ['eligibility_conditions', 'egibility_conditions'],
            'customer_commercial_description' => ['customer_commercial_description'],
            'expert_requirements_description' => ['expert_requirements_description'],
            'longueur_min' => ['longueur_min'],
            'longueur_max' => ['longueur_max'],
            'reference' => ['reference'],
        ];

        $selectParts = [];
        foreach ($wanted as $alias => $candidates) {
            foreach ($candidates as $col) {
                if (in_array($col, $cols, true)) {
                    $selectParts[] = ($col === $alias)
                        ? 'ds.`' . $col . '`'
                        : 'ds.`' . $col . '` AS `' . $alias . '`';
                    break;
                }
            }
        }
        if (empty($selectParts)) {
            $selectParts[] = 'ds.*';
        }

        $q = new \DbQuery();
        $q->select(implode(', ', $selectParts));
        $q->from('dealt_service', 'ds');
        $q->where("ds.`reference` = '" . pSQL($ref) . "'");

        return $db->getRow($q) ?: null;
    }


    public static function getServiceDetails(int $serviceProductId): array
    {
        $row = self::getServiceRowByProductId($serviceProductId) ?: [];

        return [
            'eligibility_conditions' => $row['eligibility_conditions'] ?? null,
            'customer_commercial_description' => $row['customer_commercial_description'] ?? null,
            'expert_requirements_description' => $row['expert_requirements_description'] ?? null,
            'longueur_min' => isset($row['longueur_min']) ? (float)$row['longueur_min'] : null,
            'longueur_max' => isset($row['longueur_max']) ? (float)$row['longueur_max'] : null,
            'reference' => $row['reference'] ?? null,
        ];
    }

    private static function getServiceRowsForMainProduct(\Context $ctx, ?int $mainProductId = null, int $serviceParentCategoryId = 369): array
    {
        $ids = self::getServiceProductIds($ctx, $mainProductId, $serviceParentCategoryId); // renvoie int[]
        if (!$ids) return [];

        $idsIn = implode(',', array_map('intval', $ids));

        $sql = '
        SELECT 
            p.id_product,
            ds.*
        FROM ' . _DB_PREFIX_ . 'product p
        INNER JOIN ' . _DB_PREFIX_ . 'dealt_service ds ON ds.reference = p.reference
        WHERE p.id_product IN (' . $idsIn . ')
          AND p.active = 1
    ';

        return \Db::getInstance()->executeS($sql) ?: [];
    }

    public static function getServiceProductIds(\Context $ctx, ?int $mainProductId = null, int $serviceParentCategoryId = 369): array
    {
        // 1) Déduire l'id du produit principal
        $idMain = $mainProductId ?: self::resolveMainProductId($ctx);
        if (!$idMain) {
            return [];
        }

        // 2) Catégories du produit dont le parent = catégorie "Service"
        $serviceCatIds = self::getDirectServiceCategoriesForProduct($idMain, $serviceParentCategoryId);
        if (empty($serviceCatIds)) {
            return [];
        }

        // 3) Pour chaque catégorie, récupérer le produit "service" (réf. présente dans ps_dealt_service)
        $db = \Db::getInstance();
        $serviceProductIds = [];

        foreach ($serviceCatIds as $catId) {
            $sql = '
                SELECT p.id_product
                FROM ' . _DB_PREFIX_ . 'category_product cp
                INNER JOIN ' . _DB_PREFIX_ . 'product p ON p.id_product = cp.id_product
                INNER JOIN ' . _DB_PREFIX_ . 'dealt_service ds ON ds.reference = p.reference
                WHERE cp.id_category = ' . (int)$catId . '
                  AND p.active = 1
            ';

            $pid = array_map(fn($p) => (int)$p['id_product'], $db->executeS($sql));

            if ($pid > 0) {
                $serviceProductIds = array_merge($serviceProductIds, $pid);
            }
        }
        // 4) Nettoyage des doublons / reindex
        $serviceProductIds = array_values(array_unique($serviceProductIds));

        return $serviceProductIds;
    }

    /**
     * Renvoie les IDs des catégories du produit $idProduct qui sont des "catégories service",
     * c.-à-d. dont le parent = $serviceParentCategoryId (ex: 369).
     *
     * @param int $idProduct
     * @param int $serviceParentCategoryId
     * @return int[]
     */
    private static function getDirectServiceCategoriesForProduct(int $idProduct, int $serviceParentCategoryId): array
    {
        $db = \Db::getInstance();

        $sql = '
            SELECT cp.id_category
            FROM ' . _DB_PREFIX_ . 'category_product cp
            INNER JOIN ' . _DB_PREFIX_ . 'category c ON c.id_category = cp.id_category
            WHERE cp.id_product = ' . (int)$idProduct . '
              AND c.id_parent = ' . (int)$serviceParentCategoryId . '
        ';
        $rows = $db->executeS($sql);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', array_column($rows, 'id_category'))));
    }

    // --- selon ton code existant ---
    private static function resolveMainProductId(\Context $ctx): int
    {
        // Adapte à ta manière habituelle d’obtenir l’ID produit sur la page
        $p = (int)\Tools::getValue('id_product');
        if ($p > 0) {
            return $p;
        }
        if (!empty($ctx->controller->product) && isset($ctx->controller->product->id)) {
            return (int)$ctx->controller->product->id;
        }
        return 0;
    }

    /** True si la référence est un service déclaré dans ps_dealt_service */
    private static function isServiceReference(string $reference): bool
    {
        if ($reference === '') {
            return false;
        }
        $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'dealt_service`
            WHERE `reference` = \'' . pSQL($reference) . '\'';
        return (bool)\Db::getInstance()->getValue($sql);
    }

    /**
     * Retourne les IDs de catégories “type de service” associées au produit :
     * intersection entre catégories du produit et enfants DIRECTS de SERVICES (id 369)
     */
    private static function getServiceTypeCategoriesForProduct($idProduct)
    {
        $idProduct = (int)$idProduct;
        $parentId = (int)self::SERVICES_PARENT_CATEGORY_ID;

        // Catégories du produit
        $rowsProd = \Db::getInstance()->executeS(
            'SELECT id_category FROM `' . _DB_PREFIX_ . 'category_product` WHERE id_product=' . $idProduct
        );
        if (!is_array($rowsProd) || !$rowsProd) {
            return array();
        }
        $prodCatIds = array_map('intval', array_column($rowsProd, 'id_category'));

        // Toute la descendance de la catégorie "Service" (aucun filtre active/shop)
        $rowsDesc = \Db::getInstance()->executeS('
        SELECT c.id_category
        FROM `' . _DB_PREFIX_ . 'category` c
        INNER JOIN `' . _DB_PREFIX_ . 'category` parent ON parent.id_category = ' . $parentId . '
        WHERE c.nleft > parent.nleft AND c.nright < parent.nright
    ');
        if (!is_array($rowsDesc) || !$rowsDesc) {
            return array();
        }
        $serviceCatIds = array_map('intval', array_column($rowsDesc, 'id_category'));

        // Intersection produit ∩ descendance(369)
        return array_values(array_intersect($prodCatIds, $serviceCatIds));
    }


    private static function getEligibleProductIdsForCart(Context $ctx, \Cart $cart): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $eligible = [];
        $serviceIds = self::getServiceProductIds($ctx); // tableau
        if ($serviceIds) {
            $eligible = array_values(array_unique($serviceIds));
        }

        $mainId = null;
        foreach ($cart->getProducts() as $p) {
            $pid = (int)$p['id_product'];
            if (!in_array($pid, $serviceIds, true)) { // pas un service => c’est le “main”
                $mainId = $pid;
                break;
            }
        }
        if ($mainId) $eligible[] = $mainId;

        return $cache = $eligible;
    }


    private static function ensureTva10TaxId(): int
    {
        $idTax = (int)Db::getInstance()->getValue('
            SELECT t.id_tax FROM ' . _DB_PREFIX_ . 'tax t
            WHERE t.active=1 AND ABS(t.rate-10.0)<0.0001
            ORDER BY t.id_tax ASC
        ');
        if ($idTax) return $idTax;

        $tax = new Tax();
        $tax->name = [];
        foreach (Language::getLanguages(false) as $lang) {
            $tax->name[(int)$lang['id_lang']] = 'TVA 10% (Dealt)';
        }
        $tax->rate = 10.0;
        $tax->active = 1;
        if (!$tax->add()) return 0;

        $grp = new TaxRulesGroup();
        $grp->name = 'TVA 10% FR (Dealt)';
        $grp->active = 1;
        if ($grp->add()) {
            $rule = new TaxRule();
            $rule->id_tax_rules_group = (int)$grp->id;
            $rule->id_country = (int)Country::getByIso('FR');
            $rule->id_state = 0;
            $rule->zipcode_from = 0;
            $rule->zipcode_to = 0;
            $rule->id_tax = (int)$tax->id;
            $rule->behavior = 0;
            $rule->save();
        }
        return (int)$tax->id;
    }

    private static function getTax10Calculator(): ?TaxCalculator
    {
        $idTax10 = (int)Configuration::get('DEALT_TAX10_ID');
        if ($idTax10 <= 0) {
            $idTax10 = self::ensureTva10TaxId();
            if ($idTax10 > 0) Configuration::updateValue('DEALT_TAX10_ID', $idTax10);
        }
        if ($idTax10 <= 0) return null;
        return new TaxCalculator([new Tax($idTax10)]);
    }


    /** Retourne le ref offerId :
     *  - override GET ?offer_id= , sinon dynamique depuis le produit service déduit
     *  sinon false;
     */

    public static function getOfferId(Context $ctx, ?int $mainProductId = null): string
    {
        $fromGet = Tools::getValue('offer_id');
        if ($fromGet) {
            return (string)$fromGet;
        }

        $serviceIds = self::getServiceProductIds($ctx, $mainProductId);
        $idService = (int)($serviceIds[0] ?? 0); // <-- prendre le 1er

        if ($idService > 0) {
            $p = new Product($idService, false, (int)$ctx->language->id);
            if (!empty($p->reference)) {
                return (string)$p->reference;
            }
        }
        return '';
    }

    public static function getStoreId(): string
    {
        $default = '6846de0f80a710f6d4b75296';
        return (string)Tools::getValue('store_id', $default);
    }

    public static function getServiceProductIdsByOffer(string $offerId): int
    {
        return (int)Product::getIdByReference($offerId);
    }

    public static function getServiceUrl(): string
    {
        return (string)Configuration::get('DEALT_SERVICE_URL');
    }

    public static function getEligibilityUrl(Context $ctx): string
    {
        return $ctx->link->getModuleLink('dealt', 'testapi', ['action' => 'availability'], true);
    }

    public static function buildTvaToken(Context $ctx): string
    {
        $cartId = (string)($ctx->cart ? $ctx->cart->id : 0);
        $custId = (string)($ctx->customer ? $ctx->customer->id : 0);
        return substr(sha1($cartId . '|' . $custId . '|' . _COOKIE_KEY_), 0, 16);
    }

    public static function buildProductAdditionalInfoData(Context $ctx, int $idProduct, $longueur = null): array
    {
        $offerId = self::getOfferId($ctx, $idProduct);

        $serviceIds = self::getServiceProductIds($ctx, $idProduct);

        $idServices = [];

        if(!empty($longueur) && count($serviceIds)){
            foreach ($serviceIds as $serviceId) {
                $checkIncludesLength = Db::getInstance()->getValue('SELECT d.includes_length FROM ' . _DB_PREFIX_ . 'dealt_service d
                LEFT JOIN ' . _DB_PREFIX_ . 'product p ON p.reference = d.reference 
                WHERE p.id_product = ' . $serviceId);

                if((int)$checkIncludesLength){
                    $serviceIdSql = Db::getInstance()->executeS('SELECT p.id_product FROM ' . _DB_PREFIX_ . 'product p
                    LEFT JOIN ' . _DB_PREFIX_ . 'dealt_service ds ON ds.reference = p.reference
                    WHERE '.$longueur.' >= ds.longueur_min AND '.$longueur.' <= ds.longueur_max 
                    AND p.id_product = '.$serviceId);
                } else {
                    $serviceIdSql = Db::getInstance()->executeS('SELECT p.id_product FROM ' . _DB_PREFIX_ . 'product p
                    LEFT JOIN ' . _DB_PREFIX_ . 'dealt_service ds ON ds.reference = p.reference
                    WHERE p.id_product = '.$serviceId);
                }

                if(!empty($serviceIdSql[0]['id_product'])){
                    $idService = $serviceIdSql[0]['id_product'];
                    $idServices[] = $idService;
                }
            }
        } else {
            $idService = (int)($serviceIds[0] ?? 0);
            $idServices = $serviceIds;
        }
        $data = [];

        if((count($idServices) && !empty($longueur)) OR count($idServices) == 1){
            foreach ($idServices as $idService) {
                /*            $product = new \Product($idProduct, false, (int)$ctx->language->id);*/
                $product = new \Product($idService, false, (int)$ctx->language->id);

                $servicePriceTtc = null;
                if ($idService) {
                    $priceTtc = \Product::getPriceStatic($idService, true);
                    if ($priceTtc > 0) {
                        $servicePriceTtc = self::formatPrice($ctx, $priceTtc);
                    }
                }

                $serviceRow = $idService ? self::getServiceRowByProductId($idService) : [];

                $data[] = [
                    'dealt_service_title' => $product->name ?: \Configuration::get('DEALT_SERVICE_TITLE_DEFAULT'),
                    'dealt_service_price' => $servicePriceTtc ?: '939 €',
                    'dealt_service_url' => self::getServiceUrl(),
                    'dealt_product_id' => $idProduct,
                    'dealt_service_product_id' => $idService,
                    /*                'dealt_offer_id' => $offerId,*/
                    'dealt_offer_id' => $serviceRow['reference'],
                    'dealt_store_id' => self::getStoreId(),
                    'dealt_check_availability_url' => self::getEligibilityUrl($ctx),
                    'dealt_eligibility_conditions' => self::eligibility_conditions_format($serviceRow['eligibility_conditions'] ?? null),
                    'dealt_customer_commercial_description' => self::replace_p_to_li($serviceRow['customer_commercial_description'] ?? ''),
                    'dealt_expert_requirements_description' => self::replace_p_to_li($serviceRow['expert_requirements_description'] ?? ''),
                    'dealt_longueur_min' => isset($serviceRow['longueur_min']) ? (float)$serviceRow['longueur_min'] : null,
                    'dealt_longueur_max' => isset($serviceRow['longueur_max']) ? (float)$serviceRow['longueur_max'] : null,
                ];
            }
        }

        \Media::addJsDef(['dealt_service_info'=>$data]);
        return $data;
    }

    public static function eligibility_conditions_format($string)
    {
        if (is_string($string)) {
            $decoded = json_decode($string, true);
            $items = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [$string];
        } else {
            $items = $string;
        }

        // Nettoie, échappe et génère les <li>
        $items = array_filter(array_map('trim', $items), fn($s) => $s !== '');
        $lis = array_map(
            fn($s) => '<li>' . htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>',
            $items
        );

        return implode('', $lis);
    }

    public static function replace_p_to_li($string)
    {
        $string = str_replace('<p>', '<li>', $string);
        return str_replace('</p>', '</li>', $string);
    }


    /** Données pour displayShoppingCartFooter (panier) */
    public static function buildCartFooterData(Context $ctx): array
    {
        $cart   = $ctx->cart;
        $idLang = (int)$ctx->language->id;

        if (!$cart || !$cart->id) {
            return [
                'dealt_cart_services' => [],
                'dealt_tva_enabled'   => false,
            ];
        }

        // Services déjà dans le panier (pour ne pas les re-proposer)
        $serviceIdsInCart = [];
        foreach ($cart->getProducts() as $row) {
            $pid  = (int)$row['id_product'];
            $prod = new \Product($pid, false, $idLang);
            if (!empty($prod->reference) && self::isServiceReference((string)$prod->reference)) {
                $serviceIdsInCart[$pid] = true;
            }
        }

        $services = [];

        $checkIfPack = false;
        $idPack = null;
        $idPackMainProduct = null;

        /*        dump(Context::getContext()->cookie);
                dump(Context::getContext()->cart->id);*/

        if(isset(Context::getContext()->cookie->cart_packs)){
            $cookiePack = json_decode(Context::getContext()->cookie->cart_packs);
            if(!empty($cookiePack->id_cart) && !empty($cookiePack->id_pack)){
                if((int)$cookiePack->id_cart == (int)$cart->id){
                    $checkIfPack = self::checkIfPack($cookiePack->id_pack, $cart);
                    if($checkIfPack){
                        $idPack = $cookiePack->id_pack;
                        $idPackMainProduct = Db::getInstance()->executeS('SELECT pap.id_product, pap.default_id_product_attribute FROM `'._DB_PREFIX_.'pm_advancedpack_products` pap WHERE pap.id_pack = '.(int)$idPack.' AND pap.position = 0');
                    }
                }
            }
        }

        // Pour chaque produit non-service du panier, proposer ses services liés
        foreach ($cart->getProducts() as $row) {
            $mainId = (int)$row['id_product'];
            $main   = new \Product($mainId, false, $idLang);
            $lengthPerUnit = null;


            // skip si c'est déjà un produit "service"
            if (!empty($main->reference) && self::isServiceReference((string)$main->reference)) {
                continue;
            }


            $combinations = [];

            if($checkIfPack && !empty($idPackMainProduct[0]['id_product']) && $mainId == $idPackMainProduct[0]['id_product']){
                if(!empty($idPackMainProduct[0]['default_id_product_attribute'])){
                    $combinations = self::getAttributesInformationsByProduct($idPackMainProduct[0]['id_product'], $idPackMainProduct[0]['default_id_product_attribute']);
                }
            } else {
                if(!empty($row['id_product_attribute'])){
                    $combinations = self::getAttributesInformationsByProduct($mainId, (int)$row['id_product_attribute']);
                }
            }

            if(count($combinations)){
                foreach($combinations as $combination){
                    if($combination['group'] == 'Longueur du Kit' OR $combination['group'] == 'Longueur' OR $combination['group'] == 'longueur'){
                        if(preg_match('/cm/', $combination['attribute'])){
                            $lengthPerUnit = floatval(str_replace([' cm', 'cm'], ['', '.'], $combination['attribute'])) / 100;
                        } else {
                            $lengthPerUnit = floatval(str_replace([' ml', ' m', 'ml', 'm'], ['', '', '.', '.'], $combination['attribute']));
                        }

                        break;
                    }
                }
            }

            if(empty($lengthPerUnit)){
                if($main->width != 0){
                    $lengthPerUnit = $main->width / 100;
                    $length = $lengthPerUnit * (int)$row['quantity'];
                } else {
                    $features = Product::getFrontFeaturesStatic(Context::getContext()->language->id, (int)$row['id_product']);
                    foreach ($features as $feature){
                        if($feature['name'] == 'Longueur'){
                            if(preg_match('/mm/', $feature['value']) OR preg_match('/MM/', $feature['value'])){
                                $lengthInt = (int)$feature['value'];
                                $lengthPerUnit = $lengthInt / 1000;
                            } else if(preg_match('/ml/', $feature['value'])){
                                $lengthPerUnit = floatval(preg_replace('/[ml]/', '.', $feature['value']).'0');
                            } else if(preg_match('/ML/', $feature['value'])){
                                $lengthPerUnit = floatval(preg_replace('/[ML]/', '.', $feature['value']).'0');
                            } else if(preg_match('/m/', $feature['value']) OR preg_match('/M/', $feature['value'])){
                                $lengthPerUnit = floatval(preg_replace('/[mM]/', '.', $feature['value']).'0');
                            } else if(preg_match('/cm/', $feature['value']) OR preg_match('/CM/', $feature['value'])){
                                $lengthInt = (int)$feature['value'];
                                $lengthPerUnit = $lengthInt / 100;
                            } else {
                                $lengthPerUnit = (int)$feature['value'];
                            }
                            break;
                        }
                    }
                    if(!empty($lengthPerUnit)){
                        $length = $lengthPerUnit * (int)$row['quantity'];
                    } else {
                        $length = 0;
                    }
                }
            } else {
                $length = $lengthPerUnit * (int)$row['quantity'];
            }

            // Récupère les ids services via les catégories "enfants de 369"
            if($checkIfPack && !empty($idPackMainProduct[0]['id_product']) && $mainId == $idPackMainProduct[0]['id_product'] && !empty($idPack)){
                $serviceIds = self::getServiceProductIds($ctx, $idPack);
            } else {
                $serviceIds = self::getServiceProductIds($ctx, $mainId);
            }

            foreach ($serviceIds as $sid) {
                $srv = new \Product($sid, false, $idLang);
                $priceTtc = \Product::getPriceStatic($sid, true);
                $serviceRow = self::getServiceRowByProductId($sid) ?: [];

                $checkIncludesLength = Db::getInstance()->getValue('SELECT d.includes_length FROM ' . _DB_PREFIX_ . 'dealt_service d
                LEFT JOIN ' . _DB_PREFIX_ . 'product p ON p.reference = d.reference 
                WHERE p.id_product = ' . $sid);

                if($length <= $serviceRow['longueur_max'] && $length >= $serviceRow['longueur_min'] OR !(int)$checkIncludesLength){
                    $services[] = [
                        // Contexte main
                        'main_product_id'   => $checkIfPack && !empty($idPackMainProduct[0]['id_product']) && $mainId == $idPackMainProduct[0]['id_product'] && !empty($idPack) ? $idPack : $mainId,
                        'main_product_name' => $row['name'] ?? ($main->name ?? ''),

                        // Service
                        'service_product_id' => $sid,
                        'service_title'      => $srv->name ?: 'Service de pose',
                        'service_ref'        => (string)($srv->reference ?? ''),
                        'service_price'      => $priceTtc > 0 ? self::formatPrice($ctx, $priceTtc) . ' TTC' : '',
                        'service_url'        => self::getServiceUrl(),

                        // Eligibilité
                        'offer_id'        => (string)($srv->reference ?? ''),
                        'store_id'        => self::getStoreId(),
                        'eligibility_url' => self::getEligibilityUrl($ctx),

                        // Déjà présent ?
                        'already_in_cart' => isset($serviceIdsInCart[$sid]),

                        // (optionnel) contenus BDD si tu veux les afficher dans le panier
                        'eligibility_conditions'           => $serviceRow['eligibility_conditions'] ?? null,
                        'customer_commercial_description'  => $serviceRow['customer_commercial_description'] ?? null,
                        'expert_requirements_description'  => $serviceRow['expert_requirements_description'] ?? null,
                        'longueur_max' => $serviceRow['longueur_max'] ?? null,
                        'longueur_min' => $serviceRow['longueur_min'] ?? null,
                    ];
                }
            }


        }

        $data = [
            'dealt_cart_services'  => $services,
            'dealt_tva_enabled'    => self::isTvaAttestedForCart((int)$cart->id),
            'tva_attestation_url'  => $ctx->link->getModuleLink('dealt', 'tvaattestation'),
            'tva_modal_url'        => $ctx->link->getModuleLink('dealt', 'tvaModal'),
            'dealt_toggle_tva_url' => $ctx->link->getModuleLink('dealt', 'tvaToggle'),
        ];

        \Media::addJsDef($data); // si ton JS lit ces données

        return $data;
    }

    public static function checkIfPack($id_pack,$cart){
        $ifTable = Db::getInstance()->execute('SHOW TABLES LIKE "'._DB_PREFIX_.'pm_advancedpack"');
        if(!$ifTable){
            return false;
        } else {
            $check = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'pm_advancedpack` WHERE `id_pack` = ' . (int)$id_pack . ' AND id_shop = ' . (int)Context::getContext()->shop->id);
            if(!empty($check[0])){
                $packProducts = Db::getInstance()->executeS('SELECT pap.id_product FROM `'._DB_PREFIX_.'pm_advancedpack_products` pap WHERE pap.id_pack = '.(int)$id_pack);
                $packProductsArr = array_map(fn($value) => $value['id_product'], $packProducts);
                $cartProductsArr = array_map(fn($value) => $value['id_product'], $cart->getProducts());
                $array_check = array_intersect($cartProductsArr, $packProductsArr);
                if(count($array_check) == count($packProductsArr)){
                    return true;
                }
            } else {
                return false;
            }
        }
        return false;
    }

    public static function getAttributesInformationsByProduct($id_product, $id_product_attribute)
    {
        $sql = '
            SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`, pac.`id_product_attribute`
            FROM `'._DB_PREFIX_.'attribute` a
            LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
                ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)Context::getContext()->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
                ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)Context::getContext()->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
                ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
            '.\Shop::addSqlAssociation('product_attribute', 'pa').'
            '.\Shop::addSqlAssociation('attribute', 'pac').'
            WHERE pa.`id_product` = '.(int)$id_product;

        if(!empty($id_product_attribute)){
            $sql .= ' AND pac.`id_product_attribute` = '.(int)$id_product_attribute;
        }

        $result = Db::getInstance()->executeS($sql);

        return $result;
    }

    public static function getServicesForCart($ctx, $mainId, $length, $idLang){

        $services = [];

        $length = (float)$length - 0.5;

        // Récupère les ids services via les catégories "enfants de 369"
        $serviceIds = self::getServiceProductIds($ctx, $mainId);
        foreach ($serviceIds as $sid) {
            $srv = new \Product($sid, false, $idLang);
            $priceTtc = \Product::getPriceStatic($sid, true);
            $serviceRow = self::getServiceRowByProductId($sid) ?: [];

            if($length <= $serviceRow['longueur_max'] && $length >= $serviceRow['longueur_min']){
                $services[] = [
                    // Contexte main
                    'main_product_id'   => $mainId,
                    'main_product_name' => $row['name'] ?? ($main->name ?? ''),

                    // Service
                    'service_product_id' => $sid,
                    'service_title'      => $srv->name ?: 'Service de pose',
                    'service_ref'        => (string)($srv->reference ?? ''),
                    'service_price'      => $priceTtc > 0 ? self::formatPrice($ctx, $priceTtc) . ' TTC' : '',
                    'service_url'        => self::getServiceUrl(),

                    // Eligibilité
                    'offer_id'        => (string)($srv->reference ?? ''),
                    'store_id'        => self::getStoreId(),
                    'eligibility_url' => self::getEligibilityUrl($ctx),

                    // Déjà présent ?
                    'already_in_cart' => isset($serviceIdsInCart[$sid]),

                    // (optionnel) contenus BDD si tu veux les afficher dans le panier
                    'eligibility_conditions'           => $serviceRow['eligibility_conditions'] ?? null,
                    'customer_commercial_description'  => $serviceRow['customer_commercial_description'] ?? null,
                    'expert_requirements_description'  => $serviceRow['expert_requirements_description'] ?? null,
                    'longueur_max' => $serviceRow['longueur_max'] ?? null,
                    'longueur_min' => $serviceRow['longueur_min'] ?? null,
                ];
            }
        }
        return $services;
    }
}
