<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

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

        if ($this->shouldSkipFilledFields($targetProduct, array('name', 'meta_title', 'meta_description', 'meta_keywords', 'link_rewrite'))) {
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

        if ($this->shouldSkipFilledFields($targetProduct, array('description_short', 'description'))) {
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

        if ($this->shouldSkipFilledFields($targetCategory, array('name', 'meta_title', 'meta_description', 'link_rewrite'))) {
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

        if ($this->shouldSkipFilledFields($targetCategory, array('description', 'additional_description'))) {
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

        if ($this->shouldSkipFilledFields($targetCms, array('meta_title', 'meta_description', 'meta_keywords', 'content', 'link_rewrite'))) {
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

        $jobPrompt = $this->getJobSpecificPrompt($jobCode);

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

            default:
                return '';
        }
    }

    protected function getFilteredSql($jobCode)
    {
        $filterType = (string) Configuration::get(CkcTranslate::CONF_FILTER_TYPE);
        $filterIds = $this->parseIds((string) Configuration::get(CkcTranslate::CONF_FILTER_IDS));
        $filterCategoryId = (int) Configuration::get(CkcTranslate::CONF_FILTER_CATEGORY_ID);

        switch ($jobCode) {
            case 'product_seo':
            case 'product_description':
                $sql = 'SELECT p.id_product
                        FROM ' . _DB_PREFIX_ . 'product p';

                if ($filterType === 'category' && $filterCategoryId > 0) {
                    $sql .= ' INNER JOIN ' . _DB_PREFIX_ . 'category_product cp ON cp.id_product = p.id_product';
                }

                $sql .= ' WHERE p.active = 1';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND p.id_product IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                if ($filterType === 'category' && $filterCategoryId > 0) {
                    $sql .= ' AND cp.id_category = ' . (int) $filterCategoryId;
                }

                $sql .= ' ORDER BY p.id_product ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_product',
                );

            case 'category_seo':
            case 'category_description':
                $sql = 'SELECT c.id_category
                        FROM ' . _DB_PREFIX_ . 'category c
                        WHERE c.active = 1
                        AND c.id_parent != 0';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND c.id_category IN (' . implode(',', array_map('intval', $filterIds)) . ')';
                }

                $sql .= ' ORDER BY c.id_category ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_category',
                );

            case 'cms':
                $sql = 'SELECT c.id_cms
                        FROM ' . _DB_PREFIX_ . 'cms c
                        WHERE c.active = 1';

                if ($filterType === 'ids' && !empty($filterIds)) {
                    $sql .= ' AND c.id_cms IN (' . implode(',', array_map('intval', $filterIds)) . ')';
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
                        AND cl.content != \'\'
                        ORDER BY cl.id_ce_theme ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_ce_theme',
                );

            case 'ce_meta':
                $langPad = sprintf('%02d', $this->sourceLangId);
                $sql = 'SELECT id AS source_uid
                        FROM `' . _DB_PREFIX_ . 'ce_meta`
                        WHERE `name` = \'_elementor_data\'
                        AND SUBSTRING(CAST(id AS CHAR), LENGTH(CAST(id AS CHAR))-3, 2) = \'' . pSQL($langPad) . '\'
                        ORDER BY id ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'source_uid',
                );

            case 'reassurance':
                $sql = 'SELECT pr.id_psreassurance
                        FROM `' . _DB_PREFIX_ . 'psreassurance` pr
                        WHERE pr.status = 1
                        ORDER BY pr.position ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_psreassurance',
                );

            case 'brand_reviews':
                $this->ensureBrandReviewsReady();
                $sql = 'SELECT r.id_ckc_brand_review
                        FROM `' . _DB_PREFIX_ . 'ckc_brand_review` r
                        ORDER BY r.publish_date DESC, r.id_ckc_brand_review DESC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_ckc_brand_review',
                );

            case 'order_states':
                $sql = 'SELECT os.id_order_state
                        FROM `' . _DB_PREFIX_ . 'order_state` os
                        WHERE os.deleted = 0
                        ORDER BY os.id_order_state ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_order_state',
                );

            case 'carrier_delays':
                $shopId = (int) Context::getContext()->shop->id;
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

                $sql .= ' ORDER BY c.id_carrier ASC';

                return array(
                    'sql' => $sql,
                    'column' => 'id_carrier',
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

            if (!empty($targetContent)) {
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
        if (!$this->everContentSeoTablesExist()) {
            return array(
                'success' => false,
                'message' => $this->module->l('Ever Content SEO tables not found. Is the module everpscontentseo installed?', 'CkcTranslateJobRunner'),
                'finished' => true,
            );
        }

        $item = $this->getNextEverContentSeoItem();
        if (!$item) {
            return $this->finishedResult($this->module->l('Ever Content SEO translation finished.', 'CkcTranslateJobRunner'));
        }

        $type = $item['type'];
        $id = (int) $item['id'];
        $meta = $this->getEverContentSeoTypeMeta($type);
        if (!$meta) {
            $this->logHistory('evercontentseo', $type, $id, 'skipped', 'Unknown entity type');

            return $this->nextResult(sprintf(
                $this->module->l('Ever Content SEO item skipped (unknown type: %s).', 'CkcTranslateJobRunner'),
                $type
            ));
        }

        $sourceContent = (string) Db::getInstance()->getValue(
            'SELECT content FROM `' . _DB_PREFIX_ . $meta['lang_table'] . '`
             WHERE `' . $meta['id_column'] . '` = ' . $id . '
             AND id_lang = ' . (int) $this->sourceLangId
        );

        if (trim(strip_tags($sourceContent)) === '') {
            $this->logHistory('evercontentseo', $type, $id, 'skipped', $this->module->l('No source content.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Ever Content SEO %1$s #%2$d has no source content, skipped.', 'CkcTranslateJobRunner'),
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

            if (trim(strip_tags($targetContent)) !== '') {
                $this->logHistory('evercontentseo', $type, $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                return $this->nextResult(sprintf(
                    $this->module->l('Ever Content SEO %1$s #%2$d already translated, skipped.', 'CkcTranslateJobRunner'),
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
            $this->logHistory('evercontentseo', $type, $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for Ever Content SEO %1$s #%2$d.', 'CkcTranslateJobRunner'),
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

        $this->logHistory('evercontentseo', $type, $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Ever Content SEO %1$s #%2$d translated.', 'CkcTranslateJobRunner'),
            $type,
            $id
        ));
    }

    protected function everContentSeoTablesExist()
    {
        $db = Db::getInstance();

        return (bool) $db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'everpscontentseo_categories"')
            && (bool) $db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'everpscontentseo_categories_lang"');
    }

    protected function getEverContentSeoTypeMeta($type)
    {
        $map = array(
            'category' => array(
                'table' => 'everpscontentseo_categories',
                'lang_table' => 'everpscontentseo_categories_lang',
                'id_column' => 'id_everpscontentseo_category',
                'ref_column' => 'id_category',
            ),
            'manufacturer' => array(
                'table' => 'everpscontentseo_manufacturers',
                'lang_table' => 'everpscontentseo_manufacturers_lang',
                'id_column' => 'id_everpscontentseo_manufacturer',
                'ref_column' => 'id_manufacturer',
            ),
            'supplier' => array(
                'table' => 'everpscontentseo_suppliers',
                'lang_table' => 'everpscontentseo_suppliers_lang',
                'id_column' => 'id_everpscontentseo_supplier',
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
        foreach ($types as $type) {
            $meta = $this->getEverContentSeoTypeMeta($type);
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

            $sql .= ' ORDER BY c.`' . $meta['id_column'] . '` ASC';

            $rows = Db::getInstance()->executeS($sql);
            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $items[] = array(
                    'type' => $type,
                    'id' => (int) $row['id'],
                );
            }
        }

        return $items;
    }

    protected function getNextEverContentSeoItem()
    {
        $cookie = Context::getContext()->cookie;
        $cookieKey = 'ckctranslate_evercontentseo_ids';
        $indexKey = 'ckctranslate_evercontentseo_index';

        if (!isset($cookie->{$cookieKey})) {
            $cookie->{$cookieKey} = json_encode($this->buildEverContentSeoQueue());
            $cookie->{$indexKey} = 0;
        }

        $items = json_decode($cookie->{$cookieKey}, true);
        $index = (int) $cookie->{$indexKey};

        if (!is_array($items) || !isset($items[$index])) {
            unset($cookie->{$cookieKey});
            unset($cookie->{$indexKey});
            Tools::clearAllCache();

            return null;
        }

        $item = $items[$index];
        $cookie->{$indexKey} = $index + 1;

        if (!is_array($item) || empty($item['type']) || empty($item['id'])) {
            return $this->getNextEverContentSeoItem();
        }

        return array(
            'type' => (string) $item['type'],
            'id' => (int) $item['id'],
        );
    }

    protected function runFeatures()
    {
        $item = $this->getNextTypedQueueItem(
            'ckctranslate_features_ids',
            'ckctranslate_features_index',
            array($this, 'buildFeaturesQueue')
        );

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
        $item = $this->getNextTypedQueueItem(
            'ckctranslate_attributes_ids',
            'ckctranslate_attributes_index',
            array($this, 'buildAttributesQueue')
        );

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

        $attrSql .= ' ORDER BY a.id_attribute ASC';

        $attrRows = Db::getInstance()->executeS($attrSql);
        if (is_array($attrRows)) {
            foreach ($attrRows as $row) {
                $items[] = array('type' => 'attribute', 'id' => (int) $row['id_attribute']);
            }
        }

        return $items;
    }

    protected function getNextTypedQueueItem($cookieKey, $indexKey, $builder)
    {
        $cookie = Context::getContext()->cookie;

        if (!isset($cookie->{$cookieKey})) {
            $items = is_callable($builder) ? call_user_func($builder) : array();
            $cookie->{$cookieKey} = json_encode(is_array($items) ? $items : array());
            $cookie->{$indexKey} = 0;
        }

        $items = json_decode($cookie->{$cookieKey}, true);
        $index = (int) $cookie->{$indexKey};

        if (!is_array($items) || !isset($items[$index])) {
            unset($cookie->{$cookieKey});
            unset($cookie->{$indexKey});
            Tools::clearAllCache();

            return null;
        }

        $item = $items[$index];
        $cookie->{$indexKey} = $index + 1;

        if (!is_array($item) || empty($item['type']) || empty($item['id'])) {
            return $this->getNextTypedQueueItem($cookieKey, $indexKey, $builder);
        }

        return array(
            'type' => (string) $item['type'],
            'id' => (int) $item['id'],
        );
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

        if ($maxLength > 0 && Tools::strlen($value) > $maxLength) {
            $value = Tools::substr($value, 0, $maxLength);
            $value = rtrim($value, " \t\n\r\0\x0B-,:;");
        }

        return $value;
    }

    protected function normalizeSlug($value)
    {
        $slug = Tools::link_rewrite((string) $value);
        $maxLength = (int) Configuration::get(CkcTranslate::CONF_SEO_SLUG_MAX);

        if ($maxLength > 0 && Tools::strlen($slug) > $maxLength) {
            $slug = Tools::substr($slug, 0, $maxLength);
            $slug = trim($slug, '-');
        }

        return $slug;
    }

    protected function shouldSkipFilledFields($targetObject, array $fields)
    {
        if (!(int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            return false;
        }

        foreach ($fields as $field) {
            if (!isset($targetObject->{$field}) || trim(strip_tags((string) $targetObject->{$field})) === '') {
                return false;
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
        $cookie = Context::getContext()->cookie;

        if (empty($sql) || empty($column)) {
            return 0;
        }

        if (!isset($cookie->{$cookieKey})) {
            $rows = Db::getInstance()->executeS($sql);
            $ids = array();

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (isset($row[$column])) {
                        $ids[] = (int) $row[$column];
                    }
                }
            }

            $cookie->{$cookieKey} = json_encode($ids);
            $cookie->{$indexKey} = 0;
        }

        $ids = json_decode($cookie->{$cookieKey}, true);
        $index = (int) $cookie->{$indexKey};

        if (!is_array($ids) || !isset($ids[$index])) {
            unset($cookie->{$cookieKey});
            unset($cookie->{$indexKey});
            Tools::clearAllCache();

            return 0;
        }

        $currentId = (int) $ids[$index];
        $cookie->{$indexKey} = $index + 1;

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