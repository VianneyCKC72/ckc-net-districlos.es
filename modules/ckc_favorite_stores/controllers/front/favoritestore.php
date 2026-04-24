<?php
class Ckc_Favorite_StoresFavoriteStoreModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {

        parent::initContent();

        $action = Tools::getValue('action');
        $id_store = (int) Tools::getValue('id_store');

        if ($action === 'add') {
            if (!$id_store) {
                die(json_encode(['status' => 'error', 'message' => 'Aucun magasin sélectionné.']));
            }
            
            if ($this->context->customer->isLogged()) {
                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'ckc_favorite_stores` (id_customer, id_store, date_add)
                    VALUES (' . (int)$this->context->customer->id . ', ' . (int)$id_store . ', NOW())
                    ON DUPLICATE KEY UPDATE id_store = ' . (int)$id_store
                );
            } else {
                $this->context->cookie->__set('favorite_store', $id_store);
            }
            die(json_encode(['status' => 'success']));
        }else if ($action === 'get') {
            if (!$this->context->customer->isLogged()) {
                $id_store = $this->context->cookie->__get('favorite_store');
            }else{
                $id_store = Db::getInstance()->getValue('SELECT id_store FROM '._DB_PREFIX_.'ckc_favorite_stores WHERE id_customer = '.(int)$this->context->customer->id);
                if (!$id_store) {
                    $id_store = null;
                }
            }
            die(json_encode(['status' => 'success', 'id_store' => $id_store]));
        }
    }
}
