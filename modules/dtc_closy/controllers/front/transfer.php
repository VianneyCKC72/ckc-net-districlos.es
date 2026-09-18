<?php
/**
 * Endpoint JSON appelé par YourGPT pour transférer une demande Closy au support.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'dtc_closy/classes/DtcClosyConfig.php';
require_once _PS_MODULE_DIR_ . 'dtc_closy/classes/DtcClosyChatSecurity.php';

class Dtc_closyTransferModuleFrontController extends ModuleFrontController
{
    private const DEFAULT_RECIPIENT = 'commercial@districlos.com';
    private const MAX_FIELD_LENGTH = 200;
    private const MAX_HISTORY_LENGTH = 12000;

    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Méthode non autorisée.', 405);
        }

        $submittedKey = Tools::getValue('key');
        if ($submittedKey === false || $submittedKey === null || $submittedKey === '') {
            $submittedKey = Tools::getValue('token');
        }
        if (!DtcClosyChatSecurity::validateToken((string) $submittedKey)) {
            $this->jsonError('Accès refusé.', 403);
        }

        try {
            $payload = $this->readPayload();
            $contact = $this->buildContactSummary($payload);
            if ($contact === '') {
                $this->jsonError('Contact manquant.', 400);
            }

            $subject = $this->sanitizeField(
                (string) ($payload['subject'] ?? 'Nouvelle demande service client Closy'),
                160
            );
            if ($subject === '') {
                $subject = 'Nouvelle demande service client Closy';
            }

            $message = $this->buildEmailMessage($payload, $contact);
            $recipient = $this->resolveRecipient();
            $sent = Mail::Send(
                (int) Configuration::get('PS_LANG_DEFAULT'),
                'closy_transfer',
                $subject,
                [
                    '{subject}' => $subject,
                    '{contact}' => nl2br(Tools::safeOutput($contact)),
                    '{message}' => nl2br(Tools::safeOutput($message)),
                    '{date}' => date('Y-m-d H:i:s'),
                    '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                ],
                $recipient,
                null,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_ . 'dtc_closy/mails'
            );

            if (!$sent) {
                $this->jsonError('Email non envoyé.', 500);
            }

            $this->jsonOk(['sent' => true]);
        } catch (Exception $e) {
            $this->jsonError('Erreur serveur.', 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(): array
    {
        $raw = (string) file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $data = $decoded['data'] ?? $decoded;

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildContactSummary(array $payload): string
    {
        $lines = [];
        $email = $this->sanitizeField((string) ($payload['userEmail'] ?? ''));
        $phone = $this->sanitizeField((string) ($payload['userPhone'] ?? ''));

        if ($email !== '' && Validate::isEmail($email)) {
            $lines[] = 'Email : ' . $email;
        }
        if ($phone !== '') {
            $lines[] = 'Téléphone : ' . $phone;
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildEmailMessage(array $payload, string $contact): string
    {
        $parts = [
            'Demande transférée depuis Closy.',
            '',
            'Contact client :',
            $contact,
            '',
            'Historique utilisateur :',
            $this->formatHistory($payload['userHistory'] ?? ''),
            '',
            'Historique bot :',
            $this->formatHistory($payload['botHistory'] ?? ''),
        ];

        return Tools::substr(implode("\n", $parts), 0, self::MAX_HISTORY_LENGTH);
    }

    /**
     * @param mixed $history
     */
    private function formatHistory($history): string
    {
        if (is_array($history)) {
            return $this->formatHistoryRows($history);
        }

        $history = (string) $history;
        $decoded = json_decode($history, true);
        if (is_array($decoded)) {
            return $this->formatHistoryRows($decoded);
        }

        $history = trim(strip_tags($history));

        return $history !== '' ? Tools::substr($history, 0, self::MAX_HISTORY_LENGTH) : 'Non fourni.';
    }

    /**
     * @param array<int|string, mixed> $rows
     */
    private function formatHistoryRows(array $rows): string
    {
        $lines = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $timestamp = isset($row['timestamp']) ? '[' . $this->sanitizeField((string) $row['timestamp'], 40) . '] ' : '';
            $text = (string) ($row['message'] ?? $row['response'] ?? '');
            $text = $this->sanitizeField($text, 1000);
            if ($text !== '') {
                $lines[] = $timestamp . $text;
            }
        }

        return count($lines) > 0 ? implode("\n", $lines) : 'Non fourni.';
    }

    private function sanitizeField(string $value, int $maxLength = self::MAX_FIELD_LENGTH): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/', ' ', $value);

        return Tools::substr((string) $value, 0, $maxLength);
    }

    private function resolveRecipient(): string
    {
        $shopEmail = trim((string) Configuration::get('PS_SHOP_EMAIL'));

        return Validate::isEmail(self::DEFAULT_RECIPIENT) ? self::DEFAULT_RECIPIENT : $shopEmail;
    }

    private function jsonOk(array $data): void
    {
        echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
