<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/CkcTranslateAi.php';
require_once __DIR__ . '/classes/CkcTranslateJobRunner.php';

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

    public const CONF_FILTER_TYPE = 'CKCTRANSLATE_FILTER_TYPE';
    public const CONF_FILTER_IDS = 'CKCTRANSLATE_FILTER_IDS';
    public const CONF_FILTER_CATEGORY_ID = 'CKCTRANSLATE_FILTER_CATEGORY_ID';

    public function __construct()
    {
        $this->name = 'ckctranslate';
        $this->tab = 'administration';
        $this->version = '2.5.0';
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
            && $this->installSql();
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

        return (bool) Db::getInstance()->execute($sql);
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