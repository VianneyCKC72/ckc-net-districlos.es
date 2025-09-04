<?php

require_once _PS_MODULE_DIR_ . 'ckc_espacepro/classes/front/BenefitsService.php';

class ckc_espaceprobenefitsespaceproModuleFrontController extends ModuleFrontController
{
   public function initContent()
   {

      parent::initContent();

      
      $service = new BenefitsService();
      $benefits = $service->getBenefitsForCustomer();
      $services = $service->getServicesForCustomer();


      $this->context->smarty->assign(compact('benefits', 'services'));

      $this->setTemplate('module:ckc_espacepro/views/templates/front/benefits/benefits.tpl');


   }
}