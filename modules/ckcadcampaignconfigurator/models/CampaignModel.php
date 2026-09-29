<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}


class CampaignModel extends ObjectModel
{
    public $id;
    public $title;
    public $id_client_group;
    public $active;
    public $logo_path;
    public $date_start;
    public $date_end;
    public $date_add;

    public static $definition = [
        'table' => 'ckccampaign_config_list',
        'primary' => 'id',
        'fields' => [
            'title' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'id_client_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'logo_path' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false],
            'date_start' => ['type' => self::TYPE_DATE, 'required' => true],
            'date_end' => ['type' => self::TYPE_DATE, 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'required' => true],
        ],
    ];

    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);
    }

    public function getCampaignInfo(&$id){

        $query = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckccampaign_config_list ccl
        WHERE ccl.id = '.$id);

        $queryMenu = Db::getInstance()->executeS('SELECT ccm.id as idMenu, ccm.img_path as imgMenuPath, ccm.active as menuActive, ccm.link as menuLink FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_menu ccm
        WHERE ccm.id_campaign = '.$id);

        $queryHeader = Db::getInstance()->executeS('SELECT cch.id as idHeader, cch.active as headerActive, cch.text as headerText, cch.background_color as headerColor FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_header cch
        WHERE cch.id_campaign = '.$id);

        /*        $queryCategory = Db::getInstance()->executeS('SELECT ccc.id as idCategory, ccc.text as categoryText, ccc.background_color as categoryColor, ccc.active as categoryActive, ccc.show_all as categoryShowAll
                FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_category ccc
                WHERE ccc.id_campaign = '.$id);*/

        $queryDevis = Db::getInstance()->executeS('SELECT ccd.id as idDevis, ccd.text as textDevis, ccd.background_color as colorDevis FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_devis ccd
        WHERE ccd.id_campaign = '.$id);

        $queryPromoPage = Db::getInstance()->executeS('SELECT ccpp.id as idPromoPage, ccpp.text as promoPageText FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_promopage ccpp
        WHERE ccpp.id_campaign = '.$id);

        $info = $query[0];

        if(null != $queryMenu) {
            $info["idMenu"] = $queryMenu[0]["idMenu"];
            $info["imgMenuPath"] = $queryMenu[0]["imgMenuPath"];
            $info["menuLink"] = $queryMenu[0]["menuLink"];
            $info["menuActive"] = $queryMenu[0]["menuActive"];
        } else {
            $info["idMenu"] = "";
            $info["imgMenuPath"] = "";
            $info["menuActive"] = "";
            $info["menuLink"] = "";
        }

        if(null != $queryHeader) {
            $info["idHeader"] = $queryHeader[0]["idHeader"];
            $info["headerActive"] = $queryHeader[0]["headerActive"];
            $info["headerText"] = $queryHeader[0]["headerText"];
            $info["headerColor"] = $queryHeader[0]["headerColor"];
        } else {
            $info["idHeader"] = "";
            $info["headerActive"] = "";
            $info["headerText"] = "";
            $info["headerColor"] = "";
        }

        /*        if(null != $queryCategory){
                    $info["idCategory"] = $queryCategory[0]["idCategory"];
                    $info["categoryText"] = $queryCategory[0]["categoryText"];
                    $info["categoryColor"] = $queryCategory[0]["categoryColor"];
                    $info["categoryActive"] = $queryCategory[0]["categoryActive"];
                    $info["categoryShowAll"] = $queryCategory[0]["categoryShowAll"];
                } else {
                    $info["idCategory"] = "";
                    $info["categoryText"] = "";
                    $info["categoryColor"] = "";
                    $info["categoryActive"] = "";
                    $info["categoryShowAll"] = "";
                }*/

        if(null != $queryDevis){
            $info["idDevis"] = $queryDevis[0]["idDevis"];
            $info["textDevis"] = $queryDevis[0]["textDevis"];
            $info["colorDevis"] = $queryDevis[0]["colorDevis"];
        } else {
            $info["idDevis"] = "";
            $info["textDevis"] = "";
            $info["colorDevis"] = "";
        }

        if(null != $queryPromoPage){
            $info["idPromoPage"] = $queryPromoPage[0]["idPromoPage"];
            $info["promoPageText"] = $queryPromoPage[0]["promoPageText"];
        } else {
            $info["idPromoPage"] = "";
            $info["promoPageText"] = "";
        }

        return $info;
    }

    public function getCampaignInfoCart(&$id, &$idElement){

        $queryCart = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_cart ccl
            WHERE ccl.id_campaign = '.$id.' AND ccl.id = '.$idElement);
        return $queryCart[0];

    }

    public function saveCartPromo(&$data){


        if(isset($data["ID"]) && isset($data["CART_ID"]) && null !== $data["CART_ID"]) {
            $updateMenu =  Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_cart` 
            SET `is_fixed` = "' . $data['CART_TYPE'] . '",
                `discount_fixed` = ' . $data['CART_FIXED_DISCOUNT'] . ',
                `price_fixed` = ' . $data['CART_FIXED_PRICE'] . ',
                `discount_range` = ' . $data['CART_RANGED_DISCOUNT'] . ',
                `discount_range_second` = ' . $data['CART_RANGED_DISCOUNT_SECOND'] . ',
                `discount_range_third` = ' . $data['CART_RANGED_DISCOUNT_THIRD'] . ',
                `discount_range_max` = ' . $data['CART_RANGED_DISCOUNT_MAX'] . ',
                `price_range_min` = ' . $data['CART_RANGED_PRICE_MIN'] . ',
                `price_range_second` = ' . $data['CART_RANGED_PRICE_SECOND'] . ',
                `price_range_max` = ' . $data['CART_RANGED_PRICE_MAX'] . ',
                `text` = "' . $data['CART_TEXT'] . '",
                `text_color` = "' . $data['CART_TEXT_COLOR'] . '",
                `active` = ' . $data['CART_ACTIVE'] . '
                                WHERE `id` = '.$data["CART_ID"].' AND `id_campaign` = ' .$data["ID"]);

            return $data["CART_ID"];

        } elseif (isset($data["ID"])) {
            $createCartPromo = Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_cart` 
                                    (`id`, `id_campaign`, `is_fixed`, `discount_fixed`, `price_fixed`, `discount_range`,`discount_range_second`,`discount_range_third`, `discount_range_max`, `price_range_min`,`price_range_second`, `price_range_max`, `text`, `text_color`,`active`, `date_add`)
                            VALUES (NULL, '.$data['ID'].', '.$data['CART_TYPE'].', NULLIF('.$data['CART_FIXED_DISCOUNT'].', 0), NULLIF('.$data['CART_FIXED_PRICE'].', 0),
                            NULLIF('.$data['CART_RANGED_DISCOUNT'].', 0), NULLIF('.$data['CART_RANGED_DISCOUNT_SECOND'].', 0),NULLIF('.$data['CART_RANGED_DISCOUNT_THIRD'].', 0),
                            NULLIF('.$data['CART_RANGED_DISCOUNT_MAX'].', 0),NULLIF('.$data['CART_RANGED_PRICE_MIN'].', 0),NULLIF('.$data['CART_RANGED_PRICE_SECOND'].', 0), NULLIF('.$data['CART_RANGED_PRICE_MAX'].', 0),
                            \''.$data['CART_TEXT'].'\', NULLIF(\''.$data['CART_TEXT_COLOR'].'\', ""), '.$data['CART_ACTIVE'].' ,CURRENT_TIMESTAMP())'
            );

            $findCreatedId = Db::getInstance()->executeS('SELECT `id` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_cart`
            WHERE `id_campaign` = '.$data["ID"].' AND `is_fixed` = '.$data['CART_TYPE'].' AND `active` = '.$data['CART_ACTIVE'].' AND `text` LIKE \''.$data['CART_TEXT'].'\'');

            return $findCreatedId[0]['id'];
        }

    }

    public function getCampaignInfoProduct(&$id, &$idElement) {

        $queryProduct = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_product ccp
        WHERE ccp.id_campaign = '.$id.' AND ccp.id = '.$idElement);

        return $queryProduct[0];
    }

    public function saveProductPromo(&$data){
        if(isset($data["ID"]) && isset($data["PRODUCT_ID"]) && null !== $data["PRODUCT_ID"]) {
            $updateMenu =  Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` 
            SET `text` = \'' . $data['PRODUCT_TEXT'] . '\',
                `active` = ' . $data['PRODUCT_ACTIVE'] . ',
                `show_all` = ' . $data['PRODUCT_SHOW_ALL'] . ',
                `show_above_price` = ' . (int)$data['PRODUCT_SHOW_ABOVE_PRICE'] . ',
                `reduction_percent` = NULLIF(' . (float)$data['PRODUCT_REDUCTION_PERCENT'] . ', 0)
                WHERE `id` = '.$data["PRODUCT_ID"].' AND `id_campaign` = ' .$data["ID"]);
            if(isset($data['PRODUCT_SELECTION'])){
                $deleteOld = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_product_show` WHERE `id_product_content` = '.$data["PRODUCT_ID"]);
                if("" !== $data['PRODUCT_SELECTION']){
                    $productsArr = explode(",", $data['PRODUCT_SELECTION']);
                    foreach($productsArr as $productId){
                        if($productId !== "") {
                            $insertProductSelection = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_product_show` 
                        (`id`, `id_product_content`, `id_product`)
                            VALUES (NULL, '.$data["PRODUCT_ID"].','.$productId.')');
                        }
                    }
                }
            }

            return $data["PRODUCT_ID"];

        } elseif (isset($data["ID"])) {
            $createProductPromo = Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` 
                                    (`id`, `id_campaign`, `text`,`active`, `show_all`, `show_above_price`, `reduction_percent`, `date_add`)
                            VALUES (NULL, '.$data['ID'].',\''.$data['PRODUCT_TEXT'].'\', '.$data['PRODUCT_ACTIVE'].','.$data['PRODUCT_SHOW_ALL'].','.(int)$data['PRODUCT_SHOW_ABOVE_PRICE'].',NULLIF(' . (float)$data['PRODUCT_REDUCTION_PERCENT'] . ', 0),CURRENT_TIMESTAMP())'
            );
            if(isset($data['PRODUCT_SELECTION']) && "" !== $data['PRODUCT_SELECTION']){
                $productsArr = explode(",", $data['PRODUCT_SELECTION']);
                $selectPromoId = Db::getInstance()->executeS('SELECT id FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_product` WHERE `id_campaign` = '.$data['ID'].' AND `text` LIKE \'' .$data['PRODUCT_TEXT'].'\'');
                foreach($productsArr as $productId){
                    if($productId !== "") {
                        $insertProductSelection = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_product_show` 
                        (`id`, `id_product_content`, `id_product`)
                            VALUES (NULL, '.$selectPromoId[0]["id"].','.$productId.')');
                    }
                }
            }
            $idCreatedPromo = Db::getInstance()->executeS('SELECT `id` FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_product
            WHERE `id_campaign` = '.$data["ID"].' AND `text` LIKE \''.$data['PRODUCT_TEXT'].'\' AND `active` = '.$data['PRODUCT_ACTIVE']);

            return $idCreatedPromo[0]['id'];
        }
    }

    public function getCampaignInfoCategory(&$id, &$idElement) {

        $queryCategory = Db::getInstance()->executeS('SELECT ccc.id as idCategory, ccc.text as categoryText, ccc.background_color as categoryColor, ccc.active as categoryActive, ccc.show_all as categoryShowAll
        FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_category ccc
        WHERE ccc.id_campaign = '.$id.' AND ccc.id = '.$idElement);

        return $queryCategory[0];
    }

    public function saveCategoryPromo(&$data){
        if(isset($data["ID"]) && isset($data["CATEGORY_ID"]) && null !== $data["CATEGORY_ID"]) {
            $updateCategory =  Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` 
                SET `text`  = \'' . $data["CATEGORY_TEXT"] . '\',
                    `background_color`  = \'' . $data["CATEGORY_BACKGROUND_COLOR"] . '\',
                    `active`  = ' . (int)$data["CATEGORY_ACTIVE"] . ',
                    `show_all`  = ' . (int)$data["CATEGORY_SHOW_ALL"] . '
                    WHERE  `id` = '.$data["CATEGORY_ID"].' AND `id_campaign` = ' .$data["ID"]);
            if(isset($data['CATEGORY_LIST'])){
                $deleteOld = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_category_show` WHERE id_campaign = '.$data["CATEGORY_ID"]);
                if("" !== $data['CATEGORY_LIST']) {
                    $selectedCatArr = explode(",",$data['CATEGORY_LIST']);

                    foreach ($selectedCatArr as $category) {
                        $updateNew = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_category_show` (`id`, `id_campaign`, `id_category`)
                            VALUES (NULL, '.$data["CATEGORY_ID"].', '.$category.')');
                    }
                }
            }
            return $data["CATEGORY_ID"];

        } elseif (isset($data["ID"])){
            $createCategory = Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` (`id`, `id_campaign`, `text`, `background_color`, `active`,`show_all`, `date_add`)
                                VALUES (NULL, '.$data["ID"].', \''.$data["CATEGORY_TEXT"].'\', \''.$data["CATEGORY_BACKGROUND_COLOR"].'\', 
                                '.(int)$data["CATEGORY_ACTIVE"].', '.(int)$data["CATEGORY_SHOW_ALL"].', CURRENT_TIMESTAMP())'
            );
            $queryCategory = Db::getInstance()->executeS('SELECT id FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` WHERE `id_campaign` = '.$data['ID'].' AND `text` LIKE \'' .$data['CATEGORY_TEXT'].'\'');
            if(isset($data['CATEGORY_LIST'])){
                if("" !== $data['CATEGORY_LIST']) {
                    $selectedCatArr = explode(",",$data['CATEGORY_LIST']);
                    foreach ($selectedCatArr as $category) {
                        $updateNew = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_category_show` (`id`, `id_campaign`, `id_category`)
                        VALUES (NULL, '.$queryCategory[0]["id"].', '.$category.')');
                    }
                }
            }
            return $queryCategory[0]["id"];
        }

    }
}