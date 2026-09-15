<?php
/**
 * DIAG option « supprimer le code ISO » — ets_awesomeurl — PROD (lecture seule)
 *
 * https://www.cloture-discount.fr/diag_ets_awesomeurl_iso.php?key=ckc-awu-iso-2026
 * https://www.cloture-discount.fr/diag_ets_awesomeurl_iso.php?key=ckc-awu-iso-2026&shop=1
 *
 * SUPPRIMER après usage.
 */
$key = 'ckc-awu-iso-2026';
$moduleName = 'ets_awesomeurl';
$configKey = 'ETS_AWU_ENABLE_REMOVE_LANG_CODE_IN_URL';

$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (!isset($_GET['key']) || $_GET['key'] !== $key) {
        http_response_code(403);
        exit("Forbidden. Ajoutez ?key={$key}\n");
    }
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
@set_time_limit(180);

function out($m = '')
{
    echo $m . "\n";
}
function hr($t = null)
{
    out(str_repeat('=', 72));
    if ($t !== null) {
        out($t);
        out(str_repeat('-', 72));
    }
}
function ok($m)
{
    out('[OK]  ' . $m);
}
function warn($m)
{
    out('[!!]  ' . $m);
}
function info($m)
{
    out('[..]  ' . $m);
}
function fail($m)
{
    out('[XX]  ' . $m);
}

/**
 * Extrait les chaînes ASCII lisibles d'un fichier (fonctionne même sur ionCube).
 */
function extractStrings($path, $minLen = 6)
{
    if (!is_readable($path)) {
        return [];
    }
    $raw = @file_get_contents($path);
    if ($raw === false) {
        return [];
    }
    preg_match_all('/[\x20-\x7E]{' . (int) $minLen . ',}/', $raw, $m);
    return $m[0] ?? [];
}

function grepStrings(array $strings, array $needles)
{
    $hits = [];
    foreach ($strings as $s) {
        foreach ($needles as $needle) {
            if (stripos($s, $needle) !== false) {
                $hits[] = $s;
                break;
            }
        }
    }
    return array_values(array_unique($hits));
}

function scanModuleDir($dir, array $needles, $ext = ['php', 'js', 'tpl', 'twig', 'css'])
{
    $results = [];
    if (!is_dir($dir)) {
        return $results;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile()) {
            continue;
        }
        $name = $f->getFilename();
        $extOk = false;
        foreach ($ext as $e) {
            if (substr($name, -strlen($e) - 1) === '.' . $e) {
                $extOk = true;
                break;
            }
        }
        if (!$extOk) {
            continue;
        }
        $rel = str_replace($dir . DIRECTORY_SEPARATOR, '', $f->getPathname());
        $rel = str_replace('\\', '/', $rel);
        $hits = grepStrings(extractStrings($f->getPathname()), $needles);
        if ($hits) {
            $results[$rel] = $hits;
        }
    }
    ksort($results);
    return $results;
}

out('DIAG ets_awesomeurl — option « supprimer le code ISO » (PROD, lecture seule)');
out('date: ' . date('c'));
out('cwd:  ' . __DIR__);

require __DIR__ . '/config/config.inc.php';

$db = Db::getInstance();
$prefix = _DB_PREFIX_;
$ctx = Context::getContext();
$moduleDir = _PS_MODULE_DIR_ . $moduleName;
$shopId = isset($_GET['shop']) ? (int) $_GET['shop'] : (int) $ctx->shop->id;

$needles = [
    'ETS_AWU_ENABLE_REMOVE_LANG_CODE_IN_URL',
    'ENABLE_REMOVE_LANG',
    'REMOVE_LANG',
    'remove_lang',
    'removeLang',
    'iso code',
    'ISO code',
    'language code',
    'code iso',
    'isMultiLanguageActivated',
    'getLangLink',
    'renderOptions',
    'url_schema',
    'SetUpUrl',
    'AdminMeta',
];

