<?php
/**
 * Compare les modules DTC (dtc_*) présents en prod et absents du dev.
 *
 * Répertoires par défaut :
 *   prod = districlos
 *   dev  = dev.cloture-discount
 *
 * Usage CLI :
 *   php scripts/compare_dtc_modules.php
 *   php scripts/compare_dtc_modules.php /chemin/districlos /chemin/dev.cloture-discount
 *
 * Usage HTTP :
 *   /scripts/compare_dtc_modules.php
 *   /scripts/compare_dtc_modules.php?prod=/chemin/districlos&dev=/chemin/dev.cloture-discount
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$prodDirName = 'districlos';
$devDirName = 'dev.cloture-discount';

$cli = (PHP_SAPI === 'cli');
$prodArg = $cli ? ($argv[1] ?? '') : (string) ($_GET['prod'] ?? '');
$devArg = $cli ? ($argv[2] ?? '') : (string) ($_GET['dev'] ?? '');

$startDirs = array_unique(array_filter([
    getcwd() ?: null,
    dirname(__DIR__),
    dirname(dirname(__DIR__)),
    isset($_SERVER['DOCUMENT_ROOT']) ? dirname($_SERVER['DOCUMENT_ROOT']) : null,
    isset($_SERVER['DOCUMENT_ROOT']) ? dirname(dirname($_SERVER['DOCUMENT_ROOT'])) : null,
]));

$prodRoot = $prodArg !== '' ? resolveShopRoot($prodArg) : findNamedShop($prodDirName, $startDirs);
$devRoot = $devArg !== '' ? resolveShopRoot($devArg) : findNamedShop($devDirName, $startDirs);

$errors = [];
if ($prodRoot === null || !is_dir($prodRoot . DIRECTORY_SEPARATOR . 'modules')) {
    $errors[] = 'Prod introuvable (dossier "' . $prodDirName . '" avec /modules). Passe le chemin en argument.';
}
if ($devRoot === null || !is_dir($devRoot . DIRECTORY_SEPARATOR . 'modules')) {
    $errors[] = 'Dev introuvable (dossier "' . $devDirName . '" avec /modules). Passe le chemin en argument.';
}

$prodModules = empty($errors) ? listDtcModules($prodRoot . DIRECTORY_SEPARATOR . 'modules') : [];
$devModules = empty($errors) ? listDtcModules($devRoot . DIRECTORY_SEPARATOR . 'modules') : [];

$onlyProd = [];
$onlyDev = [];
$both = [];

foreach ($prodModules as $name => $meta) {
    if (!isset($devModules[$name])) {
        $onlyProd[$name] = $meta;
    } else {
        $both[$name] = [
            'name' => $name,
            'displayName' => $meta['displayName'] !== '' ? $meta['displayName'] : $devModules[$name]['displayName'],
            'prod_version' => $meta['version'],
            'dev_version' => $devModules[$name]['version'],
            'version_diff' => $meta['version'] !== $devModules[$name]['version'],
        ];
    }
}

foreach ($devModules as $name => $meta) {
    if (!isset($prodModules[$name])) {
        $onlyDev[$name] = $meta;
    }
}

ksort($onlyProd);
ksort($onlyDev);
ksort($both);

$result = [
    'prod_root' => $prodRoot,
    'dev_root' => $devRoot,
    'counts' => [
        'prod' => count($prodModules),
        'dev' => count($devModules),
        'only_prod' => count($onlyProd),
        'only_dev' => count($onlyDev),
        'both' => count($both),
        'version_diff' => count(array_filter($both, static function ($row) {
            return $row['version_diff'];
        })),
    ],
    'only_prod' => $onlyProd,
    'both' => $both,
    'only_dev' => $onlyDev,
    'errors' => $errors,
];

if ($cli) {
    renderCli($result);
    exit(empty($errors) ? 0 : 1);
}

renderHtml($result);
exit;

/**
 * @param string[] $startDirs
 */
function findNamedShop($dirName, array $startDirs)
{
    foreach ($startDirs as $start) {
        if (!is_string($start) || $start === '' || !is_dir($start)) {
            continue;
        }

        $current = realpath($start) ?: $start;
        for ($i = 0; $i < 6; $i++) {
            $candidate = $current . DIRECTORY_SEPARATOR . $dirName;
            $resolved = resolveShopRoot($candidate);
            if ($resolved !== null) {
                return $resolved;
            }

            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }
            $current = $parent;
        }
    }

    return null;
}

