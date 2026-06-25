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

                default:
                    $entity = CkcTranslate::getModuleLangEntity($job);
                    if ($entity !== null) {
                        $result = $this->runModuleLang($entity);
                        break;
                    }

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

    protected function runModuleLang(array $entity)
    {
        $tableName = $entity['table'];
        $langTableName = $tableName . '_lang';
        $idCol = $entity['id'];
        $fields = array_values($entity['fields']);
        $cookieKey = 'ckctranslate_' . $entity['code'] . '_ids';
        $indexKey = 'ckctranslate_' . $entity['code'] . '_index';

        $listSql = 'SELECT DISTINCT `' . bqSQL($idCol) . '`
                    FROM `' . _DB_PREFIX_ . bqSQL($langTableName) . '`
                    WHERE id_lang = ' . (int) $this->sourceLangId . '
                    ORDER BY `' . bqSQL($idCol) . '` ASC';

        $id = $this->getNextId($cookieKey, $indexKey, $listSql, $entity['id']);

        if (!$id) {
            return $this->finishedResult(sprintf(
                $this->module->l('Module table "%s" translation finished.', 'CkcTranslateJobRunner'),
                $tableName
            ));
        }

        $cols = array();
        foreach ($fields as $f) {
            $cols[] = '`' . bqSQL($f) . '`';
        }
        $colsSql = implode(', ', $cols);

        $source = Db::getInstance()->getRow(
            'SELECT ' . $colsSql . '
             FROM `' . _DB_PREFIX_ . bqSQL($langTableName) . '`
             WHERE `' . bqSQL($idCol) . '` = ' . (int) $id . ' AND id_lang = ' . (int) $this->sourceLangId
        );

        if (!is_array($source)) {
            $this->logHistory($entity['code'], $tableName, (int) $id, 'skipped', $this->module->l('Source row missing.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Source row #%d missing, skipped.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        if ((int) Configuration::get(CkcTranslate::CONF_ONLY_MISSING_FIELDS)) {
            $target = Db::getInstance()->getRow(
                'SELECT ' . $colsSql . '
                 FROM `' . _DB_PREFIX_ . bqSQL($langTableName) . '`
                 WHERE `' . bqSQL($idCol) . '` = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
            );

            if (is_array($target)) {
                $allFilled = true;
                foreach ($fields as $f) {
                    if (!isset($target[$f]) || trim(strip_tags((string) $target[$f])) === '') {
                        $allFilled = false;
                        break;
                    }
                }

                if ($allFilled) {
                    $this->logHistory($entity['code'], $tableName, (int) $id, 'skipped', $this->module->l('Already translated.', 'CkcTranslateJobRunner'));

                    return $this->nextResult(sprintf(
                        $this->module->l('Row #%d already translated, skipped.', 'CkcTranslateJobRunner'),
                        (int) $id
                    ));
                }
            }
        }

        $jsonSkeleton = array();
        $sourceBlock = '';
        foreach ($fields as $f) {
            $jsonSkeleton[] = '  "' . $f . '": ""';
            $sourceBlock .= "\nSource " . $f . ":\n" . (isset($source[$f]) ? (string) $source[$f] : '') . "\n";
        }

        $systemPrompt = $this->buildSystemPrompt(
            'You are an expert e-commerce translator. Translate the module content fields naturally. Preserve any HTML tags exactly as in the source.',
            ''
        );

        $prompt = "Return ONLY valid JSON:\n{\n" . implode(",\n", $jsonSkeleton) . "\n}\n" . $sourceBlock;

        $data = $this->ai->requestJson($systemPrompt, $prompt);

        if (!$this->isValidFields($data, $fields)) {
            $this->logHistory($entity['code'], $tableName, (int) $id, 'error', $this->module->l('Invalid AI response.', 'CkcTranslateJobRunner'));

            return $this->nextResult(sprintf(
                $this->module->l('Invalid AI response for row #%d.', 'CkcTranslateJobRunner'),
                (int) $id
            ));
        }

        $update = array();
        foreach ($fields as $f) {
            $update[$f] = pSQL((string) $data[$f], true);
        }

        Db::getInstance()->update(
            $langTableName,
            $update,
            '`' . bqSQL($idCol) . '` = ' . (int) $id . ' AND id_lang = ' . (int) $this->targetLangId
        );

        $this->logHistory($entity['code'], $tableName, (int) $id, 'success', $this->module->l('Translated successfully.', 'CkcTranslateJobRunner'));

        return $this->nextResult(sprintf(
            $this->module->l('Row #%d translated.', 'CkcTranslateJobRunner'),
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

            default:
                return array(
                    'sql' => '',
                    'column' => '',
                );
        }
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