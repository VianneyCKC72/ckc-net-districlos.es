<?php
/**
 * Creative Slider - Responsive Slideshow Module
 * https://creativeslider.webshopworks.com
 *
 * @author    WebshopWorks <info@webshopworks.com>
 * @copyright 2015-2020 WebshopWorks
 * @license   One Domain Licence
 *
 * Not allowed to resell or redistribute this software
 */
@ini_set('upload_max_size', '512M');
@ini_set('post_max_size', '512m');
@ini_set('max_execution_time', '300');
@ini_set('max_input_time', '300');
@ini_set('memory_limit', '512M');

require __DIR__ . '/../../classes/HelperEspacePro.php';

defined('_PS_VERSION_') or exit;

class AdminEspaceProController extends ModuleAdminController
{
    public $translator;

    const ID_GROUP_PRO = 10; // Groupe pro

    const VIEWS_TPL = _PS_MODULE_DIR_ . 'ckc_espacepro/views/';

    public function __construct()
    {
        $this->name        = 'ckc_espacepro';
        $this->tab         = 'ckcespace';
        $this->lang        = false;
        $this->bootstrap   = true;
        $this->module_name = 'ckc_espacepro';
        $this->className   = 'EspaceProClass';
        $this->context     = Context::getContext();
        $module_link       = 'index.php?controller=AdminEspacePro&configure=ckc_espacepro&token=';
        $module_link       .= Tools::getAdminTokenLite('AdminEspacePro');
        $this->context->smarty->assign([
                                           'module_link' => $module_link
                                       ]);

        parent::__construct();
    }

