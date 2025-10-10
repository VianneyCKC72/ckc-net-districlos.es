<?php
class Ckc_RealisationMyrealisationdeleteModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function postProcess()
    {
        if (!Tools::getToken() || Tools::getToken() !== Tools::getValue('token')) {
            $this->errors[] = $this->module->l('Token invalide');
            return;
        }
        $id = (int)Tools::getValue('id_realisation');
        $real = new Realisation($id);
        if (!Validate::isLoadedObject($real) || (int)$real->id_customer !== (int)$this->context->customer->id) {
            $this->errors[] = $this->module->l('Non autorisé');
        } else {
            @unlink($real->getFilePath());
            @unlink($real->getThumbPath());
            $real->delete();
        }
        Tools::redirect($this->context->link->getModuleLink($this->module->name,'myrealisation'));
    }
}
