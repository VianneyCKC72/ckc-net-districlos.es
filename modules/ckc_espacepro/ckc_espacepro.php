<?php

@ini_set('upload_max_size', '512M');
@ini_set('post_max_size', '512');
@ini_set('max_execution_time', '300');
@ini_set('max_input_time', '300');

require_once __DIR__ . '/classes/HelperEspacePro.php';

use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * * Module Hh Registration : Ajout de champs au formulaire d'inscription
 * *
 * */
class ckc_espacepro extends Module
{
    public    $warning        = [];
    public    $error          = [];
    public    $success        = [];
    public    $info           = [];
    protected $tabs           = [
        'Professionnels' => ['class' => 'AdminEspacePro', 'active' => 1],
    ];
    public    $redirect_after = '/';

    const ID_GROUP_PRO = 10; // Groupe pro
    /**
     * @var array|string[]
     */
    /** @var array */
    protected $themes = [];

    public function __construct()
    {
        $this->name          = 'ckc_espacepro';
        $this->tab           = 'ckcespace';
        $this->author        = 'CKC';
        $this->version       = '1.0.0';
        $this->logo_path     = $this->_path . 'logo.png';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = 'CKC Espace PRO';
        $this->description = "Ce module améliore l'espace pro avec gestion des inscriptions et affichage des prix HT pour les clients professionnels, simplifiant la gestion utilisateur.";
    }

    /**
     *   * Installation du module : Installation Standard + greffe sur les hooks nécessaires
     *   * @return boolean
     *   */
    public function install()
    {
        Configuration::updateValue('CKC_DISPLAY_EP', false);
        Configuration::updateValue('CKC_FORM_MIN_EP', false);
        include(dirname(__FILE__) . '/sql/install.php');

        $isHooksRegistered = $this->registerHook('displayCustomerAccountForm') &&
            $this->registerHook('actionCustomerAccountAdd');

        return parent::install() && $isHooksRegistered;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        // Suppression de l'onglet créé lors de l'installation
        $id_tab = (int)Tab::getIdFromClassName('AdminCkcEspacePro');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        include(dirname(__FILE__) . '/sql/uninstall.php');

        // Appel à la méthode parent pour compléter la désinstallation
        return parent::uninstall() &&
            $this->unregisterHook('displayCustomerAccountForm') &&
            $this->unregisterHook('actionCustomerAccountAdd');
    }

    /**
     * @param $force_all
     *
     * @return bool
     */
    public function enable($force_all = false)
    {
        if ($res = parent::enable($force_all)) {
            $this->addTabs();
        }

        $this->registerHook('actionOrderGridQueryBuilderModifier');
        $this->registerHook('actionOrderGridDefinitionModifier');
        $this->registerHook('actionCustomerLogoutAfter');
        $this->registerHook('actionFrontControllerSetMedia');
        $this->registerHook('actionAuthentification');

        $this->clear_the_cache();

        return $res;
    }

    public function hookActionCustomerLogoutAfter($customer)
    {
        $this->_clearCache('module:tm_cmsbannerblock/views/templates/hook/tm_cmsbannerblock.tpl');

        // Vider le cache Smarty
        Cache::clean('smarty_cache');
        Cache::clean('smarty_compile');

        // Vider le cache de l'objet
        Cache::clear();
    }

    public function hookActionAuthentification($customer)
    {

        $my_env_pro = Context::getContext()->smarty->getTemplateVars()['env_pro'];
        if(!empty($my_env_pro)){
            if($my_env_pro == 1){
                $this->_clearCache('module:tm_imageslider/views/templates/hook/slider.tpl');
                Media::clearCache();
            }
        }
    }


    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {

        $searchQueryBuilder = $params['search_query_builder'];
        /** @var CustomerFilters $searchCriteria */
        $searchCriteria = $params['search_criteria'];
        $searchQueryBuilder->addSelect('pl.`name`')
            ->addSelect('cu.company')
            ->innerJoin(
                'cu',
                'ps_group_lang',
                'pl',
                'pl.id_group = cu.id_default_group');
        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('groupe' === $filterName && $filterValue) {
                $searchQueryBuilder->where('pl.`name` = \'' . $filterValue . '\'');
                $searchQueryBuilder->setParameter(':s', $filterValue);
            }
        }

