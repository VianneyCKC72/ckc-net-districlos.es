<?php

class CategoryController extends CategoryControllerCore
{
    /**
     * Initializes controller.
     *
     * @throws PrestaShopException
     * @see FrontController::init()
     */
    public function init()
    {
        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        if (Module::isEnabled('ckcadcampaignconfigurator')) {
            $campaignModule = Module::getInstanceByName('ckcadcampaignconfigurator');
            if ($campaignModule && !$campaignModule->isRegisteredInHook('actionPresentProductListing')) {
                $campaignModule->registerHook('actionPresentProductListing');
            }
        }

        if (isset($this->category->id)) {
            $links = Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'ckc_category_additional_fields`
                WHERE `id_category` = ' . (int) $this->category->id . '
                AND `category_field_content` IS NOT NULL'
            );

            if (isset($links[0]['id'])) {
                $subcategoryLinksContent = json_decode(base64_decode($links[0]['category_field_content']));
                $subcategoryLinksContent = ltrim((string) $subcategoryLinksContent, ',');

                $arrCategory = explode(',', $subcategoryLinksContent);
                $subcategoryLinks = [];

                foreach ($arrCategory as $k => $category) {
                    if ($category === '' || $category === null) {
                        continue;
                    }

                    $categ = json_decode($category, true);
                    if (!is_array($categ) || !$categ) {
                        continue;
                    }

                    $title = (string) array_keys($categ)[0];
                    $url = (string) array_values($categ)[0];
                    $class = '';

                    $resolved = $this->resolveSubcategoryLink($title, $url);
                    $title = $resolved['title'];
                    $url = $resolved['url'];

                    if ($url === '/configurateur' || preg_match('#/(en/)?configurateur/?$#', $url)) {
                        $class = 'bck-blue';
                    }

                    $subcategoryLinks[$k] = [
                        'title' => $title,
                        'class' => $class,
                        'url' => $url,
                    ];
                }

                $this->context->smarty->assign([
                    'subcategoryLinks' => $subcategoryLinks,
                ]);
            }
        }

        parent::initContent();
    }

    /**
     * Rebuild title/url from category ID when the stored link points to a category,
     * so FO language (EN/FR) is respected.
     *
     * @param string $title
     * @param string $url
     *
     * @return array{title: string, url: string}
     */
    protected function resolveSubcategoryLink($title, $url)
    {
        $idCategory = $this->extractCategoryIdFromUrl($url);
        if (!$idCategory) {
            return [
                'title' => $title,
                'url' => $url,
            ];
        }

        $idLang = (int) $this->context->language->id;
        $category = new Category($idCategory, $idLang);
        if (!Validate::isLoadedObject($category) || !(bool) $category->active) {
            return [
                'title' => $title,
                'url' => $url,
            ];
        }

        return [
            'title' => (string) $category->name,
            'url' => $this->context->link->getCategoryLink($category),
        ];
    }

    /**
     * Extract category id from relative/absolute rewritten URLs like /94-xxx or /en/94-xxx.
     *
     * @param string $url
     *
     * @return int
     */
    protected function extractCategoryIdFromUrl($url)
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '' || $path === null) {
            $path = $url;
        }

        if (preg_match('#(?:^|/)(\d+)-[^/]+/?$#', $path, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('#(?:^|/)category/(\d+)(?:/|$)#', $path, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
