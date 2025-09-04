<?php
@ini_set('upload_max_size', '512M');
@ini_set('post_max_size', '512M');
@ini_set('max_execution_time', '300');
@ini_set('max_input_time', '300');

require_once __DIR__ . '/classes/HelperEspacePro.php';

class ckc_espacepro extends Module
{
    /** @var array */
    public $warning = [];
    public $error = [];
    public $success = [];
    public $info = [];

    protected $tabs = [
        'Professionnels' => ['class' => 'AdminEspacePro', 'active' => 1],
    ];

    /** @var string */
    protected $redirect_after = '/';

    /** @var array */
    protected $themes = [];

    public function __construct()
    {
        $this->name = 'ckc_espacepro';
        $this->tab = 'administration';
        $this->author = 'CKC';
        $this->version = '1.0.1';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = 'CKC Espace PRO';
        $this->description = "Espace pro : inscription, champs dédiés, gestion des comptes professionnels.";
    }

    /** Installation */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        // Configs par défaut
        Configuration::updateValue('CKC_DISPLAY_EP', 0);
        Configuration::updateValue('CKC_FORM_MIN_EP', 0);
        Configuration::updateValue('CKC_RECAPTCHA_SECRET', ''); // ⚠️ mets la clé en BO module
        Configuration::updateValue('CKC_RECAPTCHA_ENABLED', 0);
        Configuration::updateValue('CKC_GROUP_PRO_ID', 10);
        Configuration::updateValue('CKC_GROUP_PROTICULIER_ID', 11);
        // Init URL PRO par boutique
        foreach (Shop::getShops(true, null, true) as $idShop) {
            Configuration::updateValue('CKC_PRO_URL_BOUTIQUE', '', false, null, (int)$idShop);
        }

        $ok =
            $this->registerHook('actionFrontControllerInit') &&
            $this->registerHook('displayCustomerAccountForm') &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('actionCustomerLogoutAfter') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionFrontControllerSetVariables') &&
            $this->registerHook('actionOrderGridQueryBuilderModifier') &&
            $this->registerHook('actionOrderGridDefinitionModifier') &&
            $this->registerHook('actionAuthentication')&&
            $this->registerHook('moduleRoutes');

        // Ajout onglets BO
        if ($ok) {
            $this->addTabs();
        }

