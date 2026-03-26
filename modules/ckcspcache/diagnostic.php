<?php
/**
 * Diagnostic du module ckcspcache
 * Vérifie pourquoi le cache ne s'écrit pas en production
 */

define('_PS_ADMIN_DIR_', dirname(__DIR__, 2) . '/admin024h9xfokhvsbvfmnzn');
require_once dirname(__DIR__, 2) . '/config/config.inc.php';
require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: text/html; charset=UTF-8');

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Diagnostic ckcspcache</title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
h2 { color: #667eea; margin-top: 30px; }
.check { padding: 10px; margin: 10px 0; border-radius: 4px; }
.check.ok { background: #d1fae5; border-left: 4px solid #10b981; }
.check.error { background: #fee2e2; border-left: 4px solid #ef4444; }
.check.warning { background: #fef3c7; border-left: 4px solid #f59e0b; }
.code { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 4px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
th { background: #f8f9fa; font-weight: 600; }
.badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 600; }
.badge.success { background: #10b981; color: white; }
.badge.error { background: #ef4444; color: white; }
.badge.warning { background: #f59e0b; color: white; }
</style>
</head><body>
<div class="container">
<h1>🔍 Diagnostic du module ckcspcache</h1>';

$errors = [];
$warnings = [];
$infos = [];

// ============================================
// 1. VÉRIFICATION DU DOMAINE
// ============================================
echo '<h2>1. Domaine actuel</h2>';
$currentDomain = $_SERVER['HTTP_HOST'] ?? 'unknown';
$isProd = strpos($currentDomain, 'dev.') === false && strpos($currentDomain, 'localhost') === false;

echo '<div class="check ' . ($isProd ? 'warning' : 'ok') . '">';
echo '<strong>Domaine :</strong> ' . htmlspecialchars($currentDomain) . '<br>';
echo '<strong>Environnement :</strong> ' . ($isProd ? '🔴 PRODUCTION' : '🟢 DÉVELOPPEMENT/PREPROD');
echo '</div>';

// ============================================
// 2. VÉRIFICATION DU MODULE
// ============================================
echo '<h2>2. Module ckcspcache</h2>';

$moduleDir = _PS_MODULE_DIR_ . 'ckcspcache/';
$cacheDir = $moduleDir . 'cache/sp/';

echo '<table>';
echo '<tr><th>Élément</th><th>Valeur</th><th>Statut</th></tr>';

// Module installé ?
$moduleInstalled = Module::isInstalled('ckcspcache');
echo '<tr><td>Module installé</td><td>' . ($moduleInstalled ? 'Oui' : 'Non') . '</td>';
echo '<td><span class="badge ' . ($moduleInstalled ? 'success' : 'error') . '">' . ($moduleInstalled ? 'OK' : 'ERREUR') . '</span></td></tr>';

if (!$moduleInstalled) {
    $errors[] = "Le module ckcspcache n'est pas installé";
}

// Module activé ?
$moduleActive = Module::isEnabled('ckcspcache');
echo '<tr><td>Module activé</td><td>' . ($moduleActive ? 'Oui' : 'Non') . '</td>';
echo '<td><span class="badge ' . ($moduleActive ? 'success' : 'error') . '">' . ($moduleActive ? 'OK' : 'ERREUR') . '</span></td></tr>';

if (!$moduleActive) {
    $errors[] = "Le module ckcspcache n'est pas activé";
}

// Configuration
$ttl = (int)Configuration::get('CKC_SP_CACHE_TTL', 120);
$nsVersion = (int)Configuration::get('CKC_SP_CACHE_NS_VERSION', 1);

echo '<tr><td>TTL configuré</td><td>' . $ttl . ' secondes</td>';
echo '<td><span class="badge success">OK</span></td></tr>';

echo '<tr><td>Namespace version</td><td>' . $nsVersion . '</td>';
echo '<td><span class="badge success">OK</span></td></tr>';

echo '</table>';

// ============================================
// 3. VÉRIFICATION DES RÉPERTOIRES
// ============================================
echo '<h2>3. Répertoires et permissions</h2>';

echo '<table>';
echo '<tr><th>Répertoire</th><th>Existe</th><th>Permissions</th><th>Propriétaire</th><th>Statut</th></tr>';

$dirs = [
    'Module' => $moduleDir,
    'Cache' => $cacheDir,
];

foreach ($dirs as $label => $dir) {
    $exists = is_dir($dir);
    $perms = $exists ? substr(sprintf('%o', fileperms($dir)), -4) : '-';
    $owner = $exists ? (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($dir))['name'] ?? 'N/A' : 'N/A') : '-';
    $writable = $exists && is_writable($dir);

    echo '<tr>';
    echo '<td><code>' . htmlspecialchars($dir) . '</code></td>';
    echo '<td>' . ($exists ? '✓ Oui' : '✗ Non') . '</td>';
    echo '<td>' . $perms . '</td>';
    echo '<td>' . $owner . '</td>';
    echo '<td><span class="badge ' . ($writable ? 'success' : 'error') . '">' . ($writable ? 'WRITABLE' : 'NON WRITABLE') . '</span></td>';
    echo '</tr>';

    if (!$exists) {
        $errors[] = "Répertoire manquant : $dir";
    } elseif (!$writable) {
        $errors[] = "Répertoire non accessible en écriture : $dir";
    }
}

echo '</table>';

// ============================================
// 4. TEST D'ÉCRITURE
// ============================================
echo '<h2>4. Test d\'écriture dans le cache</h2>';

$testFile = $cacheDir . 'test_write_' . time() . '.php';
$testSuccess = false;
$testError = '';

try {
    // Créer le répertoire si nécessaire
    if (!is_dir($cacheDir)) {
        $mkdirResult = @mkdir($cacheDir, 0775, true);
        echo '<div class="check ' . ($mkdirResult ? 'ok' : 'error') . '">';
        echo $mkdirResult ? '✓ Répertoire créé avec succès' : '✗ Échec de création du répertoire';
        echo '</div>';
    }

    // Tester l'écriture
    $content = "<?php\nreturn ['test' => 'diagnostic_" . date('Y-m-d H:i:s') . "'];\n";
    $writeResult = @file_put_contents($testFile, $content, LOCK_EX);

    if ($writeResult !== false) {
        $testSuccess = true;
        echo '<div class="check ok">✓ Test d\'écriture réussi : ' . $writeResult . ' octets écrits</div>';

        // Tester la lecture
        if (is_file($testFile)) {
            $readData = @include $testFile;
            echo '<div class="check ok">✓ Test de lecture réussi</div>';
            echo '<div class="code">' . htmlspecialchars(var_export($readData, true)) . '</div>';

            // Supprimer le fichier de test
            @unlink($testFile);
        } else {
            $warnings[] = "Le fichier a été écrit mais n'est pas lisible";
        }
    } else {
        $testError = error_get_last()['message'] ?? 'Erreur inconnue';
        $errors[] = "Échec du test d'écriture : $testError";
        echo '<div class="check error">✗ Échec du test d\'écriture<br>' . htmlspecialchars($testError) . '</div>';
    }
} catch (Exception $e) {
    $errors[] = "Exception lors du test d'écriture : " . $e->getMessage();
    echo '<div class="check error">✗ Exception : ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// ============================================
// 5. VÉRIFICATION DES FICHIERS CACHE EXISTANTS
// ============================================
echo '<h2>5. Fichiers cache existants</h2>';

if (is_dir($cacheDir)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    $fileCount = 0;
    $totalSize = 0;
    $oldest = null;
    $newest = null;

    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $fileCount++;
            $totalSize += $file->getSize();
            $mtime = $file->getMTime();

            if ($oldest === null || $mtime < $oldest) $oldest = $mtime;
            if ($newest === null || $mtime > $newest) $newest = $mtime;
        }
    }

    echo '<table>';
    echo '<tr><th>Métrique</th><th>Valeur</th></tr>';
    echo '<tr><td>Nombre de fichiers cache</td><td><strong>' . $fileCount . '</strong></td></tr>';
    echo '<tr><td>Taille totale</td><td>' . round($totalSize / 1024, 2) . ' KB</td></tr>';

    if ($fileCount > 0) {
        echo '<tr><td>Fichier le plus ancien</td><td>' . date('Y-m-d H:i:s', $oldest) . ' (' . round((time() - $oldest) / 60) . ' min)</td></tr>';
        echo '<tr><td>Fichier le plus récent</td><td>' . date('Y-m-d H:i:s', $newest) . ' (' . round((time() - $newest)) . ' sec)</td></tr>';
    }

    echo '</table>';

    if ($fileCount === 0) {
        $warnings[] = "Aucun fichier cache trouvé dans $cacheDir";
    }
} else {
    $errors[] = "Le répertoire cache n'existe pas : $cacheDir";
}

// ============================================
// 6. VÉRIFICATION PHP
// ============================================
echo '<h2>6. Configuration PHP</h2>';

echo '<table>';
echo '<tr><th>Paramètre</th><th>Valeur</th></tr>';
echo '<tr><td>Version PHP</td><td>' . PHP_VERSION . '</td></tr>';
echo '<tr><td>User PHP</td><td>' . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] ?? get_current_user() : get_current_user()) . '</td></tr>';
echo '<tr><td>open_basedir</td><td>' . (ini_get('open_basedir') ?: 'Non défini') . '</td></tr>';
echo '<tr><td>file_uploads</td><td>' . (ini_get('file_uploads') ? 'On' : 'Off') . '</td></tr>';
echo '<tr><td>Temp directory</td><td>' . sys_get_temp_dir() . '</td></tr>';
echo '</table>';

// ============================================
// 7. RÉSUMÉ
// ============================================
echo '<h2>7. Résumé du diagnostic</h2>';

if (empty($errors) && empty($warnings)) {
    echo '<div class="check ok">
        <strong>✓ Aucun problème détecté</strong><br>
        Le module devrait fonctionner correctement.
    </div>';
} else {
    if (!empty($errors)) {
        echo '<div class="check error">';
        echo '<strong>✗ Erreurs critiques (' . count($errors) . ') :</strong><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }

    if (!empty($warnings)) {
        echo '<div class="check warning">';
        echo '<strong>⚠ Avertissements (' . count($warnings) . ') :</strong><ul>';
        foreach ($warnings as $warning) {
            echo '<li>' . htmlspecialchars($warning) . '</li>';
        }
        echo '</ul></div>';
    }
}

// ============================================
// 8. RECOMMANDATIONS
// ============================================
echo '<h2>8. Recommandations</h2>';

echo '<div class="code">';
echo "# Pour créer le répertoire cache avec les bonnes permissions :\n";
echo "mkdir -p " . htmlspecialchars($cacheDir) . "\n";
echo "chmod 775 " . htmlspecialchars($cacheDir) . "\n\n";

echo "# Si problème de propriétaire (remplacer www-data par votre user Apache/Nginx) :\n";
echo "chown -R www-data:www-data " . htmlspecialchars($moduleDir) . "\n\n";

echo "# Vérifier les permissions actuelles :\n";
echo "ls -la " . htmlspecialchars($cacheDir) . "\n";
echo '</div>';

echo '</div></body></html>';
