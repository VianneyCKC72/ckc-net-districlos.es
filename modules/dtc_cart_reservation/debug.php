<?php
/**
 * Script de diagnostic dtc_cart_reservation — PROD uniquement, à SUPPRIMER après usage.
 *
 * URL : https://votre-site.fr/modules/dtc_cart_reservation/debug.php?token=VOTRE_TOKEN
 * Changez DTC_DEBUG_TOKEN ci-dessous avant déploiement.
 */

define('DTC_DEBUG_TOKEN', 'districlos-dtc-debug-2026');

$providedToken = isset($_GET['token']) ? (string) $_GET['token'] : '';

if ($providedToken === '' || !hash_equals(DTC_DEBUG_TOKEN, $providedToken)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Accès refusé. Utilisez ?token=...');
}

$rootDir = dirname(__FILE__, 3);
$configFile = $rootDir . '/config/config.inc.php';

if (!is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('config.inc.php introuvable : ' . $configFile);
}

require_once $configFile;

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$debugError = null;
$moduleName = 'dtc_cart_reservation';

function dtcDebugStatus($ok)
{
    return $ok ? 'ok' : 'ko';
}

function dtcDebugRow($label, $ok, $detail = '')
{
    return [
        'label' => $label,
        'status' => dtcDebugStatus($ok),
        'detail' => $detail,
    ];
}

