<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/CkcTranslateAi.php';
require_once __DIR__ . '/classes/CkcTranslateJobRunner.php';
require_once __DIR__ . '/classes/CkcTranslateCfuI18n.php';

class CkcTranslate extends Module
{
    public const CONF_API_KEY = 'CKCTRANSLATE_API_KEY';
    public const CONF_SOURCE_LANG = 'CKCTRANSLATE_SOURCE_LANG';
    public const CONF_TARGET_LANG = 'CKCTRANSLATE_TARGET_LANG';
    public const CONF_MODEL = 'CKCTRANSLATE_MODEL';
    public const CONF_PROMPT = 'CKCTRANSLATE_PROMPT';
    public const CONF_TEMPERATURE = 'CKCTRANSLATE_TEMPERATURE';
    public const CONF_MAX_TOKENS = 'CKCTRANSLATE_MAX_TOKENS';
    public const CONF_ESTIMATED_CREDIT = 'CKCTRANSLATE_ESTIMATED_CREDIT';
    public const CONF_BATCH_SIZE = 'CKCTRANSLATE_BATCH_SIZE';
    public const CONF_TARGET_COUNTRY = 'CKCTRANSLATE_TARGET_COUNTRY';
    public const CONF_COUNTRY_KEYWORDS = 'CKCTRANSLATE_COUNTRY_KEYWORDS';
    public const CONF_STYLE_GUIDELINES = 'CKCTRANSLATE_STYLE_GUIDELINES';
    public const CONF_RESTRICTIONS = 'CKCTRANSLATE_RESTRICTIONS';
    public const CONF_ONLY_MISSING_FIELDS = 'CKCTRANSLATE_ONLY_MISSING_FIELDS';

    public const CONF_GLOSSARY = 'CKCTRANSLATE_GLOSSARY';
    public const CONF_SEO_META_TITLE_MAX = 'CKCTRANSLATE_SEO_META_TITLE_MAX';
    public const CONF_SEO_META_DESCRIPTION_MAX = 'CKCTRANSLATE_SEO_META_DESCRIPTION_MAX';
    public const CONF_SEO_SLUG_MAX = 'CKCTRANSLATE_SEO_SLUG_MAX';

    public const CONF_PROMPT_PRODUCT_SEO = 'CKCTRANSLATE_PROMPT_PRODUCT_SEO';
    public const CONF_PROMPT_PRODUCT_DESCRIPTION = 'CKCTRANSLATE_PROMPT_PRODUCT_DESCRIPTION';
    public const CONF_PROMPT_CATEGORY_SEO = 'CKCTRANSLATE_PROMPT_CATEGORY_SEO';
    public const CONF_PROMPT_CATEGORY_DESCRIPTION = 'CKCTRANSLATE_PROMPT_CATEGORY_DESCRIPTION';
    public const CONF_PROMPT_CMS = 'CKCTRANSLATE_PROMPT_CMS';
    public const CONF_PROMPT_CE = 'CKCTRANSLATE_PROMPT_CE';
    public const CONF_PROMPT_REASSURANCE = 'CKCTRANSLATE_PROMPT_REASSURANCE';
    public const CONF_PROMPT_BRAND_REVIEWS = 'CKCTRANSLATE_PROMPT_BRAND_REVIEWS';
    public const CONF_PROMPT_ORDER_STATES = 'CKCTRANSLATE_PROMPT_ORDER_STATES';
    public const CONF_PROMPT_EVERCONTENTSEO = 'CKCTRANSLATE_PROMPT_EVERCONTENTSEO';
    public const CONF_PROMPT_FEATURES = 'CKCTRANSLATE_PROMPT_FEATURES';
    public const CONF_PROMPT_ATTRIBUTES = 'CKCTRANSLATE_PROMPT_ATTRIBUTES';
    public const CONF_PROMPT_CARRIER_DELAYS = 'CKCTRANSLATE_PROMPT_CARRIER_DELAYS';
    public const CONF_PROMPT_CKC_LABELS = 'CKCTRANSLATE_PROMPT_CKC_LABELS';
    public const CONF_PROMPT_HELLOWORK_JOBS = 'CKCTRANSLATE_PROMPT_HELLOWORK_JOBS';
    public const CONF_PROMPT_ETS_CFU = 'CKCTRANSLATE_PROMPT_ETS_CFU';
    public const CONF_PROMPT_CKC_REALISATION = 'CKCTRANSLATE_PROMPT_CKC_REALISATION';
    public const CONF_PROMPT_RESPONSIVEGALLERY = 'CKCTRANSLATE_PROMPT_RESPONSIVEGALLERY';
    public const CONF_PROMPT_PRODUCT_FAQ = 'CKCTRANSLATE_PROMPT_PRODUCT_FAQ';
    public const CONF_PROMPT_PRODUCT_GUIDE = 'CKCTRANSLATE_PROMPT_PRODUCT_GUIDE';

