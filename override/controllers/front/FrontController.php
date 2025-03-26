<?php

/*
 * With this override, you have a new Smarty variable called "currentController" available in header.tpl
 * This allows you to use a different header if you are on a product page, category page or home.
 */

class FrontController extends FrontControllerCore
{

    public function initContent()
    {

        // Initialisation des variables avec des valeurs par défaut
        $userId = 0;
        $kbis = [];
        $isLogged = 0;
        $env_pro = 0;
        $tva = 0;
        $connecter_avec_un_compte_particulier = 0;
        $espace_pro = !empty($_GET['ep']);
        $display_espace_pro = Configuration::get('CKC_DISPLAY_EP');
        $siret = 'Non renseigné';

        if ($display_espace_pro == 1) {
            // Vérification si le client est défini pour extraire l'ID
            if (!empty($this->context->customer)) {
                $userId = $this->context->customer->id;
                // Vérification directe de l'état de connexion du client
                $isLogged = $this->context->customer->logged;

                // Détermination si l'utilisateur appartient au groupe avec l'ID 10
                $env_pro = $isLogged && in_array(10, $this->context->customer->getGroups());
                $connecter_avec_un_compte_particulier = (int)($isLogged && !in_array(10, $this->context->customer->getGroups()));
            }
        }

        if ($env_pro) {
            $infos = $this->getInfosProCustomer($userId);
            if (isset($infos['fileUpload']) && !empty($infos['fileUpload'])) {
                // Extrait l'extension du fichier
                $fileExtension = pathinfo($infos['fileUpload'], PATHINFO_EXTENSION);
                // Stocke l'extension dans le tableau du client pour une utilisation dans le template
                $kbis['fileExtension'] = strtolower($fileExtension);
                $kbis['fileUpload'] = $infos['fileUpload'];
            }

            if ($infos['tva'] == 1) {
                $tva = 1;
            }
            if (!empty($infos['siret'])) {
                $siret = $infos['siret'];
            }
        }
        $isLogged = $this->context->customer->logged;
        if ($isLogged) {
            $env_particulier = !in_array(10, $this->context->customer->getGroups());

            $queryAddress = Db::getInstance()->executeS('SELECT id_address_default FROM `' . _DB_PREFIX_ . 'customer` 
            WHERE id_customer = ' . (int)$this->context->customer->id);
            if (!isset($queryAddress[0]['id_address_default'])) {
                $queryAddress = Db::getInstance()->executeS('SELECT id_address_delivery FROM `' . _DB_PREFIX_ . 'orders` 
                WHERE id_customer = ' . (int)$this->context->customer->id . '
                ORDER BY id_order DESC LIMIT 1');
                if (!isset($queryAddress[0]['id_address_delivery'])) {
                    $queryAddress = Address::getFirstCustomerAddressId($this->context->customer->id);
                    if (isset($queryAddress)) {
                        $this->context->smarty->assign(['default_address_id' => (int)$queryAddress]);
                    }
                } else {
                    $this->context->smarty->assign(['default_address_id' => (int)$queryAddress[0]['id_address_delivery']]);
                }
            } else {
                $this->context->smarty->assign(['default_address_id' => (int)$queryAddress[0]['id_address_default']]);
            }
            if (isset($this->context->smarty->tpl_vars['default_address_id']->value)) {
                Media::addJsDef(['default_address_id' => $this->context->smarty->tpl_vars['default_address_id']->value]);
            }
        } else {
            $env_particulier = false;
        }

        $this->context->smarty->assign([
                'display_espace_pro' => $display_espace_pro,
                'env_pro' => $env_pro,
                'espace_pro' => $espace_pro,
                'userId' => $userId,
                'isLogged' => $isLogged,
                'connecter_avec_un_compte_particulier' => $connecter_avec_un_compte_particulier,
                'tva' => $tva,
                'siret' => $siret,
                'kbis' => $kbis,
                'env_particulier' => $env_particulier,
            ]
        );

        parent::initContent();
    }

    /**
     * @param $id
     *
     * @return mixed
     * @throws PrestaShopDatabaseException
     */
    public function getInfosProCustomer($id)
    {
        $sql = 'SELECT * FROM ps_customer c WHERE id_default_group = 10 AND c.id_customer = ' . (int)$id;

        $customer = Db::getInstance()->ExecuteS($sql);

        if (!empty($customer[0]))
            return $customer[0];
        return "";
    }

    public function postProcess()
    {
        $addressId = Tools::getValue('addressId');
        if (isset($addressId) && !empty($addressId)) {

            $update = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'customer` SET id_address_default = ' . $addressId . '
            WHERE id_customer = ' . (int)$this->context->customer->id);

            Media::addJsDef(['default_address_id' => $addressId]);

            echo "success";
        }
        parent::postProcess();
    }

    public function initHeader()
    {

        $show_emptycart = false;

        $cookie = new Cookie('psAdmin', '', (int)Configuration::get('PS_COOKIE_LIFETIME_BO'));

        if (isset($cookie->id_employee) && $cookie->id_employee) {
            $show_emptycart = true;
        } else {
            $show_emptycart = false;
        }

        $this->context->smarty->assign('show_emptycart', $show_emptycart);

        //

        //call original method, to maintain default behaviour:
        return parent::initHeader();
    }
}
