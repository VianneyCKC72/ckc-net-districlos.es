<?php

namespace CkcConfigurator\Configurator;

class Helper
{
    static public function getNumberOfItem($length, $session)
    {
        $step = 2.5;

        $is_souple   = $session->isSouple();
        $is_aluminum = $session->isAluminium();

        if ($is_souple) {
            $step = 25;

            if ($length <= $step) {
                return 1;
            }
        }

        if ($is_aluminum) {
            $step = 1.5;
            $nb_item = ceil($length / $step);
            return $nb_item;
        }

        $times = $length / $step;

        for ($i = 0; $i < 10000; $i++) {
            if ($times * $step <= $i * $step) {
                return $i;
            }
        }

        return 1;
    }

    /**
     * Get all available product attributes resume.
     *
     * @param int    $id_lang Language identifier
     * @param string $attribute_value_separator
     * @param string $attribute_separator
     *
     * @return array Product attributes combinations
     */
    static public function getAttributesResume($id_lang, $id_product, $attribute_value_separator = ' - ', $attribute_separator = ', ')
    {

        $combinations = \Db::getInstance()->executeS('SELECT pa.*, product_attribute_shop.*
                FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                ' . \Shop::addSqlAssociation('product_attribute', 'pa') . '
                WHERE pa.`id_product` = ' . (int)$id_product . '
                GROUP BY pa.`id_product_attribute`
                ORDER BY pa.`id_product_attribute`');

        if (!$combinations) {
            return false;
        }

        $product_attributes = [];
        foreach ($combinations as $combination) {
            $product_attributes[] = (int)$combination['id_product_attribute'];
        }

        $lang = \Db::getInstance()->executeS('SELECT pac.id_product_attribute, GROUP_CONCAT(agl.`name`, \'' . pSQL($attribute_value_separator) . '\',al.`name` ORDER BY agl.`id_attribute_group` SEPARATOR \'' . pSQL($attribute_separator) . '\') as attribute_designation
                FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int)$id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int)$id_lang . ')
                WHERE pac.id_product_attribute IN (' . implode(',', $product_attributes) . ')
                GROUP BY pac.id_product_attribute
                ORDER BY pac.id_product_attribute');

        foreach ($lang as $k => $row) {
            $combinations[$k]['attribute_designation'] = $row['attribute_designation'];
        }

        $computingPrecision = \Context::getContext()->getComputingPrecision();
        //Get quantity of each variations
        foreach ($combinations as $key => $row) {
            $cache_key = $row['id_product'] . '_' . $row['id_product_attribute'] . '_quantity';

            if (!\Cache::isStored($cache_key)) {
                $result = \StockAvailable::getQuantityAvailableByProduct($row['id_product'], $row['id_product_attribute']);
                \Cache::store(
                    $cache_key,
                    $result
                );
                $combinations[$key]['quantity'] = $result;
            }
            else {
                $combinations[$key]['quantity'] = \Cache::retrieve($cache_key);
            }

            $ecotax                                    = (float)$combinations[$key]['ecotax'] ?? 0;
            $combinations[$key]['ecotax_tax_excluded'] = $ecotax;
            $combinations[$key]['ecotax_tax_included'] = \Tools::ps_round($ecotax * (1 + \Tax::getProductEcotaxRate() / 100), $computingPrecision);
        }

        return $combinations;

    }

    /**
     * @param $name
     *
     * @return float
     */
    static public function getCurrentHeight($name)
    {
        return floatval(str_replace('m', '.', substr($name, -4)));
    }

    /**
     * @param $name
     *
     * @return float
     */
    static public function getCurrentHeightAttributes($name)
    {
        $arrName = explode(",", $name);
        foreach ($arrName as $name) {
            if (strpos($name, "Hauteur")) {
                $arrNameheight = explode("-", $name);
                return trim($arrNameheight[1]);
            }
        }
    }

    static public function valueExists($needle, $haystack, $key)
    {
        foreach ($haystack as $item) {
            if ($item[$key] == $needle) {
                return true;
            }
        }
        return false;
    }

    static public function getIdMainProduct($id_category, $language_id)
    {

        $category = new \Category($id_category, $language_id);

        $products = $category->getProducts($language_id, 1, 1000000, null, null, false, true, false, 1, false);
//
//        dump("id_categ=".$id_category." | ".$category->name);
//        dump("id_product=".$products[0]["id_product"]);

        return $products[0]["id_product"];
    }

    static public function getPrice($id_product, $id_product_attribute = null, $quantity)
    {
        $id_product_attribute = $id_product_attribute == 0 ? null : $id_product_attribute;
        $context              = \Context::getContext();

        $price = \Product::getPriceStatic(
            $id_product,
            true,
            $id_product_attribute,
            2,
            null,
            false,
            true,
            $quantity,
            false,
            $context->customer->id,
            (int)$context->cart->id,
            null,
            $specific_price_output,
            true,
            true,
            $context
        );

        $unitPrice = $price;
        $price     = $unitPrice * $quantity;

        return ['unit_price' => $unitPrice, 'total_price' => $price];
    }

    static public function isComposite($session)
    {
        return in_array($session->id_category, [\CkcConfigurator\Config::COMPOSITE_ID, \CkcConfigurator\Config::COMPOSITE_PLEINES_ID], true)
            || $session->id_type === \CkcConfigurator\Config::COMPOSITE_PLEINES_ID;
    }

    static public function getCompositeLengthIndex($lengthMm)
    {
        if ($lengthMm <= 1800) {
            return 0;
        }
        return (int) floor(($lengthMm - 1) / 1800);
    }
}