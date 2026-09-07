<?php
// One-time OPcache reset — delete this file after use
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache reset OK.';
} else {
    echo 'OPcache not active or opcache_reset not available.';
}
