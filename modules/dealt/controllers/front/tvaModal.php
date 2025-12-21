<?php

class DealtTvaModalModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'tva_attestation_url' => $this->context->link->getModuleLink('dealt', 'tvaattestation'),
            'tva_token' => md5(uniqid()), // pour les non connectés si besoin
        ]);

        $this->setTemplate('module:dealt/views/templates/front/tva_modal.tpl');
    }
}
