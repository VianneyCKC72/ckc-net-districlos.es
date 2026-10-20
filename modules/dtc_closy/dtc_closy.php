<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/DtcProductQuestionsService.php';
require_once __DIR__ . '/classes/DtcClosyConfig.php';
require_once __DIR__ . '/classes/DtcClosyPanelService.php';
require_once __DIR__ . '/classes/DtcClosySchedule.php';
require_once __DIR__ . '/classes/DtcClosyChatSecurity.php';

class Dtc_closy extends Module
{
    const CONF_ACTIVE = 'DTC_CLOSY_ACTIVE';
    const CONF_API_KEY = 'DTC_CLOSY_OPENAI_API_KEY';
    const CONF_MODEL = 'DTC_CLOSY_OPENAI_MODEL';
    const CONF_HEADER_LABEL = 'DTC_CLOSY_HEADER_LABEL';
    const CONF_STATIC_TRANSPORT = 'DTC_CLOSY_STATIC_TRANSPORT';
    const CONF_CTA_LABEL = 'DTC_CLOSY_CTA_LABEL';
    const CONF_BATCH_SIZE = 'DTC_CLOSY_BATCH_SIZE';
    const CONF_SHOW_FALLBACK = 'DTC_CLOSY_SHOW_FALLBACK';
    const CONF_PANEL_ACTIVE = 'DTC_CLOSY_PANEL_ACTIVE';
    const CONF_YOURGPT_API_TOKEN = 'DTC_CLOSY_YOURGPT_API_TOKEN';
    const CONF_YOURGPT_PROJECT_UID = 'DTC_CLOSY_YOURGPT_PROJECT_UID';
    const CONF_YOURGPT_WIDGET_UID = 'DTC_CLOSY_YOURGPT_WIDGET_UID';
    const CONF_PANEL_TITLE = 'DTC_CLOSY_PANEL_TITLE';
    const CONF_PANEL_WELCOME = 'DTC_CLOSY_PANEL_WELCOME';
    const CONF_PANEL_WELCOME_GLOBAL = 'DTC_CLOSY_PANEL_WELCOME_GLOBAL';
    const CONF_SCHEDULE_ENABLED = 'DTC_CLOSY_SCHEDULE_ENABLED';
    const CONF_SCHEDULE_HOUR_START = 'DTC_CLOSY_SCHEDULE_HOUR_START';
    const CONF_SCHEDULE_HOUR_END = 'DTC_CLOSY_SCHEDULE_HOUR_END';
    const CONF_SCHEDULE_ACTIVE_DAYS_SELECTED = 'DTC_CLOSY_SCHEDULE_ACTIVE_DAYS_SELECTED';

