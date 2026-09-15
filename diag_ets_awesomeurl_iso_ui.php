<?php
/**
 * DIAG UI ISO — après enregistrement hooks (PROD, lecture seule)
 *
 * https://www.cloture-discount.fr/diag_ets_awesomeurl_iso_ui.php?key=ckc-awu-iso-ui-2026
 * SUPPRIMER après usage.
 */
$key = 'ckc-awu-iso-ui-2026';
$moduleName = 'ets_awesomeurl';

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
    if (!isset($_GET['key']) || $_GET['key'] !== $key) {
        http_response_code(403);
        exit("Forbidden. ?key={$key}\n");
    }
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

function out($m = '') { echo $m . "\n"; }
function hr($t = null) {
    out(str_repeat('=', 72));
    if ($t !== null) { out($t); out(str_repeat('-', 72)); }
}
function ok($m) { out('[OK]  ' . $m); }
function warn($m) { out('[!!]  ' . $m); }

require __DIR__ . '/config/config.inc.php';

$moduleDir = _PS_MODULE_DIR_ . $moduleName;
$shopId = isset($_GET['shop']) ? (int) $_GET['shop'] : 1;

out('DIAG UI ISO — hooks + templates + rendu hook');
out('date: ' . date('c'));
out('shop: ' . $shopId);
out('');

hr('1. Hooks enregistrés');
$idModule = (int) Db::getInstance()->getValue(
    'SELECT id_module FROM `' . _DB_PREFIX_ . 'module` WHERE name="' . pSQL($moduleName) . '"'
);
$hooks = Db::getInstance()->executeS(
    'SELECT h.name FROM `' . _DB_PREFIX_ . 'hook_module` hm
     JOIN `' . _DB_PREFIX_ . 'hook` h ON h.id_hook=hm.id_hook
     WHERE hm.id_module=' . $idModule . ' AND hm.id_shop=' . $shopId . '
     ORDER BY h.name'
) ?: [];
out('hooks shop ' . $shopId . ': ' . count($hooks));
$need = ['displayBackOfficeHeader', 'actionAdminMetaControllerUpdate_optionsBefore'];
foreach ($need as $h) {
    out('  ' . $h . ': ' . (in_array($h, array_column($hooks, 'name'), true) ? 'OK' : 'MANQUANT'));
}

hr('2. Fichiers UI critiques (contenu)');
$files = [
    'twig override' => $moduleDir . '/views/PrestaShop/Admin/Configure/ShopParameters/TrafficSeo/Meta/Blocks/url_schema_configuration.html.twig',
    'tpl ISO' => $moduleDir . '/views/templates/hook/remove_id_field.tpl',
    'admin.js' => $moduleDir . '/views/js/admin.js',
    'admin.css' => $moduleDir . '/views/css/admin.css',
    'EtsAwuDefine' => $moduleDir . '/classes/EtsAwuDefine.php',
];
foreach ($files as $label => $path) {
    out('');
    out('--- ' . $label . ' ---');
    out('path: ' . $path);
    if (!is_file($path)) {
        warn('ABSENT');
        continue;
    }
    ok(filesize($path) . ' octets');
    $content = file_get_contents($path);
    // Extraits pertinents
    if ($label === 'twig override' || $label === 'tpl ISO') {
        out($content);
    } else {
        foreach (explode("\n", $content) as $i => $line) {
            if (preg_match('/ISO|REMOVE_LANG|multilang|multishop|AdminMeta|remove_id|url_schema/i', $line)) {
                out(sprintf('%4d| %s', $i + 1, rtrim($line)));
            }
        }
    }
}

hr('3. Multilangue (condition affichage ISO)');
out('isMultiLanguageActivated(' . $shopId . '): ' . (Language::isMultiLanguageActivated($shopId) ? 'OUI' : 'NON'));
out('Shop::isFeatureActive(): ' . (Shop::isFeatureActive() ? 'OUI' : 'NON'));

