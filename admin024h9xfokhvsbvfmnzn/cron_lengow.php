<?php

$csv = file_get_contents('https://nouveau.cloture-discount.fr/modules/lengow/webservice/export.php?token=51707582591180585184aa20d72bd399');

var_dump($csv);
die;

file_put_contents('../modules/lengow/webservice/export_20d72bd399.csv', $csv);
