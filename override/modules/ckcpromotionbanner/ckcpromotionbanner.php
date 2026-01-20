<?php
/**
 * Override du module ckcpromotionbanner
 * Intégration API Avis Vérifiés pour récupération dynamique de la note client
 *
 * @author    Agence CKC
 * @copyright 2024
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcpromotionbannerOverride extends Ckcpromotionbanner
{
    /**
     * Récupère les données Avis Vérifiés via l'API avec système de cache
     *
     * @return array|false Retourne un tableau avec 'note' et 'total_reviews' ou false en cas d'erreur
     */
    protected function getAvisVerifiesData()
    {
        $apiKey = Configuration::get('CKC_BARREPROMO_AVIS_API_KEY');
        $apiSecret = Configuration::get('CKC_BARREPROMO_AVIS_API_SECRET');
        $authUrl = Configuration::get('CKC_BARREPROMO_AVIS_AUTH_URL');
        $cacheDuration = (int)Configuration::get('CKC_BARREPROMO_AVIS_CACHE_DURATION', 60);

        // Vérification des paramètres requis
        if (empty($apiKey) || empty($apiSecret) || empty($authUrl)) {
            return false;
        }

        // Vérification du cache persistant (Configuration)
        $cacheKey = 'CKC_BARREPROMO_AVIS_CACHE_DATA';
        $cacheTimestampKey = 'CKC_BARREPROMO_AVIS_CACHE_TIMESTAMP';
        
        $cachedData = Configuration::get($cacheKey);
        $cachedTimestamp = (int)Configuration::get($cacheTimestampKey);
        
        if ($cachedData && $cachedTimestamp > 0) {
            $cacheAge = time() - $cachedTimestamp;
            if ($cacheAge < ($cacheDuration * 60)) {
                return json_decode($cachedData, true);
            }
        }

        try {
            // Étape 1 : Authentification OAuth
            $token = $this->authenticateAvisVerifies($authUrl, $apiKey, $apiSecret);
            
            if (!$token) {
                return false;
            }

            // Étape 2 : Récupération des données (note moyenne de la marque)
            // Endpoint Skeepers pour obtenir les notes agrégées de la marque
            $apiEndpoint = Configuration::get('CKC_BARREPROMO_AVIS_API_ENDPOINT', 'https://api.skeepers.io/verified-reviews/v1/published/brands');
            
            $ch = curl_init();
            // SSL toujours activé en production
            curl_setopt_array($ch, array(
                CURLOPT_URL => $apiEndpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                ),
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError || $httpCode !== 200) {
                PrestaShopLogger::addLog(
                    'Ckcpromotionbanner - Erreur API Skeepers: ' . ($curlError ?: 'HTTP ' . $httpCode) . ' - Response: ' . substr($response, 0, 200),
                    3,
                    null,
                    'Ckcpromotionbanner'
                );
                return false;
            }

            $data = json_decode($response, true);
            
            // La réponse est un tableau, on prend le premier élément (ou on calcule la moyenne)
            if (!is_array($data) || empty($data)) {
                PrestaShopLogger::addLog(
                    'Ckcpromotionbanner - Réponse API Skeepers invalide ou vide',
                    3,
                    null,
                    'Ckcpromotionbanner'
                );
                return false;
            }

            // Extraction de la note moyenne depuis la structure Skeepers
            // La note peut être dans one_year.average ou five_year.average
            $note = null;
            $totalReviews = 0;
            
            // On prend le premier shop/brand de la réponse
            $brandData = $data[0];
            
            // Vérifier si la note est dans one_year (dernière année)
            if (isset($brandData['one_year']['average'])) {
                $note = (float)$brandData['one_year']['average'];
                $totalReviews = isset($brandData['one_year']['count_total']) ? (int)$brandData['one_year']['count_total'] : 0;
            }
            // Sinon vérifier dans five_year (5 dernières années)
            elseif (isset($brandData['five_year']['average'])) {
                $note = (float)$brandData['five_year']['average'];
                $totalReviews = isset($brandData['five_year']['count_total']) ? (int)$brandData['five_year']['count_total'] : 0;
            }
            // Sinon utiliser count_total_since_creation si disponible
            elseif (isset($brandData['count_total_since_creation']) && $brandData['count_total_since_creation'] > 0) {
                // Si pas de note moyenne dans la réponse, on peut essayer de récupérer depuis /brands/reviews
                // Pour l'instant, on retourne false et on utilisera les valeurs de secours
                PrestaShopLogger::addLog(
                    'Ckcpromotionbanner - Note moyenne non trouvée dans la réponse API',
                    2,
                    null,
                    'Ckcpromotionbanner'
                );
                return false;
            }
            
            if ($note === null || $note <= 0) {
                return false;
            }

            // Formatage des données
            $result = array(
                'note' => number_format($note, 1, '.', ''),
                'total_reviews' => $totalReviews > 0 ? $totalReviews : (isset($brandData['count_total_since_creation']) ? (int)$brandData['count_total_since_creation'] : 0),
                'recent_reviews' => isset($brandData['one_year']['count_total']) ? (int)$brandData['one_year']['count_total'] : 0,
                'certificate_link' => isset($brandData['certificate_link']) ? $brandData['certificate_link'] : '',
            );

            // Mise en cache persistant (Configuration)
            Configuration::updateValue($cacheKey, json_encode($result), true);
            Configuration::updateValue($cacheTimestampKey, time(), true);

            return $result;

        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Ckcpromotionbanner - Exception API Avis Vérifiés: ' . $e->getMessage(),
                3,
                null,
                'Ckcpromotionbanner'
            );
            return false;
        }
    }

    /**
     * Authentification OAuth avec Skeepers (Avis Vérifiés) avec cache du token
     *
     * @param string $authUrl URL d'authentification
     * @param string $apiKey Client ID
     * @param string $apiSecret Client Secret
     * @return string|false Token d'accès ou false en cas d'erreur
     */
    protected function authenticateAvisVerifies($authUrl, $apiKey, $apiSecret)
    {
        // Vérification du cache du token OAuth (durée de vie ~5 minutes selon la doc Skeepers)
        $tokenCacheKey = 'CKC_BARREPROMO_AVIS_TOKEN';
        $tokenCacheTimestampKey = 'CKC_BARREPROMO_AVIS_TOKEN_TIMESTAMP';
        
        $cachedToken = Configuration::get($tokenCacheKey);
        $tokenTimestamp = (int)Configuration::get($tokenCacheTimestampKey);
        
        // Le token expire après 300 secondes (5 minutes), on le renouvelle après 4 minutes pour être sûr
        if ($cachedToken && $tokenTimestamp > 0 && (time() - $tokenTimestamp) < 240) {
            return $cachedToken;
        }
        
        try {
            // Encodage Base64 pour Basic Auth
            $basicAuth = base64_encode($apiKey . ':' . $apiSecret);
            
            $ch = curl_init();
            // SSL toujours activé en production
            curl_setopt_array($ch, array(
                CURLOPT_URL => $authUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_POSTFIELDS => http_build_query(array(
                    'grant_type' => 'client_credentials',
                    'audience' => 'verified-reviews',
                )),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic ' . $basicAuth,
                    'Content-Type: application/x-www-form-urlencoded',
                ),
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError || $httpCode !== 200) {
                PrestaShopLogger::addLog(
                    'Ckcpromotionbanner - Erreur authentification Skeepers: ' . ($curlError ?: 'HTTP ' . $httpCode) . ' - Response: ' . substr($response, 0, 200),
                    3,
                    null,
                    'Ckcpromotionbanner'
                );
                return false;
            }

            $data = json_decode($response, true);
            
            if (!$data || !isset($data['access_token'])) {
                PrestaShopLogger::addLog(
                    'Ckcpromotionbanner - Token non reçu dans la réponse: ' . substr($response, 0, 200),
                    3,
                    null,
                    'Ckcpromotionbanner'
                );
                return false;
            }

            $token = $data['access_token'];
            
            // Mise en cache du token OAuth
            Configuration::updateValue($tokenCacheKey, $token, true);
            Configuration::updateValue($tokenCacheTimestampKey, time(), true);

            return $token;

        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Ckcpromotionbanner - Exception authentification: ' . $e->getMessage(),
                3,
                null,
                'Ckcpromotionbanner'
            );
            return false;
        }
    }

    /**
     * Installation - Ajout des nouvelles configurations
     */
    public function install()
    {
        $result = parent::install();
        
        if ($result) {
            Configuration::updateValue('CKC_BARREPROMO_AVIS_API_KEY', '');
            Configuration::updateValue('CKC_BARREPROMO_AVIS_API_SECRET', '');
            Configuration::updateValue('CKC_BARREPROMO_AVIS_AUTH_URL', '');
            Configuration::updateValue('CKC_BARREPROMO_AVIS_API_ENDPOINT', 'https://api.skeepers.io/verified-reviews/v1/published/brands');
            Configuration::updateValue('CKC_BARREPROMO_AVIS_CACHE_DURATION', 60);
            Configuration::updateValue('CKC_BARREPROMO_AVIS_FALLBACK_TEXT', 'Nos clients sont satisfaits');
            Configuration::updateValue('CKC_BARREPROMO_AVIS_FALLBACK_NOTE', '4.8/5');
        }
        
        return $result;
    }

    /**
     * Désinstallation - Suppression des configurations
     */
    public function uninstall()
    {
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_API_KEY');
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_API_SECRET');
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_AUTH_URL');
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_API_ENDPOINT');
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_CACHE_DURATION');
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_FALLBACK_TEXT');
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_FALLBACK_NOTE');
        
        // Suppression du cache persistant
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_CACHE_DATA');
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_CACHE_TIMESTAMP');
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_TOKEN');
        Configuration::deleteByName('CKC_BARREPROMO_AVIS_TOKEN_TIMESTAMP');
        
        return parent::uninstall();
    }

    /**
     * Ajout des champs de configuration pour Avis Vérifiés
     */
    protected function getConfigForm()
    {
        // Récupération du formulaire parent (retourne array('form' => ...))
        $parentForm = parent::getConfigForm();
        
        // Conversion en tableau numérique pour pouvoir ajouter plusieurs formulaires
        $form = array($parentForm);
        
        // Ajout d'une nouvelle section pour Avis Vérifiés
        $avisForm = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Avis Vérifiés - Configuration API'),
                    'icon' => 'icon-star',
                ),
                'input' => array(
                    array(
                        'name' => 'CKC_BARREPROMO_AVIS_API_KEY',
                        'label' => $this->l('Client ID (Skeepers)'),
                        'desc' => $this->l('Votre Client ID depuis https://app.netreviews.eu/ → Account management > API Access'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_AVIS_API_SECRET',
                        'label' => $this->l('Client Secret (Skeepers)'),
                        'desc' => $this->l('Votre Client Secret depuis https://app.netreviews.eu/ → Account management > API Access'),
                        'type' => 'password',
                        'required' => false,
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_AVIS_AUTH_URL',
                        'label' => $this->l('URL d\'authentification'),
                        'desc' => $this->l('URL pour l\'authentification OAuth (ex: https://auth.skeepers.io/realms/skeepers/protocol/openid-connect/token)'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_AVIS_API_ENDPOINT',
                        'label' => $this->l('Endpoint API'),
                        'desc' => $this->l('URL de l\'endpoint pour récupérer les données (ex: https://api.skeepers.io/verified-reviews/v1/published/brands)'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_AVIS_CACHE_DURATION',
                        'label' => $this->l('Durée du cache (en minutes)'),
                        'desc' => $this->l('Durée de mise en cache des données API (recommandé: 60 minutes)'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_AVIS_FALLBACK_TEXT',
                        'label' => $this->l('Texte de secours'),
                        'desc' => $this->l('Texte affiché si l\'API est indisponible'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_AVIS_FALLBACK_NOTE',
                        'label' => $this->l('Note de secours'),
                        'desc' => $this->l('Note affichée si l\'API est indisponible (ex: 4.8/5)'),
                        'type' => 'text',
                        'required' => false,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
        
        // Fusion des deux formulaires
        $form[] = $avisForm;
        
        return $form;
    }

    /**
     * Surcharge de renderForm pour gérer plusieurs formulaires
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCkc_barrepromoModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        // getConfigForm() retourne maintenant un tableau numérique de formulaires
        return $helper->generateForm($this->getConfigForm());
    }

    /**
     * Ajout des valeurs de configuration pour Avis Vérifiés
     */
    protected function getConfigFormValues()
    {
        $values = parent::getConfigFormValues();
        
        $values['CKC_BARREPROMO_AVIS_API_KEY'] = Configuration::get('CKC_BARREPROMO_AVIS_API_KEY', '');
        $values['CKC_BARREPROMO_AVIS_API_SECRET'] = Configuration::get('CKC_BARREPROMO_AVIS_API_SECRET', '');
        $values['CKC_BARREPROMO_AVIS_AUTH_URL'] = Configuration::get('CKC_BARREPROMO_AVIS_AUTH_URL', '');
        $values['CKC_BARREPROMO_AVIS_API_ENDPOINT'] = Configuration::get('CKC_BARREPROMO_AVIS_API_ENDPOINT', 'https://api.skeepers.io/verified-reviews/v1/published/brands');
        $values['CKC_BARREPROMO_AVIS_CACHE_DURATION'] = Configuration::get('CKC_BARREPROMO_AVIS_CACHE_DURATION', 60);
        $values['CKC_BARREPROMO_AVIS_FALLBACK_TEXT'] = Configuration::get('CKC_BARREPROMO_AVIS_FALLBACK_TEXT', 'Nos clients sont satisfaits');
        $values['CKC_BARREPROMO_AVIS_FALLBACK_NOTE'] = Configuration::get('CKC_BARREPROMO_AVIS_FALLBACK_NOTE', '4.8/5');
        
        return $values;
    }

    /**
     * Traitement du formulaire - Gestion des champs Avis Vérifiés avec validation
     */
    protected function postProcess()
    {
        parent::postProcess();
        
        $errors = array();
        
        // Traitement des champs Avis Vérifiés avec validation
        $apiKey = Tools::getValue('CKC_BARREPROMO_AVIS_API_KEY');
        if ($apiKey !== false) {
            Configuration::updateValue('CKC_BARREPROMO_AVIS_API_KEY', Tools::safeOutput($apiKey), true);
        }
        
        $apiSecret = Tools::getValue('CKC_BARREPROMO_AVIS_API_SECRET');
        if ($apiSecret !== false) {
            Configuration::updateValue('CKC_BARREPROMO_AVIS_API_SECRET', Tools::safeOutput($apiSecret), true);
        }
        
        $authUrl = Tools::getValue('CKC_BARREPROMO_AVIS_AUTH_URL');
        if ($authUrl !== false) {
            if (!empty($authUrl) && !Validate::isUrl($authUrl)) {
                $errors[] = $this->l('L\'URL d\'authentification n\'est pas valide.');
            } else {
                Configuration::updateValue('CKC_BARREPROMO_AVIS_AUTH_URL', Tools::safeOutput($authUrl), true);
            }
        }
        
        $apiEndpoint = Tools::getValue('CKC_BARREPROMO_AVIS_API_ENDPOINT');
        if ($apiEndpoint !== false) {
            if (!empty($apiEndpoint) && !Validate::isUrl($apiEndpoint)) {
                $errors[] = $this->l('L\'URL de l\'endpoint API n\'est pas valide.');
            } else {
                Configuration::updateValue('CKC_BARREPROMO_AVIS_API_ENDPOINT', Tools::safeOutput($apiEndpoint), true);
            }
        }
        
        $cacheDuration = Tools::getValue('CKC_BARREPROMO_AVIS_CACHE_DURATION');
        if ($cacheDuration !== false) {
            if (!empty($cacheDuration) && !Validate::isInt($cacheDuration)) {
                $errors[] = $this->l('La durée du cache doit être un nombre entier.');
            } else {
                $cacheDuration = (int)$cacheDuration;
                if ($cacheDuration < 1 || $cacheDuration > 1440) {
                    $errors[] = $this->l('La durée du cache doit être entre 1 et 1440 minutes.');
                } else {
                    Configuration::updateValue('CKC_BARREPROMO_AVIS_CACHE_DURATION', $cacheDuration, true);
                }
            }
        }
        
        $fallbackText = Tools::getValue('CKC_BARREPROMO_AVIS_FALLBACK_TEXT');
        if ($fallbackText !== false) {
            Configuration::updateValue('CKC_BARREPROMO_AVIS_FALLBACK_TEXT', Tools::safeOutput($fallbackText), true);
        }
        
        $fallbackNote = Tools::getValue('CKC_BARREPROMO_AVIS_FALLBACK_NOTE');
        if ($fallbackNote !== false) {
            Configuration::updateValue('CKC_BARREPROMO_AVIS_FALLBACK_NOTE', Tools::safeOutput($fallbackNote), true);
        }
        
        // Affichage des erreurs si présentes
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->context->controller->errors[] = $error;
            }
        } else {
            // Invalidation du cache et du token lors de la modification des paramètres API
            Configuration::deleteByName('CKC_BARREPROMO_AVIS_CACHE_DATA');
            Configuration::deleteByName('CKC_BARREPROMO_AVIS_CACHE_TIMESTAMP');
            Configuration::deleteByName('CKC_BARREPROMO_AVIS_TOKEN');
            Configuration::deleteByName('CKC_BARREPROMO_AVIS_TOKEN_TIMESTAMP');
        }
    }

    /**
     * Hook displayAfterBodyOpeningTag - Ajout des données Avis Vérifiés
     */
    public function hookDisplayAfterBodyOpeningTag()
    {
        // Récupération des données Avis Vérifiés AVANT d'appeler le parent
        // pour que les variables soient disponibles lors du rendu du template
        $avisData = $this->getAvisVerifiesData();
        
        if ($avisData && isset($avisData['note'])) {
            // Données récupérées avec succès depuis l'API
            $this->smarty->assign('avis_note', $avisData['note']);
            $this->smarty->assign('avis_total_reviews', $avisData['total_reviews']);
            $this->smarty->assign('avis_recent_reviews', $avisData['recent_reviews']);
            $this->smarty->assign('avis_text', Configuration::get('CKC_BARREPROMO_AVIS_FALLBACK_TEXT', 'Nos clients sont satisfaits'));
            // Lien dynamique vers la page des avis (certificate_link)
            $certificateLink = isset($avisData['certificate_link']) && !empty($avisData['certificate_link']) ? $avisData['certificate_link'] : '';
            $this->smarty->assign('avis_certificate_link', $certificateLink);
        } else {
            // Utilisation des valeurs de secours
            $this->smarty->assign('avis_note', false);
            $this->smarty->assign('avis_text', Configuration::get('CKC_BARREPROMO_AVIS_FALLBACK_TEXT', 'Nos clients sont satisfaits'));
            $this->smarty->assign('avis_fallback_note', Configuration::get('CKC_BARREPROMO_AVIS_FALLBACK_NOTE', '4.8/5'));
            $this->smarty->assign('avis_certificate_link', '');
        }
        
        // Maintenant on appelle le parent qui va rendre le template avec nos variables
        return parent::hookDisplayAfterBodyOpeningTag();
    }
}
