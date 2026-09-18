<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_Connectif_Custom_FieldsSmsEnvoyeModuleFrontController extends ModuleFrontController
{
    public $ajax = true;
    public $ssl = true;

    public function initContent()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!$this->isConnectifWebhookTokenValid()) {
                PrestaShopLogger::addLog(
                    'Dtc_Connectif_Custom_Fields: webhook SMS - token invalide',
                    3,
                    null,
                    'Dtc_Connectif_Custom_Fields',
                    0,
                    true
                );
                $this->respond(401, ['status' => 'error', 'message' => 'Token invalide']);
            }

            $email = $this->extractEmail();
            if ($email === '' || !Validate::isEmail($email)) {
                PrestaShopLogger::addLog(
                    'Dtc_Connectif_Custom_Fields: webhook SMS - email manquant ou invalide',
                    3,
                    null,
                    'Dtc_Connectif_Custom_Fields',
                    0,
                    true
                );
                $this->respond(400, ['status' => 'error', 'message' => 'Email manquant ou invalide']);
            }

            $idCustomer = (int) Db::getInstance()->getValue(
                'SELECT id_customer FROM `' . _DB_PREFIX_ . 'customer`
                WHERE email = "' . pSQL($email) . '"'
            );

            if ($idCustomer <= 0) {
                PrestaShopLogger::addLog(
                    'Dtc_Connectif_Custom_Fields: webhook SMS - client introuvable pour email ' . $email,
                    3,
                    null,
                    'Dtc_Connectif_Custom_Fields',
                    0,
                    true
                );
                $this->respond(404, ['status' => 'error', 'message' => 'Client introuvable']);
            }

            $saved = Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'dtc_connectif_devis_sms`
                (`id_customer`, `date_dernier_sms`)
                VALUES (' . (int) $idCustomer . ', NOW())
                ON DUPLICATE KEY UPDATE `date_dernier_sms` = NOW()'
            );

            if (!$saved) {
                PrestaShopLogger::addLog(
                    'Dtc_Connectif_Custom_Fields: webhook SMS - échec enregistrement pour client ' . $idCustomer,
                    3,
                    null,
                    'Dtc_Connectif_Custom_Fields',
                    $idCustomer,
                    true
                );
                $this->respond(500, ['status' => 'error', 'message' => 'Erreur interne']);
            }

            PrestaShopLogger::addLog(
                'Dtc_Connectif_Custom_Fields: webhook SMS enregistré pour client ' . $idCustomer . ' (' . $email . ')',
                1,
                null,
                'Dtc_Connectif_Custom_Fields',
                $idCustomer,
                true
            );

            $synced = false;
            if ($this->module instanceof Dtc_Connectif_Custom_Fields) {
                $synced = $this->module->syncQuotationFieldsToConnectif($email, $idCustomer);
            }

            $this->respond(200, [
                'status' => 'ok',
                'id_customer' => $idCustomer,
                'email' => $email,
                'connectif_synced' => $synced,
            ]);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog(
                'Dtc_Connectif_Custom_Fields: webhook SMS - exception: ' . $e->getMessage(),
                3,
                null,
                'Dtc_Connectif_Custom_Fields',
                0,
                true
            );
            $this->respond(500, ['status' => 'error', 'message' => 'Erreur interne']);
        }
    }

    private function isConnectifWebhookTokenValid(): bool
    {
        $receivedToken = (string) ($_SERVER['HTTP_X_CONNECTIF_TOKEN'] ?? '');
        $expectedToken = (string) Configuration::get('DTC_CONNECTIF_WEBHOOK_TOKEN');

        if ($receivedToken === '' || $expectedToken === '') {
            return false;
        }

        return hash_equals($expectedToken, $receivedToken);
    }

    /**
     * Accepte email en query string, POST classique ou JSON (clés email ou champ-1).
     */
    private function extractEmail(): string
    {
        $email = trim((string) Tools::getValue('email'));
        if ($email === '') {
            $email = trim((string) Tools::getValue('champ-1'));
        }

        if ($email === '') {
            $rawBody = file_get_contents('php://input');
            if (is_string($rawBody) && $rawBody !== '') {
                $payload = json_decode($rawBody, true);
                if (is_array($payload)) {
                    $email = trim((string) ($payload['email'] ?? $payload['champ-1'] ?? ''));
                }
            }
        }

        return $email;
    }

    private function respond(int $httpCode, array $payload): void
    {
        http_response_code($httpCode);
        die(json_encode($payload));
    }
}

class_alias(
    Dtc_Connectif_Custom_FieldsSmsEnvoyeModuleFrontController::class,
    'dtc_connectif_custom_fieldsSmsEnvoyeModuleFrontController'
);