// ---------------------------------------------------------------------------
// 1. Contexte boutique / multilangue (condition d'affichage la plus probable)
// ---------------------------------------------------------------------------
hr('1. Multilangue — condition d\'affichage UI');

out('Shop courant script: ' . (int) $ctx->shop->id . ' / ' . $ctx->shop->name);
out('Shop analysé (?shop=): ' . $shopId);

$langsGlobal = $db->executeS('SELECT id_lang, name, iso_code, active FROM `' . $prefix . 'lang` ORDER BY id_lang');
out('');
out('ps_lang (global):');
foreach ($langsGlobal ?: [] as $l) {
    out(sprintf('  id=%d iso=%s active=%s name=%s', $l['id_lang'], $l['iso_code'], $l['active'], $l['name']));
}

$langsShop = $db->executeS(
    'SELECT l.id_lang, l.iso_code, l.active, ls.id_shop
     FROM `' . $prefix . 'lang` l
     INNER JOIN `' . $prefix . 'lang_shop` ls ON ls.id_lang = l.id_lang
     WHERE ls.id_shop = ' . (int) $shopId . '
     ORDER BY l.id_lang'
);
out('');
out('ps_lang_shop (shop ' . $shopId . '):');
if (!$langsShop) {
    warn('Aucune langue associée à cette boutique dans ps_lang_shop');
} else {
    foreach ($langsShop as $l) {
        out(sprintf('  id=%d iso=%s active=%s', $l['id_lang'], $l['iso_code'], $l['active']));
    }
}

$activeGlobal = (int) $db->getValue('SELECT COUNT(*) FROM `' . $prefix . 'lang` WHERE active = 1');
$activeShop = (int) Language::countActiveLanguages($shopId);
$multiGlobal = Language::isMultiLanguageActivated(null);
$multiShop = Language::isMultiLanguageActivated($shopId);
$defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

out('');
out('Compteurs:');
out('  langues actives (global ps_lang):        ' . $activeGlobal);
out('  langues actives (shop via lang_shop):     ' . $activeShop);
out('  Language::isMultiLanguageActivated(null): ' . ($multiGlobal ? 'OUI' : 'NON'));
out('  Language::isMultiLanguageActivated(' . $shopId . '):  ' . ($multiShop ? 'OUI' : 'NON'));
out('  PS_LANG_DEFAULT:                          ' . $defaultLang);

if (!$multiShop) {
    fail('isMultiLanguageActivated(' . $shopId . ') = NON → le module masque l\'option ISO (2+ langues actives requises pour la boutique)');
    out('       Vérifier ps_lang.active=1 ET présence dans ps_lang_shop pour chaque langue.');
} else {
    ok('Multilangue OK pour shop ' . $shopId . ' — condition UI remplie côté langues');
}

// ---------------------------------------------------------------------------
// 2. Config module
// ---------------------------------------------------------------------------
hr('2. Configuration ETS_AWU_ENABLE_REMOVE_LANG_CODE_IN_URL');

$cfgRows = $db->executeS(
    'SELECT name, value, id_shop, id_shop_group FROM `' . $prefix . 'configuration`
     WHERE name = "' . pSQL($configKey) . '"
     ORDER BY id_shop'
);
if (!$cfgRows) {
    info('Clé absente en base (normal si jamais activée — l\'option BO doit quand même s\'afficher)');
} else {
    foreach ($cfgRows as $c) {
        out(sprintf('  shop=%s value=%s', $c['id_shop'], $c['value']));
    }
}

$allEtsCfg = $db->executeS(
    'SELECT name, value, id_shop FROM `' . $prefix . 'configuration`
     WHERE name LIKE "ETS_AWU%" ORDER BY name, id_shop LIMIT 30'
);
out('Config ETS_AWU* (max 30): ' . count($allEtsCfg ?: []));
foreach ($allEtsCfg ?: [] as $c) {
    out(sprintf('  %s = %s (shop %s)', $c['name'], strlen((string) $c['value']) > 50 ? substr($c['value'], 0, 47) . '...' : $c['value'], $c['id_shop']));
}

