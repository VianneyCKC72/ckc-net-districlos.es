<?php
require_once dirname(__DIR__, 2) . '/config/config.inc.php';

use PrestaShop\PrestaShop\Adapter\Configuration;

$token = $_GET['token'] ?? '';
$expectedToken = 'djit6a0f1c4b8d3e2c5f7b9d8e4a5b3';

if ($token !== $expectedToken) {
    http_response_code(403);
    exit('Unauthorized');
}

$mode = (int) ($_GET['maintenance'] ?? 1);

Configuration::getInstance()->set('PS_SHOP_ENABLE', $mode ? 0 : 1);

echo 'OK';
