<?php
/**
 * CKC Vianney — debug PHP uniquement si ?ckc=1 (ou &ckc=1).
 * Inclus depuis config/defines.inc.php avant la définition de _PS_MODE_DEV_.
 *
 * Ex. : /1234-produit.html?ckc=1
 */
if (defined('_PS_MODE_DEV_')) {
    return;
}

$ckcDebug = isset($_GET['ckc']) && (string) $_GET['ckc'] === '1';

if ($ckcDebug) {
    define('_PS_MODE_DEV_', true);
    @ini_set('display_errors', 'on');
    @ini_set('display_startup_errors', '1');
    @error_reporting(E_ALL);
}
