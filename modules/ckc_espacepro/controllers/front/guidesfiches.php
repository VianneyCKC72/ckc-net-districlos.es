<?php

require_once _PS_MODULE_DIR_ . 'ckc_espacepro/classes/front/ProductsService.php';

class ckc_espaceproguidesfichesModuleFrontController extends ModuleFrontController
{
   public function initContent()
   {
        parent::initContent();

        $service = new ProductsService();

        $documentSlug = $service->resolveCategoryFromRequest();

        // $sections = $service->getSections([
        //     ['title' => 'Clôture',  'keywords' => ProductsService::KEYWORDS_CLOTURES],
        //     ['title' => 'Portail',  'keywords' => ProductsService::KEYWORDS_PORTAILS],
        //     ['title' => 'Grillage', 'keywords' => ProductsService::KEYWORDS_GRILLAGES],
        // ], $documentSlug);

        $sections = $service->getSections(['Clôture', 'Portail', 'Grillage'], $documentSlug);


        $btnText = $service->getTitleBtn();


        $this->context->smarty->assign(compact('sections', 'btnText'));

        $this->setTemplate('module:ckc_espacepro/views/templates/front/guides-fiches/guides-fiches.tpl');

   }
}