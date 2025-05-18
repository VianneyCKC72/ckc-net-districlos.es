<?php
require_once dirname(__DIR__) . '/config/config.inc.php';

$token = $_GET['token'] ?? null;
if ($token !== 'djit6a0f1c4b8d3e2c5f7b9d8e4a5b3') {
    http_response_code(403);
    exit('Unauthorized');
}

$mode = (int) Tools::getValue('maintenance', 1);
Configuration::updateValue('PS_SHOP_ENABLE', $mode ? 0 : 1);
echo 'OK';
