<?php

/*
 * With this override, you have a new Smarty variable called "currentController" available in header.tpl
 * This allows you to use a different header if you are on a product page, category page or home.
 */

class FrontController extends FrontControllerCore
{
    protected $proCustomerCache = [];

    // obselete je le fais dans le module
    private function getProUrlForCurrentShop(): string
    {
        $idShop = (int)$this->context->shop->id;
        return (string) Configuration::get('CKC_PRO_URL_BOUTIQUE', null, null, $idShop);
    }

    public function initContent()
    {

        // Initialisation des variables avec des valeurs par défaut
        $userId = 0;
        $kbis = [];
        $isLogged = 0;
        $env_pro = 0;
        $newClient                            = 0;
        $tva = 0;
        $connecter_avec_un_compte_particulier = 0;
        $espace_pro = !empty($_GET['ep']);
        $display_espace_pro = Configuration::get('CKC_DISPLAY_EP');
        $siret = 'Non renseigné';
        $abtest = Configuration::get('CKCABTESTACTIVATION_ACTIVE');

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

        $isConfirmationPage = strpos($_SERVER['REQUEST_URI'], 'confirmation') !== false;

        if ($isLogged) {
            $orders    = Order::getCustomerOrders($userId);
            $newClient = count($orders) == 0 ? 1 : 0;

            if (count($orders) == 1 && $isConfirmationPage) {
                $newClient = 1;
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

        $isLogged = $this->context->customer->isLogged();

        if ($isLogged) {
            // Vérifie si le client n'est PAS dans le groupe 10 (env_particulier)
            $env_particulier = !in_array(10, $this->context->customer->getGroups());

            $id_customer = (int)$this->context->customer->id;
            $default_address_id = null;

            if (isset($this->context->cookie->default_address_id_cached)) {
                $default_address_id = (int)$this->context->cookie->default_address_id_cached;
            } else {
                $sql = "SELECT id_address_default FROM ps_customer WHERE id_customer = $id_customer LIMIT 1";

                $row = Db::getInstance()->executeS($sql);

                if (!empty($row) && isset($row['id_address_default']) && $row['id_address_default']) {
                    $default_address_id = (int)$row['id_address_default'];
                } else {
                    $sql = 'SELECT id_address_delivery 
                FROM `'._DB_PREFIX_.'orders` 
                WHERE id_customer = '.(int)$id_customer.' 
                ORDER BY id_order DESC 
                LIMIT 1';
                    $row = Db::getInstance()->executeS($sql);

                    if (!empty($row) && isset($row['id_address_delivery']) && $row['id_address_delivery']) {
                        $default_address_id = (int)$row['id_address_delivery'];
                    } else {
                        $default_address_id = (int)Address::getFirstCustomerAddressId($id_customer);
                    }
                }

                if ($default_address_id) {
                    $this->context->cookie->default_address_id_cached = $default_address_id;
                }
            }

            if ($default_address_id) {
                $this->context->smarty->assign(['default_address_id' => $default_address_id]);
                Media::addJsDef(['default_address_id' => $default_address_id]);
            }

        } else {
            $env_particulier = false;
            // Nettoyage éventuel du cache si déconnecté
            unset($this->context->cookie->default_address_id_cached);
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
                'newClient' => $newClient,
                'env_particulier' => $env_particulier,
                'abtest' => $abtest,

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
        if (isset($this->proCustomerCache[$id])) {
            return $this->proCustomerCache[$id];
        }

        if (isset($this->context->cookie->{"pro_customer_info_$id"})) {
            $info = json_decode($this->context->cookie->{"pro_customer_info_$id"}, true);
            $this->proCustomerCache[$id] = $info;
            return $info;
        }

        $sql = 'SELECT `tva`, `adresse`, `adresse2`, `cp`, `ville`, `tel`, `fileUpload`, `rib`, `naf` FROM `'._DB_PREFIX_.'customer` 
            WHERE id_default_group = 10 AND id_customer = '.(int)$id;

        $customer = Db::getInstance()->getRow($sql); // Utilise `getRow` pour une seule ligne

        if (!empty($customer)) {
            // Mise en cache dans l'objet
            $this->proCustomerCache[$id] = $customer;

            // Mise en cache dans le cookie (sérialisé)
            $this->context->cookie->{"pro_customer_info_$id"} = json_encode($customer);

            return $customer;
        }

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
        $this->context->smarty->assign('IS_EMPLOYEE_BO', $show_emptycart);
        Media::addJsDef(['IS_EMPLOYEE_BO' => (bool)$show_emptycart]);

        //call original method, to maintain default behaviour:
        return parent::initHeader();
    }
}
