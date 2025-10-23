<?php

class ckc_espacepro extends Module
{
    protected $redirect_after = '/';
    public $error   = [];
    public $success = [];
    public $info    = [];

    protected $tabs = [
        'Professionnels' => [
            'class' => 'AdminEspacePro',
            'active' => 1,
            'parent' => 'AdminParentCustomer',
        ],
        'Fiches et Guides' => [
            'class' => 'AdminGuidesFiches',
            'active' => 1,
            'parent' => 'IMPROVE',
            'icon'   => 'book'
        ],
    ];


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

    /** Installe le module + valeurs par défaut */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        Configuration::updateValue('CKC_EP_SSO_SECRET', bin2hex(random_bytes(32)));
        Configuration::updateValue('CKC_DISPLAY_EP', 0);
        Configuration::updateValue('CKC_FORM_MIN_EP', 0);
        Configuration::updateValue('CKC_RECAPTCHA_SECRET', '');
        Configuration::updateValue('CKC_RECAPTCHA_ENABLED', 0);
        Configuration::updateValue('CKC_GROUP_PRO_ID', 10);
        Configuration::updateValue('PS_CANONICAL_REDIRECT', 0, false, null, 2);
        Configuration::updateValue('CKC_GROUP_PROTICULIER_ID', 15);

        foreach (Shop::getShops(true, null, true) as $idShop) {
            Configuration::updateValue('CKC_PRO_URL_BOUTIQUE', '', false, null, (int)$idShop);
            Configuration::updateValue('CKC_PART_URL_BOUTIQUE', '', false, null, (int)$idShop);
        }

//        include __DIR__ . '/sql/install.php';

        $ok =
            $this->registerHook('displayCustomerAccountForm') &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('actionCustomerLogoutAfter') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionFrontControllerSetVariables') &&
            $this->registerHook('actionOrderGridQueryBuilderModifier') &&
            $this->registerHook('actionAuthentication') &&
            $this->registerHook('actionOrderGridDefinitionModifier') &&
            $this->registerHook('moduleRoutes') &&
            $this->registerHook('displayFavoriteStore') &&
            $this->registerHook('displayHome');

        if($ok) {
            $this->addTabs();
        }

        $this->clear_the_cache();


