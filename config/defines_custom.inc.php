<?php
/**
 * CKC Vianney — debug PHP uniquement si ?ckc=1 et langue EN.
 * Inclus depuis config/defines.inc.php avant la définition de _PS_MODE_DEV_.
 *
 * URL : /en/?ckc=1  (le sélecteur de langue preview ajoute déjà ckc=1)
 */
if (defined('_PS_MODE_DEV_')) {
    return;
}

$ckcDebug = isset($_GET['ckc']) && (string) $_GET['ckc'] === '1';
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$path = is_string($path) ? $path : '';
$iso = strtolower((string) ($_GET['isolang'] ?? ''));
$isEn = $iso === 'en' || (bool) preg_match('#(?:^|/)en(?:/|$)#i', $path);

if ($ckcDebug && $isEn) {
    define('_PS_MODE_DEV_', false);
}
