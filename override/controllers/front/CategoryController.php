<?php

class CategoryController extends CategoryControllerCore
{
    /**
     * Initializes controller.
     *
     * @throws PrestaShopException
     * @see FrontController::init()
     *
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
        if(isset($this->category->id)) {
            $links = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "ckc_category_additional_fields WHERE id_category = " . $this->category->id . " AND category_field_content IS NOT NULL");

            if (isset($links[0]["id"])) {
                $subcategoryLinksContent = json_decode(base64_decode($links[0]["category_field_content"]));
                $subcategoryLinksContent = ltrim($subcategoryLinksContent, ",");

                $class = '';
                $arrCategory = explode(",", $subcategoryLinksContent);
                $subcategoryLinks = [];
                array_filter($arrCategory);

                foreach ($arrCategory as $k => $category) {
                    $categ = json_decode($category, true);

                    $url = array_values($categ)[0];
                    if ($url == '/configurateur') {
                        $class = "bck-blue";
                    }

                    $subcategoryLinks[$k]['title'] = array_keys($categ)[0];
                    $subcategoryLinks[$k]['class'] = $class;
                    $subcategoryLinks[$k]['url'] = $url;
                }

                $this->context->smarty->assign([
                    'subcategoryLinks' => $subcategoryLinks,
                ]);
            }
        }

        parent::initContent();
    }


}