try {
$moduleDir = _PS_MODULE_DIR_ . $moduleName . '/';
$checks = [];

// --- Fichiers module ---
$requiredFiles = [
    'dtc_cart_reservation.php',
    'config.xml',
    'views/templates/hook/banner.tpl',
    'views/js/cart-reservation.js',
    'views/js/reset-bridge.js',
    'views/css/cart-reservation.css',
];

foreach ($requiredFiles as $file) {
    $path = $moduleDir . $file;
    $checks[] = dtcDebugRow('Fichier : ' . $file, is_file($path), $path);
}

// --- Module BDD & chargement PHP ---
$moduleRow = Db::getInstance()->getRow(
    'SELECT id_module, name, active, version
     FROM `' . _DB_PREFIX_ . 'module`
     WHERE name = "' . pSQL($moduleName) . '"'
);
$moduleInstalled = is_array($moduleRow) && !empty($moduleRow['id_module']);
$idModule = $moduleInstalled ? (int) $moduleRow['id_module'] : 0;

$checks[] = dtcDebugRow(
    'Module installé en BDD',
    $moduleInstalled,
    $moduleInstalled
        ? 'id_module=' . $idModule . ', active=' . (int) $moduleRow['active'] . ', version=' . $moduleRow['version']
        : 'Aucune ligne dans ps_module'
);

$moduleInstance = Module::getInstanceByName($moduleName);
$checks[] = dtcDebugRow(
    'Module::getInstanceByName() charge la classe',
    $moduleInstance instanceof Module,
    $moduleInstance instanceof Module ? get_class($moduleInstance) . ' v' . $moduleInstance->version : 'Retourne false — fichier PHP ou classe manquant'
);

$bannerTplPath = $moduleDir . 'views/templates/hook/banner.tpl';
$bannerTplContent = is_file($bannerTplPath) ? file_get_contents($bannerTplPath) : '';
$checks[] = dtcDebugRow(
    'banner.tpl contient data-duration',
    strpos($bannerTplContent, 'data-duration') !== false,
    strpos($bannerTplContent, 'data-duration') !== false ? 'OK — config via HTML (v1.0.1+)' : 'Manquant — déployer banner.tpl à jour'
);
$checks[] = dtcDebugRow(
    'banner.tpl charge JS inline (v1.0.3)',
    strpos($bannerTplContent, 'dtc_reservation_js_url') !== false,
    strpos($bannerTplContent, 'dtc_reservation_js_url') !== false ? 'OK — variable Smarty dtc_reservation_js_url' : 'Manquant — redéployer banner.tpl v1.0.3'
);
$checks[] = dtcDebugRow(
    'banner.tpl timer de secours inline',
    strpos($bannerTplContent, 'dtcCartReservationInlineBoot') !== false,
    strpos($bannerTplContent, 'dtcCartReservationInlineBoot') !== false ? 'OK — affichage garanti sans JS externe' : 'Manquant — redéployer banner.tpl v1.0.3'
);

// --- Configuration ---
$active = (int) Configuration::get('DTC_CART_RESERVATION_ACTIVE');
$duration = (int) Configuration::get('DTC_CART_RESERVATION_DURATION');

$checks[] = dtcDebugRow(
    'DTC_CART_RESERVATION_ACTIVE',
    $active === 1,
    'valeur = ' . $active
);
$checks[] = dtcDebugRow(
    'DTC_CART_RESERVATION_DURATION',
    $duration > 0,
    'valeur = ' . $duration . ' min'
);

// --- Hooks ---
$hookNames = ['displayHeader', 'displayBeforeCartOverview', 'displayAfterBodyOpeningTag', 'displayShoppingCart', 'actionFrontControllerSetMedia'];
$registeredHooks = [];

if ($idModule > 0) {
    $hookRows = Db::getInstance()->executeS(
        'SELECT h.name, hm.id_shop, hm.position
         FROM `' . _DB_PREFIX_ . 'hook_module` hm
         INNER JOIN `' . _DB_PREFIX_ . 'hook` h ON h.id_hook = hm.id_hook
         WHERE hm.id_module = ' . (int) $idModule
    );

    if (is_array($hookRows)) {
        foreach ($hookRows as $row) {
            $registeredHooks[$row['name']][] = 'shop ' . (int) $row['id_shop'] . ' (pos ' . (int) $row['position'] . ')';
        }
    }
}

foreach ($hookNames as $hookName) {
    $hookExists = (bool) Hook::getIdByName($hookName);
    $isRegistered = isset($registeredHooks[$hookName]);
    $detail = 'hook en BDD : ' . ($hookExists ? 'oui' : 'non');
    if ($isRegistered) {
        $detail .= ' | module accroché : ' . implode(', ', $registeredHooks[$hookName]);
    } else {
        $detail .= ' | module NON accroché';
    }
    $checks[] = dtcDebugRow('Hook : ' . $hookName, $hookExists && $isRegistered, $detail);
}

// --- Méthodes hook ---
if ($moduleInstance instanceof Module) {
    $checks[] = dtcDebugRow(
        'Méthode hookDisplayHeader',
        method_exists($moduleInstance, 'hookDisplayHeader'),
        method_exists($moduleInstance, 'hookDisplayHeader') ? 'présente' : 'absente'
    );
    $checks[] = dtcDebugRow(
        'Méthode hookDisplayBeforeCartOverview',
        method_exists($moduleInstance, 'hookDisplayBeforeCartOverview'),
        method_exists($moduleInstance, 'hookDisplayBeforeCartOverview') ? 'présente' : 'absente'
    );
}

// --- Thème cart.tpl ---
$themeName = _THEME_NAME_;
$cartTplPath = _PS_THEME_DIR_ . 'templates/checkout/cart.tpl';
$cartTplExists = is_file($cartTplPath);
$cartTplHasHook = false;
$cartTplSnippet = '';

if ($cartTplExists) {
    $cartTplContent = file_get_contents($cartTplPath);
    $cartTplHasHook = strpos($cartTplContent, "displayBeforeCartOverview") !== false;
    if (preg_match('/.*displayBeforeCartOverview.*/', $cartTplContent, $matches)) {
        $cartTplSnippet = trim($matches[0]);
    }
}

$checks[] = dtcDebugRow(
    'cart.tpl thème actif (' . $themeName . ')',
    $cartTplExists,
    $cartTplPath
);
$checks[] = dtcDebugRow(
    'cart.tpl contient {hook h=\'displayBeforeCartOverview\'}',
    $cartTplHasHook,
    $cartTplSnippet !== '' ? $cartTplSnippet : 'Ligne hook introuvable'
);

// --- Contexte panier (simulation front cart) ---
$cart = Context::getContext()->cart;
$nbProducts = ($cart && method_exists($cart, 'nbProducts')) ? (int) $cart->nbProducts() : 0;
$checks[] = dtcDebugRow(
    'Panier session courante (cookie navigateur)',
    true,
    'nbProducts = ' . $nbProducts . ($nbProducts === 0 ? ' — le bandeau ne s\'affiche pas si panier vide' : '')
);

$hookOutput = '';
if ($moduleInstance instanceof Module && $active === 1) {
    $version = $moduleInstance->version;
    $durationSeconds = max(1, $duration) * 60;
    $moduleInstance->context->smarty->assign([
        'dtc_reservation_duration_seconds' => $durationSeconds,
        'dtc_reservation_reposition' => false,
        'dtc_reservation_css_url' => Context::getContext()->link->getMediaLink(
            'modules/' . $moduleName . '/views/css/cart-reservation.css?v=' . $version
        ),
        'dtc_reservation_js_url' => Context::getContext()->link->getMediaLink(
            'modules/' . $moduleName . '/views/js/cart-reservation.js?v=' . $version
        ),
    ]);
    $hookOutput = $moduleInstance->display(
        _PS_MODULE_DIR_ . $moduleName . '/' . $moduleName . '.php',
        'views/templates/hook/banner.tpl'
    );
}
$checks[] = dtcDebugRow(
    'Rendu banner.tpl (simulation forcée)',
    $hookOutput !== '' && strpos($hookOutput, 'dtc-reserved-banner') !== false,
    $hookOutput !== ''
        ? 'HTML généré (' . strlen($hookOutput) . ' octets), data-duration présent : ' . (strpos($hookOutput, 'data-duration') !== false ? 'oui' : 'non')
        : 'Échec rendu Smarty — vérifier banner.tpl et droits fichiers'
);
$checks[] = dtcDebugRow(
    'Sortie hookDisplayBeforeCartOverview (panier session)',
    $nbProducts === 0,
    $nbProducts === 0
        ? 'Ignoré — nbProducts = 0 sur debug.php (normal). Tester sur /panier avec produits.'
        : 'Panier détecté — tester directement sur /panier'
);

// --- Performance / CCC ---
$checks[] = dtcDebugRow(
    'PS_JS_THEME_CACHE (Smart cache JS)',
    true,
    Configuration::get('PS_JS_THEME_CACHE') ? 'ACTIVÉ — risque dtcCartReservation undefined si ordre scripts incorrect' : 'désactivé'
);
$checks[] = dtcDebugRow(
    'PS_CSS_THEME_CACHE (Smart cache CSS)',
    true,
    Configuration::get('PS_CSS_THEME_CACHE') ? 'activé' : 'désactivé'
);
$checks[] = dtcDebugRow(
    'PS_SMARTY_CACHE',
    true,
    Configuration::get('PS_SMARTY_CACHE') ? 'activé' : 'désactivé'
);

// --- URLs assets ---
$baseUri = Tools::getShopDomainSsl(true) . __PS_BASE_URI__;
$assetUrls = [
    'reset-bridge.js' => $baseUri . 'modules/' . $moduleName . '/views/js/reset-bridge.js',
    'cart-reservation.js' => $baseUri . 'modules/' . $moduleName . '/views/js/cart-reservation.js',
    'cart-reservation.css' => $baseUri . 'modules/' . $moduleName . '/views/css/cart-reservation.css',
];

$assetReachable = [];
foreach ($assetUrls as $label => $url) {
    $reachable = false;
    $httpCode = 'n/a';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $reachable = $httpCode === 200;
    }

    $assetReachable[] = dtcDebugRow(
        'Asset HTTP ' . $label,
        $reachable,
        $url . ' → HTTP ' . $httpCode
    );
}

