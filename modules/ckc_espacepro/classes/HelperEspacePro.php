<?php

class HelperEspacePro
{
    public $warning = [];
    public $error   = [];
    public $success = [];
    public $info    = [];

    /**
     * Ajoute une notification et redirige ou enregistre dans la session ou un cookie.
     *
     *
     * @return void
     */
    public function addNotification($url = '/')
    {
        $notifications = json_encode([
                                         'error'   => $this->error,
                                         'warning' => $this->warning,
                                         'success' => $this->success,
                                         'info'    => $this->info,
                                     ]);

        if (session_status() !== PHP_SESSION_ACTIVE)
            session_start();

        $_SESSION['notifications'] = $notifications;

        return Tools::redirect($url);
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
        // Parse the current URL and its query parameters
        $urlComponents = parse_url($_SERVER['REQUEST_URI']);

        parse_str($urlComponents['query'], $queryParams);
        $activeValue = isset($queryParams['active']) ? $queryParams['active'] : null;

        unset($queryParams['id_customer'], $queryParams['active']);

        $redirectOkValue           = $activeValue == 1 ? 'active' : 'desactive';
        $queryParams['redirectOk'] = $redirectOkValue;
        $urlComponents['query']    = http_build_query($queryParams);
        $newUrl                    = http_build_url($urlComponents);

        // Redirect to the new URL
        header('Location: ' . $newUrl);
        exit;
    }

}