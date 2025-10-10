<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckc_Autocart extends Module
{
    protected static $isProcessing = false;

    public function __construct()
    {
        $this->name = 'ckc_autocart';
        $this->tab = 'billing_invoicing';
        $this->version = '2.0.0';
        $this->author = 'CKC-Net';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ajout auto de produits au panier');
        $this->description = $this->l('Ajoute automatiquement certains produits au panier.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionCartUpdateQuantityBefore')
            && $this->registerHook('actionCartSave');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /** Hook principal : appelé après updateQty (ajout/retrait) */
    public function hookActionCartUpdateQuantityBefore( $params )
    {
        $cart = $params['cart'];
        $idProduct = (int)$params['product']->id;

        $product = [
            'id_product' => $idProduct,
            'id_product_attribute' => (int)$params['id_product_attribute'],
            'quantity' => (int)$params['quantity'],
            'customizable' => (int)$params['product']->customizable,
        ];

        $this->processRules($cart, $product, $params['operator']);
    }

    public function hookActionCartSave( $params )
    {
//        if (self::$isProcessing) {
//            return;
//        }
//        $cart = $this->context->cart;
//        if (!Validate::isLoadedObject($cart)) {
//            return;
//        }
//        $this->processRules($cart);
    }

    protected function processRules( Cart $cart, $product = [], $operator = null )
    {
        if (self::$isProcessing) {
            return;
        }
        self::$isProcessing = true;

        try {
            $lines = $cart->getProducts();
            $productsInCart = array_column($lines, 'id_product');

            // 1) Produits catégorie 93 (grillages rigides) → ajouter bombe de peinture (même couleur si possible)
            $CATEGORY_GRILLAGE = 93;
            $idBombePeinture = 314; // ID produit bombe de peinture

            // 2) Produits catégorie 95 (chevilles) → ajouter caches goujons (x4 par produit)
            $CATEGORY_CHEVILLES = 95;
            $idCachesGoujons = 422;
            $multiplierCaches = 4;

            // 3) Portails coulissant (catégorie 241) + moteur CARDIN (1305 ou 1306) → crémaillères (ID 1130)
            $CATEGORY_PORTAIL = 241;
            $MOTEURS_CARDIN = [1305, 1306];
            $idCremailere = 1130;

            if ((int)$product['id_product'] === (int)$idBombePeinture && $operator === 'down') {
                return;
            }

            if (sizeof($lines) === 0) {
                $lines[] = $product;
            }else{
                foreach ($lines as $key => $line) {
                    if ((int)$line['id_product'] === (int)$product['id_product'] && (int)$line['id_product_attribute'] === (int)$product['id_product_attribute']) {
                        $lines[$key]['cart_quantity'] = (int)$line['cart_quantity'] + (int)$product['quantity'];
                        break;
                    }
                }
            }

            // —— Règle 1 ——
            foreach ($lines as $line) {
                if ($this->productInCategory($line['id_product'], $CATEGORY_GRILLAGE)) {
                    if (!$this->cartHasProduct($cart, $idBombePeinture)) {
                        $combinations  = static::getAttributesInformationsByProduct($line['id_product']);
                        $color = null;
                        foreach ($combinations as $combination) {
                            $group = strtolower(str_replace([' ', ':'], ['-', ''], $combination['group']));

                            if ($group == 'couleur' && $combination['id_product_attribute'] == $line['id_product_attribute']) {
                                $color = $combination['attribute'];
                            }
                        }
                        
                        $combinationProduct  = static::getAttributesInformationsByProduct($idBombePeinture);
                        $id_product_attribute = null;
                        foreach ($combinationProduct as $combination) {
                            $group = strtolower(str_replace([' ', ':'], ['-', ''], $combination['group']));

                            if ($group == 'couleur' && $combination['attribute'] == $color) {
                                $id_product_attribute = $combination['id_product_attribute'];
                            }
                        }

                        $cart->updateQty(1, $idBombePeinture, $id_product_attribute);
                    }
                    break;
                }
            }

            // —— Règle 2 ——
            $qtyChevillesTotal = 0;
            foreach ($lines as $line) {
                if ($this->productInCategory($line['id_product'], $CATEGORY_CHEVILLES)) {
                    $qtyChevillesTotal += (int)$line['cart_quantity'];
                }
            }
            if ($qtyChevillesTotal > 0) {
                $neededCaches = $qtyChevillesTotal * $multiplierCaches;
                $qtyCaches = $this->getProductQuantityInCart($cart, $idCachesGoujons);
                if ($qtyCaches != $neededCaches) {
                    if ($qtyCaches > 0) {
                        $cart->updateQty($qtyCaches, $idCachesGoujons, null, false, 'down');
                    }
                    $cart->updateQty($neededCaches, $idCachesGoujons);
                }
            }

            // —— Règle 3 ——


            if (in_array($product['id_product'], $MOTEURS_CARDIN)) {
                $haveCoulissant = false;
                $passageMetres = null;

                foreach ($lines as $line) {
                    if ($this->productInCategory($line['id_product'], $CATEGORY_PORTAIL)) {
                        $haveCoulissant = true;
                        if (isset($line['id_customization']) && $line['id_customization']) {
                            $customization = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'customized_data WHERE id_customization = '.$line['id_customization']);

                            if ($customization && isset($customization['value']) && $customization['value']) {
                                $data = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'dynamicproduct_input_field WHERE id_input = '.$customization['value'].' AND name = "largeur_sur_mesure"');
                                if ($data && $data['value']) {
                                    $passageMetres = (float) (((int) $data['value']) / 1000);
                                }
                            }
                        } else {
                            $combinations  = static::getAttributesInformationsByProduct($line['id_product']);
                            foreach ($combinations as $combination) {
                                $group = strtolower(str_replace([' ', ':'], ['-', ''], $combination['group']));

                                if (($group == 'passage' || $group == 'largeur') && $combination['id_product_attribute'] == $line['id_product_attribute']) {
                                    $passageMetres = (float) str_replace(['m', 'M'], '.', $combination['attribute']);
                                }else if ($group == 'largeur-du-portail/largeur-de-passage' && $combination['id_product_attribute'] == $line['id_product_attribute']) {
                                    $passageMetres = (float) explode('/', str_replace(['m', 'M'], '.', $combination['attribute']))[1];
                                }
                            }
                        }
                    }
                }

                if ($haveCoulissant && $passageMetres) {
                    $needed = (int)ceil($passageMetres + 1);
                    $qtyCrem = $this->getProductQuantityInCart($cart, $idCremailere);
                    if ($qtyCrem != $needed) {
                        if ($qtyCrem > 0) {
                            $cart->updateQty($qtyCrem, $idCremailere, 11768829, false, 'down');
                        }
                        $cart->updateQty($needed, $idCremailere, 11768829);
                    }
                }
            }
        } finally {
            self::$isProcessing = false;
        }
    }

    protected function productInCategory( $idProduct, $idCategory )
    {
        $cats = Product::getProductCategories((int)$idProduct);
        return in_array((int)$idCategory, $cats ?: []);
    }

    protected function cartHasProduct( Cart $cart, $idProduct )
    {
        foreach ($cart->getProducts() as $p) {
            if ((int)$p['id_product'] === (int)$idProduct && (int)$p['cart_quantity'] > 0) {
                return true;
            }
        }
        return false;
    }

    protected function getProductQuantityInCart( Cart $cart, $idProduct )
    {
        foreach ($cart->getProducts() as $p) {
            if ((int)$p['id_product'] === (int)$idProduct) {
                return (int)$p['cart_quantity'];
            }
        }
        return 0;
    }
    
    public static function getAttributesInformationsByProduct($id_product)
    {
        $result = \Db::getInstance()->executeS('
            SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`, pac.`id_product_attribute`
            FROM `'._DB_PREFIX_.'attribute` a
            LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
                ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)\Context::getContext()->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
                ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)\Context::getContext()->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
                ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
            '.\Shop::addSqlAssociation('product_attribute', 'pa').'
            '.\Shop::addSqlAssociation('attribute', 'pac').'
            WHERE pa.`id_product` = '.(int)$id_product);
    
        return $result;
    }
}