    public const CONF_FILTER_TYPE = 'CKCTRANSLATE_FILTER_TYPE';
    public const CONF_FILTER_IDS = 'CKCTRANSLATE_FILTER_IDS';
    public const CONF_FILTER_CATEGORY_ID = 'CKCTRANSLATE_FILTER_CATEGORY_ID';

    public function __construct()
    {
        $this->name = 'ckctranslate';
        $this->tab = 'administration';
        $this->version = '2.16.3';
        $this->author = 'CKC';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('CKC Translate');
        $this->description = $this->l('AI translation assistant for products, categories and CMS.');
        $this->ps_versions_compliancy = array(
            'min' => '1.7.8.0',
            'max' => _PS_VERSION_,
        );
    }

    public function install()
    {
        return parent::install()
            && $this->installTab()
            && $this->installConfiguration()
            && $this->installSql()
            && $this->registerHook('actionOutputHTMLBefore')
            && $this->registerHook('actionFrontControllerInitAfter')
            && $this->registerHook('actionPresentProduct')
            && $this->registerHook('actionPresentProductListing');
    }

    public function uninstall()
    {
        return $this->uninstallTab()
            && $this->uninstallConfiguration()
            && $this->uninstallSql()
            && parent::uninstall();
    }

    public function getContent()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminCkcTranslate');

        if ($idTab <= 0) {
            $this->installTab();
            $idTab = (int) Tab::getIdFromClassName('AdminCkcTranslate');
        }

        $this->installSql();
        $this->registerHook('actionOutputHTMLBefore');
        $this->registerHook('actionFrontControllerInitAfter');
        $this->registerHook('actionPresentProduct');
        $this->registerHook('actionPresentProductListing');

        $html = '';
        $html .= '<div class="panel">';
        $html .= '<h3>' . $this->l('CKC Translate') . '</h3>';
        $html .= '<p>' . $this->l('The module is installed. Use the dedicated back-office controller to configure and launch translations.') . '</p>';

