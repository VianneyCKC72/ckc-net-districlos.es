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
                return (string) Configuration::get('CKCTRANSLATE_PROMPT_REASSURANCE');

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