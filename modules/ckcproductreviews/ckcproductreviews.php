<?php

if (!defined('_PS_VERSION_')) exit;

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class CkcProductReviews extends Module implements WidgetInterface
{
    const CONFIG_MIN_RATING = 'CKC_REVIEWS_MIN_RATING';
    const AV_BRAND_REVIEWS_URL = 'https://api.skeepers.io/verified-reviews/v1/published/brands/reviews';
    const CACHE_TTL = 21600;

    public function __construct()
    {
        $this->name = 'ckcproductreviews';
        $this->author = 'Vianney - CKC NET';
        $this->version = '1.1.0';
        $this->bootstrap = true;
        $this->tab = 'front_office_features';
        $this->need_instance = true;
        $this->multishop_context = true;
        $this->logo_path     = $this->_path . 'logo.png';

        parent::__construct();
        $this->_path_assets = $this->_path.'views/assets/';

        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
        $this->displayName = $this->l('CKC Product Reviews');
        $this->description = $this->l('Affiche un carrousel personnalisé des avis clients.');
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue(self::CONFIG_MIN_RATING, 4)
            && Configuration::updateValue('CKC_REVIEWS_ENABLE_CAROUSEL', 1)
            && $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('CKC_REVIEWS_ENABLE_CAROUSEL')
            && Configuration::deleteByName(self::CONFIG_MIN_RATING);
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitCkcProductReviews')) {
            $minRating = (int) Tools::getValue(self::CONFIG_MIN_RATING);
            $enableCarousel = (int) Tools::getValue('CKC_REVIEWS_ENABLE_CAROUSEL');
            Configuration::updateValue(self::CONFIG_MIN_RATING, $minRating);
            Configuration::updateValue('CKC_REVIEWS_ENABLE_CAROUSEL', $enableCarousel);
            $output .= $this->displayConfirmation($this->l('Configuration enregistrée.'));
        }

        $minRating = (int) Configuration::get(self::CONFIG_MIN_RATING);
        $enableCarousel = (int) Configuration::get('CKC_REVIEWS_ENABLE_CAROUSEL');

        return $output.$this->renderForm($minRating, $enableCarousel);
    }

    private function renderForm($minRating, $enableCarousel)
    {
        $form = new HelperForm();

        $form->module = $this;
        $form->name_controller = $this->name;
        $form->token = Tools::getAdminTokenLite('AdminModules');
        $form->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $form->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $form->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $form->title = $this->displayName;
        $form->show_cancel_button = false;
        $form->submit_action = 'submitCkcProductReviews';

        $fields_form = [
            'form' => [
                'legend' => ['title' => $this->l('Paramètres du carrousel d’avis')],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Note minimale à afficher'),
                        'name' => self::CONFIG_MIN_RATING,
                        'options' => [
                            'query' => [
                                ['id' => 1, 'name' => '1 étoile et plus'],
                                ['id' => 2, 'name' => '2 étoiles et plus'],
                                ['id' => 3, 'name' => '3 étoiles et plus'],
                                ['id' => 4, 'name' => '4 étoiles et plus'],
                                ['id' => 5, 'name' => '5 étoiles uniquement'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activer le carrousel Swiper'),
                        'name' => 'CKC_REVIEWS_ENABLE_CAROUSEL',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ]
                ],

                'submit' => [
                    'title' => $this->l('Sauvegarder'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $form->fields_value = [self::CONFIG_MIN_RATING => $minRating, 'CKC_REVIEWS_ENABLE_CAROUSEL' => $enableCarousel];

        return $form->generateForm([$fields_form]);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!(int) Configuration::get('CKC_REVIEWS_ENABLE_CAROUSEL')) {
            return '';
        }

        $minRating = (int) Configuration::get(self::CONFIG_MIN_RATING);

        $sql = "SELECT customer_name, rating, review, date_add 
            FROM "._DB_PREFIX_."av_products_reviews 
            WHERE active = 1 
              AND rating >= ".(int)$minRating."
            ORDER BY date_add DESC 
            LIMIT 10";

        $reviews = Db::getInstance()->executeS($sql);

        $this->context->controller->addCSS($this->_path_assets.'ckcproductreviews.css');
        $this->context->controller->addJS($this->_path_assets.'ckcproductreviews.js');

        $this->smarty->assign([
            'reviews' => $reviews,
        ]);


        return $this->fetch('module:ckcproductreviews/views/templates/hook/displayProductReviews.tpl');
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        return [];
    }

    public function hookDisplayHome($params)
    {
        $enableCarousel = (int) Configuration::get('CKC_REVIEWS_ENABLE_CAROUSEL');

        $minRating = (int) Configuration::get(self::CONFIG_MIN_RATING);
        if ($minRating < 1) {
            $minRating = 4;
        }

        $allReviews = $this->getBrandReviewsFromApi();

        // Dédoublonnage par référence commande + filtrage par note minimum
        $seen = [];
        $reviews = [];
        foreach ($allReviews as $r) {
            if ((int) ($r['review_rate'] ?? 0) < $minRating) {
                continue;
            }

            $orderRef = $r['order_reference'] ?? '';
            if (!empty($orderRef) && isset($seen[$orderRef])) {
                continue;
            }
            $seen[$orderRef] = true;

            $firstname = $r['author_firstname'] ?? '';
            $lastname = $r['author_lastname'] ?? '';

            $reviews[] = [
                'rate' => $r['review_rate'],
                // Le template applique urldecode() à l'affichage, on encode ici pour rester compatible
                'review' => urlencode($r['review_content'] ?? ''),
                'customer_name' => trim($firstname . ' ' . mb_substr($lastname, 0, 1) . '.'),
                'horodate' => $r['publish_date'] ?? '',
                'horodate_order' => $r['order_date'] ?? '',
                'date_experience' => $r['order_date'] ?? '',
            ];

            if (count($reviews) >= 10) {
                break;
            }
        }

        $nb_reviews_12_months = $this->getSiteReviewCount();

        $this->context->controller->addCSS($this->_path_assets.'ckcproductreviews.css');
        $this->context->controller->addJS($this->_path_assets.'ckcproductreviews.js');
        $this->context->smarty->assign([
            'reviews' => $reviews,
            'enable_carousel' => $enableCarousel,
            'nb_reviews_12_months' => $nb_reviews_12_months,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/displayProductReviews.tpl');
    }

    /** Récupère les avis site/marque via la Published API Skeepers, avec cache fichier 6h. */
    private function getBrandReviewsFromApi(): array
    {
        static $memoryCache = null;
        if ($memoryCache !== null) {
            return $memoryCache;
        }

        $cacheDir = _PS_MODULE_DIR_ . $this->name . '/cache/';
        $cacheFile = $cacheDir . 'reviews_homepage.json';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if (is_array($data)) {
                return $memoryCache = $data;
            }
        }

        $token = $this->getOAuthToken();
        if ($token === null) {
            return [];
        }

        $ch = curl_init(self::AV_BRAND_REVIEWS_URL . '?sort=review_date&desc=review_date&limit=30');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        ]);
        $resp = curl_exec($ch);
        $ok = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);

        $reviews = ($ok && is_string($resp)) ? json_decode($resp, true) : null;
        if (!is_array($reviews)) {
            return [];
        }

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, json_encode($reviews));
        return $memoryCache = $reviews;
    }

    /** Récupère le token OAuth depuis le cache dtc_skeepers ou le demande avec ses credentials. */
    private function getOAuthToken(): ?string
    {
        $cached = Configuration::get('DTC_SKEEPERS_TOKEN');
        $ts = (int) Configuration::get('DTC_SKEEPERS_TOKEN_TIMESTAMP');
        if (!empty($cached) && $ts > 0 && (time() - $ts) < 240) {
            return $cached;
        }

        // Credentials dtc_skeepers en priorité, sinon fallback sur ceux de netreviews
        $apiKey = Configuration::get('DTC_SKEEPERS_API_KEY');
        $apiSecret = Configuration::get('DTC_SKEEPERS_API_SECRET');
        $authUrl = Configuration::get('DTC_SKEEPERS_AUTH_URL');

        if (empty($apiKey) || empty($apiSecret)) {
            $apiKey = Configuration::get('AV_CLIENTID');
            $apiSecret = Configuration::get('AV_SECRETAPI');
            $authUrl = 'https://auth.skeepers.io/am/oauth2/alpha/access_token';
        }
        if (empty($apiKey) || empty($apiSecret) || empty($authUrl)) {
            return null;
        }

        $ch = curl_init($authUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => http_build_query(['grant_type' => 'client_credentials', 'audience' => 'verified-reviews']),
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($apiKey . ':' . $apiSecret),
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);
        $resp = curl_exec($ch);
        $ok = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);

        $data = ($ok && is_string($resp)) ? json_decode($resp, true) : null;
        if (empty($data['access_token'])) {
            return null;
        }

        Configuration::updateValue('DTC_SKEEPERS_TOKEN', $data['access_token'], true);
        Configuration::updateValue('DTC_SKEEPERS_TOKEN_TIMESTAMP', time(), true);
        return $data['access_token'];
    }

    private function getSiteReviewCount(): string
    {
        $id = Configuration::get('DTC_SKEEPERS_WEBSITE_ID') ?: Configuration::get('AV_IDWEBSITE');
        if (empty($id)) {
            return '';
        }
        $p = str_split($id, 1);
        $url = sprintf('https://cl.avis-verifies.com/fr/cache/%s/%s/%s/%s/AWS/%s_infosite.txt', $p[0], $p[1], $p[2], $id, $id);
        $info = @file_get_contents($url);
        return !empty($info) ? explode(';', $info)[0] : '';
    }

    public function hookActionProductSearchAfter(array &$params)
    {
        if (empty($params['products'])) {
            return;
        }

        $productIds = array_values(array_filter(array_map(function ($p) {
            return (int)($p['id_product'] ?? 0);
        }, $params['products'])));

        if (empty($productIds)) {
            return;
        }

        // Une seule requête pour tous les produits de la liste
        $rows = Db::getInstance()->executeS(
            'SELECT ref_product, AVG(rate) AS avg_rate, COUNT(*) AS total_rate
            FROM ' . _DB_PREFIX_ . 'av_products_reviews
            WHERE ref_product IN (' . implode(',', $productIds) . ')
            GROUP BY ref_product'
        );

        $ratingsMap = [];
        foreach ($rows as $row) {
            $ratingsMap[(int)$row['ref_product']] = [
                'avg_rate'   => (float)round($row['avg_rate'], 2),
                'total_rate' => (int)$row['total_rate'],
            ];
        }

        foreach ($params['products'] as &$product) {
            $idProduct = (int)($product['id_product'] ?? 0);
            $rating = $ratingsMap[$idProduct] ?? null;
            $product['avg_rate']   = $rating ? $rating['avg_rate'] : 0;
            $product['total_rate'] = $rating ? $rating['total_rate'] : 0;
        }
    }
}