        if ($idTab > 0) {
            $adminLink = $this->context->link->getAdminLink('AdminCkcTranslate');
            $html .= '<p><a class="btn btn-primary" href="' . htmlspecialchars($adminLink, ENT_QUOTES, 'UTF-8') . '">';
            $html .= $this->l('Open CKC Translate');
            $html .= '</a></p>';
        } else {
            $html .= '<div class="alert alert-warning">';
            $html .= $this->l('The admin tab is not available yet. Please reinstall the module or clear the cache.');
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function installConfiguration()
    {
        return Configuration::updateValue(self::CONF_API_KEY, '')
            && Configuration::updateValue(self::CONF_SOURCE_LANG, (int) Configuration::get('PS_LANG_DEFAULT'))
            && Configuration::updateValue(self::CONF_TARGET_LANG, (int) Language::getIdByIso('en'))
            && Configuration::updateValue(self::CONF_MODEL, 'gpt-4.1-mini')
            && Configuration::updateValue(
                self::CONF_PROMPT,
                'Translate and adapt e-commerce content with a premium, natural and SEO-friendly tone. Respect HTML. Do not invent facts.'
            )
            && Configuration::updateValue(self::CONF_TEMPERATURE, '0.35')
            && Configuration::updateValue(self::CONF_MAX_TOKENS, '1200')
            && Configuration::updateValue(self::CONF_ESTIMATED_CREDIT, '0.0240')
            && Configuration::updateValue(self::CONF_BATCH_SIZE, 10)
            && Configuration::updateValue(self::CONF_TARGET_COUNTRY, 'US')
            && Configuration::updateValue(self::CONF_COUNTRY_KEYWORDS, 'fine jewelry, gold jewelry, sterling silver jewelry, engagement ring, gift for her')
            && Configuration::updateValue(self::CONF_STYLE_GUIDELINES, 'Elegant tone, natural English, premium e-commerce vocabulary, short sentences, benefit-driven copy.')
            && Configuration::updateValue(self::CONF_RESTRICTIONS, 'Do not invent materials, do not invent certifications, do not promise delivery speed, do not exaggerate SEO keywords.')
            && Configuration::updateValue(self::CONF_ONLY_MISSING_FIELDS, 1)
            && Configuration::updateValue(self::CONF_GLOSSARY, "gold-plated = gold plated\n925 = sterling silver\ncollection names = keep unchanged")
            && Configuration::updateValue(self::CONF_SEO_META_TITLE_MAX, 60)
            && Configuration::updateValue(self::CONF_SEO_META_DESCRIPTION_MAX, 155)
            && Configuration::updateValue(self::CONF_SEO_SLUG_MAX, 80)
            && Configuration::updateValue(self::CONF_PROMPT_PRODUCT_SEO, 'Translate product SEO fields with premium e-commerce tone. Keep result concise, natural and conversion-friendly.')
            && Configuration::updateValue(self::CONF_PROMPT_PRODUCT_DESCRIPTION, 'Translate product descriptions with elegant and persuasive tone. Preserve HTML and factual accuracy.')
            && Configuration::updateValue(self::CONF_PROMPT_CATEGORY_SEO, 'Translate category SEO fields naturally and clearly for search visibility.')
            && Configuration::updateValue(self::CONF_PROMPT_CATEGORY_DESCRIPTION, 'Translate category content with SEO-friendly, readable and premium wording.')
            && Configuration::updateValue(self::CONF_PROMPT_CMS, 'Translate CMS content with editorial quality, natural language and SEO clarity.')
            && Configuration::updateValue(self::CONF_PROMPT_CE, 'Translate page builder text content. Preserve all HTML tags and attributes exactly. Keep each translated value similar in length to the source to avoid breaking the layout.')
            && Configuration::updateValue(self::CONF_PROMPT_REASSURANCE, 'Translate reassurance block text (delivery, payment, warranty icons). Keep it short, clear and reassuring for the customer.')
            && Configuration::updateValue(self::CONF_PROMPT_BRAND_REVIEWS, 'Translate customer review texts naturally. Keep the tone authentic and personal. Do not invent details. Preserve product/brand names.')
            && Configuration::updateValue(self::CONF_PROMPT_ORDER_STATES, 'Translate order status names for an e-commerce storefront. Keep them short, clear and professional. Preserve any leading reference prefixes like "0 - " if present.')
            && Configuration::updateValue(self::CONF_PROMPT_EVERCONTENTSEO, 'Translate Ever Content SEO HTML blocks (category/manufacturer/supplier SEO footers). Preserve all HTML tags, attributes, links and images. Translate only visible text. Keep a concise SEO tone. Do not invent products, prices or promotions.')
            && Configuration::updateValue(self::CONF_PROMPT_FEATURES, 'Translate product feature names and feature values for an e-commerce catalog. Keep labels short, accurate and consistent. Preserve units, numbers, codes and brand/model names when they must stay unchanged.')
            && Configuration::updateValue(self::CONF_PROMPT_ATTRIBUTES, 'Translate product attribute group names, public names and attribute values (color, size, etc.). Keep them short for product pages and filters. Preserve codes, measurements and brand-specific terms when needed.')
            && Configuration::updateValue(self::CONF_PROMPT_CARRIER_DELAYS, 'Translate carrier delivery delay texts for checkout and shipping estimators. Keep them short and clear. Preserve numbers, time units and carrier brand names. Do not invent delivery promises.')
            && Configuration::updateValue(self::CONF_PROMPT_CKC_LABELS, 'Translate short product badge labels. Preserve HTML tags (<strong>, <b>, <br>), CSS classes and the ↓ character. Keep the result short, similar in length, suitable for a product card flag. Do not invent promotions.')
            && Configuration::updateValue(self::CONF_PROMPT_HELLOWORK_JOBS, 'Translate job offer titles and descriptions for a careers page. Keep a professional recruitment tone. Preserve HTML tags. Do not invent benefits, salaries or locations. Keep H/F and similar gender marks when present, adapted to English (M/F).')
            && Configuration::updateValue(self::CONF_PROMPT_ETS_CFU, 'Translate Contact Form Ultimate (ets_cfu) texts: form titles, URL aliases, SEO, descriptions, field labels, placeholders, help texts, dropdown/radio options, submit buttons, thank-you pages and notification emails. Preserve HTML tags, attributes, links, emails, phone numbers and brand names. If a field is JSON, keep the JSON structure and translate string values only. Keep URL aliases slug-safe. Do not invent legal clauses.')
            && Configuration::updateValue(self::CONF_PROMPT_CKC_REALISATION, 'Translate customer project photo titles and image alt texts (ckc_realisation). Keep them short, natural and descriptive. Do not invent product names, locations or materials. Leave empty fields empty.')
            && Configuration::updateValue(self::CONF_PROMPT_RESPONSIVEGALLERY, 'Translate Responsive Gallery page title, descriptions and gallery titles (product family names like Grillage rigide). Keep labels short, catalog-like and consistent with existing fence vocabulary. Preserve HTML in descriptions. Do not invent product lines.')
            && Configuration::updateValue(self::CONF_PROMPT_PRODUCT_FAQ, 'Translate product FAQ questions and answers for a fencing e-commerce catalog. Preserve all HTML tags, attributes and lists. Keep a clear, helpful customer-support tone. Do not invent technical specs, dimensions, warranties or certifications. Keep brand names and product terms consistent.')
            && Configuration::updateValue(self::CONF_PROMPT_PRODUCT_GUIDE, 'Translate the product measuring guide HTML (how to measure a fence, posts, panels). Preserve all HTML tags, attributes, images, lists and layout. Keep a clear instructional tone. Do not invent dimensions, tools or steps. Keep brand and product names.')
            && Configuration::updateValue(self::CONF_FILTER_TYPE, 'all')
            && Configuration::updateValue(self::CONF_FILTER_IDS, '')
            && Configuration::updateValue(self::CONF_FILTER_CATEGORY_ID, 0);
    }

    protected function uninstallConfiguration()
    {
        return Configuration::deleteByName(self::CONF_API_KEY)
            && Configuration::deleteByName(self::CONF_SOURCE_LANG)
            && Configuration::deleteByName(self::CONF_TARGET_LANG)
            && Configuration::deleteByName(self::CONF_MODEL)
            && Configuration::deleteByName(self::CONF_PROMPT)
            && Configuration::deleteByName(self::CONF_TEMPERATURE)
            && Configuration::deleteByName(self::CONF_MAX_TOKENS)
            && Configuration::deleteByName(self::CONF_ESTIMATED_CREDIT)
            && Configuration::deleteByName(self::CONF_BATCH_SIZE)
            && Configuration::deleteByName(self::CONF_TARGET_COUNTRY)
            && Configuration::deleteByName(self::CONF_COUNTRY_KEYWORDS)
            && Configuration::deleteByName(self::CONF_STYLE_GUIDELINES)
            && Configuration::deleteByName(self::CONF_RESTRICTIONS)
            && Configuration::deleteByName(self::CONF_ONLY_MISSING_FIELDS)
            && Configuration::deleteByName(self::CONF_GLOSSARY)
            && Configuration::deleteByName(self::CONF_SEO_META_TITLE_MAX)
            && Configuration::deleteByName(self::CONF_SEO_META_DESCRIPTION_MAX)
            && Configuration::deleteByName(self::CONF_SEO_SLUG_MAX)
            && Configuration::deleteByName(self::CONF_PROMPT_PRODUCT_SEO)
            && Configuration::deleteByName(self::CONF_PROMPT_PRODUCT_DESCRIPTION)
            && Configuration::deleteByName(self::CONF_PROMPT_CATEGORY_SEO)
            && Configuration::deleteByName(self::CONF_PROMPT_CATEGORY_DESCRIPTION)
            && Configuration::deleteByName(self::CONF_PROMPT_CMS)
            && Configuration::deleteByName(self::CONF_PROMPT_CE)
            && Configuration::deleteByName(self::CONF_PROMPT_REASSURANCE)
            && Configuration::deleteByName(self::CONF_PROMPT_BRAND_REVIEWS)
            && Configuration::deleteByName(self::CONF_PROMPT_ORDER_STATES)
            && Configuration::deleteByName(self::CONF_PROMPT_EVERCONTENTSEO)
            && Configuration::deleteByName(self::CONF_PROMPT_FEATURES)
            && Configuration::deleteByName(self::CONF_PROMPT_ATTRIBUTES)
            && Configuration::deleteByName(self::CONF_PROMPT_CARRIER_DELAYS)
            && Configuration::deleteByName(self::CONF_PROMPT_CKC_LABELS)
            && Configuration::deleteByName(self::CONF_PROMPT_HELLOWORK_JOBS)
            && Configuration::deleteByName(self::CONF_PROMPT_ETS_CFU)
            && Configuration::deleteByName(self::CONF_PROMPT_CKC_REALISATION)
            && Configuration::deleteByName(self::CONF_PROMPT_RESPONSIVEGALLERY)
            && Configuration::deleteByName(self::CONF_PROMPT_PRODUCT_FAQ)
            && Configuration::deleteByName(self::CONF_PROMPT_PRODUCT_GUIDE)
            && Configuration::deleteByName(self::CONF_FILTER_TYPE)
            && Configuration::deleteByName(self::CONF_FILTER_IDS)
            && Configuration::deleteByName(self::CONF_FILTER_CATEGORY_ID);
    }

    protected function installSql()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckctranslate_history` (
            `id_ckctranslate_history` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `job_code` VARCHAR(64) NOT NULL,
            `entity_type` VARCHAR(64) NOT NULL,
            `entity_id` INT UNSIGNED NOT NULL,
            `status` VARCHAR(32) NOT NULL,
            `model` VARCHAR(64) NOT NULL,
            `estimated_cost` DECIMAL(12,6) NOT NULL DEFAULT 0.000000,
            `message` TEXT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_ckctranslate_history`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return (bool) Db::getInstance()->execute($sql) && CkcTranslateCfuI18n::ensureTable();
    }

