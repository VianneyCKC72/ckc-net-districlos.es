<?php
/**
 * Module dtc_skeepers
 * Intégration API Skeepers (Avis Vérifiés) pour récupération dynamique de la note client
 * et génération de liens personnalisés par commande
 *
 * @author    Agence CKC
 * @copyright 2024
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_skeepers extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_skeepers';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DTC Skeepers - Avis Vérifiés');
        $this->description = $this->l('Intégration API Skeepers pour affichage dynamique des notes clients et génération de liens personnalisés par commande');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        $hookName = 'displaySkeepersRating';
        $idHook = Hook::getIdByName($hookName);
        if (!$idHook) {
            $hook = new Hook();
            $hook->name = $hookName;
            $hook->title = 'Display Skeepers Rating';
            $hook->description = 'Display Skeepers customer rating';
            $hook->position = true;
            $hook->add();
        }

        return parent::install() &&
            $this->registerHook('displaySkeepersRating') &&
            $this->registerHook('actionValidateOrder') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            Configuration::updateValue('DTC_SKEEPERS_API_KEY', '') &&
            Configuration::updateValue('DTC_SKEEPERS_API_SECRET', '') &&
            Configuration::updateValue('DTC_SKEEPERS_AUTH_URL', 'https://auth.skeepers.io/realms/skeepers/protocol/openid-connect/token') &&
            Configuration::updateValue('DTC_SKEEPERS_API_ENDPOINT', 'https://api.skeepers.io/verified-reviews/v1/published/brands') &&
            Configuration::updateValue('DTC_SKEEPERS_WEBSITE_ID', '') &&
            Configuration::updateValue('DTC_SKEEPERS_PURCHASE_EVENTS_ENABLED', 0) &&
            Configuration::updateValue('DTC_SKEEPERS_CACHE_DURATION', 60) &&
            Configuration::updateValue('DTC_SKEEPERS_FALLBACK_TEXT', 'Nos clients sont satisfaits') &&
            Configuration::updateValue('DTC_SKEEPERS_FALLBACK_NOTE', '4.8/5') &&
            $this->createPurchaseEventsTable();
    }

    public function uninstall()
    {
        Configuration::deleteByName('DTC_SKEEPERS_API_KEY');
        Configuration::deleteByName('DTC_SKEEPERS_API_SECRET');
        Configuration::deleteByName('DTC_SKEEPERS_AUTH_URL');
        Configuration::deleteByName('DTC_SKEEPERS_API_ENDPOINT');
        Configuration::deleteByName('DTC_SKEEPERS_WEBSITE_ID');
        Configuration::deleteByName('DTC_SKEEPERS_PURCHASE_EVENTS_ENABLED');
        Configuration::deleteByName('DTC_SKEEPERS_CACHE_DURATION');
        Configuration::deleteByName('DTC_SKEEPERS_FALLBACK_TEXT');
        Configuration::deleteByName('DTC_SKEEPERS_FALLBACK_NOTE');
        Configuration::deleteByName('DTC_SKEEPERS_CACHE_DATA');
        Configuration::deleteByName('DTC_SKEEPERS_CACHE_TIMESTAMP');
        Configuration::deleteByName('DTC_SKEEPERS_TOKEN');
        Configuration::deleteByName('DTC_SKEEPERS_TOKEN_TIMESTAMP');

        $this->dropPurchaseEventsTable();

        return parent::uninstall();
    }

    protected function createPurchaseEventsTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_skeepers_purchase_events` (
            `id_purchase_event` int(11) NOT NULL AUTO_INCREMENT,
            `id_order` int(11) NOT NULL,
            `purchase_reference` varchar(50) NOT NULL,
            `skeepers_purchase_id` varchar(255) DEFAULT NULL,
            `skeepers_review_link` text DEFAULT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_purchase_event`),
            UNIQUE KEY `id_order` (`id_order`),
            KEY `purchase_reference` (`purchase_reference`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    protected function dropPurchaseEventsTable()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dtc_skeepers_purchase_events`;';
        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDtcSkeepersModule')) {
            $output .= $this->postProcess();
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDtcSkeepersModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configuration API Skeepers'),
                    'icon' => 'icon-star',
                ),
                'input' => array(
                    array(
                        'name' => 'DTC_SKEEPERS_API_KEY',
                        'label' => $this->l('Client ID (Skeepers)'),
                        'desc' => $this->l('Votre Client ID depuis https://app.netreviews.eu/ → Account management > API Access'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'DTC_SKEEPERS_API_SECRET',
                        'label' => $this->l('Client Secret (Skeepers)'),
                        'desc' => $this->l('Votre Client Secret depuis https://app.netreviews.eu/ → Account management > API Access'),
                        'type' => 'password',
                        'required' => false,
                    ),
                    array(
                        'name' => 'DTC_SKEEPERS_AUTH_URL',
                        'label' => $this->l('URL d\'authentification'),
                        'desc' => $this->l('URL pour l\'authentification OAuth'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'DTC_SKEEPERS_API_ENDPOINT',
                        'label' => $this->l('Endpoint API'),
                        'desc' => $this->l('URL de l\'endpoint pour récupérer les données'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'DTC_SKEEPERS_WEBSITE_ID',
                        'label' => $this->l('Website ID (UUID)'),
                        'desc' => $this->l('L\'ID du website depuis votre compte Skeepers (format UUID)'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'DTC_SKEEPERS_PURCHASE_EVENTS_ENABLED',
                        'label' => $this->l('Activer Purchase Events'),
                        'desc' => $this->l('Activer l\'envoi automatique des commandes à Skeepers pour générer des liens personnalisés'),
                        'type' => 'switch',
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')),
                        ),
                    ),
                    array(
                        'name' => 'DTC_SKEEPERS_CACHE_DURATION',
                        'label' => $this->l('Durée du cache (en minutes)'),
                        'desc' => $this->l('Durée de mise en cache des données API (recommandé: 60 minutes)'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'DTC_SKEEPERS_FALLBACK_TEXT',
                        'label' => $this->l('Texte de secours'),
                        'desc' => $this->l('Texte affiché si l\'API est indisponible'),
                        'type' => 'text',
                        'required' => false,
                    ),
                    array(
                        'name' => 'DTC_SKEEPERS_FALLBACK_NOTE',
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
    }

    protected function getConfigFormValues()
    {
        return array(
            'DTC_SKEEPERS_API_KEY' => Configuration::get('DTC_SKEEPERS_API_KEY', ''),
            'DTC_SKEEPERS_API_SECRET' => '',
            'DTC_SKEEPERS_AUTH_URL' => Configuration::get('DTC_SKEEPERS_AUTH_URL', 'https://auth.skeepers.io/realms/skeepers/protocol/openid-connect/token'),
            'DTC_SKEEPERS_API_ENDPOINT' => Configuration::get('DTC_SKEEPERS_API_ENDPOINT', 'https://api.skeepers.io/verified-reviews/v1/published/brands'),
            'DTC_SKEEPERS_WEBSITE_ID' => Configuration::get('DTC_SKEEPERS_WEBSITE_ID', ''),
            'DTC_SKEEPERS_PURCHASE_EVENTS_ENABLED' => Configuration::get('DTC_SKEEPERS_PURCHASE_EVENTS_ENABLED', 0),
            'DTC_SKEEPERS_CACHE_DURATION' => Configuration::get('DTC_SKEEPERS_CACHE_DURATION', 60),
            'DTC_SKEEPERS_FALLBACK_TEXT' => Configuration::get('DTC_SKEEPERS_FALLBACK_TEXT', 'Nos clients sont satisfaits'),
            'DTC_SKEEPERS_FALLBACK_NOTE' => Configuration::get('DTC_SKEEPERS_FALLBACK_NOTE', '4.8/5'),
        );
    }

    protected function postProcess()
    {
        $errors = array();
        $fields = array(
            'DTC_SKEEPERS_API_KEY' => array('type' => 'text'),
            'DTC_SKEEPERS_API_SECRET' => array('type' => 'password'),
            'DTC_SKEEPERS_AUTH_URL' => array('type' => 'url', 'error' => 'L\'URL d\'authentification n\'est pas valide.'),
            'DTC_SKEEPERS_API_ENDPOINT' => array('type' => 'url', 'error' => 'L\'URL de l\'endpoint API n\'est pas valide.'),
            'DTC_SKEEPERS_WEBSITE_ID' => array('type' => 'text'),
            'DTC_SKEEPERS_PURCHASE_EVENTS_ENABLED' => array('type' => 'bool'),
            'DTC_SKEEPERS_CACHE_DURATION' => array('type' => 'int', 'error' => 'La durée du cache doit être un nombre entier.', 'min' => 1, 'max' => 1440, 'error_range' => 'La durée du cache doit être entre 1 et 1440 minutes.'),
            'DTC_SKEEPERS_FALLBACK_TEXT' => array('type' => 'text'),
            'DTC_SKEEPERS_FALLBACK_NOTE' => array('type' => 'text'),
        );

        foreach ($fields as $field => $config) {
            if ($config['type'] === 'password') {
                if (!isset($_POST[$field])) {
                    continue;
                }
                $value = Tools::getValue($field);
                if (empty($value)) {
                    continue;
                }
            } elseif ($config['type'] === 'bool') {
                $value = (int)Tools::getValue($field, 0);
            } else {
                $value = Tools::getValue($field);
                if ($value === false) {
                    continue;
                }
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

            Configuration::updateValue($field, $config['type'] === 'int' ? $value : ($config['type'] === 'bool' ? $value : Tools::safeOutput($value)), true);
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->context->controller->errors[] = $error;
            }
            return '';
        } else {
            Configuration::deleteByName('DTC_SKEEPERS_CACHE_DATA');
            Configuration::deleteByName('DTC_SKEEPERS_CACHE_TIMESTAMP');
            Configuration::deleteByName('DTC_SKEEPERS_TOKEN');
            Configuration::deleteByName('DTC_SKEEPERS_TOKEN_TIMESTAMP');
            return $this->displayConfirmation($this->l('Configuration mise à jour avec succès'));
        }
    }

    protected function getSkeepersData()
    {
        $apiKey = Configuration::get('DTC_SKEEPERS_API_KEY');
        $apiSecret = Configuration::get('DTC_SKEEPERS_API_SECRET');
        $authUrl = Configuration::get('DTC_SKEEPERS_AUTH_URL');
        $cacheDuration = (int)Configuration::get('DTC_SKEEPERS_CACHE_DURATION', 60);

        if (empty($apiKey) || empty($apiSecret) || empty($authUrl)) {
            return false;
        }

        $cacheKey = 'DTC_SKEEPERS_CACHE_DATA';
        $cacheTimestampKey = 'DTC_SKEEPERS_CACHE_TIMESTAMP';

        $cachedData = Configuration::get($cacheKey);
        $cachedTimestamp = (int)Configuration::get($cacheTimestampKey);

        if ($cachedData && $cachedTimestamp > 0) {
            $cacheAge = time() - $cachedTimestamp;
            if ($cacheAge < ($cacheDuration * 60)) {
                return json_decode($cachedData, true);
            }
        }

        try {
            $token = $this->authenticateSkeepers($authUrl, $apiKey, $apiSecret);

            if (!$token) {
                return false;
            }

            $apiEndpoint = Configuration::get('DTC_SKEEPERS_API_ENDPOINT', 'https://api.skeepers.io/verified-reviews/v1/published/brands');

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
                    'DtcSkeepers - Erreur API Skeepers: ' . ($curlError ?: 'HTTP ' . $httpCode) . ' - Response: ' . substr($response, 0, 200),
                    3,
                    null,
                    'DtcSkeepers'
                );
                return false;
            }

            $data = json_decode($response, true);

            if (!is_array($data) || empty($data)) {
                PrestaShopLogger::addLog(
                    'DtcSkeepers - Réponse API Skeepers invalide ou vide',
                    3,
                    null,
                    'DtcSkeepers'
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
                    'DtcSkeepers - Note moyenne non trouvée dans la réponse API',
                    2,
                    null,
                    'DtcSkeepers'
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
                'DtcSkeepers - Exception API Skeepers: ' . $e->getMessage(),
                3,
                null,
                'DtcSkeepers'
            );
            return false;
        }
    }

    protected function authenticateSkeepers($authUrl, $apiKey, $apiSecret)
    {
        $tokenCacheKey = 'DTC_SKEEPERS_TOKEN';
        $tokenCacheTimestampKey = 'DTC_SKEEPERS_TOKEN_TIMESTAMP';

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
                    'DtcSkeepers - Erreur authentification Skeepers: ' . ($curlError ?: 'HTTP ' . $httpCode) . ' - Response: ' . substr($response, 0, 200),
                    3,
                    null,
                    'DtcSkeepers'
                );
                return false;
            }

            $data = json_decode($response, true);

            if (!$data || !isset($data['access_token'])) {
                PrestaShopLogger::addLog(
                    'DtcSkeepers - Token non reçu dans la réponse: ' . substr($response, 0, 200),
                    3,
                    null,
                    'DtcSkeepers'
                );
                return false;
            }

            $token = $data['access_token'];

            Configuration::updateValue($tokenCacheKey, $token, true);
            Configuration::updateValue($tokenCacheTimestampKey, time(), true);

            return $token;

        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'DtcSkeepers - Exception authentification: ' . $e->getMessage(),
                3,
                null,
                'DtcSkeepers'
            );
            return false;
        }
    }

    protected function authenticateSkeepersForPurchaseEvents($authUrl, $apiKey, $apiSecret)
    {
        $basicAuth = base64_encode($apiKey . ':' . $apiSecret);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $authUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_POSTFIELDS => http_build_query(array(
                'grant_type' => 'client_credentials',
                'scope' => 'openid',
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
                'DtcSkeepers - Erreur authentification Purchase Events: ' . ($curlError ?: 'HTTP ' . $httpCode),
                3,
                null,
                'DtcSkeepers'
            );
            return false;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['access_token'])) {
            return false;
        }

        return $data['access_token'];
    }

    protected function sendPurchaseEventToSkeepers($order, $customer, $cart)
    {
        $websiteId = Configuration::get('DTC_SKEEPERS_WEBSITE_ID');
        $apiKey = Configuration::get('DTC_SKEEPERS_API_KEY');
        $apiSecret = Configuration::get('DTC_SKEEPERS_API_SECRET');
        $authUrl = Configuration::get('DTC_SKEEPERS_AUTH_URL');

        if (empty($websiteId) || empty($apiKey) || empty($apiSecret)) {
            PrestaShopLogger::addLog(
                'DtcSkeepers - Credentials manquants (Website ID: ' . (!empty($websiteId) ? 'OK' : 'MANQUANT') . ', API Key: ' . (!empty($apiKey) ? 'OK' : 'MANQUANT') . ', API Secret: ' . (!empty($apiSecret) ? 'OK' : 'MANQUANT') . ')',
                3,
                null,
                'DtcSkeepers'
            );
            return false;
        }

        $token = $this->authenticateSkeepersForPurchaseEvents($authUrl, $apiKey, $apiSecret);
        if (!$token) {
            PrestaShopLogger::addLog(
                'DtcSkeepers - Échec authentification Skeepers pour Purchase Events',
                3,
                null,
                'DtcSkeepers'
            );
            return false;
        }

        $orderDetails = $order->getOrderDetailList();
        $products = array();
        
        foreach ($orderDetails as $detail) {
            $product = new Product($detail['product_id'], false, $this->context->language->id);
            $productReference = $product->reference ?: (string)$detail['product_id'];
            $products[] = array(
                'product_ref' => array(
                    'reference' => $productReference,
                ),
                'name' => $product->name,
            );
        }

        $purchaseEvent = array(
            'purchase_reference' => $order->reference,
            'purchase_date' => date('c', strtotime($order->date_add)),
            'price' => (float)$order->total_paid_tax_incl,
            'consumer' => array(
                'email' => $customer->email,
                'first_name' => $customer->firstname,
                'last_name' => $customer->lastname,
            ),
            'products' => $products,
            'sales_channel' => 'online',
            'solicitation_parameters' => new stdClass(),
            'custom_fields' => array(),
        );

        $apiUrl = 'https://api.skeepers.io/purchase-event/websites/' . $websiteId . '/purchase_events/bulk_sync_insert';

        $jsonPayload = json_encode(array($purchaseEvent));
        
        PrestaShopLogger::addLog(
            'DtcSkeepers - Appel API Skeepers Purchase Events pour commande #' . $order->id . ' - URL: ' . $apiUrl . ' - Payload: ' . substr($jsonPayload, 0, 1000),
            1,
            null,
            'DtcSkeepers'
        );

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || ($httpCode !== 201 && $httpCode !== 206)) {
            $errorMessage = 'DtcSkeepers - Erreur envoi Purchase Event pour commande #' . $order->id;
            if ($curlError) {
                $errorMessage .= ' - Erreur cURL: ' . $curlError;
            } else {
                $errorMessage .= ' - Code HTTP: ' . $httpCode;
            }
            if ($response) {
                $errorMessage .= ' - Réponse: ' . substr($response, 0, 1000);
            }
            PrestaShopLogger::addLog(
                $errorMessage,
                3,
                null,
                'DtcSkeepers'
            );
            return false;
        }

        $data = json_decode($response, true);
        if (is_array($data) && !empty($data) && isset($data[0]['id'])) {
            $skeepersPurchaseId = $data[0]['id'];
            
            Db::getInstance()->insert('dtc_skeepers_purchase_events', array(
                'id_order' => (int)$order->id,
                'purchase_reference' => pSQL($order->reference),
                'skeepers_purchase_id' => pSQL($skeepersPurchaseId),
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ));

            PrestaShopLogger::addLog(
                'DtcSkeepers - Purchase Event créé avec succès pour commande #' . $order->id . ' (Skeepers ID: ' . $skeepersPurchaseId . ')',
                1,
                null,
                'DtcSkeepers'
            );

            return $skeepersPurchaseId;
        }

        PrestaShopLogger::addLog(
            'DtcSkeepers - Réponse API invalide pour commande #' . $order->id . ' - Réponse: ' . substr($response, 0, 500),
            3,
            null,
            'DtcSkeepers'
        );
        return false;
    }

    protected function getReviewLinkFromSkeepers($skeepersPurchaseId)
    {
        $apiKey = Configuration::get('DTC_SKEEPERS_API_KEY');
        $apiSecret = Configuration::get('DTC_SKEEPERS_API_SECRET');
        $authUrl = Configuration::get('DTC_SKEEPERS_AUTH_URL');
        $websiteId = Configuration::get('DTC_SKEEPERS_WEBSITE_ID');

        if (empty($skeepersPurchaseId) || empty($apiKey) || empty($apiSecret) || empty($websiteId)) {
            return false;
        }

        $token = $this->authenticateSkeepersForPurchaseEvents($authUrl, $apiKey, $apiSecret);
        if (!$token) {
            return false;
        }

        $apiUrl = 'https://api.skeepers.io/purchase-event/websites/' . $websiteId . '/purchase_events/' . $skeepersPurchaseId;

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
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
                'DtcSkeepers - Erreur récupération lien review: ' . ($curlError ?: 'HTTP ' . $httpCode) . ' - Response: ' . substr($response, 0, 500),
                3,
                null,
                'DtcSkeepers'
            );
            return false;
        }

        $data = json_decode($response, true);
        if (is_array($data) && isset($data['review_link'])) {
            return $data['review_link'];
        }

        return false;
    }

    protected function sendLinkToConnectif($customerEmail, $reviewLink)
    {
        if (empty($customerEmail) || empty($reviewLink)) {
            return false;
        }

        if (!Module::isInstalled('connectif') || !Module::isEnabled('connectif')) {
            return false;
        }

        require_once _PS_MODULE_DIR_ . 'connectif/classes/cn-config-utils.php';
        require_once _PS_MODULE_DIR_ . 'connectif/helpers/connectif-api-helper.php';

        $cnConfigUtils = new CnConfigUtils();
        $connectifPublicApi = $cnConfigUtils->getConnectifPublicApiUrl();
        $storeApiKey = $cnConfigUtils->getApiKey();

        if (empty($connectifPublicApi) || empty($storeApiKey)) {
            PrestaShopLogger::addLog(
                'DtcSkeepers - Connectif non configuré ou API key manquante',
                2,
                null,
                'DtcSkeepers'
            );
            return false;
        }

        $contact = array(
            'skeepers_link' => $reviewLink,
        );

        $result = ConnectifApiHelper::patchContact($customerEmail, $connectifPublicApi, $contact, $storeApiKey);

        if (!$result) {
            PrestaShopLogger::addLog(
                'DtcSkeepers - Erreur envoi lien à Connectif pour ' . $customerEmail,
                3,
                null,
                'DtcSkeepers'
            );
        }

        return $result;
    }

    public function hookDisplaySkeepersRating()
    {
        $skeepersData = $this->getSkeepersData();
        $fallbackText = Configuration::get('DTC_SKEEPERS_FALLBACK_TEXT', 'Nos clients sont satisfaits');
        $fallbackText = $this->trans($fallbackText, [], 'Shop.Theme.Customdtc');

        if ($skeepersData && isset($skeepersData['note'])) {
            $this->context->smarty->assign(array(
                'dtc_skeepers_note' => $skeepersData['note'],
                'dtc_skeepers_total_reviews' => $skeepersData['total_reviews'],
                'dtc_skeepers_recent_reviews' => $skeepersData['recent_reviews'],
                'dtc_skeepers_text' => $fallbackText,
                'dtc_skeepers_certificate_link' => !empty($skeepersData['certificate_link']) ? $skeepersData['certificate_link'] : '',
            ));
        } else {
            $this->context->smarty->assign(array(
                'dtc_skeepers_note' => false,
                'dtc_skeepers_text' => $fallbackText,
                'dtc_skeepers_fallback_note' => Configuration::get('DTC_SKEEPERS_FALLBACK_NOTE', '4.8/5'),
                'dtc_skeepers_certificate_link' => '',
            ));
        }

        return $this->display(__FILE__, 'views/templates/hook/display_rating.tpl');
    }

    public function hookActionValidateOrder($params)
    {
        try {
            if (!Configuration::get('DTC_SKEEPERS_PURCHASE_EVENTS_ENABLED')) {
                return;
            }

            if (!isset($params['order']) || !isset($params['customer']) || !isset($params['cart'])) {
                return;
            }

            $order = $params['order'];
            $customer = $params['customer'];
            $cart = $params['cart'];

            if (!Validate::isLoadedObject($order) || !Validate::isLoadedObject($customer)) {
                return;
            }

            $existingEvent = Db::getInstance()->getValue(
                'SELECT id_purchase_event FROM `' . _DB_PREFIX_ . 'dtc_skeepers_purchase_events` WHERE id_order = ' . (int)$order->id
            );

            if ($existingEvent) {
                return;
            }

            $result = $this->sendPurchaseEventToSkeepers($order, $customer, $cart);
            
            if (!$result) {
                PrestaShopLogger::addLog(
                    'DtcSkeepers - Échec envoi Purchase Event pour commande #' . $order->id,
                    3,
                    null,
                    'DtcSkeepers'
                );
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'DtcSkeepers - Exception dans hookActionValidateOrder pour commande #' . (isset($order) ? $order->id : 'N/A') . ': ' . $e->getMessage(),
                3,
                null,
                'DtcSkeepers'
            );
            // On ne propage pas l'exception pour ne pas bloquer le processus de commande
        }
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        try {
            if (!Configuration::get('DTC_SKEEPERS_PURCHASE_EVENTS_ENABLED')) {
                return;
            }

            if (!isset($params['id_order']) || !isset($params['newOrderStatus'])) {
                return;
            }

            $order = new Order((int)$params['id_order']);
            if (!Validate::isLoadedObject($order)) {
                return;
            }

            $newOrderStatus = $params['newOrderStatus'];
            $paidStatusIds = array(
                (int)Configuration::get('PS_OS_PAYMENT'),
                (int)Configuration::get('PS_OS_WS_PAYMENT')
            );

            if (!in_array((int)$newOrderStatus->id, $paidStatusIds)) {
                return;
            }

            $purchaseEvent = Db::getInstance()->getRow(
                'SELECT skeepers_purchase_id, skeepers_review_link FROM `' . _DB_PREFIX_ . 'dtc_skeepers_purchase_events` WHERE id_order = ' . (int)$order->id
            );

            // Si l'entrée n'existe pas, on la crée en envoyant l'événement à Skeepers
            if (!$purchaseEvent || empty($purchaseEvent['skeepers_purchase_id'])) {
                $customer = new Customer((int)$order->id_customer);
                if (!Validate::isLoadedObject($customer)) {
                    return;
                }
                
                $cart = new Cart((int)$order->id_cart);
                if (!Validate::isLoadedObject($cart)) {
                    return;
                }
                
                // Envoie l'événement à Skeepers et crée l'entrée en BDD
                $skeepersPurchaseId = $this->sendPurchaseEventToSkeepers($order, $customer, $cart);
                if (!$skeepersPurchaseId) {
                    return;
                }
                
                // Récupère l'entrée créée
                $purchaseEvent = Db::getInstance()->getRow(
                    'SELECT skeepers_purchase_id, skeepers_review_link FROM `' . _DB_PREFIX_ . 'dtc_skeepers_purchase_events` WHERE id_order = ' . (int)$order->id
                );
                
                if (!$purchaseEvent || empty($purchaseEvent['skeepers_purchase_id'])) {
                    return;
                }
            }

            if (!empty($purchaseEvent['skeepers_review_link'])) {
                $reviewLink = $purchaseEvent['skeepers_review_link'];
            } else {
                $reviewLink = $this->getReviewLinkFromSkeepers($purchaseEvent['skeepers_purchase_id']);
                
                if ($reviewLink) {
                    Db::getInstance()->update(
                        'dtc_skeepers_purchase_events',
                        array(
                            'skeepers_review_link' => pSQL($reviewLink),
                            'date_upd' => date('Y-m-d H:i:s'),
                        ),
                        'id_order = ' . (int)$order->id
                    );
                }
            }

            if ($reviewLink) {
                $customer = new Customer((int)$order->id_customer);
                if (Validate::isLoadedObject($customer)) {
                    $this->sendLinkToConnectif($customer->email, $reviewLink);
                }
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'DtcSkeepers - Exception dans hookActionOrderStatusPostUpdate pour commande #' . (isset($params['id_order']) ? $params['id_order'] : 'N/A') . ': ' . $e->getMessage(),
                3,
                null,
                'DtcSkeepers'
            );
            // On ne propage pas l'exception pour ne pas bloquer le processus de commande
        }
    }
}