        return $ok;
    }

    protected function addTabs()
    {
        $defaultParentClass = property_exists($this, 'parentTabClass') ? $this->parentTabClass : 'AdminParentCustomer';
        $parent = version_compare(_PS_VERSION_, '1.7.0', '<') ? 0 : (int)Tab::getIdFromClassName('AdminParentCustomer');

        $defaultParentId = 0;
        if (version_compare(_PS_VERSION_, '1.7.0', '>=') && $defaultParentClass) {
            $defaultParentId = (int)Tab::getIdFromClassName($defaultParentClass) ?: 0;
        }

        foreach ($this->tabs as $name => $tabInfo) {
            if (Tab::getIdFromClassName($tabInfo['class'])) {
                continue;
            }
            $t = $tabInfo;
            $tab             = new Tab();
            $tab->active     = (int)$t['active'];
            $tab->class_name = $t['class'];
            $tab->module     = $this->name;
            $tab->id_parent  = $parent;
            $tab->name       = [];

            if (isset($tabInfo['parent'])) {
                $parentId = (int)Tab::getIdFromClassName($tabInfo['parent']) ?: $defaultParentId;
            } else {
                $parentId = $defaultParentId;
            }
            $tab->id_parent = $parentId;

            $tab->name = [];
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $name;
            }

            if (isset($tabInfo['icon'])) {
                $tab->icon = $tabInfo['icon'];
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


    private function buildSsoUrlForShop(int $targetShopId, int $idCustomer, string $back = 'my-account'): string
    {
        $secret = (string) Configuration::get('CKC_EP_SSO_SECRET');
        $exp = time() + 300;
        $jti = bin2hex(random_bytes(8));
        $sig = hash_hmac('sha256', $idCustomer.'|'.$exp.'|'.$jti, $secret);

        $params = [
            'u'   => $idCustomer,
            'exp' => $exp,
            'jti' => $jti,
            'sig' => $sig,
            'back'=> $back,
            'ts'  => $targetShopId,
        ];

        return $this->context->link->getModuleLink('ckc_espacepro', 'consume', $params, true, null, $targetShopId);
    }

    public function hookActionAuthentication($params)
    {
        // Laisse les ini_set globaux désactivés au début du fichier (supprimés).
        $c = $params['customer'] ?? null;
        if (!$c || !(int)$c->id) return;

        $shopId         = (int)$this->context->shop->id;
        $gidDefault     = (int)($c->id_default_group ?? 0);
        $gidPro         = (int)$this->getProGroupId();
        $gidProticulier = (int)$this->getProticulierGroupId();
        $isProGroup     = in_array($gidDefault, [$gidPro, $gidProticulier], true);

        $targetShopId = 0; $reasonCode = 0;
        if ($shopId === 1 && $isProGroup) { $targetShopId = 2; $reasonCode = 11; } // PART -> PRO
        elseif ($shopId === 2 && !$isProGroup) { $targetShopId = 1; $reasonCode = 12; } // PRO -> PART
        if ($targetShopId === 0) return;

        $back = (string)Tools::getValue('back', 'my-account');
        $email = (string)$c->email;
        $targetLoginUrl = $this->buildAuthUrlForShop($targetShopId, $back, $email, $reasonCode);

        // Logout "cookie-first"
        try {
            if (method_exists($this->context->cookie, 'logout')) {
                $this->context->cookie->logout();
            } else {
                $this->context->cookie->id_customer = null;
                $this->context->cookie->customer_lastname = null;
                $this->context->cookie->customer_firstname = null;
                $this->context->cookie->logged = 0;
                $this->context->cookie->write();
            }
        } catch (\Throwable $e) {
            @file_put_contents(_PS_ROOT_DIR_.'/var/log/ckc_auth_redirect.log',
                '['.date('c')."] cookie logout: ".$e->getMessage().PHP_EOL, FILE_APPEND);
        }

        while (ob_get_level()) { @ob_end_clean(); }
        header('Location: '.$targetLoginUrl, true, 302);
        exit;
    }

    /**
     * Construit l’URL de la page "authentication" du shop cible, avec email pré-rempli
     * et un code raison pour afficher un message côté template/JS.
     *
     * @param int    $targetShopId  1 = PART, 2 = PRO
     * @param string $back          ex: 'my-account' ou un path interne
     * @param string $email         email à pré-remplir
     * @param int    $reasonCode    11 (part->pro) / 12 (pro->part)
     */
    private function buildAuthUrlForShop(int $targetShopId, string $back, string $email, int $reasonCode): string
    {
        $qs = http_build_query([
            'back'        => $back,
            'login_email' => $email,
            'login_error' => $reasonCode,
        ], '', '&', PHP_QUERY_RFC3986);

        $base = $this->context->link->getBaseLink($targetShopId, true);

        return rtrim($base, '/') . '/connexion?' . $qs;
    }



    /** Debug ON si ?ckc_debug=1 ou si config CKC_EP_DEBUG=1 */
    private function isDebug(): bool
    {
        return true;
        return Tools::getValue('ckc_debug') == '1' || (int)Configuration::get('CKC_EP_DEBUG', 0) === 1;
    }

    /** Dump propre + exit (évite le “headers already sent”) */
    private function dd(string $title, array $data = []): void
    {
        while (ob_get_level() > 0) { @ob_end_clean(); }
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><style>
            body{font-family:ui-monospace,monospace;background:#0b1020;color:#e6edf3;padding:20px}
            h1{font-size:18px;margin:0 0 12px}
            pre{background:#0f172a;padding:12px;border-radius:8px;overflow:auto}
            b{color:#7dd3fc}
          </style>';
        echo '<h1>CKC EP DEBUG · ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><pre>';
        foreach ($data as $k => $v) {
            echo '<b>' . htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') . "</b>\n";
            echo htmlspecialchars(print_r($v, true), ENT_QUOTES, 'UTF-8') . "\n\n";
        }
        echo '</pre>';
        exit;
    }

    private function buildSsoUrl(string $base, int $idCustomer, string $back = ''): string
    {
        $secret = (string)Configuration::get('CKC_EP_SSO_SECRET');
        $exp = time() + 300; // 5 min
        $jti = bin2hex(random_bytes(8)); // anti-rejeu simple
        $sig = hash_hmac('sha256', $idCustomer.'|'.$exp.'|'.$jti, $secret);

        $base = rtrim($base, '/');
        $qs = http_build_query([
            'u'   => $idCustomer,
            'exp' => $exp,
            'jti' => $jti,
            'sig' => $sig,
            'back'=> $back,
        ]);
        return $base.'/pro-sso-consume?'.$qs;
    }


    /** Désinstalle + nettoie la config */
    public function uninstall()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminEspacePro');
        if ($id_tab) {
            (new Tab($id_tab))->delete();
        }

        foreach ([
                     'CKC_DISPLAY_EP','CKC_FORM_MIN_EP','CKC_RECAPTCHA_SECRET','CKC_RECAPTCHA_ENABLED',
                     'CKC_GROUP_PRO_ID','CKC_GROUP_PROTICULIER_ID','CKC_PRO_URL_BOUTIQUE','CKC_PART_URL_BOUTIQUE'
                 ] as $k) {
            Configuration::deleteByName($k);
        }

        $this->clear_the_cache();
        return parent::uninstall();
    }

    /** Active le module + purge caches */
    public function enable($force_all = false)
    {
        $res = parent::enable($force_all);
        $this->clear_the_cache();
        return $res;
    }

    /** Désactive le module + purge caches */
    public function disable($force_all = false)
    {
        $this->clear_the_cache();
        return parent::disable($force_all);
    }

    /** Enfile les JS/CSS côté front */
    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerJavascript(
            'ckc-espacepro-auth',
            'modules/' . $this->name . '/views/js/authentification.js',
            ['position' => 'bottom', 'priority' => 1000]
        );

        $this->context->controller->registerJavascript(
            'ckc-espacepro-home-login',
            'modules/' . $this->name . '/views/js/pro-home-login.js',
            ['position' => 'bottom', 'priority' => 1001]
        );

        if (strpos($_SERVER['REQUEST_URI'] ?? '', 'avantages-pro') !== false) {
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

        // CSS pour la bannière de promotion
        $this->context->controller->registerStylesheet(
            'ckc-espace-pro-promotion',
            'modules/' . $this->name . '/views/css/front/promotion.css',
            ['position' => 'head', 'priority' => 1000]
        );
    }

    /**
     * Hook pour afficher une promotion spéciale aux utilisateurs professionnels
     */
    public function hookDisplayPromotion($params)
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->context->customer || !$this->context->customer->isLogged()) {
            return '';
        }

        $customer = $this->context->customer;
        $groups = (array)$customer->getGroups();

        $gidPro = $this->getProGroupId();
        $gidProticulier = $this->getProticulierGroupId();

        // Vérifier si l'utilisateur est professionnel ou proticulier
        $isPro = in_array($gidPro, $groups, true);
        $isProticulier = in_array($gidProticulier, $groups, true);

        if (!$isPro && !$isProticulier) {
            return '';
        }

        // Déterminer le type de promotion selon le statut
        $promoType = $isPro ? 'pro' : 'proticulier';
        $promoTitle = $isPro ? 'Offre Spéciale Professionnels' : 'Avantage Proticulier';
        $promoMessage = $isPro
            ? 'Bénéficiez de -15% sur votre prochaine commande avec le code PROPRO15'
            : 'Profitez de -10% sur une sélection de produits avec le code PROT10';
        $promoCode = $isPro ? 'PROPRO15' : 'PROT10';
        $promoDiscount = $isPro ? '15%' : '10%';

        // Assigner les variables au template
        $this->context->smarty->assign([
            'promo_type' => $promoType,
            'promo_title' => $promoTitle,
            'promo_message' => $promoMessage,
            'promo_code' => $promoCode,
            'promo_discount' => $promoDiscount,
            'customer_firstname' => $customer->firstname,
            'is_pro_environment' => $this->isProEnvironment()
        ]);

        return $this->display(__FILE__, 'views/templates/hook/promotion.tpl');
    }

    /** Variables Smarty globales */
    public function hookActionFrontControllerSetVariables($params)
    {
        $loginError = (int)Tools::getValue('login_error', 0);
        $loginEmail = (string)Tools::getValue('login_email', '');

        $this->context->smarty->assign([
            'login_error' => $loginError,
            'login_email' => Tools::safeOutput($loginEmail),
        ]);

        $this->injectGlobals();
    }

    /** Accueil PRO : bloc login si déconnecté */
    public function hookDisplayHome($params)
    {
        if (!$this->isProEnvironment()) return '';
        if ($this->context->customer && $this->context->customer->isLogged()) return '';

        $this->context->smarty->assign([
            'login_action' => $this->context->link->getPageLink('authentication', true),
            'back' => Tools::safeOutput($_SERVER['REQUEST_URI'] ?? '/'),
        ]);
        return $this->display(__FILE__, 'views/templates/hook/pro-home-login.tpl');
    }

    /** Logout : purge quelques caches */
    public function hookActionCustomerLogoutAfter($params)
    {
        $this->clear_the_cache();
    }

    /** BO module : assets */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('controller') === 'AdminModules'
            && Tools::getValue('configure') === $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/bo_espacepro.css');
            $this->context->controller->addJS($this->_path . 'views/js/bo_espacepro.js');
        }
    }

    /** Formulaire d’inscription (version EP si ?ep=1) */
    public function hookDisplayCustomerAccountForm($params)
    {
        if ((int)Configuration::get('CAPTCHA_ENABLE_ACCOUNT') === 1) {
            $themes = [0 => 'light', 1 => 'dark'];
            $this->context->smarty->assign([
                'captchaVersion' => Configuration::get('CAPTCHA_VERSION'),
                'publicKey' => Configuration::get('CAPTCHA_PUBLIC_KEY'),
                'captchaforcelang' => Configuration::get('CAPTCHA_FORCE_LANG'),
                'captchatheme' => $themes[(int)Configuration::get('CAPTCHA_THEME')],
            ]);
        }

        if ((int)Tools::getValue('ep', 0) === 1) {
            $groups = Group::getGroups($this->context->language->id);
            unset($groups[0], $groups[1], $groups[2]);

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

    /** Création client : sur shop PRO => groupe “Proticulier” direct */
    public function hookActionCustomerAccountAdd($params)
    {
        $helper = new HelperEspacePro();
        $backLink = Tools::getValue('back');
        $shopId = (int)$this->context->shop->id;
        $shop_url = $this->context->link->getBaseLink($shopId, true);
        $customer = $params['newCustomer'] ?? null;

        if (!Validate::isLoadedObject($customer)) return;

        $isProShop = ($shopId === 2) || $this->isProEnvironment();
        $gidProticulier = (int)$this->getProticulierGroupId();

        if ((int)Configuration::get('CKC_RECAPTCHA_ENABLED') === 1) {
            $token = Tools::getValue('g-recaptcha-response');
            $secret = (string)Configuration::get('CKC_RECAPTCHA_SECRET');
            if (empty($token) || empty($secret)) {
                $helper->error[] = 'Vérification reCAPTCHA indisponible.';
                $helper->addNotification('/');
                return;
            }
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

        $host = (string)$_SERVER['SERVER_NAME'];
        $mode_local = (str_contains($host, '.local') || str_contains($host, 'vianney'));

        if ($isProShop) {
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
                        $helper->error[] = $helper->getUploadErrorMessage($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE);
                    }
                }
            }

            $row += [
                'pays' => $this->escapeForSQL((string)$customer_country),
                'tva' => $this->escapeForSQL((string)$customer_tva),
                'adresse' => (int)Configuration::get('CKC_FORM_MIN_EP') === 0 ? $this->escapeForSQL($customer_address) : '...',
                'adresse2' => $this->escapeForSQL($customer_address2),
                'cp' => $this->escapeForSQL($customer_cp),
                'tel' => $this->escapeForSQL($customer_tel),
                'ville' => (int)Configuration::get('CKC_FORM_MIN_EP') === 0 ? $this->escapeForSQL($customer_city) : '...',
                'active' => 1,
                'id_default_group' => $gidProticulier,
                'naf' => $naf,
                'siret' => $siret,
            ];

            $customer->active = true;
            $customer->id_default_group = $gidProticulier;
            if ($siret !== '') $customer->siret = $siret;
            $customer->update();
            $customer->addGroups([$gidProticulier]);

            $hasAddressData = (bool)strlen(trim($customer_address));
            if ($hasAddressData) {
                $address = new Address();
                $address->alias = 'Adresse Professionnelle';
                $address->active = (bool)$customer->active;
                $address->firstname = $customer->firstname;
                $address->lastname = $customer->lastname;
                $address->company = $customer_company;
                $address->address1 = trim($customer_address);
                $address->address2 = $customer_address2;
                $address->phone = $customer_tel;
                $address->postcode = $customer_cp;
                $address->city = trim($customer_city);
                $address->vat_number = $vat_number;
                $address->id_customer = (int)$customer->id;
                $address->id_country = $customer_country ?: (int)Configuration::get('PS_COUNTRY_DEFAULT');
                $address->save();
            }

            Db::getInstance()->update('customer', $row, 'id_customer=' . (int)$customer->id, true, Db::INSERT_IGNORE);

            $helper->success[] = "Votre compte a bien été créé en tant que Proticulier. Vous pouvez déjà commander au tarif particulier. Notre équipe pourra ensuite basculer votre compte en PRO si éligible.";

            if (!$mode_local) {
                $data = [
                    '{firstname}' => $customer->firstname,
                    '{lastname}' => $customer->lastname,
                    '{company}' => $customer_company,
                    '{phone}' => $customer_tel,
                    '{naf}' => $naf,
                    '{tva}' => $vat_number,
                    '{adresse}' => $customer_address ?: '...',
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
                        "Nouveau compte Proticulier créé",
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
                    "Bienvenue ! Votre compte Proticulier est actif",
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

        $helper->success[] = "Votre compte a bien été créé et vous êtes maintenant connecté.";
        $helper->addNotification(
            (isset($backLink) && Tools::urlBelongsToShop($backLink)) ? $backLink : '/'
        );
    }

    /** BO commandes : jointure groupe client */
    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        $qb = $params['search_query_builder'];
        $searchCriteria = $params['search_criteria'];

        $qb->addSelect('pl.`name` AS group_name')
            ->innerJoin('cu',  _DB_PREFIX_.'group_lang', 'pl', 'pl.id_group = cu.id_default_group AND pl.id_lang = ' . (int)$this->context->language->id);

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ($filterName === 'groupe' && $filterValue) {
                $qb->andWhere('pl.`name` = :groupName')->setParameter('groupName', $filterValue);
            }
        }
    }

    /** BO commandes : ajoute la colonne */
    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        $definition = $params['definition'];
        $columns = $definition->getColumns();
        $columns->addAfter('id_order',
            (new \PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn('group_name'))
                ->setName($this->l('Groupe client'))
                ->setOptions(['field' => 'group_name'])
        );
    }

    /** Bloc “magasin favori” */
    public function hookDisplayFavoriteStore($params)
    {
        $sql = 'SELECT sp.`id_cms_detail`
            FROM `' . _DB_PREFIX_ . 'ckcstorespresentation` sp
            inner join `' . _DB_PREFIX_ . 'ckc_favorite_stores` fs
                on fs.`id_favorite_store` = sp.`id_ckcstorespresentation`
            AND fs.`id_customer` = ' . (int)$this->context->customer->id;

        $favoriteStoreId = Db::getInstance()->getRow($sql);
        if (!$favoriteStoreId) $favoriteStoreId = 11;

        $this->context->smarty->assign(compact('favoriteStoreId'));
        $this->context->smarty->assign('sidebar', $params['sidebar']);
        return $this->fetch('module:ckc_espacepro/views/templates/front/favorite_store.tpl');
    }

    /** Routes front du module */
    public function hookModuleRoutes($params)
    {
        return [
            'benefits_pro_route' => [
                'rule' => 'avantages-pro',
                'controller' => 'benefitsespacepro',
                'keywords' => [],
                'params' => [
                    'module' => $this->name,
                    'fc' => 'module',
                    'controller' => 'benefitsespacepro'
                ]
            ],
            'ckc_ep_whoami' => [
                'rule' => 'pro-whoami',
                'controller' => 'whoami',
                'keywords' => [],
                'params' => ['module' => $this->name, 'fc' => 'module'],
            ],
            'ckc_ep_consume' => [
                'rule'       => 'pro-sso-consume',
                'controller' => 'consume',
                'keywords'   => [],
                'params'     => ['module' => $this->name, 'fc' => 'module']
            ],
            'products_documents' => [
                'rule' => 'guides-et-fiches-techniques-produits',
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

    /** Construit le payload “pro” pour Smarty */
    public function assignProVars(): array
    {
        $ctx = $this->context;
        $cust = $ctx->customer;

        $isLogged = $cust ? (bool)$cust->isLogged() : false;
        $userId = (int)($cust->id ?? 0);
        $groups = $isLogged ? (array)$cust->getGroups() : [];

        $isProEnv = $this->isProEnvironment();
        $frontSuffix = $isProEnv ? '-pro' : '';

        $gidPro = $this->getProGroupId();
        $gidProticulier = $this->getProticulierGroupId();

        $inPro = $isLogged && in_array($gidPro, $groups, true);
        $inProticulier = $isLogged && in_array($gidProticulier, $groups, true);
        $isVisitor = !$isLogged;
        $isParticulier = $isLogged && !$inPro && !$inProticulier;

        $status = $isVisitor ? 'visitor' : ($inPro ? 'pro' : ($inProticulier ? 'proticulier' : 'particulier'));

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

        $pro = [
            'env' => (int)$isProEnv,
            'suffix' => $frontSuffix,
            'url_domain' => (string)Configuration::get('CKC_PRO_URL_BOUTIQUE', null, null, (int)$this->context->shop->id),
            'user' => [
                'id' => $userId,
                'is_logged' => (int)$isLogged,
                'is_pro' => (int)$inPro,
                'is_proticulier' => (int)$inProticulier,
                'is_particulier' => (int)$isParticulier,
                'is_visitor' => (int)$isVisitor,
                'status' => $status,
                'info' => $customerInfo,
            ],
        ];

        $ctx->smarty->assign('pro', $pro);
        return $pro;
    }

    /** Injecte les variables globales Smarty */
    private function injectGlobals(bool $bo = false): void
    {
        $pro = $this->assignProVars();

        $vars = [
            'pro' => $pro,
            'env_pro' => (int)$pro['env'],
            'pro_suffix' => (string)$pro['suffix'],
            'is_pro' => (int)$pro['user']['is_pro'],
            'is_proticulier' => (int)$pro['user']['is_proticulier'],
            'is_particulier' => (int)$pro['user']['is_particulier'],
            'is_visitor' => (int)$pro['user']['is_visitor'],
            'pro_login_error' => (string)Tools::getValue('pro_login_error', ''),
        ];

        $this->context->smarty->assign($vars);
    }

    /** Met la France en tête de liste des pays */
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

    /** Référentiel NAF */
    public function getCodeNaf(): array
    {
        return (array)Db::getInstance()->executeS('SELECT code, description FROM `' . _DB_PREFIX_ . 'code_naf`');
    }

    /** Petitéch : retire les quotes pour SQL brut */
    public function escapeForSQL($input)
    {
        return str_replace("'", '', (string)$input);
    }

    /** Page config BO */
    public function getContent()
    {
        if (Tools::isSubmit('submitCkc_ep')) {
            $this->postProcess();
        }

        $this->context->smarty->assign('token', Tools::getAdminTokenLite('AdminModules'));
        $this->context->smarty->assign('configuration', $this->renderForm());
        return $this->context->smarty->fetch($this->local_path . 'views/templates/config.tpl');
    }

    /** Construit le formulaire BO */
    public function renderForm()
    {
        return $this->createHelperForm('submitCkc_ep', $this->getConfigFormValues(), $this->getConfigForm());
    }

    /** HelperForm prêt-à-l’emploi */
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

    /** Schéma du formulaire BO */
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
                        'type' => 'text',
                        'label' => "URL Boutique Particulier (redirection)",
                        'name' => 'CKC_PART_URL_BOUTIQUE',
                        'class' => 'ckc-ep-url',
                        'placeholder' => 'https://www.exemple.fr/',
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
                        'type' => 'select',
                        'label' => 'Groupe Professionnel',
                        'name' => 'CKC_GROUP_PRO_ID',
                        'options' => [
                            'query' => array_values(Group::getGroups($this->context->language->id)),
                            'id' => 'id_group',
                            'name' => 'name',
                        ],
                        'desc' => 'Sélectionnez le groupe client utilisé pour les comptes professionnels.',
                    ],
                    [
                        'type' => 'select',
                        'label' => 'Groupe Proticulier',
                        'name' => 'CKC_GROUP_PROTICULIER_ID',
                        'options' => [
                            'query' => array_values(Group::getGroups($this->context->language->id)),
                            'id' => 'id_group',
                            'name' => 'name',
                        ],
                        'desc' => 'Sélectionnez le groupe client pour les “proticulier”.',
                    ],
                    [
                        'type'  => 'text',
                        'label' => 'Clé secrète SSO (PRO ⇄ PART)',
                        'name'  => 'CKC_EP_SSO_SECRET',
                        'desc'  => 'Utilisée pour signer les URLs de connexion /pro/.',
                    ],
                ],
                'submit' => [
                    'title' => 'Enregistrer',
                    'class' => 'pull-right',
                ],
            ],
        ];
    }

    /** Valeurs du formulaire BO */
    public function getConfigFormValues()
    {
        return [
            'CKC_DISPLAY_EP' => (int)Configuration::get('CKC_DISPLAY_EP', 0),
            'CKC_FORM_MIN_EP' => (int)Configuration::get('CKC_FORM_MIN_EP', 0),
            'CKC_PRO_URL_BOUTIQUE' => Configuration::get('CKC_PRO_URL_BOUTIQUE', null, null, (int)$this->context->shop->id),
            'CKC_PART_URL_BOUTIQUE' => Configuration::get('CKC_PART_URL_BOUTIQUE', null, null, (int)$this->context->shop->id),
            'CKC_RECAPTCHA_ENABLED' => (int)Configuration::get('CKC_RECAPTCHA_ENABLED', 0),
            'CKC_RECAPTCHA_SECRET' => (string)Configuration::get('CKC_RECAPTCHA_SECRET', ''),
            'CKC_GROUP_PRO_ID' => (int)Configuration::get('CKC_GROUP_PRO_ID', 10),
            'CKC_EP_SSO_SECRET' => (string)Configuration::get('CKC_EP_SSO_SECRET', ''),
            'CKC_GROUP_PROTICULIER_ID' => (int)Configuration::get('CKC_GROUP_PROTICULIER_ID', 15),
        ];
    }

    /** Sauvegarde la config (multi-boutique OK) */
    public function postProcess()
    {
        if (!Tools::isSubmit('submitCkc_ep')) {
            return;
        }

        $urlPro = trim((string)Tools::getValue('CKC_PRO_URL_BOUTIQUE', ''));
        $urlPart = trim((string)Tools::getValue('CKC_PART_URL_BOUTIQUE', ''));
        $displayEp = (int)Tools::getValue('CKC_DISPLAY_EP', 0);
        $formMinEp = (int)Tools::getValue('CKC_FORM_MIN_EP', 0);
        $recaptchaOn = (int)Tools::getValue('CKC_RECAPTCHA_ENABLED', 0);
        $recaptchaKey = (string)Tools::getValue('CKC_RECAPTCHA_SECRET', '');
        $gidPro = (int)Tools::getValue('CKC_GROUP_PRO_ID', 10);
        $gidProticulier = (int)Tools::getValue('CKC_GROUP_PROTICULIER_ID', 15);

        $applyForShop = function (int $idShop) use ($urlPro, $urlPart, $displayEp, $formMinEp, $recaptchaOn, $recaptchaKey, $gidPro, $gidProticulier) {
            Configuration::updateValue('CKC_PRO_URL_BOUTIQUE', $urlPro, false, null, $idShop);
            Configuration::updateValue('CKC_PART_URL_BOUTIQUE', $urlPart, false, null, $idShop);
            Configuration::updateValue('CKC_DISPLAY_EP', $displayEp, false, null, $idShop);
            Configuration::updateValue('CKC_FORM_MIN_EP', $formMinEp, false, null, $idShop);
            Configuration::updateValue('CKC_RECAPTCHA_ENABLED', $recaptchaOn, false, null, $idShop);
            Configuration::updateValue('CKC_RECAPTCHA_SECRET', $recaptchaKey, false, null, $idShop);
            Configuration::updateValue('CKC_GROUP_PRO_ID', $gidPro, false, null, $idShop);
            Configuration::updateValue('CKC_GROUP_PROTICULIER_ID', $gidProticulier, false, null, $idShop);
        };

        $epSsoSecret = (string)Tools::getValue('CKC_EP_SSO_SECRET', '');
        Configuration::updateValue('CKC_EP_SSO_SECRET', $epSsoSecret, false, null, (int)$this->context->shop->id);

        switch (Shop::getContext()) {
            case Shop::CONTEXT_SHOP:
                $applyForShop((int)Shop::getContextShopID());
                break;
            case Shop::CONTEXT_GROUP:
                foreach (Shop::getShops(true, (int)Shop::getContextShopGroupID(), true) as $idShop) {
                    $applyForShop((int)$idShop);
                }
                break;
            case Shop::CONTEXT_ALL:
            default:
                foreach (Shop::getShops(true, null, true) as $idShop) {
                    $applyForShop((int)$idShop);
                }
                break;
        }

        $this->clear_the_cache();
    }

    /** Purge caches PS */
    public function clear_the_cache()
    {
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
        Cache::clean('*');
    }

    /** Infos PRO étendues pour un client */
    private function getInfosProCustomer(int $id): array
    {
        if ($id <= 0) return [];
        $gidPro = (int)$this->getProGroupId();

        $sql = 'SELECT c.`tva`, c.`adresse`, c.`adresse2`, c.`cp`, c.`ville`, c.`tel`, c.`fileUpload`, c.`rib`, c.`naf`, c.`siret`
            FROM `' . _DB_PREFIX_ . 'customer` c
            WHERE c.`id_default_group` = ' . (int)$gidPro . '
              AND c.`id_customer` = ' . (int)$id;

        return (array)Db::getInstance()->getRow($sql);
    }

    /** ID groupe PRO (fallback) */
    private function getProGroupId(): int
    {
        $idShop = (int)$this->context->shop->id;
        $v = (int)Configuration::get('CKC_GROUP_PRO_ID', null, null, $idShop);
        return $v > 0 ? $v : 10;
    }

    /** ID groupe Proticulier (fallback) */
    private function getProticulierGroupId(): int
    {
        $idShop = (int)$this->context->shop->id;
        $v = (int)Configuration::get('CKC_GROUP_PROTICULIER_ID', null, null, $idShop);
        return $v > 0 ? $v : 15;
    }

    /** Vrai si on est côté PRO */
    private function isProEnvironment(): bool
    {
        $idShop = (int)$this->context->shop->id;
        if ($idShop === 2) return true;

        $proUrl = (string)Configuration::get('CKC_PRO_URL_BOUTIQUE', null, null, $idShop);
        if ($proUrl !== '') {
            $currentUrl = Tools::getShopDomainSsl(true);
            return stripos($currentUrl, $proUrl) !== false;
        }
        return false;
    }

    /** Bases URLs (prod/dev) */
    private function getEnvBases(): array
    {
        $host = Tools::getHttpHost();

        $pairs = [
            'www.cloture-discount.fr'   => ['pro' => 'https://pro.cloture-discount.fr/',  'part' => 'https://www.cloture-discount.fr/'],
            'cloture-discount.fr'       => ['pro' => 'https://pro.cloture-discount.fr/',  'part' => 'https://www.cloture-discount.fr/'],
            'pro.cloture-discount.fr'   => ['pro' => 'https://pro.cloture-discount.fr/',  'part' => 'https://www.cloture-discount.fr/'],
            'theo.cloture-discount.fr'  => ['pro' => 'https://theopro.cloture-discount.fr/', 'part' => 'https://theo.cloture-discount.fr/'],
            'theopro.cloture-discount.fr'=> ['pro' => 'https://theopro.cloture-discount.fr/','part' => 'https://theo.cloture-discount.fr/'],
        ];

        if (isset($pairs[$host])) {
            return ['pro_base' => $pairs[$host]['pro'], 'part_base' => $pairs[$host]['part']];
        }

        return [
            'pro_base'  => 'https://' . $host . '/pro/',
            'part_base' => 'https://' . $host . '/',
        ];
    }

    public function getProHome(): string { return $this->getEnvBases()['pro_base']; }
    public function getPartHome(): string { return $this->getEnvBases()['part_base']; }
}