    /**
     * CKC Vianney — CE carousel uses $GLOBALS['language'] for product URLs.
     */
    public function hookActionFrontControllerInitAfter($params)
    {
        if (isset($this->context->language) && is_object($this->context->language)) {
            $GLOBALS['language'] = $this->context->language;
        }
    }

    public function hookActionPresentProduct($params)
    {
        $this->fixPresentedProductUrls($params);
    }

    public function hookActionPresentProductListing($params)
    {
        $this->fixPresentedProductUrls($params);
    }

    /**
     * CKC Vianney — keep miniature / variant links in the current FO language.
     */
    protected function fixPresentedProductUrls($params)
    {
        if (empty($params['presentedProduct']) || !is_object($params['presentedProduct'])) {
            return;
        }
        $presented = $params['presentedProduct'];
        if (!method_exists($presented, 'offsetSet')) {
            return;
        }

        $idProduct = (int) $presented['id_product'];
        if ($idProduct <= 0) {
            return;
        }

        $idLang = (int) $this->context->language->id;
        $idAttr = !empty($presented['id_product_attribute']) ? (int) $presented['id_product_attribute'] : 0;
        $url = $this->context->link->getProductLink(
            $idProduct,
            null,
            null,
            null,
            $idLang,
            null,
            $idAttr > 0 ? $idAttr : null
        );

        $presented->offsetSet('url', $url, true);
        $presented->offsetSet(
            'canonical_url',
            $this->context->link->getProductLink($idProduct, null, null, null, $idLang),
            true
        );

        if (empty($presented['main_variants']) || !is_array($presented['main_variants'])) {
            return;
        }

        $variants = $presented['main_variants'];
        foreach ($variants as &$variant) {
            if (empty($variant['id_product_attribute'])) {
                continue;
            }
            $variant['url'] = $this->context->link->getProductLink(
                $idProduct,
                null,
                null,
                null,
                $idLang,
                null,
                (int) $variant['id_product_attribute']
            );
        }
        unset($variant);
        $presented->offsetSet('main_variants', $variants, true);
    }

