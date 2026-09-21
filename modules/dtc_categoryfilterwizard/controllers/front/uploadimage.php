<?php
/**
 * Category Filter Wizard — Upload image (AJAX depuis le back-office)
 *
 * Retour JSON :
 *   { "ok": true, "url": "/modules/dtc_categoryfilterwizard/views/img/uploaded/xxxx.png" }
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class dtc_categoryfilterwizardUploadimageModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json; charset=utf-8');

        if (empty($_FILES) || empty($_FILES['image'])) {
            die(json_encode(['ok' => false, 'error' => 'No file']));
        }

        // Sécurité : token AdminModules (avec fallback contexte back-office absent)
        $token    = (string) Tools::getValue('dtc_token', '');
        $employee = Context::getContext()->employee ?? null;
        $referer  = (string) ($_SERVER['HTTP_REFERER'] ?? '');

        $refererOk = (strpos($referer, 'AdminModules') !== false)
            || (strpos($referer, 'configure=dtc_categoryfilterwizard') !== false);

        if ($employee && !empty($employee->id)) {
            $expected = Tools::getAdminTokenLite('AdminModules');
            if (empty($token) || !hash_equals($expected, $token)) {
                die(json_encode(['ok' => false, 'error' => 'Forbidden']));
            }
        } elseif (!$refererOk) {
            die(json_encode(['ok' => false, 'error' => 'Forbidden']));
        }

        if (!is_uploaded_file($_FILES['image']['tmp_name'])) {
            die(json_encode(['ok' => false, 'error' => 'Upload failed']));
        }

        $file = $_FILES['image'];
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            die(json_encode(['ok' => false, 'error' => 'Invalid file size']));
        }

        $originalName = (string) ($file['name'] ?? '');
        $ext = Tools::strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed, true)) {
            die(json_encode(['ok' => false, 'error' => 'Invalid file extension']));
        }

        // Optionnel : vérification MIME basique
        $mime = (string) ($file['type'] ?? '');
        if ($mime !== '' && strpos($mime, 'image/') !== 0) {
            die(json_encode(['ok' => false, 'error' => 'Invalid file type']));
        }

        $uploadDir = _PS_ROOT_DIR_ . '/modules/' . $this->module->name . '/views/img/uploaded';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            die(json_encode(['ok' => false, 'error' => 'Upload directory not writable']));
        }

        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $safeName;

        if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
            die(json_encode(['ok' => false, 'error' => 'Move failed']));
        }

        $url = '/modules/' . $this->module->name . '/views/img/uploaded/' . $safeName;

        die(json_encode([
            'ok' => true,
            'url' => $url,
        ]));
    }
}

