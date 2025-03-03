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

        $links = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ckc_category_additional_fields` WHERE id_category = '.$this->category->id);

        if(isset($links[0]["id"])){
            $subcategoryLinksContent = json_decode(base64_decode($links[0]["category_field_content"]));
            $arrCategory = explode(",", $subcategoryLinksContent);
            $subcategoryLinks = [];
            foreach ($arrCategory as $category){
                $subcategoryLinks[] = json_decode($category, true);
            }

            $this->context->smarty->assign([
                    'subcategoryLinks' => $subcategoryLinks,
            ]);
        }

        parent::initContent();
    }


}