    /**
     * CKC Vianney — labels CFU rendus depuis la colonne form (non lang).
     */
    public function hookActionOutputHTMLBefore($params)
    {
        if (empty($params['html']) || strpos((string) $params['html'], 'ets-cfu-form') === false) {
            return;
        }
        if (!Module::isInstalled('ets_cfultimate') || !Module::isEnabled('ets_cfultimate')) {
            return;
        }
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $params['html'] = CkcTranslateCfuI18n::replaceInHtml((string) $params['html'], $idLang, $idShop);
    }

    public static function getFrontProductFaqs($idProduct, $activeOnly = true)
    {
        if (!Module::isInstalled('productfaq') || !Module::isEnabled('productfaq')) {
            return array();
        }

        return CkcTranslateJobRunner::getFrontProductFaqs((int) $idProduct, (bool) $activeOnly);
    }

    public static function getFrontProductGuideContent($idProduct)
    {
        if (!Module::isInstalled('productguide') || !Module::isEnabled('productguide')) {
            return '';
        }

        return CkcTranslateJobRunner::getFrontProductGuideContent((int) $idProduct);
    }

    protected function uninstallSql()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ckctranslate_history`';

        return (bool) Db::getInstance()->execute($sql);
    }

    protected function installTab()
    {
        $existingId = (int) Tab::getIdFromClassName('AdminCkcTranslate');
        if ($existingId > 0) {
            return true;
        }

        $parentTabId = (int) Tab::getIdFromClassName('AdminParentModulesSf');
        if ($parentTabId <= 0) {
            $parentTabId = (int) Tab::getIdFromClassName('AdminModules');
        }

        if ($parentTabId <= 0) {
            return false;
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminCkcTranslate';
        $tab->module = $this->name;
        $tab->id_parent = $parentTabId;

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'CKC Translate';
        }

        return (bool) $tab->add();
    }

    protected function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminCkcTranslate');

        if ($idTab <= 0) {
            return true;
        }

        $tab = new Tab($idTab);

        return (bool) $tab->delete();
    }
}