<?php
/**
 * Cron nightly - vide le cache PrestaShop et les fichiers ckcspcache > 1 jour
 * Usage : php cron_nightly_cache_clear.php
 * Cron  : 0 3 * * * php /var/www/html/cron_nightly_cache_clear.php >> /var/log/cron_cache.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    exit('CLI uniquement' . PHP_EOL);
}

define('_PS_ADMIN_DIR_', __DIR__ . '/admin');

include __DIR__ . '/config/config.inc.php';

$log = static function (string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
};

// --- 1. Cache PrestaShop principal ---
$log('Vidage Cache::getInstance()->resetCache()...');
try {
    Cache::getInstance()->resetCache();
    $log('Cache principal vidé.');
} catch (Throwable $e) {
    $log('ERREUR cache principal : ' . $e->getMessage());
}

// --- 2. Fichiers ckcspcache > 1 jour ---
$cacheDir = _PS_MODULE_DIR_ . 'ckcspcache/cache/sp';
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