function resolveShopRoot($path)
{
    $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    if ($path === '' || !is_dir($path)) {
        return null;
    }

    if (is_dir($path . DIRECTORY_SEPARATOR . 'modules')) {
        return $path;
    }

    foreach (['www', 'public', 'htdocs'] as $sub) {
        $withSub = $path . DIRECTORY_SEPARATOR . $sub;
        if (is_dir($withSub . DIRECTORY_SEPARATOR . 'modules')) {
            return $withSub;
        }
    }

    return null;
}

function listDtcModules($modulesDir)
{
    $out = [];
    $entries = @scandir($modulesDir);
    if ($entries === false) {
        return $out;
    }

    foreach ($entries as $name) {
        if ($name === '.' || $name === '..' || stripos($name, 'dtc_') !== 0) {
            continue;
        }

        $dir = $modulesDir . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($dir)) {
            continue;
        }

        $out[$name] = readModuleMeta($dir, $name);
    }

    ksort($out);

    return $out;
}

function readModuleMeta($dir, $name)
{
    $xmlFile = $dir . DIRECTORY_SEPARATOR . 'config.xml';
    $phpFile = $dir . DIRECTORY_SEPARATOR . $name . '.php';

    $meta = [
        'name' => $name,
        'displayName' => '',
        'version' => '',
        'author' => '',
        'has_php' => is_file($phpFile),
    ];

    if (is_file($xmlFile)) {
        $xml = @simplexml_load_file($xmlFile);
        if ($xml) {
            $meta['displayName'] = trim((string) $xml->displayName);
            $meta['version'] = trim((string) $xml->version);
            $meta['author'] = trim((string) $xml->author);
        }
    }

    if ($meta['version'] === '' && is_file($phpFile)) {
        $src = (string) file_get_contents($phpFile);
        if (preg_match("/\\\$this->version\s*=\s*'([^']+)'/", $src, $m)) {
            $meta['version'] = $m[1];
        }
        if ($meta['displayName'] === '' && preg_match("/\\\$this->displayName\s*=\s*\\\$this->l\\('([^']+)'/", $src, $m)) {
            $meta['displayName'] = $m[1];
        }
    }

    return $meta;
}

function renderCli(array $result)
{
    echo "Comparaison modules DTC\n";
    echo 'Prod : ' . ($result['prod_root'] ?: '(introuvable)') . "\n";
    echo 'Dev  : ' . ($result['dev_root'] ?: '(introuvable)') . "\n";
    echo str_repeat('-', 72) . "\n";

    foreach ($result['errors'] as $error) {
        echo '[ERREUR] ' . $error . "\n";
    }
    if ($result['errors']) {
        echo "\nExemple : php scripts/compare_dtc_modules.php /chemin/districlos /chemin/dev.cloture-discount\n";
        return;
    }

    echo sprintf(
        "Prod %d | Dev %d | En prod seulement %d | En commun %d | Versions différentes %d | En dev seulement %d\n\n",
        $result['counts']['prod'],
        $result['counts']['dev'],
        $result['counts']['only_prod'],
        $result['counts']['both'],
        $result['counts']['version_diff'],
        $result['counts']['only_dev']
    );

    echo "=== En PROD, absents du DEV ===\n";
    if (!$result['only_prod']) {
        echo "(aucun)\n";
    } else {
        foreach ($result['only_prod'] as $name => $meta) {
            echo sprintf(
                "- %-42s  v%-8s  %s\n",
                $name,
                $meta['version'] !== '' ? $meta['version'] : '?',
                $meta['displayName']
            );
        }
    }

    echo "\n=== Présents des deux côtés ===\n";
    if (!$result['both']) {
        echo "(aucun)\n";
    } else {
        foreach ($result['both'] as $name => $row) {
            $mark = $row['version_diff'] ? '  [version différente]' : '';
            echo sprintf(
                "- %-42s  prod v%-8s  dev v%-8s%s\n",
                $name,
                $row['prod_version'] !== '' ? $row['prod_version'] : '?',
                $row['dev_version'] !== '' ? $row['dev_version'] : '?',
                $mark
            );
        }
    }

    echo "\n=== En DEV, absents de la PROD ===\n";
    if (!$result['only_dev']) {
        echo "(aucun)\n";
    } else {
        foreach ($result['only_dev'] as $name => $meta) {
            echo sprintf(
                "- %-42s  v%-8s  %s\n",
                $name,
                $meta['version'] !== '' ? $meta['version'] : '?',
                $meta['displayName']
            );
        }
    }
}