    private function createHelperForm($submitAction, $fieldsValue, $form)
    {
        $helper = new HelperForm();

        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $helper->module                   = $this;
        $helper->default_form_language    = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier               = $this->identifier;
        $helper->submit_action            = $submitAction;
        $helper->currentIndex             = $this->context->link->getAdminLink('AdminEspacePro', false)
                                            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token                    = Tools::getAdminTokenLite('AdminEspacePro');
        $helper->tpl_vars                 = [
            'fields_value' => $fieldsValue,
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$form]);
    }

    protected function getCurrentURL()
    {
        return Tools::getCurrentUrlProtocolPrefix() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    public function postProcessRib()
    {
        $this->postProcessFile('rib');
    }

    public function postProcessKbis()
    {
        $this->postProcessFile('kbis');
    }

    public function postProcessFile($key)
    {
        $row          = [];
        $urlRedirect  = $this->getCurrentURL();
        $error        = '';
        $id_customer  = Tools::getValue('CKC_CUSTOMER_ID');
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $Helper       = new HelperEspacePro();

        if (isset($_FILES[$key]) && $_FILES[$key]['error'] == 0) {
            $file          = $_FILES[$key];
            $fileName      = $file['name'];
            $fileTmpName   = $file['tmp_name'];
            $fileType      = $file['type'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

            if (in_array($fileType, $allowedTypes)) {
                $newFileName = $key . "-" . $id_customer . "." . $fileExtension;
                $uploader    = new UploaderCore();
                $newFile     = $uploader->upload($file, $newFileName);
                if ($key == 'kbis')
                    $row['fileUpload'] = $newFileName;
                elseif ($key == 'rib')
                    $row['rib'] = $newFileName;
            }
            else {
                $Helper->error[] = "Type de fichier non autorisé. Seuls les PDF, JPEG et PNG sont acceptés.";
            }
        }
        else {
            $Helper->error[] = $Helper->getUploadErrorMessage($_FILES[$key]['error']);
        }

        if ($error) {
            $Helper->addNotification($urlRedirect);
        }

        if (!empty($row)) {
            Db::getInstance()->update('customer', $row, 'id_customer = ' . (int)$id_customer, true, Db::INSERT_IGNORE);
            $Helper->success[] = 'Le ' . strtoupper($fileType) . ' a été mis à jour avec succès.';
            $Helper->addNotification($urlRedirect);
        }
    }

    /**
     * @return array|false
     */
    protected function getAllCustomersEp($includeDateCondition = false)
    {
        // Début de la requête SQL sans condition de date
        $sql = 'SELECT c.*, cl.name AS country_name, ad.vat_number as vat_number
FROM ps_customer c
INNER JOIN ps_customer_group cg ON cg.id_customer = c.id_customer
LEFT JOIN ps_country_lang cl ON cl.id_country = c.pays
LEFT JOIN ps_address ad ON ad.id_customer = c.id_customer
WHERE id_group = 10';

        // Si $includeDateCondition est vrai, ajouter la condition de date à la requête
        if ($includeDateCondition) {
            $date30DaysAgo = date('Y-m-d', strtotime('-30 days'));
            $sql           .= ' AND c.date_add >= \'' . $date30DaysAgo . '\'';
        }

        // Continuation de la requête
        $sql .= ' GROUP BY c.id_customer, c.email ORDER BY c.id_customer DESC';

        // Exécution de la requête
        $customers = Db::getInstance()->ExecuteS($sql);

        if (empty($customers)) {
            return false;
        }

        $array = [];
        foreach ($customers as $customer) {
            // Supposant que 'rib' et 'fileUpload' sont des colonnes dans votre base de données
            // Extrayez l'extension du fichier et ajoutez-la aux données du client
            $customer['ribFileExtension'] = isset($customer['rib']) ? strtolower(pathinfo($customer['rib'], PATHINFO_EXTENSION)) : '';
            $customer['fileExtension']    = isset($customer['fileUpload']) ? strtolower(pathinfo($customer['fileUpload'], PATHINFO_EXTENSION)) : '';
            $array[]                      = $customer;
        }
        return $array;
    }


    protected function getInactiveCount($includeDateCondition = false)
    {
        // Début de la requête SQL sans condition de date
        $sqlInactiveCount = 'SELECT COUNT(*) as inactiveCount FROM `ps_customer` c
        INNER JOIN ps_customer_group cg ON cg.id_customer = c.id_customer
        WHERE id_group = ' . self::ID_GROUP_PRO . ' 
        AND c.active = 0
        AND c.refuse = 0';

        // Si $includeDateCondition est vrai, ajouter la condition de date à la requête
        if ($includeDateCondition) {
            $date30DaysAgo    = date('Y-m-d', strtotime('-30 days'));
            $sqlInactiveCount .= ' AND c.date_add >= \'' . $date30DaysAgo . '\'';
        }

        // Exécution de la requête
        $inactiveCountResult = Db::getInstance()->getRow($sqlInactiveCount);
        $inactiveCount       = $inactiveCountResult ? (int)$inactiveCountResult['inactiveCount'] : 0;

        return $inactiveCount;
    }

    /**
     * @return int
     */
    protected function getRefuseCount($includeDateCondition = false)
    {
        // Début de la requête SQL sans condition de date
        $sqlRefuseCount = 'SELECT COUNT(*) as refuseCount FROM `ps_customer` c
    INNER JOIN ps_customer_group cg ON cg.id_customer = c.id_customer
    WHERE id_group = ' . self::ID_GROUP_PRO . ' AND c.refuse = 1';

        // Si $includeDateCondition est vrai, ajouter la condition de date à la requête
        if ($includeDateCondition) {
            $date30DaysAgo  = date('Y-m-d', strtotime('-30 days'));
            $sqlRefuseCount .= ' AND c.date_add >= \'' . $date30DaysAgo . '\'';
        }

        // Exécution de la requête
        $refuseCountResult = Db::getInstance()->getRow($sqlRefuseCount);
        $refuseCount       = $refuseCountResult ? (int)$refuseCountResult['refuseCount'] : 0;

        return $refuseCount;
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/customers.js');
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/customers.css');
    }


    /**
     * @param $status       indique le statut à mettre à jour (1 pour actif ou refusé, 0 pour non-actif ou non-refusé).
     * @param $isActivation est un booléen qui détermine si l'action concerne l'activation (true) ou le refus (false)
     *                      du compte.
     *
     * @return void
     */
    protected function updateCustomerStatusAndSendEmail($status, $isActivation, $id_customer = null)
    {
        if (!$id_customer) {
            $id_customer = Tools::getValue('id_customer');
        }

        if (!$id_customer) return;

        $statusColumn  = $isActivation ? '`active`' : '`refuse`';
        $emailTemplate = $isActivation ? 'compte_actif_pro' : 'compte_refuse_pro';
        $emailSubject  = $isActivation ?
            "Confirmation de Création de Votre Compte Professionnel Districlos" :
            "Refus Activation Compte Pro DISTRICLOS : Informations Importantes";

        Db::getInstance()->execute("UPDATE `ps_customer` SET $statusColumn = $status WHERE `id_customer` = " . (int)$id_customer);

        $customer = Db::getInstance()->getRow("SELECT * FROM `ps_customer` WHERE `id_customer` = " . (int)$id_customer);

        if ($customer) {
            Mail::Send(
                Configuration::get('PS_LANG_DEFAULT'),
                $emailTemplate,
                $emailSubject,
                [
                    '{firstname}' => $customer['firstname'],
                    '{lastname}'  => $customer['lastname'],
                    '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                    '{shop_url}'  => $this->context->link->getBaseLink($this->context->shop->id, true),
                ],
                $customer['email'],
                Configuration::get('PS_SHOP_NAME'),
                "info@cloture-discount.fr",
                Configuration::get('PS_SHOP_NAME')
            );
        }
        $HelperEspacePro = new HelperEspacePro();
        $HelperEspacePro->redirect();
    }


    /**
     * Set values for the inputs.
     */
    public function getConfigFormValues()
    {
        return [
            'CKC_CUSTOMER_ID' => false
        ];
    }


    /** Surcharge de la fonction de traduction sur PS 1.7 et supérieur.
     * La fonction globale ne fonctionne pas
     *
     * @param type $string
     * @param type $class
     * @param type $addslashes
     * @param type $htmlentities
     *
     * @return type
     */
    public function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if (_PS_VERSION_ >= '1.7') {
            return Context::getContext()->getTranslator()->trans($string);
        }
        else {
            return parent::l($string, $class, $addslashes, $htmlentities);
        }
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitKbisCkc_ep') == true) {
            $this->postProcessKbis();
        }
        if (Tools::isSubmit('submitRibCkc_ep') == true) {
            $this->postProcessRib();
        }
        parent::postProcess();
    }

    public function display()
    {
        $activation         = Tools::getValue('active');
        $refuse             = Tools::getValue('refuse');
        $accept             = Tools::getValue('accept');
        $nbCustomersLastDay = 0;
        $nbCustomers        = 0;
        $id_customer        = Tools::getValue('id_customer');

//        dump($activation);
//        dump($refuse);
//        exit;

        if ($activation === '0' || $activation === '1') {
            $this->updateCustomerStatusAndSendEmail($activation, true, $id_customer);
        } elseif ($refuse === '0' || $refuse === '1') {
            $this->updateCustomerStatusAndSendEmail($refuse, false, $id_customer);
        }

        if($accept === '1'){
            $this->updateCustomerStatusAndSendEmail(1, true, $id_customer);
        }

        $customers        = $this->getAllCustomersEp();
        $customersLastDay = $this->getAllCustomersEp(true);
        if (!empty(count($customers))) {
            $nbCustomers = count($customers);
        }
        if (!empty(count($customers))) {
            $nbCustomersLastDay = count($customers);
        }

        $this->context->smarty->assign('customers', $customers);
        $this->context->smarty->assign('nbCustomers', $nbCustomers);
        $this->context->smarty->assign('nbCustomersLastDay', $nbCustomersLastDay);
        $this->context->smarty->assign('InactiveCount', $this->getInactiveCount());
        $this->context->smarty->assign('RefuseCount', $this->getRefuseCount());
        $this->context->smarty->assign('InactiveCountLastDay', $this->getInactiveCount(true));
        $this->context->smarty->assign('RefuseCountLastDay', $this->getRefuseCount(true));
        $this->context->smarty->assign('editkbis', $this->renderFormKbis());
        $this->context->smarty->assign('editrib', $this->renderFormRib());

        $output = $this->context->smarty->fetch(self::VIEWS_TPL . 'templates/customers.tpl');

        $this->context->smarty->assign(['content' => $output]);

        $this->display_footer = false;

        parent::display();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    public function renderFormKbis()
    {
        return $this->createHelperForm('submitKbisCkc_ep', $this->getConfigFormValues(), $this->getEditUserKbisForm());
    }

    public function renderFormRib()
    {
        return $this->createHelperForm('submitRibCkc_ep', $this->getConfigFormValues(), $this->getEditUserRibForm());
    }

    /**
     * Create the structure of your form.
     */
    protected function getEditUserKbisForm($data = null)
    {
        return $this->getEditUserForm("Modifier le KBIS d'un professionnel", "kbis");
    }

    /**
     * Create the structure of your form.
     */
    protected function getEditUserRibForm($data = null)
    {
        return $this->getEditUserForm("Modifier le RIB d'un professionnel", "rib");
    }

    /**
     * Create the structure of your form.
     */
    protected function getEditUserForm($formTitle, $fileName)
    {
        $arrSelect = [];
        $customers = $this->getAllCustomersEp();


        if (!empty($customers)) {
            foreach ($customers as $customer) {
                $name        = !empty($customer['company']) ? $customer['company'] : $customer['firstname'] . " " . $customer['lastname'];
                $arrSelect[] = [
                    'id'   => $customer['id_customer'],
                    'name' => $customer['email'] . " - " . $name
                ];
            }

            if (!empty($arrSelect)) {
                usort($arrSelect, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
            }
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $formTitle,
                    'icon'  => 'icon-user',
                ],
                'input'  => [
                    [
                        'type'    => 'select',
                        'label'   => "Clients professionnels",
                        'name'    => 'CKC_CUSTOMER_ID',
                        'options' => [
                            'query' => $arrSelect,
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                    ],
                    $fileName => [
                        'type'          => 'file',
                        'label'         => "Modifier le " . $fileName,
                        'desc'          => '',
                        'display_image' => false,
                        'name'          => $fileName
                    ],
                ],
                'submit' => [
                    'title' => 'Enregistrez',
                ],
            ],
        ];
    }
}
