<?php
if (!class_exists('HelperEspacePro', false) && file_exists(__DIR__.'/classes/HelperEspacePro.php')) {
    require_once __DIR__.'/classes/HelperEspacePro.php';
}
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShopBundle\Form\Admin\Type\CustomContentType;
use Symfony\Component\Form\FormBuilderInterface;

class ckc_espacepro extends Module
{
    protected $redirect_after = '/';
    public $error   = [];
    public $success = [];
    public $info    = [];
    private static ?array $codeNafCache = null;
    private static ?array $proGroupIdsCache = null;

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
            $this->registerHook('actionSubmitAccountBefore') &&
            $this->registerHook('actionPresentCart') &&
            $this->registerHook('actionCustomerFormBuilderModifier') &&

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

            if($tab->class_name == 'AdminGuidesFiches'){
                $tab->icon = 'icon-AdminGuidesFiches';
            } else if (isset($tabInfo['icon'])) {
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
    public function hookActionSubmitAccountBefore($params)
    {
        foreach (['kbis', 'rib'] as $fileKey) {
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $newFileName = $this->uploadProFile($_FILES[$fileKey], $fileKey);
                if ($newFileName) {
                    // stocke temporairement dans le cookie le nom du fichier
                    $this->context->cookie->__set($fileKey . '_tmp', $newFileName);
                }
            }
        }
        return true;
    }
    public function hookActionAuthentication($params)
    {
        $ctrl = $this->context->controller ? strtolower($this->context->controller->php_self) : '';
        if ($ctrl !== 'authentication') {
            return;
        }

        // Laisse les ini_set globaux désactivés au début du fichier (supprimés).
        $c = $params['customer'] ?? null;
        if (!$c || !(int)$c->id) return;

        $shopId         = (int)$this->context->shop->id;
        $gidDefault     = (int)($c->id_default_group ?? 0);
        $gidProList = $this->getProGroupIds(); // array<int>
        $isProGroup = in_array($gidDefault, $gidProList, true);
        $gidProticulier = (int)$this->getProticulierGroupId();

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
            'pro_reason'  => $reasonCode,
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
        $this->registerCeThemeLayoutFilter();

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

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, 'avantages-pro') !== false || strpos($uri, 'benefitsespacepro') !== false) {
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
     * CKC Vianney — CE Full Width charge son layout module (hooks 36/37 FR only).
     * On pointe vers le même fichier dans le thème, sans modifier Creative Elements.
     */
    private function registerCeThemeLayoutFilter(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        if (!Module::isInstalled('creativeelements') || !Module::isEnabled('creativeelements')) {
            return;
        }
        Module::getInstanceByName('creativeelements');
        if (!function_exists('CE\\add_filter')) {
            return;
        }
        \CE\add_filter('template_include', [$this, 'filterCeTemplateInclude'], 20);
        $done = true;
    }

    public function filterCeTemplateInclude($template)
    {
        if (!is_string($template) || strpos($template, 'layout-header-footer.tpl') === false) {
            return $template;
        }
        $themeFile = _PS_THEME_DIR_ . 'modules/creativeelements/views/templates/front/theme/layouts/layout-header-footer.tpl';

        return is_file($themeFile) ? $themeFile : $template;
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

        $gidProList = $this->getProGroupIds();
        $isPro = count(array_intersect($gidProList, $groups)) > 0;
        $gidProticulier = $this->getProticulierGroupId();

        // Vérifier si l'utilisateur est professionnel ou proticulier
        $isPro = in_array($gidProList, $groups, true);
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
        $this->registerCeThemeLayoutFilter();
        $loginEmail = (string)Tools::getValue('login_email', '');

        // ne plus alimenter login_error
        $this->context->smarty->assign([
            'login_email' => Tools::safeOutput($loginEmail),
            'pro_reason' => (int)Tools::getValue('pro_reason', 0),
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
        $this->context->controller->addCSS($this->_path . 'views/css/menuTabIcon.css');
    }

    /** Formulaire d’inscription */
    public function hookDisplayCustomerAccountForm($params)
    {

        if ((int)Configuration::get('CAPTCHA_ENABLE_ACCOUNT') === 1) {
            $themes = [0 => 'light', 1 => 'dark'];
            $captchaVersion = Configuration::get('CAPTCHA_VERSION');
            $publicKey      = Configuration::get('CAPTCHA_PUBLIC_KEY');
            $forceLang      = Configuration::get('CAPTCHA_FORCE_LANG');
            $captchaTheme   = $themes[(int)Configuration::get('CAPTCHA_THEME')];
            $this->context->smarty->assign([
                'captchaVersion'    => $captchaVersion,
                'publicKey'         => $publicKey,
                'captchaforcelang'  => $forceLang,
                'captchatheme'      => $captchaTheme,
            ]);
        }

        $isProShop = ((int)$this->context->shop->id === 2) || $this->isProEnvironment();



        if ($isProShop) {

            $groups = Group::getGroups($this->context->language->id);
            unset($groups[0], $groups[1], $groups[2]);

            $countries = Country::getCountries($this->context->language->id, true);
            $this->putFranceFirst($countries);

            $customer = $this->context->customer;
            $isIdentityPage = (strpos($_SERVER['REQUEST_URI'], 'identity') !== false) || (strpos($_SERVER['REQUEST_URI'], 'identite') !== false);

            if($isIdentityPage){
                $customerInfo = Db::getInstance()->executeS('SELECT c.naf FROM `'._DB_PREFIX_.'customer` c
                WHERE c.id_customer = '.(int)$customer->id);
                if(!empty($customerInfo[0])){
                    $customer->ape = $customerInfo[0]['naf'];
                }
            }

            $this->context->smarty->assign([
                'customerGroups' => $groups,
                'countries' => $countries,
                'code_naf' => $this->getCodeNaf(),
                'customer' => $customer,
                'showAddressFields' => !$isIdentityPage,
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
        $mode_local = (str_contains($host, '.local'));


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
            $formMinEp = (int)Configuration::get('CKC_FORM_MIN_EP');

            if ($formMinEp === 0) {
                $row['fileUpload'] = $this->context->cookie->__get('kbis_tmp') ?? null;
                $row['rib'] = $this->context->cookie->__get('rib_tmp') ?? null;

// Nettoyage des cookies après usage
                unset($this->context->cookie->kbis_tmp, $this->context->cookie->rib_tmp);
            }

            $row += [
                'pays' => $this->escapeForSQL((string)$customer_country),
                'tva' => $this->escapeForSQL((string)$customer_tva),
                'adresse' => $formMinEp === 0 ? $this->escapeForSQL($customer_address) : '...',
                'adresse2' => $this->escapeForSQL($customer_address2),
                'cp' => $this->escapeForSQL($customer_cp),
                'tel' => $this->escapeForSQL($customer_tel),
                'ville' => $formMinEp === 0 ? $this->escapeForSQL($customer_city) : '...',
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

            $helper->success[] = "Nous étudions votre demande pour vous donner accès aux tarifs et avantages Professionnels, et vous tiendrons informé dès que votre compte sera validé.";

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

    protected function uploadProFile(array $file, string $prefix = '')
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($file['type'], $allowed, true)) {
            return null;
        }

        $ext = Tools::strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid($prefix . '-') . '.' . $ext;
        $target = _PS_UPLOAD_DIR_ . 'pro_docs/';

        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $path = $target . $filename;
        if (move_uploaded_file($file['tmp_name'], $path)) {
            return $filename;
        }

        return null;
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
                on fs.`id_store` = sp.`id_multiboutique`
            WHERE fs.`id_customer` = ' . (int)$this->context->customer->id;

        $favoriteStoreId = Db::getInstance()->getRow($sql);

        if (!$favoriteStoreId) $favoriteStoreId = 0;

        $this->context->smarty->assign(compact('favoriteStoreId'));
        $this->context->smarty->assign('sidebar', $params['sidebar']);
        return $this->fetch('module:ckc_espacepro/views/templates/front/favorite_store.tpl');
    }

    /** Routes front du module */
    public function hookModuleRoutes($params)
    {
        return [
            'module-ckc_espacepro-benefitsespacepro' => [
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
            'lp_pro_rendez_vous' => [
                'rule' => 'lp-pro-rendez-vous',
                'controller' => 'lprendezvous',
                'keywords' => [],
                'params' => [
                    'module' => $this->name,
                    'fc' => 'module',
                    'controller' => 'lprendezvous'
                ]
            ],
            'lp_pro_rendez_vous_merci' => [
                'rule' => 'lp-pro-rendez-vous/remerciements',
                'controller' => 'lprendezvous',
                'keywords' => [],
                'params' => [
                    'module' => $this->name,
                    'fc' => 'module',
                    'controller' => 'lprendezvous',
                    'remerciements' => '1'
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

        // Récupère un tableau complet de groupes PRO
        $gidProList = $this->getProGroupIds();
        $gidProticulier = (int)$this->getProticulierGroupId();

        // Teste si le client appartient à un des groupes PRO
        $inPro = $isLogged && count(array_intersect($gidProList, $groups)) > 0;
        $inProticulier = $isLogged && in_array($gidProticulier, $groups, true);
        $isVisitor = !$isLogged;
        $isParticulier = $isLogged && !$inPro && !$inProticulier;

        $status = $isVisitor
            ? 'visitor'
            : ($inPro
                ? 'pro'
                : ($inProticulier
                    ? 'proticulier'
                    : 'particulier'));

        $customerInfo = [
            'id' => $userId,
            'siret' => null,
            'tva' => 0,
            'kbis' => null,
            'rib' => null,
        ];

        // On récupère aussi les infos PRO si connecté
        if ($userId > 0 && ($inPro || $inProticulier)) {
            $infos = $this->getInfosProCustomer($userId);
            if (!empty($infos)) {
                $customerInfo['siret'] = $infos['siret'] ?? null;
                $customerInfo['tva'] = !empty($infos['tva']) ? 1 : 0;

                if (!empty($infos['fileUpload'])) {
                    $ext = Tools::strtolower(pathinfo($infos['fileUpload'], PATHINFO_EXTENSION));
                    $customerInfo['kbis'] = [
                        'file' => $infos['fileUpload'],
                        'ext'  => $ext,
                    ];
                }

                if (!empty($infos['rib'])) {
                    $ext = Tools::strtolower(pathinfo($infos['rib'], PATHINFO_EXTENSION));
                    $customerInfo['rib'] = [
                        'file' => $infos['rib'],
                        'ext'  => $ext,
                    ];
                }
            }
        }

        $iso = strtolower((string) ($this->context->language->iso_code ?? ''));
        $pro = [
            'env' => (int)$isProEnv,
            'suffix' => $frontSuffix,
            'lang_iso' => $iso,
            'is_en' => (int) (strpos($iso, 'en') === 0),
            'url_domain' => (string)Configuration::get(
                'CKC_PRO_URL_BOUTIQUE',
                null,
                null,
                (int)$this->context->shop->id
            ),
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
        if (self::$codeNafCache === null) {
            self::$codeNafCache = (array)Db::getInstance()->executeS(
                'SELECT code, description FROM `' . _DB_PREFIX_ . 'code_naf`'
            );
        }
        return self::$codeNafCache;
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
                        'type'     => 'select',
                        'label'    => 'Groupes Professionnels',
                        // IMPORTANT : name avec [] pour multiple
                        'name'     => 'CKC_GROUP_PRO_IDS[]',
                        // Force un id propre (sans crochets)
                        'id'       => 'CKC_GROUP_PRO_IDS',
                        'class'    => 'fixed-width-xl js-ckc-pro-groups',
                        'multiple' => true,
                        // Donne une hauteur native via <select size="…">
                        'size'     => 10, // ~200px selon le thème
                        'options'  => [
                            'query' => array_values(Group::getGroups($this->context->language->id)),
                            'id'    => 'id_group',
                            'name'  => 'name',
                        ],
                        'desc'     => 'Sélectionnez tous les groupes clients considérés comme professionnels (Professionnel, Red, Négoce, Pro Plus...).',
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

        $shopId = (int)$this->context->shop->id;

        // Récupération sûre + fallback [] si vide/mal formé
        $raw = Configuration::get('CKC_GROUP_PRO_IDS', null, null, $shopId);
        $groupProIds = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $groupProIds = array_map('intval', $decoded);
            } else {
                // au cas où l’ancienne valeur soit un CSV
                $groupProIds = array_filter(array_map('intval', array_map('trim', explode(',', $raw))));
            }
        }

        return [
            'CKC_DISPLAY_EP' => (int)Configuration::get('CKC_DISPLAY_EP', 0),
            'CKC_FORM_MIN_EP' => (int)Configuration::get('CKC_FORM_MIN_EP', 0),
            'CKC_GROUP_PRO_IDS[]'       => $groupProIds,
            'CKC_PRO_URL_BOUTIQUE' => Configuration::get('CKC_PRO_URL_BOUTIQUE', null, null, (int)$this->context->shop->id),
            'CKC_PART_URL_BOUTIQUE' => Configuration::get('CKC_PART_URL_BOUTIQUE', null, null, (int)$this->context->shop->id),
            'CKC_RECAPTCHA_ENABLED' => (int)Configuration::get('CKC_RECAPTCHA_ENABLED', 0),
            'CKC_RECAPTCHA_SECRET' => (string)Configuration::get('CKC_RECAPTCHA_SECRET', ''),
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
        $gidProticulier = (int)Tools::getValue('CKC_GROUP_PROTICULIER_ID', 15);
        $gidProList = Tools::getValue('CKC_GROUP_PRO_IDS', []);
        Configuration::updateValue('CKC_GROUP_PRO_IDS', json_encode(array_map('intval', $gidProList)));

        $applyForShop = function (int $idShop) use ($urlPro, $urlPart, $displayEp, $formMinEp, $recaptchaOn, $recaptchaKey, /* $gidPro, */ $gidProticulier) {
            Configuration::updateValue('CKC_GROUP_PROTICULIER_ID', $gidProticulier, false, null, $idShop);
        };
        $applyForShop = function (int $idShop) use ($urlPro, $urlPart, $displayEp, $formMinEp, $recaptchaOn, $recaptchaKey, $gidProticulier) {
            Configuration::updateValue('CKC_PRO_URL_BOUTIQUE', $urlPro, false, null, $idShop);
            Configuration::updateValue('CKC_PART_URL_BOUTIQUE', $urlPart, false, null, $idShop);
            Configuration::updateValue('CKC_DISPLAY_EP', $displayEp, false, null, $idShop);
            Configuration::updateValue('CKC_FORM_MIN_EP', $formMinEp, false, null, $idShop);
            Configuration::updateValue('CKC_RECAPTCHA_ENABLED', $recaptchaOn, false, null, $idShop);
            Configuration::updateValue('CKC_RECAPTCHA_SECRET', $recaptchaKey, false, null, $idShop);
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


    private function getInfosProCustomer(int $id): array
    {
        if ($id <= 0) {
            return [];
        }

        // Ne filtre PAS sur le groupe ici : on veut les infos même pour "Proticulier"
        $sql = 'SELECT c.`tva`, c.`adresse`, c.`adresse2`, c.`cp`, c.`ville`, c.`tel`,
                   c.`fileUpload`, c.`rib`, c.`naf`, c.`siret`
            FROM `' . _DB_PREFIX_ . 'customer` c
            WHERE c.`id_customer` = ' . (int)$id;

        $row = Db::getInstance()->getRow($sql);

        return is_array($row) ? $row : [];
    }


    /** ID groupe PRO (fallback) */
    private function getProGroupIds(): array
    {
        if (self::$proGroupIdsCache === null) {
            $ids = json_decode(Configuration::get('CKC_GROUP_PRO_IDS', '[]'), true);
            if (!is_array($ids) || empty($ids)) {
                $ids = [(int)Configuration::get('CKC_GROUP_PRO_ID', 10)];
            }
            self::$proGroupIdsCache = array_map('intval', $ids);
        }
        return self::$proGroupIdsCache;
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

    public function hookActionPresentCart(&$params){
        if(Context::getContext()->shop->id == 2 && Context::getContext()->controller->page_name != 'checkout'){

            $total_excluding_tax = $this->context->cart->getOrderTotal(false);
            $total_including_tax = $this->context->cart->getOrderTotal(true);
            $total_excluding_tax_cart = $this->context->cart->getOrderTotal(false, Cart::BOTH_WITHOUT_SHIPPING);
            $total_including_tax_cart = $this->context->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);

            $translator = $this->context->getTranslator();
            $priceFormatter = new PriceFormatter();

            $taxesIncluded = $this->context->controller->cart_presenter->includeTaxes();
            $total_for_cart_amount = $this->context->cart->getOrderTotal($taxesIncluded, Cart::BOTH_WITHOUT_SHIPPING);

            $newParams = [
                'total_for_cart' => [
                    'type' => 'total',
                    'label' => $translator->trans('Total', [], 'Shop.Theme.Checkout'),
                    'amount' => $total_for_cart_amount,
                    'value' => $priceFormatter->format($total_for_cart_amount),
                ],
                'total' => [
                    'type' => 'total',
                    'label' => $params['presentedCart']->totals['total']['label'],
                    'amount' => $total_for_cart_amount,
                    'value' => $priceFormatter->format($total_for_cart_amount),
                ],
                'total_including_tax' => [
                    'type' => 'total',
                    'label' => $params['presentedCart']->totals['total_including_tax']['label'],
                    'amount' => $total_including_tax_cart,
                    'value' => $priceFormatter->format($total_including_tax_cart),
                ],
                'total_excluding_tax' => [
                    'type' => 'total',
                    'label' => $params['presentedCart']->totals['total_excluding_tax']['label'],
                    'amount' => $total_excluding_tax_cart,
                    'value' => $priceFormatter->format($total_excluding_tax_cart),
                ],
            ];
            $params['presentedCart']->totals = $newParams;
        }
        return json_encode($params);
    }

    public function hookActionCustomerFormBuilderModifier(array $params)
    {
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];

        $customer_shop = Db::getInstance()->executeS('SELECT id_shop FROM '._DB_PREFIX_.'customer WHERE id_customer = '.$params['id']);
        $reference_company = Db::getInstance()->getValue('SELECT CONCAT(c2.firstname, " ", c2.lastname) FROM ' . _DB_PREFIX_ . 'customer c1
        LEFT JOIN ' . _DB_PREFIX_ . 'customer c2 ON c1.id_reference_company = c2.id_customer
        WHERE c1.`id_customer` = '.(int)$params['id']);
        //       add custom template on category page
        $formBuilder->add('categoryFieldContentCustom', CustomContentType::class, [
            'required' => false,
            'label' => 'Multiboutique',
            'mapped' => false,
            'template' => '@Modules/ckc_espacepro/views/templates/admin/change_shop.html.twig',
            'data' => [
                'customerId' => $params['id'],
                'id_shop' => $customer_shop[0]['id_shop'],
                'change_shop_url' => Context::getContext()->link->getAdminLink('AdminEspacePro', true, [], ['action' => 'ChangeShop', 'ajax' => 1]),
                'reference_company' => !empty($reference_company) ? $reference_company : '',
            ]
        ]);
        $formBuilder->add('customerFieldContentCustom', CustomContentType::class, [
            'required' => false,
            'label' => 'Compte Société de Référence',
            'mapped' => false,
            'template' => '@Modules/ckc_espacepro/views/templates/admin/reference_company.html.twig',
            'data' => [
                'change_reference_url' => Context::getContext()->link->getAdminLink('AdminEspacePro', true, [], ['configure' => 'ckc_espacepro', 'module_name' => 'ckc_espacepro']),
                'reference_company' => !empty($reference_company) ? $reference_company : '',
            ]
        ]);

        $formBuilder->setData($params['data']);
    }

    public function getProHome(): string { return $this->getEnvBases()['pro_base']; }
    public function getPartHome(): string { return $this->getEnvBases()['part_base']; }
}
