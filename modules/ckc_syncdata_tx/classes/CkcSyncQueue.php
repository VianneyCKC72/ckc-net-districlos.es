<?php

class CkcSyncQueue extends ObjectModel
{
    public $id;
    public $type;
    public $entity_id;
    public $action;
    public $payload;
    public $date_add;

    public static $definition = [
        'table' => 'ckc_sync_queue',
        'primary' => 'id_ckc_sync_queue',
        'fields' => [
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'entity_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'action' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'payload' => ['type' => self::TYPE_HTML, 'validate' => 'isJson', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    public static function processQueue()
    {
        $url = rtrim(Configuration::get('CKC_SYNCDATA_TX_TARGET_URL'), '/');
        $token = Configuration::get('CKC_SYNCDATA_TX_API_TOKEN');

        if (!$url || !$token) {
            PrestaShopLogger::addLog('[CKC Sync] Configuration URL or token missing.');
            return;
        }

        $items = Db::getInstance()->executeS('
        SELECT * FROM ' . _DB_PREFIX_ . 'ckc_sync_queue
        ORDER BY date_add ASC
        LIMIT 10
    ');

        foreach ($items as $item) {
            $id = (int)$item['id_ckc_sync_queue'];
            $payload = $item['payload'];

            // Skip si le payload est vide
            if (empty($payload)) {
                Db::getInstance()->delete('ckc_sync_queue', 'id_ckc_sync_queue = ' . $id);
                continue;
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response && $httpCode >= 200 && $httpCode < 300) {
                Db::getInstance()->delete('ckc_sync_queue', 'id_ckc_sync_queue = ' . $id);
            } else {
                PrestaShopLogger::addLog('[CKC Sync] Error for queue ID ' . $id . ': ' . ($error ?: $response));
            }
        }
    }

}
