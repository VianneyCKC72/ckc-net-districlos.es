<?php
/**
 * Proxy API YourGPT (createSession / sendMessage) pour le panneau Closy.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DtcClosyPanelService
{
    const API_BASE = 'https://api.yourgpt.ai/chatbot/v1/';
    private const BOT_MESSAGE_POLL_ATTEMPTS = 12;
    private const BOT_MESSAGE_POLL_DELAY_US = 1000000;

    public static function isConfigured(): bool
    {
        if ((int) DtcClosyConfig::get('PANEL_ACTIVE', 0) !== 1) {
            return false;
        }
        if (trim((string) DtcClosyConfig::get('YOURGPT_API_TOKEN', '')) === '') {
            return false;
        }

        return (new self())->resolveWidgetUid() !== '';
    }

    public function resolveWidgetUid(): string
    {
        $uid = trim((string) DtcClosyConfig::get('YOURGPT_WIDGET_UID', ''));
        if ($uid !== '') {
            return $uid;
        }

        return trim((string) Configuration::get('CKC_YOURGPT_ACCOUNT_ID'));
    }

    /**
     * @return array{id_product:int,name:string,url:string,category:string,reference:string}
     */
    public function getProductContext(int $idProduct, int $idLang): array
    {
        $product = new Product($idProduct, false, $idLang);
        if (!Validate::isLoadedObject($product)) {
            throw new PrestaShopException('Produit introuvable.');
        }

        $categoryName = '';
        if ($product->id_category_default) {
            $cat = new Category((int) $product->id_category_default, $idLang);
            if (Validate::isLoadedObject($cat)) {
                $categoryName = (string) $cat->name;
            }
        }

        $link = Context::getContext()->link;

        return [
            'id_product' => $idProduct,
            'name' => (string) $product->name,
            'url' => $link->getProductLink($product),
            'category' => $categoryName,
            'reference' => (string) $product->reference,
        ];
    }

    public function wrapBubbleMessage(string $question, array $productContext): string
    {
        $url = $productContext['url'] ?? '';

        return '(produit concerné : "' . $url . '")' . "\n\n" . $question;
    }

    public function wrapCtaMessage(array $productContext): string
    {
        return 'J\'ai une question sur le produit : ' . ($productContext['url'] ?? '');
    }

    /**
     * @return array{session_uid:int|string}
     */
    public function createSession(?string $visitorUid = null): array
    {
        $widgetUid = $this->resolveWidgetUid();
        if ($widgetUid === '') {
            throw new PrestaShopException('Widget UID YourGPT manquant.');
        }

        $postFields = ['widget_uid' => $widgetUid];
        if ($visitorUid !== null && $visitorUid !== '') {
            $postFields['visitor_uid'] = $visitorUid;
        }

        $response = $this->request('createSession', $postFields);

        if (empty($response['data']['session_uid'])) {
            throw new PrestaShopException('Réponse createSession invalide.');
        }

        return [
            'session_uid' => $response['data']['session_uid'],
        ];
    }

    /**
     * Enrichit le message avec le contexte produit uniquement pour le premier
     * message de la session (économie de tokens : YourGPT garde l’historique).
     */
    public function prepareOutgoingMessage(
        string $message,
        int $idProduct,
        int $idLang,
        bool $isFirstMessage = false,
        string $mode = ''
    ): string {
        $message = trim($message);
        if ($message === '' || $idProduct <= 0 || !$isFirstMessage) {
            return $message;
        }

        if (stripos($message, '(produit concerné') !== false
            || stripos($message, 'J\'ai une question sur le produit') !== false) {
            return $message;
        }

        try {
            $context = $this->getProductContext($idProduct, $idLang);
        } catch (PrestaShopException $e) {
            return $message;
        }

        if ($mode === 'cta') {
            return $this->wrapCtaMessage($context);
        }

        return $this->wrapBubbleMessage($message, $context);
    }

    /**
     * Historique d'une session (YourGPT getSessionMessages).
     *
     * @return list<array{id:int|string|null,role:string,text:string,send_by:string}>
     */
    public function getSessionMessages($sessionUid, int $limit = 100, int $page = 1): array
    {
        $widgetUid = $this->resolveWidgetUid();
        if ($widgetUid === '') {
            throw new PrestaShopException('Widget UID YourGPT manquant.');
        }
        if ($sessionUid === '' || $sessionUid === null) {
            throw new PrestaShopException('session_uid manquant.');
        }

        $projectUid = trim((string) DtcClosyConfig::get('YOURGPT_PROJECT_UID', ''));
        if ($projectUid === '') {
            $projectUid = $widgetUid;
        }

        $postFields = [
            'widget_uid' => $widgetUid,
            'project_uid' => $projectUid,
            'session_uid' => $sessionUid,
            'limit' => max(1, min(100, $limit)),
            'page' => max(1, $page),
            'orderBy' => 'ASC',
        ];

        $response = $this->request('getSessionMessages', $postFields);
        $rows = $response['data'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $messages = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $text = trim((string) ($row['message'] ?? ''));
            if ($text === '') {
                continue;
            }
            $sendBy = strtolower(trim((string) ($row['send_by'] ?? '')));
            $role = $this->mapHistoryRole($sendBy);
            if ($role === null) {
                continue;
            }
            $messages[] = [
                'id' => $row['id'] ?? null,
                'role' => $role,
                'text' => $text,
                'send_by' => $sendBy,
            ];
        }

        return $messages;
    }

    private function mapHistoryRole(string $sendBy): ?string
    {
        if (in_array($sendBy, ['user', 'visitor', 'human', 'customer'], true)) {
            return 'user';
        }
        if (in_array($sendBy, ['bot', 'assistant', 'ai', 'agent', 'chatbot'], true)) {
            return 'bot';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractBotMessage(array $row): ?array
    {
        $text = trim((string) ($row['message'] ?? ''));
        if ($text === '') {
            return null;
        }

        if ($this->isInternalMessageText($text)) {
            return null;
        }

        $sendBy = strtolower(trim((string) ($row['send_by'] ?? '')));
        if ($this->mapHistoryRole($sendBy) !== 'bot') {
            return null;
        }

        return [
            'message' => $text,
            'message_id' => $row['message_id'] ?? $row['id'] ?? null,
        ];
    }

    private function isInternalMessageText(string $text): bool
    {
        $normalized = strtolower(trim($text));
        if ($normalized === '') {
            return false;
        }

        $internalPrefixes = [
            'intent detected',
            'session resolved',
            'nous vous remercions pour vos informations. veuillez patienter',
            'Nous comprenons que vous souhaitez parler avec notre équipe de support client. Nous transférons votre demande maintenant.'
        ];

        foreach ($internalPrefixes as $prefix) {
            if ($normalized === $prefix) {
                return true;
            }
            if (strpos($normalized, $prefix) === 0) {
                $next = substr($normalized, strlen($prefix), 1);
                if ($next === '' || in_array($next, [' ', ':', '-', '|'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getLatestBotMessage($sessionUid, int $minCreatedAt): ?array
    {
        $widgetUid = $this->resolveWidgetUid();
        $projectUid = trim((string) DtcClosyConfig::get('YOURGPT_PROJECT_UID', ''));
        if ($projectUid === '') {
            $projectUid = $widgetUid;
        }

        $response = $this->request('getSessionMessages', [
            'widget_uid' => $widgetUid,
            'project_uid' => $projectUid,
            'session_uid' => $sessionUid,
            'limit' => 10,
            'page' => 1,
            'orderBy' => 'DESC',
        ]);

        $rows = $response['data'] ?? [];
        if (!is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $createdAt = strtotime((string) ($row['createdAt'] ?? ''));
            if ($createdAt === false || $createdAt < $minCreatedAt) {
                continue;
            }
            $botMessage = $this->extractBotMessage($row);
            if ($botMessage !== null) {
                return $botMessage;
            }
        }

        return null;
    }

    private function waitForLatestBotMessage($sessionUid, int $minCreatedAt): ?array
    {
        for ($attempt = 0; $attempt < self::BOT_MESSAGE_POLL_ATTEMPTS; $attempt++) {
            if ($attempt > 0) {
                usleep(self::BOT_MESSAGE_POLL_DELAY_US);
            }

            $botMessage = $this->getLatestBotMessage($sessionUid, $minCreatedAt);
            if ($botMessage !== null) {
                return $botMessage;
            }
        }

        return null;
    }

    /**
     * @return array{message:string,message_id:int|string|null,raw:array}
     */
    public function sendMessage(
        $sessionUid,
        string $message,
        int $idProduct = 0,
        int $idLang = 0,
        bool $isFirstMessage = false,
        string $mode = ''
    ): array {
        $widgetUid = $this->resolveWidgetUid();
        if ($widgetUid === '') {
            throw new PrestaShopException('Widget UID YourGPT manquant.');
        }

        if ($idLang <= 0) {
            $idLang = (int) Context::getContext()->language->id;
        }

        $message = $this->prepareOutgoingMessage($message, $idProduct, $idLang, $isFirstMessage, $mode);
        $sentAt = time() - 5;

        $response = $this->request('sendMessage', [
            'widget_uid' => $widgetUid,
            'session_uid' => $sessionUid,
            'message' => $message,
        ]);

        $data = $response['data'] ?? [];
        $botMessage = is_array($data) ? $this->extractBotMessage($data) : null;
        if ($botMessage === null) {
            $botMessage = $this->waitForLatestBotMessage($sessionUid, $sentAt);
        }

        if ($botMessage === null) {
            throw new PrestaShopException('Réponse YourGPT interne ignorée.');
        }

        return [
            'message' => $botMessage['message'],
            'message_id' => $botMessage['message_id'],
            'raw' => $response,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $postFields): array
    {
        $token = trim((string) DtcClosyConfig::get('YOURGPT_API_TOKEN', ''));
        if ($token === '') {
            throw new PrestaShopException('Token API YourGPT non configuré.');
        }

        $url = self::API_BASE . $endpoint;
        $body = http_build_query($postFields);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $curlOpts = [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => $this->buildApiHeaders($token),
            ];
            $this->applyCurlSslOptions($curlOpts);
            curl_setopt_array($ch, $curlOpts);
            $raw = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($raw === false) {
                throw new PrestaShopException('YourGPT API : ' . $curlError);
            }
        } else {
            $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Authorization: Bearer {$token}\r\n"
                        . "Content-Type: application/x-www-form-urlencoded\r\n"
                        . "Accept: application/json\r\n",
                    'content' => $body,
                    'timeout' => 30,
                    'ignore_errors' => true,
                ],
            ];
            $raw = @file_get_contents($url, false, stream_context_create($opts));
            $httpCode = 200;
            if ($raw === false) {
                throw new PrestaShopException('YourGPT API indisponible.');
            }
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw new PrestaShopException('Réponse JSON invalide (HTTP ' . $httpCode . ').');
        }

        if ($httpCode >= 400 || (isset($decoded['type']) && $decoded['type'] !== 'RXSUCCESS')) {
            $err = $decoded['message'] ?? $decoded['error'] ?? 'Erreur YourGPT';
            if (is_array($err)) {
                $err = json_encode($err, JSON_UNESCAPED_UNICODE);
            }
            throw new PrestaShopException((string) $err);
        }

        return $decoded;
    }

    /**
     * @return list<string>
     */
    private function buildApiHeaders(string $token): array
    {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ];

        if (stripos($token, 'apk-') === 0) {
            $headers[] = 'api-key: ' . $token;
        } else {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * @param array<int, mixed> $curlOpts
     */
    private function applyCurlSslOptions(array &$curlOpts): void
    {
        $caFile = $this->resolveCaBundlePath();
        if ($caFile !== '') {
            $curlOpts[CURLOPT_CAINFO] = $caFile;
            $curlOpts[CURLOPT_SSL_VERIFYPEER] = true;
            $curlOpts[CURLOPT_SSL_VERIFYHOST] = 2;

            return;
        }

        if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
            $curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
            $curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
        }
    }

    private function resolveCaBundlePath(): string
    {
        $candidates = [];
        $iniCa = ini_get('curl.cainfo');
        if (is_string($iniCa) && $iniCa !== '') {
            $candidates[] = $iniCa;
        }
        $iniOpenSsl = ini_get('openssl.cafile');
        if (is_string($iniOpenSsl) && $iniOpenSsl !== '') {
            $candidates[] = $iniOpenSsl;
        }
        if (defined('_PS_ROOT_DIR_')) {
            $candidates[] = _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                . '..' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem';
        }
        $candidates[] = 'C:\\laragon\\etc\\ssl\\cacert.pem';

        foreach ($candidates as $path) {
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $path);
            if ($path !== '' && is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return '';
    }
}
