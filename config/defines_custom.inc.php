<?php
/**
 * CKC Vianney — debug PHP si ?ckc=1 (avant le #) ou cookie ckc_debug.
 *
 * Bon :  /produit.html?ckc=1
 * Bon :  /produit.html?ckc=1#/123-attr
 * Mauvais : /produit.html#/123-attr?ckc=1  ← le serveur ne reçoit rien
 */
if (!function_exists('ckc_request_wants_debug')) {
    function ckc_request_wants_debug()
    {
        if (isset($_GET['ckc']) && (string) $_GET['ckc'] === '1') {
            return true;
        }
        if (!empty($_SERVER['QUERY_STRING']) && preg_match('/(?:^|&)ckc=1(?:&|$)/', $_SERVER['QUERY_STRING'])) {
            return true;
        }
        if (!empty($_COOKIE['ckc_debug']) && (string) $_COOKIE['ckc_debug'] === '1') {
            return true;
        }

        return false;
    }
}

$ckcDebug = ckc_request_wants_debug();

if ($ckcDebug && !defined('CKC_DEBUG')) {
    define('CKC_DEBUG', true);
}

if ($ckcDebug && isset($_GET['ckc']) && (string) $_GET['ckc'] === '1') {
    @setcookie('ckc_debug', '1', time() + 3600, '/');
}

if ($ckcDebug && !defined('_PS_MODE_DEV_')) {
    define('_PS_MODE_DEV_', true);
    @ini_set('display_errors', 'on');
    @ini_set('display_startup_errors', '1');
    @error_reporting(E_ALL);

    register_shutdown_function(static function () {
        $e = error_get_last();
        if (!$e || !in_array((int) $e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
            return;
        }
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo "\n\nCKC DEBUG FATAL\n";
        echo $e['message'] . "\n";
        echo $e['file'] . ':' . $e['line'] . "\n";
    });
}
