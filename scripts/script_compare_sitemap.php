<?php

function extractUrlsFromSitemap($file) {
    $xml = simplexml_load_file($file);
    $urls = [];
    foreach ($xml->url as $url) {
        $urls[] = (string)$url->loc;
    }
    return $urls;
}

$urls1 = extractUrlsFromSitemap('prod.xml');
$urls2 = extractUrlsFromSitemap('refonte.xml');

$onlyIn1 = array_diff($urls1, $urls2);
$onlyIn2 = array_diff($urls2, $urls1);

echo "👉 URLs uniquement dans la prod :\n";
print_r($onlyIn1);

echo "👉 URLs uniquement dans la refonte :\n";
print_r($onlyIn2);
