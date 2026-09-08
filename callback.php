<?php

/**
 * IonCube callback handler - prevents fatal errors when the licence is invalid.
 *
 * This file must NEVER be encoded nor carry any licence restriction: the ionCube
 * loader executes it precisely when an encoded file could not run (licence
 * missing/corrupt/expired, file expired...). An encoded callback would be blocked
 * by the very error it is supposed to handle, and the loader would fall back to
 * its default error page.
 *
 * When an event is swallowed here, the blocked file is simply not executed: the
 * plain Cache override installed at override/classes/cache/Cache.php then finds
 * no engine to drive and falls back to plain PrestaShop behaviour, so the shop
 * keeps running with the query cache disabled instead of showing an error page.
 */
/**
 * In a degraded state the loader fires one event per encoded file per request,
 * indefinitely: throttle logging to one line per hour so the PHP error log is
 * not flooded. Dependency-free on purpose (marker file in the temp dir).
 */
function softizy_query_cache_throttled_log($message)
{
    // Namespaced by install path: two shops sharing a server must not silence
    // each other's diagnostics.
    $throttleFile = sys_get_temp_dir() . '/softizyquerycache_ioncube_event_' . md5(__DIR__) . '.marker';

    if (is_file($throttleFile) && time() - (int) @filemtime($throttleFile) < 3600) {
        return;
    }

    // A marker we cannot write (squatted path) degrades to the unthrottled
    // behaviour, never to a silenced one.
    @touch($throttleFile);
    error_log($message);
}

function ioncube_event_handler($err_code, $params)
{
    $currentFile = isset($params['current_file']) ? (string) $params['current_file'] : '';

    switch ($err_code) {
        case ION_LICENSE_NOT_FOUND:
        case ION_LICENSE_CORRUPT:
        case ION_LICENSE_EXPIRED:
        case ION_LICENSE_HEADER_INVALID:
        case ION_LICENSE_SERVER_INVALID:
            // Silently handle licence issues - the query cache stays disabled.
            softizy_query_cache_throttled_log('[softizyquerycache] ionCube licence issue (code ' . $err_code . ') on ' . $currentFile . ': query cache disabled.');
            break;
        default:
            if (strpos($currentFile, 'softizyquerycache') !== false) {
                // Any other loader event on one of our files (corrupt/expired file,
                // clock skew...) must not take the shop down either: the plain Cache
                // override provides the fallback. Log it for diagnosis.
                softizy_query_cache_throttled_log('[softizyquerycache] ionCube loader event (code ' . $err_code . ') on ' . $currentFile . ': query cache disabled. Please contact support@softizy.com.');
                break;
            }

            // Event raised by a file that does not belong to this module: keep the
            // historical behaviour, a silent skip could break third-party code in
            // harder-to-diagnose ways.
            die('The encoded file ' . $currentFile . " is invalid. \n Please contact support@softizy.com for assistance.");
    }
}
