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

        if (empty($apiKey) || empty($apiSecret) || empty($authUrl)) {
            return false;
        }

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
            $token = $this->authenticateAvisVerifies($authUrl, $apiKey, $apiSecret);
            
            if (!$token) {
                return false;
            }

            $apiEndpoint = Configuration::get('CKC_BARREPROMO_AVIS_API_ENDPOINT', 'https://api.skeepers.io/verified-reviews/v1/published/brands');
            
            $ch = curl_init();
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
            
            if (!is_array($data) || empty($data)) {
                PrestaShopLogger::addLog(
                    'Ckcpromotionbanner - Réponse API Skeepers invalide ou vide',
                    3,
                    null,
                    'Ckcpromotionbanner'
                );
                return false;
            }

            $brandData = $data[0];
            $note = null;
            $totalReviews = 0;
            
            if (isset($brandData['one_year']['average'])) {
                $note = (float)$brandData['one_year']['average'];
                $totalReviews = isset($brandData['one_year']['count_total']) ? (int)$brandData['one_year']['count_total'] : 0;
            }
            elseif (isset($brandData['five_year']['average'])) {
                $note = (float)$brandData['five_year']['average'];
                $totalReviews = isset($brandData['five_year']['count_total']) ? (int)$brandData['five_year']['count_total'] : 0;
            }
            
            if (!$note || $note <= 0) {
                PrestaShopLogger::addLog(
                    'Ckcpromotionbanner - Note moyenne non trouvée dans la réponse API',
                    2,
                    null,
                    'Ckcpromotionbanner'
                );
                return false;
            }

            $result = array(
                'note' => number_format($note, 1, '.', ''),
                'total_reviews' => $totalReviews > 0 ? $totalReviews : (isset($brandData['count_total_since_creation']) ? (int)$brandData['count_total_since_creation'] : 0),
                'recent_reviews' => isset($brandData['one_year']['count_total']) ? (int)$brandData['one_year']['count_total'] : 0,
                'certificate_link' => isset($brandData['certificate_link']) ? $brandData['certificate_link'] : '',
            );

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
        
        if ($cachedToken && $tokenTimestamp > 0 && (time() - $tokenTimestamp) < 240) {
            return $cachedToken;
        }
        
        try {
            $basicAuth = base64_encode($apiKey . ':' . $apiSecret);
            
            $ch = curl_init();
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
        $parentForm = parent::getConfigForm();
        $form = array($parentForm);
        
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
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

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
        $fields = array(
            'CKC_BARREPROMO_AVIS_API_KEY' => array('type' => 'text'),
            'CKC_BARREPROMO_AVIS_API_SECRET' => array('type' => 'password'),
            'CKC_BARREPROMO_AVIS_AUTH_URL' => array('type' => 'url', 'error' => 'L\'URL d\'authentification n\'est pas valide.'),
            'CKC_BARREPROMO_AVIS_API_ENDPOINT' => array('type' => 'url', 'error' => 'L\'URL de l\'endpoint API n\'est pas valide.'),
            'CKC_BARREPROMO_AVIS_CACHE_DURATION' => array('type' => 'int', 'error' => 'La durée du cache doit être un nombre entier.', 'min' => 1, 'max' => 1440, 'error_range' => 'La durée du cache doit être entre 1 et 1440 minutes.'),
            'CKC_BARREPROMO_AVIS_FALLBACK_TEXT' => array('type' => 'text'),
            'CKC_BARREPROMO_AVIS_FALLBACK_NOTE' => array('type' => 'text'),
        );
        
        foreach ($fields as $field => $config) {
            $value = Tools::getValue($field);
            
            if ($config['type'] === 'password') {
                if ($value === false || empty($value)) {
                    continue;
                }
            } elseif ($value === false) {
                continue;
            }
            
            if ($config['type'] === 'url' && !empty($value) && !Validate::isUrl($value)) {
                $errors[] = $this->l($config['error']);
                continue;
            }
            
            if ($config['type'] === 'int' && !empty($value)) {
                if (!Validate::isInt($value)) {
                    $errors[] = $this->l($config['error']);
                    continue;
                }
                $value = (int)$value;
                if (isset($config['min']) && isset($config['max']) && ($value < $config['min'] || $value > $config['max'])) {
                    $errors[] = $this->l($config['error_range']);
                    continue;
                }
            }
            
            Configuration::updateValue($field, $config['type'] === 'int' ? $value : Tools::safeOutput($value), true);
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->context->controller->errors[] = $error;
            }
        } else {
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
        $avisData = $this->getAvisVerifiesData();
        $fallbackText = Configuration::get('CKC_BARREPROMO_AVIS_FALLBACK_TEXT', 'Nos clients sont satisfaits');
        
        if ($avisData && isset($avisData['note'])) {
            $this->smarty->assign('avis_note', $avisData['note']);
            $this->smarty->assign('avis_total_reviews', $avisData['total_reviews']);
            $this->smarty->assign('avis_recent_reviews', $avisData['recent_reviews']);
            $this->smarty->assign('avis_text', $fallbackText);
            $this->smarty->assign('avis_certificate_link', !empty($avisData['certificate_link']) ? $avisData['certificate_link'] : '');
        } else {
            $this->smarty->assign('avis_note', false);
            $this->smarty->assign('avis_text', $fallbackText);
            $this->smarty->assign('avis_fallback_note', Configuration::get('CKC_BARREPROMO_AVIS_FALLBACK_NOTE', '4.8/5'));
            $this->smarty->assign('avis_certificate_link', '');
        }
        
        return parent::hookDisplayAfterBodyOpeningTag();
    }
}
