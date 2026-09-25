<?php
/**
 * CKC — réécrit les liens internes des contenus EN vers l’URL EN (préfixe + slug traduit).
 *
 * Dry-run (défaut, aucune écriture) :
 *   php scripts/rewrite_lang_links.php
 *
 * Écriture :
 *   php scripts/rewrite_lang_links.php --apply
 *
 * Options :
 *   --iso=en              langue cible (défaut en)
 *   --shops=1,2           boutiques (défaut : toutes les boutiques actives)
 *   --sources=cms,product,product_short,category,ce
 *                         cms = cms_lang.content
 *                         product = product_lang.description
 *                         product_short = product_lang.description_short
 *                         category = category_lang.description + additional_description
 *                         ce = ce_content_lang.content (JSON : href et champs url)
 *   --cms=12,34           limiter à ces pages CMS
 *   --product=56          limiter à ces produits
 *   --category=3,12       limiter à ces catégories
 *   --limit=20            nombre max de fiches lues (test)
 *   --debug               aucune écriture : affiche chaque lien et son code HTTP (curl)
 *
 * Rapport CSV : var/logs/rewrite_lang_links_*.csv
 * Avec --apply, un JSONL de rollback est écrit à côté (contenu avant modification).
 * Ensuite : vider le cache Smarty.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI uniquement.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.inc.php';

final class CkcRewriteLangLinks
{
    private const BATCH = 40;

    private const SKIP_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'css', 'js', 'pdf', 'zip',
        'xml', 'mp4', 'webm', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'map',
    ];

    private const DROP_QUERY_KEYS = [
        'controller', 'id_product', 'id_product_attribute', 'id_category', 'id_cms',
        'id_cms_category', 'id_manufacturer', 'id_supplier', 'id_lang', 'isolang',
        'rewrite', 'fc', 'module',
    ];

    /** @var array<string, int> */
    private array $isoToLang = [];

    /** @var array<int, string> */
    private array $langToIso = [];

    /** @var array<string, int> host => id_shop */
    private array $hostToShop = [];

    /** @var array<int, true> */
    private array $disabledCategoryIds = [];

    private int $defaultLangId = 0;

    private int $targetLangId = 0;

    private bool $removeDefaultLangCode = false;

    private Link $link;

    private Dispatcher $dispatcher;

    /** @var array<int, array<string, array<string, int[]>>> */
    private array $slugIndex = [];

    /** @var array<int, array<int, int>> */
    private array $categoryParent = [];

    /** @var array<int, array<int, array<int, string>>> shop => lang => id => rewrite */
    private array $categoryRewrite = [];

    /** @var array<int, array<string, array<int, string>>> shop => lang => rewrite => page */
    private array $metaPages = [];

    /** @var array<string, string> */
    private array $urlCache = [];

    /** @var array<string, bool> */
    private array $productActive = [];

    /** @var array<string, int> */
    private array $stats = [];

    /** @var resource */
    private $csv;

    private string $csvPath = '';

    /** @var resource|null */
    private $undo;

    private string $undoPath = '';

    private bool $apply = false;

    private bool $debug = false;

    /** @var array<int, string> */
    private array $shopBase = [];

    /** @var array<string, mixed> */
    private $options = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
        $this->debug = !empty($options['debug']);
        $this->apply = !empty($options['apply']) && !$this->debug;
        $this->link = Context::getContext()->link;
        $this->dispatcher = Dispatcher::getInstance();
        $this->defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');
        $this->removeDefaultLangCode = (bool) Configuration::get('ETS_AWU_ENABLE_REMOVE_LANG_CODE_IN_URL')
            && Language::isMultiLanguageActivated();

        foreach (Language::getLanguages(false) as $lang) {
            $id = (int) $lang['id_lang'];
            $iso = strtolower((string) $lang['iso_code']);
            $this->isoToLang[$iso] = $id;
            $this->langToIso[$id] = $iso;
        }

        $iso = strtolower((string) $this->options['iso']);
        if (!isset($this->isoToLang[$iso])) {
            throw new RuntimeException('Langue introuvable : ' . $iso);
        }
        $this->targetLangId = $this->isoToLang[$iso];

        $home = (int) Configuration::get('PS_HOME_CATEGORY');
        $root = (int) Configuration::get('PS_ROOT_CATEGORY');
        $this->disabledCategoryIds = array_fill_keys(array_filter([$home, $root]), true);

        foreach (Shop::getShops(false) as $shop) {
            $idShop = (int) $shop['id_shop'];
            foreach (['domain', 'domain_ssl'] as $field) {
                $host = strtolower((string) ($shop[$field] ?? ''));
                if ($host !== '') {
                    $this->hostToShop[$host] = $idShop;
                }
            }
        }
    }

    public function run(): int
    {
        $this->openReport();
        $sources = $this->options['sources'];
        if ($this->debug) {
            echo "MODE DEBUG (aucune écriture, contrôle HTTP)\n";
            if (!empty($this->options['apply'])) {
                echo "--apply ignoré tant que --debug est présent.\n";
            }
        } else {
            echo ($this->apply ? 'MODE ÉCRITURE' : 'DRY-RUN (aucune écriture)') . "\n";
        }
        echo 'Langue cible : ' . $this->langToIso[$this->targetLangId] . ' (id ' . $this->targetLangId . ")\n";
        echo 'Rapport : ' . $this->csvPath . "\n";

        foreach ($this->options['shops'] as $idShop) {
            $this->prepareShop($idShop);
            if (in_array('cms', $sources, true)) {
                $this->processTable(
                    'cms',
                    'cms_lang',
                    'id_cms',
                    'content',
                    $idShop,
                    $this->options['cms_ids']
                );
            }
            if (in_array('product', $sources, true)) {
                $this->processTable(
                    'product',
                    'product_lang',
                    'id_product',
                    'description',
                    $idShop,
                    $this->options['product_ids']
                );
            }
            if (in_array('product_short', $sources, true)) {
                $this->processTable(
                    'product_short',
                    'product_lang',
                    'id_product',
                    'description_short',
                    $idShop,
                    $this->options['product_ids']
                );
            }
            if (in_array('category', $sources, true)) {
                $this->processTable(
                    'category',
                    'category_lang',
                    'id_category',
                    'description',
                    $idShop,
                    $this->options['category_ids']
                );
                $this->processTable(
                    'category_add',
                    'category_lang',
                    'id_category',
                    'additional_description',
                    $idShop,
                    $this->options['category_ids']
                );
            }
            if (in_array('ce', $sources, true)) {
                $this->processTable(
                    'ce',
                    'ce_content_lang',
                    'id_ce_content',
                    'content',
                    $idShop,
                    $this->options['ce_ids'],
                    'ce'
                );
            }
        }

        $this->closeReport();
        $this->printStats();

        return 0;
    }

    private function prepareShop(int $idShop): void
    {
        Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
        $context = Context::getContext();
        $context->shop = new Shop($idShop);
        $context->language = new Language($this->targetLangId);
        if (method_exists($this->dispatcher, 'publicLoadRoutes')) {
            $this->dispatcher->publicLoadRoutes();
        }

        $this->slugIndex[$idShop] = [
            'product' => [],
            'category' => [],
            'cms' => [],
            'cms_category' => [],
            'manufacturer' => [],
            'supplier' => [],
        ];
        $this->categoryParent[$idShop] = [];
        $this->categoryRewrite[$idShop] = [];
        $this->metaPages[$idShop] = [];

        $prefix = _DB_PREFIX_;
        $shopSql = (int) $idShop;

        $products = Db::getInstance()->executeS(
            'SELECT pl.id_product, pl.id_lang, pl.link_rewrite
            FROM `' . $prefix . 'product_lang` pl
            INNER JOIN `' . $prefix . 'product_shop` ps
                ON ps.id_product = pl.id_product AND ps.id_shop = pl.id_shop
            WHERE pl.id_shop = ' . $shopSql . ' AND pl.link_rewrite != \'\''
        ) ?: [];
        foreach ($products as $row) {
            $this->addSlug($idShop, 'product', (int) $row['id_lang'], (string) $row['link_rewrite'], (int) $row['id_product']);
        }

        $categories = Db::getInstance()->executeS(
            'SELECT cl.id_category, cl.id_lang, cl.link_rewrite, c.id_parent
            FROM `' . $prefix . 'category_lang` cl
            INNER JOIN `' . $prefix . 'category` c ON c.id_category = cl.id_category
            INNER JOIN `' . $prefix . 'category_shop` cs
                ON cs.id_category = cl.id_category AND cs.id_shop = cl.id_shop
            WHERE cl.id_shop = ' . $shopSql . ' AND cl.link_rewrite != \'\''
        ) ?: [];
        foreach ($categories as $row) {
            $id = (int) $row['id_category'];
            $idLang = (int) $row['id_lang'];
            $this->categoryParent[$idShop][$id] = (int) $row['id_parent'];
            $this->categoryRewrite[$idShop][$idLang][$id] = (string) $row['link_rewrite'];
            $this->addSlug($idShop, 'category', $idLang, (string) $row['link_rewrite'], $id);
        }

        $cms = Db::getInstance()->executeS(
            'SELECT id_cms, id_lang, link_rewrite
            FROM `' . $prefix . 'cms_lang`
            WHERE id_shop = ' . $shopSql . ' AND link_rewrite != \'\''
        ) ?: [];
        foreach ($cms as $row) {
            $this->addSlug($idShop, 'cms', (int) $row['id_lang'], (string) $row['link_rewrite'], (int) $row['id_cms']);
        }

        $cmsCategories = Db::getInstance()->executeS(
            'SELECT id_cms_category, id_lang, link_rewrite
            FROM `' . $prefix . 'cms_category_lang`
            WHERE id_shop = ' . $shopSql . ' AND link_rewrite != \'\''
        ) ?: [];
        foreach ($cmsCategories as $row) {
            $this->addSlug($idShop, 'cms_category', (int) $row['id_lang'], (string) $row['link_rewrite'], (int) $row['id_cms_category']);
        }

        $manufacturers = Db::getInstance()->executeS(
            'SELECT id_manufacturer, name FROM `' . $prefix . 'manufacturer`'
        ) ?: [];
        foreach ($manufacturers as $row) {
            $slug = Tools::str2url((string) $row['name']);
            if ($slug !== '') {
                $this->addSlug($idShop, 'manufacturer', 0, $slug, (int) $row['id_manufacturer']);
            }
        }

        $suppliers = Db::getInstance()->executeS(
            'SELECT id_supplier, name FROM `' . $prefix . 'supplier`'
        ) ?: [];
        foreach ($suppliers as $row) {
            $slug = Tools::str2url((string) $row['name']);
            if ($slug !== '') {
                $this->addSlug($idShop, 'supplier', 0, $slug, (int) $row['id_supplier']);
            }
        }

        $metas = Db::getInstance()->executeS(
            'SELECT m.page, ml.id_lang, ml.url_rewrite
            FROM `' . $prefix . 'meta` m
            INNER JOIN `' . $prefix . 'meta_lang` ml ON ml.id_meta = m.id_meta
            WHERE ml.id_shop = ' . $shopSql . ' AND ml.url_rewrite != \'\''
        ) ?: [];
        foreach ($metas as $row) {
            $this->metaPages[$idShop][(int) $row['id_lang']][strtolower((string) $row['url_rewrite'])] = (string) $row['page'];
        }

        echo 'Boutique ' . $idShop . ' indexée' . "\n";
    }

    private function addSlug(int $idShop, string $type, int $idLang, string $rewrite, int $id): void
    {
        $rewrite = strtolower($rewrite);
        if ($rewrite === '' || $id <= 0) {
            return;
        }
        $this->slugIndex[$idShop][$type][$idLang][$rewrite][$id] = $id;
    }

    /**
     * @param int[] $onlyIds
     */
    private function processTable(
        string $source,
        string $table,
        string $idColumn,
        string $htmlColumn,
        int $idShop,
        array $onlyIds,
        string $kind = 'html'
    ): void {
        $offset = 0;
        $read = 0;
        $limit = $this->options['limit'];
        $prefix = _DB_PREFIX_;

        while (true) {
            if ($limit !== null && $read >= $limit) {
                break;
            }
            $batch = self::BATCH;
            if ($limit !== null) {
                $batch = min($batch, $limit - $read);
            }

            if ($kind === 'ce' && !$this->tableExists($table)) {
                echo '  ' . $source . ' : table absente, ignorée' . "\n";

                return;
            }

            $column = '`' . bqSQL($htmlColumn) . '`';
            $where = 'id_lang = ' . (int) $this->targetLangId
                . ' AND id_shop = ' . (int) $idShop;
            if ($kind === 'ce') {
                $where .= ' AND (' . $column . ' LIKE \'%href=%\' OR ' . $column . ' LIKE \'%"url"%\')';
            } else {
                $where .= ' AND ' . $column . ' LIKE \'%href=%\'';
            }
            if ($onlyIds) {
                $where .= ' AND `' . bqSQL($idColumn) . '` IN (' . implode(',', array_map('intval', $onlyIds)) . ')';
            }

            $rows = Db::getInstance()->executeS(
                'SELECT `' . bqSQL($idColumn) . '` AS id, `' . bqSQL($htmlColumn) . '` AS html
                FROM `' . $prefix . bqSQL($table) . '`
                WHERE ' . $where . '
                ORDER BY `' . bqSQL($idColumn) . '` ASC
                LIMIT ' . (int) $batch . ' OFFSET ' . (int) $offset
            ) ?: [];

            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                $id = (int) $row['id'];
                $html = (string) $row['html'];
                $changed = $kind === 'ce'
                    ? $this->rewriteCeContent($source, $id, $idShop, $html)
                    : $this->rewriteHtml($source, $id, $idShop, $html);
                if ($changed !== $html) {
                    $this->bump('fiches_modifiees');
                    if ($this->apply) {
                        $this->writeUndo($table, $idColumn, $id, $idShop, $htmlColumn, $html);
                        Db::getInstance()->update(
                            $table,
                            [$htmlColumn => pSQL($changed, true)],
                            '`' . bqSQL($idColumn) . '` = ' . $id
                                . ' AND id_lang = ' . (int) $this->targetLangId
                                . ' AND id_shop = ' . (int) $idShop
                        );
                    }
                }
                ++$read;
            }

            $offset += count($rows);
            if (count($rows) < $batch) {
                break;
            }
        }

        echo '  ' . $source . ' boutique ' . $idShop . ' : ' . $read . ' fiche(s) avec href' . "\n";
    }

    private function rewriteHtml(string $source, int $id, int $idShop, string $html): string
    {
        $replaced = preg_replace_callback(
            '/(\bhref\s*=\s*)([\'"])([^\'"]*)\2/i',
            function (array $match) use ($source, $id, $idShop) {
                $raw = html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $result = $this->rewriteHref($raw, $idShop);
                $this->bump($result['status']);
                if ($this->debug) {
                    $this->debugHref($source, $id, $idShop, $raw, $result);
                }
                if ($result['status'] === 'rewritten') {
                    $this->csvRow([
                        $result['status'],
                        $source,
                        $id,
                        $idShop,
                        $result['type'],
                        $result['entity_id'],
                        $raw,
                        $result['href'],
                    ]);
                    $encoded = htmlspecialchars($result['href'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    return $match[1] . $match[2] . $encoded . $match[2];
                }
                if (in_array($result['status'], ['unresolved', 'ambiguous'], true)) {
                    $this->csvRow([
                        $result['status'],
                        $source,
                        $id,
                        $idShop,
                        '',
                        '',
                        $raw,
                        '',
                    ]);
                }

                return $match[0];
            },
            $html
        );

        return is_string($replaced) ? $replaced : $html;
    }

    private function rewriteCeContent(string $source, int $id, int $idShop, string $raw): string
    {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $this->rewriteHtml($source, $id, $idShop, $raw);
        }
        $changed = $this->rewriteCeNode($source, $id, $idShop, $decoded);
        if ($changed === $decoded) {
            return $raw;
        }
        $flags = JSON_UNESCAPED_UNICODE;
        if (strpos($raw, '\\/') === false) {
            $flags |= JSON_UNESCAPED_SLASHES;
        }
        $encoded = json_encode($changed, $flags);

        return is_string($encoded) ? $encoded : $raw;
    }

    /**
     * @param mixed $node
     *
     * @return mixed
     */
    private function rewriteCeNode(string $source, int $id, int $idShop, $node)
    {
        if (is_string($node)) {
            if (stripos($node, 'href=') === false) {
                return $node;
            }

            return $this->rewriteHtml($source, $id, $idShop, $node);
        }
        if (!is_array($node)) {
            return $node;
        }
        foreach ($node as $key => $value) {
            if ($key === 'url' && is_string($value) && $value !== '') {
                $node[$key] = $this->rewriteCeUrl($source, $id, $idShop, $value);
                continue;
            }
            $node[$key] = $this->rewriteCeNode($source, $id, $idShop, $value);
        }

        return $node;
    }

    private function rewriteCeUrl(string $source, int $id, int $idShop, string $url): string
    {
        $wrapped = '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"></a>';
        $rewritten = $this->rewriteHtml($source, $id, $idShop, $wrapped);
        if (!preg_match('/href="([^"]*)"/', $rewritten, $match)) {
            return $url;
        }

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function tableExists(string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }
        $name = _DB_PREFIX_ . $table;
        $found = Db::getInstance()->getValue(
            'SELECT TABLE_NAME FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'' . pSQL($name) . '\''
        );
        $cache[$table] = (bool) $found;

        return $cache[$table];
    }

    /**
     * @return array{status: string, href: string, type: string, entity_id: string}
     */
    private function rewriteHref(string $href, int $contentShopId): array
    {
        $empty = ['status' => 'skipped', 'href' => $href, 'type' => '', 'entity_id' => ''];
        $href = trim($href);
        if ($href === '' || $href[0] === '#' || preg_match('#^(mailto:|tel:|javascript:|data:)#i', $href)) {
            return $empty;
        }
        if (stripos($href, 'token=') !== false) {
            return $empty;
        }

        $parts = $this->parseHref($href);
        if ($parts === null) {
            return $empty;
        }
        if (!$parts['internal']) {
            return ['status' => 'external', 'href' => $href, 'type' => '', 'entity_id' => ''];
        }
        if ($this->isAssetPath($parts['path'])) {
            return $empty;
        }

        $shopId = $parts['id_shop'] ?: $contentShopId;
        $resolved = $this->resolve($parts, $shopId);
        if ($resolved === null) {
            return ['status' => 'unresolved', 'href' => $href, 'type' => '', 'entity_id' => ''];
        }
        if (!empty($resolved['ambiguous'])) {
            return ['status' => 'ambiguous', 'href' => $href, 'type' => '', 'entity_id' => ''];
        }

        if ($resolved['type'] === 'product') {
            $fromUrl = $this->productIdFromUrlRedirect($parts['path'], $shopId);
            $resolved['id'] = $this->followProductRedirect($fromUrl ?: (int) $resolved['id'], $shopId);
        }

        $absolute = $this->buildAbsolute($resolved, $shopId);
        if ($absolute === '') {
            return ['status' => 'unresolved', 'href' => $href, 'type' => $resolved['type'], 'entity_id' => (string) $resolved['id']];
        }

        $newHref = $this->composeHref($parts, $absolute, $resolved['query']);
        if ($this->sameUrl($href, $newHref)) {
            return ['status' => 'unchanged', 'href' => $href, 'type' => $resolved['type'], 'entity_id' => (string) $resolved['id']];
        }

        return [
            'status' => 'rewritten',
            'href' => $newHref,
            'type' => $resolved['type'],
            'entity_id' => (string) $resolved['id'],
        ];
    }

    /**
     * @return array{internal: bool, absolute: bool, scheme: string, host: string, path: string, query: array<string, mixed>, fragment: string, id_shop: int}|null
     */
    private function parseHref(string $href): ?array
    {
        $absolute = (bool) preg_match('#^https?://#i', $href);
        $protoRelative = strpos($href, '//') === 0;
        $rootRelative = strpos($href, '/') === 0;
        $legacy = (bool) preg_match('#(^|/)index\.php(\?|$)#i', $href);
        $relative = !$absolute && !$protoRelative && !$rootRelative && !$legacy
            && !preg_match('#^[a-z][a-z0-9+.-]*:#i', $href);

        if (!$absolute && !$protoRelative && !$rootRelative && !$legacy && !$relative) {
            return null;
        }

        if ($relative) {
            $href = $this->resolveRelativePath($href);
            $rootRelative = true;
        }

        $toParse = $href;
        if ($protoRelative) {
            $toParse = 'https:' . $href;
        } elseif (!$absolute) {
            $toParse = 'https://internal.local' . ($rootRelative ? '' : '/') . $href;
        }

        $parsed = parse_url($toParse);
        if (!is_array($parsed)) {
            return null;
        }

        $host = strtolower((string) ($parsed['host'] ?? ''));
        $internal = !$absolute && !$protoRelative;
        $idShop = 0;
        if ($absolute || $protoRelative) {
            if (!isset($this->hostToShop[$host])) {
                return [
                    'internal' => false,
                    'absolute' => true,
                    'scheme' => '',
                    'host' => $host,
                    'path' => '',
                    'query' => [],
                    'fragment' => '',
                    'id_shop' => 0,
                ];
            }
            $internal = true;
            $idShop = $this->hostToShop[$host];
        }

        $query = [];
        if (!empty($parsed['query'])) {
            parse_str((string) $parsed['query'], $query);
        }

        $path = (string) ($parsed['path'] ?? '/');
        if (!$rootRelative && !$absolute && !$protoRelative && $legacy) {
            $path = '/' . ltrim($path, '/');
        }

        return [
            'internal' => $internal,
            'absolute' => $absolute || $protoRelative,
            'scheme' => $protoRelative ? '' : strtolower((string) ($parsed['scheme'] ?? 'https')),
            'host' => $host,
            'path' => $path === '' ? '/' : $path,
            'query' => $query,
            'fragment' => (string) ($parsed['fragment'] ?? ''),
            'id_shop' => $idShop,
        ];
    }

    /**
     * ../59-fiche ou ./page → /59-fiche, /page. Le .. ne remonte pas au-dessus de la racine.
     */
    private function resolveRelativePath(string $href): string
    {
        $fragment = '';
        $query = '';
        $hashPos = strpos($href, '#');
        if ($hashPos !== false) {
            $fragment = substr($href, $hashPos);
            $href = substr($href, 0, $hashPos);
        }
        $queryPos = strpos($href, '?');
        if ($queryPos !== false) {
            $query = substr($href, $queryPos);
            $href = substr($href, 0, $queryPos);
        }

        $segments = [];
        foreach (explode('/', $href) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return '/' . implode('/', $segments) . $query . $fragment;
    }

    /**
     * @param array{path: string, query: array<string, mixed>} $parts
     *
     * @return array{type: string, id: int, ipa: int, filters: string, page: string, query: array<string, mixed>, ambiguous?: bool}|null
     */
    private function resolve(array $parts, int $idShop): ?array
    {
        $query = $parts['query'];
        $fromQuery = $this->resolveQuery($query);
        if ($fromQuery !== null) {
            $fromQuery['query'] = $this->cleanQuery($query);

            return $fromQuery;
        }

        [$urlLangId, $path] = $this->stripLangPrefix($parts['path']);
        $path = $this->normalizePath($path);
        if ($path === '/') {
            return [
                'type' => 'page',
                'id' => 0,
                'ipa' => 0,
                'filters' => '',
                'page' => 'index',
                'query' => $this->cleanQuery($query),
            ];
        }

        $preferLangs = array_values(array_unique(array_filter([
            $urlLangId,
            $this->defaultLangId,
            $this->targetLangId,
        ])));
        foreach (array_keys($this->langToIso) as $idLang) {
            if (!in_array($idLang, $preferLangs, true)) {
                $preferLangs[] = $idLang;
            }
        }

        $routes = $this->routesFor($idShop, $urlLangId ?: $this->defaultLangId);
        $priority = [
            'product_rule', 'cms_rule', 'cms_category_rule', 'manufacturer_rule',
            'supplier_rule', 'category_rule', 'layered_rule',
        ];

        foreach ($priority as $routeId) {
            if (!isset($routes[$routeId]['rule'])) {
                continue;
            }
            $match = $this->matchRule((string) $routes[$routeId]['rule'], $routeId, $path);
            if ($match === null) {
                continue;
            }
            $resolved = $this->resolveMatch($routeId, $match, $idShop, $preferLangs);
            if ($resolved === null) {
                continue;
            }
            $resolved['query'] = $this->cleanQuery($query);

            return $resolved;
        }

        $metaKey = strtolower(trim($path, '/'));
        foreach ($preferLangs as $idLang) {
            if (!empty($this->metaPages[$idShop][$idLang][$metaKey])) {
                return [
                    'type' => 'page',
                    'id' => 0,
                    'ipa' => 0,
                    'filters' => '',
                    'page' => $this->metaPages[$idShop][$idLang][$metaKey],
                    'query' => $this->cleanQuery($query),
                ];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{type: string, id: int, ipa: int, filters: string, page: string}|null
     */
    private function resolveQuery(array $query): ?array
    {
        $controller = isset($query['controller']) ? (string) $query['controller'] : '';
        $map = [
            'id_product' => 'product',
            'id_category' => 'category',
            'id_cms' => 'cms',
            'id_cms_category' => 'cms_category',
            'id_manufacturer' => 'manufacturer',
            'id_supplier' => 'supplier',
        ];
        foreach ($map as $key => $type) {
            if (!empty($query[$key]) && ($controller === '' || $controller === $type || ($type === 'cms_category' && $controller === 'cms'))) {
                return [
                    'type' => $type,
                    'id' => (int) $query[$key],
                    'ipa' => (int) ($query['id_product_attribute'] ?? 0),
                    'filters' => '',
                    'page' => '',
                ];
            }
        }
        if ($controller !== '' && !in_array($controller, ['product', 'category', 'cms', 'manufacturer', 'supplier'], true)) {
            return [
                'type' => 'page',
                'id' => 0,
                'ipa' => 0,
                'filters' => '',
                'page' => $controller,
                'query' => [],
            ];
        }

        return null;
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function stripLangPrefix(string $path): array
    {
        if (preg_match('#^/([a-z]{2})(/|$)#', $path, $match) && isset($this->isoToLang[$match[1]])) {
            $rest = substr($path, strlen($match[1]) + 1);
            if ($rest === '' || $rest === false) {
                $rest = '/';
            }

            return [$this->isoToLang[$match[1]], $rest];
        }

        return [$this->removeDefaultLangCode ? $this->defaultLangId : 0, $path];
    }

    /**
     * @return array<string, array{rule?: string, regexp?: string, controller?: string}>
     */
    private function routesFor(int $idShop, int $idLang): array
    {
        $all = $this->dispatcher->getRoutes();
        if (!empty($all[$idShop][$idLang])) {
            return $all[$idShop][$idLang];
        }
        if (!empty($all[$idShop][$this->defaultLangId])) {
            return $all[$idShop][$this->defaultLangId];
        }

        return [];
    }

    /**
     * @return array<string, string>|null
     */
    private function matchRule(string $rule, string $routeId, string $path): ?array
    {
        $regex = $this->compileRule($rule, $routeId);
        if ($regex === null || !preg_match($regex, $path, $matches)) {
            return null;
        }
        $named = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key) && $value !== '') {
                $named[$key] = $value;
            }
        }

        return $named;
    }

    private function compileRule(string $rule, string $routeId): ?string
    {
        $idName = [
            'product_rule' => 'id_product',
            'category_rule' => 'id_category',
            'layered_rule' => 'id_category',
            'cms_rule' => 'id_cms',
            'cms_category_rule' => 'id_cms_category',
            'manufacturer_rule' => 'id_manufacturer',
            'supplier_rule' => 'id_supplier',
        ][$routeId] ?? 'id';

        $patterns = [
            'id' => '[0-9]+',
            'rewrite' => '[_a-zA-Z0-9\pL\-]+',
            'parent_rewrite' => '[/_a-zA-Z0-9\pL\-]*',
            'category' => '[_a-zA-Z0-9\pL\-]*',
            'categories' => '[/_a-zA-Z0-9\pL\-]*',
            'id_product_attribute' => '[0-9]+',
            'ean13' => '[0-9\pL]*',
            'selected_filters' => '.+',
            'meta_keywords' => '[_a-zA-Z0-9\pL\-]*',
            'meta_title' => '[_a-zA-Z0-9\pL\-]*',
            'manufacturer' => '[_a-zA-Z0-9\pL\-]*',
            'supplier' => '[_a-zA-Z0-9\pL\-]*',
            'reference' => '[_a-zA-Z0-9\pL\-]*',
            'price' => '[0-9\.,]*',
            'tags' => '[a-zA-Z0-9\pL\-]*',
        ];

        $parts = preg_split('#(\{[^{}]+\})#', $rule, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return null;
        }
        $regex = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if ($part[0] !== '{') {
                $regex .= preg_quote($part, '#');
                continue;
            }
            $token = substr($part, 1, -1);
            $prepend = '';
            $append = '';
            $keyword = $token;
            if (preg_match('#^(?:(.+):)?([a-z0-9_]+)(?::(.*))?$#', $token, $captured)) {
                $prepend = (string) $captured[1];
                $keyword = (string) $captured[2];
                $append = (string) ($captured[3] ?? '');
            }
            $name = $keyword === 'id' ? $idName : $keyword;
            $inner = $patterns[$keyword] ?? '.+';
            $group = '(?P<' . $name . '>' . $inner . ')';
            if ($prepend !== '' || $append !== '') {
                $regex .= '(?:' . preg_quote($prepend, '#') . $group . preg_quote((string) $append, '#') . ')?';
            } else {
                $regex .= $group;
            }
        }

        return '#^/' . $regex . '/?$#u';
    }

    /**
     * @param array<string, string> $match
     * @param int[] $preferLangs
     *
     * @return array{type: string, id: int, ipa: int, filters: string, page: string, ambiguous?: bool}|null
     */
    private function resolveMatch(string $routeId, array $match, int $idShop, array $preferLangs): ?array
    {
        $type = [
            'product_rule' => 'product',
            'category_rule' => 'category',
            'layered_rule' => 'category',
            'cms_rule' => 'cms',
            'cms_category_rule' => 'cms_category',
            'manufacturer_rule' => 'manufacturer',
            'supplier_rule' => 'supplier',
        ][$routeId] ?? '';
        if ($type === '') {
            return null;
        }

        $idKey = [
            'product' => 'id_product',
            'category' => 'id_category',
            'cms' => 'id_cms',
            'cms_category' => 'id_cms_category',
            'manufacturer' => 'id_manufacturer',
            'supplier' => 'id_supplier',
        ][$type];

        $id = (int) ($match[$idKey] ?? 0);
        if ($type === 'product' && $id > 0 && !empty($match['rewrite'])) {
            $rewrite = (string) $match['rewrite'];
            $owns = $this->productOwnsRewrite($idShop, $id, $rewrite);
            $active = $this->isProductActive($idShop, $id);
            if (!$owns || !$active) {
                $replacement = $this->activeProductByRewrite($idShop, $rewrite, $id);
                if ($replacement > 0) {
                    $id = $replacement;
                }
            }
        }
        if ($id <= 0 && !empty($match['rewrite'])) {
            if ($type === 'category') {
                $found = $this->findCategory($idShop, (string) $match['rewrite'], (string) ($match['parent_rewrite'] ?? ''), $preferLangs);
            } else {
                $found = $this->findIds($idShop, $type, (string) $match['rewrite'], $preferLangs);
            }
            if (!empty($found['ambiguous'])) {
                return ['type' => $type, 'id' => 0, 'ipa' => 0, 'filters' => '', 'page' => '', 'ambiguous' => true];
            }
            $id = (int) ($found['id'] ?? 0);
        }
        if ($id <= 0) {
            return null;
        }

        return [
            'type' => $type,
            'id' => $id,
            'ipa' => (int) ($match['id_product_attribute'] ?? 0),
            'filters' => (string) ($match['selected_filters'] ?? ''),
            'page' => '',
        ];
    }

    /**
     * @param int[] $preferLangs
     *
     * @return array{id?: int, ambiguous?: bool}
     */
    private function findIds(int $idShop, string $type, string $rewrite, array $preferLangs): array
    {
        $rewrite = strtolower($rewrite);
        $seen = [];
        foreach ($preferLangs as $idLang) {
            $ids = $this->slugIndex[$idShop][$type][$idLang][$rewrite] ?? [];
            if (!$ids) {
                continue;
            }
            foreach ($ids as $id) {
                $seen[$id] = $id;
            }
            if (count($seen) === 1) {
                return ['id' => (int) reset($seen)];
            }
            if (count($seen) > 1) {
                return ['ambiguous' => true];
            }
        }
        foreach ($this->slugIndex[$idShop][$type] as $map) {
            foreach ($map[$rewrite] ?? [] as $id) {
                $seen[$id] = $id;
            }
        }
        if (count($seen) === 1) {
            return ['id' => (int) reset($seen)];
        }
        if (count($seen) > 1) {
            return ['ambiguous' => true];
        }

        return [];
    }

    /**
     * @param int[] $preferLangs
     *
     * @return array{id?: int, ambiguous?: bool}
     */
    private function findCategory(int $idShop, string $rewrite, string $parentRewrite, array $preferLangs): array
    {
        $rewrite = strtolower(trim($rewrite, '/'));
        $parentRewrite = trim($parentRewrite, '/');
        $candidates = [];
        foreach ($preferLangs as $idLang) {
            foreach ($this->slugIndex[$idShop]['category'][$idLang][$rewrite] ?? [] as $id) {
                $candidates[$id] = $idLang;
            }
            if ($candidates) {
                break;
            }
        }
        if (!$candidates) {
            foreach ($this->slugIndex[$idShop]['category'] as $idLang => $map) {
                foreach ($map[$rewrite] ?? [] as $id) {
                    $candidates[$id] = (int) $idLang;
                }
            }
        }
        if (!$candidates) {
            return [];
        }

        $parents = $parentRewrite === '' ? [] : explode('/', strtolower($parentRewrite));
        $matched = [];
        foreach ($candidates as $id => $idLang) {
            $path = $this->categoryAncestorSlugs($idShop, (int) $id, (int) $idLang);
            if ($parents === $path) {
                $matched[] = (int) $id;
            }
        }
        if (count($matched) === 1) {
            return ['id' => $matched[0]];
        }
        if (count($matched) > 1) {
            return ['ambiguous' => true];
        }
        if ($parentRewrite === '' && count($candidates) === 1) {
            return ['id' => (int) array_key_first($candidates)];
        }
        if (count($candidates) > 1) {
            return ['ambiguous' => true];
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function categoryAncestorSlugs(int $idShop, int $idCategory, int $idLang): array
    {
        $slugs = [];
        $current = $this->categoryParent[$idShop][$idCategory] ?? 0;
        $guard = 0;
        while ($current > 0 && $guard < 25) {
            ++$guard;
            if (!isset($this->disabledCategoryIds[$current])) {
                $slug = $this->categoryRewrite[$idShop][$idLang][$current]
                    ?? $this->categoryRewrite[$idShop][$this->defaultLangId][$current]
                    ?? '';
                if ($slug !== '') {
                    array_unshift($slugs, strtolower($slug));
                }
            }
            $current = $this->categoryParent[$idShop][$current] ?? 0;
        }

        return $slugs;
    }

    /**
     * Ancienne URL enregistrée dans ets_awu_redirect (le produit source peut être en 404).
     */
    private function productIdFromUrlRedirect(string $path, int $idShop): int
    {
        if (!preg_match('#(\d+-[_a-zA-Z0-9\pL\-]+\.html)#u', $path, $match)) {
            return 0;
        }
        $table = $this->redirectTable();
        if ($table === '') {
            return 0;
        }
        $row = Db::getInstance()->getRow(
            'SELECT target FROM `' . bqSQL($table) . '`
            WHERE active = 1
            AND (id_shop = ' . (int) $idShop . ' OR id_shop = 0)
            AND url LIKE \'%' . pSQL($match[1]) . '\'
            ORDER BY id_ets_awu_redirect DESC'
        );
        if (!is_array($row) || empty($row['target'])) {
            return 0;
        }
        if (!preg_match('#/(\d+)-[_a-zA-Z0-9\pL\-]+\.html#u', (string) $row['target'], $target)) {
            return 0;
        }
        $id = (int) $target[1];

        return $id > 0 ? $id : 0;
    }

    private function redirectTable(): string
    {
        static $table = null;
        if ($table !== null) {
            return $table;
        }
        $name = _DB_PREFIX_ . 'ets_awu_redirect';
        $found = Db::getInstance()->getValue(
            'SELECT TABLE_NAME FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'' . pSQL($name) . '\''
        );
        $table = $found ? $name : '';

        return $table;
    }

    private function productOwnsRewrite(int $idShop, int $idProduct, string $rewrite): bool
    {
        $rewrite = strtolower($rewrite);
        foreach ($this->slugIndex[$idShop]['product'] ?? [] as $map) {
            if (isset($map[$rewrite][$idProduct])) {
                return true;
            }
        }

        return false;
    }

    private function activeProductByRewrite(int $idShop, string $rewrite, int $except): int
    {
        $rewrite = strtolower($rewrite);
        $ids = [];
        foreach ($this->slugIndex[$idShop]['product'] ?? [] as $map) {
            foreach ($map[$rewrite] ?? [] as $id) {
                $ids[(int) $id] = (int) $id;
            }
        }
        foreach ($ids as $id) {
            if ($id !== $except && $this->isProductActive($idShop, $id)) {
                return $id;
            }
        }

        return 0;
    }

    private function isProductActive(int $idShop, int $idProduct): bool
    {
        $key = $idShop . ':' . $idProduct;
        if (!array_key_exists($key, $this->productActive)) {
            $active = Db::getInstance()->getValue(
                'SELECT active FROM `' . _DB_PREFIX_ . 'product_shop`
                WHERE id_product = ' . (int) $idProduct . ' AND id_shop = ' . (int) $idShop
            );
            $this->productActive[$key] = (int) $active === 1;
        }

        return $this->productActive[$key];
    }

    /**
     * Produit inactif redirigé vers un autre produit : on écrit l’URL de la cible.
     * Même règle que ProductController (type « default » = config boutique).
     */
    private function followProductRedirect(int $idProduct, int $idShop): int
    {
        $current = $idProduct;
        $seen = [];
        $defaultType = (string) Configuration::get('PS_PRODUCT_REDIRECTION_DEFAULT');
        for ($i = 0; $i < 5; ++$i) {
            if ($current <= 0 || isset($seen[$current])) {
                break;
            }
            $seen[$current] = true;
            $row = Db::getInstance()->getRow(
                'SELECT active, redirect_type, id_type_redirected
                FROM `' . _DB_PREFIX_ . 'product_shop`
                WHERE id_product = ' . (int) $current . ' AND id_shop = ' . (int) $idShop
            );
            if (!is_array($row) || (int) $row['active'] === 1) {
                break;
            }
            $type = (string) $row['redirect_type'];
            if ($type === '' || $type === 'default') {
                $type = $defaultType;
            }
            $target = (int) $row['id_type_redirected'];
            if (($type === '301-product' || $type === '302-product') && $target > 0 && $target !== $current) {
                $current = $target;
                continue;
            }
            break;
        }

        return $current > 0 ? $current : $idProduct;
    }

    /**
     * @param array{type: string, id: int, ipa: int, filters: string, page: string} $resolved
     */
    private function buildAbsolute(array $resolved, int $idShop): string
    {
        $cacheKey = $resolved['type'] . '|' . $resolved['id'] . '|' . $idShop . '|' . $this->targetLangId
            . '|' . $resolved['ipa'] . '|' . $resolved['filters'] . '|' . $resolved['page'];
        if (isset($this->urlCache[$cacheKey])) {
            return $this->urlCache[$cacheKey];
        }

        $lang = $this->targetLangId;
        $url = '';
        try {
            switch ($resolved['type']) {
                case 'product':
                    $url = $this->link->getProductLink(
                        $resolved['id'],
                        null,
                        null,
                        null,
                        $lang,
                        $idShop,
                        $resolved['ipa'] ?: null,
                        false,
                        false,
                        false,
                        [],
                        false
                    );
                    break;
                case 'category':
                    $url = $this->link->getCategoryLink(
                        $resolved['id'],
                        null,
                        $lang,
                        $resolved['filters'] !== '' ? $resolved['filters'] : null,
                        $idShop
                    );
                    break;
                case 'cms':
                    $url = $this->link->getCMSLink($resolved['id'], null, true, $lang, $idShop);
                    break;
                case 'cms_category':
                    $url = $this->link->getCMSCategoryLink($resolved['id'], null, $lang, $idShop);
                    break;
                case 'manufacturer':
                    $url = $this->link->getManufacturerLink($resolved['id'], null, $lang, $idShop);
                    break;
                case 'supplier':
                    $url = $this->link->getSupplierLink($resolved['id'], null, $lang, $idShop);
                    break;
                case 'page':
                    $url = $this->link->getPageLink($resolved['page'], true, $lang, null, false, $idShop);
                    break;
            }
        } catch (Throwable $e) {
            $url = '';
        }

        $this->urlCache[$cacheKey] = (string) $url;

        return (string) $url;
    }

    /**
     * @param array{absolute: bool, scheme: string, host: string, fragment: string} $original
     * @param array<string, mixed> $query
     */
    private function composeHref(array $original, string $absolute, array $query): string
    {
        $generated = parse_url($absolute);
        if (!is_array($generated)) {
            return $absolute;
        }
        $path = (string) ($generated['path'] ?? '/');
        if (!empty($generated['query'])) {
            $extra = [];
            parse_str((string) $generated['query'], $extra);
            $query = array_merge($extra, $query);
        }
        $queryString = $query ? http_build_query($query) : '';
        $fragment = $original['fragment'];

        if ($original['absolute'] && $original['host'] !== '' && $original['host'] !== 'internal.local') {
            $scheme = $original['scheme'] !== '' ? $original['scheme'] : 'https';
            $href = $scheme . '://' . $original['host'] . $path;
        } else {
            $href = $path;
        }
        if ($queryString !== '') {
            $href .= '?' . $queryString;
        }
        if ($fragment !== '') {
            $href .= '#' . $fragment;
        }

        return $href;
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function cleanQuery(array $query): array
    {
        foreach (self::DROP_QUERY_KEYS as $key) {
            unset($query[$key]);
        }

        return $query;
    }

    private function sameUrl(string $left, string $right): bool
    {
        return $this->normalizeUrl($left) === $this->normalizeUrl($right);
    }

    private function normalizeUrl(string $url): string
    {
        $absolute = (bool) preg_match('#^https?://#i', $url) || strpos($url, '//') === 0;
        $toParse = $url;
        if (strpos($url, '//') === 0) {
            $toParse = 'https:' . $url;
        } elseif (!$absolute) {
            $toParse = 'https://internal.local' . (strpos($url, '/') === 0 ? '' : '/') . $url;
        }
        $parsed = parse_url($toParse);
        if (!is_array($parsed)) {
            return strtolower($url);
        }
        $host = $absolute ? strtolower((string) ($parsed['host'] ?? '')) : '';
        $path = $this->normalizePath((string) ($parsed['path'] ?? '/'));
        $query = (string) ($parsed['query'] ?? '');
        $fragment = (string) ($parsed['fragment'] ?? '');

        return $host . $path . ($query !== '' ? '?' . $query : '') . ($fragment !== '' ? '#' . $fragment : '');
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    private function isAssetPath(string $path): bool
    {
        $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));

        return $ext !== '' && in_array($ext, self::SKIP_EXTENSIONS, true);
    }

    private function bump(string $key): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + 1;
    }

    private function openReport(): void
    {
        $dir = dirname(__DIR__) . '/var/logs';
        if (!is_dir($dir)) {
            $dir = __DIR__ . '/reports';
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Impossible de créer le dossier de rapport.');
            }
        }
        $stamp = date('Ymd_His');
        $this->csvPath = $dir . '/rewrite_lang_links_' . $stamp . '.csv';
        $handle = fopen($this->csvPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Impossible d\'écrire le rapport.');
        }
        $this->csv = $handle;
        fputcsv($this->csv, ['status', 'source', 'id', 'id_shop', 'entity_type', 'entity_id', 'old_href', 'new_href'], ';');

        if ($this->apply) {
            $this->undoPath = $dir . '/rewrite_lang_links_' . $stamp . '.undo.jsonl';
            $undo = fopen($this->undoPath, 'wb');
            if ($undo === false) {
                throw new RuntimeException('Impossible d\'écrire le fichier de rollback.');
            }
            $this->undo = $undo;
        }
    }

    /**
     * @param array<int, string|int> $row
     */
    private function csvRow(array $row): void
    {
        fputcsv($this->csv, $row, ';');
    }

    private function writeUndo(string $table, string $idColumn, int $id, int $idShop, string $column, string $html): void
    {
        if (!$this->undo) {
            return;
        }
        fwrite($this->undo, json_encode([
            'table' => $table,
            'id_column' => $idColumn,
            'id' => $id,
            'id_shop' => $idShop,
            'id_lang' => $this->targetLangId,
            'column' => $column,
            'html' => $html,
        ], JSON_UNESCAPED_UNICODE) . "\n");
    }

    private function closeReport(): void
    {
        fclose($this->csv);
        if ($this->undo) {
            fclose($this->undo);
            echo 'Rollback : ' . $this->undoPath . "\n";
        }
    }

    /**
     * @param array{status: string, href: string, type: string, entity_id: string} $result
     */
    private function debugHref(string $source, int $id, int $idShop, string $oldHref, array $result): void
    {
        $kept = $result['status'] === 'rewritten' ? $result['href'] : $oldHref;
        $probe = in_array($result['status'], ['rewritten', 'unchanged'], true)
            ? $this->httpStatus($this->toAbsolute($kept, $idShop))
            : '-';
        $entity = trim($result['type'] . ' ' . $result['entity_id']);

        echo "\n[" . $source . ' ' . $id . ' boutique ' . $idShop . '] ' . $result['status'];
        if ($entity !== '') {
            echo '  ' . $entity;
        }
        echo "\n";
        echo '  actuel : ' . $oldHref . "\n";
        if ($result['status'] === 'rewritten') {
            echo '  généré : ' . $result['href'] . "\n";
        }
        echo '  HTTP   : ' . $probe . "\n";
    }

    private function toAbsolute(string $href, int $idShop): string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }
        if (strpos($href, '//') === 0) {
            return 'https:' . $href;
        }
        if (!isset($this->shopBase[$idShop])) {
            $shop = new Shop($idShop);
            $this->shopBase[$idShop] = rtrim($shop->getBaseURL(true), '/');
        }

        return $this->shopBase[$idShop] . '/' . ltrim($href, '/');
    }

    private function httpStatus(string $url): string
    {
        if (!function_exists('curl_init')) {
            return 'extension curl absente';
        }
        $head = $this->curlHeaders($url, true);
        if ($head['code'] === 405 || $head['code'] === 0) {
            $head = $this->curlHeaders($url, false);
        }
        if ($head['code'] === 0) {
            return 'échec ' . $head['error'];
        }
        $https = $this->httpsUpgrade($url, $head['code'], $head['location']);
        if ($https !== '') {
            $head = $this->curlHeaders($https, true);
            if ($head['code'] === 405 || $head['code'] === 0) {
                $head = $this->curlHeaders($https, false);
            }
            $url = $https;
        }
        if ($head['code'] === 0) {
            return 'échec ' . $head['error'];
        }
        $line = (string) $head['code'] . '  ' . $url;
        if ($head['location'] !== '') {
            $line .= "\n           -> " . $head['location'];
        }

        return $line;
    }

    private function httpsUpgrade(string $url, int $code, string $location): string
    {
        if (!in_array($code, [301, 302], true) || stripos($url, 'http://') !== 0) {
            return '';
        }
        $secure = 'https://' . substr($url, 7);
        if ($location === $secure || $location === '') {
            return $secure;
        }
        $locationPath = preg_replace('#^https?://[^/]+#i', '', $location);
        $urlPath = preg_replace('#^https?://[^/]+#i', '', $url);

        return ($locationPath === $urlPath && stripos($location, 'https://') === 0) ? $location : '';
    }

    /**
     * @return array{code: int, location: string, error: string}
     */
    private function curlHeaders(string $url, bool $headOnly): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => $headOnly,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'CkcRewriteLangLinks-debug',
        ]);
        if (!$headOnly) {
            curl_setopt($ch, CURLOPT_RANGE, '0-0');
        }
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = (string) curl_error($ch);
        curl_close($ch);
        $location = '';
        if (is_string($raw) && preg_match('/^location:\s*(\S+)/im', $raw, $match)) {
            $location = $match[1];
        }

        return ['code' => $code, 'location' => $location, 'error' => $error];
    }

    private function printStats(): void
    {
        echo "\nRésumé\n";
        foreach (['rewritten', 'unchanged', 'unresolved', 'ambiguous', 'external', 'skipped', 'fiches_modifiees'] as $key) {
            echo '  ' . $key . ' : ' . ($this->stats[$key] ?? 0) . "\n";
        }
        if ($this->debug) {
            echo "\nAucune base modifiée. Les codes HTTP portent sur l’URL qui serait enregistrée.\n";
        } elseif (!$this->apply) {
            echo "\nAucune base modifiée. Relancer avec --apply après contrôle du CSV.\n";
        } else {
            echo "\nBase mise à jour. Vider le cache Smarty (var/cache/).\n";
        }
    }
}

