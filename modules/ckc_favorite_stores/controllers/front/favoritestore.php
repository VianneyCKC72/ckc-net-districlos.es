<?php
class Ckc_Favorite_StoresFavoriteStoreModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $action = Tools::getValue('action');
        $id_store = (int) Tools::getValue('id_store');

        header('Content-Type: application/json; charset=utf-8');

        if ($action === 'add') {
            if (!$id_store) {
                die(json_encode([
                    'status' => 'error',
                    'message' => ($this->context->language->iso_code === 'en')
                        ? 'No store selected.'
                        : 'Aucun magasin sélectionné.',
                ]));
            }

            if ($this->context->customer->isLogged()) {
                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'ckc_favorite_stores` (id_customer, id_store, date_add)
                    VALUES (' . (int) $this->context->customer->id . ', ' . (int) $id_store . ', NOW())
                    ON DUPLICATE KEY UPDATE id_store = ' . (int) $id_store
                );
            } else {
                $this->context->cookie->__set('favorite_store', $id_store);
            }
            die(json_encode(['status' => 'success', 'id_store' => $id_store]));
        }

        if ($action === 'remove') {
            if ($this->context->customer->isLogged()) {
                Db::getInstance()->execute(
                    'DELETE FROM `' . _DB_PREFIX_ . 'ckc_favorite_stores`
                    WHERE id_customer = ' . (int) $this->context->customer->id
                    . ($id_store ? ' AND id_store = ' . (int) $id_store : '')
                );
            } else {
                $this->context->cookie->__unset('favorite_store');
            }
            die(json_encode(['status' => 'success', 'id_store' => null]));
        }

        if ($action === 'get') {
            if ($this->context->customer->isLogged()) {
                $id_store = Db::getInstance()->getValue(
                    'SELECT id_store FROM `' . _DB_PREFIX_ . 'ckc_favorite_stores`
                    WHERE id_customer = ' . (int) $this->context->customer->id
                ) ?: null;
            } else {
                $id_store = $this->context->cookie->__get('favorite_store') ?: null;
            }
            die(json_encode(['status' => 'success', 'id_store' => $id_store]));
        }

        die(json_encode([
            'status' => 'error',
            'message' => ($this->context->language->iso_code === 'en') ? 'Invalid action.' : 'Action invalide.',
        ]));
    }
}
