<?php

class Ckc_RealisationMyrealisationModuleFrontController extends ModuleFrontController
{
    public $auth = true;              // exige d’être connecté
    public $guestAllowed = false;
    public $ssl = true;
    public $display_header = true;
    public $display_footer = true;
    public $content_only = false;

    public function init()
    {
        parent::init();

        $this->registerStylesheet(
            'module-ckc-real-front',
            'modules/'.$this->module->name.'/views/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );
    }

    public function initContent()
    {
        parent::initContent();

        // (optionnel) forcer le layout “Mon compte” plein largeur du thème
        // Si votre thème utilise un autre layout, mettez celui qu'il attend.
        $this->context->smarty->assign('layout', 'layouts/layout-full-width.tpl');

        $shopId = (int) $this->context->shop->id;

        // Récupérer les réalisations du client
        $rows = Db::getInstance()->executeS('
            SELECT id_realisation, filename, status, date_add
            FROM '._DB_PREFIX_.'ckc_realisation
            WHERE id_shop='.(int)$shopId.' AND id_customer='.(int)$this->context->customer->id.'
            ORDER BY date_add DESC
        ') ?: [];

        $fileBaseUrl = Tools::getShopDomainSsl(true).__PS_BASE_URI__
            .'modules/'.$this->module->name.'/uploads/'.$shopId.'/';

        $this->context->smarty->assign([
            'module_name'   => $this->module->name,
            'module_dir'    => $this->module->getPathUri(),
            'file_base_url' => $fileBaseUrl,
            'token'         => Tools::getToken(false),
            'upload_link'   => $this->context->link->getModuleLink($this->module->name, 'myrealisation', [], true),
            'delete_link'   => $this->context->link->getModuleLink($this->module->name, 'myrealisation', [], true),
            'rows'          => $rows,
            'add_link' => $this->context->link->getModuleLink($this->module->name, 'myrealisationadd', [], true),
            'del_link' => $this->context->link->getModuleLink($this->module->name, 'myrealisationdelete', [], true),
        ]);

        // IMPORTANT : le chemin “module:…”
        $this->setTemplate('module:ckc_realisation/views/templates/front/myrealisation.tpl');
    }


}
