<?php
/**
 * DIAG PROVISOIRE — à supprimer après usage
 * Usage navigateur :
 *   https://dev.cloture-discount.fr/diag_configurateur.php?key=ckc-diag-2026
 * Usage CLI (si ionCube OK) :
 *   php diag_configurateur.php
 */
$key = 'ckc-diag-2026';
if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
    if (!isset($_GET['key']) || $_GET['key'] !== $key) {
        http_response_code(403);
        exit("Forbidden. Ajoutez ?key={$key}\n");
    }
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== DIAG CONFIGURATEUR ===\n";
echo 'date: ' . date('c') . "\n";
echo 'cwd: ' . __DIR__ . "\n\n";

$candidates = [
    'ckcconfigurateurdistriclos',
    'ckc_configurator',
];

echo "--- Dossiers modules ---\n";
foreach ($candidates as $name) {
    $dir = __DIR__ . '/modules/' . $name;
    echo $name . ': ' . (is_dir($dir) ? 'PRESENT' : 'ABSENT') . "\n";
    $front = $dir . '/controllers/front';
    if (is_dir($front)) {
        foreach (scandir($front) as $f) {
            if (substr($f, -4) === '.php' && $f !== 'index.php') {
                echo "  controller: {$f}\n";
            }
        }
    }
}
echo "\n";

require __DIR__ . '/config/config.inc.php';

echo "--- Shop / context ---\n";
$ctx = Context::getContext();
echo 'shop id: ' . (int) $ctx->shop->id . "\n";
echo 'shop name: ' . $ctx->shop->name . "\n";
echo 'lang id/iso: ' . (int) $ctx->language->id . ' / ' . $ctx->language->iso_code . "\n";
echo 'base uri: ' . __PS_BASE_URI__ . "\n";
echo 'link protocol: ' . (Tools::usingSecureMode() ? 'https' : 'http') . "\n\n";

echo "--- Modules en base ---\n";
$rows = Db::getInstance()->executeS(
    "SELECT m.id_module, m.name, m.active, m.version,
            GROUP_CONCAT(CONCAT(ms.id_shop, ':', ms.enable_device) SEPARATOR ', ') AS shops
     FROM `" . _DB_PREFIX_ . "module` m
     LEFT JOIN `" . _DB_PREFIX_ . "module_shop` ms ON ms.id_module = m.id_module
     WHERE m.name IN ('ckcconfigurateurdistriclos', 'ckc_configurator', 'ets_awesomeurl')
     GROUP BY m.id_module"
);
if (!$rows) {
    echo "(aucune ligne)\n";
} else {
    foreach ($rows as $r) {
        echo sprintf(
            "%s | id=%s | active=%s | version=%s | shops=%s\n",
            $r['name'],
            $r['id_module'],
            $r['active'],
            $r['version'],
            $r['shops'] ?: '-'
        );
    }
}
echo "\n";

echo "--- Module::isEnabled / instance ---\n";
foreach (array_merge($candidates, ['ets_awesomeurl']) as $name) {
    $enabled = Module::isEnabled($name) ? 'oui' : 'non';
    $inst = Module::getInstanceByName($name);
    echo sprintf(
        "%s | enabled=%s | instance=%s | class=%s\n",
        $name,
        $enabled,
        $inst ? 'oui' : 'non',
        $inst ? get_class($inst) : '-'
    );
}
echo "\n";

echo "--- URLs générées (Link) ---\n";
$link = $ctx->link;
foreach ($candidates as $name) {
    if (!Module::isEnabled($name)) {
        echo "{$name}: skip (disabled)\n";
        continue;
    }
    foreach (['configurator', 'default', 'index'] as $ctrl) {
        try {
            $url = $link->getModuleLink($name, $ctrl);
            echo "{$name}/{$ctrl}: {$url}\n";
        } catch (Exception $e) {
            echo "{$name}/{$ctrl}: EXCEPTION " . $e->getMessage() . "\n";
        }
    }
}
echo "\n";

echo "--- Meta / url_rewrite contenant configur ---\n";
$metas = Db::getInstance()->executeS(
    "SELECT m.id_meta, m.page, ml.id_lang, ml.url_rewrite, ml.title
     FROM `" . _DB_PREFIX_ . "meta` m
     LEFT JOIN `" . _DB_PREFIX_ . "meta_lang` ml ON ml.id_meta = m.id_meta
     WHERE m.page LIKE '%configur%' OR ml.url_rewrite LIKE '%configur%'"
);
if (!$metas) {
    echo "(aucune)\n";
} else {
    foreach ($metas as $m) {
        echo sprintf(
            "id_meta=%s page=%s lang=%s rewrite=%s title=%s\n",
            $m['id_meta'],
            $m['page'],
            $m['id_lang'],
            $m['url_rewrite'],
            $m['title']
        );
    }
}
echo "\n";

echo "--- Config ETS_AWU ---\n";
$cfgs = Db::getInstance()->executeS(
    "SELECT name, value FROM `" . _DB_PREFIX_ . "configuration`
     WHERE name LIKE 'ETS_AWU%' ORDER BY name"
);
if (!$cfgs) {
    echo "(aucune)\n";
} else {
    foreach ($cfgs as $c) {
        echo $c['name'] . ' = ' . $c['value'] . "\n";
    }
}
echo "\n";

echo "--- Dispatcher controller pour /configurateur ---\n";
$_SERVER['REQUEST_URI'] = __PS_BASE_URI__ . 'configurateur';
$_GET = [];
try {
    // Ne pas casser le singleton trop fort : juste afficher request
    echo 'REQUEST_URI simulé: ' . $_SERVER['REQUEST_URI'] . "\n";
    echo 'PS_REWRITING_SETTINGS: ' . Configuration::get('PS_REWRITING_SETTINGS') . "\n";
} catch (Exception $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
}

echo "\n=== FIN DIAG — SUPPRIMER ce fichier ===\n";
echo "rm " . __FILE__ . "\n";