// ---------------------------------------------------------------------------
// 3. Fichiers override AdminMeta (UI BO)
// ---------------------------------------------------------------------------
hr('3. AdminMetaController (override global + module)');

$adminMetaPaths = [
    'override global' => __DIR__ . '/override/controllers/admin/AdminMetaController.php',
    'module override' => $moduleDir . '/override/controllers/admin/AdminMetaController.php',
    'module .off' => $moduleDir . '/override/controllers/admin/AdminMetaController.php.off',
];

foreach ($adminMetaPaths as $label => $path) {
    if (is_file($path)) {
        out($label . ': OUI (' . filesize($path) . ' o) → ' . $path);
        if (filesize($path) < 50000) {
            out('--- contenu ---');
            out(trim((string) file_get_contents($path)));
            out('--- fin ---');
        }
    } else {
        out($label . ': ABSENT');
    }
}

// Classe compilée dans l'index override ?
$classIndex = __DIR__ . '/var/cache/prod/class_index.php';
if (!is_file($classIndex)) {
    $classIndex = __DIR__ . '/var/cache/dev/class_index.php';
}
if (is_file($classIndex)) {
    $idx = file_get_contents($classIndex);
    if (strpos($idx, 'AdminMetaController') !== false) {
        ok('class_index.php référence AdminMetaController');
        if (preg_match('/AdminMetaController[^\n]+/', $idx, $m)) {
            out('  extrait: ' . substr($m[0], 0, 120));
        }
    } else {
        warn('AdminMetaController absent de class_index.php — regénérer cache après ajout override');
    }
} else {
    info('class_index.php introuvable (cache vidé ?)');
}

// ---------------------------------------------------------------------------
// 4. Recherche dans le module (strings, même ionCube)
// ---------------------------------------------------------------------------
hr('4. Recherche dans modules/ets_awesomeurl/ (strings)');

if (!is_dir($moduleDir)) {
    fail('Module absent: ' . $moduleDir);
} else {
    ok('Module dir OK');
    $scan = scanModuleDir($moduleDir, $needles);
    if (!$scan) {
        warn('Aucune chaîne ISO/AdminMeta trouvée en clair dans php/js/tpl (ionCube probable sur le main)');
    } else {
        out('Fichiers avec chaînes pertinentes: ' . count($scan));
        foreach ($scan as $file => $hits) {
            out('');
            out('  [' . $file . ']');
            foreach (array_slice($hits, 0, 15) as $h) {
                out('    ' . $h);
            }
            if (count($hits) > 15) {
                out('    ... +' . (count($hits) - 15) . ' autres');
            }
        }
    }
}

// Fichiers JS admin probables
hr('5. Assets JS admin du module');
$jsCandidates = glob($moduleDir . '/views/js/*.js') ?: [];
$jsCandidates = array_merge($jsCandidates, glob($moduleDir . '/views/js/admin/*.js') ?: []);
$jsCandidates = array_merge($jsCandidates, glob($moduleDir . '/views/js/back/*.js') ?: []);
if (!$jsCandidates) {
    info('Pas de views/js/*.js trouvé à la racine');
} else {
    foreach ($jsCandidates as $js) {
        $rel = str_replace($moduleDir . '/', '', $js);
        $hits = grepStrings(extractStrings($js), $needles);
        out($rel . ' (' . filesize($js) . ' o)' . ($hits ? ' → ' . implode(' | ', array_slice($hits, 0, 5)) : ''));
    }
}

// ---------------------------------------------------------------------------
// 6. Hooks module (injection BO Symfony)
// ---------------------------------------------------------------------------
hr('6. Hooks enregistrés (displayBackOfficeHeader, etc.)');