    public function __construct()
    {
        $this->name = 'dtc_closy';
        $this->tab = 'front_office_features';
        $this->version = '1.1.2';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Districlos - Closy');
        $this->description = $this->l('Assistant Closy : questions IA sur fiche produit et panneau YourGPT avec carrousel produits\.');

        $this->ps_versions_compliancy = ['min' => '1.7.5.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        Configuration::updateValue(self::CONF_ACTIVE, 1);
        Configuration::updateValue(self::CONF_API_KEY, '');
        Configuration::updateValue(self::CONF_MODEL, DtcProductQuestionsService::DEFAULT_MODEL);
        DtcClosyConfig::seedTranslatableDefaults();
        Configuration::updateValue(self::CONF_BATCH_SIZE, 50);
        Configuration::updateValue(self::CONF_SHOW_FALLBACK, 1);
        Configuration::updateValue(self::CONF_PANEL_ACTIVE, 0);
        Configuration::updateValue(self::CONF_YOURGPT_API_TOKEN, '');
        Configuration::updateValue(self::CONF_YOURGPT_PROJECT_UID, '');
        Configuration::updateValue(self::CONF_YOURGPT_WIDGET_UID, '');
        Configuration::updateValue(self::CONF_SCHEDULE_ENABLED, 1);
        Configuration::updateValue(self::CONF_SCHEDULE_HOUR_START, 9);
        Configuration::updateValue(self::CONF_SCHEDULE_HOUR_END, 19);
        Configuration::updateValue(self::CONF_SCHEDULE_ACTIVE_DAYS_SELECTED, '1,2,3,4,5');

        DtcClosyConfig::runMigrations();

        return parent::install()
            && $this->installSql()
            && $this->registerHook('displayProductBelowGallery')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('actionProductUpdate')
            && $this->repairModuleGroupRestrictions();
    }

    /**
     * Avec PS_GROUP_FEATURE_ACTIVE, PrestaShop filtre les modules sur ps_module_group.
     * Sans lignes pour ce module, Hook::exec renvoie une chaîne vide (aucun hook exécuté).
     */
    public function repairModuleGroupRestrictions(): bool
    {
        if (!class_exists('Group') || !Group::isFeatureActive()) {
            return true;
        }

        $idModule = (int) $this->id;
        if ($idModule <= 0) {
            return false;
        }

        Group::truncateRestrictionsByModule($idModule);

        $shops = Shop::getCompleteListOfShopsID();
        if (empty($shops)) {
            $shops = [(int) Configuration::get('PS_SHOP_DEFAULT')];
        }

        $ok = (bool) Group::addRestrictionsForModule($idModule, $shops);

        if ($ok && class_exists('Hook')) {
            Cache::clean(Hook::MODULE_LIST_BY_HOOK_KEY . '*');
        }

        return $ok;
    }

    public function uninstall()
    {
        /* Désenregistrement PrestaShop d’abord (hooks, ligne ps_module), puis nettoyage BDD / config.
         * Évite les états incohérents si le DROP échoue avant le parent. */
        if (!parent::uninstall()) {
            return false;
        }

        DtcClosyConfig::deleteAll();

        return $this->uninstallSql();
    }

    private function installSql()
    {
        $file = __DIR__ . '/sql/install.php';
        if (!is_file($file)) {
            return true;
        }
        $ok = (bool) include $file;

        return $ok;
    }

    private function uninstallSql()
    {
        $file = __DIR__ . '/sql/uninstall.php';
        if (!is_file($file)) {
            return true;
        }
        $ok = (bool) include $file;

        return $ok;
    }

    public function hookDisplayHeader()
    {
        if ($this->isClosyExcludedPage()) {
            return;
        }

        $controller = $this->context->controller;
        $isProductPage = $controller && isset($controller->php_self) && $controller->php_self === 'product';
        $isTestMode = (string) Tools::getValue('dtc_closy_test') === '1';
        $panelEnabled = $this->isClosyPanelVisible();

        if ($isProductPage && ((int) Configuration::get(self::CONF_ACTIVE) || $isTestMode)) {
            $this->context->controller->registerStylesheet(
                'dtc-closy-front',
                'modules/' . $this->name . '/views/css/front.css',
                ['media' => 'all', 'priority' => 200]
            );
            $this->context->controller->registerJavascript(
                'dtc-closy-front',
                'modules/' . $this->name . '/views/js/front.js',
                ['position' => 'bottom', 'priority' => 200]
            );
        }

        if (!$panelEnabled) {
            Media::addJsDef(['dtcClosy' => ['enabled' => false]]);

            return;
        }

        $this->context->controller->registerStylesheet(
            'dtc-closy-panel',
            'modules/' . $this->name . '/views/css/panel.css',
            ['media' => 'all', 'priority' => 201]
        );
        $this->context->controller->registerJavascript(
            'dtc-closy-panel',
            'modules/' . $this->name . '/views/js/panel.js',
            ['position' => 'bottom', 'priority' => 199]
        );

        Media::addJsDef([
            'dtcClosy' => [
                'enabled' => true,
                'chatUrl' => $this->context->link->getModuleLink($this->name, 'chat', [], true),
                'chatToken' => DtcClosyChatSecurity::ensureToken(),
                'widgetUid' => (new DtcClosyPanelService())->resolveWidgetUid(),
                'socketUrl' => 'https://wss.yourgpt.ai/chatbot',
                'welcome' => $this->resolvePanelWelcomeMessage($isProductPage),
                'typingLabel' => $this->trans('Réflexion en cours…', [], 'Shop.Theme.Customdtc'),
                'likeLabel' => $this->trans('Utile', [], 'Shop.Theme.Customdtc'),
                'dislikeLabel' => $this->trans('Pas utile', [], 'Shop.Theme.Customdtc'),
                'feedbackThanksLabel' => $this->trans('Merci pour votre avis', [], 'Shop.Theme.Customdtc'),
                'feedbackGroupLabel' => $this->trans('Évaluez cette réponse', [], 'Shop.Theme.Customdtc'),
                'viewProductLabel' => $this->trans('Voir le produit', [], 'Shop.Theme.Customdtc'),
                'launcherLabel' => $this->trans('Ouvrir l’assistant Closy', [], 'Shop.Theme.Customdtc'),
                'botAvatarUrl' => $this->context->link->getMediaLink(
                    _MODULE_DIR_ . $this->name . '/views/img/icon_bulle_closy.webp'
                ),
            ],
        ]);
    }

    public function hookDisplayBeforeBodyClosingTag()
    {
        if ($this->isClosyExcludedPage() || !$this->isClosyPanelVisible()) {
            return '';
        }

        $isProductPage = $this->isProductPageContext();
        $idProduct = $isProductPage ? (int) Tools::getValue('id_product') : 0;
        $idLang = (int) $this->context->language->id;
        $panelProduct = null;
        if ($idProduct > 0) {
            $panelProduct = $this->buildPanelProductCard($idProduct, $idLang);
        }

        $this->context->smarty->assign([
            'dtc_closy_panel_title' => DtcClosyConfig::getLangValue(
                self::CONF_PANEL_TITLE,
                (int) $this->context->language->id
            ),
            'dtc_closy_panel_logo_url' => $this->context->link->getMediaLink(
                _MODULE_DIR_ . $this->name . '/views/img/logo.svg'
            ),
            'dtc_closy_panel_id_product' => $idProduct,
            'dtc_closy_panel_product' => $panelProduct,
            'dtc_closy_launcher_icon_url' => $this->context->link->getMediaLink(
                _MODULE_DIR_ . $this->name . '/views/img/closy_entier.webp'
            ),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/shopping_panel.tpl');
    }

    /**
     * Pages sans intérêt pour l’assistant : panier, tunnel, compte client.
     */
    private function isClosyExcludedPage(): bool
    {
        $controller = $this->context->controller;
        if (!$controller || !isset($controller->php_self)) {
            return false;
        }

        return in_array($controller->php_self, [
            'cart',
            'order',
            'checkout',
            'authentication',
            'password',
            'address',
            'addresses',
            'identity',
            'history',
            'order-follow',
            'guest-tracking',
            'order-confirmation',
        ], true);
    }

    private function isProductPageContext(): bool
    {
        $controller = $this->context->controller;

        return $controller && isset($controller->php_self) && $controller->php_self === 'product';
    }

    private function isClosyPanelVisible(): bool
    {
        return DtcClosyPanelService::isConfigured() && DtcClosySchedule::isVisibleNow();
    }

    private function resolvePanelWelcomeMessage(bool $isProductPage): string
    {
        $idLang = (int) $this->context->language->id;
        if ($isProductPage) {
            return DtcClosyConfig::getLangValue(self::CONF_PANEL_WELCOME, $idLang);
        }

        $global = trim(DtcClosyConfig::getLangValue(self::CONF_PANEL_WELCOME_GLOBAL, $idLang));

        return $global !== '' ? $global : DtcClosyConfig::getLangValue(self::CONF_PANEL_WELCOME, $idLang);
    }

    public function hookDisplayProductBelowGallery($params)
    {
        $testMode = (string) Tools::getValue('dtc_closy_test') === '1';

        if (!(int) Configuration::get(self::CONF_ACTIVE)) {
            if ($testMode) {
                return $this->renderTestBanner(
                    'danger',
                    'Module désactivé',
                    'DTC_CLOSY_ACTIVE = 0. Activez le module dans le BO ou désactivez le mode test.'
                );
            }

            return '';
        }

        $idProduct = $this->resolveProductIdFromHookParams($params);

        if ($idProduct <= 0) {
            if ($testMode) {
                $hint = $this->describeHookProductParam($params);

                return $this->renderTestBanner(
                    'warning',
                    'Hook exécuté mais id_product introuvable',
                    $hint
                );
            }

            return '';
        }

        $idLang = (int) $this->context->language->id;

        $service = new DtcProductQuestionsService();
        $rows = $service->getQuestions($idProduct, $idLang);

        $aiQuestions = [];
        foreach ($rows as $r) {
            $aiQuestions[] = $r['question'];
        }

        $staticTransport = DtcClosyConfig::getLangValue(self::CONF_STATIC_TRANSPORT, $idLang);
        $headerLabel = DtcClosyConfig::getLangValue(self::CONF_HEADER_LABEL, $idLang);
        $ctaLabel = DtcClosyConfig::getLangValue(self::CONF_CTA_LABEL, $idLang);
        $showFallback = (int) Configuration::get(self::CONF_SHOW_FALLBACK) === 1;

        if (count($aiQuestions) === 0 && !$showFallback) {
            if ($testMode) {
                return $this->renderTestBanner(
                    'warning',
                    'Bloc masqué (fallback désactivé)',
                    'Aucune question IA en cache et « Afficher même sans questions IA » = Non. Activez le fallback ou générez des questions.'
                );
            }

            return '';
        }

        $productForLink = new Product($idProduct, false, $idLang);
        $productUrl = '';
        $productName = '';
        if (Validate::isLoadedObject($productForLink)) {
            $productUrl = $this->context->link->getProductLink($productForLink);
            $productName = (string) $productForLink->name;
        }

        $this->context->smarty->assign([
            'dtc_closy_header_label' => $headerLabel,
            'dtc_closy_ai_questions' => $aiQuestions,
            'dtc_closy_static_transport' => $staticTransport,
            'dtc_closy_cta_label' => $ctaLabel,
            'dtc_closy_id_product' => $idProduct,
            'dtc_closy_product_url' => $productUrl,
            'dtc_closy_product_name' => $productName,
            'dtc_closy_panel_enabled' => $this->isClosyPanelVisible(),
        ]);

        $html = $this->display(__FILE__, 'views/templates/hook/product_questions.tpl');

        if ($testMode) {
            $html = $this->renderTestBanner(
                'success',
                'Test OK — hook displayProductBelowGallery',
                'id_product=' . (int) $idProduct
                    . ' · questions IA=' . count($aiQuestions)
                    . ' · fallback=' . ($showFallback ? 'oui' : 'non')
                    . ' · Retirez ?dtc_closy_test=1 de l’URL pour masquer ce bandeau.'
            ) . $html;
        }

        return $html;
    }

    /**
     * Données pour la carte produit fixe du panneau (image, prix, lien).
     *
     * @return array<string, mixed>|null
     */
    private function buildPanelProductCard(int $idProduct, int $idLang): ?array
    {
        if ($idProduct <= 0) {
            return null;
        }

        $product = new Product($idProduct, false, $idLang);
        if (!Validate::isLoadedObject($product)) {
            return null;
        }

        $idProductAttribute = (int) Tools::getValue('id_product_attribute');
        $priceTaxIncl = Product::getPriceStatic(
            $idProduct,
            true,
            $idProductAttribute > 0 ? $idProductAttribute : null,
            6,
            null,
            false,
            true,
            1
        );

        $imageUrl = '';
        $cover = Product::getCover($idProduct);
        if (!empty($cover['id_image'])) {
            $imageUrl = $this->context->link->getImageLink(
                $product->link_rewrite,
                (int) $cover['id_image'],
                ImageType::getFormattedName('home')
            );
        }

        $minimalQty = max(1, (int) $product->minimal_quantity);

        return [
            'id_product' => $idProduct,
            'id_product_attribute' => $idProductAttribute,
            'name' => (string) $product->name,
            'url' => $this->context->link->getProductLink($product),
            'image_url' => $imageUrl,
            'price' => Tools::displayPrice($priceTaxIncl),
            'minimal_quantity' => $minimalQty,
            'can_add_to_cart' => false,
        ];
    }

    /**
     * Résout l’id produit depuis les paramètres du hook (array, ProductLazyArray, Product…).
     */
    private function resolveProductIdFromHookParams(array $params): int
    {
        $idProduct = 0;
        if (isset($params['product'])) {
            $p = $params['product'];
            if (is_array($p)) {
                $idProduct = (int) ($p['id_product'] ?? ($p['id'] ?? 0));
            } elseif (is_object($p)) {
                if ($p instanceof Product) {
                    $idProduct = (int) $p->id;
                } else {
                    $idProduct = (int) ($p->id_product ?? ($p->id ?? 0));
                    if ($idProduct <= 0 && $p instanceof \ArrayAccess && isset($p['id_product'])) {
                        $idProduct = (int) $p['id_product'];
                    }
                    if ($idProduct <= 0 && $p instanceof \ArrayAccess && isset($p['id'])) {
                        $idProduct = (int) $p['id'];
                    }
                }
            }
        }
        if ($idProduct <= 0) {
            $idProduct = (int) Tools::getValue('id_product');
        }

        return $idProduct;
    }

    /**
     * Texte d’aide pour le mode test quand id_product est introuvable.
     */
    private function describeHookProductParam(array $params): string
    {
        $parts = [];
        $parts[] = 'Clés reçues dans $params : ' . implode(', ', array_keys($params));
        if (isset($params['product'])) {
            $p = $params['product'];
            $parts[] = 'Type de product : ' . (is_object($p) ? get_class($p) : gettype($p));
        } else {
            $parts[] = 'Aucune clé « product » dans les paramètres du hook.';
        }

        return implode(' — ', $parts);
    }

    /**
     * Bandeau de diagnostic (visible uniquement avec ?dtc_closy_test=1).
     */
    private function renderTestBanner(string $type, string $title, string $message): string
    {
        $type = in_array($type, ['success', 'warning', 'danger', 'info'], true) ? $type : 'info';

        return '<div class="alert alert-' . $type . ' dtc-closy-debug" role="alert" style="margin:10px 0;font-size:14px;">'
            . '<strong>[Dtc_closy]</strong> ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
            . '<br><span class="small">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</div>';
    }

    /**
     * Si la fiche est modifiée, on supprime les questions pour forcer la régénération au prochain cron.
     */
    public function hookActionProductUpdate($params)
    {
        $idProduct = (int) ($params['id_product'] ?? ($params['product']->id ?? 0));
        if ($idProduct <= 0) {
            return;
        }
        Db::getInstance()->delete(
            DtcProductQuestionsService::TABLE_QUESTIONS,
            '`id_product` = ' . (int) $idProduct
        );
    }

    public function getContent()
    {
        $output = '';
        $this->ensurePanelConfigDefaults();
        DtcClosyConfig::migrateToMultilang();

        if ($this->context->controller instanceof AdminController) {
            $this->context->controller->addJS($this->_path . 'views/js/admin_configure.js');
        }

        $isSaveSubmit = Tools::isSubmit('submitDtcClosy');

        if ($isSaveSubmit) {
            Configuration::updateValue(self::CONF_ACTIVE, (int) Tools::getValue(self::CONF_ACTIVE));
            Configuration::updateValue(self::CONF_API_KEY, trim((string) Tools::getValue(self::CONF_API_KEY)));
            Configuration::updateValue(self::CONF_MODEL, trim((string) Tools::getValue(self::CONF_MODEL)));
            foreach (array_keys(DtcClosyConfig::getTranslatableConfigKeys()) as $langKey) {
                Configuration::updateValue($langKey, DtcClosyConfig::collectLangPost($langKey), true);
            }
            Configuration::updateValue(self::CONF_BATCH_SIZE, max(1, (int) Tools::getValue(self::CONF_BATCH_SIZE)));
            Configuration::updateValue(self::CONF_SHOW_FALLBACK, (int) Tools::getValue(self::CONF_SHOW_FALLBACK));
            Configuration::updateValue(self::CONF_PANEL_ACTIVE, (int) Tools::getValue(self::CONF_PANEL_ACTIVE));
            Configuration::updateValue(self::CONF_YOURGPT_API_TOKEN, trim((string) Tools::getValue(self::CONF_YOURGPT_API_TOKEN)));
            Configuration::updateValue(self::CONF_YOURGPT_WIDGET_UID, trim((string) Tools::getValue(self::CONF_YOURGPT_WIDGET_UID)));
            Configuration::updateValue(self::CONF_SCHEDULE_ENABLED, (int) Tools::getValue(self::CONF_SCHEDULE_ENABLED));
            $scheduleDays = $this->collectScheduleDaysFromPost();
            Configuration::updateValue(self::CONF_SCHEDULE_ACTIVE_DAYS_SELECTED, $scheduleDays);
            DtcClosyConfig::update('SCHEDULE_ACTIVE_DAYS_SELECTED', $scheduleDays);

            $hourStart = (int) Tools::getValue(self::CONF_SCHEDULE_HOUR_START);
            $hourEnd = (int) Tools::getValue(self::CONF_SCHEDULE_HOUR_END);
            $hoursOk = $hourStart >= 0 && $hourStart <= 24 && $hourEnd >= 0 && $hourEnd <= 24 && $hourEnd > $hourStart;
            if ($hoursOk) {
                Configuration::updateValue(self::CONF_SCHEDULE_HOUR_START, $hourStart);
                Configuration::updateValue(self::CONF_SCHEDULE_HOUR_END, $hourEnd);
            }

            $output .= $this->displayConfirmation($this->l('Configuration enregistrée.'));
            if (!$hoursOk) {
                $output .= $this->displayError($this->l('Plage horaire invalide (heure de fin > heure de début, valeurs 0–24). Les jours ont bien été enregistrés.'));
            }
        }

        if (Tools::isSubmit('submitDtcRunBatch')) {
            $service = new DtcProductQuestionsService();
            $report = $service->generateBatch((int) Configuration::get(self::CONF_BATCH_SIZE));
            $output .= $this->displayConfirmation(
                $this->l('Batch terminé : ')
                . 'processed=' . (int) $report['processed']
                . ', success=' . (int) ($report['success'] ?? 0)
                . ', skipped=' . (int) ($report['skipped'] ?? 0)
                . ', error=' . (int) ($report['error'] ?? 0)
            );
        }

        if (Tools::isSubmit('submitDtcTestProduct')) {
            $idProduct = (int) Tools::getValue('dtc_test_id_product');
            if ($idProduct > 0) {
                $service = new DtcProductQuestionsService();
                $result = $service->generateForProduct($idProduct, (int) $this->context->language->id, true);
                $output .= $this->displayConfirmation(
                    $this->l('Test produit ') . $idProduct . ' : ' . json_encode($result, JSON_UNESCAPED_UNICODE)
                );
            } else {
                $output .= $this->displayError($this->l('id_product invalide.'));
            }
        }

        if (Tools::isSubmit('submitDtcRepairModuleGroups')) {
            if ($this->repairModuleGroupRestrictions()) {
                $output .= $this->displayConfirmation(
                    $this->l('Restrictions groupe client (table ps_module_group) recréées pour ce module. Videz le cache PrestaShop / Smarty si le front ne se met pas à jour tout de suite.')
                );
            } else {
                $output .= $this->displayError($this->l('La réparation des restrictions groupe a échoué (voir les logs / id_module).'));
            }
        }

        $stats = $this->getStats();
        $selectedDays = DtcClosySchedule::getSelectedDayIds();
        $scheduleDays = DtcClosySchedule::getWeekDayOptions();
        foreach ($scheduleDays as &$scheduleDay) {
            $scheduleDay['checked'] = in_array((int) $scheduleDay['id'], $selectedDays, true);
        }
        unset($scheduleDay);

        $scheduleDayFlags = [];
        for ($d = 1; $d <= 7; ++$d) {
            $scheduleDayFlags[$d] = in_array($d, $selectedDays, true);
        }

        $this->context->smarty->assign([
            'dtc_closy_active' => (int) Configuration::get(self::CONF_ACTIVE),
            'dtc_closy_api_key' => (string) Configuration::get(self::CONF_API_KEY),
            'dtc_closy_model' => (string) Configuration::get(self::CONF_MODEL),
            'dtc_closy_header_label' => DtcClosyConfig::getLangValues(self::CONF_HEADER_LABEL),
            'dtc_closy_static_transport' => DtcClosyConfig::getLangValues(self::CONF_STATIC_TRANSPORT),
            'dtc_closy_cta_label' => DtcClosyConfig::getLangValues(self::CONF_CTA_LABEL),
            'dtc_closy_batch_size' => (int) Configuration::get(self::CONF_BATCH_SIZE),
            'dtc_closy_show_fallback' => (int) Configuration::get(self::CONF_SHOW_FALLBACK),
            'dtc_closy_panel_active' => (int) Configuration::get(self::CONF_PANEL_ACTIVE),
            'dtc_closy_yourgpt_api_token' => (string) Configuration::get(self::CONF_YOURGPT_API_TOKEN),
            'dtc_closy_yourgpt_widget_uid' => (string) Configuration::get(self::CONF_YOURGPT_WIDGET_UID),
            'dtc_closy_panel_title' => DtcClosyConfig::getLangValues(self::CONF_PANEL_TITLE),
            'dtc_closy_panel_welcome' => DtcClosyConfig::getLangValues(self::CONF_PANEL_WELCOME),
            'dtc_closy_panel_welcome_global' => DtcClosyConfig::getLangValues(self::CONF_PANEL_WELCOME_GLOBAL),
            'dtc_closy_languages' => $this->context->controller instanceof AdminController
                ? $this->context->controller->getLanguages()
                : Language::getLanguages(false),
            'dtc_closy_id_lang' => (int) $this->context->language->id,
            'dtc_closy_schedule_enabled' => (int) Configuration::get(self::CONF_SCHEDULE_ENABLED),
            'dtc_closy_schedule_hour_start' => (int) Configuration::get(self::CONF_SCHEDULE_HOUR_START),
            'dtc_closy_schedule_hour_end' => (int) Configuration::get(self::CONF_SCHEDULE_HOUR_END),
            'dtc_closy_schedule_days' => $scheduleDays,
            'dtc_closy_schedule_days_saved' => (string) DtcClosyConfig::get('SCHEDULE_ACTIVE_DAYS_SELECTED', ''),
            'dtc_closy_schedule_day_1' => $scheduleDayFlags[1],
            'dtc_closy_schedule_day_2' => $scheduleDayFlags[2],
            'dtc_closy_schedule_day_3' => $scheduleDayFlags[3],
            'dtc_closy_schedule_day_4' => $scheduleDayFlags[4],
            'dtc_closy_schedule_day_5' => $scheduleDayFlags[5],
            'dtc_closy_schedule_day_6' => $scheduleDayFlags[6],
            'dtc_closy_schedule_day_7' => $scheduleDayFlags[7],
            'dtc_closy_admin_token' => Tools::getAdminTokenLite('AdminModules'),
            'dtc_closy_form_action' => $this->context->link->getAdminLink('AdminModules', true, [], [
                'configure' => $this->name,
                'tab_module' => $this->tab,
                'module_name' => $this->name,
            ]),
            'dtc_closy_panel_ready' => DtcClosyPanelService::isConfigured(),
            'dtc_closy_panel_visible_now' => $this->isClosyPanelVisible(),
            'dtc_closy_stats' => $stats,
            'dtc_closy_module_name' => $this->name,
        ]);

        return $output . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    /**
     * Valeurs par défaut + hook panneau pour les mises à jour sans réinstall.
     */
    private function ensurePanelConfigDefaults(): void
    {
        if (!$this->isRegisteredInHook('displayBeforeBodyClosingTag')) {
            $this->registerHook('displayBeforeBodyClosingTag');
        }

        $defaults = [
            self::CONF_PANEL_ACTIVE => 0,
            self::CONF_YOURGPT_API_TOKEN => '',
            self::CONF_YOURGPT_PROJECT_UID => '',
            self::CONF_YOURGPT_WIDGET_UID => '',
            self::CONF_SCHEDULE_ENABLED => 1,
            self::CONF_SCHEDULE_HOUR_START => 9,
            self::CONF_SCHEDULE_HOUR_END => 19,
            self::CONF_SCHEDULE_ACTIVE_DAYS_SELECTED => '1,2,3,4,5',
        ];

        foreach ($defaults as $key => $value) {
            if (!Configuration::hasKey($key)) {
                Configuration::updateValue($key, $value);
            }
        }

        DtcClosyConfig::runMigrations();
    }

    private function collectScheduleDaysFromPost(): string
    {
        $hidden = '';
        if (isset($_POST['DTC_CLOSY_SCHEDULE_ACTIVE_DAYS_SELECTED'])) {
            $hidden = trim((string) $_POST['DTC_CLOSY_SCHEDULE_ACTIVE_DAYS_SELECTED']);
        } else {
            $hidden = trim((string) Tools::getValue('DTC_CLOSY_SCHEDULE_ACTIVE_DAYS_SELECTED'));
        }

        $checkboxDays = [];
        for ($i = 1; $i <= 7; ++$i) {
            $key = 'DTC_CLOSY_SCHEDULE_DAY_' . $i;
            if (!empty($_POST[$key]) || (Tools::getIsset($key) && (string) Tools::getValue($key) !== '0')) {
                $checkboxDays[] = $i;
            }
        }

        $result = $hidden !== ''
            ? $this->normalizeScheduleDaysString($hidden)
            : $this->normalizeScheduleDaysString(implode(',', $checkboxDays));

        return $result;
    }

    private function normalizeScheduleDaysString(string $raw): string
    {
        $days = [];
        foreach (explode(',', $raw) as $part) {
            $id = (int) trim($part);
            if ($id >= 1 && $id <= 7) {
                $days[] = $id;
            }
        }
        $days = array_values(array_unique($days));
        sort($days);

        return implode(',', $days);
    }

    private function getStats(): array
    {
        $idLang = (int) $this->context->language->id;
        $totalProducts = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product` WHERE active = 1'
        );
        $productsWithQuestions = (int) Db::getInstance()->getValue(
            'SELECT COUNT(DISTINCT id_product) FROM `' . _DB_PREFIX_ . DtcProductQuestionsService::TABLE_QUESTIONS . '`
             WHERE id_lang = ' . (int) $idLang
        );
        $totalQuestions = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . DtcProductQuestionsService::TABLE_QUESTIONS . '`'
        );
        $lastLog = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . DtcProductQuestionsService::TABLE_LOG . '`
             ORDER BY id_log DESC LIMIT 10'
        );

        return [
            'total_products' => $totalProducts,
            'products_with_questions' => $productsWithQuestions,
            'total_questions' => $totalQuestions,
            'last_logs' => is_array($lastLog) ? $lastLog : [],
        ];
    }
}
