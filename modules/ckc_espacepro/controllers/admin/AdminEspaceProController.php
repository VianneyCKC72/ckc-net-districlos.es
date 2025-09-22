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

    protected function getAllCustomersEp($includeDateCondition = false)
    {
        $idLang      = (int)$this->context->language->id;
        $gidPro      = (int)$this->getProGroupId();
        $gidProti    = (int)$this->getProticulierGroupId();

        $sql = 'SELECT c.*,
                   gl.name AS default_group_name,
                   cl.name AS country_name,
                   ad.vat_number as vat_number
            FROM `'._DB_PREFIX_.'customer` c
            LEFT JOIN `'._DB_PREFIX_.'group_lang` gl
                   ON gl.id_group = c.id_default_group AND gl.id_lang = '.$idLang.'
            LEFT JOIN `'._DB_PREFIX_.'country_lang` cl
                   ON cl.id_country = c.pays AND cl.id_lang = '.$idLang.'
            LEFT JOIN `'._DB_PREFIX_.'address` ad
                   ON ad.id_customer = c.id_customer
            WHERE c.id_default_group IN ('.(int)$gidPro.', '.(int)$gidProti.')';

        if ($includeDateCondition) {
            $date30DaysAgo = date('Y-m-d', strtotime('-30 days'));
            $sql .= ' AND c.date_add >= \''.$date30DaysAgo.'\'';
        }

        $sql .= ' GROUP BY c.id_customer, c.email
              ORDER BY c.id_customer DESC';

        $customers = Db::getInstance()->ExecuteS($sql);
        if (empty($customers)) return false;

        foreach ($customers as &$customer) {
            $customer['ribFileExtension'] = isset($customer['rib']) ? strtolower(pathinfo($customer['rib'], PATHINFO_EXTENSION)) : '';
            $customer['fileExtension']    = isset($customer['fileUpload']) ? strtolower(pathinfo($customer['fileUpload'], PATHINFO_EXTENSION)) : '';
        }
        return $customers;
    }

    protected function getInactiveCount($includeDateCondition = false)
    {
        $gidPro   = (int)$this->getProGroupId();
        $gidProti = (int)$this->getProticulierGroupId();

        $sql = 'SELECT COUNT(*) as inactiveCount
            FROM `'._DB_PREFIX_.'customer` c
            WHERE c.id_default_group IN ('.$gidPro.', '.$gidProti.')
              AND c.active = 0
              AND c.refuse = 0';

        if ($includeDateCondition) {
            $date30DaysAgo = date('Y-m-d', strtotime('-30 days'));
            $sql .= ' AND c.date_add >= \'' . pSQL($date30DaysAgo) . '\'';
        }

        $row = Db::getInstance()->getRow($sql);
        return $row ? (int)$row['inactiveCount'] : 0;
    }

    /**
     * @return int
     */
    protected function getRefuseCount($includeDateCondition = false)
    {
        $gidPro   = (int)$this->getProGroupId();            // lit CKC_GROUP_PRO_ID
        $gidProti = (int)$this->getProticulierGroupId();    // lit CKC_GROUP_PROTICULIER_ID

        $sql = 'SELECT COUNT(*) AS refuseCount
            FROM `'._DB_PREFIX_.'customer` c
            WHERE c.id_default_group IN ('.$gidPro.', '.$gidProti.')
              AND c.refuse = 1';

        if ($includeDateCondition) {
            $date30 = pSQL(date('Y-m-d', strtotime('-30 days')));
            $sql   .= " AND c.date_add >= '{$date30}'";
        }

        $row = Db::getInstance()->getRow($sql);
        return $row ? (int)$row['refuseCount'] : 0;
    }


    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/customers.js');
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/customers.css');
    }


    /**
     * @return void
     */
    protected function updateCustomerStatusAndSendEmail($status, $isActivation, $id_customer = null)
    {
        if (!$id_customer) {
            $id_customer = (int)Tools::getValue('id_customer');
        }
        if (!$id_customer) return;

        // Colonne ciblée
        $statusColumn  = $isActivation ? 'active' : 'refuse';
        $emailTemplate = $isActivation ? 'compte_actif_pro' : 'compte_refuse_pro';
        $emailSubject  = $isActivation
            ? "Confirmation de Création de Votre Compte Professionnel Districlos"
            : "Refus Activation Compte Pro DISTRICLOS : Informations Importantes";

        // UPDATE avec préfixe
        Db::getInstance()->update(
            'customer',
            [$statusColumn => (int)$status],
            'id_customer = '.(int)$id_customer
        );

        // Lecture client (avec préfixe correct)
        $customer = Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'customer` WHERE `id_customer` = '.(int)$id_customer
        );

        if ($customer) {
            // ⚠️ Assure-toi que le template existe en /mails/<lang>/$emailTemplate.html
            Mail::Send(
                (int)Configuration::get('PS_LANG_DEFAULT'),
                $emailTemplate,
                $emailSubject,
                [
                    '{firstname}' => $customer['firstname'],
                    '{lastname}'  => $customer['lastname'],
                    '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                    '{shop_url}'  => $this->context->link->getBaseLink((int)$this->context->shop->id, true),
                ],
                $customer['email'],
                $customer['firstname'].' '.$customer['lastname'],
                'info@cloture-discount.fr',
                Configuration::get('PS_SHOP_NAME')
            );
        }

        (new HelperEspacePro())->redirect();
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
        $nbCustomersLastDay = 0;
        $nbCustomers        = 0;

        $customers        = $this->getAllCustomersEp();
        $customersLastDay = $this->getAllCustomersEp(true);
        $nbCustomers        = $customers ? count($customers) : 0;
        $nbCustomersLastDay = $customersLastDay ? count($customersLastDay) : 0;

        $this->context->smarty->assign('customers', $customers);
        $this->context->smarty->assign('nbCustomers', $nbCustomers);
        $this->context->smarty->assign('nbCustomersLastDay', $nbCustomersLastDay);
        $this->context->smarty->assign($this->getCountsByType());
        $this->context->smarty->assign('editkbis', $this->renderFormKbis());
        $this->context->smarty->assign('editrib', $this->renderFormRib());

        $groupsSwitchable = [
            ['id_group' => (int)$this->getProticulierGroupId(), 'name' => $this->l('Proticulier')],
            ['id_group' => (int)$this->getProGroupId(),         'name' => $this->l('Professionnel')],
        ];
        $this->context->smarty->assign([
                                           'groupsSwitchable'      => $groupsSwitchable,
                                           'id_group_pro'          => (int)$this->getProGroupId(),
                                           'id_group_proticulier'  => (int)$this->getProticulierGroupId(),
                                           'ajax_change_group_url' => self::$currentIndex.'&token='.$this->token,
                                       ]);

        // >>> RENDU DU TEMPLATE <<<
        $output = $this->context->smarty->fetch(self::VIEWS_TPL . 'templates/customers.tpl');
        $this->context->smarty->assign(['content' => $output]);
        $this->display_footer = false;

        parent::display();
    }


    protected function getCountsByType(): array
    {
        $gidPro   = (int)$this->getProGroupId();
        $gidProti = (int)$this->getProticulierGroupId();

        $row = Db::getInstance()->getRow('
        SELECT
          SUM(c.id_default_group='.(int)$gidPro.')   AS nb_pro,
          SUM(c.id_default_group='.(int)$gidProti.') AS nb_proticulier
        FROM `'._DB_PREFIX_.'customer` c
        WHERE c.id_default_group IN ('.(int)$gidPro.', '.(int)$gidProti.')
    ');
        return [
            'nbPro'         => (int)($row['nb_pro'] ?? 0),
            'nbProticulier' => (int)($row['nb_proticulier'] ?? 0),
        ];
    }


    public function ajaxProcessChangeCustomerGroup()
    {
        header('Content-Type: application/json');

        if (!$this->access('edit')) {
            echo Tools::jsonEncode(['success' => false, 'message' => $this->l('Permission refusée')]);
            exit;
        }

        $idCustomer = (int)Tools::getValue('id_customer');
        $idGroup    = (int)Tools::getValue('id_group');

        if (!$idCustomer || !$idGroup) {
            echo Tools::jsonEncode(['success' => false, 'message' => $this->l('Paramètres manquants')]);
            exit;
        }

        $customer = new Customer($idCustomer);
        if (!Validate::isLoadedObject($customer)) {
            echo Tools::jsonEncode(['success' => false, 'message' => $this->l('Client introuvable')]);
            exit;
        }

        $gidPro   = (int)$this->getProGroupId();
        $gidProti = (int)$this->getProticulierGroupId();
        if (!in_array($idGroup, [$gidPro, $gidProti], true)) {
            echo Tools::jsonEncode(['success' => false, 'message' => $this->l('Groupe non autorisé')]);
            exit;
        }

        $db = Db::getInstance();

        $ok = $db->update(
            'customer',
            ['id_default_group' => (int)$idGroup],
            'id_customer = '.(int)$idCustomer
        );
        if (!$ok) {
            $err = method_exists($db, 'getMsgError') ? $db->getMsgError() : '';
            echo Tools::jsonEncode(['success' => false, 'message' => ($err ?: $this->l('Échec mise à jour client'))]);
            exit;
        }

        $db->insert(
            'customer_group',
            ['id_customer' => (int)$idCustomer, 'id_group' => (int)$idGroup],
            false,
            true,
            Db::INSERT_IGNORE
        );

        $db->delete(
            'customer_group',
            'id_customer='.(int)$idCustomer.' AND id_group IN ('.(int)$gidPro.','.(int)$gidProti.') AND id_group!='.(int)$idGroup
        );

        Hook::exec('actionCustomerAccountUpdate', ['customer' => $customer]);

        echo json_encode(['success' => true]);
        exit;
    }


    private function getProGroupId(): int
    {
        $v = (int)Configuration::get('CKC_GROUP_PRO_ID');
        return $v > 0 ? $v : 10;
    }

    private function getProticulierGroupId(): int
    {
        $v = (int)Configuration::get('CKC_GROUP_PROTICULIER_ID');
        return $v > 0 ? $v : 11;
    }

    public function initProcess()
    {
        parent::initProcess();

        if (Tools::getIsset('ajax') && Tools::getValue('action') === 'changeCustomerGroup') {
            $this->ajaxProcessChangeCustomerGroup();
            exit; // important
        }
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