hr('4. Simulation hook displayBackOfficeHeader (contexte AdminMeta)');
// Forcer contexte BO minimal
$ctx = Context::getContext();
if (!$ctx->employee || !Validate::isLoadedObject($ctx->employee)) {
    warn('Pas d\'employee en contexte web CLI/FPM — simulation partielle');
}
$_GET['controller'] = 'AdminMeta';
$_REQUEST['controller'] = 'AdminMeta';
if (isset($ctx->shop) && (int) $ctx->shop->id !== $shopId) {
    $ctx->shop = new Shop($shopId);
}

$html = Hook::exec('displayBackOfficeHeader');
$len = is_string($html) ? strlen($html) : 0;
out('Longueur sortie hook: ' . $len);
if ($len > 0) {
    $needles = ['ETS_AWU_ENABLE_REMOVE_LANG', 'Remove ISO', 'Supprimer le code ISO', 'remove_id_field', 'admin.js', 'ets_awesomeurl'];
    foreach ($needles as $n) {
        out('  contient "' . $n . '": ' . (stripos($html, $n) !== false ? 'OUI' : 'non'));
    }
    if (stripos($html, 'ISO') === false && stripos($html, 'ETS_AWU_ENABLE_REMOVE_LANG') === false) {
        warn('Hook ne renvoie pas le switch ISO — chercher condition dans hookDisplayBackOfficeHeader');
        out('--- extrait HTML (2000 premiers chars) ---');
        out(substr($html, 0, 2000));
    } else {
        ok('Switch ISO présent dans le HTML du hook');
        if (preg_match('/.{0,200}ETS_AWU_ENABLE_REMOVE_LANG.{0,400}/s', $html, $m)) {
            out('--- extrait ---');
            out($m[0]);
        }
    }
} else {
    warn('Hook displayBackOfficeHeader vide — ISO ne peut pas s\'injecter');
}

hr('5. Module actif dans ModuleTemplateLoader ?');
$repo = PrestaShop\PrestaShop\Adapter\Module\Repository\ModuleRepository::class;
if (class_exists($repo)) {
    try {
        $container = PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        if ($container && $container->has('prestashop.adapter.module.repository.module_repository')) {
            /** @var PrestaShop\PrestaShop\Adapter\Module\Repository\ModuleRepository $modRepo */
            $modRepo = $container->get('prestashop.adapter.module.repository.module_repository');
            $paths = $modRepo->getActiveModulesPaths();
            $found = false;
            foreach ($paths as $p) {
                if (strpos($p, $moduleName) !== false) {
                    $found = true;
                    ok('Chemin module actif Symfony: ' . $p);
                    $twigPath = $p . '/views/PrestaShop/Admin/Configure/ShopParameters/TrafficSeo/Meta/Blocks/url_schema_configuration.html.twig';
                    out('  twig override existe: ' . (is_file($twigPath) ? 'OUI' : 'NON'));
                }
            }
            if (!$found) {
                warn('Module absent de getActiveModulesPaths() — twig override ignoré');
            }
        }
    } catch (Throwable $e) {
        warn('ModuleRepository: ' . $e->getMessage());
    }
}

hr('6. Workaround SQL (activation ISO sans BO)');
out('Si UI reste invisible, activer quand même le comportement front:');
out("INSERT INTO `" . _DB_PREFIX_ . "configuration` (name, value, date_add, date_upd, id_shop, id_shop_group)");
out("VALUES ('ETS_AWU_ENABLE_REMOVE_LANG_CODE_IN_URL', '1', NOW(), NOW(), " . $shopId . ", 0)");
out('ON DUPLICATE KEY UPDATE value=\'1\', date_upd=NOW();');
out('Puis rm -rf var/cache/prod/*');

hr('7. Synthèse');
out('Si hook vide ou sans ISO → condition PHP dans hookDisplayBackOfficeHeader (controller, multilang, theme).');
out('Si hook contient ISO mais pas visible BO → CSS multishop (.multishop-enabled) ou JS append DOM.');
out('Comparer sortie section 4 avec dev si possible.');
out('SUPPRIMER: rm ' . __FILE__);
