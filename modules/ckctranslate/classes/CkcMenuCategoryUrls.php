<?php
/**
 * CKC Vianney — remplace les slugs FR encore présents dans les contenus EN (home CE, menus).
 * Whitelist exacte uniquement (jamais de réécriture générique id→catégorie : casse les URLs produit).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcMenuCategoryUrls
{
    public const FLAG = 'CKC_EN_MENU_CATEGORY_URLS_OK';

    /**
     * Slugs EN canoniques (category_lang) — audit home 2026-09-28.
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
     * Chemins FR (302) → EN (200). Remplacement exact dans les contenus.
     *
     * @var array<string,string>
     */
    public static $exactPathMap = array(
        '/en/3-cloture-rigide' => '/en/3-rigid-fence-panels',
        '/en/2-grillage-souple' => '/en/2-flexible-mesh-fencing',
        '/en/4-portail-et-portillon' => '/en/4-gates-garden-gates',
        '/en/4-portillon-portail' => '/en/4-gates-garden-gates',
        '/en/6-brise-vue' => '/en/6-privacy-screening',
        '/en/215-cloture-aluminium' => '/en/215-aluminum-fencing',
        '/en/130-portillons' => '/en/130-garden-gates',
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

        $targets = array(
            array('table' => 'ets_mm_block_lang', 'id' => 'id_block', 'fields' => array('content', 'title_link', 'image_link'), 'lang' => true),
            array('table' => 'ets_mm_menu_lang', 'id' => 'id_menu', 'fields' => array('link'), 'lang' => true),
            array('table' => 'ets_mm_tab_lang', 'id' => 'id_tab', 'fields' => array('url'), 'lang' => true),
            array('table' => 'cms_lang', 'id' => 'id_cms', 'fields' => array('content'), 'lang' => true, 'shop' => true),
        );

        foreach ($targets as $target) {
            self::rewriteTable($target, $idLang, $idShop, $result, $dryRun);
        }

        // CE : toutes les rows contenant un chemin /en/… FR (préfixe /en/ = URL EN, pas de filtre UID)
        self::rewriteCeMeta($result, $dryRun);
        self::rewriteLinkList($idLang, $idShop, $result, $dryRun);

        if (!$dryRun && ($result['replacements'] > 0 || $result['slugs'] > 0)) {
            self::clearMenuCache();
            self::clearCeElementCache();
            PrestaShopLogger::addLog(
                'CKC Vianney — liens EN whitelist : ' . (int) $result['replacements']
                . ' remplacements, ' . (int) $result['slugs'] . ' slugs catégories.',
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
     * @param array{replacements:int,rows:int,slugs:int,samples:array} $result
     */
    protected static function rewriteTable(array $target, $idLang, $idShop, array &$result, $dryRun)
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
                $rewritten = self::rewriteField((string) $row[$field], $result['samples']);
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
     * @param array{replacements:int,rows:int,slugs:int,samples:array} $result
     */
    protected static function rewriteCeMeta(array &$result, $dryRun)
    {
        if (!self::tableExists('ce_meta')) {
            return;
        }

        $likes = array();
        foreach (array_keys(self::$exactPathMap) as $from) {
            $likes[] = '`value` LIKE \'%' . pSQL($from) . '%\'';
            $likes[] = '`value` LIKE \'%' . pSQL(str_replace('/', '\\/', $from)) . '%\'';
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id`, `name`, `value` FROM `' . _DB_PREFIX_ . 'ce_meta`
            WHERE (' . implode(' OR ', $likes) . ')
              AND `name` NOT IN (\'_ce_element_cache\', \'_elementor_css\')'
        );
        if (!$rows) {
            return;
        }

        foreach ($rows as $row) {
            $rewritten = self::rewriteField((string) $row['value'], $result['samples']);
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
     * @param array{replacements:int,rows:int,slugs:int,samples:array} $result
     */
    protected static function rewriteLinkList($idLang, $idShop, array &$result, $dryRun)
    {
        $candidates = array(
            array('table' => 'link_block', 'id' => 'id_link_block', 'fields' => array('content'), 'lang' => false),
            array('table' => 'link_block_lang', 'id' => 'id_link_block', 'fields' => array('title', 'custom_url'), 'lang' => true),
        );
        foreach ($candidates as $target) {
            if (!self::tableExists($target['table'])) {
                continue;
            }
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
            self::rewriteTable($target, $idLang, $idShop, $result, $dryRun);
        }
    }

    /**
     * Remplacement exact whitelist (forme /en/… et \/en\/…).
     *
     * @param array<int,array{from:string,to:string}> $samples
     *
     * @return array{value:string,count:int}
     */
    public static function rewriteField($value, array &$samples = array())
    {
        $count = 0;
        foreach (self::$exactPathMap as $from => $to) {
            $n = substr_count($value, $from);
            if ($n > 0) {
                $value = str_replace($from, $to, $value);
                $count += $n;
                self::pushSample($samples, $from, $to);
            }
            $fromEsc = str_replace('/', '\\/', $from);
            $toEsc = str_replace('/', '\\/', $to);
            $nEsc = substr_count($value, $fromEsc);
            if ($nEsc > 0) {
                $value = str_replace($fromEsc, $toEsc, $value);
                $count += $nEsc;
                self::pushSample($samples, $from, $to);
            }
        }

        return array('value' => $value, 'count' => $count);
    }

    /**
     * Whitelist only (script de verif).
     *
     * @param array<int,string> $pathsById ignore
     */
    public static function rewriteHref($url, array $pathsById = array())
    {
        unset($pathsById);
        $url = trim((string) $url);
        if ($url === '') {
            return $url;
        }
        if (!preg_match('~^(?P<pre>https?://[^/]+)?(?P<path>/[^?#]*)(?P<tail>[?#].*)?$~i', $url, $parts)) {
            return $url;
        }
        foreach (self::$exactPathMap as $from => $to) {
            if ($parts['path'] === $from) {
                return (isset($parts['pre']) ? $parts['pre'] : '') . $to . (isset($parts['tail']) ? $parts['tail'] : '');
            }
        }

        return $url;
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
        if (count($samples) >= 40) {
            return;
        }
        foreach ($samples as $s) {
            if ($s['from'] === $from && $s['to'] === $to) {
                return;
            }
        }
        $samples[] = array('from' => $from, 'to' => $to);
    }
}
