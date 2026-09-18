<?php
/**
 * Contrôleur pour traiter la soumission du formulaire de concours
 */

class Dtc_Contest_FormSubmitModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Vérifier que c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(false, $this->trans('Méthode non autorisée', [], 'Shop.Theme.Customdtc'), 405);
            return;
        }

        // Vérifier le token
        $token = Tools::getValue('contest_token');
        if ($token !== 'concours-2025') {
            $this->sendJsonResponse(false, $this->trans('Token invalide', [], 'Shop.Theme.Customdtc'), 403);
            return;
        }

        // Charger la classe ObjectModel
        require_once dirname(__FILE__) . '/../../classes/ContestEntry.php';

        // Récupérer et valider les données
        $lastName = Tools::getValue('last_name');
        $firstName = Tools::getValue('first_name');
        $phone = Tools::getValue('phone');
        $email = Tools::getValue('email');
        $activity = Tools::getValue('activity');
        $postalCode = Tools::getValue('postal_code');
        $guessCode = Tools::getValue('guess_code');

        $errors = [];
        if (!$lastName) {
            $errors[] = $this->trans('Nom requis', [], 'Shop.Theme.Customdtc');
        }
        if (!$firstName) {
            $errors[] = $this->trans('Prénom requis', [], 'Shop.Theme.Customdtc');
        }
        if (!$phone) {
            $errors[] = $this->trans('Téléphone requis', [], 'Shop.Theme.Customdtc');
        }
        if (!Validate::isEmail($email)) {
            $errors[] = $this->trans('Email invalide', [], 'Shop.Theme.Customdtc');
        }
        if (!$postalCode) {
            $errors[] = $this->trans('Code postal requis', [], 'Shop.Theme.Customdtc');
        }
        if (!$guessCode) {
            $errors[] = $this->trans('Code à deviner requis', [], 'Shop.Theme.Customdtc');
        }

        if (!empty($errors)) {
            $this->sendJsonResponse(false, implode("\n", $errors), 400);
            return;
        }

        // Sauvegarder en base de données
        $contestEntry = new ContestEntry();
        $contestEntry->last_name = $lastName;
        $contestEntry->first_name = $firstName;
        $contestEntry->phone = $phone;
        $contestEntry->email = $email;
        $contestEntry->activity = $activity;
        $contestEntry->postal_code = $postalCode;
        $contestEntry->guess_code = $guessCode;
        $contestEntry->date_add = date('Y-m-d H:i:s');

        try {
            if (!$contestEntry->add()) {
                PrestaShopLogger::addLog(
                    'Erreur lors de la sauvegarde de la participation au concours: ' . Db::getInstance()->getMsgError(),
                    3,
                    null,
                    'ContestEntry',
                    null,
                    true
                );
                $this->sendJsonResponse(false, $this->trans('Erreur lors de l\'enregistrement', [], 'Shop.Theme.Customdtc'), 500);
                return;
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Exception lors de la sauvegarde de la participation: ' . $e->getMessage(),
                3,
                null,
                'ContestEntry',
                null,
                true
            );
            $this->sendJsonResponse(false, $this->trans('Erreur lors de l\'enregistrement', [], 'Shop.Theme.Customdtc'), 500);
            return;
        }

        // Envoyer l'email
        $message = sprintf(
            "Nom : %s %s\nTéléphone : %s\nEmail : %s\nActivité : %s\nCode postal : %s\nDevinez le code : %s",
            pSQL($lastName),
            pSQL($firstName),
            pSQL($phone),
            pSQL($email),
            pSQL($activity) ?: $this->trans('Non renseignée', [], 'Shop.Theme.Customdtc'),
            pSQL($postalCode),
            pSQL($guessCode)
        );

        $templateVars = [
            '{email}' => pSQL($email),
            '{message}' => nl2br($message),
            '{order_name}' => '',
            '{attached_file}' => '',
        ];

        $success = Mail::Send(
            (int)$this->context->language->id,
            'contact',
            $this->trans('Participation concours', [], 'Shop.Theme.Customdtc'),
            $templateVars,
            'yan.ledoux@districlos.com',
            // 'clementine.rozenblum@districlos.com',
            null,
            null,
            null,
            null,
            null,
            _PS_MAIL_DIR_
        );

        if (!$success) {
            PrestaShopLogger::addLog(
                'Erreur lors de l\'envoi de l\'email pour la participation ID: ' . $contestEntry->id,
                3,
                null,
                'ContestEntry',
                $contestEntry->id,
                true
            );
            // On considère que l'enregistrement en base est réussi même si l'email échoue
        }

        $this->sendJsonResponse(true, $this->trans('Merci, votre participation est enregistrée.', [], 'Shop.Theme.Customdtc'));
    }

    /**
     * Envoie une réponse JSON
     */
    private function sendJsonResponse($success, $message, $httpCode = 200)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
        exit;
    }
}
