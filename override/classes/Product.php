<?php
class Product extends ProductCore
{
	public $en_scellement;
	public $avec_sousbassement;
    public static function getAttributesInformationsByProduct($id_product)
    {
        if (Module::isInstalled('blocklayered') && Module::isEnabled('blocklayered')) {
            $nb_custom_values = Db::getInstance()->executeS('
            SELECT DISTINCT la.`id_attribute`, la.`url_name` as `attribute`, pac.`id_product_attribute`
            FROM `'._DB_PREFIX_.'attribute` a
            LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
                ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
            '.Shop::addSqlAssociation('product_attribute', 'pa').'
            LEFT JOIN `'._DB_PREFIX_.'layered_indexable_attribute_lang_value` la
                ON (la.`id_attribute` = a.`id_attribute` AND la.`id_lang` = '.(int)Context::getContext()->language->id.')
            WHERE la.`url_name` IS NOT NULL AND la.`url_name` != \'\'
            AND pa.`id_product` = '.(int)$id_product);
            if (!empty($nb_custom_values)) {
                $tab_id_attribute = array();
                foreach ($nb_custom_values as $attribute) {
                    $tab_id_attribute[] = $attribute['id_attribute'];
                    $group = Db::getInstance()->executeS('
                    SELECT g.`id_attribute_group`, g.`url_name` as `group`
                    FROM `'._DB_PREFIX_.'layered_indexable_attribute_group_lang_value` g
                    LEFT JOIN `'._DB_PREFIX_.'attribute` a
                        ON (a.`id_attribute_group` = g.`id_attribute_group`)
                    WHERE a.`id_attribute` = '.(int)$attribute['id_attribute'].'
                    AND g.`id_lang` = '.(int)Context::getContext()->language->id.'
                    AND g.`url_name` IS NOT NULL AND g.`url_name` != \'\'');
                    if (empty($group)) {
                        $group = Db::getInstance()->executeS('
                        SELECT g.`id_attribute_group`, g.`name` as `group`
                        FROM `'._DB_PREFIX_.'attribute_group_lang` g
                        LEFT JOIN `'._DB_PREFIX_.'attribute` a
                            ON (a.`id_attribute_group` = g.`id_attribute_group`)
                        WHERE a.`id_attribute` = '.(int)$attribute['id_attribute'].'
                        AND g.`id_lang` = '.(int)Context::getContext()->language->id.'
                        AND g.`name` IS NOT NULL');
                    }
                    if(isset($group[0])) {
                        $result[] = array_merge($attribute, $group[0]);
                    } else {
                        $result[] = $attribute;
                    }
                }
                $values_not_custom = Db::getInstance()->executeS('
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
                '.Shop::addSqlAssociation('product_attribute', 'pa').'
                '.Shop::addSqlAssociation('attribute', 'pac').'
                WHERE pa.`id_product` = '.(int)$id_product.'
                AND a.`id_attribute` NOT IN('.implode(', ', $tab_id_attribute).')');
                $result = array_merge($values_not_custom, $result);
            } else {
                $result = Db::getInstance()->executeS('
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
                '.Shop::addSqlAssociation('product_attribute', 'pa').'
                '.Shop::addSqlAssociation('attribute', 'pac').'
                WHERE pa.`id_product` = '.(int)$id_product);
            }
        } else {
            $result = Db::getInstance()->executeS('
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
            '.Shop::addSqlAssociation('product_attribute', 'pa').'
            '.Shop::addSqlAssociation('attribute', 'pac').'
            WHERE pa.`id_product` = '.(int)$id_product);
        }
        return $result;
    }

    /**
     * Adrien CKC (17/05/2023)
     * Copie les transporteurs sur toutes les boutiques sur le contexte "Toutes les boutiques" (ID = 1) est sélectionné
     * Si un autre contexte de boutique est sélectionnée (ID != 1), les modifications ne s'appliquent qu'à cette boutique
     */
    public function setCarriers($carrier_list)
    {
        $selectedShop = Context::getContext()->shop->id;
        $shops = array_map(function($i) { return $i['id_shop']; }, Shop::getShops());

        $data = [];

        foreach ($carrier_list as $carrier)
        {
            if($selectedShop == 1)
            {
                foreach($shops as $shopId) {
                    $data[] = [
                        'id_product' => (int) $this->id,
                        'id_carrier_reference' => (int) $carrier,
                        'id_shop' => (int) $shopId,
                    ];
                }
            }
            else
            {
                $data[] = [
                    'id_product' => (int) $this->id,
                    'id_carrier_reference' => (int) $carrier,
                    'id_shop' => (int) $this->id_shop,
                ];
            }
        }

        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'product_carrier`
            WHERE id_product = ' . (int) $this->id . '
            AND id_shop ' . (($selectedShop == 1) ? 'IN ' . '(' . implode(',', $shops) . ')' : '= ' . (int) $this->id_shop)
        );

        $unique_array = [];
        foreach ($data as $sub_array) {
            if (!in_array($sub_array, $unique_array)) {
                $unique_array[] = $sub_array;
            }
        }

        if (count($unique_array)) {
            Db::getInstance()->insert('product_carrier', $unique_array, false, true, Db::INSERT_IGNORE);
        }
    }

    /*
    * module: dynamicproduct
    * date: 2023-05-04 16:05:26
    * version: 2.104.1
    */
    public static function getProductProperties($id_lang, $row, Context $context = null)
    {
        $result = parent::getProductProperties($id_lang, $row, $context);
        /** @var DynamicProduct $module */
        $module = Module::getInstanceByName('dynamicproduct');
        if (Module::isEnabled('dynamicproduct') && $module->provider->isAfter1730()) {
            $id_product = (int)$row['id_product'];

            $controller = Tools::getValue('controller');
            if ($controller == 'product') {
                $current_product = (int)Tools::getValue('id_product');
                // if it's the current product, we don't modify its property
                if ($current_product == $id_product) {
                    return $result;
                }
            }

            $dynamic_config = classes\models\DynamicConfig::getByProduct($id_product);
            if ($dynamic_config->active) {
                $displayed_price = classes\models\DynamicConfig::getDisplayedPrice($id_product);
                if ($displayed_price || $dynamic_config->display_dynamic_price) {
                    $module->calculator->assignProductPrices($row, $displayed_price, $result);
                }
            }
        }

        return $result;
    }

    public function haveAccessories($id_product) {
        $have_accessories = false;
        $sql = "SELECT
                        ag.`id_accessory_group`,
                        ag.`show_name`,
                        ag.`collapse_expand`,
                        ag.`buy_together`,
                        IF(ag.`display_style` = 3, 0 , ag.`display_style`) as display_style,
                        agl.`name`
                    FROM
                        `ps_accessory_group` ag
                    LEFT JOIN
                        `ps_accessory_group_lang` `agl` ON agl.`id_accessory_group` = ag.`id_accessory_group`
                    WHERE
                        `agl`.`id_lang` = 1 AND `ag`.`active` = 1
                    ORDER BY ag.`position` ASC";
        $groups = Db::getInstance()->executeS($sql);
        $id_groups = array();
        if (!empty($groups)) {
            foreach ($groups as $group) {
                $id_groups[] = $group['id_accessory_group'];
            }
            $have_accessories = false;
            if (!empty($id_groups)) {
                $id_groups = '('.implode(',', $id_groups).')';
                $sql = "SELECT
                                DISTINCT apg.`id_accessory_group`,
                                apg.`id_accessory`,
                                apg.`default_quantity`,
                                apg.`min_quantity`,
                                apg.`required`,
                                apg.`position`,
                                pl.`link_rewrite`,
                                pl.`description_short`,
                                pl.`available_later`,
                                p.`id_product` AS `id_product`,
                                p.`is_virtual`,
                                stock.`quantity` AS `stock_available`,
                                stock.`out_of_stock`,
                                apg.`id_accessory_group_product`,
                                apg.`id_product_attribute`,
                                i.`id_image`,\n
                                IF(`is`.`id_image`, `is`.`id_image`, i.`id_image`) `id_image`                    
                            FROM
                                    `ps_accessory_group_product` apg INNER JOIN `ps_product` p ON (p.`id_product` = apg.`id_accessory` AND p.active =1)LEFT JOIN
                                        `ps_product_lang` pl ON (pl.`id_product` = apg.`id_accessory` AND pl.`id_shop` = 1 AND pl.`id_lang` = 1)
                                LEFT JOIN
                                        `ps_accessory_group_product_lang` agpl ON (agpl.`id_accessory_group_product` = apg.`id_accessory_group_product`  AND agpl.`id_lang` = 1)
                                LEFT JOIN
                                        `ps_image` i ON (i.`id_product` = apg.`id_accessory` AND `i`.`cover` = 1)
                                LEFT JOIN
                                   `ps_image_shop` `is` ON ( `is`.`id_image` = i.`id_image` AND `is`.id_shop = 1 AND `is`.`cover` = 1)
                                LEFT JOIN
                                                `ps_image_lang` il ON (il.`id_image` = i.`id_image`  AND il.`id_lang` = 1)
                                LEFT JOIN ps_stock_available stock
                                    ON (stock.id_product = `p`.id_product AND stock.id_product_attribute = 0 AND stock.id_shop = 1  AND stock.id_shop_group = 0  )
                                INNER JOIN ps_product_shop product_shop
                                         ON (product_shop.id_product = p.id_product AND product_shop.id_shop = 1)    
                                WHERE
                                        apg.`id_accessory_group` IN ".$id_groups." AND apg.`id_product` IN(".$id_product.") AND p.`id_product` NOT IN (".$id_product.") AND p.`visibility` != 'none'
                                GROUP BY
                                        apg.`id_accessory_group`,
                                        apg.`id_accessory`
                                ORDER BY apg.`position` ASC";
                $accessories = Db::getInstance()->executeS($sql);
                if (!empty($accessories)) {
                    $have_accessories = true;
                }
            }
        }
        return $have_accessories;
    }
}
