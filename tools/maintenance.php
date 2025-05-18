<?php
require_once dirname(__DIR__) . '/config/config.inc.php';

$token = Tools::getValue('token');
if ($token !== 'TON_TOKEN_SECRET') {
    http_response_code(403);
    exit('Unauthorized');
}

$mode = (int) Tools::getValue('maintenance', 1);
Configuration::updateValue('PS_SHOP_ENABLE', $mode ? 0 : 1);
echo 'OK';
