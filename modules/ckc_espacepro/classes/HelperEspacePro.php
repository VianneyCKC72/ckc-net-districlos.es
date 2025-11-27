<?php
if (!defined('_PS_VERSION_')) { exit; }

if (!class_exists('HelperEspacePro', false)) {
    class HelperEspacePro
    {
        public $warning = [];
        public $error   = [];
        public $success = [];
        public $info    = [];

        public function addNotification($url = '/')
        {
            $notifications = json_encode([
                                             'error'   => $this->error,
                                             'warning' => $this->warning,
                                             'success' => $this->success,
                                             'info'    => $this->info,
                                         ]);

            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }

            $_SESSION['notifications'] = $notifications;
            Tools::redirect($url);
        }

        public function getUploadErrorMessage($errorCode)
        {
            $errors = [
                UPLOAD_ERR_INI_SIZE   => "Le fichier téléchargé excède la directive upload_max_filesize dans php.ini.",
                UPLOAD_ERR_FORM_SIZE  => "Le fichier téléchargé excède la directive MAX_FILE_SIZE spécifiée dans le formulaire HTML.",
                UPLOAD_ERR_PARTIAL    => "Le fichier n'a été que partiellement téléchargé.",
                UPLOAD_ERR_NO_FILE    => "Aucun fichier n'a été téléchargé.",
                UPLOAD_ERR_NO_TMP_DIR => "Manque un dossier temporaire.",
                UPLOAD_ERR_CANT_WRITE => "Échec de l'écriture du fichier sur le disque.",
                UPLOAD_ERR_EXTENSION  => "Une extension PHP a arrêté l'envoi de fichier."
            ];
            return $errors[$errorCode] ?? "Erreur inconnue lors du téléchargement du fichier.";
        }

        public function redirect()
        {
            $urlComponents = parse_url($_SERVER['REQUEST_URI']);
            parse_str($urlComponents['query'] ?? '', $queryParams);
            $activeValue = $queryParams['active'] ?? null;
            unset($queryParams['id_customer'], $queryParams['active']);
            $queryParams['redirectOk'] = ($activeValue == 1 ? 'active' : 'desactive');
            $urlComponents['query'] = http_build_query($queryParams);

            // http_build_url n’est pas natif partout : on reconstruit simplement
            $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'https';
            $host   = $_SERVER['HTTP_HOST'] ?? '';
            $path   = $urlComponents['path'] ?? '';
            $qs     = $urlComponents['query'] ? '?'.$urlComponents['query'] : '';
            header('Location: '.$scheme.'://'.$host.$path.$qs);
            exit;
        }
    }
}
