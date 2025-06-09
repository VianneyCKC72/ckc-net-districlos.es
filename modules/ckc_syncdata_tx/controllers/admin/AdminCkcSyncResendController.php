<?php

class AdminCkcSyncResendController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        $id_order = (int)Tools::getValue('id_order');

        if (!$id_order) {
            die('Invalid order ID');
        }

        /** @var Ckc_Syncdata_Tx $module */
        $module = Module::getInstanceByName('ckc_syncdata_tx');
        if (!$module || !$module->active) {
            die('Module not available');
        }

        $module->syncToRemote('order', $id_order, 'manual_resend');
        Tools::redirectAdmin($_SERVER['HTTP_REFERER'].'&conf=4');
    }
}
