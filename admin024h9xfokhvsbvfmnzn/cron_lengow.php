<?php

$csv = file_get_contents('https://nouveau.cloture-discount.fr/modules/lengow/webservice/export.php?token=51707582591180585184aa20d72bd399');

if ($csv === false) {
    die('Error fetching CSV data');
}

file_put_contents('../modules/lengow/webservice/export.csv', $csv);
