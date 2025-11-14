<?php

require_once _PS_MODULE_DIR_ . 'ckc_espacepro/classes/SectionGuidesFichesServices.php';

class AdminGuidesFichesController extends ModuleAdminController
{
    protected $sectionService;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $this->sectionService = new SectionGuidesFichesServices();
        $this->templatePath = _PS_MODULE_DIR_ . 'ckc_espacepro/views/templates/admin/';
    }

    public function initContent()
    {
        parent::initContent();

        $sections = CkcSectionGuidesFiches::getSections();
        $categories = CkcSectionGuidesFiches::getCategories();

        $this->context->smarty->assign([
            'categories' => $categories,
            'sections' => $sections,
            'url' => $this->context->link->getAdminLink('AdminGuidesFichesController')
        ]);

        $this->setTemplate('admin-fichesguides.tpl');
    }

    public function postProcess()
    {
        parent::postProcess();

        $result = null;

        if (Tools::isSubmit('submit_add_section')) {
            $result = $this->sectionService->addSection(
                Tools::getValue('id_category'),
                Tools::getValue('title_section')
            );
        }

        if (Tools::isSubmit('update_section')) {
            $result = $this->sectionService->updateTitle(
                Tools::getValue('id_section'),
                Tools::getValue('title_section')
            );
        }

        if (Tools::isSubmit('delete_section')) {
            $result = $this->sectionService->deleteSection(
                Tools::getValue('id_section')
            );
        }

        if (Tools::isSubmit('bulk_delete_sections') && Tools::getValue('bulk_ids')) {
            $result = $this->sectionService->bulkDeleteSections(
                array_map('intval', Tools::getValue('bulk_ids'))
            );
        }

        if (!empty($result['error'])) {
            $this->errors[] = $this->l($result['error']);
        }

        if (!empty($result['success'])) {
            $this->confirmations[] = $this->l($result['success']);
        }

        if ($result !== null) {
                Tools::redirectAdmin(self::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminGuidesFiches'));
            }
    }

    public function ajaxProcessToggleActive()
    {
        header('Content-Type: application/json');
        $result = $this->sectionService->toggleActive((int) Tools::getValue('id_section'));
        die(json_encode($result));
    }

    public function ajaxProcessUpdateOrdering()
    {
        header('Content-Type: application/json');
        $orderedIds = Tools::getValue('ordered_ids');
        $result = $this->sectionService->updateOrdering(is_array($orderedIds) ? $orderedIds : []);
        die(json_encode($result));
    }


}

