<?php
/**
 * CKC Vianney — aligne les liens catégories du menu EN sur l’URL canonique (200).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcMenuCategoryUrls
{
    public const FLAG = 'CKC_EN_MENU_CATEGORY_URLS_OK';

    /**
     * @param bool $dryRun
     *
     * @return array{replacements:int,rows:int,samples:array<int,array{from:string,to:string}>}
     */
    public static function apply($dryRun = false)
    {
        $result = array(
            'replacements' => 0,
            'rows' => 0,
            'samples' => array(),
        );

        $idLang = (int) Language::getIdByIso('en');
        if ($idLang <= 0 || !self::tableExists('ets_mm_block_lang')) {
            return $result;
        }

        $idShop = self::resolveShopId($idLang);
        if ($idShop <= 0) {
            return $result;
        }

        $paths = self::canonicalPaths($idLang, $idShop);
        if (!$paths) {
            return $result;
        }

        $targets = array(
            array('table' => 'ets_mm_block_lang', 'id' => 'id_block', 'fields' => array('content', 'title_link', 'image_link')),
            array('table' => 'ets_mm_menu_lang', 'id' => 'id_menu', 'fields' => array('link')),
            array('table' => 'ets_mm_tab_lang', 'id' => 'id_tab', 'fields' => array('url')),
        );

        foreach ($targets as $target) {
            if (!self::tableExists($target['table'])) {
                continue;
            }
            $idCol = $target['id'];
            $rows = Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . bqSQL($target['table']) . '` WHERE id_lang = ' . $idLang
            );
            if (!$rows) {
                continue;
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
                if (!$dryRun) {
                    Db::getInstance()->update(
                        $target['table'],
                        $update,
                        '`' . bqSQL($idCol) . '` = ' . (int) $row[$idCol] . ' AND id_lang = ' . $idLang
                    );
                }
            }
        }

        if (!$dryRun && $result['replacements'] > 0) {
            self::clearMenuCache();
            PrestaShopLogger::addLog(
                'CKC Vianney — menu EN : ' . (int) $result['replacements'] . ' liens catégories alignés sur l’URL canonique.',
                1
            );
        }

        return $result;
    }

    /**
     * @param array<int,string> $pathsById
     * @param array<int,array{from:string,to:string}> $samples
     *
     * @return array{value:string,count:int}
     */
    public static function rewriteField($value, array $pathsById, array &$samples = array())
    {
        if (strpos($value, '<') !== false) {
            $count = 0;
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

            return array('value' => $html === null ? $value : $html, 'count' => $count);
        }

        $next = self::rewriteHref($value, $pathsById);
        if ($next !== $value) {
            self::pushSample($samples, $value, $next);

            return array('value' => $next, 'count' => 1);
        }

        return array('value' => $value, 'count' => 0);
    }

    /**
     * @param array<int,string> $pathsById chemin canonique, ex. /en/107-welded-wir
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
            $rewrite = (string) $row['link_rewrite'];
            if ($rewrite === '') {
                continue;
            }
            $full = $context->link->getCategoryLink(
                (int) $row['id_category'],
                $rewrite,
                $idLang,
                null,
                $idShop
            );
            $path = parse_url((string) $full, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $paths[(int) $row['id_category']] = $path;
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

    /**
     * @param array<int,array{from:string,to:string}> $samples
     */
    protected static function pushSample(array &$samples, $from, $to)
    {
        if (count($samples) >= 20) {
            return;
        }
        $samples[] = array('from' => $from, 'to' => $to);
    }
}
