<?php
class Ckc_RealisationMyrealisationModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $idCustomer = (int)$this->context->customer->id;
        $idShop = (int)$this->context->shop->id;

        // Récup stores liés au client
        $stores = Db::getInstance()->executeS('
          SELECT s.id_store, s.name
          FROM '._DB_PREFIX_.'ckc_favorite_stores fs
          INNER JOIN '._DB_PREFIX_.'store s ON s.id_store = fs.id_store
          WHERE fs.id_customer='.(int)$idCustomer
        );

        // Réalisations du client sur ce shop
        $rows = Db::getInstance()->executeS('
          SELECT r.*, rl.title, rl.alt
          FROM '._DB_PREFIX_.'ckc_realisation r
          LEFT JOIN '._DB_PREFIX_.'ckc_realisation_lang rl
            ON (rl.id_realisation=r.id_realisation AND rl.id_lang='.(int)$this->context->language->id.')
          WHERE r.id_shop='.(int)$idShop.' AND r.id_customer='.(int)$idCustomer.'
          ORDER BY r.date_add DESC
        ');

        $this->context->smarty->assign([
                                           'stores' => $stores,
                                           'rows' => $rows,
                                           'max_upload' => 5*1024*1024,
                                           'upload_link' => $this->context->link->getModuleLink($this->module->name,'myrealisationadd'),
                                           'delete_link' => $this->context->link->getModuleLink($this->module->name,'myrealisationdelete'),
                                           'token' => Tools::getToken(),
                                       ]);

        $this->setTemplate('module:ckc_realisation/views/templates/front/myrealisation.tpl');
    }
}
