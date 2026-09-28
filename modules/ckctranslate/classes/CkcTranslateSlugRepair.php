<?php
/**
 * CKC Vianney — répare slugs / noms / metas EN tronqués (plafond accidentel à 10).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcTranslateSlugRepair
{
    /** Ancien plafond accidentel (max(0, 10) après save BO vide). */
    public const BAD_MAX = 10;

    /**
     * @return array{
     *   categories:int,
     *   products:int,
     *   cms:int,
     *   names:int,
     *   meta_titles:int,
     *   meta_descriptions:int,
     *   config_fixed:bool
     * }
     */
    public static function repairEnglish($idLang = 0, $idShop = 0)
    {
        $result = array(
            'categories' => 0,
            'products' => 0,
            'cms' => 0,
            'names' => 0,
            'meta_titles' => 0,
            'meta_descriptions' => 0,
            'config_fixed' => false,
        );

        $result['config_fixed'] = self::fixSeoLimitsConfig();

        if ($idLang <= 0) {
            $idLang = (int) Language::getIdByIso('en');
        }
        if ($idLang <= 0) {
            return $result;
        }

        if ($idShop <= 0) {
            $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }

        $idSourceLang = (int) Configuration::get('PS_LANG_DEFAULT');
        if ($idSourceLang <= 0) {
            $idSourceLang = (int) Language::getIdByIso('fr');
        }

        $cat = self::repairEntityLang(
            'category_lang',
            'id_category',
            $idLang,
            $idShop,
            true,
            true,
            $idSourceLang
        );
        $prod = self::repairEntityLang(
            'product_lang',
            'id_product',
            $idLang,
            $idShop,
            true,
            true,
            $idSourceLang
        );
        $cms = self::repairEntityLang(
            'cms_lang',
            'id_cms',
            $idLang,
            $idShop,
            true,
            false,
            $idSourceLang
        );

        $result['categories'] = $cat['slugs'];
        $result['products'] = $prod['slugs'];
        $result['cms'] = $cms['slugs'];
        $result['names'] = $cat['names'] + $prod['names'] + $cms['names'];
        $result['meta_titles'] = $cat['meta_titles'] + $prod['meta_titles'] + $cms['meta_titles'];
        $result['meta_descriptions'] = $cat['meta_descriptions'] + $prod['meta_descriptions'] + $cms['meta_descriptions'];

        return $result;
    }

    /**
     * @return bool true si une config a été corrigée
     */
    public static function fixSeoLimitsConfig()
    {
        $fixed = false;

        $slugMax = (int) Configuration::get(CkcTranslate::CONF_SEO_SLUG_MAX);
        if ($slugMax < 32) {
            Configuration::updateValue(CkcTranslate::CONF_SEO_SLUG_MAX, 80);
            $fixed = true;
        }

        $titleMax = (int) Configuration::get(CkcTranslate::CONF_SEO_META_TITLE_MAX);
        if ($titleMax < 30) {
            Configuration::updateValue(CkcTranslate::CONF_SEO_META_TITLE_MAX, 60);
            $fixed = true;
        }

        $descMax = (int) Configuration::get(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX);
        if ($descMax < 50) {
            Configuration::updateValue(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX, 155);
            $fixed = true;
        }

        return $fixed;
    }

    /**
     * @return array{slugs:int,names:int,meta_titles:int,meta_descriptions:int}
     */
    protected static function repairEntityLang(
        $table,
        $idColumn,
        $idLang,
        $idShop,
        $hasShop,
        $hasName,
        $idSourceLang
    ) {
        $counts = array(
            'slugs' => 0,
            'names' => 0,
            'meta_titles' => 0,
            'meta_descriptions' => 0,
        );

        $select = 't.`' . bqSQL($idColumn) . '` AS id, t.`link_rewrite`, t.`meta_title`, t.`meta_description`';
        if ($hasName) {
            $select .= ', t.`name`';
        }

        $sql = 'SELECT ' . $select . '
            FROM `' . _DB_PREFIX_ . bqSQL($table) . '` t
            WHERE t.id_lang = ' . (int) $idLang;
        if ($hasShop && $idShop > 0) {
            $sql .= ' AND t.id_shop = ' . (int) $idShop;
        }

        $rows = Db::getInstance()->executeS($sql);
        if (!$rows) {
            return $counts;
        }

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $update = array();
            $name = $hasName ? trim((string) $row['name']) : '';
            $metaTitle = trim((string) $row['meta_title']);
            $metaDescription = trim((string) $row['meta_description']);
            $slug = trim((string) $row['link_rewrite']);

            $source = self::loadSourceRow($table, $idColumn, $id, $idSourceLang, $idShop, $hasShop, $hasName);

            // 1) Name tronqué → reconstruire depuis un slug complet, sinon vider pour retraduction
            if ($hasName && self::looksTruncatedLabel($name, $source ? $source['name'] : '')) {
                $fromSlug = self::labelFromSlug($slug);
                if ($fromSlug !== '' && Tools::strlen($fromSlug) > Tools::strlen($name)) {
                    $update['name'] = pSQL($fromSlug);
                    $name = $fromSlug;
                    $counts['names']++;
                } else {
                    $update['name'] = '';
                    $name = '';
                    $counts['names']++;
                }
            }

            // 2) Meta title tronquée → recopier le name si OK, sinon vider
            if (self::looksTruncatedLabel($metaTitle, $source ? $source['meta_title'] : '')) {
                if ($name !== '' && !self::looksTruncatedLabel($name, '')) {
                    $update['meta_title'] = pSQL($name);
                    $metaTitle = $name;
                } else {
                    $update['meta_title'] = '';
                    $metaTitle = '';
                }
                $counts['meta_titles']++;
            }

            // 3) Meta description tronquée → vider pour retraduction
            if (self::looksTruncatedLabel($metaDescription, $source ? $source['meta_description'] : '')) {
                $update['meta_description'] = '';
                $counts['meta_descriptions']++;
            }

            // 4) Slug depuis name (ou meta_title CMS)
            $slugSource = $hasName ? $name : $metaTitle;
            if ($slugSource !== '') {
                $expectedSlug = Tools::link_rewrite($slugSource);
                if ($expectedSlug !== '' && self::looksTruncated($slug, $expectedSlug)) {
                    $update['link_rewrite'] = pSQL($expectedSlug);
                    $counts['slugs']++;
                }
            }

            if (!$update) {
                continue;
            }

            $where = '`' . bqSQL($idColumn) . '` = ' . $id . ' AND id_lang = ' . (int) $idLang;
            if ($hasShop && $idShop > 0) {
                $where .= ' AND id_shop = ' . (int) $idShop;
            }
            Db::getInstance()->update($table, $update, $where);
        }

        return $counts;
    }

    /**
     * @return array{name?:string,meta_title:string,meta_description:string}|null
     */
    protected static function loadSourceRow($table, $idColumn, $id, $idSourceLang, $idShop, $hasShop, $hasName)
    {
        if ($idSourceLang <= 0) {
            return null;
        }

        $select = '`meta_title`, `meta_description`';
        if ($hasName) {
            $select = '`name`, ' . $select;
        }

        $sql = 'SELECT ' . $select . '
            FROM `' . _DB_PREFIX_ . bqSQL($table) . '`
            WHERE `' . bqSQL($idColumn) . '` = ' . (int) $id . '
              AND id_lang = ' . (int) $idSourceLang;
        if ($hasShop && $idShop > 0) {
            $sql .= ' AND id_shop = ' . (int) $idShop;
        }

        $row = Db::getInstance()->getRow($sql);
        if (!$row) {
            return null;
        }

        return array(
            'name' => $hasName ? (string) $row['name'] : '',
            'meta_title' => (string) $row['meta_title'],
            'meta_description' => (string) $row['meta_description'],
        );
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

        if ($curLen === self::BAD_MAX && strpos($expected, $current) === 0) {
            return true;
        }

        if ($curLen < self::BAD_MAX + 5 && strpos($expected, $current) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Détecte un libellé / meta coupé à ~10 car (Rigid Fenc, Gates & Ga…).
     *
     * @param string $text
     * @param string $sourceText
     */
    public static function looksTruncatedLabel($text, $sourceText = '')
    {
        $text = trim(html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8'));
        if ($text === '') {
            return false;
        }

        $len = Tools::strlen($text);
        $sourceLen = Tools::strlen(trim((string) $sourceText));

        // Coupe nette à l’ancien plafond
        if ($len === self::BAD_MAX) {
            if (preg_match('/(?:\s|&|&amp;|,|-)$/u', $text)) {
                return true;
            }
            // Dernier « mot » trop court : Fenc, Ga, Wir…
            if (preg_match('/(?:^|\s|&)([A-Za-z]{1,4})$/u', $text, $m)) {
                $tail = Tools::strtolower($m[1]);
                $whitelist = array('mesh', 'gate', 'wood', 'iron', 'post', 'wire', 'kit', 'pro', 'diy');
                if (!in_array($tail, $whitelist, true)) {
                    return true;
                }
            } elseif ($sourceLen >= 18) {
                // 10 car et source FR nettement plus longue, sans mot final whitelisté
                return true;
            }
        }

        // Trop court vs source FR (même logique pages système)
        if ($sourceLen > 0 && $len < max(12, (int) floor($sourceLen * 0.45))) {
            if (preg_match('/(?:\s|&|&amp;|\band\b|\bof\b|\bthe\b|,|-)$/iu', $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $slug
     */
    protected static function labelFromSlug($slug)
    {
        $slug = trim((string) $slug);
        if ($slug === '' || Tools::strlen($slug) <= self::BAD_MAX) {
            return '';
        }

        $label = str_replace('-', ' ', $slug);
        $label = Tools::strtolower($label);

        return Tools::ucfirst($label);
    }
}
