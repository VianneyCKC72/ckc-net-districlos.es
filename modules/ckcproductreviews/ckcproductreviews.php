<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

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
        $this->version = '1.2.0';
        $this->bootstrap = true;
        $this->tab = 'front_office_features';
        $this->need_instance = true;
        $this->multishop_context = true;
        $this->logo_path = $this->_path . 'logo.png';

        parent::__construct();
        $this->_path_assets = $this->_path . 'views/assets/';

        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
        $this->displayName = $this->l('CKC Product Reviews');
        $this->description = $this->l('Affiche un carrousel personnalisé des avis clients.');
    }

    public function install()
    {
        return parent::install()
            && $this->installSql()
            && Configuration::updateValue(self::CONFIG_MIN_RATING, 4)
            && Configuration::updateValue('CKC_REVIEWS_ENABLE_CAROUSEL', 1)
            && $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallSql()
            && Configuration::deleteByName('CKC_REVIEWS_ENABLE_CAROUSEL')
            && Configuration::deleteByName(self::CONFIG_MIN_RATING);
    }

    public function getContent()
    {
        $this->installSql();
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

        return $output . $this->renderForm($minRating, $enableCarousel);
    }

    /** Crée les tables de stockage / traduction des avis marque (homepage). */
    public function installSql()
    {
        $engine = _MYSQL_ENGINE_;
        $ok = Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckc_brand_review` (
                `id_ckc_brand_review` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `review_key` VARCHAR(64) NOT NULL,
                `order_reference` VARCHAR(64) NOT NULL DEFAULT \'\',
                `rating` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `customer_name` VARCHAR(255) NOT NULL DEFAULT \'\',
                `publish_date` DATETIME NULL,
                `order_date` DATETIME NULL,
                `review_content` TEXT NOT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_ckc_brand_review`),
                UNIQUE KEY `review_key` (`review_key`),
                KEY `rating_publish` (`rating`, `publish_date`)
            ) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;'
        );

        $ok = $ok && Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckc_brand_review_lang` (
                `id_ckc_brand_review` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `review_content` TEXT NOT NULL,
                PRIMARY KEY (`id_ckc_brand_review`, `id_lang`)
            ) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;'
        );

        return (bool) $ok;
    }

    protected function uninstallSql()
    {
        return (bool) Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ckc_brand_review_lang`')
            && (bool) Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ckc_brand_review`');
    }

    private function renderForm($minRating, $enableCarousel)
    {
        $form = new HelperForm();

        $form->module = $this;
        $form->name_controller = $this->name;
        $form->token = Tools::getAdminTokenLite('AdminModules');
        $form->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $form->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
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
                            'name' => 'name',
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
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Sauvegarder'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $form->fields_value = [
            self::CONFIG_MIN_RATING => $minRating,
            'CKC_REVIEWS_ENABLE_CAROUSEL' => $enableCarousel,
        ];

        return $form->generateForm([$fields_form]);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!(int) Configuration::get('CKC_REVIEWS_ENABLE_CAROUSEL')) {
            return '';
        }

        $minRating = (int) Configuration::get(self::CONFIG_MIN_RATING);

        $sql = 'SELECT customer_name, rating, review, date_add
            FROM ' . _DB_PREFIX_ . 'av_products_reviews
            WHERE active = 1
              AND rating >= ' . (int) $minRating . '
            ORDER BY date_add DESC
            LIMIT 10';

        $reviews = Db::getInstance()->executeS($sql);

        $this->context->controller->addCSS($this->_path_assets . 'ckcproductreviews.css');
        $this->context->controller->addJS($this->_path_assets . 'ckcproductreviews.js');

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
        $this->installSql();

        $enableCarousel = (int) Configuration::get('CKC_REVIEWS_ENABLE_CAROUSEL');

        $minRating = (int) Configuration::get(self::CONFIG_MIN_RATING);
        if ($minRating < 1) {
            $minRating = 4;
        }

        $allReviews = $this->getBrandReviewsFromApi();
        $this->syncBrandReviews($allReviews);

        $idLang = (int) $this->context->language->id;
        $reviews = $this->getHomepageReviewsForDisplay($idLang, $minRating, 10);

        $nb_reviews_12_months = $this->getSiteReviewCount();

        $this->context->controller->addCSS($this->_path_assets . 'ckcproductreviews.css');
        $this->context->controller->addJS($this->_path_assets . 'ckcproductreviews.js');
        $this->context->smarty->assign([
            'reviews' => $reviews,
            'enable_carousel' => $enableCarousel,
            'nb_reviews_12_months' => $nb_reviews_12_months,
            'module_dir' => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/displayProductReviews.tpl');
    }

    /**
     * Synchronise le cache fichier API vers la table (nouveaux avis = lignes sans trad).
     * Utilisé aussi par CKC Translate pour alimenter le job.
     */
    public function syncFromCacheFile()
    {
        $this->installSql();
        $cacheFile = _PS_MODULE_DIR_ . $this->name . '/cache/reviews_homepage.json';
        if (!file_exists($cacheFile)) {
            return 0;
        }
        $data = json_decode((string) file_get_contents($cacheFile), true);
        if (!is_array($data)) {
            return 0;
        }

        return $this->syncBrandReviews($data);
    }

    /** Upsert des avis API ; invalide les trads si le texte source change. */
    public function syncBrandReviews(array $apiReviews)
    {
        $this->installSql();
        $now = date('Y-m-d H:i:s');
        $synced = 0;

        foreach ($apiReviews as $r) {
            if (!is_array($r)) {
                continue;
            }
            $content = trim((string) ($r['review_content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $key = $this->buildReviewKey($r);
            $firstname = (string) ($r['author_firstname'] ?? '');
            $lastname = (string) ($r['author_lastname'] ?? '');
            $customerName = trim($firstname . ' ' . mb_substr($lastname, 0, 1) . '.');
            $publishDate = $this->normalizeDate($r['publish_date'] ?? null);
            $orderDate = $this->normalizeDate($r['order_date'] ?? null);
            $orderRef = (string) ($r['order_reference'] ?? '');
            $rating = (int) ($r['review_rate'] ?? 0);

            $existing = Db::getInstance()->getRow(
                'SELECT `id_ckc_brand_review`, `review_content`
                 FROM `' . _DB_PREFIX_ . 'ckc_brand_review`
                 WHERE `review_key` = \'' . pSQL($key) . '\''
            );

            if ($existing) {
                $id = (int) $existing['id_ckc_brand_review'];
                if ((string) $existing['review_content'] !== $content) {
                    Db::getInstance()->update(
                        'ckc_brand_review',
                        [
                            'order_reference' => pSQL($orderRef),
                            'rating' => $rating,
                            'customer_name' => pSQL($customerName),
                            'publish_date' => $publishDate ? pSQL($publishDate) : null,
                            'order_date' => $orderDate ? pSQL($orderDate) : null,
                            'review_content' => pSQL($content, true),
                            'date_upd' => pSQL($now),
                        ],
                        'id_ckc_brand_review = ' . $id
                    );
                    Db::getInstance()->delete('ckc_brand_review_lang', 'id_ckc_brand_review = ' . $id);
                }
            } else {
                Db::getInstance()->insert('ckc_brand_review', [
                    'review_key' => pSQL($key),
                    'order_reference' => pSQL($orderRef),
                    'rating' => $rating,
                    'customer_name' => pSQL($customerName),
                    'publish_date' => $publishDate ? pSQL($publishDate) : null,
                    'order_date' => $orderDate ? pSQL($orderDate) : null,
                    'review_content' => pSQL($content, true),
                    'date_add' => pSQL($now),
                    'date_upd' => pSQL($now),
                ]);
            }
            ++$synced;
        }

        return $synced;
    }

    /** Avis homepage prêts à afficher, avec texte traduit si disponible. */
    public function getHomepageReviewsForDisplay($idLang, $minRating, $limit = 10)
    {
        $idLang = (int) $idLang;
        $minRating = (int) $minRating;
        $limit = max(1, (int) $limit);
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');

        $rows = Db::getInstance()->executeS(
            'SELECT r.`id_ckc_brand_review`, r.`rating`, r.`customer_name`,
                    r.`publish_date`, r.`order_date`, r.`review_content` AS source_content,
                    rl.`review_content` AS translated_content
             FROM `' . _DB_PREFIX_ . 'ckc_brand_review` r
             LEFT JOIN `' . _DB_PREFIX_ . 'ckc_brand_review_lang` rl
               ON rl.`id_ckc_brand_review` = r.`id_ckc_brand_review`
              AND rl.`id_lang` = ' . $idLang . '
             WHERE r.`rating` >= ' . $minRating . '
             ORDER BY r.`publish_date` DESC, r.`id_ckc_brand_review` DESC
             LIMIT ' . $limit
        );

        if (!is_array($rows)) {
            return [];
        }

        $reviews = [];
        foreach ($rows as $row) {
            $useTranslated = ($idLang !== $idLangDefault)
                && !empty($row['translated_content']);
            $text = $useTranslated ? $row['translated_content'] : $row['source_content'];

            $reviews[] = [
                'rate' => (int) $row['rating'],
                'review' => urlencode((string) $text),
                'customer_name' => $row['customer_name'],
                'horodate' => $row['publish_date'],
                'horodate_order' => $row['order_date'],
                'date_experience' => $row['order_date'],
            ];
        }

        return $reviews;
    }

    protected function buildReviewKey(array $r)
    {
        $parts = [
            (string) ($r['id'] ?? ''),
            (string) ($r['review_id'] ?? ''),
            (string) ($r['order_reference'] ?? ''),
            (string) ($r['publish_date'] ?? ''),
            (string) ($r['author_firstname'] ?? ''),
            (string) ($r['author_lastname'] ?? ''),
        ];
        $raw = implode('|', $parts);
        if (trim(str_replace('|', '', $raw)) === '') {
            $raw = (string) ($r['review_content'] ?? '') . '|' . (string) ($r['review_rate'] ?? '');
        }

        return sha1($raw);
    }

    protected function normalizeDate($value)
    {
        if (empty($value)) {
            return null;
        }
        $ts = strtotime((string) $value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }

    /** Récupère les avis site/marque via la Published API Skeepers, avec cache fichier 6h. */
    private function getBrandReviewsFromApi()
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
    private function getOAuthToken()
    {
        $cached = Configuration::get('DTC_SKEEPERS_TOKEN');
        $ts = (int) Configuration::get('DTC_SKEEPERS_TOKEN_TIMESTAMP');
        if (!empty($cached) && $ts > 0 && (time() - $ts) < 240) {
            return $cached;
        }

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

    private function getSiteReviewCount()
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
            return (int) ($p['id_product'] ?? 0);
        }, $params['products'])));

        if (empty($productIds)) {
            return;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT ref_product, AVG(rate) AS avg_rate, COUNT(*) AS total_rate
            FROM ' . _DB_PREFIX_ . 'av_products_reviews
            WHERE ref_product IN (' . implode(',', $productIds) . ')
            GROUP BY ref_product'
        );

        $ratingsMap = [];
        foreach ($rows as $row) {
            $ratingsMap[(int) $row['ref_product']] = [
                'avg_rate' => (float) round($row['avg_rate'], 2),
                'total_rate' => (int) $row['total_rate'],
            ];
        }

        foreach ($params['products'] as &$product) {
            $idProduct = (int) ($product['id_product'] ?? 0);
            $rating = $ratingsMap[$idProduct] ?? null;
            $product['avg_rate'] = $rating ? $rating['avg_rate'] : 0;
            $product['total_rate'] = $rating ? $rating['total_rate'] : 0;
        }
    }
}
