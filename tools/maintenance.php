<?php
require '../config/config.inc.php';
//require_once dirname(__FILE__) . '/init.php';

$token = $_GET['token'] ?? '';
$expectedToken = 'djit6a0f1c4b8d3e2c5f7b9d8e4a5b3';

if ($token !== $expectedToken) {
    http_response_code(403);
    exit('Unauthorized');
}

$mode = (int) ($_GET['maintenance'] ?? 1);

Configuration::updateValue('PS_SHOP_ENABLE', $mode ? 1 : 0);

echo 'OK';
