<?php

require_once __DIR__ . '/../ckcadcampaignconfigurator.php';

class CampaignFormHelper extends ModuleAdminController{

    public function __construct(){
        $this->context = Context::getContext();
        $this->module = new Ckcadcampaignconfigurator;
    }

}