$idModule = (int) $db->getValue(
    'SELECT id_module FROM `' . $prefix . 'module` WHERE name = "' . pSQL($moduleName) . '"'
);
out('id_module: ' . ($idModule ?: 'ABSENT'));
out('Module::isEnabled: ' . (Module::isEnabled($moduleName) ? 'OUI' : 'NON'));

if ($idModule) {
    $hooks = $db->executeS(
        'SELECT h.name, hm.id_shop, hm.position
         FROM `' . $prefix . 'hook_module` hm
         JOIN `' . $prefix . 'hook` h ON h.id_hook = hm.id_hook
         WHERE hm.id_module = ' . $idModule . '
         ORDER BY h.name, hm.id_shop'
    );
    out('hook_module rows: ' . count($hooks ?: []));
    foreach ($hooks ?: [] as $h) {
        out('  ' . $h['name'] . ' shop=' . $h['id_shop']);
    }
    if (!$hooks) {
        warn('0 hooks — injection JS BO via displayBackOfficeHeader impossible');
        out('       Les cases « retirer IDs » peuvent venir d\'AdminMetaController ou JS inline autre source.');
    }
}

// ---------------------------------------------------------------------------
// 7. Page BO PS 8.2 = Symfony (pas legacy renderOptions)
// ---------------------------------------------------------------------------
hr('7. Contexte PrestaShop 8.2 (Symfony MetaController)');

out('PS version: ' . _PS_VERSION_);
out('Page SEO/URL = Symfony MetaController + twig (UrlSchemaType, SetUpUrlType)');
out('AdminMetaController::renderOptions() legacy peut ne PAS alimenter le bloc Symfony.');
out('Le module ETS injecte souvent via JS (hooks) ou extension AdminMeta pour l\'option ISO.');

// ---------------------------------------------------------------------------
// 8. Synthèse
// ---------------------------------------------------------------------------
hr('8. Synthèse — pourquoi l\'option ISO ne s\'affiche pas');

$issues = [];

if (!$multiShop) {
    $issues[] = 'BLOQUANT: isMultiLanguageActivated(shop ' . $shopId . ') = false. Le module (Link.php + UI) n\'affiche pas l\'option sans 2+ langues actives liées à la boutique via ps_lang_shop.';
}

$globalAdminMeta = __DIR__ . '/override/controllers/admin/AdminMetaController.php';
if (!is_file($globalAdminMeta)) {
    $issues[] = 'AdminMetaController global absent dans /override/controllers/admin/ — copier depuis modules/ets_awesomeurl/override/ + vider cache.';
}

if ($idModule && empty($hooks)) {
    $issues[] = '0 hooks enregistrés — si l\'UI ISO dépend de displayBackOfficeHeader (JS), elle ne s\'injectera pas. Réinstaller/reset hooks du module (sans toucher overrides git).';
}

if ((int) $ctx->shop->id !== $shopId && Shop::isFeatureActive()) {
    $issues[] = 'Tester en BO avec sélecteur sur Districlos (shop 1), pas « Toutes les boutiques ».';
}

if (!$issues) {
    ok('Conditions langues + AdminMeta OK — comparer avec DEV:');
    out('  1) Où exactement l\'option apparaît sur dev (Format URLs vs Config module)');
    out('  2) strings ISO dans views/js du module');
    out('  3) Reset hooks module si JS manquant');
} else {
    foreach ($issues as $i => $issue) {
        out('  ' . ($i + 1) . '. ' . $issue);
    }
}

out('');
out('Vérif SQL rapide lang_shop:');
out('  SELECT l.id_lang,l.iso_code,l.active FROM ps_lang l');
out('  JOIN ps_lang_shop ls ON ls.id_lang=l.id_lang WHERE ls.id_shop=1;');
out('');
out('SUPPRIMER: rm ' . __FILE__);
out('FIN — lecture seule.');
