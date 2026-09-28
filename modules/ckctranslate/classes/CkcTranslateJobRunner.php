<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/CkcTranslateCfuI18n.php';

class CkcTranslateJobRunner
{
    /** @var Module */
    protected $module;

    /** @var CkcTranslateAi */
    protected $ai;

    /** @var int */
    protected $sourceLangId;

    /** @var int */
    protected $targetLangId;

    public function __construct(Module $module, CkcTranslateAi $ai, $sourceLangId, $targetLangId)
    {
        $this->module = $module;
        $this->ai = $ai;
        $this->sourceLangId = (int) $sourceLangId;
        $this->targetLangId = (int) $targetLangId;
    }

    public function run($job)
    {
        $batchSize = (int) Configuration::get(CkcTranslate::CONF_BATCH_SIZE);
        if ($batchSize < 1) {
            $batchSize = 1;
        }

        $processed = 0;
        $lastResult = array(
            'success' => true,
            'message' => $this->module->l('Nothing processed.', 'CkcTranslateJobRunner'),
            'finished' => false,
        );

        while ($processed < $batchSize) {
            switch ($job) {
                case 'product_seo':
                    $result = $this->runProductSeo();
                    break;

                case 'product_description':
                    $result = $this->runProductDescription();
                    break;

                case 'category_seo':
                    $result = $this->runCategorySeo();
                    break;

                case 'category_description':
                    $result = $this->runCategoryDescription();
                    break;

                case 'cms':
                    $result = $this->runCms();
                    break;

                case 'creative_elements':
                    $result = $this->runCreativeElements();
                    break;

                case 'ce_meta':
                    $result = $this->runCreativeElementsMeta();
                    break;

                case 'reassurance':
                    $result = $this->runReassurance();
                    break;

                case 'evercontentseo':
                    $result = $this->runEverContentSeo();
                    break;

                case 'features':
                    $result = $this->runFeatures();
                    break;

                case 'attributes':
                    $result = $this->runAttributes();
                    break;

                case 'brand_reviews':
                    $result = $this->runBrandReviews();
                    break;

                case 'order_states':
                    $result = $this->runOrderStates();
                    break;

                case 'carrier_delays':
                    $result = $this->runCarrierDelays();
                    break;

                case 'ckc_labels':
                    $result = $this->runCkcLabels();
                    break;

                case 'hellowork_jobs':
                    $result = $this->runHelloworkJobs();
                    break;

                case 'ets_cfu':
                    $result = $this->runEtsCfu();
                    break;

                case 'ckc_realisation':
                    $result = $this->runCkcRealisation();
                    break;

                case 'responsivegallery':
                    $result = $this->runResponsiveGallery();
                    break;

                case 'product_faq':
                    $result = $this->runProductFaq();
                    break;

                case 'product_guide':
                    $result = $this->runProductGuide();
                    break;

                case 'system_pages':
                    $result = $this->runSystemPages();
                    break;

                default:
                    return array(
                        'success' => false,
                        'message' => $this->module->l('Unknown job.', 'CkcTranslateJobRunner'),
                        'finished' => true,
                    );
            }

            $lastResult = $result;
            $processed++;

            if (!empty($result['finished'])) {
                break;
            }
        }

        $lastResult['message'] .= ' ' . sprintf(
                $this->module->l('Processed in this launch: %d item(s).', 'CkcTranslateJobRunner'),
                (int) $processed
            );

        return $lastResult;
    }

