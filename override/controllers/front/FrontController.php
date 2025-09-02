<?php

/*
 * Override minimal : ne gère plus rien de l'espace pro.
 * Conserve uniquement la logique id_address_default (cache + assign + MAJ).
 */

class FrontController extends FrontControllerCore
{
    public function initContent()
    {
        $isLogged = $this->context->customer ? (bool)$this->context->customer->isLogged() : false;

        if ($isLogged) {
            $id_customer = (int) $this->context->customer->id;
            $default_address_id = null;

            if ($id_customer > 0 && !empty($this->context->cookie->default_address_id_cached)) {
                $default_address_id = (int) $this->context->cookie->default_address_id_cached;
                if ($default_address_id <= 0) {
                    $default_address_id = null;
                    unset($this->context->cookie->default_address_id_cached);
                }
            }

            if ($id_customer > 0 && !$default_address_id) {
                // 1) Colonne custom customer.id_address_default
                $sql = 'SELECT `id_address_default`
                FROM `' . _DB_PREFIX_ . 'customer`
                WHERE `id_customer` = ' . (int) $id_customer;

                $row = Db::getInstance()->getRow($sql);
                if (!empty($row['id_address_default'])) {
                    $default_address_id = (int) $row['id_address_default'];
                }

                // 2) Fallback : dernière adresse de livraison d'une commande
                if (!$default_address_id) {
                    $sql = 'SELECT `id_address_delivery`
                    FROM `' . _DB_PREFIX_ . 'orders`
                    WHERE `id_customer` = ' . (int) $id_customer . '
                    ORDER BY `id_order` DESC';

                    $row = Db::getInstance()->getRow($sql);
                    if (!empty($row['id_address_delivery'])) {
                        $default_address_id = (int) $row['id_address_delivery'];
                    }
                }

                // 3) Fallback final : toute première adresse du client
                if (!$default_address_id) {
                    $default_address_id = (int) Address::getFirstCustomerAddressId($id_customer);
                }

                if ($default_address_id > 0) {
                    $this->context->cookie->default_address_id_cached = $default_address_id;
                }
            }

            if ($default_address_id > 0) {
                $this->context->smarty->assign(['default_address_id' => $default_address_id]);
                Media::addJsDef(['default_address_id' => $default_address_id]);
            }
        } else {
            // Nettoyage éventuel du cache si déconnecté
            unset($this->context->cookie->default_address_id_cached);
        }



        parent::initContent();
    }

    public function postProcess()
    {
        // MAJ id_address_default via requête (ex: Ajax)
        $addressId = (int)Tools::getValue('addressId');

        if ($addressId) {
            Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'customer`
                 SET `id_address_default` = '.$addressId.'
                 WHERE `id_customer` = '.(int)$this->context->customer->id
            );

            Media::addJsDef(['default_address_id' => $addressId]);
            echo 'success';
        }

        parent::postProcess();
    }

    public function initHeader()
    {
        $show_emptycart = false;
        $cookie = new Cookie('psAdmin', '', (int)Configuration::get('PS_COOKIE_LIFETIME_BO'));

        if (!empty($cookie->id_employee)) {
            $show_emptycart = true;
        }

        $this->context->smarty->assign('show_emptycart', $show_emptycart);
        $this->context->smarty->assign('IS_EMPLOYEE_BO', $show_emptycart);
        Media::addJsDef(['IS_EMPLOYEE_BO' => (bool)$show_emptycart]);

        return parent::initHeader();
    }
}
