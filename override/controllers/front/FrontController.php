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
        $userId                               = 0;
        $kbis                                 = [];
        $isLogged                             = 0;
        $env_pro                              = 0;
        $tva                                  = 0;
        $connecter_avec_un_compte_particulier = 0;
        $espace_pro                           = !empty($_GET['ep']);
        $display_espace_pro                   = Configuration::get('CKC_DISPLAY_EP');
        $siret                                = 'Non renseigné';

        if ($display_espace_pro == 1) {
            // Vérification si le client est défini pour extraire l'ID
            if (!empty($this->context->customer)) {
                $userId = $this->context->customer->id;
                // Vérification directe de l'état de connexion du client
                $isLogged = $this->context->customer->logged;

                // Détermination si l'utilisateur appartient au groupe avec l'ID 10
                $env_pro                              = $isLogged && in_array(10, $this->context->customer->getGroups());
                $connecter_avec_un_compte_particulier = (int) ($isLogged && !in_array(10, $this->context->customer->getGroups()));
            }
        }

        if ($env_pro) {
            $infos = $this->getInfosProCustomer($userId);
            if (isset($infos['fileUpload']) && !empty($infos['fileUpload'])) {
                // Extrait l'extension du fichier
                $fileExtension = pathinfo($infos['fileUpload'], PATHINFO_EXTENSION);
                // Stocke l'extension dans le tableau du client pour une utilisation dans le template
                $kbis['fileExtension'] = strtolower($fileExtension);
                $kbis['fileUpload']    = $infos['fileUpload'];
            }

            if ($infos['tva'] == 1) {
                $tva = 1;
            }
            if (!empty($infos['siret'])) {
                $siret = $infos['siret'];
            }
        }

        $this->context->smarty->assign([
                                           'display_espace_pro'                   => $display_espace_pro,
                                           'env_pro'                              => $env_pro,
                                           'espace_pro'                           => $espace_pro,
                                           'userId'                               => $userId,
                                           'isLogged'                             => $isLogged,
                                           'connecter_avec_un_compte_particulier' => $connecter_avec_un_compte_particulier,
                                           'tva'                                  => $tva,
                                           'siret'                                => $siret,
                                           'kbis'                                 => $kbis
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

        if(!empty($customer[0]))
         return $customer[0];
        return "";
    }
}