    protected function runProductSeo()
    {
        $filtered = $this->getFilteredSql('product_seo');

        $id = $this->getNextId(
            'ckctranslate_product_seo_ids',
            'ckctranslate_product_seo_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Product SEO translation finished.', 'CkcTranslateJobRunner'));
        }

        $product = new Product((int) $id, false, $this->sourceLangId);
        $targetProduct = new Product((int) $id, false, $this->targetLangId);

        if (!Validate::isLoadedObject($product)) {
            $this->logHistory('product_seo', 'product', (int) $id, 'skipped', $this->module->l('Invalid product.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid product #%d skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        if ($this->shouldSkipFilledFields($targetProduct, array('name', 'meta_title', 'meta_description', 'meta_keywords', 'link_rewrite'), $product)) {
            $this->logHistory('product_seo', 'product', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Product #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are an expert e-commerce SEO translator. Translate product SEO fields naturally and cleanly.',
            'product_seo'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "name": "",
  "meta_title": "",
  "meta_description": "",
  "meta_keywords": "",
  "slug": ""
}

Source product name:
{$product->name}

Source meta title:
{$product->meta_title}

Source meta description:
{$product->meta_description}

Source meta keywords:
{$product->meta_keywords}

Source slug:
{$product->link_rewrite}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('name', 'meta_title', 'meta_description', 'meta_keywords', 'slug'))) {
            $this->logHistory('product_seo', 'product', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for product #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $metaTitle = $this->normalizeSeoText($data['meta_title'], (int) Configuration::get(CkcTranslate::CONF_SEO_META_TITLE_MAX));
        $metaDescription = $this->normalizeSeoText($data['meta_description'], (int) Configuration::get(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX));
        $slug = $this->normalizeSlug($data['slug']);

        Db::getInstance()->update(
            'product_lang',
            array(
                'name' => pSQL($data['name']),
                'meta_title' => pSQL($metaTitle),
                'meta_description' => pSQL($metaDescription),
                'meta_keywords' => pSQL($data['meta_keywords']),
                'link_rewrite' => pSQL($slug),
            ),
            'id_product = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
        );

        Db::getInstance()->update(
            'product',
            array('date_upd' => date('Y-m-d H:i:s')),
            'id_product = ' . (int) $id
        );

        $this->logHistory('product_seo', 'product', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Product #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    protected function runProductDescription()
    {
        $filtered = $this->getFilteredSql('product_description');

        $id = $this->getNextId(
            'ckctranslate_product_desc_ids',
            'ckctranslate_product_desc_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Product descriptions translation finished.', 'CkcTranslateJobRunner'));
        }

        $product = new Product((int) $id, false, $this->sourceLangId);
        $targetProduct = new Product((int) $id, false, $this->targetLangId);

        if (!Validate::isLoadedObject($product)) {
            $this->logHistory('product_description', 'product', (int) $id, 'skipped', $this->module->l('Invalid product.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid product #%d skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        if ($this->shouldSkipFilledFields($targetProduct, array('description_short', 'description'), $product)) {
            $this->logHistory('product_description', 'product', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Product descriptions #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are an expert premium e-commerce copywriter. Translate descriptions while preserving meaning and HTML.',
            'product_description'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "description_short": "",
  "description": ""
}

Source short description:
{$product->description_short}

Source long description:
{$product->description}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('description_short', 'description'))) {
            $this->logHistory('product_description', 'product', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for product #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        Db::getInstance()->update(
            'product_lang',
            array(
                'description_short' => pSQL($data['description_short'], true),
                'description' => pSQL($data['description'], true),
            ),
            'id_product = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
        );

        Db::getInstance()->update(
            'product',
            array('date_upd' => date('Y-m-d H:i:s')),
            'id_product = ' . (int) $id
        );

        $this->logHistory('product_description', 'product', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Product descriptions #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    protected function runCategorySeo()
    {
        $filtered = $this->getFilteredSql('category_seo');

        $id = $this->getNextId(
            'ckctranslate_category_seo_ids',
            'ckctranslate_category_seo_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Category SEO translation finished.', 'CkcTranslateJobRunner'));
        }

        $category = new Category((int) $id, $this->sourceLangId);
        $targetCategory = new Category((int) $id, $this->targetLangId);

        if (!Validate::isLoadedObject($category)) {
            $this->logHistory('category_seo', 'category', (int) $id, 'skipped', $this->module->l('Invalid category.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid category #%d skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        if ($this->shouldSkipFilledFields($targetCategory, array('name', 'meta_title', 'meta_description', 'link_rewrite'), $category)) {
            $this->logHistory('category_seo', 'category', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Category #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are an expert category SEO translator for e-commerce. Keep it concise and natural.',
            'category_seo'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "name": "",
  "meta_title": "",
  "meta_description": "",
  "slug": ""
}

Source category name:
{$category->name}

Source meta title:
{$category->meta_title}

Source meta description:
{$category->meta_description}

Source slug:
{$category->link_rewrite}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('name', 'meta_title', 'meta_description', 'slug'))) {
            $this->logHistory('category_seo', 'category', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for category #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $metaTitle = $this->normalizeSeoText($data['meta_title'], (int) Configuration::get(CkcTranslate::CONF_SEO_META_TITLE_MAX));
        $metaDescription = $this->normalizeSeoText($data['meta_description'], (int) Configuration::get(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX));
        $slug = $this->normalizeSlug($data['slug']);

        Db::getInstance()->update(
            'category_lang',
            array(
                'name' => pSQL($data['name']),
                'meta_title' => pSQL($metaTitle),
                'meta_description' => pSQL($metaDescription),
                'link_rewrite' => pSQL($slug),
            ),
            'id_category = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
        );

        Db::getInstance()->update(
            'category',
            array('date_upd' => date('Y-m-d H:i:s')),
            'id_category = ' . (int) $id
        );

        $this->logHistory('category_seo', 'category', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Category #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    protected function runCategoryDescription()
    {
        $filtered = $this->getFilteredSql('category_description');

        $id = $this->getNextId(
            'ckctranslate_category_desc_ids',
            'ckctranslate_category_desc_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Category descriptions translation finished.', 'CkcTranslateJobRunner'));
        }

        $category = new Category((int) $id, $this->sourceLangId);
        $targetCategory = new Category((int) $id, $this->targetLangId);

        if (!Validate::isLoadedObject($category)) {
            $this->logHistory('category_description', 'category', (int) $id, 'skipped', $this->module->l('Invalid category.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid category #%d skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        if ($this->shouldSkipFilledFields($targetCategory, array('description', 'additional_description'), $category)) {
            $this->logHistory('category_description', 'category', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Category descriptions #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are an expert category content translator. Respect HTML and preserve meaning.',
            'category_description'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "description": "",
  "additional_description": ""
}

Source description:
{$category->description}

Source additional description:
{$category->additional_description}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('description', 'additional_description'))) {
            $this->logHistory('category_description', 'category', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for category #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        Db::getInstance()->update(
            'category_lang',
            array(
                'description' => pSQL($data['description'], true),
                'additional_description' => pSQL($data['additional_description'], true),
            ),
            'id_category = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
        );

        Db::getInstance()->update(
            'category',
            array('date_upd' => date('Y-m-d H:i:s')),
            'id_category = ' . (int) $id
        );

        $this->logHistory('category_description', 'category', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Category descriptions #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    protected function runCms()
    {
        $filtered = $this->getFilteredSql('cms');

        $id = $this->getNextId(
            'ckctranslate_cms_ids',
            'ckctranslate_cms_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('CMS translation finished.', 'CkcTranslateJobRunner'));
        }

        $cms = new CMS((int) $id, $this->sourceLangId);
        $targetCms = new CMS((int) $id, $this->targetLangId);

        if (!Validate::isLoadedObject($cms)) {
            $this->logHistory('cms', 'cms', (int) $id, 'skipped', $this->module->l('Invalid CMS.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid CMS #%d skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        if ($this->shouldSkipFilledFields($targetCms, array('meta_title', 'meta_description', 'meta_keywords', 'content', 'link_rewrite'), $cms)) {
            $this->logHistory('cms', 'cms', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('CMS #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are an expert CMS translator for editorial e-commerce pages. Keep it elegant and SEO-friendly.',
            'cms'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "meta_title": "",
  "meta_description": "",
  "meta_keywords": "",
  "content": "",
  "slug": ""
}

Source meta title:
{$cms->meta_title}

Source meta description:
{$cms->meta_description}

Source content:
{$cms->content}

Source slug:
{$cms->link_rewrite}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('meta_title', 'meta_description', 'meta_keywords', 'content', 'slug'))) {
            $this->logHistory('cms', 'cms', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for CMS #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $metaTitle = $this->normalizeSeoText($data['meta_title'], (int) Configuration::get(CkcTranslate::CONF_SEO_META_TITLE_MAX));
        $metaDescription = $this->normalizeSeoText($data['meta_description'], (int) Configuration::get(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX));
        $slug = $this->normalizeSlug($data['slug']);

        Db::getInstance()->update(
            'cms_lang',
            array(
                'meta_title' => pSQL($metaTitle),
                'meta_description' => pSQL($metaDescription),
                'meta_keywords' => pSQL($data['meta_keywords']),
                'content' => pSQL($data['content'], true),
                'link_rewrite' => pSQL($slug),
            ),
            'id_cms = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
        );

        $this->logHistory('cms', 'cms', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('CMS #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    protected function buildSystemPrompt($basePrompt, $jobCode = '')
    {
        $country = (string) Configuration::get(CkcTranslate::CONF_TARGET_COUNTRY);
        $keywords = (string) Configuration::get(CkcTranslate::CONF_COUNTRY_KEYWORDS);
        $guidelines = (string) Configuration::get(CkcTranslate::CONF_STYLE_GUIDELINES);
        $restrictions = (string) Configuration::get(CkcTranslate::CONF_RESTRICTIONS);
        $glossary = (string) Configuration::get(CkcTranslate::CONF_GLOSSARY);
        $metaTitleMax = (int) Configuration::get(CkcTranslate::CONF_SEO_META_TITLE_MAX);
        $metaDescriptionMax = (int) Configuration::get(CkcTranslate::CONF_SEO_META_DESCRIPTION_MAX);
        $slugMax = (int) Configuration::get(CkcTranslate::CONF_SEO_SLUG_MAX);
        if ($slugMax < 32) {
            $slugMax = 80;
        }
        if ($metaTitleMax < 30) {
            $metaTitleMax = 60;
        }
        if ($metaDescriptionMax < 50) {
            $metaDescriptionMax = 155;
        }
        $jobPrompt = $this->getJobSpecificPrompt($jobCode);

        // CKC Vianney — pages système : limites Meta PS (128/255), pas le plafond produit.
        if ($jobCode === 'system_pages') {
            $metaTitleMax = 128;
            $metaDescriptionMax = 255;
        }

        return trim(
            $basePrompt
            . "\n\nJob-specific prompt: " . $jobPrompt
            . "\nTarget country: " . $country
            . "\nSuggested local keywords: " . $keywords
            . "\nEditorial guidelines: " . $guidelines
            . "\nRestrictions: " . $restrictions
            . "\nGlossary rules: " . $glossary
            . "\nSEO rules:"
            . "\n- Meta title max length: " . $metaTitleMax
            . "\n- Meta description max length: " . $metaDescriptionMax
            . "\n- Slug max length: " . $slugMax
        );
    }

    protected function getJobSpecificPrompt($jobCode)
    {
        switch ($jobCode) {
            case 'product_seo':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_SEO);

            case 'product_description':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_DESCRIPTION);

            case 'category_seo':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_CATEGORY_SEO);

            case 'category_description':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_CATEGORY_DESCRIPTION);

            case 'cms':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_CMS);

            case 'creative_elements':
            case 'ce_meta':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_CE);

            case 'reassurance':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_REASSURANCE);

            case 'evercontentseo':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_EVERCONTENTSEO);

            case 'features':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_FEATURES);

            case 'attributes':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_ATTRIBUTES);

            case 'brand_reviews':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_BRAND_REVIEWS);

            case 'order_states':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_ORDER_STATES);

            case 'carrier_delays':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_CARRIER_DELAYS);

            case 'ckc_labels':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_CKC_LABELS);

            case 'hellowork_jobs':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_HELLOWORK_JOBS);

            case 'ets_cfu':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_ETS_CFU);

            case 'ckc_realisation':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_CKC_REALISATION);

            case 'responsivegallery':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_RESPONSIVEGALLERY);

            case 'product_faq':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_FAQ);

            case 'product_guide':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_PRODUCT_GUIDE);

            case 'system_pages':
                return (string) Configuration::get(CkcTranslate::CONF_PROMPT_SYSTEM_PAGES);

            default:
                return '';
        }
    }

    /**
     * Count entities still needing translation for a job (respects filters + only-missing).
     *
     * @param string $jobCode
     * @param bool   $forceOnlyMissing Always count missing/untranslated even if config is off
     *
     * @return int
     */
    public function countUntranslated($jobCode, $forceOnlyMissing = true)
    {
        switch ($jobCode) {
            case 'evercontentseo':
                return $this->countEverContentSeoMissing($forceOnlyMissing);

            case 'features':
                return $this->countFeaturesMissing($forceOnlyMissing);

            case 'attributes':
                return $this->countAttributesMissing($forceOnlyMissing);

            case 'ets_cfu':
                return count($this->buildEtsCfuQueueItems($forceOnlyMissing));

            case 'responsivegallery':
                return count($this->buildResponsiveGalleryQueueItems($forceOnlyMissing));

            default:
                $filtered = $this->getFilteredSql($jobCode, $forceOnlyMissing);
                if (empty($filtered['sql']) || empty($filtered['column'])) {
                    return 0;
                }

                $rows = Db::getInstance()->executeS($filtered['sql']);

                return is_array($rows) ? count($rows) : 0;
        }
    }

    protected function countEverContentSeoMissing($onlyMissing = true)
    {
        if (!$onlyMissing) {
            return count($this->buildEverContentSeoQueue());
        }

        $items = array();
        $shopId = (int) Context::getContext()->shop->id;
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $filterCategoryId = (int) Configuration::get(CkcTranslate::CONF_FILTER_CATEGORY_ID);
        $types = array('category', 'manufacturer', 'supplier');

        foreach (self::discoverEverContentSeoModules() as $module) {
            foreach ($types as $type) {
                $meta = self::getEverContentSeoTypeMetaForModule($module, $type);
                if (!$meta) {
                    continue;
                }
                if (!(bool) Db::getInstance()->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . $meta['table'] . '"')) {
                    continue;
                }

                $sql = 'SELECT COUNT(*)
                        FROM `' . _DB_PREFIX_ . $meta['table'] . '` c
                        INNER JOIN `' . _DB_PREFIX_ . $meta['lang_table'] . '` src
                          ON src.`' . $meta['id_column'] . '` = c.`' . $meta['id_column'] . '`
                         AND src.id_lang = ' . (int) $this->sourceLangId . '
                        WHERE src.content IS NOT NULL
                          AND TRIM(src.content) != \'\'
                          AND c.id_shop = ' . (int) $shopId . '
                          AND NOT EXISTS (
                            SELECT 1 FROM `' . _DB_PREFIX_ . $meta['lang_table'] . '` tgt
                            WHERE tgt.`' . $meta['id_column'] . '` = c.`' . $meta['id_column'] . '`
                              AND tgt.id_lang = ' . (int) $this->targetLangId . '
                              AND tgt.content IS NOT NULL
                              AND TRIM(tgt.content) != \'\'
                              AND tgt.content != src.content
                          )';

                if ($type === 'category') {
                    if ($filterType === 'ids' && !empty($filterIds)) {
                        $sql .= ' AND c.`' . $meta['ref_column'] . '` IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                    }
                    if ($filterType === 'category' && $filterCategoryId > 0) {
                        $sql .= ' AND c.`' . $meta['ref_column'] . '` = ' . (int) $filterCategoryId;
                    }
                }

                $items[] = (int) Db::getInstance()->getValue($sql);
            }
        }

        return array_sum($items);
    }

    protected function countFeaturesMissing($onlyMissing = true)
    {
        if (!$onlyMissing) {
            return count($this->buildFeaturesQueue());
        }

        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $db = Db::getInstance();

        $featureSql = 'SELECT COUNT(*)
                       FROM `' . _DB_PREFIX_ . 'feature` f
                       INNER JOIN `' . _DB_PREFIX_ . 'feature_lang` src
                         ON src.id_feature = f.id_feature
                        AND src.id_lang = ' . (int) $this->sourceLangId . '
                       WHERE src.name IS NOT NULL
                         AND TRIM(src.name) != \'\'
                         AND NOT EXISTS (
                            SELECT 1 FROM `' . _DB_PREFIX_ . 'feature_lang` tgt
                            WHERE tgt.id_feature = f.id_feature
                              AND tgt.id_lang = ' . (int) $this->targetLangId . '
                              AND tgt.name IS NOT NULL
                              AND TRIM(tgt.name) != \'\'
                              AND tgt.name != src.name
                         )';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $featureSql .= ' AND f.id_feature IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        $valueSql = 'SELECT COUNT(*)
                     FROM `' . _DB_PREFIX_ . 'feature_value` fv
                     INNER JOIN `' . _DB_PREFIX_ . 'feature_value_lang` src
                       ON src.id_feature_value = fv.id_feature_value
                      AND src.id_lang = ' . (int) $this->sourceLangId . '
                     WHERE src.value IS NOT NULL
                       AND TRIM(src.value) != \'\'
                       AND NOT EXISTS (
                          SELECT 1 FROM `' . _DB_PREFIX_ . 'feature_value_lang` tgt
                          WHERE tgt.id_feature_value = fv.id_feature_value
                            AND tgt.id_lang = ' . (int) $this->targetLangId . '
                            AND tgt.value IS NOT NULL
                            AND TRIM(tgt.value) != \'\'
                            AND tgt.value != src.value
                       )';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $valueSql .= ' AND fv.id_feature IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        return (int) $db->getValue($featureSql) + (int) $db->getValue($valueSql);
    }

    protected function countAttributesMissing($onlyMissing = true)
    {
        if (!$onlyMissing) {
            return count($this->buildAttributesQueue());
        }

        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $db = Db::getInstance();

        $groupSql = 'SELECT COUNT(*)
                     FROM `' . _DB_PREFIX_ . 'attribute_group` ag
                     INNER JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` src
                       ON src.id_attribute_group = ag.id_attribute_group
                      AND src.id_lang = ' . (int) $this->sourceLangId . '
                     WHERE (
                        (src.name IS NOT NULL AND TRIM(src.name) != \'\')
                        OR (src.public_name IS NOT NULL AND TRIM(src.public_name) != \'\')
                     )
                     AND NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . 'attribute_group_lang` tgt
                        WHERE tgt.id_attribute_group = ag.id_attribute_group
                          AND tgt.id_lang = ' . (int) $this->targetLangId . '
                          AND tgt.name IS NOT NULL AND TRIM(tgt.name) != \'\' AND tgt.name != src.name
                          AND tgt.public_name IS NOT NULL AND TRIM(tgt.public_name) != \'\' AND tgt.public_name != src.public_name
                     )';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $groupSql .= ' AND ag.id_attribute_group IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        $attrSql = 'SELECT COUNT(*)
                    FROM `' . _DB_PREFIX_ . 'attribute` a
                    INNER JOIN `' . _DB_PREFIX_ . 'attribute_lang` src
                      ON src.id_attribute = a.id_attribute
                     AND src.id_lang = ' . (int) $this->sourceLangId . '
                    WHERE src.name IS NOT NULL
                      AND TRIM(src.name) != \'\'
                      AND NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . 'attribute_lang` tgt
                        WHERE tgt.id_attribute = a.id_attribute
                          AND tgt.id_lang = ' . (int) $this->targetLangId . '
                          AND tgt.name IS NOT NULL
                          AND TRIM(tgt.name) != \'\'
                          AND tgt.name != src.name
                      )';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $attrSql .= ' AND a.id_attribute_group IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        return (int) $db->getValue($groupSql) + (int) $db->getValue($attrSql);
    }

    protected function getFilteredSql($jobCode, $forceOnlyMissing = null)
    {
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $filterCategoryId = (int) Configuration::get(CkcTranslate::CONF_FILTER_CATEGORY_ID);
        if ($forceOnlyMissing === null) {
            $onlyMissing = (bool) (int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS);
        } else {
            $onlyMissing = (bool) $forceOnlyMissing;
        }
        $shopId = (int) Context::getContext()->shop->id;

        switch ($jobCode) {
            case 'product_seo':
            case 'product_description':
                $sql = 'SELECT DISTINCT p.id_product
                        FROM ' . _DB_PREFIX_ . 'product p';

                if ($filterType === 'category' && $filterCategoryId > 0) {
                    $sql .= ' INNER JOIN ' . _DB_PREFIX_ . 'category_product cp ON cp.id_product = p.id_product';
                }

                if ($onlyMissing) {
                    $sql .= ' INNER JOIN `' . _DB_PREFIX_ . 'product_lang` src
                                ON src.id_product = p.id_product
                               AND src.id_lang = ' . (int) $this->sourceLangId . '
                               AND src.id_shop = ' . (int) $shopId . '
                              LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` tgt
                                ON tgt.id_product = p.id_product
                               AND tgt.id_lang = ' . (int) $this->targetLangId . '
                               AND tgt.id_shop = ' . (int) $shopId;
                }

                $sql .= ' WHERE p.active = 1';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND p.id_product IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($filterType === 'category' && $filterCategoryId > 0) {
                    $sql .= ' AND cp.id_category = ' . (int) $filterCategoryId;
                }

                if ($onlyMissing) {
                    if ($jobCode === 'product_seo') {
                        $sql .= ' AND (
                            tgt.id_product IS NULL
                            OR TRIM(IFNULL(tgt.name, \'\')) = \'\'
                            OR TRIM(IFNULL(tgt.meta_title, \'\')) = \'\'
                            OR TRIM(IFNULL(tgt.meta_description, \'\')) = \'\'
                            OR TRIM(IFNULL(tgt.link_rewrite, \'\')) = \'\'
                            OR (TRIM(IFNULL(src.name, \'\')) != \'\' AND tgt.name = src.name)
                            OR (TRIM(IFNULL(src.meta_title, \'\')) != \'\' AND tgt.meta_title = src.meta_title)
                            OR (TRIM(IFNULL(src.link_rewrite, \'\')) != \'\' AND tgt.link_rewrite = src.link_rewrite)
                        )';
                    } else {
                        $sql .= ' AND (
                            tgt.id_product IS NULL
                            OR TRIM(IFNULL(tgt.description, \'\')) = \'\'
                            OR TRIM(IFNULL(tgt.description_short, \'\')) = \'\'
                            OR (TRIM(IFNULL(src.description, \'\')) != \'\' AND tgt.description = src.description)
                            OR (TRIM(IFNULL(src.description_short, \'\')) != \'\' AND tgt.description_short = src.description_short)
                        )';
                    }
                }

                $sql .= ' ORDER BY p.id_product ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_product',
                );

            case 'category_seo':
            case 'category_description':
                $sql = 'SELECT DISTINCT c.id_category
                        FROM ' . _DB_PREFIX_ . 'category c';

                if ($onlyMissing) {
                    $sql .= ' INNER JOIN `' . _DB_PREFIX_ . 'category_lang` src
                                ON src.id_category = c.id_category
                               AND src.id_lang = ' . (int) $this->sourceLangId . '
                               AND src.id_shop = ' . (int) $shopId . '
                              LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` tgt
                                ON tgt.id_category = c.id_category
                               AND tgt.id_lang = ' . (int) $this->targetLangId . '
                               AND tgt.id_shop = ' . (int) $shopId;
                }

                $sql .= ' WHERE c.active = 1
                        AND c.id_parent != 0';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND c.id_category IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($onlyMissing) {
                    if ($jobCode === 'category_seo') {
                        $sql .= ' AND (
                            tgt.id_category IS NULL
                            OR TRIM(IFNULL(tgt.name, \'\')) = \'\'
                            OR TRIM(IFNULL(tgt.meta_title, \'\')) = \'\'
                            OR TRIM(IFNULL(tgt.link_rewrite, \'\')) = \'\'
                            OR (TRIM(IFNULL(src.name, \'\')) != \'\' AND tgt.name = src.name)
                            OR (TRIM(IFNULL(src.meta_title, \'\')) != \'\' AND tgt.meta_title = src.meta_title)
                            OR (TRIM(IFNULL(src.link_rewrite, \'\')) != \'\' AND tgt.link_rewrite = src.link_rewrite)
                        )';
                    } else {
                        $sql .= ' AND (
                            tgt.id_category IS NULL
                            OR TRIM(IFNULL(tgt.description, \'\')) = \'\'
                            OR (TRIM(IFNULL(src.description, \'\')) != \'\' AND tgt.description = src.description)
                        )';
                    }
                }

                $sql .= ' ORDER BY c.id_category ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_category',
                );

            case 'cms':
                $sql = 'SELECT DISTINCT c.id_cms
                        FROM ' . _DB_PREFIX_ . 'cms c';

                if ($onlyMissing) {
                    $sql .= ' INNER JOIN `' . _DB_PREFIX_ . 'cms_lang` src
                                ON src.id_cms = c.id_cms
                               AND src.id_lang = ' . (int) $this->sourceLangId . '
                               AND src.id_shop = ' . (int) $shopId . '
                              LEFT JOIN `' . _DB_PREFIX_ . 'cms_lang` tgt
                                ON tgt.id_cms = c.id_cms
                               AND tgt.id_lang = ' . (int) $this->targetLangId . '
                               AND tgt.id_shop = ' . (int) $shopId;
                }

                $sql .= ' WHERE c.active = 1';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND c.id_cms IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($onlyMissing) {
                    $sql .= ' AND (
                        tgt.id_cms IS NULL
                        OR TRIM(IFNULL(tgt.meta_title, \'\')) = \'\'
                        OR TRIM(IFNULL(tgt.content, \'\')) = \'\'
                        OR TRIM(IFNULL(tgt.link_rewrite, \'\')) = \'\'
                        OR (TRIM(IFNULL(src.meta_title, \'\')) != \'\' AND tgt.meta_title = src.meta_title)
                        OR (TRIM(IFNULL(src.content, \'\')) != \'\' AND tgt.content = src.content)
                        OR (TRIM(IFNULL(src.link_rewrite, \'\')) != \'\' AND tgt.link_rewrite = src.link_rewrite)
                    )';
                }

                $sql .= ' ORDER BY c.id_cms ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_cms',
                );

            case 'creative_elements':
                $sql = 'SELECT DISTINCT cl.id_ce_theme
                        FROM `' . _DB_PREFIX_ . 'ce_theme_lang` cl
                        WHERE cl.id_lang = ' . (int) $this->sourceLangId . '
                        AND cl.content IS NOT NULL
                        AND cl.content != \'\'';

                if ($onlyMissing) {
                    $sql .= ' AND NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . 'ce_theme_lang` tgt
                        WHERE tgt.id_ce_theme = cl.id_ce_theme
                          AND tgt.id_lang = ' . (int) $this->targetLangId . '
                          AND tgt.content IS NOT NULL
                          AND TRIM(tgt.content) != \'\'
                          AND tgt.content != cl.content
                    )';
                }

                $sql .= ' ORDER BY cl.id_ce_theme ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_ce_theme',
                );

            case 'ce_meta':
                $langPad = sprintf('%02d', $this->sourceLangId);
                $targetLangPad = sprintf('%02d', $this->targetLangId);
                $sql = 'SELECT src.id AS source_uid
                        FROM `' . _DB_PREFIX_ . 'ce_meta` src
                        WHERE src.`name` = \'_elementor_data\'
                        AND SUBSTRING(CAST(src.id AS CHAR), LENGTH(CAST(src.id AS CHAR))-3, 2) = \'' . pSQL($langPad) . '\'';

                if ($onlyMissing) {
                    $sql .= ' AND NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . 'ce_meta` tgt
                        WHERE tgt.`name` = \'_elementor_data\'
                          AND tgt.id = CAST(
                            CONCAT(
                              SUBSTRING(CAST(src.id AS CHAR), 1, LENGTH(CAST(src.id AS CHAR)) - 4),
                              \'' . pSQL($targetLangPad) . '\',
                              SUBSTRING(CAST(src.id AS CHAR), -2)
                            ) AS UNSIGNED
                          )
                          AND tgt.value IS NOT NULL
                          AND TRIM(tgt.value) != \'\'
                          AND tgt.value != src.value
                    )';
                }

                $sql .= ' ORDER BY src.id ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'source_uid',
                );

            case 'reassurance':
                $sql = 'SELECT pr.id_psreassurance
                        FROM `' . _DB_PREFIX_ . 'psreassurance` pr
                        WHERE pr.status = 1';

                if ($onlyMissing) {
                    $sql .= ' AND EXISTS (
                        SELECT 1
                        FROM `' . _DB_PREFIX_ . 'psreassurance_lang` src
                        LEFT JOIN `' . _DB_PREFIX_ . 'psreassurance_lang` tgt
                          ON tgt.id_psreassurance = src.id_psreassurance
                         AND tgt.id_lang = ' . (int) $this->targetLangId . '
                        WHERE src.id_psreassurance = pr.id_psreassurance
                          AND src.id_lang = ' . (int) $this->sourceLangId . '
                          AND (
                            tgt.id_psreassurance IS NULL
                            OR TRIM(IFNULL(tgt.title, \'\')) = \'\'
                            OR (TRIM(IFNULL(src.title, \'\')) != \'\' AND tgt.title = src.title)
                            OR (
                              TRIM(IFNULL(src.description, \'\')) != \'\'
                              AND (
                                TRIM(IFNULL(tgt.description, \'\')) = \'\'
                                OR tgt.description = src.description
                              )
                            )
                          )
                    )';
                }

                $sql .= ' ORDER BY pr.position ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_psreassurance',
                );

            case 'brand_reviews':
                $this->ensureBrandReviewsReady();
                $sql = 'SELECT r.id_ckc_brand_review
                        FROM `' . _DB_PREFIX_ . 'ckc_brand_review` r';

                if ($onlyMissing) {
                    $sql .= ' WHERE NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . 'ckc_brand_review_lang` rl
                        WHERE rl.id_ckc_brand_review = r.id_ckc_brand_review
                          AND rl.id_lang = ' . (int) $this->targetLangId . '
                          AND rl.review_content IS NOT NULL
                          AND rl.review_content != \'\'
                    )';
                }

                $sql .= ' ORDER BY r.publish_date DESC, r.id_ckc_brand_review DESC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_ckc_brand_review',
                );

            case 'order_states':
                $sql = 'SELECT os.id_order_state
                        FROM `' . _DB_PREFIX_ . 'order_state` os';

                if ($onlyMissing) {
                    $sql .= ' INNER JOIN `' . _DB_PREFIX_ . 'order_state_lang` src
                                ON src.id_order_state = os.id_order_state
                               AND src.id_lang = ' . (int) $this->sourceLangId . '
                              LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` tgt
                                ON tgt.id_order_state = os.id_order_state
                               AND tgt.id_lang = ' . (int) $this->targetLangId;
                }

                $sql .= ' WHERE os.deleted = 0';

                if ($onlyMissing) {
                    $sql .= ' AND (tgt.name IS NULL OR tgt.name = \'\' OR tgt.name = src.name)';
                }

                $sql .= ' ORDER BY os.id_order_state ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_order_state',
                );

            case 'carrier_delays':
                $sql = 'SELECT c.id_carrier
                        FROM `' . _DB_PREFIX_ . 'carrier` c
                        INNER JOIN `' . _DB_PREFIX_ . 'carrier_lang` cl
                          ON cl.id_carrier = c.id_carrier
                         AND cl.id_lang = ' . (int) $this->sourceLangId . '
                         AND cl.id_shop = ' . (int) $shopId . '
                        WHERE c.deleted = 0
                          AND cl.delay IS NOT NULL
                          AND TRIM(cl.delay) != \'\'';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND c.id_carrier IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($onlyMissing) {
                    $sql .= ' AND NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . 'carrier_lang` tgt
                        WHERE tgt.id_carrier = c.id_carrier
                          AND tgt.id_lang = ' . (int) $this->targetLangId . '
                          AND tgt.id_shop = ' . (int) $shopId . '
                          AND tgt.delay IS NOT NULL
                          AND TRIM(tgt.delay) != \'\'
                          AND tgt.delay != cl.delay
                    )';
                }

                $sql .= ' ORDER BY c.id_carrier ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_carrier',
                );

            case 'ckc_labels':
                if (!Module::isInstalled('ckc_label') || !Module::isEnabled('ckc_label')) {
                    return array('sql' => '', 'column' => '');
                }
                $sql = 'SELECT l.id_label
                        FROM `' . _DB_PREFIX_ . 'ckc_labels` l
                        INNER JOIN `' . _DB_PREFIX_ . 'ckc_labels_lang` src
                          ON src.id_label = l.id_label
                         AND src.id_lang = ' . (int) $this->sourceLangId . '
                        WHERE src.text IS NOT NULL
                          AND TRIM(src.text) != \'\'';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND l.id_label IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($onlyMissing) {
                    $sql .= ' AND NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . 'ckc_labels_lang` tgt
                        WHERE tgt.id_label = l.id_label
                          AND tgt.id_lang = ' . (int) $this->targetLangId . '
                          AND tgt.text IS NOT NULL
                          AND TRIM(tgt.text) != \'\'
                          AND tgt.text != src.text
                    )';
                }

                $sql .= ' ORDER BY l.id_label ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_label',
                );

            case 'hellowork_jobs':
                if (!Module::isInstalled('dtc_feedhelloworkjob') || !Module::isEnabled('dtc_feedhelloworkjob')) {
                    return array('sql' => '', 'column' => '');
                }
                $sql = 'SELECT j.id_job
                        FROM `' . _DB_PREFIX_ . 'ckc_hellowork_job` j
                        INNER JOIN `' . _DB_PREFIX_ . 'ckc_hellowork_job_lang` src
                          ON src.id_job = j.id_job
                         AND src.id_lang = ' . (int) $this->sourceLangId . '
                        WHERE src.title IS NOT NULL
                          AND TRIM(src.title) != \'\'';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND j.id_job IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($onlyMissing) {
                    $sql .= ' AND NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . 'ckc_hellowork_job_lang` tgt
                        WHERE tgt.id_job = j.id_job
                          AND tgt.id_lang = ' . (int) $this->targetLangId . '
                          AND tgt.title IS NOT NULL
                          AND TRIM(tgt.title) != \'\'
                          AND tgt.title != src.title
                    )';
                }

                $sql .= ' ORDER BY j.id_job ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_job',
                );

            case 'ckc_realisation':
                if (!Module::isInstalled('ckc_realisation') || !Module::isEnabled('ckc_realisation')) {
                    return array('sql' => '', 'column' => '');
                }
                $sql = 'SELECT r.id_realisation
                        FROM `' . _DB_PREFIX_ . 'ckc_realisation` r
                        INNER JOIN `' . _DB_PREFIX_ . 'ckc_realisation_lang` src
                          ON src.id_realisation = r.id_realisation
                         AND src.id_lang = ' . (int) $this->sourceLangId . '
                        WHERE (
                            (src.title IS NOT NULL AND TRIM(src.title) != \'\')
                            OR (src.alt IS NOT NULL AND TRIM(src.alt) != \'\')
                        )
                          AND r.id_shop = ' . (int) $shopId;

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND r.id_realisation IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($onlyMissing) {
                    $sql .= ' AND NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . 'ckc_realisation_lang` tgt
                        WHERE tgt.id_realisation = r.id_realisation
                          AND tgt.id_lang = ' . (int) $this->targetLangId . '
                          AND (
                            (src.title IS NULL OR TRIM(src.title) = \'\' OR (TRIM(IFNULL(tgt.title, \'\')) != \'\' AND tgt.title != src.title))
                            AND
                            (src.alt IS NULL OR TRIM(src.alt) = \'\' OR (TRIM(IFNULL(tgt.alt, \'\')) != \'\' AND tgt.alt != src.alt))
                          )
                    )';
                }

                $sql .= ' ORDER BY r.id_realisation ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_realisation',
                );

            case 'product_faq':
                if (!Module::isInstalled('productfaq') || !Module::isEnabled('productfaq')) {
                    return array('sql' => '', 'column' => '');
                }
                $this->ensureProductFaqLangTable();

                $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
                $sourceIsDefault = ((int) $this->sourceLangId === $defaultLang);
                $targetIsDefault = ((int) $this->targetLangId === $defaultLang);
                $sql = 'SELECT f.id FROM `' . _DB_PREFIX_ . 'product_faq` f';
                if (!$sourceIsDefault) {
                    $sql .= ' INNER JOIN `' . _DB_PREFIX_ . 'product_faq_lang` src
                                ON src.id_faq = f.id
                               AND src.id_lang = ' . (int) $this->sourceLangId;
                }
                $srcQuestion = $sourceIsDefault ? 'f.question' : 'src.question';
                $sql .= ' WHERE TRIM(IFNULL(' . $srcQuestion . ', \'\')) != \'\'';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND f.id_product IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($onlyMissing) {
                    if ($targetIsDefault) {
                        $sql .= ' AND (
                            TRIM(IFNULL(f.question, \'\')) = \'\'
                            OR f.question = ' . $srcQuestion . '
                        )';
                    } else {
                        $sql .= ' AND NOT EXISTS (
                            SELECT 1 FROM `' . _DB_PREFIX_ . 'product_faq_lang` tgt
                            WHERE tgt.id_faq = f.id
                              AND tgt.id_lang = ' . (int) $this->targetLangId . '
                              AND TRIM(IFNULL(tgt.question, \'\')) != \'\'
                              AND tgt.question != ' . $srcQuestion . '
                        )';
                    }
                }

                $sql .= ' ORDER BY f.id ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id',
                );

            case 'product_guide':
                if (!Module::isInstalled('productguide') || !Module::isEnabled('productguide')) {
                    return array('sql' => '', 'column' => '');
                }
                $this->ensureProductGuideLangTable();

                $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
                $sourceIsDefault = ((int) $this->sourceLangId === $defaultLang);
                $targetIsDefault = ((int) $this->targetLangId === $defaultLang);
                $sql = 'SELECT g.id_product FROM `' . _DB_PREFIX_ . 'product_guide` g';
                if (!$sourceIsDefault) {
                    $sql .= ' INNER JOIN `' . _DB_PREFIX_ . 'product_guide_lang` src
                                ON src.id_product = g.id_product
                               AND src.id_lang = ' . (int) $this->sourceLangId;
                }
                $srcContent = $sourceIsDefault ? 'g.guide_content' : 'src.guide_content';
                $sql .= ' WHERE TRIM(IFNULL(' . $srcContent . ', \'\')) != \'\'';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND g.id_product IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($onlyMissing) {
                    if ($targetIsDefault) {
                        $sql .= ' AND (
                            TRIM(IFNULL(g.guide_content, \'\')) = \'\'
                            OR g.guide_content = ' . $srcContent . '
                        )';
                    } else {
                        $sql .= ' AND NOT EXISTS (
                            SELECT 1 FROM `' . _DB_PREFIX_ . 'product_guide_lang` tgt
                            WHERE tgt.id_product = g.id_product
                              AND tgt.id_lang = ' . (int) $this->targetLangId . '
                              AND TRIM(IFNULL(tgt.guide_content, \'\')) != \'\'
                              AND tgt.guide_content != ' . $srcContent . '
                        )';
                    }
                }

                $sql .= ' ORDER BY g.id_product ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_product',
                );

            case 'system_pages':
                // CKC Vianney — pages système PS (meta / meta_lang) : title + description.
                $sql = 'SELECT m.id_meta
                        FROM `' . _DB_PREFIX_ . 'meta` m
                        INNER JOIN `' . _DB_PREFIX_ . 'meta_lang` src
                          ON src.id_meta = m.id_meta
                         AND src.id_lang = ' . (int) $this->sourceLangId . '
                         AND src.id_shop = ' . (int) $shopId . '
                        WHERE (
                            TRIM(IFNULL(src.title, \'\')) != \'\'
                            OR TRIM(IFNULL(src.description, \'\')) != \'\'
                        )';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND m.id_meta IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($onlyMissing) {
                    // CKC Vianney — ignore les traductions trop courtes / coupées (ex. "Fencing &").
                    $sql .= ' AND NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . 'meta_lang` tgt
                        WHERE tgt.id_meta = m.id_meta
                          AND tgt.id_lang = ' . (int) $this->targetLangId . '
                          AND tgt.id_shop = ' . (int) $shopId . '
                          AND (
                            TRIM(IFNULL(src.title, \'\')) = \'\'
                            OR (
                              TRIM(IFNULL(tgt.title, \'\')) != \'\'
                              AND tgt.title != src.title
                              AND CHAR_LENGTH(TRIM(tgt.title)) >= GREATEST(12, FLOOR(CHAR_LENGTH(TRIM(src.title)) * 0.45))
                            )
                          )
                          AND (
                            TRIM(IFNULL(src.description, \'\')) = \'\'
                            OR (
                              TRIM(IFNULL(tgt.description, \'\')) != \'\'
                              AND tgt.description != src.description
                              AND CHAR_LENGTH(TRIM(tgt.description)) >= GREATEST(20, FLOOR(CHAR_LENGTH(TRIM(src.description)) * 0.45))
                            )
                          )
                    )';
                }

                $sql .= ' ORDER BY m.id_meta ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_meta',
                );

            default:
                return array(
                    'sql' => '',
                    'column' => '',
                );
        }
    }

    protected function runCreativeElements()
    {
        $filtered = $this->getFilteredSql('creative_elements');

        $id = $this->getNextId(
            'ckctranslate_ce_ids',
            'ckctranslate_ce_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Creative Elements translation finished.', 'CkcTranslateJobRunner'));
        }

        $sourceRows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ce_theme_lang`
             WHERE id_ce_theme = ' . (int) $id . ' AND id_lang = ' . (int) $this->sourceLangId . '
             LIMIT 1'
        );
        $sourceRow = $sourceRows ? $sourceRows[0] : false;

        if (!$sourceRow || empty($sourceRow['content'])) {
            $this->logHistory('creative_elements', 'ce_theme', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('CE theme #%d has no source content, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $targetRows = Db::getInstance()->executeS(
                'SELECT content FROM `' . _DB_PREFIX_ . 'ce_theme_lang`
                 WHERE id_ce_theme = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId . '
                 LIMIT 1'
            );
            $targetContent = ($targetRows && !empty($targetRows[0]['content'])) ? $targetRows[0]['content'] : '';

            if (!empty($targetContent) && $targetContent !== $sourceRow['content']) {
                $this->logHistory('creative_elements', 'ce_theme', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('CE theme #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                    (int) $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are a professional e-commerce page translator. Translate only the visible text content. Never modify HTML tags, attributes, class names, href links, src URLs, data attributes, inline styles, or any non-text content. Keep each translated text the same approximate length as the source to preserve the page layout.',
            'creative_elements'
        );

        $content = $sourceRow['content'];

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "content": ""
}

Translate only the visible text in the HTML below from French to the target language.
Preserve all HTML tags and attributes exactly. Keep translated text similar in length to the source.

Source HTML:
{$content}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('content'))) {
            $this->logHistory('creative_elements', 'ce_content', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for CE content #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $translatedContent = $data['content'];

        $sourceRows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ce_theme_lang`
             WHERE id_ce_theme = ' . (int) $id . ' AND id_lang = ' . (int) $this->sourceLangId
        );

        if (!is_array($sourceRows) || empty($sourceRows)) {
            $sourceRows = array(array('id_shop' => 1, 'title' => ''));
        }

        foreach ($sourceRows as $row) {
            $idShop = (int) ($row['id_shop'] ?? 1);

            $existsRows = Db::getInstance()->executeS(
                'SELECT id_ce_theme FROM `' . _DB_PREFIX_ . 'ce_theme_lang`
                 WHERE id_ce_theme = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId . ' AND id_shop = ' . $idShop . '
                 LIMIT 1'
            );
            $exists = !empty($existsRows);

            if ($exists) {
                Db::getInstance()->update(
                    'ce_theme_lang',
                    array('content' => pSQL($translatedContent, true)),
                    'id_ce_theme = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId . ' AND id_shop = ' . $idShop
                );
            } else {
                Db::getInstance()->insert('ce_theme_lang', array(
                    'id_ce_theme' => (int) $id,
                    'id_lang' => (int) $this->targetLangId,
                    'id_shop' => $idShop,
                    'title' => pSQL(isset($row['title']) ? $row['title'] : ''),
                    'content' => pSQL($translatedContent, true),
                ));
            }
        }

        $this->logHistory('creative_elements', 'ce_theme', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('CE theme #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    protected function runCreativeElementsMeta()
    {
        $filtered = $this->getFilteredSql('ce_meta');

        $sourceUid = $this->getNextId(
            'ckctranslate_cemeta_ids',
            'ckctranslate_cemeta_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$sourceUid) {
            return $this->finishedResult($this->module->l('CE meta translation finished.', 'CkcTranslateJobRunner'));
        }

        $sourceUidStr = (string) $sourceUid;

        $sourceRows = Db::getInstance()->executeS(
            'SELECT `value` FROM `' . _DB_PREFIX_ . 'ce_meta`
             WHERE `id` = ' . (int) $sourceUid . ' AND `name` = \'_elementor_data\'
             LIMIT 1'
        );

        if (!$sourceRows || empty($sourceRows[0]['value'])) {
            $this->logHistory('ce_meta', 'ce_meta', (int) $sourceUid, 'skipped', $this->module->l('No source JSON.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('CE meta #%s has no JSON, skipped.', 'CkcTranslateJobRunner'),
                $sourceUidStr
            ));
        }

        $sourceJson = $sourceRows[0]['value'];
        $elements = json_decode($sourceJson, true);

        if (!is_array($elements)) {
            $this->logHistory('ce_meta', 'ce_meta', (int) $sourceUid, 'skipped', $this->module->l('Invalid JSON.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('CE meta #%s invalid JSON, skipped.', 'CkcTranslateJobRunner'),
                $sourceUidStr
            ));
        }

        // Build target UID: replace lang portion (chars -4 to -2 from end)
        $targetUidStr = substr($sourceUidStr, 0, -4) . sprintf('%02d', $this->targetLangId) . substr($sourceUidStr, -2);

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $existsRows = Db::getInstance()->executeS(
                'SELECT id FROM `' . _DB_PREFIX_ . 'ce_meta`
                 WHERE `id` = ' . (int) $targetUidStr . ' AND `name` = \'_elementor_data\'
                 LIMIT 1'
            );
            if (!empty($existsRows)) {
                $this->logHistory('ce_meta', 'ce_meta', (int) $sourceUid, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('CE meta #%s already translated, skipped.', 'CkcTranslateJobRunner'),
                    $sourceUidStr
                ));
            }
        }

        // Extract translatable texts from Elementor JSON
        $texts = array();
        $counter = 0;
        $this->extractTranslatableTexts($elements, $texts, $counter);

        if (empty($texts)) {
            $this->saveMetaForTarget($targetUidStr, $sourceUid, $sourceJson);
            $this->logHistory('ce_meta', 'ce_meta', (int) $sourceUid, 'success', $this->module->l('No text, copied.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('CE meta #%s has no translatable text, copied.', 'CkcTranslateJobRunner'),
                $sourceUidStr
            ));
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are a professional e-commerce page translator. Translate only the visible text values in the JSON map. Preserve HTML tags within values. Return a JSON object with the exact same keys and translated values.',
            'ce_meta'
        );

        $prompt = 'Return ONLY valid JSON with the same keys and translated values:' . "\n" . json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $translations = $this->ai->requestJson($systemPrompt, $prompt);

        if (!is_array($translations) || empty($translations)) {
            // AI failed — save source JSON as fallback so the page is not blank
            $this->saveMetaForTarget($targetUidStr, $sourceUid, $sourceJson);
            $this->logHistory('ce_meta', 'ce_meta', (int) $sourceUid, 'error', $this->module->l('Invalid AI response — saved source as fallback.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for CE meta #%s — source saved as fallback.', 'CkcTranslateJobRunner'),
                $sourceUidStr
            ));
        }

        $translatedElements = $this->injectTranslatedTexts($elements, $translations);
        $translatedJson = json_encode($translatedElements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->saveMetaForTarget($targetUidStr, $sourceUid, $translatedJson);

        $this->logHistory('ce_meta', 'ce_meta', (int) $sourceUid, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('CE meta #%s translated.', 'CkcTranslateJobRunner'),
            $sourceUidStr
        ));
    }

    protected function runReassurance()
    {
        $filtered = $this->getFilteredSql('reassurance');

        $id = $this->getNextId(
            'ckctranslate_reassurance_ids',
            'ckctranslate_reassurance_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Reassurance translation finished.', 'CkcTranslateJobRunner'));
        }

        $sourceRows = Db::getInstance()->executeS(
            'SELECT title, description FROM `' . _DB_PREFIX_ . 'psreassurance_lang`
             WHERE id_psreassurance = ' . (int) $id . ' AND id_lang = ' . (int) $this->sourceLangId . '
             LIMIT 1'
        );
        $sourceRow = $sourceRows ? $sourceRows[0] : false;

        if (!$sourceRow || (empty($sourceRow['title']) && empty($sourceRow['description']))) {
            $this->logHistory('reassurance', 'reassurance', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Reassurance #%d has no source content, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $targetRows = Db::getInstance()->executeS(
                'SELECT title, description FROM `' . _DB_PREFIX_ . 'psreassurance_lang`
                 WHERE id_psreassurance = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId . '
                 LIMIT 1'
            );
            $targetRow = $targetRows ? $targetRows[0] : false;

            if ($targetRow && (!empty($targetRow['title']) || !empty($targetRow['description']))) {
                $this->logHistory('reassurance', 'reassurance', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Reassurance #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                    (int) $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are an e-commerce copywriter. Translate short reassurance texts (delivery, payment, warranty, etc.). Keep them brief and trustworthy.',
            'reassurance'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "title": "",
  "description": ""
}

Source title:
{$sourceRow['title']}

Source description:
{$sourceRow['description']}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('title', 'description'))) {
            $this->logHistory('reassurance', 'reassurance', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for reassurance #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $existsRows = Db::getInstance()->executeS(
            'SELECT id_psreassurance FROM `' . _DB_PREFIX_ . 'psreassurance_lang`
             WHERE id_psreassurance = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId . '
             LIMIT 1'
        );

        if (!empty($existsRows)) {
            Db::getInstance()->update(
                'psreassurance_lang',
                array(
                    'title' => pSQL($data['title']),
                    'description' => pSQL($data['description'], true),
                ),
                'id_psreassurance = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
            );
        } else {
            Db::getInstance()->insert('psreassurance_lang', array(
                'id_psreassurance' => (int) $id,
                'id_lang' => (int) $this->targetLangId,
                'title' => pSQL($data['title']),
                'description' => pSQL($data['description'], true),
            ));
        }

        $this->logHistory('reassurance', 'reassurance', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Reassurance #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    protected function runEverContentSeo()
    {
        $modules = self::discoverEverContentSeoModules();
        if (empty($modules)) {
            return array(
                'success' => false,
                'message' => $this->module->l('Ever Content SEO tables not found. Install everpscontentseo / everpscontentseo3 (or another everpscontentseo* clone).', 'CkcTranslateJobRunner'),
                'finished' => true,
            );
        }

        $item = $this->getNextEverContentSeoItem();
        if (!$item) {
            return $this->finishedResult($this->module->l('Ever Content SEO translation finished (all modules).', 'CkcTranslateJobRunner'));
        }

        $module = !empty($item['module']) ? (string) $item['module'] : 'everpscontentseo';
        $type = $item['type'];
        $id = (int) $item['id'];
        $historyType = $module . ':' . $type;
        $meta = self::getEverContentSeoTypeMetaForModule($module, $type);
        if (!$meta) {
            $this->logHistory('evercontentseo', $historyType, $id, 'skipped', 'Unknown entity type');

            return $this->nextResult(sprintf(
                $this->module->l('Ever Content SEO item skipped (unknown type: %s / %s).', 'CkcTranslateJobRunner'),
                $module,
                $type
            ));
        }

        $sourceContent = (string) Db::getInstance()->getValue(
            'SELECT content FROM `' . _DB_PREFIX_ . $meta['lang_table'] . '`
             WHERE `' . $meta['id_column'] . '` = ' . $id . '
             AND id_lang = ' . (int) $this->sourceLangId
        );

        if (trim(strip_tags($sourceContent)) === '') {
            $this->logHistory('evercontentseo', $historyType, $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Ever Content SEO %1$s %2$s #%3$d has no source content, skipped.', 'CkcTranslateJobRunner'),
                $module,
                $type,
                $id
            ));
        }

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $targetContent = (string) Db::getInstance()->getValue(
                'SELECT content FROM `' . _DB_PREFIX_ . $meta['lang_table'] . '`
                 WHERE `' . $meta['id_column'] . '` = ' . $id . '
                 AND id_lang = ' . (int) $this->targetLangId
            );

            // Une copie FR déjà présente en EN ne doit PAS être considérée comme traduite.
            if (trim(strip_tags($targetContent)) !== ''
                && trim($targetContent) !== trim($sourceContent)
            ) {
                $this->logHistory('evercontentseo', $historyType, $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Ever Content SEO %1$s %2$s #%3$d already translated, skipped.', 'CkcTranslateJobRunner'),
                    $module,
                    $type,
                    $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are an SEO e-commerce copywriter. Translate HTML SEO blocks shown on category/manufacturer/supplier pages. Preserve all HTML tags, attributes, links and images. Translate only visible text.',
            'evercontentseo'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "content": ""
}

Source HTML content:
{$sourceContent}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('content'))) {
            $this->logHistory('evercontentseo', $historyType, $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for Ever Content SEO %1$s %2$s #%3$d.', 'CkcTranslateJobRunner'),
                $module,
                $type,
                $id
            ));
        }

        $translated = (string) $data['content'];
        $exists = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . $meta['lang_table'] . '`
             WHERE `' . $meta['id_column'] . '` = ' . $id . '
             AND id_lang = ' . (int) $this->targetLangId
        );

        if ($exists > 0) {
            Db::getInstance()->update(
                $meta['lang_table'],
                array('content' => pSQL($translated, true)),
                '`' . $meta['id_column'] . '` = ' . $id . ' AND id_lang = ' . (int) $this->targetLangId
            );
        } else {
            Db::getInstance()->insert($meta['lang_table'], array(
                $meta['id_column'] => $id,
                'id_lang' => (int) $this->targetLangId,
                'content' => pSQL($translated, true),
            ));
        }

        $this->logHistory('evercontentseo', $historyType, $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Ever Content SEO %1$s %2$s #%3$d translated.', 'CkcTranslateJobRunner'),
            $module,
            $type,
            $id
        ));
    }

    /**
     * Discover all Ever Content SEO module clones (everpscontentseo, everpscontentseo3, …).
     *
     * @return string[]
     */
    public static function discoverEverContentSeoModules()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $found = array();
        $db = Db::getInstance();

        // 1) Module folders everpscontentseo*
        $dirs = glob(_PS_MODULE_DIR_ . 'everpscontentseo*', GLOB_ONLYDIR);
        if (is_array($dirs)) {
            foreach ($dirs as $dir) {
                $name = basename($dir);
                if (!preg_match('/^everpscontentseo[a-z0-9_]*$/', $name)) {
                    continue;
                }
                if ((bool) $db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . pSQL($name) . '_categories"')
                    && (bool) $db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . pSQL($name) . '_categories_lang"')
                ) {
                    $found[$name] = true;
                }
            }
        }

        // 2) DB tables (covers clones present in DB even if folder missing locally)
        $tables = $db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'everpscontentseo%_categories"');
        if (is_array($tables)) {
            foreach ($tables as $row) {
                $tableName = (string) reset($row);
                $prefix = _DB_PREFIX_;
                if (strpos($tableName, $prefix) !== 0) {
                    continue;
                }
                $withoutPrefix = substr($tableName, strlen($prefix));
                if (!preg_match('/^(everpscontentseo[a-z0-9]*)_categories$/', $withoutPrefix, $m)) {
                    continue;
                }
                $name = $m[1];
                if ((bool) $db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . pSQL($name) . '_categories_lang"')) {
                    $found[$name] = true;
                }
            }
        }

        $cache = array_keys($found);
        sort($cache);

        return $cache;
    }

    /**
     * @param string $module Module technical name (e.g. everpscontentseo3)
     * @param string $type category|manufacturer|supplier
     *
     * @return array|null
     */
    public static function getEverContentSeoTypeMetaForModule($module, $type)
    {
        $module = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $module));
        if ($module === '' || strpos($module, 'everpscontentseo') !== 0) {
            return null;
        }

        $map = array(
            'category' => array(
                'table' => $module . '_categories',
                'lang_table' => $module . '_categories_lang',
                'id_column' => 'id_' . $module . '_category',
                'ref_column' => 'id_category',
            ),
            'manufacturer' => array(
                'table' => $module . '_manufacturers',
                'lang_table' => $module . '_manufacturers_lang',
                'id_column' => 'id_' . $module . '_manufacturer',
                'ref_column' => 'id_manufacturer',
            ),
            'supplier' => array(
                'table' => $module . '_suppliers',
                'lang_table' => $module . '_suppliers_lang',
                'id_column' => 'id_' . $module . '_supplier',
                'ref_column' => 'id_supplier',
            ),
        );

        return isset($map[$type]) ? $map[$type] : null;
    }

    protected function buildEverContentSeoQueue()
    {
        $items = array();
        $shopId = (int) Context::getContext()->shop->id;
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $filterCategoryId = (int) Configuration::get(CkcTranslate::CONF_FILTER_CATEGORY_ID);

        $types = array('category', 'manufacturer', 'supplier');
        foreach (self::discoverEverContentSeoModules() as $module) {
            foreach ($types as $type) {
                $meta = self::getEverContentSeoTypeMetaForModule($module, $type);
                if (!$meta) {
                    continue;
                }

                if (!(bool) Db::getInstance()->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . $meta['table'] . '"')) {
                    continue;
                }

                $sql = 'SELECT c.`' . $meta['id_column'] . '` AS id
                        FROM `' . _DB_PREFIX_ . $meta['table'] . '` c
                        INNER JOIN `' . _DB_PREFIX_ . $meta['lang_table'] . '` cl
                          ON cl.`' . $meta['id_column'] . '` = c.`' . $meta['id_column'] . '`
                         AND cl.id_lang = ' . (int) $this->sourceLangId . '
                        WHERE cl.content IS NOT NULL
                          AND TRIM(cl.content) != \'\'
                          AND c.id_shop = ' . (int) $shopId;

                if ($type === 'category') {
                    if ($filterType === 'ids' && !empty($filterIds)) {
                        $sql .= ' AND c.`' . $meta['ref_column'] . '` IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                    }
                    if ($filterType === 'category' && $filterCategoryId > 0) {
                        $sql .= ' AND c.`' . $meta['ref_column'] . '` = ' . (int) $filterCategoryId;
                    }
                }

                if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
                    $sql .= ' AND NOT EXISTS (
                        SELECT 1 FROM `' . _DB_PREFIX_ . $meta['lang_table'] . '` tgt
                        WHERE tgt.`' . $meta['id_column'] . '` = c.`' . $meta['id_column'] . '`
                          AND tgt.id_lang = ' . (int) $this->targetLangId . '
                          AND tgt.content IS NOT NULL
                          AND TRIM(tgt.content) != \'\'
                          AND tgt.content != cl.content
                    )';
                }

                $sql .= ' ORDER BY c.`' . $meta['id_column'] . '` ASC';

                $rows = Db::getInstance()->executeS($sql);
                if (!is_array($rows)) {
                    continue;
                }

                foreach ($rows as $row) {
                    $items[] = array(
                        'module' => $module,
                        'type' => $type,
                        'id' => (int) $row['id'],
                    );
                }
            }
        }

        return $items;
    }

    protected function getNextEverContentSeoItem()
    {
        return $this->getNextTypedQueueItem('evercontentseo', array($this, 'buildEverContentSeoQueue'));
    }

    protected function runFeatures()
    {
        $item = $this->getNextTypedQueueItem('features', array($this, 'buildFeaturesQueue'));

        if (!$item) {
            return $this->finishedResult($this->module->l('Features translation finished.', 'CkcTranslateJobRunner'));
        }

        $type = $item['type'];
        $id = (int) $item['id'];

        if ($type === 'feature') {
            return $this->translateSimpleLangField(
                'features',
                'feature',
                $id,
                'feature_lang',
                'id_feature',
                'name',
                128,
                'You translate PrestaShop product feature names (caractéristiques). Keep them short, catalog-friendly and consistent.'
            );
        }

        if ($type === 'feature_value') {
            return $this->translateSimpleLangField(
                'features',
                'feature_value',
                $id,
                'feature_value_lang',
                'id_feature_value',
                'value',
                255,
                'You translate PrestaShop product feature values. Keep units, numbers and technical codes unchanged when appropriate.'
            );
        }

        $this->logHistory('features', $type, $id, 'skipped', 'Unknown entity type');

        return $this->nextResult(sprintf(
            $this->module->l('Feature item skipped (unknown type: %s).', 'CkcTranslateJobRunner'),
            $type
        ));
    }

    protected function runAttributes()
    {
        $item = $this->getNextTypedQueueItem('attributes', array($this, 'buildAttributesQueue'));

        if (!$item) {
            return $this->finishedResult($this->module->l('Attributes translation finished.', 'CkcTranslateJobRunner'));
        }

        $type = $item['type'];
        $id = (int) $item['id'];

        if ($type === 'attribute_group') {
            return $this->translateAttributeGroup($id);
        }

        if ($type === 'attribute') {
            return $this->translateSimpleLangField(
                'attributes',
                'attribute',
                $id,
                'attribute_lang',
                'id_attribute',
                'name',
                128,
                'You translate PrestaShop product attribute values (color, size, material, etc.). Keep them short for selectors and filters.'
            );
        }

        $this->logHistory('attributes', $type, $id, 'skipped', 'Unknown entity type');

        return $this->nextResult(sprintf(
            $this->module->l('Attribute item skipped (unknown type: %s).', 'CkcTranslateJobRunner'),
            $type
        ));
    }

    protected function buildFeaturesQueue()
    {
        $items = array();
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));

        $featureSql = 'SELECT f.id_feature
                       FROM `' . _DB_PREFIX_ . 'feature` f
                       INNER JOIN `' . _DB_PREFIX_ . 'feature_lang` fl
                         ON fl.id_feature = f.id_feature
                        AND fl.id_lang = ' . (int) $this->sourceLangId . '
                       WHERE fl.name IS NOT NULL
                         AND TRIM(fl.name) != \'\'';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $featureSql .= ' AND f.id_feature IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $featureSql .= ' AND NOT EXISTS (
                SELECT 1 FROM `' . _DB_PREFIX_ . 'feature_lang` tgt
                WHERE tgt.id_feature = f.id_feature
                  AND tgt.id_lang = ' . (int) $this->targetLangId . '
                  AND tgt.name IS NOT NULL
                  AND TRIM(tgt.name) != \'\'
                  AND tgt.name != fl.name
            )';
        }

        $featureSql .= ' ORDER BY f.id_feature ASC';

        $featureRows = Db::getInstance()->executeS($featureSql);
        if (is_array($featureRows)) {
            foreach ($featureRows as $row) {
                $items[] = array('type' => 'feature', 'id' => (int) $row['id_feature']);
            }
        }

        $valueSql = 'SELECT fv.id_feature_value
                     FROM `' . _DB_PREFIX_ . 'feature_value` fv
                     INNER JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl
                       ON fvl.id_feature_value = fv.id_feature_value
                      AND fvl.id_lang = ' . (int) $this->sourceLangId . '
                     WHERE fvl.value IS NOT NULL
                       AND TRIM(fvl.value) != \'\'';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $valueSql .= ' AND fv.id_feature IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $valueSql .= ' AND NOT EXISTS (
                SELECT 1 FROM `' . _DB_PREFIX_ . 'feature_value_lang` tgt
                WHERE tgt.id_feature_value = fv.id_feature_value
                  AND tgt.id_lang = ' . (int) $this->targetLangId . '
                  AND tgt.value IS NOT NULL
                  AND TRIM(tgt.value) != \'\'
                  AND tgt.value != fvl.value
            )';
        }

        $valueSql .= ' ORDER BY fv.id_feature_value ASC';

        $valueRows = Db::getInstance()->executeS($valueSql);
        if (is_array($valueRows)) {
            foreach ($valueRows as $row) {
                $items[] = array('type' => 'feature_value', 'id' => (int) $row['id_feature_value']);
            }
        }

        return $items;
    }

    protected function buildAttributesQueue()
    {
        $items = array();
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));

        $groupSql = 'SELECT ag.id_attribute_group
                     FROM `' . _DB_PREFIX_ . 'attribute_group` ag
                     INNER JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                       ON agl.id_attribute_group = ag.id_attribute_group
                      AND agl.id_lang = ' . (int) $this->sourceLangId . '
                     WHERE (
                        (agl.name IS NOT NULL AND TRIM(agl.name) != \'\')
                        OR (agl.public_name IS NOT NULL AND TRIM(agl.public_name) != \'\')
                     )';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $groupSql .= ' AND ag.id_attribute_group IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $groupSql .= ' AND NOT EXISTS (
                SELECT 1 FROM `' . _DB_PREFIX_ . 'attribute_group_lang` tgt
                WHERE tgt.id_attribute_group = ag.id_attribute_group
                  AND tgt.id_lang = ' . (int) $this->targetLangId . '
                  AND tgt.name IS NOT NULL AND TRIM(tgt.name) != \'\' AND tgt.name != agl.name
                  AND tgt.public_name IS NOT NULL AND TRIM(tgt.public_name) != \'\' AND tgt.public_name != agl.public_name
            )';
        }

        $groupSql .= ' ORDER BY ag.id_attribute_group ASC';

        $groupRows = Db::getInstance()->executeS($groupSql);
        if (is_array($groupRows)) {
            foreach ($groupRows as $row) {
                $items[] = array('type' => 'attribute_group', 'id' => (int) $row['id_attribute_group']);
            }
        }

        $attrSql = 'SELECT a.id_attribute
                    FROM `' . _DB_PREFIX_ . 'attribute` a
                    INNER JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                      ON al.id_attribute = a.id_attribute
                     AND al.id_lang = ' . (int) $this->sourceLangId . '
                    WHERE al.name IS NOT NULL
                      AND TRIM(al.name) != \'\'';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $attrSql .= ' AND a.id_attribute_group IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $attrSql .= ' AND NOT EXISTS (
                SELECT 1 FROM `' . _DB_PREFIX_ . 'attribute_lang` tgt
                WHERE tgt.id_attribute = a.id_attribute
                  AND tgt.id_lang = ' . (int) $this->targetLangId . '
                  AND tgt.name IS NOT NULL
                  AND TRIM(tgt.name) != \'\'
                  AND tgt.name != al.name
            )';
        }

        $attrSql .= ' ORDER BY a.id_attribute ASC';

        $attrRows = Db::getInstance()->executeS($attrSql);
        if (is_array($attrRows)) {
            foreach ($attrRows as $row) {
                $items[] = array('type' => 'attribute', 'id' => (int) $row['id_attribute']);
            }
        }

        return $items;
    }

    /**
     * Files de file d'attente (le cookie BO est limité à 4096 octets).
     */
    public static function getQueueDir()
    {
        $varDir = _PS_MODULE_DIR_ . 'ckctranslate/var/';
        $dir = $varDir . 'queues/';

        if (!is_dir($varDir)) {
            @mkdir($varDir, 0755, true);
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        foreach (array($varDir . 'index.php', $dir . 'index.php') as $indexPhp) {
            if (!file_exists($indexPhp)) {
                @file_put_contents($indexPhp, "<?php\nheader('Location: ../');\nexit;\n");
            }
        }

        return $dir;
    }

    public static function normalizeQueueName($queueName)
    {
        $queueName = (string) $queueName;
        if (strpos($queueName, 'ckctranslate_') === 0) {
            $queueName = substr($queueName, strlen('ckctranslate_'));
        }
        $queueName = preg_replace('/_ids$/', '', $queueName);

        return preg_replace('/[^a-z0-9_]/i', '', $queueName);
    }

    public static function getQueueFilePaths($queueName)
    {
        $employeeId = (int) (Context::getContext()->employee ? Context::getContext()->employee->id : 0);
        $shopId = (int) Context::getContext()->shop->id;
        $safe = self::normalizeQueueName($queueName);
        $base = self::getQueueDir() . $safe . '_e' . $employeeId . '_s' . $shopId;

        return array(
            'items' => $base . '_items.json',
            'index' => $base . '_index.txt',
        );
    }

    public static function getFileQueueCounters($queueName)
    {
        $paths = self::getQueueFilePaths($queueName);
        if (!file_exists($paths['items'])) {
            return null;
        }

        $items = json_decode((string) @file_get_contents($paths['items']), true);
        if (!is_array($items)) {
            return null;
        }

        $index = (int) @file_get_contents($paths['index']);

        return array(
            'total' => count($items),
            'remaining' => max(count($items) - $index, 0),
        );
    }

    public static function resetFileQueue($queueName)
    {
        $paths = self::getQueueFilePaths($queueName);
        if (file_exists($paths['items'])) {
            @unlink($paths['items']);
        }
        if (file_exists($paths['index'])) {
            @unlink($paths['index']);
        }
    }

    public static function getKnownFileQueueNames()
    {
        return array(
            'product_seo',
            'product_desc',
            'category_seo',
            'category_desc',
            'cms',
            'ce',
            'cemeta',
            'reassurance',
            'evercontentseo',
            'features',
            'attributes',
            'carrier_delays',
            'brand_reviews',
            'order_states',
            'ckc_labels',
            'hellowork_jobs',
            'ets_cfu',
            'ckc_realisation',
            'responsivegallery',
            'product_faq',
            'product_guide',
            'system_pages',
        );
    }

    public static function resetAllFileQueues()
    {
        foreach (self::getKnownFileQueueNames() as $name) {
            self::resetFileQueue($name);
        }
    }

    public static function jobCodeToQueueName($jobCode)
    {
        $map = array(
            'product_seo' => 'product_seo',
            'product_description' => 'product_desc',
            'category_seo' => 'category_seo',
            'category_description' => 'category_desc',
            'cms' => 'cms',
            'creative_elements' => 'ce',
            'ce_meta' => 'cemeta',
            'reassurance' => 'reassurance',
            'evercontentseo' => 'evercontentseo',
            'features' => 'features',
            'attributes' => 'attributes',
            'carrier_delays' => 'carrier_delays',
            'brand_reviews' => 'brand_reviews',
            'order_states' => 'order_states',
            'ckc_labels' => 'ckc_labels',
            'hellowork_jobs' => 'hellowork_jobs',
            'ets_cfu' => 'ets_cfu',
            'ckc_realisation' => 'ckc_realisation',
            'responsivegallery' => 'responsivegallery',
            'product_faq' => 'product_faq',
            'product_guide' => 'product_guide',
            'system_pages' => 'system_pages',
        );

        return isset($map[$jobCode]) ? $map[$jobCode] : self::normalizeQueueName($jobCode);
    }

    protected function getNextTypedQueueItem($queueName, $builder)
    {
        $paths = self::getQueueFilePaths($queueName);

        if (!file_exists($paths['items'])) {
            $items = is_callable($builder) ? call_user_func($builder) : array();
            if (!is_array($items)) {
                $items = array();
            }
            @file_put_contents($paths['items'], json_encode(array_values($items)));
            @file_put_contents($paths['index'], '0');
        }

        $items = json_decode((string) @file_get_contents($paths['items']), true);
        $index = (int) @file_get_contents($paths['index']);

        if (!is_array($items) || !isset($items[$index])) {
            self::resetFileQueue($queueName);
            Tools::clearAllCache();

            return null;
        }

        $item = $items[$index];
        @file_put_contents($paths['index'], (string) ($index + 1));

        if (!is_array($item) || empty($item['type']) || empty($item['id'])) {
            return $this->getNextTypedQueueItem($queueName, $builder);
        }

        $result = array(
            'type' => (string) $item['type'],
            'id' => (int) $item['id'],
        );
        if (!empty($item['module'])) {
            $result['module'] = (string) $item['module'];
        }

        return $result;
    }

    protected function translateSimpleLangField(
        $jobCode,
        $entityType,
        $id,
        $langTable,
        $idColumn,
        $field,
        $maxLength,
        $defaultSystemPrompt
    ) {
        $sourceValue = (string) Db::getInstance()->getValue(
            'SELECT `' . bqSQL($field) . '` FROM `' . _DB_PREFIX_ . bqSQL($langTable) . '`
             WHERE `' . bqSQL($idColumn) . '` = ' . (int) $id . '
               AND id_lang = ' . (int) $this->sourceLangId
        );

        if (trim($sourceValue) === '') {
            $this->logHistory($jobCode, $entityType, $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('%1$s #%2$d has no source content, skipped.', 'CkcTranslateJobRunner'),
                $entityType,
                $id
            ));
        }

        $targetValue = (string) Db::getInstance()->getValue(
            'SELECT `' . bqSQL($field) . '` FROM `' . _DB_PREFIX_ . bqSQL($langTable) . '`
             WHERE `' . bqSQL($idColumn) . '` = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId
        );

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            if ($targetValue !== '' && $targetValue !== $sourceValue) {
                $this->logHistory($jobCode, $entityType, $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('%1$s #%2$d already translated, skipped.', 'CkcTranslateJobRunner'),
                    $entityType,
                    $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt($defaultSystemPrompt, $jobCode);
        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "{$field}": ""
}

Source {$field}:
{$sourceValue}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array($field)) || trim((string) $data[$field]) === '') {
            $this->logHistory($jobCode, $entityType, $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for %1$s #%2$d.', 'CkcTranslateJobRunner'),
                $entityType,
                $id
            ));
        }

        $translated = trim((string) $data[$field]);
        if ($maxLength > 0 && Tools::strlen($translated) > $maxLength) {
            $translated = Tools::substr($translated, 0, $maxLength);
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT `' . bqSQL($idColumn) . '` FROM `' . _DB_PREFIX_ . bqSQL($langTable) . '`
             WHERE `' . bqSQL($idColumn) . '` = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId
        );

        if ($exists) {
            Db::getInstance()->update(
                $langTable,
                array($field => pSQL($translated)),
                '`' . bqSQL($idColumn) . '` = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
            );
        } else {
            Db::getInstance()->insert($langTable, array(
                $idColumn => (int) $id,
                'id_lang' => (int) $this->targetLangId,
                $field => pSQL($translated),
            ));
        }

        $this->logHistory($jobCode, $entityType, $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('%1$s #%2$d translated (%3$s).', 'CkcTranslateJobRunner'),
            $entityType,
            $id,
            $translated
        ));
    }

    protected function translateAttributeGroup($id)
    {
        $sourceRows = Db::getInstance()->executeS(
            'SELECT name, public_name FROM `' . _DB_PREFIX_ . 'attribute_group_lang`
             WHERE id_attribute_group = ' . (int) $id . '
               AND id_lang = ' . (int) $this->sourceLangId . '
             LIMIT 1'
        );
        $sourceRow = $sourceRows ? $sourceRows[0] : false;

        if (!$sourceRow || (trim((string) $sourceRow['name']) === '' && trim((string) $sourceRow['public_name']) === '')) {
            $this->logHistory('attributes', 'attribute_group', $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Attribute group #%d has no source content, skipped.', 'CkcTranslateJobRunner'),
                $id
            ));
        }

        $targetRows = Db::getInstance()->executeS(
            'SELECT name, public_name FROM `' . _DB_PREFIX_ . 'attribute_group_lang`
             WHERE id_attribute_group = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId . '
             LIMIT 1'
        );
        $targetRow = $targetRows ? $targetRows[0] : false;

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS) && $targetRow) {
            $nameDone = trim((string) $targetRow['name']) !== '' && (string) $targetRow['name'] !== (string) $sourceRow['name'];
            $publicDone = trim((string) $targetRow['public_name']) !== '' && (string) $targetRow['public_name'] !== (string) $sourceRow['public_name'];
            if ($nameDone && $publicDone) {
                $this->logHistory('attributes', 'attribute_group', $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Attribute group #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                    $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You translate PrestaShop attribute group labels. "name" is the back-office label, "public_name" is shown on the product page. Keep both short and consistent.',
            'attributes'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "name": "",
  "public_name": ""
}

Source name:
{$sourceRow['name']}

Source public_name:
{$sourceRow['public_name']}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('name', 'public_name'))) {
            $this->logHistory('attributes', 'attribute_group', $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for attribute group #%d.', 'CkcTranslateJobRunner'),
                $id
            ));
        }

        $translatedName = trim((string) $data['name']);
        $translatedPublicName = trim((string) $data['public_name']);
        if ($translatedName === '') {
            $translatedName = trim((string) $sourceRow['name']);
        }
        if ($translatedPublicName === '') {
            $translatedPublicName = $translatedName !== '' ? $translatedName : trim((string) $sourceRow['public_name']);
        }
        if (Tools::strlen($translatedName) > 128) {
            $translatedName = Tools::substr($translatedName, 0, 128);
        }
        if (Tools::strlen($translatedPublicName) > 64) {
            $translatedPublicName = Tools::substr($translatedPublicName, 0, 64);
        }

        if ($targetRow) {
            Db::getInstance()->update(
                'attribute_group_lang',
                array(
                    'name' => pSQL($translatedName),
                    'public_name' => pSQL($translatedPublicName),
                ),
                'id_attribute_group = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
            );
        } else {
            Db::getInstance()->insert('attribute_group_lang', array(
                'id_attribute_group' => (int) $id,
                'id_lang' => (int) $this->targetLangId,
                'name' => pSQL($translatedName),
                'public_name' => pSQL($translatedPublicName),
            ));
        }

        $this->logHistory('attributes', 'attribute_group', $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Attribute group #%d translated (%s).', 'CkcTranslateJobRunner'),
            $id,
            $translatedPublicName !== '' ? $translatedPublicName : $translatedName
        ));
    }

    protected function runBrandReviews()
    {
        $this->ensureBrandReviewsReady();

        $filtered = $this->getFilteredSql('brand_reviews');

        $id = $this->getNextId(
            'ckctranslate_brand_reviews_ids',
            'ckctranslate_brand_reviews_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Brand reviews translation finished.', 'CkcTranslateJobRunner'));
        }

        $sourceContent = (string) Db::getInstance()->getValue(
            'SELECT review_content FROM `' . _DB_PREFIX_ . 'ckc_brand_review`
             WHERE id_ckc_brand_review = ' . (int) $id
        );

        if ($sourceContent === '') {
            $this->logHistory('brand_reviews', 'brand_review', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Review #%d has no source content, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $existingTranslation = (string) Db::getInstance()->getValue(
                'SELECT review_content FROM `' . _DB_PREFIX_ . 'ckc_brand_review_lang`
                 WHERE id_ckc_brand_review = ' . (int) $id . '
                   AND id_lang = ' . (int) $this->targetLangId
            );

            if ($existingTranslation !== '') {
                $this->logHistory('brand_reviews', 'brand_review', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Review #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                    (int) $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are translating authentic customer reviews for an e-commerce fencing website. Keep the voice natural and personal. Do not invent facts.',
            'brand_reviews'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "review_content": ""
}

Source review:
{$sourceContent}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('review_content')) || trim((string) $data['review_content']) === '') {
            $this->logHistory('brand_reviews', 'brand_review', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for review #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_ckc_brand_review FROM `' . _DB_PREFIX_ . 'ckc_brand_review_lang`
             WHERE id_ckc_brand_review = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId
        );

        if ($exists) {
            Db::getInstance()->update(
                'ckc_brand_review_lang',
                array(
                    'review_content' => pSQL($data['review_content'], true),
                ),
                'id_ckc_brand_review = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
            );
        } else {
            Db::getInstance()->insert('ckc_brand_review_lang', array(
                'id_ckc_brand_review' => (int) $id,
                'id_lang' => (int) $this->targetLangId,
                'review_content' => pSQL($data['review_content'], true),
            ));
        }

        $this->logHistory('brand_reviews', 'brand_review', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Review #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    protected function runOrderStates()
    {
        $filtered = $this->getFilteredSql('order_states');

        $id = $this->getNextId(
            'ckctranslate_order_states_ids',
            'ckctranslate_order_states_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Order states translation finished.', 'CkcTranslateJobRunner'));
        }

        $sourceName = (string) Db::getInstance()->getValue(
            'SELECT name FROM `' . _DB_PREFIX_ . 'order_state_lang`
             WHERE id_order_state = ' . (int) $id . '
               AND id_lang = ' . (int) $this->sourceLangId
        );

        if ($sourceName === '') {
            $this->logHistory('order_states', 'order_state', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Order state #%d has no source name, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $targetName = (string) Db::getInstance()->getValue(
            'SELECT name FROM `' . _DB_PREFIX_ . 'order_state_lang`
             WHERE id_order_state = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId
        );

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            // Skip only if target exists and differs from French source (already localized)
            if ($targetName !== '' && $targetName !== $sourceName) {
                $this->logHistory('order_states', 'order_state', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Order state #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                    (int) $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You translate PrestaShop order status names. Keep them concise for badges and emails. Do not invent payment methods. Preserve numeric prefixes such as "0 - " when present.',
            'order_states'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "name": ""
}

Source order status name:
{$sourceName}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('name')) || trim((string) $data['name']) === '') {
            $this->logHistory('order_states', 'order_state', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for order state #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $translatedName = trim((string) $data['name']);
        // order_state_lang.name is limited (validate isGenericName, typically short)
        if (Tools::strlen($translatedName) > 64) {
            $translatedName = Tools::substr($translatedName, 0, 64);
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_order_state FROM `' . _DB_PREFIX_ . 'order_state_lang`
             WHERE id_order_state = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId
        );

        $template = (string) Db::getInstance()->getValue(
            'SELECT template FROM `' . _DB_PREFIX_ . 'order_state_lang`
             WHERE id_order_state = ' . (int) $id . '
               AND id_lang = ' . (int) $this->sourceLangId
        );

        if ($exists) {
            Db::getInstance()->update(
                'order_state_lang',
                array(
                    'name' => pSQL($translatedName),
                ),
                'id_order_state = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
            );
        } else {
            Db::getInstance()->insert('order_state_lang', array(
                'id_order_state' => (int) $id,
                'id_lang' => (int) $this->targetLangId,
                'name' => pSQL($translatedName),
                'template' => pSQL($template),
            ));
        }

        $this->logHistory('order_states', 'order_state', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Order state #%d translated (%s).', 'CkcTranslateJobRunner'),
            (int) $id,
            $translatedName
        ));
    }

    protected function runCkcLabels()
    {
        if (!Module::isInstalled('ckc_label') || !Module::isEnabled('ckc_label')) {
            return $this->finishedResult($this->module->l('ckc_label is not installed.', 'CkcTranslateJobRunner'));
        }

        $filtered = $this->getFilteredSql('ckc_labels');
        $id = $this->getNextId(
            'ckctranslate_ckc_labels_ids',
            'ckctranslate_ckc_labels_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Product labels translation finished.', 'CkcTranslateJobRunner'));
        }

        $sourceText = (string) Db::getInstance()->getValue(
            'SELECT text FROM `' . _DB_PREFIX_ . 'ckc_labels_lang`
             WHERE id_label = ' . (int) $id . '
               AND id_lang = ' . (int) $this->sourceLangId
        );
        if ($sourceText === '') {
            $sourceText = (string) Db::getInstance()->getValue(
                'SELECT text FROM `' . _DB_PREFIX_ . 'ckc_labels` WHERE id_label = ' . (int) $id
            );
        }

        if (trim(strip_tags($sourceText)) === '') {
            $this->logHistory('ckc_labels', 'ckc_label', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Label #%d has no source text, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $targetText = (string) Db::getInstance()->getValue(
            'SELECT text FROM `' . _DB_PREFIX_ . 'ckc_labels_lang`
             WHERE id_label = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId
        );

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            if ($targetText !== '' && $targetText !== $sourceText) {
                $this->logHistory('ckc_labels', 'ckc_label', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Label #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                    (int) $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You translate short PrestaShop product badge labels. Preserve HTML tags (<strong>, <b>, <br>), CSS classes and the ↓ character. Keep similar length. Do not invent promotions.',
            'ckc_labels'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "text": ""
}

Source label HTML:
{$sourceText}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('text'))) {
            $this->logHistory('ckc_labels', 'ckc_label', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for label #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $translated = trim((string) $data['text']);
        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_label FROM `' . _DB_PREFIX_ . 'ckc_labels_lang`
             WHERE id_label = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId
        );

        if ($exists) {
            Db::getInstance()->update(
                'ckc_labels_lang',
                array('text' => pSQL($translated, true)),
                'id_label = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
            );
        } else {
            Db::getInstance()->insert('ckc_labels_lang', array(
                'id_label' => (int) $id,
                'id_lang' => (int) $this->targetLangId,
                'text' => pSQL($translated, true),
            ));
        }

        if (Module::isInstalled('ckc_label')) {
            $labelModule = Module::getInstanceByName('ckc_label');
            if ($labelModule && method_exists($labelModule, 'clearCache')) {
                $labelModule->clearCache();
            }
        }

        $this->logHistory('ckc_labels', 'ckc_label', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Label #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    protected function runHelloworkJobs()
    {
        if (!Module::isInstalled('dtc_feedhelloworkjob') || !Module::isEnabled('dtc_feedhelloworkjob')) {
            return $this->finishedResult($this->module->l('dtc_feedhelloworkjob is not installed.', 'CkcTranslateJobRunner'));
        }

        if (!class_exists('CkcHelloworkI18nHelper', false)) {
            $helperFile = _PS_MODULE_DIR_ . 'dtc_feedhelloworkjob/classes/CkcHelloworkI18nHelper.php';
            if (is_file($helperFile)) {
                require_once $helperFile;
            }
        }
        if (class_exists('CkcHelloworkI18nHelper', false)) {
            CkcHelloworkI18nHelper::ensureSql();
        }

        $filtered = $this->getFilteredSql('hellowork_jobs');
        $id = $this->getNextId(
            'ckctranslate_hellowork_jobs_ids',
            'ckctranslate_hellowork_jobs_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('HelloWork jobs translation finished.', 'CkcTranslateJobRunner'));
        }

        $source = Db::getInstance()->getRow(
            'SELECT title, description FROM `' . _DB_PREFIX_ . 'ckc_hellowork_job_lang`
             WHERE id_job = ' . (int) $id . '
               AND id_lang = ' . (int) $this->sourceLangId
        );
        $sourceTitle = $source ? trim((string) $source['title']) : '';
        $sourceDescription = $source ? (string) $source['description'] : '';

        if ($sourceTitle === '') {
            $this->logHistory('hellowork_jobs', 'hellowork_job', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Job offer #%d has no source title, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $target = Db::getInstance()->getRow(
            'SELECT title, description FROM `' . _DB_PREFIX_ . 'ckc_hellowork_job_lang`
             WHERE id_job = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId
        );
        $targetTitle = $target ? (string) $target['title'] : '';

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            if ($targetTitle !== '' && $targetTitle !== $sourceTitle) {
                $this->logHistory('hellowork_jobs', 'hellowork_job', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Job offer #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                    (int) $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You translate job offers for a company careers page. Preserve HTML. Do not invent salary, benefits or locations. Adapt H/F to M/F when translating to English.',
            'hellowork_jobs'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "title": "",
  "description": ""
}

Source job title:
{$sourceTitle}

Source job description HTML:
{$sourceDescription}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('title'))) {
            $this->logHistory('hellowork_jobs', 'hellowork_job', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for job offer #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $translatedTitle = trim((string) $data['title']);
        $translatedDescription = isset($data['description']) ? (string) $data['description'] : '';
        if (trim(strip_tags($translatedDescription)) === '' && trim(strip_tags($sourceDescription)) !== '') {
            $this->logHistory('hellowork_jobs', 'hellowork_job', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for job offer #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        if (!class_exists('CkcHelloworkI18nHelper', false)) {
            $helperFile = _PS_MODULE_DIR_ . 'dtc_feedhelloworkjob/classes/CkcHelloworkI18nHelper.php';
            if (is_file($helperFile)) {
                require_once $helperFile;
            }
        }
        CkcHelloworkI18nHelper::saveLangRow(
            (int) $id,
            (int) $this->targetLangId,
            $translatedTitle,
            $translatedDescription
        );

        $this->logHistory('hellowork_jobs', 'hellowork_job', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Job offer #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    /**
     * CKC Vianney — titres / alt des photos de réalisations (ckc_realisation).
     */
    protected function runCkcRealisation()
    {
        if (!Module::isInstalled('ckc_realisation') || !Module::isEnabled('ckc_realisation')) {
            return $this->finishedResult($this->module->l('ckc_realisation is not installed.', 'CkcTranslateJobRunner'));
        }

        $filtered = $this->getFilteredSql('ckc_realisation');
        $id = $this->getNextId(
            'ckctranslate_ckc_realisation_ids',
            'ckctranslate_ckc_realisation_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Project photos translation finished.', 'CkcTranslateJobRunner'));
        }

        $source = Db::getInstance()->getRow(
            'SELECT title, alt FROM `' . _DB_PREFIX_ . 'ckc_realisation_lang`
             WHERE id_realisation = ' . (int) $id . '
               AND id_lang = ' . (int) $this->sourceLangId
        );
        $sourceTitle = $source ? trim((string) $source['title']) : '';
        $sourceAlt = $source ? trim((string) $source['alt']) : '';

        if ($sourceTitle === '' && $sourceAlt === '') {
            $this->logHistory('ckc_realisation', 'ckc_realisation', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Project photo #%d has no source title/alt, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $target = Db::getInstance()->getRow(
            'SELECT title, alt FROM `' . _DB_PREFIX_ . 'ckc_realisation_lang`
             WHERE id_realisation = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId
        );
        $targetTitle = $target ? trim((string) $target['title']) : '';
        $targetAlt = $target ? trim((string) $target['alt']) : '';

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $titleDone = ($sourceTitle === '' || ($targetTitle !== '' && $targetTitle !== $sourceTitle));
            $altDone = ($sourceAlt === '' || ($targetAlt !== '' && $targetAlt !== $sourceAlt));
            if ($titleDone && $altDone) {
                $this->logHistory('ckc_realisation', 'ckc_realisation', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Project photo #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                    (int) $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You translate short titles and image alt texts for customer project photos (garden, fencing, outdoor work). Keep them short, natural and descriptive. Do not invent product names, locations or materials. Leave a field empty if the source is empty.',
            'ckc_realisation'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "title": "",
  "alt": ""
}

Source title:
{$sourceTitle}

Source alt:
{$sourceAlt}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);
        $required = array();
        if ($sourceTitle !== '') {
            $required[] = 'title';
        }
        if ($sourceAlt !== '') {
            $required[] = 'alt';
        }

        if (!$this->isValidFields($data, $required)) {
            $this->logHistory('ckc_realisation', 'ckc_realisation', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for project photo #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $translatedTitle = $sourceTitle === '' ? '' : trim((string) $data['title']);
        $translatedAlt = $sourceAlt === '' ? '' : trim((string) (isset($data['alt']) ? $data['alt'] : ''));

        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_realisation FROM `' . _DB_PREFIX_ . 'ckc_realisation_lang`
             WHERE id_realisation = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId
        );

        if ($exists) {
            Db::getInstance()->update(
                'ckc_realisation_lang',
                array(
                    'title' => pSQL($translatedTitle),
                    'alt' => pSQL($translatedAlt),
                ),
                'id_realisation = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
            );
        } else {
            Db::getInstance()->insert('ckc_realisation_lang', array(
                'id_realisation' => (int) $id,
                'id_lang' => (int) $this->targetLangId,
                'title' => pSQL($translatedTitle),
                'alt' => pSQL($translatedAlt),
            ));
        }

        $this->logHistory('ckc_realisation', 'ckc_realisation', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Project photo #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    /**
     * CKC Vianney — FAQ produit (question / réponse HTML).
     */
    protected function runProductFaq()
    {
        if (!Module::isInstalled('productfaq') || !Module::isEnabled('productfaq')) {
            return $this->finishedResult($this->module->l('productfaq is not installed.', 'CkcTranslateJobRunner'));
        }
        $this->ensureProductFaqLangTable();

        $filtered = $this->getFilteredSql('product_faq');
        $id = $this->getNextId(
            'ckctranslate_product_faq_ids',
            'ckctranslate_product_faq_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Product FAQ translation finished.', 'CkcTranslateJobRunner'));
        }

        $source = $this->getProductFaqTexts((int) $id, (int) $this->sourceLangId);
        $sourceQuestion = $source ? trim((string) $source['question']) : '';
        $sourceAnswer = $source ? trim((string) $source['answer']) : '';

        if ($sourceQuestion === '' && $sourceAnswer === '') {
            $this->logHistory('product_faq', 'product_faq', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Product FAQ #%d has no source content, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $target = $this->getProductFaqTexts((int) $id, (int) $this->targetLangId);
        $targetQuestion = $target ? trim((string) $target['question']) : '';
        $targetAnswer = $target ? trim((string) $target['answer']) : '';

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $questionDone = ($sourceQuestion === '' || ($targetQuestion !== '' && $targetQuestion !== $sourceQuestion));
            $answerDone = ($sourceAnswer === '' || ($targetAnswer !== '' && $targetAnswer !== $sourceAnswer));
            if ($questionDone && $answerDone) {
                $this->logHistory('product_faq', 'product_faq', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Product FAQ #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                    (int) $id
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You translate product FAQ questions and answers for a fencing e-commerce catalog. Preserve all HTML tags, attributes and lists exactly. Keep a clear, helpful customer-support tone. Do not invent technical specs, dimensions, warranties or certifications. Leave a field empty if the source is empty.',
            'product_faq'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "question": "",
  "answer": ""
}

Source question:
{$sourceQuestion}

Source answer:
{$sourceAnswer}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);
        $required = array();
        if ($sourceQuestion !== '') {
            $required[] = 'question';
        }
        if ($sourceAnswer !== '') {
            $required[] = 'answer';
        }

        if (!$this->isValidFields($data, $required)) {
            $this->logHistory('product_faq', 'product_faq', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for product FAQ #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $translatedQuestion = $sourceQuestion === '' ? '' : trim((string) $data['question']);
        $translatedAnswer = $sourceAnswer === '' ? '' : trim((string) (isset($data['answer']) ? $data['answer'] : ''));

        $this->saveProductFaqTexts((int) $id, (int) $this->targetLangId, $translatedQuestion, $translatedAnswer);

        $this->logHistory('product_faq', 'product_faq', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Product FAQ #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    /**
     * CKC Vianney — guide de mesure produit (HTML).
     */
    protected function runProductGuide()
    {
        if (!Module::isInstalled('productguide') || !Module::isEnabled('productguide')) {
            return $this->finishedResult($this->module->l('productguide is not installed.', 'CkcTranslateJobRunner'));
        }
        $this->ensureProductGuideLangTable();

        $filtered = $this->getFilteredSql('product_guide');
        $id = $this->getNextId(
            'ckctranslate_product_guide_ids',
            'ckctranslate_product_guide_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Product guide translation finished.', 'CkcTranslateJobRunner'));
        }

        $sourceContent = trim($this->getProductGuideContent((int) $id, (int) $this->sourceLangId));
        if ($sourceContent === '') {
            $this->logHistory('product_guide', 'product_guide', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Product guide #%d has no source content, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $targetContent = trim($this->getProductGuideContent((int) $id, (int) $this->targetLangId));
        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)
            && $targetContent !== ''
            && $targetContent !== $sourceContent
        ) {
            $this->logHistory('product_guide', 'product_guide', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Product guide #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You translate a product measuring guide (HTML) for a fencing e-commerce catalog. Preserve all HTML tags, attributes, images, lists and layout exactly. Keep a clear instructional tone. Do not invent dimensions, tools or steps. Leave the field empty if the source is empty.',
            'product_guide'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "guide_content": ""
}

Source HTML:
{$sourceContent}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);
        if (!$this->isValidFields($data, array('guide_content'))) {
            $this->logHistory('product_guide', 'product_guide', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for product guide #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $this->saveProductGuideContent((int) $id, (int) $this->targetLangId, trim((string) $data['guide_content']));

        $this->logHistory('product_guide', 'product_guide', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Product guide #%d translated.', 'CkcTranslateJobRunner'),
            (int) $id
        ));
    }

    /**
     * CKC Vianney — meta title / description des pages système (index, panier, login…).
     */
    protected function runSystemPages()
    {
        $shopId = (int) Context::getContext()->shop->id;
        $filtered = $this->getFilteredSql('system_pages');

        $id = $this->getNextId(
            'ckctranslate_system_pages_ids',
            'ckctranslate_system_pages_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('System pages SEO translation finished.', 'CkcTranslateJobRunner'));
        }

        $source = Db::getInstance()->getRow(
            'SELECT m.page, ml.title, ml.description, ml.url_rewrite
             FROM `' . _DB_PREFIX_ . 'meta` m
             INNER JOIN `' . _DB_PREFIX_ . 'meta_lang` ml
               ON ml.id_meta = m.id_meta
              AND ml.id_lang = ' . (int) $this->sourceLangId . '
              AND ml.id_shop = ' . (int) $shopId . '
             WHERE m.id_meta = ' . (int) $id
        );

        if (!$source) {
            $this->logHistory('system_pages', 'meta', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('System page #%d has no source content, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $sourceTitle = trim((string) $source['title']);
        $sourceDescription = trim((string) $source['description']);
        $pageCode = (string) $source['page'];
        $label = $pageCode !== '' ? $pageCode : ('#' . (int) $id);

        if ($sourceTitle === '' && $sourceDescription === '') {
            $this->logHistory('system_pages', 'meta', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('System page %s has no source SEO, skipped.', 'CkcTranslateJobRunner'),
                $label
            ));
        }

        $target = Db::getInstance()->getRow(
            'SELECT title, description, url_rewrite
             FROM `' . _DB_PREFIX_ . 'meta_lang`
             WHERE id_meta = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId . '
               AND id_shop = ' . (int) $shopId
        );

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS) && is_array($target)) {
            $targetTitle = trim((string) $target['title']);
            $targetDescription = trim((string) $target['description']);
            $titleDone = ($sourceTitle === '' || !$this->isIncompleteSystemPageSeo($targetTitle, $sourceTitle));
            $descDone = ($sourceDescription === '' || !$this->isIncompleteSystemPageSeo($targetDescription, $sourceDescription, 20));

            if ($titleDone && $descDone) {
                $this->logHistory('system_pages', 'meta', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('System page %s already translated, skipped.', 'CkcTranslateJobRunner'),
                    $label
                ));
            }
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You translate PrestaShop system page SEO (browser tab title and meta description). Keep brand names such as Districlos unchanged, usually at the start of the homepage title. Return COMPLETE texts close in length to the source. Never cut mid-phrase. Never end with &, and, of, the, or a dangling comma. Do not invent promotions. Leave empty source fields empty.',
            'system_pages'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "title": "",
  "description": ""
}

Rules:
- Keep brand names (e.g. Districlos) unchanged.
- Title and description must be complete sentences/phrases, similar in length to the source.
- Do not truncate. Do not end with "&" or a hanging word.

Page code: {$pageCode}

Source meta title:
{$sourceTitle}

Source meta description:
{$sourceDescription}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!is_array($data) || !array_key_exists('title', $data) || !array_key_exists('description', $data)) {
            $this->logHistory('system_pages', 'meta', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for system page %s.', 'CkcTranslateJobRunner'),
                $label
            ));
        }

        // Limites ObjectModel Meta (title 128 / description 255), pas le plafond SEO produit (60).
        $translatedTitle = '';
        if ($sourceTitle !== '') {
            $translatedTitle = $this->normalizeSystemPageSeoText($data['title'], 128);
            if ($this->isIncompleteSystemPageSeo($translatedTitle, $sourceTitle)) {
                $this->logHistory('system_pages', 'meta', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Invalid AI response for system page %s.', 'CkcTranslateJobRunner'),
                    $label
                ));
            }
        }

        $translatedDescription = '';
        if ($sourceDescription !== '') {
            $translatedDescription = $this->normalizeSystemPageSeoText($data['description'], 255);
            if ($this->isIncompleteSystemPageSeo($translatedDescription, $sourceDescription, 20)) {
                $this->logHistory('system_pages', 'meta', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Invalid AI response for system page %s.', 'CkcTranslateJobRunner'),
                    $label
                ));
            }
        }

        $urlRewrite = is_array($target) && isset($target['url_rewrite'])
            ? (string) $target['url_rewrite']
            : (string) $source['url_rewrite'];

        if (is_array($target)) {
            Db::getInstance()->update(
                'meta_lang',
                array(
                    'title' => pSQL($translatedTitle),
                    'description' => pSQL($translatedDescription),
                ),
                'id_meta = ' . (int) $id
                . ' AND id_lang = ' . (int) $this->targetLangId
                . ' AND id_shop = ' . (int) $shopId
            );
        } else {
            $insert = array(
                'id_meta' => (int) $id,
                'id_shop' => (int) $shopId,
                'id_lang' => (int) $this->targetLangId,
                'title' => pSQL($translatedTitle),
                'description' => pSQL($translatedDescription),
                'url_rewrite' => pSQL($urlRewrite),
            );

            // CKC Vianney — certaines boutiques ont encore keywords dans meta_lang.
            $hasKeywords = (bool) Db::getInstance()->executeS(
                'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'meta_lang` LIKE \'keywords\''
            );
            if ($hasKeywords) {
                $insert['keywords'] = '';
            }

            Db::getInstance()->insert('meta_lang', $insert);
        }

        $this->logHistory('system_pages', 'meta', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('System page %s translated.', 'CkcTranslateJobRunner'),
            $label
        ));
    }

    /**
     * CKC Vianney — autonome : productfaq 1.0.0 n'a pas ensureLangTable().
     */
    public static function ensureProductFaqLangTable()
    {
        return (bool) Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_faq_lang` (
            `id_faq` INT(11) NOT NULL,
            `id_lang` INT UNSIGNED NOT NULL,
            `question` TEXT NOT NULL,
            `answer` TEXT NOT NULL,
            PRIMARY KEY (`id_faq`, `id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
    }

    /**
     * @return array{question: string, answer: string}|false
     */
    public static function getProductFaqTexts($idFaq, $idLang)
    {
        self::ensureProductFaqLangTable();
        $idFaq = (int) $idFaq;
        $idLang = (int) $idLang;
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        if ($idLang === $defaultLang) {
            return Db::getInstance()->getRow(
                'SELECT question, answer FROM `' . _DB_PREFIX_ . 'product_faq` WHERE id = ' . (int) $idFaq
            );
        }

        $row = Db::getInstance()->getRow(
            'SELECT question, answer FROM `' . _DB_PREFIX_ . 'product_faq_lang`
             WHERE id_faq = ' . (int) $idFaq . ' AND id_lang = ' . (int) $idLang
        );
        if ($row && trim((string) $row['question']) !== '') {
            return $row;
        }

        return Db::getInstance()->getRow(
            'SELECT question, answer FROM `' . _DB_PREFIX_ . 'product_faq` WHERE id = ' . (int) $idFaq
        );
    }

    public static function getFrontProductFaqs($idProduct, $activeOnly = true, $idLang = null)
    {
        self::ensureProductFaqLangTable();
        if ($idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }

        $sql = 'SELECT f.id, f.id_product, f.active,
                COALESCE(NULLIF(TRIM(l.question), \'\'), f.question) AS question,
                COALESCE(NULLIF(TRIM(l.answer), \'\'), f.answer) AS answer
            FROM `' . _DB_PREFIX_ . 'product_faq` f
            LEFT JOIN `' . _DB_PREFIX_ . 'product_faq_lang` l
              ON l.id_faq = f.id AND l.id_lang = ' . (int) $idLang . '
            WHERE f.id_product = ' . (int) $idProduct;

        if ($activeOnly) {
            $sql .= ' AND f.active = 1';
        }

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : array();
    }

    public static function saveProductFaqTexts($idFaq, $idLang, $question, $answer)
    {
        self::ensureProductFaqLangTable();
        $idFaq = (int) $idFaq;
        $idLang = (int) $idLang;
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $data = array(
            'question' => pSQL($question, true),
            'answer' => pSQL($answer, true),
        );

        if ($idLang === $defaultLang) {
            return Db::getInstance()->update('product_faq', $data, 'id = ' . (int) $idFaq);
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_faq FROM `' . _DB_PREFIX_ . 'product_faq_lang`
             WHERE id_faq = ' . (int) $idFaq . ' AND id_lang = ' . (int) $idLang
        );
        if ($exists) {
            return Db::getInstance()->update(
                'product_faq_lang',
                $data,
                'id_faq = ' . (int) $idFaq . ' AND id_lang = ' . (int) $idLang
            );
        }

        return Db::getInstance()->insert('product_faq_lang', array_merge($data, array(
            'id_faq' => (int) $idFaq,
            'id_lang' => (int) $idLang,
        )));
    }

    public static function ensureProductGuideLangTable()
    {
        return (bool) Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_guide_lang` (
            `id_product` INT(11) NOT NULL,
            `id_lang` INT UNSIGNED NOT NULL,
            `guide_content` TEXT NOT NULL,
            PRIMARY KEY (`id_product`, `id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
    }

    public static function getProductGuideContent($idProduct, $idLang)
    {
        self::ensureProductGuideLangTable();
        $idProduct = (int) $idProduct;
        $idLang = (int) $idLang;
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        if ($idLang === $defaultLang) {
            return (string) Db::getInstance()->getValue(
                'SELECT guide_content FROM `' . _DB_PREFIX_ . 'product_guide` WHERE id_product = ' . (int) $idProduct
            );
        }

        $row = (string) Db::getInstance()->getValue(
            'SELECT guide_content FROM `' . _DB_PREFIX_ . 'product_guide_lang`
             WHERE id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang
        );
        if (trim($row) !== '') {
            return $row;
        }

        return (string) Db::getInstance()->getValue(
            'SELECT guide_content FROM `' . _DB_PREFIX_ . 'product_guide` WHERE id_product = ' . (int) $idProduct
        );
    }

    public static function getFrontProductGuideContent($idProduct, $idLang = null)
    {
        self::ensureProductGuideLangTable();
        if ($idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }

        $row = Db::getInstance()->getRow(
            'SELECT COALESCE(NULLIF(TRIM(l.guide_content), \'\'), g.guide_content) AS guide_content
             FROM `' . _DB_PREFIX_ . 'product_guide` g
             LEFT JOIN `' . _DB_PREFIX_ . 'product_guide_lang` l
               ON l.id_product = g.id_product AND l.id_lang = ' . (int) $idLang . '
             WHERE g.id_product = ' . (int) $idProduct
        );

        return $row ? (string) $row['guide_content'] : '';
    }

    public static function saveProductGuideContent($idProduct, $idLang, $content)
    {
        self::ensureProductGuideLangTable();
        $idProduct = (int) $idProduct;
        $idLang = (int) $idLang;
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $data = array('guide_content' => pSQL($content, true));

        if ($idLang === $defaultLang) {
            return Db::getInstance()->update('product_guide', $data, 'id_product = ' . (int) $idProduct);
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_product FROM `' . _DB_PREFIX_ . 'product_guide_lang`
             WHERE id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang
        );
        if ($exists) {
            return Db::getInstance()->update(
                'product_guide_lang',
                $data,
                'id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang
            );
        }

        return Db::getInstance()->insert('product_guide_lang', array_merge($data, array(
            'id_product' => (int) $idProduct,
            'id_lang' => (int) $idLang,
        )));
    }

    /**
     * CKC Vianney — Responsive Gallery (titre de page + titres de galeries).
     */
    protected function runResponsiveGallery()
    {
        if (!Module::isInstalled('responsivegallery') || !Module::isEnabled('responsivegallery')) {
            return $this->finishedResult($this->module->l('responsivegallery is not installed.', 'CkcTranslateJobRunner'));
        }

        $item = $this->getNextTypedQueueItem('responsivegallery', array($this, 'buildResponsiveGalleryQueue'));
        if (!$item) {
            return $this->finishedResult($this->module->l('Responsive Gallery translation finished.', 'CkcTranslateJobRunner'));
        }

        $type = isset($item['type']) ? (string) $item['type'] : '';
        if ($type === 'config') {
            return $this->translateResponsiveGalleryConfig();
        }
        if ($type === 'gallery') {
            return $this->translateResponsiveGalleryGallery((int) $item['id']);
        }

        $this->logHistory('responsivegallery', $type, (int) (isset($item['id']) ? $item['id'] : 0), 'skipped', $this->module->l('Unknown Responsive Gallery item.', 'CkcTranslateJobRunner'));

        return $this->nextResult($this->module->l('Unknown Responsive Gallery item, skipped.', 'CkcTranslateJobRunner'));
    }

    protected function buildResponsiveGalleryQueue()
    {
        return $this->buildResponsiveGalleryQueueItems();
    }

    public function buildResponsiveGalleryQueueItems($forceOnlyMissing = null)
    {
        if (!Module::isInstalled('responsivegallery') || !Module::isEnabled('responsivegallery')) {
            return array();
        }

        if ($forceOnlyMissing === null) {
            $onlyMissing = (bool) (int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS);
        } else {
            $onlyMissing = (bool) $forceOnlyMissing;
        }

        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $shopId = (int) Context::getContext()->shop->id;
        $items = array();

        if ($filterType !== 'ids') {
            $config = $this->getResponsiveGalleryConfigPair();
            if ($this->responsiveGalleryFieldsNeedTranslation($config['source'], $config['target'], $onlyMissing)) {
                $items[] = array('type' => 'config', 'id' => 1);
            }
        }

        $sql = 'SELECT g.id_responsive_gallery_gallery AS id
                FROM `' . _DB_PREFIX_ . 'responsive_gallery_gallery` g
                INNER JOIN `' . _DB_PREFIX_ . 'responsive_gallery_gallery_lang` src
                  ON src.id_responsive_gallery_gallery = g.id_responsive_gallery_gallery
                 AND src.id_lang = ' . (int) $this->sourceLangId . '
                 AND src.id_shop = ' . (int) $shopId . '
                WHERE (
                    (src.title IS NOT NULL AND TRIM(src.title) != \'\')
                    OR (src.description IS NOT NULL AND TRIM(src.description) != \'\')
                    OR (src.alt IS NOT NULL AND TRIM(src.alt) != \'\')
                )';

        if ($filterType === 'ids' && !empty($filterIds)) {
            $sql .= ' AND g.id_responsive_gallery_gallery IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }

        if ($onlyMissing) {
            $sql .= ' AND NOT EXISTS (
                SELECT 1 FROM `' . _DB_PREFIX_ . 'responsive_gallery_gallery_lang` tgt
                WHERE tgt.id_responsive_gallery_gallery = g.id_responsive_gallery_gallery
                  AND tgt.id_lang = ' . (int) $this->targetLangId . '
                  AND tgt.id_shop = ' . (int) $shopId . '
                  AND (
                    (src.title IS NULL OR TRIM(src.title) = \'\' OR (TRIM(IFNULL(tgt.title, \'\')) != \'\' AND tgt.title != src.title))
                    AND
                    (src.description IS NULL OR TRIM(src.description) = \'\' OR (TRIM(IFNULL(tgt.description, \'\')) != \'\' AND tgt.description != src.description))
                    AND
                    (src.alt IS NULL OR TRIM(src.alt) = \'\' OR (TRIM(IFNULL(tgt.alt, \'\')) != \'\' AND tgt.alt != src.alt))
                  )
            )';
        }

        $sql .= ' ORDER BY g.id_responsive_gallery_gallery ASC';
        $rows = Db::getInstance()->executeS($sql);
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $items[] = array(
                    'type' => 'gallery',
                    'id' => (int) $row['id'],
                );
            }
        }

        return $items;
    }

    public function countResponsiveGalleryItems()
    {
        if (!Module::isInstalled('responsivegallery')) {
            return 0;
        }

        $shopId = (int) Context::getContext()->shop->id;
        $galleries = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop`
             WHERE id_shop = ' . (int) $shopId
        );

        return 1 + $galleries;
    }

    protected function getResponsiveGalleryConfigPair()
    {
        $keys = array(
            'title_page' => 'RG_TITLE_PAGE',
            'description_page' => 'RG_DESCRIPTION_PAGE',
            'title_home' => 'RG_TITLE_HOME',
            'description_home' => 'RG_DESCRIPTION_HOME',
        );
        $source = array();
        $target = array();
        foreach ($keys as $field => $confKey) {
            $source[$field] = trim((string) Configuration::get($confKey, (int) $this->sourceLangId));
            $target[$field] = trim((string) Configuration::get($confKey, (int) $this->targetLangId));
        }

        return array(
            'source' => $source,
            'target' => $target,
            'keys' => $keys,
        );
    }

    protected function responsiveGalleryFieldsNeedTranslation(array $source, array $target, $onlyMissing)
    {
        $hasSource = false;
        foreach ($source as $value) {
            if (trim(strip_tags((string) $value)) !== '') {
                $hasSource = true;
                break;
            }
        }
        if (!$hasSource) {
            return false;
        }
        if (!$onlyMissing) {
            return true;
        }
        foreach ($source as $field => $value) {
            $src = trim(strip_tags((string) $value));
            if ($src === '') {
                continue;
            }
            $tgt = isset($target[$field]) ? trim((string) $target[$field]) : '';
            if ($tgt === '' || $tgt === $value) {
                return true;
            }
        }

        return false;
    }

    protected function translateResponsiveGalleryConfig()
    {
        $pair = $this->getResponsiveGalleryConfigPair();
        $source = $pair['source'];
        $target = $pair['target'];
        $onlyMissing = (bool) (int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS);

        if (!$this->responsiveGalleryFieldsNeedTranslation($source, $target, $onlyMissing)) {
            $this->logHistory('responsivegallery', 'config', 1, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

            return $this->nextResult($this->module->l('Responsive Gallery page texts already translated, skipped.', 'CkcTranslateJobRunner'));
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You translate Responsive Gallery page titles and short HTML descriptions for a fencing e-commerce site. Keep titles short. Preserve HTML. Do not invent product lines.',
            'responsivegallery'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "title_page": "",
  "description_page": "",
  "title_home": "",
  "description_home": ""
}

Source page title:
{$source['title_page']}

Source page description HTML:
{$source['description_page']}

Source home title:
{$source['title_home']}

Source home description HTML:
{$source['description_home']}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);
        $required = array();
        foreach ($source as $field => $value) {
            if (trim(strip_tags((string) $value)) !== '') {
                $required[] = $field;
            }
        }

        if (!$this->isValidFields($data, $required)) {
            $this->logHistory('responsivegallery', 'config', 1, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult($this->module->l('Invalid AI response for Responsive Gallery page texts.', 'CkcTranslateJobRunner'));
        }

        foreach ($pair['keys'] as $field => $confKey) {
            $sourceValue = $source[$field];
            if (trim(strip_tags((string) $sourceValue)) === '') {
                continue;
            }
            $translated = isset($data[$field]) ? trim((string) $data[$field]) : '';
            if ($translated === '') {
                continue;
            }
            $this->updateConfigurationLangValue($confKey, $translated, strpos($field, 'description') !== false);
        }

        $this->logHistory('responsivegallery', 'config', 1, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult($this->module->l('Responsive Gallery page texts translated.', 'CkcTranslateJobRunner'));
    }

    protected function translateResponsiveGalleryGallery($id)
    {
        $id = (int) $id;
        $shopId = (int) Context::getContext()->shop->id;
        $source = Db::getInstance()->getRow(
            'SELECT title, description, alt FROM `' . _DB_PREFIX_ . 'responsive_gallery_gallery_lang`
             WHERE id_responsive_gallery_gallery = ' . (int) $id . '
               AND id_lang = ' . (int) $this->sourceLangId . '
               AND id_shop = ' . (int) $shopId
        );
        $sourceTitle = $source ? trim((string) $source['title']) : '';
        $sourceDescription = $source ? (string) $source['description'] : '';
        $sourceAlt = $source ? trim((string) $source['alt']) : '';

        if ($sourceTitle === '' && trim(strip_tags($sourceDescription)) === '' && $sourceAlt === '') {
            $this->logHistory('responsivegallery', 'gallery', $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Gallery #%d has no source content, skipped.', 'CkcTranslateJobRunner'),
                $id
            ));
        }

        $target = Db::getInstance()->getRow(
            'SELECT title, description, alt FROM `' . _DB_PREFIX_ . 'responsive_gallery_gallery_lang`
             WHERE id_responsive_gallery_gallery = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId . '
               AND id_shop = ' . (int) $shopId
        );
        $targetFields = array(
            'title' => $target ? trim((string) $target['title']) : '',
            'description' => $target ? (string) $target['description'] : '',
            'alt' => $target ? trim((string) $target['alt']) : '',
        );
        $sourceFields = array(
            'title' => $sourceTitle,
            'description' => $sourceDescription,
            'alt' => $sourceAlt,
        );

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)
            && !$this->responsiveGalleryFieldsNeedTranslation($sourceFields, $targetFields, true)
        ) {
            $this->logHistory('responsivegallery', 'gallery', $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Gallery #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                $id
            ));
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You translate Responsive Gallery titles for a fencing catalog. Titles are short product-family names (e.g. Grillage rigide → Rigid fencing). Keep them short. Preserve HTML in descriptions. Leave empty fields empty.',
            'responsivegallery'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "title": "",
  "description": "",
  "alt": ""
}

Source title:
{$sourceTitle}

Source description HTML:
{$sourceDescription}

Source alt:
{$sourceAlt}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);
        $required = array();
        if ($sourceTitle !== '') {
            $required[] = 'title';
        }
        if (trim(strip_tags($sourceDescription)) !== '') {
            $required[] = 'description';
        }
        if ($sourceAlt !== '') {
            $required[] = 'alt';
        }

        if (!$this->isValidFields($data, $required)) {
            $this->logHistory('responsivegallery', 'gallery', $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for gallery #%d.', 'CkcTranslateJobRunner'),
                $id
            ));
        }

        $translatedTitle = $sourceTitle === '' ? '' : trim((string) $data['title']);
        $translatedDescription = trim(strip_tags($sourceDescription)) === '' ? '' : (string) $data['description'];
        $translatedAlt = $sourceAlt === '' ? '' : trim((string) (isset($data['alt']) ? $data['alt'] : ''));

        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_responsive_gallery_gallery FROM `' . _DB_PREFIX_ . 'responsive_gallery_gallery_lang`
             WHERE id_responsive_gallery_gallery = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId . '
               AND id_shop = ' . (int) $shopId
        );

        if ($exists) {
            Db::getInstance()->update(
                'responsive_gallery_gallery_lang',
                array(
                    'title' => pSQL($translatedTitle, true),
                    'description' => pSQL($translatedDescription, true),
                    'alt' => pSQL($translatedAlt),
                ),
                'id_responsive_gallery_gallery = ' . (int) $id . '
                 AND id_lang = ' . (int) $this->targetLangId . '
                 AND id_shop = ' . (int) $shopId
            );
        } else {
            Db::getInstance()->insert('responsive_gallery_gallery_lang', array(
                'id_responsive_gallery_gallery' => (int) $id,
                'id_shop' => (int) $shopId,
                'id_lang' => (int) $this->targetLangId,
                'title' => pSQL($translatedTitle, true),
                'description' => pSQL($translatedDescription, true),
                'alt' => pSQL($translatedAlt),
            ));
        }

        $this->logHistory('responsivegallery', 'gallery', $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Gallery #%d translated (%s).', 'CkcTranslateJobRunner'),
            $id,
            $translatedTitle !== '' ? $translatedTitle : $sourceTitle
        ));
    }

    protected function updateConfigurationLangValue($key, $translated, $html = false)
    {
        $values = array();
        foreach (Language::getLanguages(false) as $lang) {
            $values[(int) $lang['id_lang']] = (string) Configuration::get($key, (int) $lang['id_lang']);
        }
        $values[(int) $this->targetLangId] = $translated;
        Configuration::updateValue($key, $values, (bool) $html);
    }

    /**
     * CKC Vianney — Contact Form Ultimate (ets_cfu / ets_cfultimate).
     */
    protected function runEtsCfu()
    {
        $tables = self::discoverEtsCfuLangTables();
        if (empty($tables)) {
            return array(
                'success' => false,
                'message' => $this->module->l('Contact Form Ultimate tables not found. Install and enable ets_cfultimate (ets_cfu).', 'CkcTranslateJobRunner'),
                'finished' => true,
            );
        }

        $item = $this->getNextTypedQueueItem('ets_cfu', array($this, 'buildEtsCfuQueue'));
        if (!$item) {
            return $this->finishedResult($this->module->l('Contact Form Ultimate translation finished.', 'CkcTranslateJobRunner'));
        }

        return $this->translateEtsCfuRow((string) $item['type'], (int) $item['id']);
    }

    /**
     * @return array<int, array{table: string, id_column: string, fields: array, has_id_shop: bool, parent_table: string, form_id_column: string}>
     */
    public static function discoverEtsCfuLangTables()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $found = array();
        $db = Db::getInstance();
        $like = str_replace('_', '\\_', _DB_PREFIX_) . 'ets\\_cfu%\\_lang';
        $rows = $db->executeS('SHOW TABLES LIKE "' . $like . '"');
        if (!is_array($rows)) {
            $cache = array();

            return $cache;
        }

        foreach ($rows as $row) {
            $fullName = (string) reset($row);
            $prefix = _DB_PREFIX_;
            if (strpos($fullName, $prefix) !== 0) {
                continue;
            }
            $table = substr($fullName, strlen($prefix));
            if (!preg_match('/^ets_cfu[a-z0-9_]*_lang$/', $table)) {
                continue;
            }
            $meta = self::getEtsCfuLangTableMeta($table);
            if ($meta) {
                $found[] = $meta;
            }
        }

        $cache = $found;

        return $cache;
    }

    /**
     * @param string $table Table name without prefix
     *
     * @return array|null
     */
    public static function getEtsCfuLangTableMeta($table)
    {
        $table = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $table));
        if ($table === '' || strpos($table, 'ets_cfu') !== 0 || substr($table, -5) !== '_lang') {
            return null;
        }

        $cols = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . bqSQL($table) . '`');
        if (!is_array($cols) || empty($cols)) {
            return null;
        }

        $parentTable = substr($table, 0, -5);
        $shortName = preg_replace('/^ets_cfu_/', '', $parentTable);
        $expectedPks = array_values(array_unique(array_filter(array(
            'id_' . $parentTable,
            $shortName !== '' ? 'id_' . $shortName : '',
            $table === 'ets_cfu_contact_lang' ? 'id_contact' : '',
        ))));
        $skipFields = array('thank_you_url');
        $slugFields = array('alias', 'link_rewrite', 'friendly_url', 'url_alias', 'title_alias', 'thank_you_alias');
        $idCandidates = array();
        $priKeys = array();
        $hasIdShop = false;
        $hasFormId = false;
        $fields = array();
        foreach ($cols as $col) {
            $name = (string) $col['Field'];
            $type = strtolower((string) $col['Type']);
            $key = isset($col['Key']) ? strtoupper((string) $col['Key']) : '';
            if ($name === 'id_lang') {
                continue;
            }
            if ($name === 'id_shop') {
                $hasIdShop = true;
                continue;
            }
            if ($name === 'id_ets_cfu_form' || $name === 'id_contact') {
                $hasFormId = true;
            }
            if (strpos($name, 'id_') === 0) {
                $idCandidates[] = $name;
                if ($key === 'PRI') {
                    $priKeys[] = $name;
                }
                continue;
            }
            if (in_array($name, $skipFields, true)) {
                continue;
            }
            if (!preg_match('/char|text|enum|json|blob/', $type)) {
                continue;
            }
            if (preg_match('/^(tiny|medium|long)?blob$/', $type)) {
                continue;
            }
            $length = 0;
            if (preg_match('/\((\d+)\)/', $type, $m)) {
                $length = (int) $m[1];
            }
            $fields[$name] = array(
                'html' => (bool) preg_match('/text|blob|json/', $type),
                'slug' => in_array($name, $slugFields, true),
                'length' => $length,
            );
        }

        $idColumn = self::pickEtsCfuIdColumn($expectedPks, $idCandidates, $priKeys);
        if ($idColumn === '' || empty($fields)) {
            return null;
        }

        $formIdColumn = '';
        if (in_array('id_ets_cfu_form', $idCandidates, true)) {
            $formIdColumn = 'id_ets_cfu_form';
        } elseif ($idColumn === 'id_contact' || in_array('id_contact', $idCandidates, true)) {
            $formIdColumn = 'id_contact';
            $hasFormId = true;
        } elseif (self::etsCfuTableHasColumn($parentTable, 'id_ets_cfu_form')) {
            $formIdColumn = 'id_ets_cfu_form';
        } elseif (self::etsCfuTableHasColumn($parentTable, 'id_contact')) {
            $formIdColumn = 'id_contact';
        }

        return array(
            'table' => $table,
            'id_column' => $idColumn,
            'fields' => $fields,
            'has_id_shop' => $hasIdShop,
            'has_form_id_on_lang' => $hasFormId,
            'parent_table' => $parentTable,
            'form_id_column' => $formIdColumn,
        );
    }

    /**
     * @param string[] $expectedPks
     * @param string[] $idCandidates
     * @param string[] $priKeys
     *
     * @return string
     */
    protected static function pickEtsCfuIdColumn(array $expectedPks, array $idCandidates, array $priKeys)
    {
        foreach ($expectedPks as $expectedPk) {
            if ($expectedPk !== '' && in_array($expectedPk, $idCandidates, true)) {
                return $expectedPk;
            }
        }
        $priWithoutForm = array_values(array_diff($priKeys, array('id_ets_cfu_form')));
        if (count($priWithoutForm) === 1) {
            return $priWithoutForm[0];
        }
        if (count($priKeys) === 1) {
            return $priKeys[0];
        }
        $candidatesWithoutForm = array_values(array_diff($idCandidates, array('id_ets_cfu_form')));
        if (!empty($candidatesWithoutForm)) {
            return $candidatesWithoutForm[0];
        }

        return !empty($idCandidates) ? $idCandidates[0] : '';
    }

    /**
     * @param string $table
     * @param string $column
     *
     * @return bool
     */
    protected static function etsCfuTableHasColumn($table, $column)
    {
        $table = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $table));
        $column = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $column));
        if ($table === '' || $column === '') {
            return false;
        }
        $tableLike = str_replace('_', '\\_', _DB_PREFIX_ . $table);
        if (!(bool) Db::getInstance()->executeS('SHOW TABLES LIKE "' . $tableLike . '"')) {
            return false;
        }

        $columnLike = str_replace('_', '\\_', $column);

        return (bool) Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . bqSQL($table) . '` LIKE "' . $columnLike . '"'
        );
    }

    /**
     * @return array<int, array{type: string, id: int}>
     */
    protected function buildEtsCfuQueue()
    {
        return $this->buildEtsCfuQueueItems((bool) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS));
    }

    /**
     * @param bool $onlyMissing
     *
     * @return array<int, array{type: string, id: int}>
     */
    protected function buildEtsCfuQueueItems($onlyMissing)
    {
        $items = array();
        $shopId = (int) Context::getContext()->shop->id;
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $useFormFilter = ($filterType === 'ids' && !empty($filterIds));

        foreach (self::discoverEtsCfuLangTables() as $meta) {
            $idCol = bqSQL($meta['id_column']);
            $table = bqSQL($meta['table']);
            $contentParts = array();
            foreach (array_keys($meta['fields']) as $field) {
                $contentParts[] = 'TRIM(IFNULL(src.`' . bqSQL($field) . '`, \'\')) != \'\'';
            }

            $sql = 'SELECT DISTINCT src.`' . $idCol . '` AS id
                    FROM `' . _DB_PREFIX_ . $table . '` src';

            $needParentJoin = $useFormFilter
                && !empty($meta['form_id_column'])
                && empty($meta['has_form_id_on_lang'])
                && !empty($meta['parent_table'])
                && $meta['form_id_column'] !== $meta['id_column'];
            if ($needParentJoin) {
                $sql .= ' INNER JOIN `' . _DB_PREFIX_ . bqSQL($meta['parent_table']) . '` p
                            ON p.`' . $idCol . '` = src.`' . $idCol . '`';
            }

            if ($onlyMissing) {
                $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . $table . '` tgt
                            ON tgt.`' . $idCol . '` = src.`' . $idCol . '`
                           AND tgt.id_lang = ' . (int) $this->targetLangId;
                if (!empty($meta['has_id_shop'])) {
                    $sql .= ' AND tgt.id_shop = src.id_shop';
                }
            }

            $sql .= ' WHERE src.id_lang = ' . (int) $this->sourceLangId;
            if (!empty($contentParts)) {
                $sql .= ' AND (' . implode(' OR ', $contentParts) . ')';
            }
            if (!empty($meta['has_id_shop']) && $shopId > 0) {
                $sql .= ' AND src.id_shop IN (0, ' . (int) $shopId . ')';
            }
            if ($useFormFilter && !empty($meta['form_id_column'])) {
                $in = implode(',', array_map('intval', $filterIds));
                if (!empty($meta['has_form_id_on_lang']) || $meta['form_id_column'] === $meta['id_column']) {
                    $sql .= ' AND src.`' . bqSQL($meta['form_id_column']) . '` IN (' . $in . ')';
                } elseif ($needParentJoin) {
                    $sql .= ' AND p.`' . bqSQL($meta['form_id_column']) . '` IN (' . $in . ')';
                }
            }

            if ($onlyMissing) {
                $missingParts = array();
                foreach (self::getEtsCfuMissingFieldNames($meta) as $field) {
                    $f = bqSQL($field);
                    $missingParts[] = '(
                        TRIM(IFNULL(src.`' . $f . '`, \'\')) != \'\'
                        AND (
                            tgt.`' . $idCol . '` IS NULL
                            OR TRIM(IFNULL(tgt.`' . $f . '`, \'\')) = \'\'
                            OR tgt.`' . $f . '` = src.`' . $f . '`
                        )
                    )';
                }
                if (!empty($missingParts)) {
                    $sql .= ' AND (' . implode(' OR ', $missingParts) . ')';
                }
            }

            $sql .= ' ORDER BY src.`' . $idCol . '` ASC';

            try {
                $rows = Db::getInstance()->executeS($sql);
            } catch (Exception $e) {
                PrestaShopLogger::addLog(
                    'CKC Translate ets_cfu SQL error on ' . $meta['table'] . ': ' . $e->getMessage(),
                    3
                );
                continue;
            }
            if (!is_array($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                $id = (int) $row['id'];
                if ($id <= 0) {
                    continue;
                }
                $items[] = array(
                    'type' => $meta['table'],
                    'id' => $id,
                );
            }
        }

        foreach ($this->buildEtsCfuFormQueueItems($onlyMissing, $useFormFilter, $filterIds) as $formItem) {
            $items[] = $formItem;
        }

        return $items;
    }

    /**
     * Champs qui déterminent le badge « restant » (pas les 40 messages identiques FR/EN).
     *
     * @param array $meta
     *
     * @return string[]
     */
    protected static function getEtsCfuMissingFieldNames(array $meta)
    {
        $available = array_keys($meta['fields']);
        $preferred = array(
            'short_code',
            'title',
            'button_label',
            'floating_label',
            'subject',
            'thank_you_page_title',
            'thank_you_message',
        );
        $use = array_values(array_intersect($preferred, $available));

        return !empty($use) ? $use : $available;
    }

    /**
     * Total rows with source content (all ets_cfu lang tables).
     *
     * @return int
     */
    public function countEtsCfuItems()
    {
        return count($this->buildEtsCfuQueueItems(false));
    }

    /**
     * @param string $table
     * @param int    $id
     *
     * @return array
     */
    protected function translateEtsCfuRow($table, $id)
    {
        $id = (int) $id;
        if ($table === CkcTranslateCfuI18n::VIRTUAL_TYPE) {
            return $this->translateEtsCfuFormBody($id);
        }

        $meta = self::getEtsCfuLangTableMeta($table);
        if (!$meta || $id <= 0) {
            $this->logHistory('ets_cfu', $table, $id, 'skipped', $this->module->l('Invalid Contact Form Ultimate row.', 'CkcTranslateJobRunner'));

            return $this->nextResult($this->module->l('Invalid Contact Form Ultimate row skipped.', 'CkcTranslateJobRunner'));
        }

        $shopId = (int) Context::getContext()->shop->id;
        $shopSql = '';
        if (!empty($meta['has_id_shop']) && $shopId > 0) {
            $shopSql = ' AND id_shop IN (0, ' . (int) $shopId . ')';
        }

        $source = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . bqSQL($meta['table']) . '`
             WHERE `' . bqSQL($meta['id_column']) . '` = ' . $id . '
               AND id_lang = ' . (int) $this->sourceLangId . $shopSql
        );
        if (!$source && $shopSql !== '') {
            $source = Db::getInstance()->getRow(
                'SELECT * FROM `' . _DB_PREFIX_ . bqSQL($meta['table']) . '`
                 WHERE `' . bqSQL($meta['id_column']) . '` = ' . $id . '
                   AND id_lang = ' . (int) $this->sourceLangId
            );
        }
        if (!$source) {
            $this->logHistory('ets_cfu', $meta['table'], $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Contact Form Ultimate %1$s #%2$d has no source row, skipped.', 'CkcTranslateJobRunner'),
                $meta['table'],
                $id
            ));
        }

        $target = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . bqSQL($meta['table']) . '`
             WHERE `' . bqSQL($meta['id_column']) . '` = ' . $id . '
               AND id_lang = ' . (int) $this->targetLangId . $shopSql
        );

        $translated = array();
        $toTranslate = array();
        foreach ($meta['fields'] as $field => $fieldMeta) {
            $sourceVal = isset($source[$field]) ? (string) $source[$field] : '';
            if (trim(strip_tags($sourceVal)) === '' && trim($sourceVal) === '') {
                continue;
            }
            if (preg_match('/^[\d.\s]+$/', trim($sourceVal))) {
                continue;
            }
            if (preg_match('/^#[0-9a-fA-F]{3,8}$/', trim($sourceVal))) {
                continue;
            }
            $trimVal = ltrim($sourceVal);
            if ($trimVal !== '' && preg_match('/^[aOsidbNC]:\d+[:;{]/', $trimVal)) {
                continue;
            }
            $targetVal = ($target && isset($target[$field])) ? (string) $target[$field] : '';
            if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)
                && $targetVal !== ''
                && $targetVal !== $sourceVal
            ) {
                continue;
            }
            if ($field === 'short_code') {
                $shortCodeResult = $this->translateEtsCfuShortCodeHtml($sourceVal, $id);
                if (is_string($shortCodeResult) && $shortCodeResult !== '') {
                    $translated[$field] = $shortCodeResult;
                }
                continue;
            }
            $decodedJson = json_decode($sourceVal, true);
            if (is_array($decodedJson)) {
                $translatedJson = $this->translateEtsCfuJsonTree($decodedJson, $table, $id, $field);
                if (is_array($translatedJson)) {
                    $encoded = json_encode($translatedJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    if (is_string($encoded) && $encoded !== '') {
                        $translated[$field] = $encoded;
                    }
                }
                continue;
            }
            $toTranslate[$field] = $sourceVal;
        }

        if (empty($toTranslate) && empty($translated)) {
            $this->logHistory('ets_cfu', $meta['table'], $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Contact Form Ultimate %1$s #%2$d already translated, skipped.', 'CkcTranslateJobRunner'),
                $meta['table'],
                $id
            ));
        }

        $chunks = array_chunk($toTranslate, 8, true);
        foreach ($chunks as $chunk) {
            $jsonKeys = array();
            $sourceBlocks = '';
            foreach ($chunk as $field => $value) {
                $jsonKeys[] = '  "' . $field . '": ""';
                $sourceBlocks .= "\nSource {$field}:\n{$value}\n";
            }
            $jsonExample = "{\n" . implode(",\n", $jsonKeys) . "\n}";

            $systemPrompt = $this->buildSystemPrompt(
                'You translate PrestaShop Contact Form Ultimate texts (form titles, SEO, labels, emails, validation messages, thank-you pages). Preserve HTML, attributes, links, emails and phone numbers. If a value is JSON, keep the JSON structure and translate string values only. Keep URL aliases slug-safe (title_alias, thank_you_alias). Do not invent legal terms.',
                'ets_cfu'
            );

            $prompt = <<<PROMPT
Return ONLY valid JSON with these keys:
{$jsonExample}
{$sourceBlocks}
PROMPT;

            $data = $this->ai->requestJson($systemPrompt, $prompt);
            if (!is_array($data)) {
                continue;
            }

            foreach ($chunk as $field => $sourceVal) {
                if (!isset($data[$field])) {
                    continue;
                }
                $value = (string) $data[$field];
                if (trim(strip_tags($value)) === '' && trim(strip_tags($sourceVal)) !== '') {
                    continue;
                }
                $fieldMeta = $meta['fields'][$field];
                if (!empty($fieldMeta['slug'])) {
                    $value = $this->normalizeSlug($value);
                }
                if (!empty($fieldMeta['length']) && Tools::strlen($value) > (int) $fieldMeta['length']) {
                    $value = Tools::substr($value, 0, (int) $fieldMeta['length']);
                }
                $translated[$field] = $value;
            }
        }

        if (empty($translated)) {
            $this->logHistory('ets_cfu', $meta['table'], $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for Contact Form Ultimate %1$s #%2$d.', 'CkcTranslateJobRunner'),
                $meta['table'],
                $id
            ));
        }

        $payload = array();
        foreach ($translated as $field => $value) {
            $html = !empty($meta['fields'][$field]['html']) || strpos($value, '<') !== false;
            $payload[$field] = pSQL($value, $html);
        }

        $where = '`' . bqSQL($meta['id_column']) . '` = ' . $id
            . ' AND id_lang = ' . (int) $this->targetLangId
            . $shopSql;

        if ($target) {
            Db::getInstance()->update($meta['table'], $payload, $where);
        } else {
            $insert = array();
            foreach ($source as $col => $value) {
                if ($col === 'id_lang') {
                    $insert[$col] = (int) $this->targetLangId;
                    continue;
                }
                if (isset($payload[$col])) {
                    $insert[$col] = $payload[$col];
                    continue;
                }
                if (strpos($col, 'id_') === 0) {
                    $insert[$col] = (int) $value;
                    continue;
                }
                $html = isset($meta['fields'][$col]) && !empty($meta['fields'][$col]['html']);
                $insert[$col] = pSQL((string) $value, $html);
            }
            Db::getInstance()->insert($meta['table'], $insert);
        }

        $this->logHistory('ets_cfu', $meta['table'], $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Contact Form Ultimate %1$s #%2$d translated.', 'CkcTranslateJobRunner'),
            $meta['table'],
            $id
        ));
    }

    /**
     * Traduit le HTML/shortcode CFU (labels, options, submit) sans casser les tags [text* …].
     *
     * @param string $sourceHtml
     * @param int    $idContact
     *
     * @return string
     */
    protected function translateEtsCfuShortCodeHtml($sourceHtml, $idContact)
    {
        $sourceHtml = (string) $sourceHtml;
        $idContact = (int) $idContact;
        if (trim($sourceHtml) === '') {
            return '';
        }

        $shopId = (int) Context::getContext()->shop->id;
        $map = CkcTranslateCfuI18n::getMap($idContact, $this->targetLangId, $shopId);
        $strings = CkcTranslateCfuI18n::extractStrings($sourceHtml);
        $missing = array();
        foreach ($strings as $text) {
            if (!isset($map[$text]) || trim((string) $map[$text]) === '') {
                $missing[] = $text;
            }
        }

        if (!empty($missing)) {
            $chunks = array_chunk($missing, 12, false);
            foreach ($chunks as $chunk) {
                $example = array();
                foreach ($chunk as $text) {
                    $example[$text] = '';
                }
                $systemPrompt = $this->buildSystemPrompt(
                    'You translate visible Contact Form Ultimate field labels, dropdown options, placeholders and submit buttons. Keep required asterisks. Preserve HTML inside labels. Do not translate field names like menu-899 or CF7 tags. Return a JSON object whose keys are the exact source strings.',
                    'ets_cfu'
                );
                $prompt = "Return ONLY valid JSON mapping each source string to its translation:\n"
                    . json_encode($example, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    . "\n\nSource strings:\n"
                    . json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $data = $this->ai->requestJson($systemPrompt, $prompt);
                if (!is_array($data)) {
                    continue;
                }
                foreach ($chunk as $sourceText) {
                    if (!isset($data[$sourceText])) {
                        continue;
                    }
                    $value = trim((string) $data[$sourceText]);
                    if ($value === '') {
                        continue;
                    }
                    $map[$sourceText] = $value;
                }
            }
            if (!empty($map)) {
                CkcTranslateCfuI18n::saveMap($idContact, $this->targetLangId, $shopId, $map);
            }
        }

        if (empty($map)) {
            $systemPrompt = $this->buildSystemPrompt(
                'You translate a Contact Form Ultimate form template. Preserve all HTML tags, CF7 shortcodes like [text* text-652], field names, attributes, emails and URLs. Translate only visible labels, options and submit text. Return JSON {"short_code": ""}.',
                'ets_cfu'
            );
            $prompt = "Return ONLY valid JSON:\n{\"short_code\": \"\"}\n\nSource short_code:\n" . $sourceHtml;
            $data = $this->ai->requestJson($systemPrompt, $prompt);
            if (is_array($data) && !empty($data['short_code'])) {
                $translatedHtml = (string) $data['short_code'];
                if (trim(strip_tags($translatedHtml)) !== '') {
                    $builtMap = array();
                    $srcStrings = CkcTranslateCfuI18n::extractStrings($sourceHtml);
                    $tgtStrings = CkcTranslateCfuI18n::extractStrings($translatedHtml);
                    $limit = min(count($srcStrings), count($tgtStrings));
                    for ($i = 0; $i < $limit; $i++) {
                        $builtMap[$srcStrings[$i]] = $tgtStrings[$i];
                    }
                    if (!empty($builtMap)) {
                        CkcTranslateCfuI18n::saveMap($idContact, $this->targetLangId, $shopId, $builtMap);
                        CkcTranslateCfuI18n::updateRenderForm($idContact, $builtMap, $this->sourceLangId, $this->targetLangId);
                    }

                    return $translatedHtml;
                }
            }

            return '';
        }

        CkcTranslateCfuI18n::updateRenderForm($idContact, $map, $this->sourceLangId, $this->targetLangId);

        return CkcTranslateCfuI18n::applyMap($sourceHtml, $map);
    }

    /**
     * @param bool  $onlyMissing
     * @param bool  $useFormFilter
     * @param int[] $filterIds
     *
     * @return array<int, array{type: string, id: int}>
     */
    protected function buildEtsCfuFormQueueItems($onlyMissing, $useFormFilter, array $filterIds)
    {
        $items = array();
        $formMeta = CkcTranslateCfuI18n::getContactFormMeta();
        if (!$formMeta) {
            return $items;
        }

        CkcTranslateCfuI18n::ensureTable();
        $shopId = (int) Context::getContext()->shop->id;
        $idCol = bqSQL($formMeta['id_column']);
        $formCol = bqSQL($formMeta['form_column']);
        $table = bqSQL($formMeta['table']);

        $sql = 'SELECT DISTINCT c.`' . $idCol . '` AS id
                FROM `' . _DB_PREFIX_ . $table . '` c
                WHERE TRIM(IFNULL(c.`' . $formCol . '`, \'\')) != \'\'';
        if (!empty($formMeta['has_id_shop']) && $shopId > 0) {
            $sql .= ' AND c.id_shop IN (0, ' . (int) $shopId . ')';
        }
        if ($useFormFilter && !empty($filterIds)) {
            $sql .= ' AND c.`' . $idCol . '` IN (' . implode(',', array_map('intval', $filterIds)) . ')';
        }
        $sql .= ' ORDER BY c.`' . $idCol . '` ASC';

        try {
            $rows = Db::getInstance()->executeS($sql);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'CKC Translate ets_cfu form SQL error: ' . $e->getMessage(),
                3
            );

            return $items;
        }
        if (!is_array($rows)) {
            return $items;
        }
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if ($id <= 0) {
                continue;
            }
            $items[] = array(
                'type' => CkcTranslateCfuI18n::VIRTUAL_TYPE,
                'id' => $id,
            );
        }

        return $items;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    protected function translateEtsCfuFormBody($id)
    {
        $id = (int) $id;
        $formMeta = CkcTranslateCfuI18n::getContactFormMeta();
        $shopId = (int) Context::getContext()->shop->id;
        if (!$formMeta || $id <= 0) {
            $this->logHistory('ets_cfu', CkcTranslateCfuI18n::VIRTUAL_TYPE, $id, 'skipped', $this->module->l('Form body not found.', 'CkcTranslateJobRunner'));

            return $this->nextResult($this->module->l('Contact Form Ultimate form body skipped.', 'CkcTranslateJobRunner'));
        }

        $shopSql = '';
        if (!empty($formMeta['has_id_shop']) && $shopId > 0) {
            $shopSql = ' AND id_shop IN (0, ' . (int) $shopId . ')';
        }

        $form = (string) Db::getInstance()->getValue(
            'SELECT `' . bqSQL($formMeta['form_column']) . '`
             FROM `' . _DB_PREFIX_ . bqSQL($formMeta['table']) . '`
             WHERE `' . bqSQL($formMeta['id_column']) . '` = ' . $id . $shopSql
        );
        if ($form === '' && $shopSql !== '') {
            $form = (string) Db::getInstance()->getValue(
                'SELECT `' . bqSQL($formMeta['form_column']) . '`
                 FROM `' . _DB_PREFIX_ . bqSQL($formMeta['table']) . '`
                 WHERE `' . bqSQL($formMeta['id_column']) . '` = ' . $id
            );
        }
        if (trim($form) === '') {
            $this->logHistory('ets_cfu', CkcTranslateCfuI18n::VIRTUAL_TYPE, $id, 'skipped', $this->module->l('No form template.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Contact Form Ultimate form #%d has no template, skipped.', 'CkcTranslateJobRunner'),
                $id
            ));
        }

        $translatedMap = CkcTranslateCfuI18n::getMap($id, $this->targetLangId, $shopId);
        $onlyMissing = (int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS);
        $needsEtsWrite = CkcTranslateCfuI18n::formNeedsEtsLangWrite($form, $this->targetLangId);

        if ($onlyMissing && !empty($translatedMap) && !$needsEtsWrite) {
            $this->logHistory('ets_cfu', CkcTranslateCfuI18n::VIRTUAL_TYPE, $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Contact Form Ultimate form #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                $id
            ));
        }

        if (empty($translatedMap)) {
            $strings = CkcTranslateCfuI18n::extractStrings($form);
            if (empty($strings)) {
                $this->logHistory('ets_cfu', CkcTranslateCfuI18n::VIRTUAL_TYPE, $id, 'skipped', $this->module->l('No visible form labels.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Contact Form Ultimate form #%d has no labels to translate, skipped.', 'CkcTranslateJobRunner'),
                    $id
                ));
            }

            $chunks = array_chunk($strings, 12, false);
            foreach ($chunks as $chunk) {
                $example = array();
                foreach ($chunk as $text) {
                    $example[$text] = '';
                }
                $systemPrompt = $this->buildSystemPrompt(
                    'You translate visible Contact Form Ultimate field labels, dropdown options, placeholders and submit buttons. Keep required asterisks. Preserve HTML inside labels. Do not translate field names like menu-899, emails, URLs or file extensions. Return a JSON object whose keys are the exact source strings.',
                    'ets_cfu'
                );
                $prompt = "Return ONLY valid JSON mapping each source string to its translation:\n"
                    . json_encode($example, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    . "\n\nSource strings:\n"
                    . json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $data = $this->ai->requestJson($systemPrompt, $prompt);
                if (!is_array($data)) {
                    continue;
                }
                foreach ($chunk as $sourceText) {
                    if (!isset($data[$sourceText])) {
                        continue;
                    }
                    $value = trim((string) $data[$sourceText]);
                    if ($value === '') {
                        continue;
                    }
                    $translatedMap[$sourceText] = $value;
                }
            }
        }

        if (empty($translatedMap)) {
            $this->logHistory('ets_cfu', CkcTranslateCfuI18n::VIRTUAL_TYPE, $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for Contact Form Ultimate form #%d.', 'CkcTranslateJobRunner'),
                $id
            ));
        }

        CkcTranslateCfuI18n::saveMap($id, $this->targetLangId, $shopId, $translatedMap);

        $updatedForm = CkcTranslateCfuI18n::writeLangValuesIntoForm(
            $form,
            $translatedMap,
            $this->sourceLangId,
            $this->targetLangId
        );
        if ($updatedForm !== $form) {
            $where = '`' . bqSQL($formMeta['id_column']) . '` = ' . $id . $shopSql;
            Db::getInstance()->update(
                $formMeta['table'],
                array($formMeta['form_column'] => pSQL($updatedForm, true)),
                $where
            );
        }
        CkcTranslateCfuI18n::writeLangValuesIntoAdditionalSettings(
            $id,
            $this->sourceLangId,
            $this->targetLangId,
            $shopId,
            $translatedMap
        );

        $this->logHistory('ets_cfu', CkcTranslateCfuI18n::VIRTUAL_TYPE, $id, 'success', $this->module->l('Form labels translated.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Contact Form Ultimate form #%d labels written to ETS (label_%d).', 'CkcTranslateJobRunner'),
            $id,
            $this->targetLangId
        ));
    }

    /**
     * @param array  $tree
     * @param string $table
     * @param int    $id
     * @param string $field
     *
     * @return array|null
     */
    protected function translateEtsCfuJsonTree(array $tree, $table, $id, $field)
    {
        $strings = array();
        $this->collectEtsCfuJsonStrings($tree, '', $strings);
        if (empty($strings)) {
            return $tree;
        }

        $translated = array();
        $chunks = array_chunk($strings, 12, true);
        foreach ($chunks as $chunk) {
            $example = array();
            foreach ($chunk as $key => $value) {
                $example[$key] = '';
            }
            $systemPrompt = $this->buildSystemPrompt(
                'You translate Contact Form Ultimate JSON string values (labels, options, messages). Keep JSON keys unchanged in the response. Preserve HTML, emails and URLs. Do not invent legal clauses.',
                'ets_cfu'
            );
            $prompt = "Return ONLY valid JSON with these keys and translated values:\n"
                . json_encode($example, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . "\n\nSource values:\n"
                . json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $data = $this->ai->requestJson($systemPrompt, $prompt);
            if (!is_array($data)) {
                continue;
            }
            foreach ($chunk as $key => $sourceVal) {
                if (!isset($data[$key])) {
                    continue;
                }
                $value = (string) $data[$key];
                if (trim(strip_tags($value)) === '' && trim(strip_tags($sourceVal)) !== '') {
                    continue;
                }
                $translated[$key] = $value;
            }
        }

        if (empty($translated)) {
            PrestaShopLogger::addLog(
                'CKC Translate ets_cfu JSON empty for ' . $table . '#' . (int) $id . '.' . $field,
                2
            );

            return null;
        }

        return $this->injectEtsCfuJsonStrings($tree, '', $translated);
    }

    /**
     * @param mixed                $node
     * @param string               $path
     * @param array<string, string> $out
     */
    protected function collectEtsCfuJsonStrings($node, $path, array &$out)
    {
        if (is_string($node)) {
            if (trim(strip_tags($node)) === '') {
                return;
            }
            if (preg_match('/^[\d.\s]+$/', trim($node)) || preg_match('/^#[0-9a-fA-F]{3,8}$/', trim($node))) {
                return;
            }
            if (preg_match('/^(https?:\/\/|www\.)/i', $node) || (strpos($node, '@') !== false && strpos($node, ' ') === false)) {
                return;
            }
            $out[$path === '' ? 'root' : $path] = $node;

            return;
        }
        if (!is_array($node)) {
            return;
        }
        $skipKeys = array(
            'short_code', 'thank_you_url', 'name', 'id', 'class', 'type', 'accept',
            'filetypes', 'email', 'from', 'to', 'bcc', 'title_alias', 'thank_you_alias',
        );
        foreach ($node as $key => $value) {
            $keyStr = is_string($key) ? $key : (string) $key;
            if (in_array($keyStr, $skipKeys, true)) {
                continue;
            }
            $childPath = $path === '' ? $keyStr : $path . '.' . $keyStr;
            $this->collectEtsCfuJsonStrings($value, $childPath, $out);
        }
    }

    /**
     * @param mixed                $node
     * @param string               $path
     * @param array<string, string> $translated
     *
     * @return mixed
     */
    protected function injectEtsCfuJsonStrings($node, $path, array $translated)
    {
        if (is_string($node)) {
            $key = $path === '' ? 'root' : $path;
            if (isset($translated[$key])) {
                return $translated[$key];
            }

            return $node;
        }
        if (!is_array($node)) {
            return $node;
        }
        foreach ($node as $key => $value) {
            $keyStr = is_string($key) ? $key : (string) $key;
            $childPath = $path === '' ? $keyStr : $path . '.' . $keyStr;
            $node[$key] = $this->injectEtsCfuJsonStrings($value, $childPath, $translated);
        }

        return $node;
    }

    protected function runCarrierDelays()
    {
        $filtered = $this->getFilteredSql('carrier_delays');
        $shopId = (int) Context::getContext()->shop->id;

        $id = $this->getNextId(
            'ckctranslate_carrier_delays_ids',
            'ckctranslate_carrier_delays_index',
            $filtered['sql'],
            $filtered['column']
        );

        if (!$id) {
            return $this->finishedResult($this->module->l('Carrier delays translation finished.', 'CkcTranslateJobRunner'));
        }

        $sourceDelay = (string) Db::getInstance()->getValue(
            'SELECT delay FROM `' . _DB_PREFIX_ . 'carrier_lang`
             WHERE id_carrier = ' . (int) $id . '
               AND id_lang = ' . (int) $this->sourceLangId . '
               AND id_shop = ' . (int) $shopId
        );

        if (trim($sourceDelay) === '') {
            $this->logHistory('carrier_delays', 'carrier', (int) $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Carrier #%d has no source delay, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $targetDelay = (string) Db::getInstance()->getValue(
            'SELECT delay FROM `' . _DB_PREFIX_ . 'carrier_lang`
             WHERE id_carrier = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId . '
               AND id_shop = ' . (int) $shopId
        );

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            if ($targetDelay !== '' && $targetDelay !== $sourceDelay) {
                $this->logHistory('carrier_delays', 'carrier', (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Carrier #%d delay already translated, skipped.', 'CkcTranslateJobRunner'),
                    (int) $id
                ));
            }
        }

        $carrierName = (string) Db::getInstance()->getValue(
            'SELECT name FROM `' . _DB_PREFIX_ . 'carrier` WHERE id_carrier = ' . (int) $id
        );

        $systemPrompt = $this->buildSystemPrompt(
            'You translate PrestaShop carrier delivery delay texts shown at checkout and in shipping estimators. Keep them short and clear. Preserve numbers, time units and carrier brand names. Do not invent delivery promises.',
            'carrier_delays'
        );

        $prompt = <<<PROMPT
Return ONLY valid JSON:
{
  "delay": ""
}

Carrier name (do not translate, context only):
{$carrierName}

Source delay:
{$sourceDelay}
PROMPT;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, array('delay')) || trim((string) $data['delay']) === '') {
            $this->logHistory('carrier_delays', 'carrier', (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for carrier #%d delay.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $translatedDelay = trim((string) $data['delay']);
        if (Tools::strlen($translatedDelay) > 512) {
            $translatedDelay = Tools::substr($translatedDelay, 0, 512);
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_carrier FROM `' . _DB_PREFIX_ . 'carrier_lang`
             WHERE id_carrier = ' . (int) $id . '
               AND id_lang = ' . (int) $this->targetLangId . '
               AND id_shop = ' . (int) $shopId
        );

        if ($exists) {
            Db::getInstance()->update(
                'carrier_lang',
                array('delay' => pSQL($translatedDelay)),
                'id_carrier = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId . ' AND id_shop = ' . (int) $shopId
            );
        } else {
            Db::getInstance()->insert('carrier_lang', array(
                'id_carrier' => (int) $id,
                'id_shop' => (int) $shopId,
                'id_lang' => (int) $this->targetLangId,
                'delay' => pSQL($translatedDelay),
            ));
        }

        $this->logHistory('carrier_delays', 'carrier', (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Carrier #%d delay translated (%s).', 'CkcTranslateJobRunner'),
            (int) $id,
            $translatedDelay
        ));
    }

    /** Assure tables + sync cache avis pour le job CKC Translate. */
    protected function ensureBrandReviewsReady()
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

    protected function saveMetaForTarget($targetUidStr, $sourceUid, $elementorDataJson)
    {
        $allMeta = Db::getInstance()->executeS(
            'SELECT `name`, `value` FROM `' . _DB_PREFIX_ . 'ce_meta`
             WHERE `id` = ' . (int) $sourceUid
        );

        foreach ($allMeta as $meta) {
            $metaName = $meta['name'];
            // Skip compiled cache — CE will regenerate it for the new language
            if ($metaName === '_ce_element_cache') {
                continue;
            }
            $metaValue = ($metaName === '_elementor_data') ? $elementorDataJson : $meta['value'];

            $existsRows = Db::getInstance()->executeS(
                'SELECT id FROM `' . _DB_PREFIX_ . 'ce_meta`
                 WHERE `id` = ' . (int) $targetUidStr . ' AND `name` = \'' . pSQL($metaName) . '\'
                 LIMIT 1'
            );

            if (!empty($existsRows)) {
                Db::getInstance()->update(
                    'ce_meta',
                    array('value' => pSQL($metaValue, true)),
                    '`id` = ' . (int) $targetUidStr . ' AND `name` = \'' . pSQL($metaName) . '\''
                );
            } else {
                Db::getInstance()->insert('ce_meta', array(
                    'id' => (int) $targetUidStr,
                    'name' => pSQL($metaName),
                    'value' => pSQL($metaValue, true),
                ));
            }
        }
    }

    protected function getTranslatableSettingsKeys()
    {
        return array(
            'title', 'editor', 'description', 'text', 'heading', 'content',
            'caption', 'inner_text', 'suffix', 'prefix',
            'tab_title', 'tab_content',
            'testimonial_content', 'testimonial_name', 'testimonial_job',
            'button_text', 'html',
            'slide_heading', 'slide_description', 'slide_button_text',
            'alert_title', 'alert_description',
        );
    }

    protected function extractTranslatableTexts(array $elements, array &$texts, &$counter)
    {
        $translatableKeys = $this->getTranslatableSettingsKeys();

        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }

            $elType = isset($element['elType']) ? $element['elType'] : '';
            $elId = isset($element['id']) ? $element['id'] : ('el' . $counter++);

            if ($elType === 'widget' && !empty($element['settings'])) {
                $settings = $element['settings'];

                foreach ($translatableKeys as $key) {
                    if (isset($settings[$key]) && is_string($settings[$key])) {
                        if (trim(strip_tags($settings[$key])) !== '') {
                            $texts[$elId . '__' . $key] = $settings[$key];
                        }
                    }
                }

                // Handle repeater fields (accordion, tabs, slides, etc.)
                foreach ($settings as $settingKey => $settingValue) {
                    if (!is_array($settingValue)) {
                        continue;
                    }

                    foreach ($settingValue as $itemIdx => $item) {
                        if (!is_array($item)) {
                            continue;
                        }

                        foreach ($translatableKeys as $subKey) {
                            if (isset($item[$subKey]) && is_string($item[$subKey])) {
                                if (trim(strip_tags($item[$subKey])) !== '') {
                                    $texts[$elId . '__' . $settingKey . '_' . $itemIdx . '__' . $subKey] = $item[$subKey];
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($element['elements']) && is_array($element['elements'])) {
                $this->extractTranslatableTexts($element['elements'], $texts, $counter);
            }
        }
    }

    protected function injectTranslatedTexts(array $elements, array $translations)
    {
        $translatableKeys = $this->getTranslatableSettingsKeys();

        foreach ($elements as &$element) {
            if (!is_array($element)) {
                continue;
            }

            $elType = isset($element['elType']) ? $element['elType'] : '';
            $elId = isset($element['id']) ? $element['id'] : '';

            if ($elType === 'widget' && !empty($element['settings']) && $elId !== '') {
                foreach ($translatableKeys as $key) {
                    $mapKey = $elId . '__' . $key;
                    if (isset($translations[$mapKey])) {
                        $element['settings'][$key] = $translations[$mapKey];
                    }
                }

                foreach ($element['settings'] as $settingKey => &$settingValue) {
                    if (!is_array($settingValue)) {
                        continue;
                    }

                    foreach ($settingValue as $itemIdx => &$item) {
                        if (!is_array($item)) {
                            continue;
                        }

                        foreach ($translatableKeys as $subKey) {
                            $mapKey = $elId . '__' . $settingKey . '_' . $itemIdx . '__' . $subKey;
                            if (isset($translations[$mapKey])) {
                                $item[$subKey] = $translations[$mapKey];
                            }
                        }
                    }
                    unset($item);
                }
                unset($settingValue);
            }

            if (!empty($element['elements']) && is_array($element['elements'])) {
                $element['elements'] = $this->injectTranslatedTexts($element['elements'], $translations);
            }
        }
        unset($element);

        return $elements;
    }

    protected function parseIds($raw)
    {
        $ids = preg_split('/[^0-9]+/', (string) $raw);
        $ids = array_filter(array_map('intval', $ids));

        return array_values(array_unique($ids));
    }

    protected function normalizeSeoText($value, $maxLength)
    {
        $value = trim((string) $value);
        $maxLength = (int) $maxLength;
        // CKC Vianney — garde-fou : un plafond à 10 coupait titres / metas (ex. Rigid Fenc).
        if ($maxLength > 0 && $maxLength < 30) {
            $maxLength = 60;
        }

        if ($maxLength > 0 && Tools::strlen($value) > $maxLength) {
            $value = Tools::substr($value, 0, $maxLength);
            if (preg_match('/^(.*)\s+\S*$/u', $value, $matches) && trim($matches[1]) !== '') {
                $value = trim($matches[1]);
            }
            $value = rtrim($value, " \t\n\r\0\x0B-,:;");
        }

        return $value;
    }

    /**
     * CKC Vianney — SEO pages système : coupe au mot, jamais de "&" / connecteur orphelin.
     */
    protected function normalizeSystemPageSeoText($value, $maxLength)
    {
        $value = html_entity_decode(trim(strip_tags((string) $value)), ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = trim((string) $value);

        if ($maxLength > 0 && Tools::strlen($value) > $maxLength) {
            $value = Tools::substr($value, 0, $maxLength);
            if (preg_match('/^(.*)\s+\S*$/u', $value, $matches) && trim($matches[1]) !== '') {
                $value = trim($matches[1]);
            }
            $value = rtrim($value, " \t\n\r\0\x0B-,:;.&");
        }

        return $value;
    }

    /**
     * Détecte une meta coupée / trop courte par rapport à la source.
     *
     * @param string $target
     * @param string $source
     * @param int    $minAbsolute
     *
     * @return bool true = incomplet (à retraduire)
     */
    protected function isIncompleteSystemPageSeo($target, $source, $minAbsolute = 12)
    {
        $target = trim((string) $target);
        $source = trim((string) $source);

        if ($source === '') {
            return false;
        }

        if ($target === '' || $target === $source) {
            return true;
        }

        $sourceLen = Tools::strlen($source);
        $targetLen = Tools::strlen($target);
        $minLen = max((int) $minAbsolute, (int) floor($sourceLen * 0.45));

        if ($targetLen < $minLen) {
            return true;
        }

        if (preg_match('/(?:\s|&|&amp;|\band\b|\bof\b|\bthe\b|\bfor\b|\bwith\b|\bto\b|,|-)$/iu', $target)) {
            return true;
        }

        return false;
    }

    protected function normalizeSlug($value)
    {
        $slug = Tools::link_rewrite((string) $value);
        $maxLength = (int) Configuration::get(CkcTranslate::CONF_SEO_SLUG_MAX);
        // CKC Vianney — garde-fou : une config à 10 coupait tous les slugs (ex. rigid-fenc).
        if ($maxLength < 32) {
            $maxLength = 80;
        }

        if (Tools::strlen($slug) > $maxLength) {
            $slug = Tools::substr($slug, 0, $maxLength);
            $cut = strrpos($slug, '-');
            if ($cut !== false && $cut >= (int) floor($maxLength * 0.6)) {
                $slug = Tools::substr($slug, 0, $cut);
            }
            $slug = trim($slug, '-');
        }

        return $slug;
    }

    protected function shouldSkipFilledFields($targetObject, array $fields, $sourceObject = null)
    {
        if (!(int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            return false;
        }

        foreach ($fields as $field) {
            $targetValue = isset($targetObject->{$field})
                ? trim(strip_tags((string) $targetObject->{$field}))
                : '';

            if ($targetValue === '') {
                return false;
            }

            if ($sourceObject !== null) {
                $sourceValue = isset($sourceObject->{$field})
                    ? trim(strip_tags((string) $sourceObject->{$field}))
                    : '';

                // New entities often copy source language into target: treat as untranslated.
                if ($sourceValue !== '' && $targetValue === $sourceValue) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function logHistory($jobCode, $entityType, $entityId, $status, $message)
    {
        $estimatedCost = (float) Configuration::get(CkcTranslate::CONF_ESTIMATED_CREDIT);

        Db::getInstance()->insert(
            'ckctranslate_history',
            array(
                'job_code' => pSQL($jobCode),
                'entity_type' => pSQL($entityType),
                'entity_id' => (int) $entityId,
                'status' => pSQL($status),
                'model' => pSQL((string) Configuration::get(CkcTranslate::CONF_MODEL)),
                'estimated_cost' => (float) $estimatedCost,
                'message' => pSQL($message, true),
                'date_add' => date('Y-m-d H:i:s'),
            )
        );
    }

    protected function getNextId($cookieKey, $indexKey, $sql, $column)
    {
        if (empty($sql) || empty($column)) {
            return 0;
        }

        $queueName = self::normalizeQueueName($cookieKey);
        $paths = self::getQueueFilePaths($queueName);

        if (!file_exists($paths['items'])) {
            $rows = Db::getInstance()->executeS($sql);
            $ids = array();

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (isset($row[$column])) {
                        $ids[] = (int) $row[$column];
                    }
                }
            }

            @file_put_contents($paths['items'], json_encode($ids));
            @file_put_contents($paths['index'], '0');
        }

        $ids = json_decode((string) @file_get_contents($paths['items']), true);
        $index = (int) @file_get_contents($paths['index']);

        if (!is_array($ids) || !isset($ids[$index])) {
            self::resetFileQueue($queueName);
            Tools::clearAllCache();

            return 0;
        }

        $currentId = (int) $ids[$index];
        @file_put_contents($paths['index'], (string) ($index + 1));

        return $currentId;
    }

    protected function isValidFields($data, array $fields)
    {
        if (!is_array($data)) {
            return false;
        }

        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                return false;
            }

            if (trim(strip_tags((string) $data[$field])) === '') {
                return false;
            }
        }

        return true;
    }

    protected function nextResult($message)
    {
        return array(
            'success' => true,
            'message' => $message,
            'finished' => false,
        );
    }

    protected function finishedResult($message)
    {
        return array(
            'success' => true,
            'message' => $message,
            'finished' => true,
        );
    }
}