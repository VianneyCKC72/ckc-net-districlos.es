<?php
class DealtTvaToggleModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $apply = Tools::getValue('apply');
        $this->context->cookie->dealt_tva = $apply ? 1 : 0;
        exit;
    }
}
