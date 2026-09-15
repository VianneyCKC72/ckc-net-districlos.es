<?php
/**
 * Helpers to localize HS MultiAccessories labels on FO.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcHsmaI18nHelper
{
    /** @var array<string, string> */
    protected static $groupMap = array(
        'Accessoires pour poteau' => 'Post accessories',
        'Accessoires' => 'Accessories',
        'Accessoire' => 'Accessory',
    );

    /**
     * Smarty modifier: {$name|ckc_hsma_product_name:$id_product}
     *
     * @param string $name
     * @param int $idProduct
     *
     * @return string
     */
    public static function smartyProductName($name, $idProduct = 0)
    {
        return self::localizeProductName((string) $name, (int) $idProduct);
    }

    /**
     * Smarty modifier: {$name|ckc_hsma_group_name}
     *
     * @param string $name
     *
     * @return string
     */
    public static function smartyGroupName($name)
    {
        return self::localizeGroupName((string) $name);
    }

    /**
     * Smarty modifier: {$accessories_table_price|ckc_hsma_table_price nofilter}
     * Input is a JSON string built by hsmultiaccessoriespro.
     *
     * @param string $json
     *
     * @return string
     */
    public static function smartyTablePrice($json)
    {
        if (!is_string($json) || $json === '') {
            return $json;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return $json;
        }

        foreach ($data as $key => &$row) {
            if (!is_array($row) || strpos((string) $key, 'ACC_') !== 0) {
                continue;
            }
            $idProduct = isset($row['id_accessory']) ? (int) $row['id_accessory'] : 0;
            if (!$idProduct && isset($row['id_product'])) {
                $idProduct = (int) $row['id_product'];
            }
            if (isset($row['name'])) {
                $row['name'] = self::localizeProductName((string) $row['name'], $idProduct);
            }
        }
        unset($row);

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
        return $encoded === false ? $json : $encoded;
    }

    /**
     * Prefer product_lang in current language when custom accessory name is still FR.
     *
     * @param string $name
     * @param int $idProduct
     *
     * @return string
     */
    public static function localizeProductName($name, $idProduct)
    {
        $name = trim((string) $name);
        $idProduct = (int) $idProduct;
        $context = Context::getContext();
        if (!$context || !$context->language) {
            return $name;
        }

        $idLang = (int) $context->language->id;
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        if ($idLang === $idLangDefault || $idProduct <= 0) {
            return $name;
        }

        $translated = self::getProductName($idProduct, $idLang);
        if ($translated === '') {
            return $name;
        }

        // On non-default languages, always prefer the catalog product name (already translated).
        return $translated;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function localizeGroupName($name)
    {
        $name = trim((string) $name);
        $context = Context::getContext();
        if (!$context || !$context->language) {
            return $name;
        }

        $idLang = (int) $context->language->id;
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        if ($idLang === $idLangDefault || $name === '') {
            return $name;
        }

        if (isset(self::$groupMap[$name])) {
            return self::$groupMap[$name];
        }

        if ($context->getTranslator()) {
            $translated = $context->getTranslator()->trans(
                $name,
                array(),
                'Shop.Theme.Custommodules',
                $context->language->locale
            );
            if ($translated !== '' && $translated !== $name) {
                return $translated;
            }
        }

        return $name;
    }

    /**
     * Localize accessories_groups + accessory_groups arrays (used by module override).
     *
     * @param array $accessoryGroups
     * @param array $accessoriesGroups
     * @param array $accessoriesTablePrice decoded array
     *
     * @return array{0: array, 1: array, 2: array}
     */
    public static function localizeBlockData(array $accessoryGroups, array $accessoriesGroups, array $accessoriesTablePrice)
    {
        foreach ($accessoryGroups as &$group) {
            if (isset($group['name'])) {
                $group['name'] = self::localizeGroupName($group['name']);
            }
        }
        unset($group);

        foreach ($accessoriesGroups as &$groupAccessories) {
            if (!is_array($groupAccessories)) {
                continue;
            }
            foreach ($groupAccessories as &$accessory) {
                if (!is_array($accessory)) {
                    continue;
                }
                $idProduct = isset($accessory['id_accessory']) ? (int) $accessory['id_accessory'] : 0;
                if (isset($accessory['name'])) {
                    $accessory['name'] = self::localizeProductName($accessory['name'], $idProduct);
                }
            }
            unset($accessory);
        }
        unset($groupAccessories);

        foreach ($accessoriesTablePrice as $key => &$row) {
            if (!is_array($row) || strpos((string) $key, 'ACC_') !== 0) {
                continue;
            }
            $idProduct = isset($row['id_accessory']) ? (int) $row['id_accessory'] : 0;
            if (isset($row['name'])) {
                $row['name'] = self::localizeProductName($row['name'], $idProduct);
            }
        }
        unset($row);

        return array($accessoryGroups, $accessoriesGroups, $accessoriesTablePrice);
    }

    /**
     * @param int $idProduct
     * @param int $idLang
     *
     * @return string
     */
    protected static function getProductName($idProduct, $idLang)
    {
        $idProduct = (int) $idProduct;
        $idLang = (int) $idLang;
        if ($idProduct <= 0 || $idLang <= 0) {
            return '';
        }

        $idShop = (int) Context::getContext()->shop->id;
        $name = Db::getInstance()->getValue(
            'SELECT `name` FROM `' . _DB_PREFIX_ . 'product_lang`
             WHERE `id_product` = ' . $idProduct . '
               AND `id_lang` = ' . $idLang . '
               AND `id_shop` = ' . $idShop
        );

        return $name ? trim((string) $name) : '';
    }
}
