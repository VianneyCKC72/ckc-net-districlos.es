<?php
/**
 * Advanced Pack 5
 *
 * @author    Presta-Module.com <support@presta-module.com> - https://www.presta-module.com
 * @copyright Presta-Module - https://www.presta-module.com
 * @license   see file: LICENSE.txt
 *
 *           ____     __  __
 *          |  _ \   |  \/  |
 *          | |_) |  | |\/| |
 *          |  __/   | |  | |
 *          |_|      |_|  |_|
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
class pm_advancedpackadd_packModuleFrontController extends ModuleFrontController
{
    protected $idPack;
    protected $quantity;
    protected $idProductAttributeList = [];
    protected $productPackExclude = [];
    protected $productPackQuantityList = [];
    protected $productPackCustomizationList = [];
    protected $packHasEditedQuantity = false;
    public $ajax = true;
    public $display_header = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $display_column_right = false;
    protected $pack_ml = null;
    protected $ml = 0;

    public function init()
    {
        parent::init();
        header('X-Robots-Tag: noindex, nofollow', true);
        $this->ajax = true;
        $this->idPack = (int)Tools::getValue('id_pack');
        $this->quantity = (int)abs(Tools::getValue('qty', 1));
        if ($this->quantity <= 0) {
            $this->quantity = 1;
        }
        $ap5RawIdPal = Tools::getValue('id_product_attribute_list');

        $idProductAttributeList = $ap5RawIdPal;
        if (is_string($idProductAttributeList)) {
            $idProductAttributeList = json_decode($idProductAttributeList);
        }
        $idProductAttributeList = (array) $idProductAttributeList;
        if (!is_array($idProductAttributeList)) {
            $this->idProductAttributeList = [];
        } else {
            foreach ($idProductAttributeList as $idProductPack => $idProductAttribute) {
                if (empty($idProductPack) || empty($idProductAttribute) || !is_numeric($idProductPack) || !is_numeric($idProductAttribute)) {
                    continue;
                } else {
                    $this->idProductAttributeList[(int)$idProductPack] = (int)$idProductAttribute;
                }
            }
        }

        // Fallback runtime (qty>1): si le champ id_product_attribute_list n'est pas envoyé,
        // on reconstruit les déclinaisons via les champs group_<id_product>_<id_attribute_group>.
        if (!count($this->idProductAttributeList)) {
            $byProductId = [];
            foreach ($_POST as $k => $v) {
                if (!is_string($k) || strpos($k, 'group_') !== 0) {
                    continue;
                }
                // format attendu: group_<id_product>_<id_attribute_group>
                $parts = explode('_', $k);
                if (count($parts) < 3) {
                    continue;
                }
                $idProduct = (int) $parts[1];
                $idAttribute = (int) $v;
                if ($idProduct > 0 && $idAttribute > 0) {
                    $byProductId[$idProduct][] = $idAttribute;
                }
            }

            if (count($byProductId)) {
                $packProducts = AdvancedPack::getPackContent($this->idPack);
                $productIdToPackRow = [];
                if (is_array($packProducts)) {
                    foreach ($packProducts as $pp) {
                        if (!empty($pp['id_product']) && !empty($pp['id_product_pack'])) {
                            $productIdToPackRow[(int) $pp['id_product']] = (int) $pp['id_product_pack'];
                        }
                    }
                }

                foreach ($byProductId as $idProduct => $attrIds) {
                    $idProductPackRow = $productIdToPackRow[(int) $idProduct] ?? null;
                    if (!$idProductPackRow) {
                        continue;
                    }
                    // Résoudre la combinaison depuis la liste d'attributs
                    $idProductAttribute = (int) Product::getIdProductAttributeByIdAttributes((int) $idProduct, $attrIds, true);
                    if ($idProductAttribute > 0) {
                        $this->idProductAttributeList[(int) $idProductPackRow] = $idProductAttribute;
                    }
                }
            }
        }
        unset($idProductAttributeList);
        if (Tools::getIsset('productPackExclude')) {
            $this->productPackExclude = array_unique(array_map('intval', (array)Tools::getValue('productPackExclude')));
        }
        if (Tools::getIsset('quantity_ml') && (int)Tools::getValue('quantity_ml') > 0) {
            $this->pack_ml = true;
            $this->ml = (int)Tools::getValue('quantity_ml');
        }

        if (Tools::getIsset('productPackQuantityList')) {
            $tmp_productPackQuantityList = (array)Tools::getValue('productPackQuantityList');
            if (is_array($tmp_productPackQuantityList) && count($tmp_productPackQuantityList)) {
                $packProducts = AdvancedPack::getPackContent($this->idPack, null, false, [], $this->productPackQuantityList);
                foreach ($tmp_productPackQuantityList as $packChoiceRow) {
                    if (!is_numeric($packChoiceRow['quantity']) || (int)$packChoiceRow['quantity'] <= 0) {
                        $packChoiceRow['quantity'] = 1;
                    }
                    $this->productPackQuantityList[(int)$packChoiceRow['idProductPack']] = (int)$packChoiceRow['quantity'];
                }

                foreach ($packProducts as $packProduct) {
                    if ($this->pack_ml) {
                        $formula = $packProduct['formula'];
                        if ($formula) {
                            $quantity = (int)$packProduct['quantity'];
                            $ml = $this->ml;
                            $formula = str_replace('[ML]', $ml, $formula);
                            $formula = str_replace('[QUANTITY]', $quantity, $formula);
                            $newQuantity = AdvancedPack::evaluateMathExpression($formula);
                            $this->productPackQuantityList[(int)$packProduct['id_product_pack']] = (int)$newQuantity;
                        }
                    }
                    if (isset($this->productPackQuantityList[(int)$packProduct['id_product_pack']]) && $this->productPackQuantityList[(int)$packProduct['id_product_pack']] != (int)$packProduct['quantity']) {
                        $this->packHasEditedQuantity = true;
                        break;
                    }
                }
            }
        }

        if ($this->pack_ml) {
            $packProducts = AdvancedPack::getPackContent($this->idPack, null, false, [], $this->productPackQuantityList);
            foreach ($packProducts as $packProduct) {
                $formula = $packProduct['formula'];
                if ($formula) {
                    $quantity = (int)$packProduct['quantity'];
                    $ml = $this->ml;
                    $formula = str_replace('[ML]', $ml, $formula);
                    $formula = str_replace('[QUANTITY]', $quantity, $formula);
                    $newQuantity = AdvancedPack::evaluateMathExpression($formula);
                    $this->productPackQuantityList[(int)$packProduct['id_product_pack']] = (int)$newQuantity;
                    $this->packHasEditedQuantity = true;
                }else{
                    $this->productPackQuantityList[(int)$packProduct['id_product_pack']] = (int)$packProduct['quantity'];
                }
            }
        }

        if (Tools::getIsset('productPackCustomizationList')) {
            $tmp_productPackCustomizationList = (array)Tools::getValue('productPackCustomizationList');
            if (is_array($tmp_productPackCustomizationList) && count($tmp_productPackCustomizationList)) {
                $packProducts = AdvancedPack::getPackContent($this->idPack);
                foreach ($tmp_productPackCustomizationList as $packChoiceRow) {
                    if (!in_array((int)$packChoiceRow['idProductPack'], $this->productPackExclude)) {
                        $this->productPackCustomizationList[(int)$packChoiceRow['idProductPack']][(int)$packChoiceRow['idCustomizationField']] = $packChoiceRow['value'];
                    }
                }
            }
        }
        if (!$this->context->cart->id) {
            if (Context::getContext()->cookie->id_guest) {
                $guest = new Guest(Context::getContext()->cookie->id_guest);
                $this->context->cart->mobile_theme = $guest->mobile_theme;
            }
            $this->context->cart->id_currency = 1;
            $this->context->cart->add();
            if ($this->context->cart->id) {
                $this->context->cookie->id_cart = (int)$this->context->cart->id;
            }
        }
    }
    public function postProcess()
    {
        if (!$this->isTokenValid()) {
            Tools::redirect('index.php');
        }
    }
    public function displayAjax()
    {

        AdvancedPack::addExplodedPackToCart($this->idPack, $this->quantity, $this->idProductAttributeList, $this->productPackCustomizationList, $this->productPackQuantityList, $this->productPackExclude);

        // Ajouter les accessoires Multi Accessories Pro (HSMA)
        $idAccessories = trim(Tools::getValue('id_accessories', ''));
        $idAccessoriesAttributes = trim(Tools::getValue('id_accesories_attributes', ''));
        $customQty = trim(Tools::getValue('custom_qty', ''));

        if ($idAccessories !== '' && $idAccessories !== false) {
            $ids = array_map('intval', array_filter(explode(',', $idAccessories)));
            $attrs = $idAccessoriesAttributes !== '' ? array_map('intval', array_filter(explode(',', $idAccessoriesAttributes))) : array_fill(0, count($ids), 0);
            $qtys = $customQty !== '' ? array_map('intval', array_filter(explode(',', $customQty))) : array_fill(0, count($ids), 1);
            foreach ($ids as $i => $idProduct) {
                if ($idProduct <= 0) {
                    continue;
                }
                $idProductAttribute = isset($attrs[$i]) ? $attrs[$i] : 0;
                $quantity = isset($qtys[$i]) && $qtys[$i] > 0 ? $qtys[$i] : 1;
                $this->context->cart->updateQty($quantity, $idProduct, $idProductAttribute, null, 'up');
            }
        }
    }
    public function initContent()
    {
    }
}
