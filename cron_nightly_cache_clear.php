<?php
/**
 * Cron nightly - vide le cache APC/APCu et les fichiers ckcspcache > 1 jour
 * Usage : php cron_nightly_cache_clear.php
 * Cron  : 0 3 * * * php /var/www/html/cron_nightly_cache_clear.php >> /var/log/cron_cache.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    exit('CLI uniquement' . PHP_EOL);
}

$log = static function (string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
};

// --- 1. Cache APC/APCu (driver PrestaShop actif en prod) ---
$log('Vidage cache APC/APCu...');
if (extension_loaded('apcu')) {
    apcu_clear_cache();
    $log('APCu vidé.');
} elseif (extension_loaded('apc')) {
    apc_clear_cache();
    apc_clear_cache('user');
    $log('APC vidé.');
} else {
    $log('AVERTISSEMENT : ni APCu ni APC disponible, cache non vidé.');
}

// --- 2. Fichiers ckcspcache > 1 jour ---
$cacheDir = __DIR__ . '/modules/ckcspcache/cache/sp';
$log('Nettoyage ckcspcache : ' . $cacheDir);

if (!is_dir($cacheDir)) {
    $log('Répertoire introuvable, skip.');
    exit(0);
}

$cutoff  = time() - 86400;
$deleted = 0;
$errors  = 0;

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($it as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) {
        continue;
    }
    $mtime = $file->getMTime();
    if ($mtime !== false && $mtime < $cutoff) {
        if (@unlink($file->getPathname())) {
            $deleted++;
        } else {
            $errors++;
        }
    }
}

$log("ckcspcache : $deleted fichier(s) supprimé(s), $errors erreur(s).");
$log('Terminé.');
exit(0);
