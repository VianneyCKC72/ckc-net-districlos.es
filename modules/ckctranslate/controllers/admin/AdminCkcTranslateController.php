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
        // Anciennes files JSON dans le cookie BO → exception PrestaShop (limite 4096 octets).
        $this->purgeLegacyQueueCookies();

        parent::initContent();

        if (!Configuration::get(CkcTranslate::CONF_PROMPT_BRAND_REVIEWS)) {
            Configuration::updateValue(
                CkcTranslate::CONF_PROMPT_BRAND_REVIEWS,
                'Translate customer review texts naturally. Keep the tone authentic and personal. Do not invent details. Preserve product/brand names.'
            );
        }
        if (!Configuration::get(CkcTranslate::CONF_PROMPT_ORDER_STATES)) {
            Configuration::updateValue(
                CkcTranslate::CONF_PROMPT_ORDER_STATES,
                'Translate order status names for an e-commerce storefront. Keep them short, clear and professional. Preserve any leading reference prefixes like "0 - " if present.'
            );
        }
        if (!Configuration::get(CkcTranslate::CONF_PROMPT_EVERCONTENTSEO)) {
            Configuration::updateValue(
                CkcTranslate::CONF_PROMPT_EVERCONTENTSEO,
                'Translate Ever Content SEO HTML blocks (category/manufacturer/supplier SEO footers). Preserve all HTML tags, attributes, links and images. Translate only visible text. Keep a concise SEO tone. Do not invent products, prices or promotions.'
            );
        }
        if (!Configuration::get(CkcTranslate::CONF_PROMPT_FEATURES)) {
            Configuration::updateValue(
                CkcTranslate::CONF_PROMPT_FEATURES,
                'Translate product feature names and feature values for an e-commerce catalog. Keep labels short, accurate and consistent. Preserve units, numbers, codes and brand/model names when they must stay unchanged.'
            );
        }
        if (!Configuration::get(CkcTranslate::CONF_PROMPT_ATTRIBUTES)) {
            Configuration::updateValue(
                CkcTranslate::CONF_PROMPT_ATTRIBUTES,
                'Translate product attribute group names, public names and attribute values (color, size, etc.). Keep them short for product pages and filters. Preserve codes, measurements and brand-specific terms when needed.'
            );
        }
        if (!Configuration::get(CkcTranslate::CONF_PROMPT_CARRIER_DELAYS)) {
            Configuration::updateValue(
                CkcTranslate::CONF_PROMPT_CARRIER_DELAYS,
                'Translate carrier delivery delay texts for checkout and shipping estimators. Keep them short and clear. Preserve numbers, time units and carrier brand names. Do not invent delivery promises.'
            );
        }
        if (!Configuration::get(CkcTranslate::CONF_PROMPT_CKC_LABELS)) {
            Configuration::updateValue(
                CkcTranslate::CONF_PROMPT_CKC_LABELS,
                'Translate short product badge labels. Preserve HTML tags (<strong>, <b>, <br>), CSS classes and the ↓ character. Keep the result short, similar in length, suitable for a product card flag. Do not invent promotions.'
            );
        }

        if (!Configuration::get(CkcTranslate::CONF_PROMPT_HELLOWORK_JOBS)) {
            Configuration::updateValue(
                CkcTranslate::CONF_PROMPT_HELLOWORK_JOBS,
                'Translate job offer titles and descriptions for a careers page. Keep a professional recruitment tone. Preserve HTML tags. Do not invent benefits, salaries or locations. Keep H/F and similar gender marks when present, adapted to English (M/F).'
            );
        }
        if (!Configuration::get(CkcTranslate::CONF_PROMPT_ETS_CFU)) {
            Configuration::updateValue(
                CkcTranslate::CONF_PROMPT_ETS_CFU,
                'Translate Contact Form Ultimate (ets_cfu) texts: form titles, URL aliases, SEO, descriptions, field labels, placeholders, help texts, dropdown/radio options, submit buttons, thank-you pages and notification emails. Preserve HTML tags, attributes, links, emails, phone numbers and brand names. If a field is JSON, keep the JSON structure and translate string values only. Keep URL aliases slug-safe. Do not invent legal clauses.'
            );
        }
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
            $jobCode = (string) Tools::getValue('run_job');
            if (Tools::getValue('fresh_queue')) {
                $this->resetJobQueue($jobCode);
                // Rebuild queue against missing translations (new entities / source copies).
                Configuration::set(CkcTranslate::CONF_ONLY_MISSING_FIELDS, 1);
            }
            $this->runJob($jobCode);

            return;
        }

        if (Tools::getValue('ckc_debug_ce')) {
            $this->debugCreativeElements();

            return;
        }

        $this->context->smarty->assign(array(
            'form_html' => $this->renderFormConfig(),
            'jobs' => $this->getJobsWithCounters(),
            'untranslated_rows' => $this->getUntranslatedRows(),
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
        $promptReassurance = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_REASSURANCE));
        $promptBrandReviews = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_BRAND_REVIEWS));
        $promptOrderStates = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_ORDER_STATES));
        $promptEverContentSeo = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_EVERCONTENTSEO));
        $promptFeatures = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_FEATURES));
        $promptAttributes = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_ATTRIBUTES));
        $promptCarrierDelays = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_CARRIER_DELAYS));
        $promptCkcLabels = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_CKC_LABELS));
        $promptHelloworkJobs = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_HELLOWORK_JOBS));
        $promptEtsCfu = trim((string) Tools::getValue(CkcTranslate::CONF_PROMPT_ETS_CFU));
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
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_REASSURANCE, $promptReassurance);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_BRAND_REVIEWS, $promptBrandReviews);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_ORDER_STATES, $promptOrderStates);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_EVERCONTENTSEO, $promptEverContentSeo);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_FEATURES, $promptFeatures);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_ATTRIBUTES, $promptAttributes);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_CARRIER_DELAYS, $promptCarrierDelays);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_CKC_LABELS, $promptCkcLabels);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_HELLOWORK_JOBS, $promptHelloworkJobs);
        Configuration::updateValue(CkcTranslate::CONF_PROMPT_ETS_CFU, $promptEtsCfu);
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
                        'name' => CkcTranslate::CONF_PROMPT_REASSURANCE,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For blockreassurance items (delivery, payment, warranty, etc.).'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Homepage reviews'),
                        'name' => CkcTranslate::CONF_PROMPT_BRAND_REVIEWS,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For CKC Product Reviews carousel texts (Avis Vérifiés / Skeepers).'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Order statuses'),
                        'name' => CkcTranslate::CONF_PROMPT_ORDER_STATES,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For order status names displayed in customer account and emails.'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Ever Content SEO'),
                        'name' => CkcTranslate::CONF_PROMPT_EVERCONTENTSEO,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For Ever Content SEO HTML blocks on all clones (everpscontentseo, everpscontentseo3, …) — categories, manufacturers, suppliers.'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Features'),
                        'name' => CkcTranslate::CONF_PROMPT_FEATURES,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For product feature names and feature values (caractéristiques).'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Attributes'),
                        'name' => CkcTranslate::CONF_PROMPT_ATTRIBUTES,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For attribute groups and attribute values (color, size, etc.).'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Carrier delays'),
                        'name' => CkcTranslate::CONF_PROMPT_CARRIER_DELAYS,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For carrier delivery delay texts (checkout + shipping estimator).'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Product labels (ckc_label)'),
                        'name' => CkcTranslate::CONF_PROMPT_CKC_LABELS,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For product card badge texts. Preserve HTML and the down-arrow.'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - HelloWork jobs'),
                        'name' => CkcTranslate::CONF_PROMPT_HELLOWORK_JOBS,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For job offer titles and descriptions (dtc_feedhelloworkjob).'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Prompt - Contact Form Ultimate (ets_cfu)'),
                        'name' => CkcTranslate::CONF_PROMPT_ETS_CFU,
                        'rows' => 4,
                        'cols' => 60,
                        'desc' => $this->module->l('For all multilingual Contact Form Ultimate fields: forms, inputs, options, emails, thank-you pages.'),
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
            CkcTranslate::CONF_PROMPT_REASSURANCE => Configuration::get(CkcTranslate::CONF_PROMPT_REASSURANCE),
            CkcTranslate::CONF_PROMPT_BRAND_REVIEWS => Configuration::get(CkcTranslate::CONF_PROMPT_BRAND_REVIEWS)
                ?: 'Translate customer review texts naturally. Keep the tone authentic and personal. Do not invent details. Preserve product/brand names.',
            CkcTranslate::CONF_PROMPT_ORDER_STATES => Configuration::get(CkcTranslate::CONF_PROMPT_ORDER_STATES)
                ?: 'Translate order status names for an e-commerce storefront. Keep them short, clear and professional. Preserve any leading reference prefixes like "0 - " if present.',
            CkcTranslate::CONF_PROMPT_EVERCONTENTSEO => Configuration::get(CkcTranslate::CONF_PROMPT_EVERCONTENTSEO)
                ?: 'Translate Ever Content SEO HTML blocks (category/manufacturer/supplier SEO footers). Preserve all HTML tags, attributes, links and images. Translate only visible text. Keep a concise SEO tone. Do not invent products, prices or promotions.',
            CkcTranslate::CONF_PROMPT_FEATURES => Configuration::get(CkcTranslate::CONF_PROMPT_FEATURES)
                ?: 'Translate product feature names and feature values for an e-commerce catalog. Keep labels short, accurate and consistent. Preserve units, numbers, codes and brand/model names when they must stay unchanged.',
            CkcTranslate::CONF_PROMPT_ATTRIBUTES => Configuration::get(CkcTranslate::CONF_PROMPT_ATTRIBUTES)
                ?: 'Translate product attribute group names, public names and attribute values (color, size, etc.). Keep them short for product pages and filters. Preserve codes, measurements and brand-specific terms when needed.',
            CkcTranslate::CONF_PROMPT_CARRIER_DELAYS => Configuration::get(CkcTranslate::CONF_PROMPT_CARRIER_DELAYS),
            CkcTranslate::CONF_PROMPT_CKC_LABELS => Configuration::get(CkcTranslate::CONF_PROMPT_CKC_LABELS)
                ?: 'Translate carrier delivery delay texts for checkout and shipping estimators. Keep them short and clear. Preserve numbers, time units and carrier brand names. Do not invent delivery promises.',
            CkcTranslate::CONF_PROMPT_HELLOWORK_JOBS => Configuration::get(CkcTranslate::CONF_PROMPT_HELLOWORK_JOBS),
            CkcTranslate::CONF_PROMPT_ETS_CFU => Configuration::get(CkcTranslate::CONF_PROMPT_ETS_CFU),
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
            array(
                'code' => 'evercontentseo',
                'label' => $this->module->l('Translate Ever Content SEO (all everpscontentseo* clones)'),
                'advice' => $this->getEverContentSeoJobAdvice(),
            ),
            array(
                'code' => 'features',
                'label' => $this->module->l('Translate features (caractéristiques)'),
                'advice' => $this->module->l('Translates feature names and feature values. Filter by IDs = id_feature.'),
            ),
            array(
                'code' => 'attributes',
                'label' => $this->module->l('Translate attributes'),
                'advice' => $this->module->l('Translates attribute groups (name + public name) and attribute values. Filter by IDs = id_attribute_group.'),
            ),
            array(
                'code' => 'carrier_delays',
                'label' => $this->module->l('Translate carrier delays'),
                'advice' => $this->module->l('Translates carrier_lang.delay texts shown at checkout and in the shipping estimator. Filter by IDs = id_carrier.'),
            ),
            array(
                'code' => 'brand_reviews',
                'label' => $this->module->l('Translate homepage reviews (CKC Product Reviews)'),
                'advice' => $this->module->l('Translates brand/homepage carousel reviews. New reviews synced from Avis Vérifiés appear here until translated.'),
            ),
            array(
                'code' => 'order_states',
                'label' => $this->module->l('Translate order statuses'),
                'advice' => $this->module->l('Translates order status names shown in FO account/history and used in status emails.'),
            ),
            array(
                'code' => 'ckc_labels',
                'label' => $this->module->l('Translate product labels (ckc_label)'),
                'advice' => $this->module->l('Translates badge texts (PRIX DEGRESSIF, etc.) into the target language. Requires ckc_label.'),
            ),
            array(
                'code' => 'hellowork_jobs',
                'label' => $this->module->l('Translate HelloWork job offers'),
                'advice' => $this->module->l('Translates job titles and descriptions stored after HelloWork sync. Requires dtc_feedhelloworkjob.'),
            ),
            array(
                'code' => 'ets_cfu',
                'label' => $this->module->l('Translate Contact Form Ultimate (ets_cfu)'),
                'advice' => $this->module->l('Translates all multilingual fields: form titles, aliases, SEO, labels, placeholders, options, emails and thank-you pages. Filter by IDs = form ID. Requires ets_cfultimate.'),
            ),
        );
    }

    protected function getJobsWithCounters()
    {
        $jobs = $this->getJobs();
        $runner = $this->createJobRunner(false);

        foreach ($jobs as &$job) {
            $job['total'] = 0;
            $job['remaining'] = 0;
            $job['untranslated'] = 0;

            if (!isset($job['code'])) {
                continue;
            }

            $counters = $this->getJobCounters($job['code'], $runner);
            $job['total'] = isset($counters['total']) ? (int) $counters['total'] : 0;
            $job['remaining'] = isset($counters['remaining']) ? (int) $counters['remaining'] : 0;
            $job['untranslated'] = isset($counters['untranslated'])
                ? (int) $counters['untranslated']
                : (int) $job['remaining'];
        }
        unset($job);

        return $jobs;
    }

    /**
     * Rows for the "untranslated entities" table (new / missing translations).
     *
     * @return array
     */
    protected function getUntranslatedRows()
    {
        $rows = array();
        $runner = $this->createJobRunner(false);

        foreach ($this->getJobs() as $job) {
            if (empty($job['code'])) {
                continue;
            }

            $untranslated = 0;
            try {
                $untranslated = (int) $runner->countUntranslated($job['code']);
            } catch (Throwable $e) {
                $untranslated = 0;
            }

            $rows[] = array(
                'code' => $job['code'],
                'label' => $job['label'],
                'advice' => isset($job['advice']) ? $job['advice'] : '',
                'untranslated' => $untranslated,
                'run_url' => self::$currentIndex
                    . '&token=' . $this->token
                    . '&run_job=' . urlencode($job['code'])
                    . '&fresh_queue=1',
            );
        }

        return $rows;
    }

    /**
     * @param bool $withAi
     *
     * @return CkcTranslateJobRunner
     */
    protected function createJobRunner($withAi = true)
    {
        $ai = new CkcTranslateAi(
            $withAi ? Configuration::get(CkcTranslate::CONF_API_KEY) : '',
            Configuration::get(CkcTranslate::CONF_MODEL),
            (float) Configuration::get(CkcTranslate::CONF_TEMPERATURE),
            (int) Configuration::get(CkcTranslate::CONF_MAX_TOKENS),
            Configuration::get(CkcTranslate::CONF_PROMPT)
        );

        return new CkcTranslateJobRunner(
            $this->module,
            $ai,
            (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG),
            (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG)
        );
    }

    protected function resetJobQueue($jobCode)
    {
        $queueName = CkcTranslateJobRunner::jobCodeToQueueName($jobCode);
        CkcTranslateJobRunner::resetFileQueue($queueName);
    }

    protected function getJobCounters($jobCode, $runner = null)
    {
        $fileCounters = CkcTranslateJobRunner::getFileQueueCounters(
            CkcTranslateJobRunner::jobCodeToQueueName($jobCode)
        );
        if (is_array($fileCounters)) {
            $fileCounters['untranslated'] = isset($fileCounters['remaining'])
                ? (int) $fileCounters['remaining']
                : 0;

            return $fileCounters;
        }

        if (!$runner instanceof CkcTranslateJobRunner) {
            $runner = $this->createJobRunner(false);
        }

        $untranslated = 0;
        try {
            $untranslated = (int) $runner->countUntranslated($jobCode);
        } catch (Throwable $e) {
            $untranslated = 0;
        }

        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $filterCategoryId = (int) Configuration::get(CkcTranslate::CONF_FILTER_CATEGORY_ID);
        $onlyMissing = (int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS);

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
                break;

            case 'category_seo':
            case 'category_description':
                $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'category`
                        WHERE active = 1 AND id_parent != 0';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND id_category IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                $total = (int) Db::getInstance()->getValue($sql);
                break;

            case 'cms':
                $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'cms` WHERE active = 1';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND id_cms IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                $total = (int) Db::getInstance()->getValue($sql);
                break;

            case 'creative_elements':
                $sourceLangId = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
                $sql = 'SELECT COUNT(DISTINCT cl.id_ce_theme)
                        FROM `' . _DB_PREFIX_ . 'ce_theme_lang` cl
                        WHERE cl.id_lang = ' . $sourceLangId . '
                        AND cl.content IS NOT NULL
                        AND cl.content != \'\'';

                $total = (int) Db::getInstance()->getValue($sql);
                break;

            case 'ce_meta':
                $sourceLangId = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
                $langPad = sprintf('%02d', $sourceLangId);
                $sql = 'SELECT COUNT(DISTINCT id)
                        FROM `' . _DB_PREFIX_ . 'ce_meta`
                        WHERE `name` = \'_elementor_data\'
                        AND SUBSTRING(CAST(id AS CHAR), LENGTH(CAST(id AS CHAR))-3, 2) = \'' . pSQL($langPad) . '\'';

                $total = (int) Db::getInstance()->getValue($sql);
                break;

            case 'reassurance':
                $total = (int) Db::getInstance()->getValue(
                    'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'psreassurance` WHERE status = 1'
                );
                break;

            case 'evercontentseo':
                $total = $this->countEverContentSeoItems();
                break;

            case 'features':
                $total = $this->countFeaturesItems();
                break;

            case 'attributes':
                $total = $this->countAttributesItems();
                break;

            case 'carrier_delays':
                $shopId = (int) $this->context->shop->id;
                $sourceLangId = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
                $sql = 'SELECT COUNT(*)
                        FROM `' . _DB_PREFIX_ . 'carrier` c
                        INNER JOIN `' . _DB_PREFIX_ . 'carrier_lang` cl
                          ON cl.id_carrier = c.id_carrier
                         AND cl.id_lang = ' . (int) $sourceLangId . '
                         AND cl.id_shop = ' . (int) $shopId . '
                        WHERE c.deleted = 0
                          AND cl.delay IS NOT NULL
                          AND TRIM(cl.delay) != \'\'';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND c.id_carrier IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                $total = (int) Db::getInstance()->getValue($sql);
                break;

            case 'brand_reviews':
                $this->ensureBrandReviewsTables();
                $total = (int) Db::getInstance()->getValue(
                    'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ckc_brand_review`'
                );
                break;

            case 'order_states':
                $total = (int) Db::getInstance()->getValue(
                    'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'order_state` WHERE deleted = 0'
                );
                break;

            case 'ckc_labels':
                $total = 0;
                if (Module::isInstalled('ckc_label')) {
                    $total = (int) Db::getInstance()->getValue(
                        'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ckc_labels`'
                    );
                }
                break;

            case 'hellowork_jobs':
                $total = 0;
                if (Module::isInstalled('dtc_feedhelloworkjob')) {
                    $total = (int) Db::getInstance()->getValue(
                        'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ckc_hellowork_job`'
                    );
                }
                break;

            case 'ets_cfu':
                $total = 0;
                if ($runner instanceof CkcTranslateJobRunner) {
                    $total = (int) $runner->countEtsCfuItems();
                }
                break;

            default:
                return array(
                    'total' => 0,
                    'remaining' => 0,
                    'untranslated' => 0,
                );
        }

        $remaining = $onlyMissing ? $untranslated : $total;

        return array(
            'total' => (int) $total,
            'remaining' => (int) $remaining,
            'untranslated' => (int) $untranslated,
        );
    }

    protected function ensureBrandReviewsTables()
    {
        $module = Module::getInstanceByName('ckcproductreviews');
        if (!$module || !Validate::isLoadedObject($module)) {
            return;
        }
        if (method_exists($module, 'installSql')) {
            $module->installSql();
        }
        if (method_exists($module, 'syncFromCacheFile')) {
            $module->syncFromCacheFile();
        }
    }

    protected function getEverContentSeoJobAdvice()
    {
        $modules = CkcTranslateJobRunner::discoverEverContentSeoModules();
        $list = !empty($modules) ? implode(', ', $modules) : '—';

        return sprintf(
            $this->module->l('Translates SEO HTML blocks for every Ever Content SEO clone — categories, manufacturers and suppliers. Detected: %s. Category filters apply to category contents only. Reset the queue after upgrading so all modules are included.', 'AdminCkcTranslateController'),
            $list
        );
    }

    protected function countEverContentSeoItems($onlyMissing = false)
    {
        $shopId = (int) $this->context->shop->id;
        $sourceLangId = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
        $targetLangId = (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG);
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $filterCategoryId = (int) Configuration::get(CkcTranslate::CONF_FILTER_CATEGORY_ID);

        $total = 0;
        $db = Db::getInstance();
        $types = array('category', 'manufacturer', 'supplier');

        foreach (CkcTranslateJobRunner::discoverEverContentSeoModules() as $module) {
            foreach ($types as $type) {
                $meta = CkcTranslateJobRunner::getEverContentSeoTypeMetaForModule($module, $type);
                if (!$meta) {
                    continue;
                }

                if (!(bool) $db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . $meta['table'] . '"')) {
                    continue;
                }

                $sql = 'SELECT COUNT(*)
                        FROM `' . _DB_PREFIX_ . $meta['table'] . '` c
                        INNER JOIN `' . _DB_PREFIX_ . $meta['lang_table'] . '` src
                          ON src.`' . $meta['id_column'] . '` = c.`' . $meta['id_column'] . '`
                         AND src.id_lang = ' . (int) $sourceLangId . '
                        WHERE src.content IS NOT NULL
                          AND TRIM(src.content) != \'\'
                          AND c.id_shop = ' . (int) $shopId;

                if ($type === 'category') {
                    if ($filterType === 'ids' && !empty($filterIds)) {
                        $sql .= ' AND c.`' . $meta['ref_column'] . '` IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                    }
                    if ($filterType === 'category' && $filterCategoryId > 0) {
                        $sql .= ' AND c.`' . $meta['ref_column'] . '` = ' . (int) $filterCategoryId;
                    }
                }

                if ($onlyMissing && $targetLangId > 0) {
                    $sql .= ' AND (
                        NOT EXISTS (
                            SELECT 1 FROM `' . _DB_PREFIX_ . $meta['lang_table'] . '` tgt
                            WHERE tgt.`' . $meta['id_column'] . '` = c.`' . $meta['id_column'] . '`
                              AND tgt.id_lang = ' . (int) $targetLangId . '
                              AND tgt.content IS NOT NULL
                              AND TRIM(tgt.content) != \'\'
                              AND tgt.content != src.content
                        )
                    )';
                }

                $total += (int) $db->getValue($sql);
            }
        }

        return $total;
    }

    protected function countFeaturesItems($onlyMissing = false)
    {
        $sourceLangId = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
        $targetLangId = (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG);
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $db = Db::getInstance();

        $featureSql = 'SELECT COUNT(*)
                       FROM `' . _DB_PREFIX_ . 'feature` f
                       INNER JOIN `' . _DB_PREFIX_ . 'feature_lang` src
                         ON src.id_feature = f.id_feature
                        AND src.id_lang = ' . (int) $sourceLangId . '
                       WHERE src.name IS NOT NULL
                         AND TRIM(src.name) != \'\'';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $featureSql .= ' AND f.id_feature IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        if ($onlyMissing && $targetLangId > 0) {
            $featureSql .= ' AND NOT EXISTS (
                SELECT 1 FROM `' . _DB_PREFIX_ . 'feature_lang` tgt
                WHERE tgt.id_feature = f.id_feature
                  AND tgt.id_lang = ' . (int) $targetLangId . '
                  AND tgt.name IS NOT NULL
                  AND TRIM(tgt.name) != \'\'
                  AND tgt.name != src.name
            )';
        }

        $valueSql = 'SELECT COUNT(*)
                     FROM `' . _DB_PREFIX_ . 'feature_value` fv
                     INNER JOIN `' . _DB_PREFIX_ . 'feature_value_lang` src
                       ON src.id_feature_value = fv.id_feature_value
                      AND src.id_lang = ' . (int) $sourceLangId . '
                     WHERE src.value IS NOT NULL
                       AND TRIM(src.value) != \'\'';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $valueSql .= ' AND fv.id_feature IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        if ($onlyMissing && $targetLangId > 0) {
            $valueSql .= ' AND NOT EXISTS (
                SELECT 1 FROM `' . _DB_PREFIX_ . 'feature_value_lang` tgt
                WHERE tgt.id_feature_value = fv.id_feature_value
                  AND tgt.id_lang = ' . (int) $targetLangId . '
                  AND tgt.value IS NOT NULL
                  AND TRIM(tgt.value) != \'\'
                  AND tgt.value != src.value
            )';
        }

        return (int) $db->getValue($featureSql) + (int) $db->getValue($valueSql);
    }

    protected function countAttributesItems($onlyMissing = false)
    {
        $sourceLangId = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
        $targetLangId = (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG);
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $db = Db::getInstance();

        $groupSql = 'SELECT COUNT(*)
                     FROM `' . _DB_PREFIX_ . 'attribute_group` ag
                     INNER JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` src
                       ON src.id_attribute_group = ag.id_attribute_group
                      AND src.id_lang = ' . (int) $sourceLangId . '
                     WHERE (
                        (src.name IS NOT NULL AND TRIM(src.name) != \'\')
                        OR (src.public_name IS NOT NULL AND TRIM(src.public_name) != \'\')
                     )';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $groupSql .= ' AND ag.id_attribute_group IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        if ($onlyMissing && $targetLangId > 0) {
            $groupSql .= ' AND NOT EXISTS (
                SELECT 1 FROM `' . _DB_PREFIX_ . 'attribute_group_lang` tgt
                WHERE tgt.id_attribute_group = ag.id_attribute_group
                  AND tgt.id_lang = ' . (int) $targetLangId . '
                  AND tgt.name IS NOT NULL AND TRIM(tgt.name) != \'\' AND tgt.name != src.name
                  AND tgt.public_name IS NOT NULL AND TRIM(tgt.public_name) != \'\' AND tgt.public_name != src.public_name
            )';
        }

        $attrSql = 'SELECT COUNT(*)
                    FROM `' . _DB_PREFIX_ . 'attribute` a
                    INNER JOIN `' . _DB_PREFIX_ . 'attribute_lang` src
                      ON src.id_attribute = a.id_attribute
                     AND src.id_lang = ' . (int) $sourceLangId . '
                    WHERE src.name IS NOT NULL
                      AND TRIM(src.name) != \'\'';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $attrSql .= ' AND a.id_attribute_group IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        if ($onlyMissing && $targetLangId > 0) {
            $attrSql .= ' AND NOT EXISTS (
                SELECT 1 FROM `' . _DB_PREFIX_ . 'attribute_lang` tgt
                WHERE tgt.id_attribute = a.id_attribute
                  AND tgt.id_lang = ' . (int) $targetLangId . '
                  AND tgt.name IS NOT NULL
                  AND TRIM(tgt.name) != \'\'
                  AND tgt.name != src.name
            )';
        }

        return (int) $db->getValue($groupSql) + (int) $db->getValue($attrSql);
    }

    protected function resetQueues()
    {
        CkcTranslateJobRunner::resetAllFileQueues();

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
            'ckctranslate_evercontentseo_ids',
            'ckctranslate_evercontentseo_index',
            'ckctranslate_features_ids',
            'ckctranslate_features_index',
            'ckctranslate_attributes_ids',
            'ckctranslate_attributes_index',
            'ckctranslate_carrier_delays_ids',
            'ckctranslate_carrier_delays_index',
            'ckctranslate_brand_reviews_ids',
            'ckctranslate_brand_reviews_index',
            'ckctranslate_order_states_ids',
            'ckctranslate_order_states_index',
            'ckctranslate_ckc_labels_ids',
            'ckctranslate_ckc_labels_index',
            'ckctranslate_hellowork_jobs_ids',
            'ckctranslate_hellowork_jobs_index',
            'ckctranslate_ets_cfu_ids',
            'ckctranslate_ets_cfu_index',
        );

        foreach ($keys as $key) {
            unset($this->context->cookie->{$key});
        }

        Tools::clearAllCache();
        $this->confirmations[] = $this->module->l('Translation queues have been reset.');
    }

    protected function purgeLegacyQueueCookies()
    {
        if (!isset($this->context->cookie) || !is_object($this->context->cookie)) {
            return;
        }

        $cookie = $this->context->cookie;
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
            'ckctranslate_evercontentseo_ids',
            'ckctranslate_evercontentseo_index',
            'ckctranslate_features_ids',
            'ckctranslate_features_index',
            'ckctranslate_attributes_ids',
            'ckctranslate_attributes_index',
            'ckctranslate_carrier_delays_ids',
            'ckctranslate_carrier_delays_index',
            'ckctranslate_brand_reviews_ids',
            'ckctranslate_brand_reviews_index',
            'ckctranslate_order_states_ids',
            'ckctranslate_order_states_index',
            'ckctranslate_ckc_labels_ids',
            'ckctranslate_ckc_labels_index',
            'ckctranslate_hellowork_jobs_ids',
            'ckctranslate_hellowork_jobs_index',
            'ckctranslate_ets_cfu_ids',
            'ckctranslate_ets_cfu_index',
        );

        foreach ($keys as $key) {
            unset($cookie->{$key});
        }
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