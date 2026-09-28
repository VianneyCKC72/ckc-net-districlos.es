<?php
/**
 * CKC Vianney — répare les link_rewrite EN tronqués (ex. rigid-fenc au lieu de rigid-fencing).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcTranslateSlugRepair
{
    /** Ancien plafond accidentel (max(0, 10) après save BO vide). */
    public const BAD_MAX = 10;

    /**
     * @return array{categories:int,products:int,cms:int,config_fixed:bool}
     */
    public static function repairEnglish($idLang = 0, $idShop = 0)
    {
        $result = array(
            'categories' => 0,
            'products' => 0,
            'cms' => 0,
            'config_fixed' => false,
        );

        $slugMax = (int) Configuration::get(CkcTranslate::CONF_SEO_SLUG_MAX);
        if ($slugMax < 32) {
            Configuration::updateValue(CkcTranslate::CONF_SEO_SLUG_MAX, 80);
            $result['config_fixed'] = true;
        }

        if ($idLang <= 0) {
            $idLang = (int) Language::getIdByIso('en');
        }
        if ($idLang <= 0) {
            return $result;
        }

        if ($idShop <= 0) {
            $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }

        $result['categories'] = self::repairTable(
            'category_lang',
            'id_category',
            'name',
            $idLang,
            $idShop,
            true
        );
        $result['products'] = self::repairTable(
            'product_lang',
            'id_product',
            'name',
            $idLang,
            $idShop,
            true
        );
        $result['cms'] = self::repairTable(
            'cms_lang',
            'id_cms',
            'meta_title',
            $idLang,
            $idShop,
            true
        );

        return $result;
    }

    /**
     * @return int nombre de lignes mises à jour
     */
    protected static function repairTable($table, $idColumn, $sourceColumn, $idLang, $idShop, $hasShop)
    {
        $sql = 'SELECT `' . bqSQL($idColumn) . '` AS id, `' . bqSQL($sourceColumn) . '` AS source_label, `link_rewrite`
            FROM `' . _DB_PREFIX_ . bqSQL($table) . '`
            WHERE id_lang = ' . (int) $idLang;
        if ($hasShop && $idShop > 0) {
            $sql .= ' AND id_shop = ' . (int) $idShop;
        }

        $rows = Db::getInstance()->executeS($sql);
        if (!$rows) {
            return 0;
        }

        $updated = 0;
        foreach ($rows as $row) {
            $current = trim((string) $row['link_rewrite']);
            $expected = Tools::link_rewrite((string) $row['source_label']);
            if ($expected === '' || $current === $expected) {
                continue;
            }
            if (!self::looksTruncated($current, $expected)) {
                continue;
            }

            $where = '`' . bqSQL($idColumn) . '` = ' . (int) $row['id']
                . ' AND id_lang = ' . (int) $idLang;
            if ($hasShop && $idShop > 0) {
                $where .= ' AND id_shop = ' . (int) $idShop;
            }

            $ok = Db::getInstance()->update(
                $table,
                array('link_rewrite' => pSQL($expected)),
                $where
            );
            if ($ok) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * @param string $current
     * @param string $expected
     */
    public static function looksTruncated($current, $expected)
    {
        $current = (string) $current;
        $expected = (string) $expected;
        if ($current === '' || $expected === '') {
            return false;
        }

        $curLen = Tools::strlen($current);
        $expLen = Tools::strlen($expected);
        if ($expLen <= $curLen) {
            return false;
        }

        // Coupe nette à l’ancien plafond (10)
        if ($curLen === self::BAD_MAX && strpos($expected, $current) === 0) {
            return true;
        }

        // Préfixe tronqué plus court que le slug attendu depuis le name
        if ($curLen < self::BAD_MAX + 5 && strpos($expected, $current) === 0) {
            return true;
        }

        return false;
    }
}