$checks = array_merge($checks, $assetReachable);

// --- JS attendu sur page panier ---
$expectedJsDef = [
    'duration' => max(1, $duration) * 60,
    'deadlineKey' => 'dtc_cart_reservation_deadline',
    'resetFlagKey' => 'dtc_cart_reservation_reset',
];

$failCount = 0;
foreach ($checks as $check) {
    if ($check['status'] === 'ko') {
        ++$failCount;
    }
}

$shopId = (Context::getContext()->shop && isset(Context::getContext()->shop->id))
    ? (int) Context::getContext()->shop->id
    : 0;

} catch (Throwable $e) {
    $debugError = $e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString();
    $checks = isset($checks) && is_array($checks) ? $checks : [];
    $failCount = 1;
    $expectedJsDef = [];
    $themeName = defined('_THEME_NAME_') ? _THEME_NAME_ : 'n/a';
    $shopId = 0;
    $moduleDir = isset($moduleDir) ? $moduleDir : '';
    $providedToken = isset($providedToken) ? $providedToken : '';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <title>Debug dtc_cart_reservation</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 24px; background: #f5f5f5; color: #1a1a1a; }
        h1 { font-size: 1.4rem; margin-bottom: 8px; }
        .meta { color: #555; margin-bottom: 20px; font-size: 14px; }
        .summary { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .summary.ok { background: #e6f4ea; color: #137333; }
        .summary.ko { background: #fce8e6; color: #c5221f; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #eee; vertical-align: top; font-size: 14px; }
        th { background: #fafafa; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .badge.ok { background: #e6f4ea; color: #137333; }
        .badge.ko { background: #fce8e6; color: #c5221f; }
        pre { background: #f0f0f0; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 13px; }
        .warn { background: #fff8e1; border-left: 4px solid #f9a825; padding: 12px 16px; margin: 20px 0; border-radius: 4px; font-size: 14px; }
    </style>
</head>
<body>
    <h1>Debug — dtc_cart_reservation</h1>
    <p class="meta">
        <?= htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8') ?>
        | PrestaShop <?= htmlspecialchars(_PS_VERSION_, ENT_QUOTES, 'UTF-8') ?>
        | Boutique #<?= (int) $shopId ?>
        | Thème <?= htmlspecialchars($themeName, ENT_QUOTES, 'UTF-8') ?>
    </p>

    <div class="summary <?= $failCount === 0 ? 'ok' : 'ko' ?>">
        <?= $failCount === 0 ? 'Tous les contrôles sont OK.' : $failCount . ' contrôle(s) en échec.' ?>
    </div>

    <?php if (!empty($debugError)) : ?>
        <div class="warn">
            <strong>Erreur PHP capturée :</strong>
            <pre><?= htmlspecialchars($debugError, ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Contrôle</th>
                <th>Statut</th>
                <th>Détail</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($checks as $check) : ?>
                <tr>
                    <td><?= htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= htmlspecialchars($check['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($check['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Journal du hook — Configuration (fiable)</h2>
    <p>Trace écrite à chaque exécution de hook. Visitez <strong>/panier</strong> avec des produits, puis rechargez.</p>
    <?php
    $configTrace = (string) Configuration::get('DTC_CART_RESERVATION_DEBUG_TRACE');
    if ($configTrace !== '') {
        echo '<pre>' . htmlspecialchars($configTrace, ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '<p><a href="?token=' . htmlspecialchars($providedToken, ENT_QUOTES, 'UTF-8') . '&clearlog=1">Vider les traces</a></p>';
    } else {
        echo '<pre>Aucune trace. Après visite de /panier avec produits :'
            . "\n" . ' → si toujours vide : le module n\'est pas exécuté (cache page HTTP)'
            . "\n" . ' → vérifier que la version 1.0.8 est déployée et le cache vidé.</pre>";
    }
    ?>

    <h2>Journal du hook — Base de données (ps_log)</h2>
    <p>Fiable même si le dossier module n'est pas accessible en écriture. Visitez <strong>/panier</strong> AVEC des produits, puis rechargez cette page.</p>
    <?php
    if (isset($_GET['clearlog'])) {
        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'log` WHERE object_type = "dtc_cart_reservation"');
        Configuration::deleteByName('DTC_CART_RESERVATION_DEBUG_TRACE');
        @unlink($moduleDir . 'dtc_hook.log');
        echo '<p><strong>Logs vidés. Rechargez la page.</strong></p>';
    }

    $dbLogs = Db::getInstance()->executeS(
        'SELECT date_add, message
         FROM `' . _DB_PREFIX_ . 'log`
         WHERE object_type = "dtc_cart_reservation"
         ORDER BY id_log DESC
         LIMIT 25'
    );

    if (is_array($dbLogs) && count($dbLogs) > 0) {
        $lines = [];
        foreach ($dbLogs as $row) {
            $lines[] = $row['date_add'] . ' | ' . $row['message'];
        }
        echo '<pre>' . htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '<p><a href="?token=' . htmlspecialchars($providedToken, ENT_QUOTES, 'UTF-8') . '&clearlog=1">Vider les logs</a></p>';
    } else {
        echo '<pre>Aucun log en base. Après avoir visité /panier, si rien n\'apparaît ici,'
            . "\n" . 'le module n\'est PAS exécuté sur la page panier :'
            . "\n" . ' → cache de page HTTP en amont de PHP (reverse proxy / Varnish / CDN)'
            . "\n" . ' → ou page panier servie sans passer par PrestaShop.</pre>';
    }

    $logPath = $moduleDir . 'dtc_hook.log';
    if (is_file($logPath)) {
        $logLines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logLines = is_array($logLines) ? array_slice($logLines, -25) : [];
        echo '<h3>Journal fichier (dtc_hook.log)</h3>';
        echo '<pre>' . htmlspecialchars(implode("\n", $logLines), ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        echo '<h3>Journal fichier</h3><pre>Fichier dtc_hook.log absent (dossier probablement non accessible en écriture — normal, on utilise la base).</pre>';
    }
    ?>

    <h2>Variable JS attendue sur /panier</h2>
    <p>Dans la console du navigateur sur la page panier : <code>typeof dtcCartReservation</code> doit retourner <code>"object"</code>.</p>
    <pre><?= htmlspecialchars(json_encode($expectedJsDef, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>

    <h2>Test navigateur (page panier)</h2>
    <ol>
        <li>Ouvrir <strong>/panier</strong> avec au moins 1 produit (pas depuis debug.php).</li>
        <li>Ctrl+U → chercher <code>dtc-reserved-banner</code> :
            <ul>
                <li><strong>Absent</strong> → cache Smarty / ckcspcache : vider <code>var/cache/prod/smarty/</code> + cache module cache</li>
                <li><strong>Présent</strong> → le JS doit l'afficher en moins de 1 seconde</li>
            </ul>
        </li>
        <li>Console : <code>document.getElementById('dtc-reserved-banner')</code> puis <code>typeof dtcCartReservationBoot</code></li>
    </ol>

    <div class="warn">
        <strong>Sécurité :</strong> supprimez ce fichier (<code>modules/dtc_cart_reservation/debug.php</code>) après diagnostic.
        Changez aussi <code>DTC_DEBUG_TOKEN</code> si vous le gardez temporairement.
    </div>
</body>
</html>
