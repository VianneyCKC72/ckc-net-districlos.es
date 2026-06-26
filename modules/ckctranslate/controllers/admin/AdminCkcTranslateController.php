<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminCkcTranslateController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::isSubmit('submitCkcTranslateConfig')) {
            $this->postProcessConfig();
        }

        if (Tools::isSubmit('submitCkcTranslateResetQueue')) {
            $this->resetQueues();
        }

        if (Tools::isSubmit('submitCkcTranslateClearHistory')) {
            $this->clearHistory();
        }

        if (Tools::getValue('run_job')) {
            $this->runJob((string) Tools::getValue('run_job'));

            return;
        }

        $this->context->smarty->assign(array(
            'form_html' => $this->renderFormConfig(),
            'jobs' => $this->getJobsWithCounters(),
            'current_url' => self::$currentIndex . '&token=' . $this->token,
            'history_rows' => $this->getHistoryRows(),
            'preview_rows' => $this->getTranslationPreview(),
            'prompt_example' => $this->getAdvancedPromptExample(),
            'estimated_batch_cost' => $this->getEstimatedBatchCost(),
            'available_models' => $this->getAvailableModels(),
        ));

        $this->setTemplate('configure.tpl');
    }

    /**
     * Retourne la valeur de config, ou la valeur par défaut si elle est vide/absente.
     */
    protected function configOrDefault($key)
    {
        $value = Configuration::get($key);

        if ($value === false || $value === null || $value === '') {
            $defaults = CkcTranslate::getDefaultConfig();
            if (array_key_exists($key, $defaults)) {
                return $defaults[$key];
            }
        }

        return $value;
    }

    protected function postProcessConfig()
    {
        $apiKey = trim((string) Tools::getValue(CkcTranslate::CONF_API_KEY));
        $sourceLang = (int) Tools::getValue(CkcTranslate::CONF_SOURCE_LANG);
        $targetLang = (int) Tools::getValue(CkcTranslate::CONF_TARGET_LANG);
        $model = trim((string) Tools::getValue(CkcTranslate::CONF_MODEL));
        $prompt = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT));
        $temperature = (float) Tools::getValue(CkcTranslate::CONF_TEMPERATURE);
        $maxTokens = (int) Tools::getValue(CkcTranslate::CONF_MAX_TOKENS);
        $batchSize = (int) Tools::getValue(CkcTranslate::CONF_BATCH_SIZE);
        $targetCountry = trim((string) Tools::getValue(CkcTranslate::CONF_TARGET_COUNTRY));
        $countryKeywords = trim((string) Tools::getValue(CkcTranslate::CONF_COUNTRY_KEYWORDS));
        $styleGuidelines = trim((string) Tools::getValue(CkcTranslate::CONF_STYLE_GUIDELINES));
        $restrictions = trim((string) Tools::getValue(CkcTranslate::CONF_RESTRICTIONS));
        $onlyMissingFields = (int) Tools::getValue(CkcTranslate::CONF_ONLY_MISSING_FIELDS);

        $glossary = trim((string) Tools::getValue(CkcTranslate::CONF_GLOSSARY));
        $seoMetaTitleMax = (int) Tools::getValue(CkcTranslate::CONF_SEO_META_TITLE_MAX);
        $seoMetaDescriptionMax = (int) Tools::getValue(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX);
        $seoSlugMax = (int) Tools::getValue(CkcTranslate::CONF_SEO_SLUG_MAX);
        $promptProductSeo = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_PRODUCT_SEO));
        $promptProductDescription = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_PRODUCT_DESCRIPTION));
        $promptCategorySeo = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_CATEGORY_SEO));
        $promptCategoryDescription = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_CATEGORY_DESCRIPTION));
        $promptCms = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_CMS));
        $filterType = trim((string) Tools::getValue(CkcTranslate::CONF_FILTER_TYPE));
        $filterIds = trim((string) Tools::getValue(CkcTranslate::CONF_FILTER_IDS));
        $filterCategoryId = (int) Tools::getValue(CkcTranslate::CONF_FILTER_CATEGORY_ID);
        $allowedIpsRaw = trim((string) Tools::getValue(CkcTranslate::CONF_ALLOWED_IPS));
        $allowedIpsNormalized = implode("\n", CkcTranslate::parseAllowedIps($allowedIpsRaw));

        $defaults = CkcTranslate::getDefaultConfig();

        // Champs vides/invalides : on applique automatiquement la valeur par défaut
        // (plutôt que de bloquer l'enregistrement avec une erreur).
        if ($sourceLang <= 0) {
            $sourceLang = (int) $defaults[CkcTranslate::CONF_SOURCE_LANG];
        }
        if ($targetLang <= 0) {
            $targetLang = (int) $defaults[CkcTranslate::CONF_TARGET_LANG];
        }
        if ($temperature < 0 || $temperature > 1) {
            $temperature = (float) $defaults[CkcTranslate::CONF_TEMPERATURE];
        }
        if ($maxTokens < 100) {
            $maxTokens = (int) $defaults[CkcTranslate::CONF_MAX_TOKENS];
        }
        if ($batchSize < 1) {
            $batchSize = (int) $defaults[CkcTranslate::CONF_BATCH_SIZE];
        }

        // Seul cas réellement bloquant : langues source et cible identiques.
        if ($sourceLang === $targetLang) {
            $this->errors[] = $this->module->l('Source and target languages must be different.');
            return;
        }

        if (!in_array($filterType, array('all', 'ids', 'category'))) {
            $filterType = 'all';
        }

        Configuration::updateValue(CkcTranslate::CONF_API_KEY, $apiKey);
        Configuration::updateValue(CkcTranslate::CONF_SOURCE_LANG, $sourceLang);
        Configuration::updateValue(CkcTranslate::CONF_TARGET_LANG, $targetLang);
        Configuration::updateValue(CkcTranslate::CONF_MODEL, $model ?: 'gpt-4.1-mini');
        Configuration::updateValue(CkcTranslate::CONF_PROMPT, $prompt);
        Configuration::updateValue(CkcTranslate::CONF_TEMPERATURE, (string) $temperature);
        Configuration::updateValue(CkcTranslate::CONF_MAX_TOKENS, $maxTokens);
        Configuration::updateValue(CkcTranslate::CONF_ESTIMATED_CREDIT, $this->estimateCredit($maxTokens));
        Configuration::updateValue(CkcTranslate::CONF_BATCH_SIZE, $batchSize);
        Configuration::updateValue(CkcTranslate::CONF_TARGET_COUNTRY, pSQL($targetCountry));
        Configuration::updateValue(CkcTranslate::CONF_COUNTRY_KEYWORDS, $countryKeywords);
        Configuration::updateValue(CkcTranslate::CONF_STYLE_GUIDELINES, $styleGuidelines);
        Configuration::updateValue(CkcTranslate::CONF_RESTRICTIONS, $restrictions);
        Configuration::updateValue(CkcTranslate::CONF_ONLY_MISSING_FIELDS, $onlyMissingFields ? 1 : 0);

        Configuration::updateValue(CkcTranslate::CONF_GLOSSARY, $glossary);
        Configuration::updateValue(CkcTranslate::CONF_SEO_META_TITLE_MAX, max($seoMetaTitleMax, 10));
        Configuration::updateValue(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX, max($seoMetaDescriptionMax, 10));
        Configuration::updateValue(CkcTranslate::CONF_SEO_SLUG_MAX, max($seoSlugMax, 10));
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_PRODUCT_SEO, $promptProductSeo);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_PRODUCT_DESCRIPTION, $promptProductDescription);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_CATEGORY_SEO, $promptCategorySeo);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_CATEGORY_DESCRIPTION, $promptCategoryDescription);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_CMS, $promptCms);
        Configuration::updateValue(CkcTranslate::CONF_FILTER_TYPE, $filterType);
        Configuration::updateValue(CkcTranslate::CONF_FILTER_IDS, $filterIds);
        Configuration::updateValue(CkcTranslate::CONF_FILTER_CATEGORY_ID, $filterCategoryId);
        Configuration::updateValue(CkcTranslate::CONF_ALLOWED_IPS, $allowedIpsNormalized);

        $this->confirmations[] = $this->module->l('Settings updated successfully.');
    }

    protected function renderFormConfig()
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $currentIp = (string) Tools::getRemoteAddr();
        $modelOptions = array();

        foreach ($this->getAvailableModels() as $model) {
            $modelOptions[] = array(
                'id_option' => $model['id'],
                'name' => $model['label'],
            );
        }

        $filterOptions = array(
            array(
                'id_option' => 'all',
                'name' => $this->module->l('All items'),
            ),
            array(
                'id_option' => 'ids',
                'name' => $this->module->l('Only specific IDs'),
            ),
            array(
                'id_option' => 'category',
                'name' => $this->module->l('Only products from one category'),
            ),
        );

        $fieldsForm = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->module->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'description' => $this->module->l('Pre-filled values are provided to help users start quickly.'),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('API key'),
                        'name' => CkcTranslate::CONF_API_KEY,
                        'required' => true,
                        'desc' => $this->module->l('Paste your API key here. Never hardcode it in source files.'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->module->l('Source language'),
                        'name' => CkcTranslate::CONF_SOURCE_LANG,
                        'required' => true,
                        'options' => array(
                            'query' => Language::getLanguages(false),
                            'id' => 'id_lang',
                            'name' => 'name',
                        ),
                        'desc' => $this->module->l('Usually the default language of the shop.'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->module->l('Target language'),
                        'name' => CkcTranslate::CONF_TARGET_LANG,
                        'required' => true,
                        'options' => array(
                            'query' => Language::getLanguages(false),
                            'id' => 'id_lang',
                            'name' => 'name',
                        ),
                        'desc' => $this->module->l('Language that will receive the generated translation.'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->module->l('Model'),
                        'name' => CkcTranslate::CONF_MODEL,
                        'required' => true,
                        'options' => array(
                            'query' => $modelOptions,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'desc' => $this->module->l('Recommended models proposed by the module.'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->module->l('Translate only missing fields'),
                        'name' => CkcTranslate::CONF_ONLY_MISSING_FIELDS,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->module->l('Yes'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->module->l('No'),
                            ),
                        ),
                        'desc' => $this->module->l('If enabled, the module skips entries already translated in the target language.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Pages / items per launch'),
                        'name' => CkcTranslate::CONF_BATCH_SIZE,
                        'required' => true,
                        'desc' => $this->module->l('Number of items translated per launch cycle. Example: 10, 20, 50.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Target country / market'),
                        'name' => CkcTranslate::CONF_TARGET_COUNTRY,
                        'desc' => $this->module->l('Examples: US, UK, CA, AU, BE. Used to adapt wording and keyword hints.'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Global prompt'),
                        'name' => CkcTranslate::CONF_PROMPT,
                        'rows' => 5,
                        'cols' => 60,
                        'desc' => $this->module->l('Base instruction reused in translation jobs. Keep it clear and business-oriented.'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Glossary'),
                        'name' => CkcTranslate::CONF_GLOSSARY,
                        'rows' => 6,
                        'cols' => 60,
                        'desc' => $this->module->l('One rule per line. Example: gold-plated = gold plated'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('SEO max meta title'),
                        'name' => CkcTranslate::CONF_SEO_META_TITLE_MAX,
                        'desc' => $this->module->l('Recommended maximum length for meta titles.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('SEO max meta description'),
                        'name' => CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX,
                        'desc' => $this->module->l('Recommended maximum length for meta descriptions.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('SEO max slug length'),
                        'name' => CkcTranslate::CONF_SEO_SLUG_MAX,
                        'desc' => $this->module->l('Recommended maximum length for slugs.'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Product SEO'),
                        'name' => CkcTranslate::CONF_PROMPT_PRODUCT_SEO,
                        'rows' => 4,
                        'cols' => 60,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Product descriptions'),
                        'name' => CkcTranslate::CONF_PROMPT_PRODUCT_DESCRIPTION,
                        'rows' => 4,
                        'cols' => 60,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Category SEO'),
                        'name' => CkcTranslate::CONF_PROMPT_CATEGORY_SEO,
                        'rows' => 4,
                        'cols' => 60,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Category descriptions'),
                        'name' => CkcTranslate::CONF_PROMPT_CATEGORY_DESCRIPTION,
                        'rows' => 4,
                        'cols' => 60,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - CMS'),
                        'name' => CkcTranslate::CONF_PROMPT_CMS,
                        'rows' => 4,
                        'cols' => 60,
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->module->l('Filter type'),
                        'name' => CkcTranslate::CONF_FILTER_TYPE,
                        'options' => array(
                            'query' => $filterOptions,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'desc' => $this->module->l('Choose how to limit the content to translate.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Filter IDs'),
                        'name' => CkcTranslate::CONF_FILTER_IDS,
                        'desc' => $this->module->l('Comma-separated IDs. Example: 12,15,48'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Filter category ID'),
                        'name' => CkcTranslate::CONF_FILTER_CATEGORY_ID,
                        'desc' => $this->module->l('Used when filter type = category'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Suggested country keywords'),
                        'name' => CkcTranslate::CONF_COUNTRY_KEYWORDS,
                        'rows' => 4,
                        'cols' => 60,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Editorial guidelines / advice'),
                        'name' => CkcTranslate::CONF_STYLE_GUIDELINES,
                        'rows' => 4,
                        'cols' => 60,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Restrictions'),
                        'name' => CkcTranslate::CONF_RESTRICTIONS,
                        'rows' => 4,
                        'cols' => 60,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Temperature'),
                        'name' => CkcTranslate::CONF_TEMPERATURE,
                        'required' => true,
                        'desc' => $this->module->l('Recommended: 0.2 to 0.4 for stable SEO translations.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Max tokens'),
                        'name' => CkcTranslate::CONF_MAX_TOKENS,
                        'required' => true,
                        'desc' => $this->module->l('Recommended: 800 to 1500 depending on content length.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Estimated credit / request'),
                        'name' => CkcTranslate::CONF_ESTIMATED_CREDIT,
                        'readonly' => true,
                        'desc' => $this->module->l('Approximate estimate calculated from the token limit.'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Front-office language switcher — allowed IPs'),
                        'name' => CkcTranslate::CONF_ALLOWED_IPS,
                        'rows' => 5,
                        'cols' => 60,
                        'desc' => sprintf(
                            $this->module->l('One IP per line. Supports exact IPv4 (e.g. 1.2.3.4), wildcards (e.g. 192.168.1.*) and CIDR (e.g. 10.0.0.0/24). For everyone else, the language selector is hidden and the language is forced to the shop default. Leave empty to hide the selector for all visitors. Your current IP: %s'),
                            $currentIp !== '' ? $currentIp : $this->module->l('unknown')
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->module->l('Save'),
                    'name' => 'submitCkcTranslateConfig',
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->name_controller = $this->module->name;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCkcTranslateConfig';
        $helper->currentIndex = self::$currentIndex . '&token=' . $this->token;
        $helper->token = $this->token;
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;
        $helper->show_toolbar = false;

        $helper->fields_value = array(
            CkcTranslate::CONF_API_KEY => Configuration::get(CkcTranslate::CONF_API_KEY),
            CkcTranslate::CONF_SOURCE_LANG => (int) $this->configOrDefault(CkcTranslate::CONF_SOURCE_LANG),
            CkcTranslate::CONF_TARGET_LANG => (int) $this->configOrDefault(CkcTranslate::CONF_TARGET_LANG),
            CkcTranslate::CONF_MODEL => $this->configOrDefault(CkcTranslate::CONF_MODEL),
            CkcTranslate::CONF_PROMPT => $this->configOrDefault(CkcTranslate::CONF_PROMPT),
            CkcTranslate::CONF_TEMPERATURE => $this->configOrDefault(CkcTranslate::CONF_TEMPERATURE),
            CkcTranslate::CONF_MAX_TOKENS => $this->configOrDefault(CkcTranslate::CONF_MAX_TOKENS),
            CkcTranslate::CONF_ESTIMATED_CREDIT => Configuration::get(CkcTranslate::CONF_ESTIMATED_CREDIT),
            CkcTranslate::CONF_BATCH_SIZE => (int) $this->configOrDefault(CkcTranslate::CONF_BATCH_SIZE),
            CkcTranslate::CONF_TARGET_COUNTRY => $this->configOrDefault(CkcTranslate::CONF_TARGET_COUNTRY),
            CkcTranslate::CONF_COUNTRY_KEYWORDS => Configuration::get(CkcTranslate::CONF_COUNTRY_KEYWORDS),
            CkcTranslate::CONF_STYLE_GUIDELINES => Configuration::get(CkcTranslate::CONF_STYLE_GUIDELINES),
            CkcTranslate::CONF_RESTRICTIONS => Configuration::get(CkcTranslate::CONF_RESTRICTIONS),
            CkcTranslate::CONF_ONLY_MISSING_FIELDS => (int) $this->configOrDefault(CkcTranslate::CONF_ONLY_MISSING_FIELDS),
            CkcTranslate::CONF_GLOSSARY => Configuration::get(CkcTranslate::CONF_GLOSSARY),
            CkcTranslate::CONF_SEO_META_TITLE_MAX => (int) $this->configOrDefault(CkcTranslate::CONF_SEO_META_TITLE_MAX),
            CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX => (int) $this->configOrDefault(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX),
            CkcTranslate::CONF_SEO_SLUG_MAX => (int) $this->configOrDefault(CkcTranslate::CONF_SEO_SLUG_MAX),
            CkcTranslate::CONF_PROMPT_PRODUCT_SEO => Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_SEO),
            CkcTranslate::CONF_PROMPT_PRODUCT_DESCRIPTION => Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_DESCRIPTION),
            CkcTranslate::CONF_PROMPT_CATEGORY_SEO => Configuration::get(CkcTranslate::CONF_PROMPT_CATEGORY_SEO),
            CkcTranslate::CONF_PROMPT_CATEGORY_DESCRIPTION => Configuration::get(CkcTranslate::CONF_PROMPT_CATEGORY_DESCRIPTION),
            CkcTranslate::CONF_PROMPT_CMS => Configuration::get(CkcTranslate::CONF_PROMPT_CMS),
            CkcTranslate::CONF_FILTER_TYPE => $this->configOrDefault(CkcTranslate::CONF_FILTER_TYPE),
            CkcTranslate::CONF_FILTER_IDS => Configuration::get(CkcTranslate::CONF_FILTER_IDS),
            CkcTranslate::CONF_FILTER_CATEGORY_ID => (int) Configuration::get(CkcTranslate::CONF_FILTER_CATEGORY_ID),
            CkcTranslate::CONF_ALLOWED_IPS => Configuration::get(CkcTranslate::CONF_ALLOWED_IPS),
        );

        return $helper->generateForm(array($fieldsForm));
    }

    protected function runJob($job)
    {
        $ai = new CkcTranslateAi(
            Configuration::get(CkcTranslate::CONF_API_KEY),
            Configuration::get(CkcTranslate::CONF_MODEL),
            (float) Configuration::get(CkcTranslate::CONF_TEMPERATURE),
            (int) Configuration::get(CkcTranslate::CONF_MAX_TOKENS),
            Configuration::get(CkcTranslate::CONF_PROMPT)
        );

        if (!$ai->isConfigured()) {
            $this->errors[] = $this->module->l('API key is missing.');
            $this->context->smarty->assign(array(
                'result' => array(
                    'success' => false,
                    'message' => $this->module->l('Please save an API key before launching a job.'),
                    'finished' => true,
                ),
                'back_url' => self::$currentIndex . '&token=' . $this->token,
                'auto_reload_url' => '',
                'auto_reload' => false,
            ));
            $this->setTemplate('result.tpl');

            return;
        }

        $runner = new CkcTranslateJobRunner(
            $this->module,
            $ai,
            (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG),
            (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG)
        );

        $result = $runner->run($job);
        $autoReloadUrl = self::$currentIndex . '&token=' . $this->token . '&run_job=' . urlencode($job);

        $this->context->smarty->assign(array(
            'result' => $result,
            'back_url' => self::$currentIndex . '&token=' . $this->token,
            'auto_reload_url' => $autoReloadUrl,
            'auto_reload' => empty($result['finished']),
        ));

        $this->setTemplate('result.tpl');
    }

    protected function getJobs()
    {
        $jobs = array(
            array(
                'code' => 'product_seo',
                'label' => $this->module->l('Translate product SEO fields'),
                'advice' => $this->module->l('Recommended first step for catalogs.'),
            ),
            array(
                'code' => 'product_description',
                'label' => $this->module->l('Translate product descriptions'),
                'advice' => $this->module->l('Use after SEO fields for better consistency.'),
            ),
            array(
                'code' => 'category_seo',
                'label' => $this->module->l('Translate category SEO fields'),
                'advice' => $this->module->l('Good for menu, listing pages and indexing.'),
            ),
            array(
                'code' => 'category_description',
                'label' => $this->module->l('Translate category descriptions'),
                'advice' => $this->module->l('Run after category SEO if needed.'),
            ),
            array(
                'code' => 'cms',
                'label' => $this->module->l('Translate CMS pages'),
                'advice' => $this->module->l('Useful for editorial and SEO pages.'),
            ),
        );

        foreach (CkcTranslate::getModuleLangEntities() as $entity) {
            $jobs[] = array(
                'code' => $entity['code'],
                'label' => $entity['label'],
                'advice' => $this->module->l('Module per-language field translation.'),
            );
        }

        return $jobs;
    }

    protected function getJobsWithCounters()
    {
        $jobs = $this->getJobs();

        foreach ($jobs as &$job) {
            $job['total'] = 0;
            $job['remaining'] = 0;

            if (!isset($job['code'])) {
                continue;
            }

            $counters = $this->getJobCounters($job['code']);
            $job['total'] = isset($counters['total']) ? (int) $counters['total'] : 0;
            $job['remaining'] = isset($counters['remaining']) ? (int) $counters['remaining'] : 0;
        }
        unset($job);

        return $jobs;
    }

    protected function getJobCounters($jobCode)
    {
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $filterCategoryId = (int) Configuration::get(CkcTranslate::CONF_FILTER_CATEGORY_ID);

        switch ($jobCode) {
            case 'product_seo':
            case 'product_description':
                $sql = 'SELECT COUNT(DISTINCT p.id_product)
                        FROM `' . _DB_PREFIX_ . 'product` p';

                if ($filterType === 'category' && $filterCategoryId > 0) {
                    $sql .= ' INNER JOIN `' . _DB_PREFIX_ . 'category_product` cp ON cp.id_product = p.id_product';
                }

                $sql .= ' WHERE p.active = 1';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND p.id_product IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($filterType === 'category' && $filterCategoryId > 0) {
                    $sql .= ' AND cp.id_category = ' . (int) $filterCategoryId;
                }

                $total = (int) Db::getInstance()->getValue($sql);
                $cookieKey = $jobCode === 'product_seo' ? 'ckctranslate_product_seo_ids' : 'ckctranslate_product_desc_ids';
                $indexKey = $jobCode === 'product_seo' ? 'ckctranslate_product_seo_index' : 'ckctranslate_product_desc_index';
                break;

            case 'category_seo':
            case 'category_description':
                $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'category`
                        WHERE active = 1 AND id_parent != 0';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND id_category IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                $total = (int) Db::getInstance()->getValue($sql);
                $cookieKey = $jobCode === 'category_seo' ? 'ckctranslate_category_seo_ids' : 'ckctranslate_category_desc_ids';
                $indexKey = $jobCode === 'category_seo' ? 'ckctranslate_category_seo_index' : 'ckctranslate_category_desc_index';
                break;

            case 'cms':
                $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'cms` WHERE active = 1';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND id_cms IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                $total = (int) Db::getInstance()->getValue($sql);
                $cookieKey = 'ckctranslate_cms_ids';
                $indexKey = 'ckctranslate_cms_index';
                break;

            default:
                $entity = CkcTranslate::getModuleLangEntity($jobCode);
                if ($entity === null) {
                    return array(
                        'total' => 0,
                        'remaining' => 0,
                    );
                }

                $sql = 'SELECT COUNT(DISTINCT `' . bqSQL($entity['id']) . '`)
                        FROM `' . _DB_PREFIX_ . bqSQL($entity['table']) . '_lang`
                        WHERE id_lang = ' . (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
                $total = (int) Db::getInstance()->getValue($sql);
                $cookieKey = 'ckctranslate_' . $entity['code'] . '_ids';
                $indexKey = 'ckctranslate_' . $entity['code'] . '_index';
                break;
        }

        $cookie = $this->context->cookie;

        // Seul l'index est désormais stocké en cookie (plus de liste d'IDs surdimensionnée).
        if (isset($cookie->{$indexKey})) {
            $index = (int) $cookie->{$indexKey};

            return array(
                'total' => (int) $total,
                'remaining' => max((int) $total - $index, 0),
            );
        }

        return array(
            'total' => (int) $total,
            'remaining' => (int) $total,
        );
    }

    protected function resetQueues()
    {
        $keys = array(
            'ckctranslate_product_seo_ids',
            'ckctranslate_product_seo_index',
            'ckctranslate_product_desc_ids',
            'ckctranslate_product_desc_index',
            'ckctranslate_category_seo_ids',
            'ckctranslate_category_seo_index',
            'ckctranslate_category_desc_ids',
            'ckctranslate_category_desc_index',
            'ckctranslate_cms_ids',
            'ckctranslate_cms_index',
        );

        foreach (CkcTranslate::getModuleLangEntities() as $entity) {
            $keys[] = 'ckctranslate_' . $entity['code'] . '_ids';
            $keys[] = 'ckctranslate_' . $entity['code'] . '_index';
        }

        foreach ($keys as $key) {
            unset($this->context->cookie->{$key});
        }

        Tools::clearAllCache();
        $this->confirmations[] = $this->module->l('Translation queues have been reset.');
    }

    protected function clearHistory()
    {
        Db::getInstance()->delete('ckctranslate_history', '1 = 1');
        $this->confirmations[] = $this->module->l('Translation history has been cleared.');
    }

    protected function getAvailableModels()
    {
        return array(
            array(
                'id' => 'gpt-4.1-mini',
                'label' => 'gpt-4.1-mini — fast / economical / recommended',
            ),
            array(
                'id' => 'gpt-4.1',
                'label' => 'gpt-4.1 — higher quality / slower / more expensive',
            ),
            array(
                'id' => 'gpt-4o-mini',
                'label' => 'gpt-4o-mini — balanced alternative',
            ),
        );
    }

    protected function getAdvancedPromptExample()
    {
        return 'You are a senior e-commerce localization expert for premium jewelry. Adapt the text for the target market, keep natural human phrasing, preserve HTML, improve clarity and SEO relevance without keyword stuffing. Prioritize conversion, elegance, trust and readability. Respect local wording for the target country, keep brand voice premium, never invent materials, certifications, discounts or shipping promises.';
    }

    protected function getEstimatedBatchCost()
    {
        $perRequest = (float) Configuration::get(CkcTranslate::CONF_ESTIMATED_CREDIT);
        $batchSize = (int) Configuration::get(CkcTranslate::CONF_BATCH_SIZE);

        return number_format($perRequest * $batchSize, 4, '.', '');
    }

    protected function getHistoryRows()
    {
        $sql = 'SELECT job_code, entity_type, entity_id, status, model, estimated_cost, message, date_add
                FROM `' . _DB_PREFIX_ . 'ckctranslate_history`
                ORDER BY id_ckctranslate_history DESC
                LIMIT 20';

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : array();
    }

    /**
     * Aperçu des traductions réellement produites (source FR -> cible EN),
     * pour les entités traduites avec succès les plus récentes.
     */
    protected function getTranslationPreview($limit = 30)
    {
        $sourceLang = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
        $targetLang = (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG);

        $rows = Db::getInstance()->executeS(
            'SELECT entity_type, entity_id, MAX(date_add) AS last_date,
                    GROUP_CONCAT(DISTINCT job_code SEPARATOR ", ") AS jobs
             FROM `' . _DB_PREFIX_ . 'ckctranslate_history`
             WHERE status = "success"
             GROUP BY entity_type, entity_id
             ORDER BY last_date DESC
             LIMIT ' . (int) $limit
        );

        if (!is_array($rows)) {
            return array();
        }

        $preview = array();
        foreach ($rows as $r) {
            $fields = $this->getEntityFieldPreview($r['entity_type'], (int) $r['entity_id'], $sourceLang, $targetLang);
            if (empty($fields)) {
                continue;
            }
            $preview[] = array(
                'type' => $r['entity_type'],
                'id' => (int) $r['entity_id'],
                'jobs' => $r['jobs'],
                'date' => $r['last_date'],
                'fields' => $fields,
            );
        }

        return $preview;
    }

    /**
     * Retourne, pour une entité, la liste [label, source, target] des champs traduisibles.
     */
    protected function getEntityFieldPreview($type, $id, $sourceLang, $targetLang)
    {
        $fields = array();
        $defs = array();
        $sourceObj = null;
        $targetObj = null;

        if ($type === 'product' && class_exists('Product')) {
            $sourceObj = new Product($id, false, $sourceLang);
            $targetObj = new Product($id, false, $targetLang);
            $defs = array('name', 'meta_title', 'description_short', 'description');
        } elseif ($type === 'category' && class_exists('Category')) {
            $sourceObj = new Category($id, $sourceLang);
            $targetObj = new Category($id, $targetLang);
            $defs = array('name', 'meta_title', 'description');
        } elseif ($type === 'cms' && class_exists('CMS')) {
            $sourceObj = new CMS($id, $sourceLang);
            $targetObj = new CMS($id, $targetLang);
            $defs = array('meta_title', 'content');
        } else {
            // Table de module par langue (ex. ckc_realisation)
            foreach (CkcTranslate::getModuleLangEntities() as $entity) {
                if ($entity['table'] !== $type) {
                    continue;
                }
                $cols = array();
                foreach ($entity['fields'] as $f) {
                    $cols[] = '`' . bqSQL($f) . '`';
                }
                $base = 'SELECT ' . implode(', ', $cols) . ' FROM `' . _DB_PREFIX_ . bqSQL($entity['table']) . '_lang`'
                    . ' WHERE `' . bqSQL($entity['id']) . '` = ' . (int) $id . ' AND id_lang = ';
                $src = Db::getInstance()->getRow($base . (int) $sourceLang);
                $tgt = Db::getInstance()->getRow($base . (int) $targetLang);
                foreach ($entity['fields'] as $f) {
                    $fields[] = array(
                        'label' => $f,
                        'source' => $this->previewSnippet(isset($src[$f]) ? $src[$f] : ''),
                        'target' => $this->previewSnippet(isset($tgt[$f]) ? $tgt[$f] : ''),
                    );
                }
                break;
            }

            return $fields;
        }

        if ($sourceObj && Validate::isLoadedObject($sourceObj)) {
            foreach ($defs as $f) {
                $sv = isset($sourceObj->{$f}) ? (string) $sourceObj->{$f} : '';
                $tv = isset($targetObj->{$f}) ? (string) $targetObj->{$f} : '';
                if (trim(strip_tags($sv)) === '' && trim(strip_tags($tv)) === '') {
                    continue;
                }
                $fields[] = array(
                    'label' => $f,
                    'source' => $this->previewSnippet($sv),
                    'target' => $this->previewSnippet($tv),
                );
            }
        }

        return $fields;
    }

    protected function previewSnippet($value, $max = 220)
    {
        $value = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));
        if (Tools::strlen($value) > $max) {
            $value = Tools::substr($value, 0, $max) . '…';
        }

        return $value;
    }

    protected function estimateCredit($maxTokens)
    {
        $estimate = ((float) $maxTokens / 1000) * 0.02;

        return number_format($estimate, 4, '.', '');
    }

    protected function parseIds($raw)
    {
        $ids = preg_split('/[^0-9]+/', (string) $raw);
        $ids = array_filter(array_map('intval', $ids));

        return array_values(array_unique($ids));
    }
}