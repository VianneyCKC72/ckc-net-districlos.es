<?php

class DealtTvaAttestationModuleFrontController extends ModuleFrontController
{
    public $ssl  = true;
    public $ajax = true;

    public function initContent()
    {
        // Forcer JSON + pas de cache
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->jsonError('Méthode invalide', 405);
            }

            if (!Tools::isSubmit('submit_tva_attestation')) {
                return $this->jsonError('Requête invalide', 400);
            }

            $this->processForm();
        } catch (\Throwable $e) {
            // Log détaillé
            $this->logError($e);
            // Retour JSON simplifié pour l'AJAX
            return $this->jsonError('Exception serveur: '.$e->getMessage(), 500);
        }
    }

    protected function processForm()
    {
        if (!$this->tvaTableExists()) {
            return $this->jsonError('Table dealt_tva_attestation absente. Exécutez l’installation DB.', 500);
        }

        $cart = $this->context->cart;
        if (!$cart || !$cart->id) {
            return $this->jsonError('Panier introuvable', 404);
        }

        if (Tools::getValue('eligible') != '1') {
            return $this->jsonError('Éligibilité non confirmée', 400);
        }

        $token = (string)Tools::getValue('token');
        $id_main_product = (int)Tools::getValue('id_main_product');

        $id_customer = ($this->context->customer && $this->context->customer->isLogged())
            ? (int)$this->context->customer->id
            : null;

        $id_cart = (int)$cart->id;
        $ip      = pSQL(Tools::getRemoteAddr());
        $ua      = pSQL($_SERVER['HTTP_USER_AGENT'] ?? '');
        $now     = date('Y-m-d H:i:s');

        // Supprime les anciennes lignes pour ce panier
        if (!Db::getInstance()->delete('dealt_tva_attestation', 'id_cart = '.(int)$id_cart)) {
            return $this->jsonError('Erreur SQL lors de la suppression: '.Db::getInstance()->getMsgError(), 500);
        }

        $data = [
            'id_customer'  => $id_customer,
            'id_main_product' => $id_main_product,
            'id_cart'      => $id_cart,
            'is_validated' => 1,
            'validated_at' => $now,
            'ip_address'   => $ip,
            'user_agent'   => $ua,
            'token'        => pSQL($token),
            'date_add'     => $now,
            'date_upd'     => $now,
        ];

        if (!Db::getInstance()->insert('dealt_tva_attestation', $data)) {
            return $this->jsonError('Erreur SQL lors de l’insertion: '.Db::getInstance()->getMsgError(), 500);
        }

        return $this->jsonSuccess(['success' => true]);
    }

    private function tvaTableExists()
    {
        $table = pSQL(_DB_PREFIX_.'dealt_tva_attestation');

        $sql = "
        SELECT COUNT(*) 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = '".$table."'
    ";

        return (bool) Db::getInstance()->getValue($sql);
    }


    private function jsonError($message, $code = 500)
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error'   => $message,
            'code'    => $code
        ]);
        exit;
    }

    private function jsonSuccess(array $data)
    {
        http_response_code(200);
        echo json_encode($data);
        exit;
    }

    private function logError(\Throwable $e)
    {
        $logMsg = sprintf("[%s] %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        // Chemin du fichier log (à adapter si besoin)
        $logFile = _PS_ROOT_DIR_.'/var/logs/dealt_tva.log';
        file_put_contents($logFile, $logMsg, FILE_APPEND);
    }
}
