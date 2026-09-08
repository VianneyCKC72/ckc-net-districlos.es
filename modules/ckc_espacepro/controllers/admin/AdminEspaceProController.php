<?php
@ini_set('upload_max_size', '512M');
@ini_set('post_max_size', '512m');
@ini_set('max_execution_time', '300');
@ini_set('max_input_time', '300');
@ini_set('memory_limit', '512M');

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

        $module_link  = 'index.php?controller=AdminEspacePro&configure=ckc_espacepro&token=';
        $module_link .= Tools::getAdminTokenLite('AdminEspacePro');
        $this->context->smarty->assign(['module_link' => $module_link]);

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

    public function postProcessRib()  { $this->postProcessFile('rib'); }
    public function postProcessKbis() { $this->postProcessFile('kbis'); }

    public function ajaxProcessDownloadFile(){

        $fileName = Tools::getValue('fileName');

        $file =_PS_UPLOAD_DIR_."pro_docs/".$fileName;

        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"". basename($file) ."\"");
        header('User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36');
        header('Content-Length:' . filesize($file));

        readfile($file);

        exit();
    }

    public function ajaxProcessChangeShop()
    {
        $customerId = Tools::getValue('customerId');

        $customer = new Customer((int)$customerId);
        $old_default_group = $customer->id_default_group;

        $checkInAnotherShop = Db::getInstance()->executeS('SELECT id_customer FROM '._DB_PREFIX_.'customer 
        WHERE email LIKE "'.$customer->email.'" AND id_shop != '.$customer->id_shop);

        if(empty($checkInAnotherShop[0]['id_customer'])){
            if($customer->id_shop == 1){
                $customer->id_shop = 2;
                $customer->id_default_group = 10;
            } else if ($customer->id_shop == 2){
                $customer->id_shop = 1;
                $customer->id_default_group = 3;
            }

            $groups = $customer->getGroups();
            $newGroupList = array_filter($groups, function ($group) use ($old_default_group) { return $group != $old_default_group; });

            $newGroupList[] = $customer->id_default_group;
            $newGroupList[] = 3;
            $customer->updateGroup(array_unique($newGroupList));

            $customer->update();

            echo 'success';
        } else {
            echo 'fail';
        }
        exit();
    }

    public function ajaxProcessFindReferenceCompany(){
        $customerId = (int)Tools::getValue('selectedClient');
        if(!empty($customerId)){
            $selectReferenceCompany = Db::getInstance()->executeS('SELECT id_reference_company FROM '._DB_PREFIX_.'customer WHERE id_customer = '.$customerId);
            echo $selectReferenceCompany[0]['id_reference_company'];

        } else {
            echo 'fail';
        }
        exit();
    }

    public function postProcessFile($key)
    {
        $row          = [];
        $urlRedirect  = $this->getCurrentURL();
        $id_customer  = (int)Tools::getValue('CKC_CUSTOMER_ID');
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $Helper       = new HelperEspacePro();

        if (isset($_FILES[$key]) && (int)$_FILES[$key]['error'] === 0) {
            $file          = $_FILES[$key];
            $fileName      = $file['name'];
            $fileType      = $file['type'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

            if (in_array($fileType, $allowedTypes, true)) {
                $newFileName = $key . "-" . $id_customer . "." . $fileExtension;
                $uploader    = new UploaderCore();
                $uploader->upload($file, $newFileName);
                if ($key === 'kbis') { $row['fileUpload'] = pSQL($newFileName); }
                elseif ($key === 'rib') { $row['rib'] = pSQL($newFileName); }
            } else {
                $Helper->error[] = "Type de fichier non autorisé. Seuls les PDF, JPEG et PNG sont acceptés.";
            }
        } else {
            $Helper->error[] = $Helper->getUploadErrorMessage($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE);
        }

        if (!empty($row)) {
            Db::getInstance()->update('customer', $row, 'id_customer = ' . (int)$id_customer, true, Db::INSERT_IGNORE);
            $Helper->success[] = 'Le fichier a été mis à jour avec succès.';
        }
        $Helper->addNotification($urlRedirect);
    }

    /** ----- NEW: multi-groupes PRO ----- */
    private function getProGroupIds(): array
    {
        $ids = json_decode((string)Configuration::get('CKC_GROUP_PRO_IDS', '[]'), true);
        if (!is_array($ids) || empty($ids)) {
            $ids = [(int)Configuration::get('CKC_GROUP_PRO_ID', 10)];
        }
        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function getPrimaryProGroupId(): int
    {
        $v = (int)Configuration::get('CKC_GROUP_PRO_ID');
        return $v > 0 ? $v : 10;
    }

    private function getProticulierGroupId(): int
    {
        $v = (int)Configuration::get('CKC_GROUP_PROTICULIER_ID');
        return $v > 0 ? $v : 15; // standardise à 15
    }

    /** Helpers SQL */
    private function sqlList(array $ids): string
    {
        $ids = array_map('intval', $ids);
        return implode(',', $ids ?: [0]);
    }

    protected function getAllCustomersEp($includeDateCondition = false)
    {
        $idLang   = (int)$this->context->language->id;
        $proIds   = $this->getProGroupIds();             // groupes PRO
        $gidProti = $this->getProticulierGroupId();      // groupe proticulier

        // SEUL LE DEFAULT GROUP COMPTE
        $idsFilter = $this->sqlList(array_merge($proIds, [$gidProti]));

        // Boutique PRO = id_shop = 2
        $idShopPro = 2;

        $sql = 'SELECT c.*,
                   gl.name AS default_group_name,
                   cl.name AS country_name,
                   ad.vat_number AS vat_number,
                   c.id_reference_company,
                   c.date_add 
            FROM `'._DB_PREFIX_.'customer` c

            LEFT JOIN `'._DB_PREFIX_.'group_lang` gl
                   ON gl.id_group = c.id_default_group
                  AND gl.id_lang = '.$idLang.'

            /* JOIN SUR ADRESSE PAR DÉFAUT (IMPORTANT !) */
            LEFT JOIN `'._DB_PREFIX_.'address` ad
                   ON ad.id_address = c.id_address_default

            LEFT JOIN `'._DB_PREFIX_.'country_lang` cl
                   ON cl.id_country = ad.id_country
                  AND cl.id_lang = '.$idLang.'

            WHERE c.id_default_group IN ('.$idsFilter.')
              AND c.id_shop = '.(int)$idShopPro;

        if ($includeDateCondition) {
            $date30 = pSQL(date('Y-m-d', strtotime('-30 days')));
            $sql .= " AND c.date_add >= '{$date30}'";
        }

        $sql .= ' GROUP BY c.id_customer
              ORDER BY c.id_customer DESC';

        $customers = Db::getInstance()->ExecuteS($sql);
        if (empty($customers)) return false;

        /* PETIT TRAITEMENT DES EXTENSIONS (inchangé) */
        foreach ($customers as &$customer) {
            $customer["ribFileExtension"]  = !empty($customer["rib"]) ? strtolower(pathinfo($customer["rib"], PATHINFO_EXTENSION)) : "";
            $customer["fileExtension"]     = !empty($customer["fileUpload"]) ? strtolower(pathinfo($customer["fileUpload"], PATHINFO_EXTENSION)) : "";
            $customer["reference_company"] = !empty($customer["id_reference_company"]) ? Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname, ", ", email) FROM `'._DB_PREFIX_.'customer` WHERE id_customer = '.$customer["id_reference_company"]) : "";
        }

        return $customers;
    }



    protected function getInactiveCount($includeDateCondition = false)
    {
        $proIds   = $this->getProGroupIds();
        $gidProti = $this->getProticulierGroupId();
        $ids      = $this->sqlList(array_merge($proIds, [$gidProti]));

        $sql = 'SELECT COUNT(*) as inactiveCount
                FROM `'._DB_PREFIX_.'customer` c
                WHERE c.id_default_group IN ('.$ids.')
                  AND c.active = 0
                  AND c.refuse = 0';

        if ($includeDateCondition) {
            $date30DaysAgo = pSQL(date('Y-m-d', strtotime('-30 days')));
            $sql .= " AND c.date_add >= '{$date30DaysAgo}'";
        }

        $row = Db::getInstance()->getRow($sql);
        return $row ? (int)$row['inactiveCount'] : 0;
    }

    protected function getRefuseCount($includeDateCondition = false)
    {
        $proIds   = $this->getProGroupIds();
        $gidProti = $this->getProticulierGroupId();
        $ids      = $this->sqlList(array_merge($proIds, [$gidProti]));

        $sql = 'SELECT COUNT(*) AS refuseCount
                FROM `'._DB_PREFIX_.'customer` c
                WHERE c.id_default_group IN ('.$ids.')
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

    protected function updateCustomerStatusAndSendEmail($status, $isActivation, $id_customer = null)
    {
        if (!$id_customer) $id_customer = (int)Tools::getValue('id_customer');
        if (!$id_customer) return;

        $statusColumn  = $isActivation ? 'active' : 'refuse';
        $emailTemplate = $isActivation ? 'compte_actif_pro' : 'compte_refuse_pro';
        $emailSubject  = $isActivation
            ? "Confirmation de Création de Votre Compte Professionnel Districlos"
            : "Refus Activation Compte Pro DISTRICLOS : Informations Importantes";

        Db::getInstance()->update('customer', [$statusColumn => (int)$status], 'id_customer = '.(int)$id_customer);

        $customer = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'customer` WHERE `id_customer` = '.(int)$id_customer);
        if ($customer) {
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

    public function getConfigFormValues()
    {
        return ['CKC_CUSTOMER_ID' => false];
    }

    public function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if (_PS_VERSION_ >= '1.7') {
            return Context::getContext()->getTranslator()->trans($string);
        }
        return parent::l($string, $class, $addslashes, $htmlentities);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitKbisCkc_ep')) { $this->postProcessKbis(); }
        if (Tools::isSubmit('submitRibCkc_ep'))  { $this->postProcessRib(); }
        if (Tools::isSubmit('submitReferenceCompany_ep') && !Tools::isSubmit('DeleteCompanyReference'))  { $this->postProcessReferenceCompany(); }
        if (Tools::isSubmit('DeleteCompanyReference'))  { $this->postProcessDeleteCompanyReference(); }
        parent::postProcess();
    }

    public function display()
    {
        $tplPath = self::VIEWS_TPL . 'templates/customers.tpl';

        // logs
        $logDir = _PS_MODULE_DIR_ . 'ckc_espacepro/var/log';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logFile    = $logDir . '/ckc_ep_tpl_debug.log';
        $compileDir = _PS_ROOT_DIR_ . '/var/cache/dev/smarty/compile';

        // data
        $customers        = $this->getAllCustomersEp();
        $customersLastDay = $this->getAllCustomersEp(true);

        // sanitize rows
        $sanitized = [];
        if (is_array($customers)) {
            foreach ($customers as $row) {
                if (!is_array($row)) {
                    continue;
                }
                foreach ($row as $k => $v) {
                    if (is_array($v) || is_object($v) || $v === null) {
                        $row[$k] = '';
                    } elseif (is_bool($v)) {
                        $row[$k] = (int) $v;
                    }
                }
                $sanitized[] = $row;
            }
        }

        $nbCustomers        = count($sanitized);
        $nbCustomersLastDay = is_array($customersLastDay) ? count($customersLastDay) : 0;

        $ajaxUrl = $this->context->link->getAdminLink('AdminEspacePro', true, [], [
            'ajax'   => 1,
            'action' => 'changeCustomerGroup',
        ]);

        // Groupes switchables = Proticulier + PRO
        $groupsSwitchable = [];

        $proIds       = array_unique($this->getProGroupIds());
        $proIdsMap    = array_fill_keys($proIds, true);
        $gidProti     = (int) $this->getProticulierGroupId();
        $primaryProId = (int) $this->getPrimaryProGroupId();

        $allGroupsLang = Group::getGroups($this->context->language->id);

        // ajouter Proticulier en premier
        foreach ($allGroupsLang as $g) {
            if ((int) $g['id_group'] === $gidProti) {
                $groupsSwitchable[] = [
                    'id_group' => (int) $g['id_group'],
                    'name'     => $g['name'],
                ];
                break;
            }
        }

        // ajouter les PRO
        foreach ($proIds as $pid) {
            foreach ($allGroupsLang as $g) {
                if ((int) $g['id_group'] === (int) $pid) {
                    $groupsSwitchable[] = [
                        'id_group' => (int) $g['id_group'],
                        'name'     => $g['name'],
                    ];
                    break;
                }
            }
        }

        // dédoublonnage propre des arrays multidimensionnels
        $groupsSwitchable = array_values(array_unique($groupsSwitchable, SORT_REGULAR));

        // compteurs
        $counts        = (array) $this->getCountsByType();
        $nbPro         = (int) ($counts['nbPro'] ?? 0);
        $nbProticulier = (int) ($counts['nbProticulier'] ?? 0);

        $assign = [
            'customers'               => $sanitized,
            'customersLastDay'        => is_array($customersLastDay) ? $customersLastDay : [],
            'nbCustomers'             => $nbCustomers,
            'nbCustomersLastDay'      => $nbCustomersLastDay,
            'nbPro'                   => $nbPro,
            'nbProticulier'           => $nbProticulier,
            'editkbis'                => (string) $this->createHelperForm('submitKbisCkc_ep', $this->getConfigFormValues(), $this->getEditUserKbisForm()),
            'editrib'                 => (string) $this->createHelperForm('submitRibCkc_ep',  $this->getConfigFormValues(), $this->getEditUserRibForm()),
            'editcompanyreference'     => (string) $this->createHelperForm('submitReferenceCompany_ep',  $this->getConfigFormValuesReference(), $this->getEditUserReferenceCompanyForm()),
            'downloadFileUrl'         => Context::getContext()->link->getAdminLink('AdminEspacePro', true, [], ['action' => 'DownloadFile', 'ajax' => 1]),
            'findReferenceCompany'    => Context::getContext()->link->getAdminLink('AdminEspacePro', true, [], ['action' => 'FindReferenceCompany', 'ajax' => 1]),
            'groupsSwitchable'        => $groupsSwitchable,
            'id_group_pro'            => $primaryProId,
            'id_group_proticulier'    => $gidProti,
            'id_groups_pro'           => $proIds,
            'id_groups_pro_map'       => $proIdsMap,
            'ajax_change_group_url'   => $ajaxUrl,
        ];

        // log types
        $typeLines = [];
        foreach ($assign as $k => $v) {
            $typeLines[] = $k . '=' . (is_array($v) ? 'array('.count($v).')' : gettype($v));
        }
        @file_put_contents($logFile, '['.date('c')."] assign types => ".implode(', ', $typeLines)."\n", FILE_APPEND);

        // assign + render
        $this->context->smarty->clearAssign(array_keys($assign));
        $this->context->smarty->assign($assign);

        try {
            $output = $this->context->smarty->fetch($tplPath);
            $this->context->smarty->assign(['content' => (string) $output]);
        } catch (\Throwable $e) {

            $compiledHit = '';
            $lineGuess   = 154;
            $snippet     = '';

            $rii = is_dir($compileDir)
                ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($compileDir))
                : null;

            if ($rii) {
                foreach ($rii as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }
                    $fp = $file->getPathname();
                    if (strpos($fp, 'file.customers.tpl.php') !== false) {
                        $compiledHit = $fp;
                        break;
                    }
                }
            }

            if ($compiledHit && is_readable($compiledHit)) {
                $lines = @file($compiledHit, FILE_IGNORE_NEW_LINES);
                if ($lines !== false) {
                    $start  = max(0, $lineGuess - 10);
                    $end    = min(count($lines) - 1, $lineGuess + 10);
                    $chunk  = array_slice($lines, $start, $end - $start + 1, true);

                    foreach ($chunk as $ln => $code) {
                        $snippet .= sprintf("%06d: %s\n", $ln + 1, $code);
                    }
                }
            }

            @file_put_contents(
                $logFile,
                '['.date('c')."] FETCH ERROR: ".$e->getMessage().
                "\ncompiled: ".$compiledHit.
                "\n---- code around line ".$lineGuess." ----\n".$snippet.
                "\n-----------------------------------\n",
                FILE_APPEND
            );

            die(
                '<pre style="white-space:pre-wrap;max-width:100%;font:12px/1.4 monospace">'.
                "Template fetch FAILED: ".htmlspecialchars($e->getMessage())."\n\n".
                "Compiled file: ".htmlspecialchars($compiledHit ?: '[not found]')."\n".
                "Log file: ".htmlspecialchars($logFile)."\n".
                "→ Ouvre ce log dans le module pour voir le code exact.\n".
                "</pre>"
            );
        }

        $this->display_footer = false;
        parent::display();
    }


    protected function getCountsByType(): array
    {
        $proIds   = $this->getProGroupIds();
        $gidProti = $this->getProticulierGroupId();

        $inPro    = $this->sqlList($proIds);
        $sql = '
            SELECT
              SUM(c.id_default_group IN ('.$inPro.')) AS nb_pro,
              SUM(c.id_default_group = '.(int)$gidProti.') AS nb_proticulier
            FROM `'._DB_PREFIX_.'customer` c
            WHERE c.id_default_group IN ('.$inPro.','.(int)$gidProti.')
        ';
        $row = Db::getInstance()->getRow($sql);

        return [
            'nbPro'         => (int)($row['nb_pro'] ?? 0),
            'nbProticulier' => (int)($row['nb_proticulier'] ?? 0),
        ];
    }

    public function ajaxProcessChangeCustomerGroup()
    {
        // Protection : en-tête et buffer
        if (ob_get_length()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        $response = ['success' => false, 'message' => ''];

        try {
            if (!$this->access('edit')) {
                throw new Exception($this->l('Permission refusée'));
            }

            $idCustomer = (int)Tools::getValue('id_customer');
            $idGroup    = (int)Tools::getValue('id_group');
            if (!$idCustomer || !$idGroup) {
                throw new Exception($this->l('Paramètres manquants'));
            }

            $customer = new Customer($idCustomer);
            if (!Validate::isLoadedObject($customer)) {
                throw new Exception($this->l('Client introuvable'));
            }

            $proIds     = $this->getProGroupIds();
            $gidProti   = $this->getProticulierGroupId();
            $allowedIds = array_merge($proIds, [$gidProti]);
            $previousDefaultGroup = (int) $customer->id_default_group;

            if (!in_array($idGroup, $allowedIds, true)) {
                throw new Exception($this->l('Groupe non autorisé'));
            }

            $db = Db::getInstance();
            $ok = $db->update('customer', ['id_default_group' => (int)$idGroup], 'id_customer = '.(int)$idCustomer);
            if (!$ok) {
                $err = method_exists($db, 'getMsgError') ? $db->getMsgError() : '';
                throw new Exception($err ?: $this->l('Échec mise à jour client'));
            }

            // MAJ des appartenances
            $db->insert(
                'customer_group',
                ['id_customer' => (int)$idCustomer, 'id_group' => (int)$idGroup],
                false,
                true,
                Db::INSERT_IGNORE
            );

            // Nettoyage des autres groupes gérés
            $toClean = $this->sqlList($allowedIds);
            $db->delete(
                'customer_group',
                'id_customer='.(int)$idCustomer.' AND id_group IN ('.$toClean.') AND id_group!='.(int)$idGroup
            );

            // Hook isolé pour éviter tout output
            ob_start();
            Hook::exec('actionCustomerAccountUpdate', ['customer' => $customer]);
            ob_end_clean();

            // Mail d'acceptation proticulier → professionnel (+ sync Club Pro)
            $targetProIds = array_values(array_diff($proIds, [$gidProti]));
            if ($previousDefaultGroup === $gidProti && in_array($idGroup, $targetProIds, true)) {
                $customer = new Customer($idCustomer);
                $this->sendProticulierAcceptedAsProMail($customer);
                $this->syncClubProAfterProAcceptance($idCustomer);
            }

            $response['success'] = true;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        // Réponse finale propre
        echo json_encode($response);
        exit;
    }


    public function initProcess()
    {
        parent::initProcess();
        if (Tools::getIsset('ajax') && Tools::getValue('action') === 'changeCustomerGroup') {
            $this->ajaxProcessChangeCustomerGroup();
            exit;
        }
    }

    public function renderFormKbis() { return $this->createHelperForm('submitKbisCkc_ep', $this->getConfigFormValues(), $this->getEditUserKbisForm()); }
    public function renderFormRib()  { return $this->createHelperForm('submitRibCkc_ep',  $this->getConfigFormValues(), $this->getEditUserRibForm()); }

    protected function getEditUserKbisForm($data = null) { return $this->getEditUserForm("Modifier le KBIS d'un professionnel", "kbis"); }
    protected function getEditUserRibForm($data = null)  { return $this->getEditUserForm("Modifier le RIB d'un professionnel", "rib"); }

    protected function getEditUserForm($formTitle, $fileName)
    {
        $arrSelect = [];
        $customers = $this->getAllCustomersEp();

        if (!empty($customers)) {
            foreach ($customers as $customer) {
                $name        = !empty($customer['company']) ? $customer['company'] : ($customer['firstname'].' '.$customer['lastname']);
                $arrSelect[] = ['id' => (int)$customer['id_customer'], 'name' => $customer['email'].' - '.$name];
            }
            if (!empty($arrSelect)) {
                usort($arrSelect, function ($a, $b) { return strcmp($a['name'], $b['name']); });
            }
        }

        return [
            'form' => [
                'legend' => ['title' => $formTitle, 'icon'  => 'icon-user'],
                'input'  => [
                    [
                        'type'    => 'select',
                        'label'   => "Clients professionnels",
                        'name'    => 'CKC_CUSTOMER_ID',
                        'options' => ['query' => $arrSelect, 'id' => 'id', 'name' => 'name'],
                    ],
                    $fileName => [
                        'type'          => 'file',
                        'label'         => "Modifier le " . $fileName,
                        'display_image' => false,
                        'name'          => $fileName
                    ],
                ],
                'submit' => ['title' => 'Enregistrez'],
            ],
        ];
    }
    protected function getEditUserReferenceCompanyForm()
    {
        $arrSelect = [];
        $arrSelect[] = ['id' => 0, 'name' => 'Séléctionnez un client'];
        $customers = $this->getAllCustomersEp();

        if (!empty($customers)) {
            foreach ($customers as $customer) {
                $name        = !empty($customer['company']) ? $customer['company'] : ($customer['firstname'].' '.$customer['lastname']);
                $arrSelect[] = ['id' => (int)$customer['id_customer'], 'name' => $customer['email'].' - '.$name];
            }
            if (!empty($arrSelect)) {
                usort($arrSelect, function ($a, $b) { return strcmp($a['name'], $b['name']); });
            }
        }

        return [
            'form' => [
                'legend' => ['title' => 'Définir un compte professionnel de référence', 'icon'  => 'icon-user'],
                'description' => 'Cette section vous permet de rattacher plusieurs comptes professionnels (clients secondaires) à un compte principal appelé “Société de référence”.</br>Le compte principal devient alors le compte parent.</br><i>Exemple : La société A peut être définie comme société de référence pour les comptes B, C, D…</i>',
                'buttons' => [
                    'Supprimer'=> [
                        'type' => 'submit',
                        'name' => 'DeleteCompanyReference',
                        'title' => $this->l('Supprimer ce compte de société de référence pour le compte enfant sélectionné'),
                        'class' => 'btn btn-default pull-left',
                        'id'   => 'mybutton',
                        'icon' => 'icon-foo',
                    ],
                ],
                'input'  => [
                    [
                        'type'    => 'select',
                        'label'   => 'Clients professionnels <i class="icon icon-question custom-help-box"></i>',
                        'name'    => 'CKC_CUSTOMER_ID',
                        'desc'    => 'Compte enfant : sélectionnez le client à rattacher',
                        'hint'    => 'Sélectionnez ici le compte que vous souhaitez rattacher à une société de référence.</br>Ce compte deviendra un compte enfant.',
                        'options' => ['query' => $arrSelect, 'id' => 'id', 'name' => 'name'],
                    ],
                    [
                        'type'    => 'select',
                        'label'   => 'Compte de Société de référence <i class="icon icon-question custom-help-box"></i>',
                        'name'    => 'CKC_COMPANY_REFERENCE_ID',
                        'desc'    => 'Compte parent : sélectionnez la société de référence',
                        'hint'    => 'Choisissez ici le compte principal (parent) auquel rattacher le client sélectionné.</br>Ce compte sera considéré comme la société de référence.',
                        'options' => ['query' => $arrSelect, 'id' => 'id', 'name' => 'name'],
                    ],
                ],
                'submit' => ['title' => 'Enregistrez'],
            ],
        ];
    }

    public function getConfigFormValuesReference()
    {
        return ['CKC_CUSTOMER_ID' => false, 'CKC_COMPANY_REFERENCE_ID' => false];
    }

    public function postProcessReferenceCompany(){
        $clientId = Tools::getValue('CKC_CUSTOMER_ID');
        $referenceCompany = Tools::getValue('CKC_COMPANY_REFERENCE_ID');
        $checkReferenceCompany = Db::getInstance()->executeS('SELECT id_reference_company FROM ' . _DB_PREFIX_ . 'customer WHERE `id_customer` = '.(int)$referenceCompany);
        if(!empty($checkReferenceCompany[0]['id_reference_company'])){
            $this->errors[] = "Ce compte de référence est déjà un compte enfant et possède une société de référence";
        } else {
            if (!empty($clientId) && !empty($referenceCompany)) {
                $updateReferenceCompany = Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'customer SET `id_reference_company` = "'.$referenceCompany.'" 
            WHERE `id_customer` = '.(int)$clientId);
                if ($updateReferenceCompany) {
                    $clientChild = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM ' . _DB_PREFIX_ . 'customer WHERE `id_customer` = '.(int)$clientId);
                    $clientParent = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM ' . _DB_PREFIX_ . 'customer WHERE `id_customer` = '.(int)$referenceCompany);
                    $this->confirmations[] = 'Le client <strong>'.$clientChild.'</strong> est maintenant rattaché à la société de référence <strong>'.$clientParent.'</strong>.';
                } else {
                    $this->errors[] = "Erreur lors d'enregistrement d'une société de référence.";
                }
            }
        }
    }

    public function postProcessDeleteCompanyReference(){
        $clientId = Tools::getValue('CKC_CUSTOMER_ID');
        $referenceCompany = Tools::getValue('CKC_COMPANY_REFERENCE_ID');
        if(!empty($clientId) && !empty($referenceCompany)){
            $deleteReferenceCompany = Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'customer SET `id_reference_company` = null 
            WHERE `id_customer` = '.(int)$clientId);
            if($deleteReferenceCompany){
                $clientChild = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM ' . _DB_PREFIX_ . 'customer WHERE `id_customer` = '.(int)$clientId);
                $clientParent = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM ' . _DB_PREFIX_ . 'customer WHERE `id_customer` = '.(int)$referenceCompany);
                $this->confirmations[] = 'Le compte de référence <strong>'.$clientParent.'</strong> est maintenant supprimé pour le client <strong>'.$clientChild.'</strong>.';
            } else {
                $this->errors[] = "Erreur lors de suppression d'une société de référence.";
            }
        }
    }

    /**
     * Mail de bienvenue Club Pro lors du passage proticulier → professionnel.
     */
    private function sendProticulierAcceptedAsProMail(Customer $customer): void
    {
        if (!Validate::isLoadedObject($customer) || !Validate::isEmail($customer->email)) {
            return;
        }

        $idLang = (int) ($customer->id_lang ?: Configuration::get('PS_LANG_DEFAULT'));
        $idShop = (int) $this->context->shop->id;
        // Boutique PRO (multi-shop) : URLs / logo cohérents avec les mails Club Pro
        if (Shop::isFeatureActive() && Validate::isLoadedObject(new Shop(2))) {
            $idShop = 2;
        }

        $link = $this->context->link;
        $baseUrl = rtrim(
            $link ? $link->getBaseLink($idShop, true) : Tools::getShopDomainSsl(true) . __PS_BASE_URI__,
            '/'
        ) . '/';

        $statusUrl = $link
            ? $link->getPageLink('discount', true, $idLang, null, false, $idShop)
            : $baseUrl . 'reduction';

        $shopPhone = trim((string) Configuration::get('PS_SHOP_PHONE', null, null, $idShop));
        if ($shopPhone === '') {
            $shopPhone = '04 22 53 10 34';
        }

        $imgBase = $baseUrl . 'modules/dtc_paliers_remise/views/img/';

        $isoLang = Language::getIsoById($idLang);
        $mailSubject = ($isoLang === 'en')
            ? 'Your Districlos professional account is now active'
            : 'Votre compte professionnel Districlos est activé';

        Mail::Send(
            $idLang,
            'proticulier_accepte_pro',
            $mailSubject,
            [
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME', null, null, $idShop) ?: Configuration::get('PS_SHOP_NAME'),
                '{shop_url}' => $baseUrl,
                '{status_url}' => $statusUrl,
                '{shop_phone}' => $shopPhone,
                '{shop_phone_link}' => preg_replace('/\D+/', '', $shopPhone),
                '{stores_url}' => $baseUrl . 'content/11-nos-magasins-de-cloture',
                '{club_pro_logo}' => $imgBase . 'logo-pro.png',
                '{badge_bronze}' => $imgBase . 'badge-bronze.png',
                '{badge_argent}' => $imgBase . 'badge-argent.png',
                '{badge_or}' => $imgBase . 'badge-or.png',
            ],
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            'info@cloture-discount.fr',
            Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            _PS_MODULE_DIR_ . 'ckc_espacepro/mails/',
            false,
            $idShop
        );
    }

    /**
     * Recalcule le palier Club Pro sans renvoyer les mails upgrade/downgrade
     * (le mail de bienvenue suffit à l'acceptation).
     */
    private function syncClubProAfterProAcceptance(int $idCustomer): void
    {
        if ($idCustomer <= 0 || !Module::isEnabled('dtc_paliers_remise')) {
            return;
        }

        $servicePath = _PS_MODULE_DIR_ . 'dtc_paliers_remise/classes/DtcPalierService.php';
        $cartPath = _PS_MODULE_DIR_ . 'dtc_paliers_remise/classes/DtcCartRuleManager.php';
        if (!is_file($servicePath) || !is_file($cartPath)) {
            return;
        }

        require_once $servicePath;
        require_once $cartPath;

        if (!class_exists('DtcPalierService', false) || !class_exists('DtcCartRuleManager', false)) {
            return;
        }

        $tier = DtcPalierService::recompute($idCustomer);
        DtcCartRuleManager::syncCartRule($idCustomer, $tier);

        $after = DtcPalierService::getCustomerTier($idCustomer);
        if (!$after || empty($after['current_tier'])) {
            return;
        }

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'dtc_customer_palier`
             SET `last_notified_tier` = \'' . pSQL((string) $after['current_tier']) . '\'
             WHERE id_customer = ' . (int) $idCustomer
        );
    }
}
