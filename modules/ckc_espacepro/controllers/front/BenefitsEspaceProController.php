<?php


class ckc_espaceprobenefitsespaceproModuleFrontController extends ModuleFrontController
{
   public function initContent()
   {
    dump('test');die;

    // $this->context->smarty->assign(
            
    // );

    $this->setTemplate('module:ckc_espacepro/views/templates/front/benefits.tpl');

    parent::initContent();

   }
}