$options = [
    'apply' => in_array('--apply', $argv, true),
    'debug' => in_array('--debug', $argv, true),
    'iso' => 'en',
    'shops' => [],
    'sources' => ['cms', 'product'],
    'cms_ids' => [],
    'product_ids' => [],
    'category_ids' => [],
    'ce_ids' => [],
    'limit' => null,
];

foreach ($argv as $arg) {
    if (preg_match('/^--iso=([a-z]{2})$/i', $arg, $match)) {
        $options['iso'] = strtolower($match[1]);
    } elseif (preg_match('/^--shops=([0-9,]+)$/', $arg, $match)) {
        $options['shops'] = array_values(array_filter(array_map('intval', explode(',', $match[1]))));
    } elseif (preg_match('/^--sources=([a-z,]+)$/', $arg, $match)) {
        $options['sources'] = array_values(array_intersect(
            ['cms', 'product', 'product_short', 'category', 'ce'],
            explode(',', strtolower($match[1]))
        ));
    } elseif (preg_match('/^--cms=([0-9,]+)$/', $arg, $match)) {
        $options['cms_ids'] = array_values(array_filter(array_map('intval', explode(',', $match[1]))));
    } elseif (preg_match('/^--product=([0-9,]+)$/', $arg, $match)) {
        $options['product_ids'] = array_values(array_filter(array_map('intval', explode(',', $match[1]))));
    } elseif (preg_match('/^--category=([0-9,]+)$/', $arg, $match)) {
        $options['category_ids'] = array_values(array_filter(array_map('intval', explode(',', $match[1]))));
    } elseif (preg_match('/^--ce=([0-9,]+)$/', $arg, $match)) {
        $options['ce_ids'] = array_values(array_filter(array_map('intval', explode(',', $match[1]))));
    } elseif (preg_match('/^--limit=([0-9]+)$/', $arg, $match)) {
        $options['limit'] = (int) $match[1];
    }
}

if (!$options['shops']) {
    $options['shops'] = array_map('intval', array_column(Shop::getShops(true), 'id_shop'));
}
if (!$options['sources']) {
    fwrite(STDERR, "Aucune source. Utiliser --sources=cms,product,product_short,category,ce\n");
    exit(1);
}

try {
    exit((new CkcRewriteLangLinks($options))->run());
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
