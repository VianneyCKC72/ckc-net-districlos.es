<?php
/**
 * CKC Vianney — répare slugs / noms / metas EN (troncatures, metas trop longues, marque).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcTranslateSlugRepair
{
    /** Ancien plafond accidentel (max(0, 10) après save BO vide). */
    public const BAD_MAX = 10;

    /** Meta description SEO cible (Google ~150–160). */
    public const META_DESC_TARGET = 155;

    /**
     * Catégories encore tronquées (audit home) — name/meta à retraduire.
     *
     * @var int[]
     */
    public static $forceCategoryIds = array(
        7, 216, 237, 239, 252, 255, 321, 324, 325, 337, 339, 345, 356, 438, 439, 441, 444,
    );

    /**
     * Produits encore tronqués (audit fiches).
     *
     * @var int[]
     */
    public static $forceProductIds = array(
        485, 638, 1223, 1228, 1267, 1285, 1302,
    );

    /**
     * @return array{
     *   categories:int,
     *   products:int,
     *   cms:int,
     *   names:int,
     *   meta_titles:int,
     *   meta_descriptions:int,
     *   config_fixed:bool,
     *   force_reset:int
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
            'force_reset' => 0,
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
            $idSourceLang,
            self::$forceCategoryIds
        );
        $prod = self::repairEntityLang(
            'product_lang',
            'id_product',
            $idLang,
            $idShop,
            true,
            true,
            $idSourceLang,
            self::$forceProductIds
        );
        $cms = self::repairEntityLang(
            'cms_lang',
            'id_cms',
            $idLang,
            $idShop,
            true,
            false,
            $idSourceLang,
            array()
        );

        $result['categories'] = $cat['slugs'];
        $result['products'] = $prod['slugs'];
        $result['cms'] = $cms['slugs'];
        $result['names'] = $cat['names'] + $prod['names'] + $cms['names'];
        $result['meta_titles'] = $cat['meta_titles'] + $prod['meta_titles'] + $cms['meta_titles'];
        $result['meta_descriptions'] = $cat['meta_descriptions'] + $prod['meta_descriptions'] + $cms['meta_descriptions'];
        $result['force_reset'] = $cat['force_reset'] + $prod['force_reset'] + $cms['force_reset'];

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
        if ($titleMax < 30 || $titleMax > 70) {
            Configuration::updateValue(CkcTranslate::CONF_SEO_META_TITLE_MAX, 60);
            $fixed = true;
        }

        $descMax = (int) Configuration::get(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX);
        // Trop bas (bug 10) ou trop haut (copie de description longue)
        if ($descMax < 50 || $descMax > 170) {
            Configuration::updateValue(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX, self::META_DESC_TARGET);
            $fixed = true;
        }

        $catPrompt = 'Translate category SEO fields for Districlos. name = complete category title (never truncate mid-word). meta_title = same as name or a close complete variant (max ~60 chars). meta_description = a DEDICATED SEO snippet ~150 characters (not the full category description), natural, one or two short sentences, brand Districlos only (never "Clôture Discount"). Always put a space after periods. slug = full kebab-case URL.';
        $prodPrompt = 'Translate product SEO fields for Districlos. name = complete product title (never truncate). meta_title = same as name or a close complete variant (max ~60 chars). meta_description = a DEDICATED SEO snippet ~150 characters (not the full product description), natural, brand Districlos only (never "Clôture Discount"). Always put a space after periods. slug = full kebab-case URL.';

        if ((string) Configuration::get(CkcTranslate::CONF_PROMPT_CATEGORY_SEO) !== $catPrompt) {
            Configuration::updateValue(CkcTranslate::CONF_PROMPT_CATEGORY_SEO, $catPrompt);
            $fixed = true;
        }
        if ((string) Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_SEO) !== $prodPrompt) {
            Configuration::updateValue(CkcTranslate::CONF_PROMPT_PRODUCT_SEO, $prodPrompt);
            $fixed = true;
        }

        return $fixed;
    }

    /**
     * @param int[] $forceIds
     *
     * @return array{slugs:int,names:int,meta_titles:int,meta_descriptions:int,force_reset:int}
     */
    protected static function repairEntityLang(
        $table,
        $idColumn,
        $idLang,
        $idShop,
        $hasShop,
        $hasName,
        $idSourceLang,
        array $forceIds
    ) {
        $counts = array(
            'slugs' => 0,
            'names' => 0,
            'meta_titles' => 0,
            'meta_descriptions' => 0,
            'force_reset' => 0,
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

        $forceMap = array_fill_keys(array_map('intval', $forceIds), true);

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $update = array();
            $name = $hasName ? trim((string) $row['name']) : '';
            $metaTitle = trim((string) $row['meta_title']);
            $metaDescription = trim((string) $row['meta_description']);
            $slug = trim((string) $row['link_rewrite']);
            $force = isset($forceMap[$id]);

            $source = self::loadSourceRow($table, $idColumn, $id, $idSourceLang, $idShop, $hasShop, $hasName);
            $sourceName = $source ? $source['name'] : '';
            $sourceMetaTitle = $source ? $source['meta_title'] : '';
            $sourceMetaDescription = $source ? $source['meta_description'] : '';

            // IDs audit : forcer la reprise SEO
            if ($force) {
                $nameTruncated = $hasName && (
                    $name === ''
                    || self::looksTruncatedLabel($name, $sourceName)
                    || (Tools::strlen($name) <= 12 && Tools::strlen($sourceName) >= 20)
                );

                if ($hasName && $nameTruncated) {
                    $update['name'] = '';
                    $name = '';
                    $counts['names']++;
                    $update['meta_title'] = '';
                    $metaTitle = '';
                    $counts['meta_titles']++;
                } elseif ($hasName && $name !== '' && $metaTitle !== $name) {
                    $update['meta_title'] = pSQL($name);
                    $metaTitle = $name;
                    $counts['meta_titles']++;
                } elseif (!$hasName) {
                    $update['meta_title'] = '';
                    $metaTitle = '';
                    $counts['meta_titles']++;
                }

                // Meta description : toujours à regénérer (~150 car dédiés)
                $update['meta_description'] = '';
                $metaDescription = '';
                $counts['meta_descriptions']++;
                $counts['force_reset']++;
            } else {
                // 1) Name tronqué
                if ($hasName && self::looksTruncatedLabel($name, $sourceName)) {
                    $fromSlug = self::labelFromSlug($slug);
                    if ($fromSlug !== '' && Tools::strlen($fromSlug) > Tools::strlen($name) + 2) {
                        $update['name'] = pSQL($fromSlug);
                        $name = $fromSlug;
                    } else {
                        $update['name'] = '';
                        $name = '';
                    }
                    $counts['names']++;
                }

                $nameOk = $hasName && $name !== '' && !self::looksTruncatedLabel($name, $sourceName);

                // 2) Meta title : recopier le H1/name dès qu'il est meilleur
                if ($nameOk && self::metaTitleShouldFollowName($metaTitle, $name, $sourceMetaTitle)) {
                    $update['meta_title'] = pSQL($name);
                    $metaTitle = $name;
                    $counts['meta_titles']++;
                } elseif (!$nameOk && self::looksTruncatedLabel($metaTitle, $sourceMetaTitle)) {
                    $update['meta_title'] = '';
                    $metaTitle = '';
                    $counts['meta_titles']++;
                }

                // 3) Meta description : tronquée → vide ; sinon polish (marque, espaces, longueur)
                if (self::looksTruncatedLabel($metaDescription, $sourceMetaDescription)
                    || self::looksTruncatedMetaDescription($metaDescription)
                ) {
                    $update['meta_description'] = '';
                    $metaDescription = '';
                    $counts['meta_descriptions']++;
                } else {
                    $polished = self::polishMetaDescription($metaDescription);
                    if ($polished !== $metaDescription) {
                        $update['meta_description'] = pSQL($polished);
                        $metaDescription = $polished;
                        $counts['meta_descriptions']++;
                    }
                }
            }

            // 4) Slug depuis name (ou meta_title CMS)
            $slugSource = $hasName ? $name : $metaTitle;
            if ($slugSource !== '') {
                $expectedSlug = Tools::link_rewrite($slugSource);
                if ($expectedSlug !== '' && ($force || self::looksTruncated($slug, $expectedSlug))) {
                    if ($expectedSlug !== $slug) {
                        $update['link_rewrite'] = pSQL($expectedSlug);
                        $counts['slugs']++;
                    }
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
     * Meta title doit suivre le H1 quand le H1 est complet et plus fiable.
     */
    public static function metaTitleShouldFollowName($metaTitle, $name, $sourceMetaTitle = '')
    {
        $metaTitle = trim((string) $metaTitle);
        $name = trim((string) $name);
        if ($name === '') {
            return false;
        }
        if ($metaTitle === $name) {
            return false;
        }
        if ($metaTitle === '' || self::looksTruncatedLabel($metaTitle, $sourceMetaTitle)) {
            return true;
        }
        // Prefixe / version courte du H1 (Fence Post vs Fence Posts Hot-Dip…)
        if (Tools::strlen($name) > Tools::strlen($metaTitle) + 2) {
            return true;
        }
        // H1 différent et plus long (fiches produit)
        if (Tools::strtolower($metaTitle) !== Tools::strtolower($name)
            && Tools::strlen($name) >= Tools::strlen($metaTitle)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Descriptions encore clairement coupées (Shop durab, Durable T…).
     */
    public static function looksTruncatedMetaDescription($text)
    {
        $text = trim(html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8'));
        if ($text === '') {
            return false;
        }
        $len = Tools::strlen($text);
        if ($len <= 20) {
            return true;
        }
        if ($len <= 40 && preg_match('/(?:\s|&|-|T)$/u', $text)) {
            return true;
        }
        if (preg_match('/\b(durab|flex|essenti|versatil|graduat|swing gate)$/iu', $text)) {
            return true;
        }

        return false;
    }

    /**
     * Nettoie marque + phrases collées + longueur SEO.
     */
    public static function polishMetaDescription($text)
    {
        $text = trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES, 'UTF-8'));
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s+/u', ' ', $text);
        // « long-lasting.At Districlos » → espace après le point
        $text = preg_replace('/\.([A-Za-z])/u', '. $1', $text);
        $text = preg_replace('/\?(?=[A-Za-z])/u', '? ', $text);
        $text = preg_replace('/!(?=[A-Za-z])/u', '! ', $text);
        $text = str_ireplace(
            array('Clôture Discount', 'Cloture Discount', 'clôture discount', 'cloture discount'),
            'Districlos',
            $text
        );
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string) $text);

        $max = self::META_DESC_TARGET;
        if (Tools::strlen($text) > $max) {
            $cut = Tools::substr($text, 0, $max);
            // Coupe à la fin de phrase si possible
            if (preg_match('/^(.*?[\.\!\?])\s+/u', $cut, $m) && Tools::strlen($m[1]) >= 80) {
                $text = trim($m[1]);
            } elseif (preg_match('/^(.*)\s+\S*$/u', $cut, $m) && trim($m[1]) !== '') {
                $text = rtrim(trim($m[1]), '-,:;');
            } else {
                $text = rtrim($cut, '-,:;');
            }
        }

        return $text;
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
     * Détecte un libellé / meta coupé (Rigid Fenc, Artificial, Composite…).
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
        $source = trim(html_entity_decode((string) $sourceText, ENT_QUOTES, 'UTF-8'));
        $sourceLen = Tools::strlen($source);
        $wordCount = preg_match_all('/[\p{L}\p{N}]+/u', $text);

        // Coupe nette à l’ancien plafond
        if ($len === self::BAD_MAX) {
            if (preg_match('/(?:\s|&|&amp;|,|-)$/u', $text)) {
                return true;
            }
            if (preg_match('/(?:^|\s|&)([A-Za-z]{1,4})$/u', $text, $m)) {
                $tail = Tools::strtolower($m[1]);
                $whitelist = array('mesh', 'gate', 'wood', 'iron', 'post', 'wire', 'kit', 'pro', 'diy');
                if (!in_array($tail, $whitelist, true)) {
                    return true;
                }
            } elseif ($sourceLen >= 18) {
                return true;
            }
        }

        // Un seul mot EN alors que la source FR en a plusieurs (Artificial, Composite, Motorized…)
        if ($wordCount === 1 && $len <= 12 && $sourceLen >= 12) {
            $sourceWords = preg_match_all('/[\p{L}\p{N}]+/u', $source);
            if ($sourceWords >= 2) {
                return true;
            }
        }

        // Trop court vs source FR (évite de casser Tools / Gabion / Wood Fence)
        if ($sourceLen > 0 && $len < max(12, (int) floor($sourceLen * 0.45))) {
            if ($wordCount === 1 || preg_match('/(?:\s|&|&amp;|\band\b|\bof\b|\bthe\b|,|-)$/iu', $text)) {
                return true;
            }
        }

        if (preg_match('/(?:\s|&|&amp;|\band\b|\bof\b|\bthe\b|,|-)$/iu', $text)) {
            return true;
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