        $searchQueryBuilder->addSelect('pl.name');

    }

    /**
     * @param $force_all
     *
     * @return bool
     */
    public function disable($force_all = false)
    {
        $this->deleteTabs();

        $this->clear_the_cache();

        return parent::disable($force_all);
    }

    /**
     * Save form data.
     */
    public function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm($data = null)
    {
        return [
            'form' => [
                'legend' => [
                    'title' => 'Configuration Module Espace Professionnels',
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'    => 'select',
                        'label'   => "Afficher l'espace des professionnels sur le front-office ?",
                        'name'    => 'CKC_DISPLAY_EP',
                        'options' => [
                            'query' => [
                                ['id' => 1, 'name' => 'Oui'],
                                ['id' => 0, 'name' => 'Non'],
                            ],
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                    ],
                    [
                        'type'    => 'select',
                        'label'   => "Formulaire simplifié ?",
                        'name'    => 'CKC_FORM_MIN_EP',
                        'options' => [
                            'query' => [
                                ['id' => 1, 'name' => 'Oui'],
                                ['id' => 0, 'name' => 'Non'],
                            ],
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                    ],
                ],
                'submit' => [
                    'title' => 'Enregistrez',
                ],
            ],
        ];
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
        $helper->currentIndex             = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token                    = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars                 = [
            'fields_value' => $fieldsValue,
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$form]);
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    public function renderForm()
    {
        return $this->createHelperForm('submitCkc_ep', $this->getConfigFormValues(), $this->getConfigForm());
    }

    /**
     * Set values for the inputs.
     */
    public function getConfigFormValues()
    {
        return [
            'CKC_DISPLAY_EP'  => Configuration::get('CKC_DISPLAY_EP', false),
            'CKC_FORM_MIN_EP' => Configuration::get('CKC_FORM_MIN_EP', false),
        ];
    }

    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitCkc_ep')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('token', Tools::getAdminTokenLite('AdminModules'));
        $this->context->smarty->assign('configuration', $this->renderForm());
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/config.tpl');
        $this->context->smarty->assign(['content' => $output]);

        return $output;
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function deleteTabs()
    {
        foreach ($this->tabs as $t) {
            $id_tab = (int)Tab::getIdFromClassName($t['class']);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }
    }

    public function clear_the_cache()
    {
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
    }

    /**
     * @return void
     */
    protected function addTabs()
    {
        $parent = version_compare(_PS_VERSION_, '1.7.0', '<') ? 0 : (int)Tab::getIdFromClassName('AdminParentCustomer');
        foreach ($this->tabs as $name => $t) {
            $tab             = new Tab();
            $tab->active     = $t['active'];
            $tab->class_name = $t['class'];
            $tab->name       = [];
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = isset($this->lang[$lang['iso_code']]) ? $this->lang[$lang['iso_code']][$name] : $name;
            }
            if (isset($t['icon'])) {
                $tab->icon = $t['icon'];
            }
            $tab->module    = $this->name;
            $tab->id_parent = $parent;
            $tab->add();
        }
    }

    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerJavascript(
            'front-js',
            'modules/' . $this->name . '/views/js/authentification.js',
            ['position' => 'bottom', 'priority' => 1000]
        );
    }

    /**
     *   * Nouveaux champs à rajouter sur le formulaire de création de compte
     *   * @param type $params
     *   */
    public function hookDisplayCustomerAccountForm($params)
    {

        if (Configuration::get('CAPTCHA_ENABLE_ACCOUNT') == 1) {
            $this->themes = [0 => 'light', 1 => 'dark'];

            $elCaptcha = [
                'captchaVersion'   => Configuration::get('CAPTCHA_VERSION'),
                'publicKey'        => Configuration::get('CAPTCHA_PUBLIC_KEY'),
                'captchaforcelang' => Configuration::get('CAPTCHA_FORCE_LANG'),
                'captchatheme'     => $this->themes[Configuration::get('CAPTCHA_THEME')],
            ];
            $this->context->smarty->assign($elCaptcha);
        }

        if (!empty($_GET['ep']) == 1) {

            //Récupération des groupes clients Prestashop
            $groups = Group::getGroups($this->context->language->id);

            //On supprime les groupes systèmes de prestashop ( Les 3 premiers ids )
            unset($groups[0]);
            unset($groups[1]);
            unset($groups[2]);

            $countries = Country::getCountries($this->context->language->id, true);

            $this->putFranceFirst($countries);

            //Assignation des variables au template
            $this->context->smarty->assign('customerGroups', $groups);
            $this->context->smarty->assign('countries', $countries);
            $this->context->smarty->assign('code_naf', $this->getCodeNaf());
            $this->context->smarty->assign('ckc_form_minimal',  Configuration::get('CKC_FORM_MIN_EP'));

            //Affichage du template du module ( situé dans views/templates/hook )
            return $this->display(__FILE__, 'hookDisplayCustomerAccountForm.tpl');
        }
    }

    function putFranceFirst(&$countries)
    {
        // Identifiez et extrayez la France
        $franceKey = null;
        foreach ($countries as $key => $country) {
            if ($country['iso_code'] == 'FR') {
                $franceKey = $key;
                break;
            }
        }

        if ($franceKey !== null) {
            $france = $countries[$franceKey];
            unset($countries[$franceKey]);      // Retirez la France du tableau original
            array_unshift($countries, $france); // Ajoutez la France au début du tableau
        }
    }


    /**
     *   Traitement des nouveaux champs du formulaire d'inscription
     */
    public function hookActionCustomerAccountAdd($params)
    {
        $backLink = Tools::getValue('back');
        $logFilePath = __DIR__ . '/logs.txt';

        $shop_url          = $this->context->link->getBaseLink($this->context->shop->id, true);

        // Récupérer le token reCAPTCHA et vérifier avec Google
        if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
            $recaptchaToken = $_POST['g-recaptcha-response'];
        }
        else {
            // Le token n'est pas présent ou est vide
            logMessage("reCAPTCHA token is missing.");
            echo 'reCAPTCHA token is missing.';
            exit;
        }

        $response     = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=6LenlNocAAAAALI74qAIBnDd4Yj4GrQQpvrGE6aI&response=" . $recaptchaToken);
        $responseKeys = json_decode($response, true);
        $customerId   = isset($params['newCustomer']->id) ? $params['newCustomer']->id : 'Unknown';
        $currentUrl   = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $isPro        = isset($_GET['ep']) && $_GET['ep'] == 1 ? 'Pro' : 'Non-Pro';
        if ($responseKeys["success"]) {
            $score    = $responseKeys["score"];
            $logEntry = "ID: $customerId, Score: $score, IP: {$_SERVER['REMOTE_ADDR']}, User Agent: {$_SERVER['HTTP_USER_AGENT']}, URL: $currentUrl, User Type: $isPro, Post Data: " . json_encode($_POST) . "\n";
            file_put_contents($logFilePath, $logEntry, FILE_APPEND | LOCK_EX);
        }
        else {
            $logEntry = "(ID: $customerId) Échec de la vérification reCAPTCHA. Response Keys: " . json_encode($responseKeys) . ", IP: {$_SERVER['REMOTE_ADDR']}, User Agent: {$_SERVER['HTTP_USER_AGENT']}, URL: $currentUrl, User Type: $isPro, Post Data: " . json_encode($_POST) . "\n";
            file_put_contents($logFilePath, $logEntry, FILE_APPEND | LOCK_EX);
        }

        if (strpos($_SERVER['SERVER_NAME'], '.local') !== false || strpos($_SERVER['SERVER_NAME'], 'vianney') !== false) {
            $mode_local = true;
        }
        else {
            $mode_local = false;
        }

        $siret  = '';
        $Helper = new HelperEspacePro();

        if (!empty($_GET['ep']) == 1) {
            $row['siret'] = Tools::getValue('customer-siret', '');

            if (isset($row['siret'])) {
                $siret = $row['siret'];
            }

            //On récupère notre variable groupe
            $customer_pays     = Tools::getValue('customer-pays', '');
            $customer_tva      = Tools::getValue('customer-tva', '');
            $customer_adresse  = Tools::getValue('customer-adresse', '');
            $customer_adresse2 = Tools::getValue('customer-adresse2', '');
            $customer_cp       = Tools::getValue('customer-cp', '');
            $customer_tel      = str_replace(' ', '', Tools::getValue('customer-tel', ''));
            $customer_city     = Tools::getValue('customer-ville', '');
            $customer_company  = Tools::getValue('customer-company', '');
            $vat_number        = Tools::getValue('customer-vat_number', '');
            $naf               = Tools::getValue('naf', '');
            $error             = ''; // Initialiser la variable d'erreur
            if(Configuration::get('CKC_FORM_MIN_EP') == 0) {
                $files = ['kbis', 'rib'];
                foreach ($files as $fileKey) {
                    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == 0) {
                        $file         = $_FILES[$fileKey];
                        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                        if (in_array($file['type'], $allowedTypes)) {
                            $siretUse    = empty($siret) ? "nosiret" : $siret;
                            $lastname    = iconv('UTF-8', 'ASCII//TRANSLIT', str_replace(' ', '', strtolower($params['newCustomer']->lastname)));
                            $newFileName = $siretUse . ($fileKey == 'rib' ? "-rib-" : "-kbis-") . $lastname . "." . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $uploader    = new UploaderCore();
                            $newFile     = $uploader->upload($file, $newFileName);
                            if ($fileKey == 'kbis')
                                $row['fileUpload'] = $newFileName;
                            else
                                $row['rib'] = $newFileName;
                        }
                        else {
                            $error = "Type de fichier non autorisé. Seuls les PDF, JPEG et PNG sont acceptés.";
                        }
                    }
                    else {
                        $error = $Helper->getUploadErrorMessage($_FILES[$fileKey]['error']);
                    }
                    if ($error) {
                        $Helper->error[] = $error;
                        $Helper->addNotification("/");
                    }
                }
            }
            if ($error) {
                $Helper->error[] = $error;
                $Helper->addNotification("/");
            }

            $row['pays']                   = $this->escapeForSQL($customer_pays);
            $row['tva']                    = $this->escapeForSQL($customer_tva);
            if(Configuration::get('CKC_FORM_MIN_EP') == 0) {
                $row['adresse'] = $this->escapeForSQL($customer_adresse);
            } else {
                $row['adresse'] = "...";
                $customer_adresse = $row['adresse'];
            }
            $row['adresse2']               = $this->escapeForSQL($customer_adresse2);
            $row['cp']                     = $this->escapeForSQL($customer_cp);
            $row['tel']                    = $this->escapeForSQL($customer_tel);
            if(Configuration::get('CKC_FORM_MIN_EP') == 0) {
                $row['ville'] = $this->escapeForSQL($customer_city);
            } else {
                $row['ville'] = "...";
                $customer_city = $row['ville'];
            }

            $row['active']                 = 0;
            $row['id_default_group']       = self::ID_GROUP_PRO;
            $row['naf']                    = $naf;
            $params['newCustomer']->active = false;
            $params['newCustomer']->siret  = Tools::getValue('customer-siret', '');

            $address              = new Address();
            $address->alias       = 'Adresse Professionnel';
            $address->active      = $params['newCustomer']->active;
            $address->firstname   = $params['newCustomer']->firstname;
            $address->lastname    = $params['newCustomer']->lastname;
            $address->company     = $customer_company;
            $address->address1    = $customer_adresse;
            $address->address2    = $customer_adresse2;
            $address->phone       = (str_replace(' ', '', $customer_tel));
            $address->postcode    = $customer_cp;
            $address->city        = $customer_city;
            $address->vat_number  = $vat_number;
            $address->id_customer = $params['newCustomer']->id; // todo : a verifier
            $address->id_country = $customer_pays;

            $address->save();

            Db::getInstance()->update('customer', $row, 'id_customer = ' . (int)$params['newCustomer']->id, true, Db::INSERT_IGNORE);

            // On ajoute le groupe espace_pro
            $params['newCustomer']->addGroups([self::ID_GROUP_PRO]);
            $Helper->warning[] = "Notre équipe va procéder à la vérification de vos informations et activera votre compte professionnel dans les plus brefs délais. Nous vous remercions pour votre patience et votre confiance.";

            $naf               = '';
            if (isset($row['naf'])) {
                $naf = $row['naf'];
            }

            $data = [
                '{firstname}' => $address->firstname,
                '{lastname}'  => $address->lastname,
                '{company}'   => $customer_company,
                '{phone}'     => $customer_tel,
                '{naf}'       => $naf,
                '{tva}'       => $vat_number,
                '{adresse}'   => $customer_adresse,
                '{siret}'     => $row['siret'],
                '{cp}'        => $customer_cp,
                '{email}'     => $params['newCustomer']->email,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                '{shop_url}'  => $shop_url,
            ];

            $file_attachment = false;
            $emailSend       = ['commercial@districlos.com', 'nelly.serrano@districlos.com'];

            foreach ($emailSend as $email_to) {
                if (!$mode_local) {
                    Mail::Send(
                        Configuration::get('PS_LANG_DEFAULT'),
                        'new_customers',
                        "Action Requise : Validation d'un Compte Professionnel",
                        $data,
                        $email_to,
                        Configuration::get('PS_SHOP_NAME'),
                        "info@cloture-discount.fr",
                        Configuration::get('PS_SHOP_NAME'),
                        $file_attachment
                    );
                }
            }
            if (!$mode_local) {
                Mail::Send(
                    Configuration::get('PS_LANG_DEFAULT'),
                    'inscription_pro',
                    "Votre Compte chez Cloture Discount est en Cours de Validation !",
                    $data,
                    $params['newCustomer']->email,
                    Configuration::get('PS_SHOP_NAME'),
                    "info@cloture-discount.fr",
                    Configuration::get('PS_SHOP_NAME')
                );
            }

            if (isset($backLink) && Tools::urlBelongsToShop($backLink)){
                $Helper->addNotification($backLink);
            } else {
                $Helper->addNotification("/");
            }

        } else {
            $Helper->success[] = "Votre compte a bien été créé et vous êtes maintenant connecté.";

//            $data = [
//                '{firstname}' =>$params['newCustomer']->firstname,
//                '{lastname}'  => $params['newCustomer']->lastname,
//                '{email}'     => $params['newCustomer']->email,
//                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
//                '{shop_url}'  => $shop_url,
//            ];
//
//            Mail::Send(
//                Configuration::get('PS_LANG_DEFAULT'),
//                'account',
//                "Bienvenue !",
//                $data,
//                $params['newCustomer']->email,
//                Configuration::get('PS_SHOP_NAME'),
//                "info@cloture-discount.fr",
//                Configuration::get('PS_SHOP_NAME')
//            );

            if (isset($backLink) && Tools::urlBelongsToShop($backLink)){
                $Helper->addNotification($backLink);
            } else {
                $Helper->addNotification("/");
            }
        }


    }

    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        /** @var GridDefinitionInterface $definition */
        $definition = $params['definition'];

        /** @var FilterCollection $filters */
        $filters = $definition->getFilters();

        /** @var ColumnCollection */
        $columns = $definition->getColumns();

        $columns
            ->addAfter('id_order', (new DataColumn('group'))
                ->setName($this->l('Groupes'))
                ->setOptions(['field' => 'name'])
            );
//        $filters
//            ->add((new Filter('group', TextType::class))
//                      ->setTypeOptions([
//                                           'required' => false,
//                                           'attr'     => [
//                                               'placeholder' => $this->trans('Nom du groupe', [], 'Admin.Actions'),
//                                           ],
//                                       ])
//                      ->setAssociatedColumn('group'));

    }

    public function getCodeNaf()
    {
        $sql = 'SELECT code, description FROM  ps_code_naf';

        return Db::getInstance()->ExecuteS($sql);
    }

    public function escapeForSQL($input)
    {
        // Remplace les apostrophes par un espace pour échapper
        return str_replace("'", "", $input);
    }
}

function logMessage($message)
{
    global $logFilePath;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry  = "[$timestamp] $message\n";

    dump($logFilePath);
    exit;

    file_put_contents($logFilePath, $logEntry, FILE_APPEND | LOCK_EX);
}