        $this->clear_the_cache();
        return $ok;
    }

    public function hookActionFrontControllerSetVariables($params)
    {
        $loginError = (int)Tools::getValue('login_error', 0);
        $loginEmail = (string)Tools::getValue('login_email', '');

        $this->context->smarty->assign([
            'login_error' => $loginError,
            'login_email' => Tools::safeOutput($loginEmail),
        ]);


        return $this->injectGlobals();
    }

    public function hookActionFrontControllerInit($params)
    {
        die('test');

        $this->enforceShopAccessRouting();

        return $this->injectGlobals();
    }
    public function hookDisplayHome($params)
    {
        // Afficher uniquement sur la boutique PRO
        if (!$this->isProEnvironment()) {
            return '';
        }
        if ($this->context->customer && $this->context->customer->isLogged()) {
            return '';
        }
        $this->context->smarty->assign([
            'login_action' => $this->context->link->getPageLink('authentication', true),
            'back'         => Tools::safeOutput($_SERVER['REQUEST_URI'] ?? '/'),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/pro-home-login.tpl');
    }


    public function uninstall()
    {
        // Supprime l’onglet
        $id_tab = (int)Tab::getIdFromClassName('AdminEspacePro');
        if ($id_tab) {
            (new Tab($id_tab))->delete();
        }

//        include __DIR__ . '/sql/uninstall.php';

        // Nettoyage configs
        Configuration::deleteByName('CKC_DISPLAY_EP');
        Configuration::deleteByName('CKC_FORM_MIN_EP');
        Configuration::deleteByName('CKC_RECAPTCHA_SECRET');
        Configuration::deleteByName('CKC_RECAPTCHA_ENABLED');
        Configuration::deleteByName('CKC_GROUP_PRO_ID');
        Configuration::deleteByName('CKC_GROUP_PROTICULIER_ID');

        $this->clear_the_cache();

        return parent::uninstall();
    }

    private function getProGroupId(): int
    {
        $v = (int) Configuration::get('CKC_GROUP_PRO_ID');
        return $v > 0 ? $v : 10;
    }

    private function getProticulierGroupId(): int
    {
        $v = (int) Configuration::get('CKC_GROUP_PROTICULIER_ID');
        return $v > 0 ? $v : 11;
    }

    public function enable($force_all = false)
    {
        $res = parent::enable($force_all);
        if ($res) {
            $this->addTabs();
            $this->clear_the_cache();
        }
        return $res;
    }

    public function disable($force_all = false)
    {
        $this->deleteTabs();
        $this->clear_the_cache();
        return parent::disable($force_all);
    }

    private function getProUrlForCurrentShop(): string
    {
        $idShop = (int)$this->context->shop->id;
        return (string)Configuration::get('CKC_PRO_URL_BOUTIQUE', null, null, $idShop);
    }

    private function isProEnvironment(): bool
    {
        $idShop = (int) $this->context->shop->id;

        // Si c’est le shop 2 → toujours environnement pro todo : peu etre le rendre dynnamique aussi
        if ($idShop === 2) {
            return true;
        }

        $proUrl = (string) Configuration::get('CKC_PRO_URL_BOUTIQUE', null, null, $idShop);

        if ($proUrl !== '') {
            $currentUrl = Tools::getShopDomainSsl(true);
            return stripos($currentUrl, $proUrl) !== false;
        }

        return false;
    }

    public function assignProVars(): array
    {
        $ctx = $this->context;
        $cust = $ctx->customer;

        $isLogged = $cust ? (bool)$cust->isLogged() : false;
        $userId = (int)($cust->id ?? 0);
        $groups = $isLogged ? (array)$cust->getGroups() : [];

        $isProEnv    = $this->isProEnvironment();
        $frontSuffix = $isProEnv ? '-pro' : '';

        // Les status définie par kevin dans son cdc
        $gidPro         = $this->getProGroupId();
        $gidProticulier = $this->getProticulierGroupId();

        $inPro         = $isLogged && in_array($gidPro, $groups, true);
        $inProticulier = $isLogged && in_array($gidProticulier, $groups, true);
        $isVisitor = !$isLogged;
        $isParticulier = $isLogged && !$inPro && !$inProticulier;

        // Label status harmonisé
        $status = 'visitor';
        if ($isVisitor) {
            $status = 'visitor';
        } elseif ($inPro) {
            $status = 'pro';
        } elseif ($inProticulier) {
            $status = 'proticulier';
        } else {
            $status = 'particulier';
        }

        $customerInfo = [
            'id' => $userId,
            'siret' => null,
            'tva' => 0,
            'kbis' => null,
        ];

        if ($inPro && $userId > 0) {
            $infos = $this->getInfosProCustomer($userId);
            if (!empty($infos)) {
                $customerInfo['siret'] = $infos['siret'] ?? null;
                $customerInfo['tva'] = !empty($infos['tva']) ? 1 : 0;

                if (!empty($infos['fileUpload'])) {
                    $ext = Tools::strtolower(pathinfo($infos['fileUpload'], PATHINFO_EXTENSION));
                    $customerInfo['kbis'] = [
                        'file' => $infos['fileUpload'],
                        'ext' => $ext,
                    ];
                }
            }
        }

        // Mon nouveau payload a utilisé a la place des variables qu'on faisait dans FrontController.
        $pro = [
            'env'   => (int)$isProEnv,
            'suffix'=> $frontSuffix,
            'url_domain' => (string)Configuration::get('CKC_PRO_URL_BOUTIQUE', null, null, (int)$this->context->shop->id),
            'user'  => [
                'id'             => $userId,
                'is_logged'      => (int)$isLogged,
                'is_pro'         => (int)$inPro,
                'is_proticulier' => (int)$inProticulier,
                'is_particulier' => (int)$isParticulier,
                'is_visitor'     => (int)$isVisitor,
                'status'         => $status,
                'info'           => $customerInfo,
            ],
        ];

        $ctx->smarty->assign('pro' ,$pro);

        return $pro;
    }

    public function hookDisplayHeader($p)
    {
        $this->injectGlobals();
        return '';
    }

    /** Logout : purge caches utiles */
    public function hookActionCustomerLogoutAfter($params)
    {
        $this->clear_the_cache();
    }

    public function hookActionAuthentication($params)
    {
        $pro = $this->context->smarty->getTemplateVars('pro');
        $env_pro = (int)($pro['env'] ?? 0);

        if ($env_pro === 1) {
            Media::clearCache();
        }
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('controller') === 'AdminModules'
            && Tools::getValue('configure') === $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/bo_espacepro.css');
            $this->context->controller->addJS($this->_path . 'views/js/bo_espacepro.js');
        }
    }


    private function injectGlobals(bool $bo = false): array
    {
        $pro = $this->assignProVars();

        $vars = [
            'pro'        => $pro,
            'env_pro'    => (int)$pro['env'],
            'pro_suffix' => (string)$pro['suffix'],
            'is_pro'         => (int)$pro['user']['is_pro'],
            'is_proticulier' => (int)$pro['user']['is_proticulier'],
            'is_particulier' => (int)$pro['user']['is_particulier'],
            'is_visitor'     => (int)$pro['user']['is_visitor'],
        ];

        $this->context->smarty->assign($vars);
        foreach ($vars as $k => $v) {
            $this->context->smarty->tpl_vars[$k] = new Smarty_Variable($v, true); // nocache
        }
        return $vars;
    }

    /** JS FO */
    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerJavascript(
            'ckc-espacepro-auth',
            'modules/' . $this->name . '/views/js/authentification.js',
            ['position' => 'bottom', 'priority' => 1000]
        );

        $this->context->controller->registerJavascript(
            'ckc-espacepro-home-login',
            'modules/'.$this->name.'/views/js/pro-home-login.js',
            ['position' => 'bottom', 'priority' => 1001]
        );



         if (strpos($_SERVER['REQUEST_URI'], 'avantages-pro') !== false) {

            $this->context->controller->registerJavascript(
                'ckc-espacepro-layout',
                'modules/' . $this->name . '/views/js/front/layout.js',
                ['position' => 'bottom', 'priority' => 1000]
            );

             $this->context->controller->registerStylesheet(
            'ckc-espace-pro-front',
            'modules/' . $this->name . '/views/css/front/benefits.css',
            ['position' => 'bottom', 'priority' => 1000]

            );
        }
            $this->context->controller->registerJavascript(
                'ckc-espacepro-search',
                'modules/' . $this->name . '/views/js/front/search.js',
                ['position' => 'bottom', 'priority' => 1000]
            );

          $this->context->controller->registerStylesheet(
            'ckc-espace-pro-guides-fiche',
            'modules/' . $this->name . '/views/css/front/guides-fiches.css',
            ['position' => 'bottom', 'priority' => 1000]

            );
    }

    public function hookDisplayCustomerAccountForm($params)
    {
        if ((int)Configuration::get('CAPTCHA_ENABLE_ACCOUNT') === 1) {
            $this->themes = [0 => 'light', 1 => 'dark'];
            $this->context->smarty->assign([
                'captchaVersion' => Configuration::get('CAPTCHA_VERSION'),
                'publicKey' => Configuration::get('CAPTCHA_PUBLIC_KEY'),
                'captchaforcelang' => Configuration::get('CAPTCHA_FORCE_LANG'),
                'captchatheme' => $this->themes[(int)Configuration::get('CAPTCHA_THEME')],
            ]);
        }

        if ((int)Tools::getValue('ep', 0) === 1) {
            // Groupes clients (on retire les 3 systèmes)
            $groups = Group::getGroups($this->context->language->id);
            unset($groups[0], $groups[1], $groups[2]);

            // Pays, France en premier
            $countries = Country::getCountries($this->context->language->id, true);
            $this->putFranceFirst($countries);

            $this->context->smarty->assign([
                'customerGroups' => $groups,
                'countries' => $countries,
                'code_naf' => $this->getCodeNaf(),
                'ckc_form_minimal' => (int)Configuration::get('CKC_FORM_MIN_EP'),
            ]);

            return $this->display(__FILE__, 'hookDisplayCustomerAccountForm.tpl');
        }

        return '';
    }

    /** Ajout client : traite l’inscription pro si ep=1  todo a modifier avec l'idshop*/
    public function hookActionCustomerAccountAdd($params)
    {
        $gidPro = (int) $this->getProGroupId();

        $helper = new HelperEspacePro();
        $backLink = Tools::getValue('back');
        $shop_url = $this->context->link->getBaseLink($this->context->shop->id, true);
        $isPro = (int)Tools::getValue('ep', 0) === 1;
        $customer = $params['newCustomer'] ?? null;

        if (!Validate::isLoadedObject($customer)) {
            return;
        }

        // reCAPTCHA (opt-in via config)
        if ((int)Configuration::get('CKC_RECAPTCHA_ENABLED') === 1) {
            $token = Tools::getValue('g-recaptcha-response');
            $secret = (string)Configuration::get('CKC_RECAPTCHA_SECRET');

            if (empty($token) || empty($secret)) {
                $helper->error[] = 'Vérification reCAPTCHA indisponible.';
                $helper->addNotification('/');
                return;
            }

            // Vérif simple côté serveur
            $resp = @file_get_contents(
                'https://www.google.com/recaptcha/api/siteverify?secret='
                . rawurlencode($secret) . '&response=' . rawurlencode($token)
            );
            $data = json_decode($resp ?: '{}', true);

            if (empty($data['success'])) {
                $helper->error[] = 'Échec de la vérification reCAPTCHA.';
                $helper->addNotification('/');
                return;
            }
        }

        // Mode local (désactive envois mails)
        $host = (string)$_SERVER['SERVER_NAME'];
        $mode_local = (str_contains($host, '.local') || str_contains($host, 'vianney'));

        if ($isPro) {
            // Champs
            $siret = Tools::getValue('customer-siret', '');
            $customer_country = (int)Tools::getValue('customer-pays', 0);
            $customer_tva = Tools::getValue('customer-tva', '');
            $customer_address = Tools::getValue('customer-adresse', '');
            $customer_address2 = Tools::getValue('customer-adresse2', '');
            $customer_cp = Tools::getValue('customer-cp', '');
            $customer_tel = str_replace(' ', '', Tools::getValue('customer-tel', ''));
            $customer_city = Tools::getValue('customer-ville', '');
            $customer_company = Tools::getValue('customer-company', '');
            $vat_number = Tools::getValue('customer-vat_number', '');
            $naf = Tools::getValue('naf', '');

            // Uploads (si formulaire non minimal)
            $row = [];
            if ((int)Configuration::get('CKC_FORM_MIN_EP') === 0) {
                $files = ['kbis', 'rib'];
                foreach ($files as $fileKey) {
                    if (isset($_FILES[$fileKey]) && (int)$_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES[$fileKey];
                        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
                        if (!in_array($file['type'], $allowed, true)) {
                            $helper->error[] = "Type de fichier non autorisé ($fileKey).";
                            continue;
                        }
                        $siretUse = $siret !== '' ? $siret : 'nosiret';
                        $lastname = iconv('UTF-8', 'ASCII//TRANSLIT', str_replace(' ', '', Tools::strtolower($customer->lastname)));
                        $newFileName = $siretUse . ($fileKey === 'rib' ? '-rib-' : '-kbis-') . $lastname . '.' . Tools::strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                        $uploader = new UploaderCore();
                        $newFile = $uploader->upload($file, $newFileName);
                        if (!$newFile) {
                            $helper->error[] = "Erreur d’upload pour $fileKey.";
                            continue;
                        }

                        if ($fileKey === 'kbis') {
                            $row['fileUpload'] = $newFileName;
                        } else {
                            $row['rib'] = $newFileName;
                        }
                    } else {
                        // Message lisible
                        $helper->error[] = $helper->getUploadErrorMessage($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE);
                    }
                }
            }

            // Valeurs clients pro
            $row += [
                'pays' => $this->escapeForSQL((string)$customer_country),
                'tva' => $this->escapeForSQL((string)$customer_tva),
                'adresse' => (int)Configuration::get('CKC_FORM_MIN_EP') === 0 ? $this->escapeForSQL($customer_address) : '...',
                'adresse2' => $this->escapeForSQL($customer_address2),
                'cp' => $this->escapeForSQL($customer_cp),
                'tel' => $this->escapeForSQL($customer_tel),
                'ville' => (int)Configuration::get('CKC_FORM_MIN_EP') === 0 ? $this->escapeForSQL($customer_city) : '...',
                'active' => 0,
                'id_default_group' =>$gidPro,
                'naf' => $naf,
                'siret' => $siret,
            ];

            // Met le compte en inactif et groupe pro
            $customer->active = false;
            $customer->siret = $siret;
            $customer->update();
            $customer->addGroups([$gidPro]);

            // Adresse pro
            $address = new Address();
            $address->alias = 'Adresse Professionnelle';
            $address->active = $customer->active;
            $address->firstname = $customer->firstname;
            $address->lastname = $customer->lastname;
            $address->company = $customer_company;
            $address->address1 = (int)Configuration::get('CKC_FORM_MIN_EP') === 0 ? $customer_address : '...';
            $address->address2 = $customer_address2;
            $address->phone = $customer_tel;
            $address->postcode = $customer_cp;
            $address->city = (int)Configuration::get('CKC_FORM_MIN_EP') === 0 ? $customer_city : '...';
            $address->vat_number = $vat_number;
            $address->id_customer = (int)$customer->id;
            $address->id_country = $customer_country;
            $address->save();

            // Mise à jour table customer (colonnes custom)
            Db::getInstance()->update('customer', $row, 'id_customer=' . (int)$customer->id, true, Db::INSERT_IGNORE);

            // Notifications
            $helper->warning[] = "Votre compte professionnel est en cours de validation par notre équipe.";

            // Emails (désactivés en local)
            if (!$mode_local) {
                $data = [
                    '{firstname}' => $address->firstname,
                    '{lastname}' => $address->lastname,
                    '{company}' => $customer_company,
                    '{phone}' => $customer_tel,
                    '{naf}' => $naf,
                    '{tva}' => $vat_number,
                    '{adresse}' => $address->address1,
                    '{siret}' => $siret,
                    '{cp}' => $customer_cp,
                    '{email}' => $customer->email,
                    '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                    '{shop_url}' => $shop_url,
                ];

                $staffRecipients = ['commercial@districlos.com', 'nelly.serrano@districlos.com'];
                foreach ($staffRecipients as $email_to) {
                    Mail::Send(
                        (int)Configuration::get('PS_LANG_DEFAULT'),
                        'new_customers',
                        "Action requise : Validation compte professionnel",
                        $data,
                        $email_to,
                        Configuration::get('PS_SHOP_NAME'),
                        "info@cloture-discount.fr",
                        Configuration::get('PS_SHOP_NAME')
                    );
                }

                Mail::Send(
                    (int)Configuration::get('PS_LANG_DEFAULT'),
                    'inscription_pro',
                    "Votre compte pro est en cours de validation",
                    $data,
                    $customer->email,
                    $customer->firstname . ' ' . $customer->lastname,
                    "info@cloture-discount.fr",
                    Configuration::get('PS_SHOP_NAME')
                );
            }

            $helper->addNotification(
                (isset($backLink) && Tools::urlBelongsToShop($backLink)) ? $backLink : '/'
            );

            return;
        }

        // Compte standard
        $this->success[] = "Votre compte a bien été créé et vous êtes maintenant connecté.";
        $helper->addNotification(
            (isset($backLink) && Tools::urlBelongsToShop($backLink)) ? $backLink : '/'
        );
    }

    /** BO : colonnes/joins du grid commandes */
    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        $qb = $params['search_query_builder'];
        $searchCriteria = $params['search_criteria'];

        // Exemple d’enrichissement : jointure groupe client
        $qb->addSelect('pl.`name` AS group_name')
            ->innerJoin('cu', 'ps_group_lang', 'pl', 'pl.id_group = cu.id_default_group AND pl.id_lang = ' . (int)$this->context->language->id);

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ($filterName === 'groupe' && $filterValue) {
                $qb->andWhere('pl.`name` = :groupName')->setParameter('groupName', $filterValue);
            }
        }
    }

    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        /** @var GridDefinitionInterface $definition */
        $definition = $params['definition'];

        $columns = $definition->getColumns();
        $columns->addAfter('id_order',
            (new \PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn('group_name'))
                ->setName($this->l('Groupe client'))
                ->setOptions(['field' => 'group_name'])
        );
    }

    /** Pays : France en premier */
    protected function putFranceFirst(array &$countries): void
    {
        foreach ($countries as $k => $country) {
            if (($country['iso_code'] ?? '') === 'FR') {
                $fr = $countries[$k];
                unset($countries[$k]);
                array_unshift($countries, $fr);
                break;
            }
        }
    }

    public function getCodeNaf(): array
    {
        return (array)Db::getInstance()->executeS('SELECT code, description FROM `' . _DB_PREFIX_ . 'code_naf`');
    }

    public function escapeForSQL($input)
    {
        return str_replace("'", '', (string)$input);
    }

    protected function addTabs()
    {
        $parent = version_compare(_PS_VERSION_, '1.7.0', '<') ? 0 : (int)Tab::getIdFromClassName('AdminParentCustomer');

        foreach ($this->tabs as $name => $t) {
            if (Tab::getIdFromClassName($t['class'])) {
                continue; // évite doublons
            }
            $tab = new Tab();
            $tab->active = (int)$t['active'];
            $tab->class_name = $t['class'];
            $tab->module = $this->name;
            $tab->id_parent = $parent;
            $tab->name = [];

            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $name;
            }

            if (isset($t['icon'])) {
                $tab->icon = $t['icon'];
            }

            $tab->add();
        }
    }

    protected function deleteTabs()
    {
        foreach ($this->tabs as $t) {
            $id_tab = (int)Tab::getIdFromClassName($t['class']);
            if ($id_tab) {
                (new Tab($id_tab))->delete();
            }
        }
    }

    /** BO config */
    public function getContent()
    {
        if (Tools::isSubmit('submitCkc_ep')) {
            $this->postProcess();
        }

        $this->context->smarty->assign('token', Tools::getAdminTokenLite('AdminModules'));
        $this->context->smarty->assign('configuration', $this->renderForm());
        return $this->context->smarty->fetch($this->local_path . 'views/templates/config.tpl');
    }

    public function renderForm()
    {
        return $this->createHelperForm('submitCkc_ep', $this->getConfigFormValues(), $this->getConfigForm());
    }

    protected function createHelperForm($submitAction, $fieldsValue, $form)
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int)$this->context->language->id;
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = $submitAction;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $fieldsValue,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => (int)$this->context->language->id,
        ];

        return $helper->generateForm([$form]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => 'Configuration Espace Pro',
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => "Afficher l'espace pro en FO",
                        'name' => 'CKC_DISPLAY_EP',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => "Formulaire simplifié",
                        'name' => 'CKC_FORM_MIN_EP',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => "URL Espace Pro (redirection)",
                        'name' => 'CKC_PRO_URL_BOUTIQUE',
                        'class' => 'ckc-ep-url',
                        'placeholder' => 'https://pro.exemple.fr/',
                        'desc' => 'Spécifique à la boutique sélectionnée.',
                    ],
                    [
                        'type' => 'switch',
                        'label' => 'Activer reCAPTCHA',
                        'name' => 'CKC_RECAPTCHA_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => 'Clé secrète reCAPTCHA',
                        'name' => 'CKC_RECAPTCHA_SECRET',
                        'desc' => 'Clé serveur (ne pas la hardcoder dans le code).',
                    ],
                    [
                        'type'  => 'select',
                        'label' => 'Groupe Professionnel',
                        'name'  => 'CKC_GROUP_PRO_ID',
                        'options' => [
                            'query' => array_values(Group::getGroups($this->context->language->id)),
                            'id'    => 'id_group',
                            'name'  => 'name',
                        ],
                        'desc'  => 'Sélectionnez le groupe client utilisé pour les comptes professionnels.',
                    ],
                    [
                        'type'  => 'select',
                        'label' => 'Groupe Proticulier',
                        'name'  => 'CKC_GROUP_PROTICULIER_ID',
                        'options' => [
                            'query' => array_values(Group::getGroups($this->context->language->id)),
                            'id'    => 'id_group',
                            'name'  => 'name',
                        ],
                        'desc'  => 'Sélectionnez le groupe client pour les “proticulier” (pro avec tarifs particuliers).',
                    ],

                ],
                'submit' => [
                    'title' => 'Enregistrer',
                    'class' => 'pull-right',
                ],
            ],
        ];
    }

    public function getConfigFormValues()
    {
        return [
            'CKC_DISPLAY_EP' => (int)Configuration::get('CKC_DISPLAY_EP', 0),
            'CKC_FORM_MIN_EP' => (int)Configuration::get('CKC_FORM_MIN_EP', 0),
            'CKC_PRO_URL_BOUTIQUE' => Configuration::get('CKC_PRO_URL_BOUTIQUE', null, null, (int)$this->context->shop->id),
            'CKC_RECAPTCHA_ENABLED' => (int)Configuration::get('CKC_RECAPTCHA_ENABLED', 0),
            'CKC_RECAPTCHA_SECRET' => (string)Configuration::get('CKC_RECAPTCHA_SECRET', ''),
            'CKC_GROUP_PRO_ID'           => (int) Configuration::get('CKC_GROUP_PRO_ID', 10),
            'CKC_GROUP_PROTICULIER_ID'   => (int) Configuration::get('CKC_GROUP_PROTICULIER_ID', 11),
        ];
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitCkc_ep')) {
            // URL PRO multi-boutique
            $url = trim((string)Tools::getValue('CKC_PRO_URL_BOUTIQUE', ''));

            switch (Shop::getContext()) {
                case Shop::CONTEXT_SHOP:
                    Configuration::updateValue('CKC_PRO_URL_BOUTIQUE', $url, false, null, (int)Shop::getContextShopID());
                    break;
                case Shop::CONTEXT_GROUP:
                    foreach (Shop::getShops(true, (int)Shop::getContextShopGroupID(), true) as $idShop) {
                        Configuration::updateValue('CKC_PRO_URL_BOUTIQUE', $url, false, null, (int)$idShop);
                    }
                    break;
                case Shop::CONTEXT_ALL:
                    foreach (Shop::getShops(true, null, true) as $idShop) {
                        Configuration::updateValue('CKC_PRO_URL_BOUTIQUE', $url, false, null, (int)$idShop);
                    }
                    break;
            }

            // Autres configs
            $map = [
                'CKC_DISPLAY_EP',
                'CKC_FORM_MIN_EP',
                'CKC_RECAPTCHA_ENABLED',
                'CKC_RECAPTCHA_SECRET',
                'CKC_GROUP_PRO_ID',
                'CKC_GROUP_PROTICULIER_ID',
            ];
            foreach ($map as $key) {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
    }

    public function clear_the_cache()
    {
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
        Cache::clean('*');
    }

    private function getInfosProCustomer(int $id): array
    {
        if ($id <= 0) {
            return [];
        }
        $gidPro = (int) $this->getProGroupId();

        $sql = 'SELECT c.`tva`, c.`adresse`, c.`adresse2`, c.`cp`, c.`ville`, c.`tel`, c.`fileUpload`, c.`rib`, c.`naf`, c.`siret`
            FROM `'._DB_PREFIX_.'customer` c
            WHERE c.`id_default_group` = '.(int)$gidPro.'
              AND c.`id_customer` = '.(int)$id;

        return (array) Db::getInstance()->getRow($sql);
    }

    private function enforceShopAccessRouting(): void
    {
        $ctx  = $this->context;
        $cust = $ctx->customer;

        if (!$cust || !$cust->isLogged()) {
            return; // pas connecté : on ne force rien
        }

        $groups         = (array) $cust->getGroups();

        $gidPro         = (int) $this->getProGroupId();            // ex: 10
        $gidProticulier = (int) $this->getProticulierGroupId();    // ex: 11

        $isPro         = in_array($gidPro, $groups, true);
        $isProticulier = in_array($gidProticulier, $groups, true);
        $isAnyPro      = ($isPro || $isProticulier);

        $shopId = (int) $ctx->shop->id;

        $urlPro  = (string) Configuration::get('CKC_PRO_URL_BOUTIQUE',  null, null, 2); // shop PRO
        $urlPart = (string) Configuration::get('CKC_PART_URL_BOUTIQUE', null, null, 1); // shop Particulier

        $currentUrl = Tools::getShopDomainSsl(true) . $_SERVER['REQUEST_URI'];

        if ($shopId === 1 && $isAnyPro && !empty($urlPro)) {
            if (stripos($currentUrl, $urlPro) === false) {
                Tools::redirect($urlPro);
                exit;
            }
        }

        if ($shopId === 2 && !$isAnyPro && !empty($urlPart)) {
            if (stripos($currentUrl, $urlPart) === false) {
                Tools::redirect($urlPart);
                exit;
            }
        }
    }

    public function hookModuleRoutes($params)
    {
        return [
            'benefits_pro_route' => [
                'rule' => 'avantages-pro', //url de la page
                'controller' => 'benefitsespacepro',
                'keywords' => [],
                'params' => [
                    'module' => $this->name,
                    'fc' => 'module',
                    'controller' => 'benefitsespacepro'
                ]
            ],
                'guides_pro_route' => [
                'rule' => 'guides-pro', //url de la page
                'controller' => 'guidesfiches',
                'keywords' => [],
                'params' => [
                    'module' => $this->name,
                    'fc' => 'module',
                    'controller' => 'guidesfiches'
                ]
            ],
                'fiches_pro_route' => [
                'rule' => 'fiches-techniques-pro', //url de la page
                'controller' => 'guidesfiches',
                'keywords' => [],
                'params' => [
                    'module' => $this->name,
                    'fc' => 'module',
                    'controller' => 'guidesfiches'
                ]
            ],

        ];

    }

}
