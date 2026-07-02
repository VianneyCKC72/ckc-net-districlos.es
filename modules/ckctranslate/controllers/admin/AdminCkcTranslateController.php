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

        if (Tools::isSubmit('submitCkcClearCeCache')) {
            $this->clearCeElementCache();
        }

        if (Tools::getValue('run_job')) {
            $this->runJob((string) Tools::getValue('run_job'));

            return;
        }

        if (Tools::getValue('ckc_debug_ce')) {
            $this->debugCreativeElements();

            return;
        }

        $this->context->smarty->assign(array(
            'form_html' => $this->renderFormConfig(),
            'jobs' => $this->getJobsWithCounters(),
            'current_url' => self::$currentIndex . '&token=' . $this->token,
            'history_rows' => $this->getHistoryRows(),
            'prompt_example' => $this->getAdvancedPromptExample(),
            'estimated_batch_cost' => $this->getEstimatedBatchCost(),
            'available_models' => $this->getAvailableModels(),
        ));

        $this->setTemplate('configure.tpl');
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
        $promptCe = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_CE));
        $promptReassurance = trim((string) Tools::getValue('CKCTRANSLATE_PROMPT_REASSURANCE'));
        $filterType = trim((string) Tools::getValue(CkcTranslate::CONF_FILTER_TYPE));
        $filterIds = trim((string) Tools::getValue(CkcTranslate::CONF_FILTER_IDS));
        $filterCategoryId = (int) Tools::getValue(CkcTranslate::CONF_FILTER_CATEGORY_ID);

        if ($sourceLang <= 0 || $targetLang <= 0) {
            $this->errors[] = $this->module->l('Please select valid source and target languages.');
            return;
        }

        if ($sourceLang === $targetLang) {
            $this->errors[] = $this->module->l('Source and target languages must be different.');
            return;
        }

        if ($temperature < 0 || $temperature > 1) {
            $this->errors[] = $this->module->l('Temperature must be between 0 and 1.');
            return;
        }

        if ($maxTokens < 100) {
            $this->errors[] = $this->module->l('Max tokens must be at least 100.');
            return;
        }

        if ($batchSize < 1) {
            $this->errors[] = $this->module->l('Batch size must be at least 1.');
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
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_CE, $promptCe);
        Configuration::updateValue('CKCTRANSLATE_PROMPT_REASSURANCE', $promptReassurance);
        Configuration::updateValue(CkcTranslate::CONF_FILTER_TYPE, $filterType);
        Configuration::updateValue(CkcTranslate::CONF_FILTER_IDS, $filterIds);
        Configuration::updateValue(CkcTranslate::CONF_FILTER_CATEGORY_ID, $filterCategoryId);

        $this->confirmations[] = $this->module->l('Settings updated successfully.');
    }

    protected function renderFormConfig()
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
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
                        'desc' => $this->module->l('Paste your API key here. Never hardcode it in source files.'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->module->l('Source language'),
                        'name' => CkcTranslate::CONF_SOURCE_LANG,
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
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Creative Elements'),
                        'name' => CkcTranslate::CONF_PROMPT_CE,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For page builder blocks (ps_ce_content_lang). Increase max tokens to 3000+ for large pages.'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Reassurance blocks'),
                        'name' => 'CKCTRANSLATE_PROMPT_REASSURANCE',
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For blockreassurance items (delivery, payment, warranty, etc.).'),
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
                        'desc' => $this->module->l('Recommended: 0.2 to 0.4 for stable SEO translations.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Max tokens'),
                        'name' => CkcTranslate::CONF_MAX_TOKENS,
                        'desc' => $this->module->l('Recommended: 800 to 1500 depending on content length.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Estimated credit / request'),
                        'name' => CkcTranslate::CONF_ESTIMATED_CREDIT,
                        'readonly' => true,
                        'desc' => $this->module->l('Approximate estimate calculated from the token limit.'),
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
            CkcTranslate::CONF_SOURCE_LANG => (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG),
            CkcTranslate::CONF_TARGET_LANG => (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG),
            CkcTranslate::CONF_MODEL => Configuration::get(CkcTranslate::CONF_MODEL),
            CkcTranslate::CONF_PROMPT => Configuration::get(CkcTranslate::CONF_PROMPT),
            CkcTranslate::CONF_TEMPERATURE => Configuration::get(CkcTranslate::CONF_TEMPERATURE),
            CkcTranslate::CONF_MAX_TOKENS => Configuration::get(CkcTranslate::CONF_MAX_TOKENS),
            CkcTranslate::CONF_ESTIMATED_CREDIT => Configuration::get(CkcTranslate::CONF_ESTIMATED_CREDIT),
            CkcTranslate::CONF_BATCH_SIZE => (int) Configuration::get(CkcTranslate::CONF_BATCH_SIZE),
            CkcTranslate::CONF_TARGET_COUNTRY => Configuration::get(CkcTranslate::CONF_TARGET_COUNTRY),
            CkcTranslate::CONF_COUNTRY_KEYWORDS => Configuration::get(CkcTranslate::CONF_COUNTRY_KEYWORDS),
            CkcTranslate::CONF_STYLE_GUIDELINES => Configuration::get(CkcTranslate::CONF_STYLE_GUIDELINES),
            CkcTranslate::CONF_RESTRICTIONS => Configuration::get(CkcTranslate::CONF_RESTRICTIONS),
            CkcTranslate::CONF_ONLY_MISSING_FIELDS => (int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS),
            CkcTranslate::CONF_GLOSSARY => Configuration::get(CkcTranslate::CONF_GLOSSARY),
            CkcTranslate::CONF_SEO_META_TITLE_MAX => (int) Configuration::get(CkcTranslate::CONF_SEO_META_TITLE_MAX),
            CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX => (int) Configuration::get(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX),
            CkcTranslate::CONF_SEO_SLUG_MAX => (int) Configuration::get(CkcTranslate::CONF_SEO_SLUG_MAX),
            CkcTranslate::CONF_PROMPT_PRODUCT_SEO => Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_SEO),
            CkcTranslate::CONF_PROMPT_PRODUCT_DESCRIPTION => Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_DESCRIPTION),
            CkcTranslate::CONF_PROMPT_CATEGORY_SEO => Configuration::get(CkcTranslate::CONF_PROMPT_CATEGORY_SEO),
            CkcTranslate::CONF_PROMPT_CATEGORY_DESCRIPTION => Configuration::get(CkcTranslate::CONF_PROMPT_CATEGORY_DESCRIPTION),
            CkcTranslate::CONF_PROMPT_CMS => Configuration::get(CkcTranslate::CONF_PROMPT_CMS),
            CkcTranslate::CONF_PROMPT_CE => Configuration::get(CkcTranslate::CONF_PROMPT_CE),
            'CKCTRANSLATE_PROMPT_REASSURANCE' => Configuration::get('CKCTRANSLATE_PROMPT_REASSURANCE'),
            CkcTranslate::CONF_FILTER_TYPE => Configuration::get(CkcTranslate::CONF_FILTER_TYPE),
            CkcTranslate::CONF_FILTER_IDS => Configuration::get(CkcTranslate::CONF_FILTER_IDS),
            CkcTranslate::CONF_FILTER_CATEGORY_ID => (int) Configuration::get(CkcTranslate::CONF_FILTER_CATEGORY_ID),
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

        try {
            $result = $runner->run($job);
        } catch (Throwable $e) {
            $result = array(
                'success' => false,
                'message' => 'PHP Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                'finished' => true,
            );
        }

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
        return array(
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
            array(
                'code' => 'creative_elements',
                'label' => $this->module->l('Translate Creative Elements themes (ce_theme_lang)'),
                'advice' => $this->module->l('Translates global theme HTML sections (header/footer builder blocks).'),
            ),
            array(
                'code' => 'ce_meta',
                'label' => $this->module->l('Translate Creative Elements pages (ce_meta JSON)'),
                'advice' => $this->module->l('Translates page-specific Elementor JSON content (homepage, product pages, etc.). Run this to fix blank pages in the target language.'),
            ),
            array(
                'code' => 'reassurance',
                'label' => $this->module->l('Translate reassurance blocks (blockreassurance)'),
                'advice' => $this->module->l('Translates title and description of reassurance items (delivery, payment, warranty, etc.).'),
            ),
        );
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

            case 'creative_elements':
                $sourceLangId = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
                $sql = 'SELECT COUNT(DISTINCT cl.id_ce_theme)
                        FROM `' . _DB_PREFIX_ . 'ce_theme_lang` cl
                        WHERE cl.id_lang = ' . $sourceLangId . '
                        AND cl.content IS NOT NULL
                        AND cl.content != \'\'';

                $total = (int) Db::getInstance()->getValue($sql);
                $cookieKey = 'ckctranslate_ce_ids';
                $indexKey = 'ckctranslate_ce_index';
                break;

            case 'ce_meta':
                $sourceLangId = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
                $langPad = sprintf('%02d', $sourceLangId);
                $sql = 'SELECT COUNT(DISTINCT id)
                        FROM `' . _DB_PREFIX_ . 'ce_meta`
                        WHERE `name` = \'_elementor_data\'
                        AND SUBSTRING(CAST(id AS CHAR), LENGTH(CAST(id AS CHAR))-3, 2) = \'' . pSQL($langPad) . '\'';

                $total = (int) Db::getInstance()->getValue($sql);
                $cookieKey = 'ckctranslate_cemeta_ids';
                $indexKey = 'ckctranslate_cemeta_index';
                break;

            case 'reassurance':
                $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'psreassurance` WHERE status = 1';

                $total = (int) Db::getInstance()->getValue($sql);
                $cookieKey = 'ckctranslate_reassurance_ids';
                $indexKey = 'ckctranslate_reassurance_index';
                break;

            default:
                return array(
                    'total' => 0,
                    'remaining' => 0,
                );
        }

        $cookie = $this->context->cookie;

        if (isset($cookie->{$cookieKey}) && isset($cookie->{$indexKey})) {
            $ids = json_decode($cookie->{$cookieKey}, true);
            $index = (int) $cookie->{$indexKey};

            if (is_array($ids)) {
                return array(
                    'total' => count($ids),
                    'remaining' => max(count($ids) - $index, 0),
                );
            }
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
            'ckctranslate_ce_ids',
            'ckctranslate_ce_index',
            'ckctranslate_cemeta_ids',
            'ckctranslate_cemeta_index',
            'ckctranslate_reassurance_ids',
            'ckctranslate_reassurance_index',
        );

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

    protected function clearCeElementCache()
    {
        $targetLangId = (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG);
        $langPad = sprintf('%02d', $targetLangId);
        $deleted = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ce_meta`
             WHERE `name` = \'_ce_element_cache\'
             AND SUBSTRING(CAST(id AS CHAR), LENGTH(CAST(id AS CHAR))-3, 2) = \'' . pSQL($langPad) . '\''
        );
        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'ce_meta`
             WHERE `name` = \'_ce_element_cache\'
             AND SUBSTRING(CAST(id AS CHAR), LENGTH(CAST(id AS CHAR))-3, 2) = \'' . pSQL($langPad) . '\''
        );
        $this->confirmations[] = sprintf(
            $this->module->l('CE element cache cleared for target language (lang_id=%d). %d entries deleted.'),
            $targetLangId,
            $deleted
        );
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

    protected function debugCreativeElements()
    {
        $sourceLangId = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
        $db = Db::getInstance();
        $prefix = _DB_PREFIX_;

        $out = '<div style="font-family:monospace;font-size:13px;padding:20px;background:#f5f5f5">';
        $out .= '<h2>CKC Translate — Debug Creative Elements</h2>';
        $out .= '<p><strong>Source lang ID (config):</strong> ' . $sourceLangId . '</p>';
        $out .= '<p><strong>DB prefix:</strong> ' . $prefix . '</p>';

        // Check all CE tables
        $tables = array('ce_content', 'ce_content_lang', 'ce_content_shop', 'ce_theme', 'ce_theme_lang', 'ce_theme_shop', 'ce_meta', 'ce_revision', 'ce_template');
        $out .= '<h3>Toutes les tables CE</h3><ul>';
        foreach ($tables as $t) {
            $full = $prefix . $t;
            $exists = $db->getValue("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . pSQL($full) . "'");
            $count = $exists ? (int) $db->getValue('SELECT COUNT(*) FROM `' . $full . '`') : '—';
            $color = ($count > 0) ? 'green' : 'inherit';
            $out .= '<li><strong>' . $full . '</strong> : ' . ($exists ? 'EXISTS' : '<span style="color:red">MISSING</span>') . ' — <span style="color:' . $color . '">' . $count . ' rows</span></li>';
        }
        $out .= '</ul>';

        // ce_theme_lang details
        $themeRows = $db->executeS('SELECT id_lang, COUNT(*) as nb FROM `' . $prefix . 'ce_theme_lang` GROUP BY id_lang');
        $out .= '<h3>' . $prefix . 'ce_theme_lang — par langue</h3>';
        if ($themeRows) {
            $out .= '<table border="1" cellpadding="4"><tr><th>id_lang</th><th>rows</th><th>content non-vide</th></tr>';
            foreach ($themeRows as $lr) {
                $nonEmpty = (int) $db->getValue('SELECT COUNT(*) FROM `' . $prefix . 'ce_theme_lang` WHERE id_lang = ' . (int) $lr['id_lang'] . ' AND content IS NOT NULL AND content != \'\'');
                $out .= '<tr><td>' . $lr['id_lang'] . '</td><td>' . $lr['nb'] . '</td><td>' . $nonEmpty . '</td></tr>';
            }
            $out .= '</table>';
            $samples = $db->executeS('SELECT id_ce_theme, id_lang, id_shop, title, LEFT(content, 150) as preview FROM `' . $prefix . 'ce_theme_lang` LIMIT 5');
            $out .= '<table border="1" cellpadding="4"><tr><th>id_ce_theme</th><th>id_lang</th><th>id_shop</th><th>title</th><th>content preview</th></tr>';
            foreach ($samples as $s) {
                $out .= '<tr><td>' . $s['id_ce_theme'] . '</td><td>' . $s['id_lang'] . '</td><td>' . $s['id_shop'] . '</td><td>' . htmlspecialchars($s['title']) . '</td><td>' . htmlspecialchars($s['preview']) . '</td></tr>';
            }
            $out .= '</table>';
        } else {
            $out .= '<p style="color:orange">Vide.</p>';
        }

        // ce_meta details
        $metaRows = $db->executeS('SELECT `name`, COUNT(*) as nb FROM `' . $prefix . 'ce_meta` GROUP BY `name` ORDER BY nb DESC LIMIT 10');
        $out .= '<h3>' . $prefix . 'ce_meta — par clé (top 10)</h3>';
        if ($metaRows) {
            $out .= '<table border="1" cellpadding="4"><tr><th>name (clé)</th><th>rows</th></tr>';
            foreach ($metaRows as $mr) {
                $out .= '<tr><td>' . htmlspecialchars($mr['name']) . '</td><td>' . $mr['nb'] . '</td></tr>';
            }
            $out .= '</table>';
            $metaSample = $db->executeS('SELECT id, `name`, LEFT(`value`, 200) as val_preview FROM `' . $prefix . 'ce_meta` LIMIT 3');
            $out .= '<p><strong>Exemples de lignes :</strong></p>';
            $out .= '<table border="1" cellpadding="4"><tr><th>id</th><th>name</th><th>value (200 chars)</th></tr>';
            foreach ($metaSample as $ms) {
                $out .= '<tr><td>' . $ms['id'] . '</td><td>' . htmlspecialchars($ms['name']) . '</td><td>' . htmlspecialchars($ms['val_preview']) . '</td></tr>';
            }
            $out .= '</table>';
        } else {
            $out .= '<p style="color:orange">Vide.</p>';
        }

        // Final count — ce_theme_lang
        $countSql = 'SELECT COUNT(DISTINCT cl.id_ce_theme) FROM `' . $prefix . 'ce_theme_lang` cl WHERE cl.id_lang = ' . $sourceLangId . ' AND cl.content IS NOT NULL AND cl.content != \'\'';
        $finalCount = (int) $db->getValue($countSql);
        $out .= '<h3>Compteur CE → ce_theme_lang (source lang ' . $sourceLangId . ')</h3>';
        $out .= '<p><code>' . htmlspecialchars($countSql) . '</code></p>';
        $out .= '<p><strong>Résultat : ' . $finalCount . '</strong></p>';

        // ce_meta — lang breakdown from UID
        $langPad = sprintf('%02d', $sourceLangId);
        $out .= '<h3>' . $prefix . 'ce_meta — analyse UIDs (_elementor_data)</h3>';
        $sourceMetaCount = (int) $db->getValue(
            'SELECT COUNT(*) FROM `' . $prefix . 'ce_meta`
             WHERE `name` = \'_elementor_data\'
             AND SUBSTRING(CAST(id AS CHAR), LENGTH(CAST(id AS CHAR))-3, 2) = \'' . pSQL($langPad) . '\''
        );
        $out .= '<p>Entries _elementor_data avec lang source (' . $sourceLangId . ') : <strong>' . $sourceMetaCount . '</strong></p>';

        // Sample UIDs for source lang
        $sampleUids = $db->executeS(
            'SELECT id FROM `' . $prefix . 'ce_meta`
             WHERE `name` = \'_elementor_data\'
             AND SUBSTRING(CAST(id AS CHAR), LENGTH(CAST(id AS CHAR))-3, 2) = \'' . pSQL($langPad) . '\'
             ORDER BY id ASC LIMIT 10'
        );
        if ($sampleUids) {
            $targetLangId = (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG);
            $targetLangPad = sprintf('%02d', $targetLangId);
            $out .= '<table border="1" cellpadding="4"><tr><th>source UID</th><th>decoded (page|type|lang|shop)</th><th>target UID</th><th>target exists?</th></tr>';
            foreach ($sampleUids as $row) {
                $uid = (string) $row['id'];
                $decodedLang = substr($uid, -4, 2);
                $decodedShop = substr($uid, -2);
                $decodedType = substr($uid, -6, 2);
                $decodedPage = substr($uid, 0, -6);
                $targetUid = substr($uid, 0, -4) . $targetLangPad . substr($uid, -2);
                $targetExists = (int) $db->getValue(
                    'SELECT COUNT(*) FROM `' . $prefix . 'ce_meta`
                     WHERE id = ' . (int) $targetUid . ' AND `name` = \'_elementor_data\''
                );
                $out .= '<tr>'
                    . '<td>' . $uid . '</td>'
                    . '<td>page=' . $decodedPage . ' type=' . $decodedType . ' lang=' . $decodedLang . ' shop=' . $decodedShop . '</td>'
                    . '<td>' . $targetUid . '</td>'
                    . '<td style="color:' . ($targetExists ? 'green' : 'red') . '">' . ($targetExists ? 'OUI' : 'NON') . '</td>'
                    . '</tr>';
            }
            $out .= '</table>';
        }

        // Deep JSON inspection — first source entry
        if ($sampleUids) {
            $firstUid = (int) $sampleUids[0]['id'];
            $jsonRows = $db->executeS(
                'SELECT `value` FROM `' . $prefix . 'ce_meta`
                 WHERE id = ' . $firstUid . ' AND `name` = \'_elementor_data\'
                 LIMIT 1'
            );
            if ($jsonRows && !empty($jsonRows[0]['value'])) {
                $elements = json_decode($jsonRows[0]['value'], true);
                $out .= '<h3>Structure JSON (premier UID ' . $firstUid . ')</h3>';
                if (is_array($elements)) {
                    $out .= '<p>Nombre d\'éléments racine : <strong>' . count($elements) . '</strong></p>';
                    $firstEl = $elements[0] ?? array();
                    $out .= '<p>Clés du premier élément : <code>' . htmlspecialchars(implode(', ', array_keys($firstEl))) . '</code></p>';
                    $elType = $firstEl['elType'] ?? '(manquant)';
                    $out .= '<p>elType du premier élément : <strong>' . htmlspecialchars($elType) . '</strong></p>';
                    // Count elType values at all levels
                    $elTypes = array();
                    array_walk_recursive($elements, function ($val, $key) use (&$elTypes) {
                        if ($key === 'elType') {
                            $elTypes[$val] = ($elTypes[$val] ?? 0) + 1;
                        }
                    });
                    $out .= '<p>elType présents : <code>' . htmlspecialchars(json_encode($elTypes)) . '</code></p>';
                    // Count widgetType values
                    $widgetTypes = array();
                    array_walk_recursive($elements, function ($val, $key) use (&$widgetTypes) {
                        if ($key === 'widgetType') {
                            $widgetTypes[$val] = ($widgetTypes[$val] ?? 0) + 1;
                        }
                    });
                    $out .= '<p>widgetType présents : <code>' . htmlspecialchars(json_encode($widgetTypes)) . '</code></p>';
                    // Show raw JSON preview
                    $out .= '<details><summary>JSON brut (1500 chars)</summary><pre>' . htmlspecialchars(substr($jsonRows[0]['value'], 0, 1500)) . '</pre></details>';
                } else {
                    $out .= '<p style="color:red">json_decode() a échoué.</p>';
                }
            }
        }

        $backUrl = self::$currentIndex . '&token=' . $this->token;
        $out .= '<p><a href="' . htmlspecialchars($backUrl) . '">← Retour</a></p>';
        $out .= '</div>';

        echo $out;
        exit;
    }
}