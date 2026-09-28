<?php
/**
 * CKC Vianney — aligne les liens catégories EN (menu, home CE, contenus) sur l’URL 200.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcMenuCategoryUrls
{
    public const FLAG = 'CKC_EN_MENU_CATEGORY_URLS_OK';

    /**
     * Slugs EN canoniques (audit home 2026-09-28 — liens FR en 302).
     *
     * @var array<int,string>
     */
    public static $forcedEnSlugs = array(
        2 => 'flexible-mesh-fencing',
        3 => 'rigid-fence-panels',
        4 => 'gates-garden-gates',
        6 => 'privacy-screening',
        130 => 'garden-gates',
        215 => 'aluminum-fencing',
    );

    /**
     * @param bool $dryRun
     *
     * @return array{replacements:int,rows:int,slugs:int,samples:array<int,array{from:string,to:string}>}
     */
    public static function apply($dryRun = false)
    {
        $result = array(
            'replacements' => 0,
            'rows' => 0,
            'slugs' => 0,
            'samples' => array(),
        );

        $idLang = (int) Language::getIdByIso('en');
        if ($idLang <= 0) {
            return $result;
        }

        $idShop = self::resolveShopId($idLang);
        if ($idShop <= 0) {
            return $result;
        }

        $result['slugs'] = self::applyForcedSlugs($idLang, $idShop, $dryRun);

        $paths = self::canonicalPaths($idLang, $idShop);
        if (!$paths) {
            return $result;
        }

        $targets = array(
            array('table' => 'ets_mm_block_lang', 'id' => 'id_block', 'fields' => array('content', 'title_link', 'image_link'), 'lang' => true),
            array('table' => 'ets_mm_menu_lang', 'id' => 'id_menu', 'fields' => array('link'), 'lang' => true),
            array('table' => 'ets_mm_tab_lang', 'id' => 'id_tab', 'fields' => array('url'), 'lang' => true),
            array('table' => 'cms_lang', 'id' => 'id_cms', 'fields' => array('content'), 'lang' => true, 'shop' => true),
        );

        foreach ($targets as $target) {
            self::rewriteTable($target, $idLang, $idShop, $paths, $result, $dryRun);
        }

        // Creative Elements : JSON Elementor (home EN, footer CE, etc.)
        self::rewriteCeMeta($paths, $result, $dryRun);

        // Linklist custom URLs (si table présente)
        self::rewriteLinkList($idLang, $idShop, $paths, $result, $dryRun);

        if (!$dryRun && ($result['replacements'] > 0 || $result['slugs'] > 0)) {
            self::clearMenuCache();
            self::clearCeElementCache();
            PrestaShopLogger::addLog(
                'CKC Vianney — liens EN : ' . (int) $result['replacements']
                . ' remplacements, ' . (int) $result['slugs'] . ' slugs catégories forcés.',
                1
            );
        }

        return $result;
    }

    /**
     * @return int
     */
    protected static function applyForcedSlugs($idLang, $idShop, $dryRun)
    {
        $updated = 0;
        foreach (self::$forcedEnSlugs as $idCategory => $slug) {
            $current = (string) Db::getInstance()->getValue(
                'SELECT link_rewrite FROM `' . _DB_PREFIX_ . 'category_lang`
                WHERE id_category = ' . (int) $idCategory . '
                  AND id_lang = ' . (int) $idLang . '
                  AND id_shop = ' . (int) $idShop
            );
            if ($current === $slug) {
                continue;
            }
            $updated++;
            if (!$dryRun) {
                Db::getInstance()->update(
                    'category_lang',
                    array('link_rewrite' => pSQL($slug)),
                    'id_category = ' . (int) $idCategory
                    . ' AND id_lang = ' . (int) $idLang
                    . ' AND id_shop = ' . (int) $idShop
                );
            }
        }

        return $updated;
    }

    /**
     * @param array{table:string,id:string,fields:array<int,string>,lang?:bool,shop?:bool} $target
     * @param array<int,string> $paths
     * @param array{replacements:int,rows:int,slugs:int,samples:array} $result
     */
    protected static function rewriteTable(array $target, $idLang, $idShop, array $paths, array &$result, $dryRun)
    {
        if (!self::tableExists($target['table'])) {
            return;
        }

        $idCol = $target['id'];
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . bqSQL($target['table']) . '` WHERE 1';
        if (!empty($target['lang'])) {
            $sql .= ' AND id_lang = ' . (int) $idLang;
        }
        if (!empty($target['shop'])) {
            $sql .= ' AND id_shop = ' . (int) $idShop;
        }

        $rows = Db::getInstance()->executeS($sql);
        if (!$rows) {
            return;
        }

        foreach ($rows as $row) {
            $update = array();
            foreach ($target['fields'] as $field) {
                if (!array_key_exists($field, $row) || $row[$field] === null || $row[$field] === '') {
                    continue;
                }
                $rewritten = self::rewriteField((string) $row[$field], $paths, $result['samples']);
                if ($rewritten['count'] > 0 && $rewritten['value'] !== $row[$field]) {
                    $update[$field] = pSQL($rewritten['value'], true);
                    $result['replacements'] += $rewritten['count'];
                }
            }
            if (!$update) {
                continue;
            }
            $result['rows']++;
            if ($dryRun) {
                continue;
            }
            $where = '`' . bqSQL($idCol) . '` = ' . (int) $row[$idCol];
            if (!empty($target['lang'])) {
                $where .= ' AND id_lang = ' . (int) $idLang;
            }
            if (!empty($target['shop']) && isset($row['id_shop'])) {
                $where .= ' AND id_shop = ' . (int) $row['id_shop'];
            }
            Db::getInstance()->update($target['table'], $update, $where);
        }
    }

    /**
     * @param array<int,string> $paths
     * @param array{replacements:int,rows:int,slugs:int,samples:array} $result
     */
    protected static function rewriteCeMeta(array $paths, array &$result, $dryRun)
    {
        if (!self::tableExists('ce_meta')) {
            return;
        }

        $idLang = (int) Language::getIdByIso('en');
        if ($idLang <= 0) {
            return;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id`, `name`, `value` FROM `' . _DB_PREFIX_ . 'ce_meta`
            WHERE `value` LIKE \'%/en/%\'
               OR `value` LIKE \'%\\\\/en\\\\/%\'
               OR `value` LIKE \'%cloture%\'
               OR `value` LIKE \'%grillage%\'
               OR `value` LIKE \'%portail%\'
               OR `value` LIKE \'%portillon%\'
               OR `value` LIKE \'%brise-vue%\''
        );
        if (!$rows) {
            return;
        }

        foreach ($rows as $row) {
            // UID CE : …{type:2}{lang:2}{shop:2} — ne toucher que la langue EN
            $uid = (string) $row['id'];
            if (Tools::strlen($uid) > 6) {
                $uidLang = (int) Tools::substr($uid, -4, 2);
                if ($uidLang !== $idLang) {
                    continue;
                }
            }

            $rewritten = self::rewriteField((string) $row['value'], $paths, $result['samples']);
            if ($rewritten['count'] <= 0 || $rewritten['value'] === $row['value']) {
                continue;
            }
            $result['replacements'] += $rewritten['count'];
            $result['rows']++;
            if ($dryRun) {
                continue;
            }
            Db::getInstance()->update(
                'ce_meta',
                array('value' => pSQL($rewritten['value'], true)),
                '`id` = \'' . pSQL((string) $row['id']) . '\' AND `name` = \'' . pSQL($row['name']) . '\''
            );
        }
    }

    /**
     * @param array<int,string> $paths
     * @param array{replacements:int,rows:int,slugs:int,samples:array} $result
     */
    protected static function rewriteLinkList($idLang, $idShop, array $paths, array &$result, $dryRun)
    {
        // PS 8 linklist : URLs custom parfois en JSON dans link_block
        $candidates = array(
            array('table' => 'link_block', 'id' => 'id_link_block', 'fields' => array('content'), 'lang' => false),
            array('table' => 'link_block_lang', 'id' => 'id_link_block', 'fields' => array('title', 'custom_url'), 'lang' => true),
        );
        foreach ($candidates as $target) {
            if (!self::tableExists($target['table'])) {
                continue;
            }
            // Vérifier que les colonnes existent avant SELECT *
            $cols = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . bqSQL($target['table']) . '`');
            $colNames = array();
            foreach ($cols ?: array() as $col) {
                $colNames[] = $col['Field'];
            }
            $fields = array_values(array_intersect($target['fields'], $colNames));
            if (!$fields) {
                continue;
            }
            $target['fields'] = $fields;
            self::rewriteTable($target, $idLang, $idShop, $paths, $result, $dryRun);
        }
    }

    /**
     * @param array<int,string> $pathsById
     * @param array<int,array{from:string,to:string}> $samples
     *
     * @return array{value:string,count:int}
     */
    public static function rewriteField($value, array $pathsById, array &$samples = array())
    {
        $count = 0;

        // HTML href="..."
        if (stripos($value, 'href') !== false) {
            $html = preg_replace_callback(
                '#\bhref\s*=\s*(["\'])([^"\']*)\1#i',
                function (array $match) use ($pathsById, &$count, &$samples) {
                    $next = self::rewriteHref($match[2], $pathsById);
                    if ($next !== $match[2]) {
                        $count++;
                        self::pushSample($samples, $match[2], $next);
                    }

                    return 'href=' . $match[1] . $next . $match[1];
                },
                $value
            );
            if (is_string($html)) {
                $value = $html;
            }
        }

        // Chemins catégorie partout (texte, JSON CE avec \/ échappé)
        $json = preg_replace_callback(
            '#(?P<pre>https?://[^/\\\\\"\'\s]*)?(?P<path>(?:\\\\?/)+(?:[a-z]{2}(?:\\\\?/))?(?P<id>\d+)-(?P<slug>[A-Za-z0-9\-]+))#',
            function (array $match) use ($pathsById, &$count, &$samples) {
                $escaped = strpos($match['path'], '\\/') !== false;
                $path = str_replace('\\/', '/', $match['path']);
                $path = '/' . ltrim($path, '/');
                $next = self::rewriteHref($path, $pathsById);
                if ($next === $path) {
                    return $match[0];
                }
                $count++;
                self::pushSample($samples, $path, $next);
                if ($escaped) {
                    $next = str_replace('/', '\\/', $next);
                }

                return (isset($match['pre']) ? $match['pre'] : '') . $next;
            },
            $value
        );
        if (is_string($json)) {
            $value = $json;
        }

        // Valeur = URL seule
        if ($count === 0 && preg_match('#^https?://|^/#', trim($value))) {
            $next = self::rewriteHref(trim($value), $pathsById);
            if ($next !== trim($value)) {
                self::pushSample($samples, trim($value), $next);

                return array('value' => $next, 'count' => 1);
            }
        }

        return array('value' => $value, 'count' => $count);
    }

    /**
     * @param array<int,string> $pathsById chemin canonique, ex. /en/3-rigid-fence-panels
     */
    public static function rewriteHref($url, array $pathsById)
    {
        $url = trim((string) $url);
        if ($url === '' || $url[0] === '#') {
            return $url;
        }

        if (!preg_match('~^(?P<pre>https?://[^/]+)?(?P<path>/[^?#]*)(?P<tail>[?#].*)?$~i', $url, $parts)) {
            return $url;
        }

        if (!preg_match('#^/(?:[a-z]{2}/)?(\d+)-([^/]+)$#i', $parts['path'], $cat)) {
            return $url;
        }

        if (strpos($cat[2], '.') !== false) {
            return $url;
        }

        $id = (int) $cat[1];
        if ($id <= 0 || !isset($pathsById[$id])) {
            return $url;
        }

        $newPath = $pathsById[$id];
        if ($newPath === $parts['path']) {
            return $url;
        }

        return (isset($parts['pre']) ? $parts['pre'] : '') . $newPath . (isset($parts['tail']) ? $parts['tail'] : '');
    }

    /**
     * @return array<int,string>
     */
    protected static function canonicalPaths($idLang, $idShop)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT cl.id_category, cl.link_rewrite
            FROM `' . _DB_PREFIX_ . 'category_lang` cl
            INNER JOIN `' . _DB_PREFIX_ . 'category` c ON c.id_category = cl.id_category
            WHERE cl.id_lang = ' . (int) $idLang . ' AND cl.id_shop = ' . (int) $idShop
        );
        if (!$rows) {
            return array();
        }

        $context = Context::getContext();
        if (!$context->link) {
            $context->link = new Link();
        }

        $paths = array();
        foreach ($rows as $row) {
            $idCategory = (int) $row['id_category'];
            // Priorité aux slugs EN forcés (audit home), même en dry-run
            $rewrite = isset(self::$forcedEnSlugs[$idCategory])
                ? self::$forcedEnSlugs[$idCategory]
                : (string) $row['link_rewrite'];
            if ($rewrite === '') {
                continue;
            }
            $full = $context->link->getCategoryLink(
                $idCategory,
                $rewrite,
                $idLang,
                null,
                $idShop
            );
            $path = parse_url((string) $full, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $paths[$idCategory] = $path;
            }
        }

        // Garantir les IDs audit même absents de category_lang
        $iso = (string) Language::getIsoById($idLang);
        foreach (self::$forcedEnSlugs as $idCategory => $slug) {
            if (!isset($paths[$idCategory]) && $iso !== '') {
                $paths[(int) $idCategory] = '/' . $iso . '/' . (int) $idCategory . '-' . $slug;
            }
        }

        return $paths;
    }

    protected static function resolveShopId($idLang)
    {
        $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
        if ($idShop > 0 && self::shopHasLang($idShop, $idLang)) {
            return $idShop;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT id_shop, COUNT(*) AS n
            FROM `' . _DB_PREFIX_ . 'category_lang`
            WHERE id_lang = ' . (int) $idLang . '
            GROUP BY id_shop
            ORDER BY n DESC'
        );
        if (!empty($rows[0]['id_shop'])) {
            return (int) $rows[0]['id_shop'];
        }

        return $idShop;
    }

    protected static function shopHasLang($idShop, $idLang)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT id_category FROM `' . _DB_PREFIX_ . 'category_lang`
            WHERE id_shop = ' . (int) $idShop . ' AND id_lang = ' . (int) $idLang
        ) > 0;
    }

    protected static function tableExists($table)
    {
        $name = _DB_PREFIX_ . $table;
        $found = Db::getInstance()->getValue(
            'SELECT TABLE_NAME FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'' . pSQL($name) . '\''
        );

        return $found === $name;
    }

    protected static function clearMenuCache()
    {
        $dir = _PS_MODULE_DIR_ . 'ets_megamenu/cache/';
        foreach (glob($dir . 'cache.*') ?: array() as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    protected static function clearCeElementCache()
    {
        if (!self::tableExists('ce_meta')) {
            return;
        }
        Db::getInstance()->delete('ce_meta', '`name` = \'_ce_element_cache\'');
    }

    /**
     * @param array<int,array{from:string,to:string}> $samples
     */
    protected static function pushSample(array &$samples, $from, $to)
    {
        if (count($samples) >= 30) {
            return;
        }
        $samples[] = array('from' => $from, 'to' => $to);
    }
}