function renderHtml(array $result)
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8">';
    echo '<title>Comparaison modules DTC</title>';
    echo '<style>
        body{font-family:Segoe UI,Arial,sans-serif;margin:24px;color:#222}
        h1{font-size:20px} h2{margin-top:28px;font-size:16px}
        .meta{color:#555;margin-bottom:16px}
        .err{background:#fdecea;color:#b71c1c;padding:10px 12px;border-radius:6px}
        table{border-collapse:collapse;width:100%;max-width:980px}
        th,td{border:1px solid #ddd;padding:8px 10px;text-align:left;font-size:14px}
        th{background:#f5f5f5}
        .ok{color:#2e7d32} .warn{color:#c62828} .muted{color:#777}
        code{background:#f3f3f3;padding:1px 4px}
    </style></head><body>';
    echo '<h1>Comparaison modules DTC</h1>';
    echo '<div class="meta">Prod : <code>' . htmlspecialchars((string) $result['prod_root']) . '</code><br>';
    echo 'Dev : <code>' . htmlspecialchars((string) $result['dev_root']) . '</code></div>';

    foreach ($result['errors'] as $error) {
        echo '<p class="err">' . htmlspecialchars($error) . '</p>';
    }
    if ($result['errors']) {
        echo '<p>Ajoute <code>?prod=/chemin/districlos&amp;dev=/chemin/dev.cloture-discount</code></p></body></html>';
        return;
    }

    echo '<p>Prod <strong>' . (int) $result['counts']['prod'] . '</strong> · Dev <strong>' . (int) $result['counts']['dev'] . '</strong> · ';
    echo 'En prod seulement <strong class="warn">' . (int) $result['counts']['only_prod'] . '</strong> · ';
    echo 'En commun ' . (int) $result['counts']['both'] . ' · Versions différentes <strong class="warn">' . (int) $result['counts']['version_diff'] . '</strong> · ';
    echo 'En dev seulement ' . (int) $result['counts']['only_dev'] . '</p>';

    echo '<h2>En PROD, absents du DEV</h2>';
    renderHtmlMetaTable($result['only_prod']);

    echo '<h2>Présents des deux côtés</h2>';
    renderHtmlBothTable($result['both']);

    echo '<h2>En DEV, absents de la PROD</h2>';
    renderHtmlMetaTable($result['only_dev']);

    echo '</body></html>';
}

function renderHtmlMetaTable(array $modules)
{
    if (!$modules) {
        echo '<p class="muted">(aucun)</p>';
        return;
    }

    echo '<table><thead><tr><th>Module</th><th>Version</th><th>Nom</th><th>Auteur</th></tr></thead><tbody>';
    foreach ($modules as $name => $meta) {
        echo '<tr>';
        echo '<td><code>' . htmlspecialchars($name) . '</code></td>';
        echo '<td>' . htmlspecialchars($meta['version'] !== '' ? $meta['version'] : '?') . '</td>';
        echo '<td>' . htmlspecialchars($meta['displayName']) . '</td>';
        echo '<td>' . htmlspecialchars($meta['author']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function renderHtmlBothTable(array $rows)
{
    if (!$rows) {
        echo '<p class="muted">(aucun)</p>';
        return;
    }

    echo '<table><thead><tr><th>Module</th><th>Version prod</th><th>Version dev</th><th>Nom</th></tr></thead><tbody>';
    foreach ($rows as $name => $row) {
        echo '<tr>';
        echo '<td><code>' . htmlspecialchars($name) . '</code></td>';
        echo '<td>' . htmlspecialchars($row['prod_version'] !== '' ? $row['prod_version'] : '?') . '</td>';
        echo '<td class="' . ($row['version_diff'] ? 'warn' : 'ok') . '">' . htmlspecialchars($row['dev_version'] !== '' ? $row['dev_version'] : '?') . '</td>';
        echo '<td>' . htmlspecialchars($row['displayName']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
