<?php
if (!defined('_PS_VERSION_')) { exit; }

class ckc_espacepro_invisible extends Module
{
    const CFG_ENABLED         = 'CKC_EPINV_ENABLED';
    const CFG_MODE            = 'CKC_EPINV_MODE'; // ip_or_admin | admins_only | ip_whitelist
    const CFG_ALLOWED_IPS     = 'CKC_EPINV_ALLOWED_IPS';
    const CFG_PRO_SHOP_ID     = 'CKC_EPINV_PRO_SHOP_ID';
    const CFG_PRO_URL_SUBSTR  = 'CKC_EPINV_PRO_URL';
    const CFG_PART_URL        = 'CKC_EPINV_PART_URL';
    const CFG_PREVIEW_TOKEN   = 'CKC_EPINV_PREVIEW_TOKEN';

    public function __construct()
    {
        $this->name = 'ckc_espacepro_invisible';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'CKC';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'CKC Espace PRO – Invisible';
        $this->description = 'Accès shop PRO limité (IP/Admin), redirection visiteurs, noindex.';
    }

    public function install()
    {
        $ok = parent::install()
              && $this->registerHook('actionFrontControllerInitBefore')
              && $this->registerHook('displayHeader');

        if ($ok) {
            Configuration::updateValue(self::CFG_ENABLED, 1);
            Configuration::updateValue(self::CFG_MODE, 'ip_or_admin');
            Configuration::updateValue(self::CFG_ALLOWED_IPS, "127.0.0.1\n::1");
            Configuration::updateValue(self::CFG_PRO_SHOP_ID, 2);
            Configuration::updateValue(self::CFG_PRO_URL_SUBSTR, '');
            Configuration::updateValue(self::CFG_PART_URL, 'https://www.monsite.fr/');
            Configuration::updateValue(self::CFG_PREVIEW_TOKEN, '');
        }

        return $ok;
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CFG_ENABLED);
        Configuration::deleteByName(self::CFG_MODE);
        Configuration::deleteByName(self::CFG_ALLOWED_IPS);
        Configuration::deleteByName(self::CFG_PRO_SHOP_ID);
        Configuration::deleteByName(self::CFG_PRO_URL_SUBSTR);
        Configuration::deleteByName(self::CFG_PART_URL);
        Configuration::deleteByName(self::CFG_PREVIEW_TOKEN);

        return parent::uninstall();
    }


    // Redirection / filtrage le plus tôt possible
    public function hookActionFrontControllerInitBefore($params)
    {
        if (!(int)Configuration::get(self::CFG_ENABLED)) {
            return;
        }

        if (!$this->isProEnvironment()) {
            return;
        }

        // Bypass via token (session/cookie de prévisualisation)
        if ($this->hasValidPreviewBypass()) {
            return;
        }

        $mode = (string)Configuration::get(self::CFG_MODE);
        $isAdmin = $this->isEmployeeLogged();
        $ipAllowed = $this->isIpAllowed(Tools::getRemoteAddr());

        $allowed = false;
        switch ($mode) {
            case 'admins_only':
                $allowed = $isAdmin;
                break;
            case 'ip_whitelist':
                $allowed = $ipAllowed;
                break;
            case 'ip_or_admin':
            default:
                $allowed = ($isAdmin || $ipAllowed);
                break;
        }

        if (!$allowed) {
            $partUrl = trim((string)Configuration::get(self::CFG_PART_URL));
            if ($partUrl !== '') {
                Tools::redirect($partUrl);
            } else {
                header('HTTP/1.1 403 Forbidden');
                exit;
            }
        }
    }

    public function hookDisplayHeader($params)
    {
        if (!(int)Configuration::get(self::CFG_ENABLED)) {
            return '';
        }
        if (!$this->isProEnvironment()) {
            return '';
        }

        // HTTP header
        @header('X-Robots-Tag: noindex, nofollow, noarchive');

        // Balise meta
        return '<meta name="robots" content="noindex,nofollow,noarchive">';
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitCkcEpinv')) {
            $this->postProcess();
        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->default_form_language = (int)$this->context->language->id;
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCkcEpinv';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                                .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => (int)$this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        $modes = [
            ['id' => 'ip_or_admin',  'name' => $this->l('IP autorisées OU Employés BO')],
            ['id' => 'admins_only',  'name' => $this->l('Uniquement Employés BO')],
            ['id' => 'ip_whitelist','name' => $this->l('Uniquement IP autorisées')],
        ];

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Espace PRO – Invisibilité / Accès restreint'),
                    'icon'  => 'icon-eye-close',
                ],
                'input'  => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Activer le filtrage'),
                        'name'   => self::CFG_ENABLED,
                        'is_bool'=> true,
                        'values' => [
                            ['id' => 'on',  'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type'    => 'select',
                        'label'   => $this->l('Mode d’accès'),
                        'name'    => self::CFG_MODE,
                        'options' => [
                            'query' => $modes,
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                        'desc'    => $this->l('Choisir la règle d’autorisation (IP et/ou Employés BO).'),
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->l('IP autorisées'),
                        'name'  => self::CFG_ALLOWED_IPS,
                        'cols'  => 60,
                        'rows'  => 6,
                        'desc'  => $this->l("Une par ligne. Supporte IPv4/IPv6, CIDR (ex: 192.168.0.0/24) et wildcards (ex: 192.168.*.*). Votre IP actuelle : ").Tools::safeOutput(Tools::getRemoteAddr()),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('ID boutique PRO'),
                        'name'  => self::CFG_PRO_SHOP_ID,
                        'class' => 'fixed-width-sm',
                        'desc'  => $this->l('ID de la boutique PRO (ex: 2).'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Sous-domaine / URL partielle PRO'),
                        'name'  => self::CFG_PRO_URL_SUBSTR,
                        'class' => 'fixed-width-xxl',
                        'desc'  => $this->l('Optionnel. Exemple : pro.mondomaine.fr (détection par sous-chaîne).'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('URL redirection Particulier'),
                        'name'  => self::CFG_PART_URL,
                        'class' => 'fixed-width-xxl',
                        'desc'  => $this->l('Ex: https://www.monsite.fr/'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Token de prévisualisation'),
                        'name'  => self::CFG_PREVIEW_TOKEN,
                        'class' => 'fixed-width-lg',
                        'desc'  => $this->l('Optionnel. Autorise l’accès avec ?ckc_preview=TOKEN (conserve un cookie de session).'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        return [
            self::CFG_ENABLED        => (int)Configuration::get(self::CFG_ENABLED, 1),
            self::CFG_MODE           => (string)Configuration::get(self::CFG_MODE, 'ip_or_admin'),
            self::CFG_ALLOWED_IPS    => (string)Configuration::get(self::CFG_ALLOWED_IPS, ''),
            self::CFG_PRO_SHOP_ID    => (int)Configuration::get(self::CFG_PRO_SHOP_ID, 2),
            self::CFG_PRO_URL_SUBSTR => (string)Configuration::get(self::CFG_PRO_URL_SUBSTR, ''),
            self::CFG_PART_URL       => (string)Configuration::get(self::CFG_PART_URL, ''),
            self::CFG_PREVIEW_TOKEN  => (string)Configuration::get(self::CFG_PREVIEW_TOKEN, ''),
        ];
    }

    protected function postProcess()
    {
        $keys = [
            self::CFG_ENABLED,
            self::CFG_MODE,
            self::CFG_ALLOWED_IPS,
            self::CFG_PRO_SHOP_ID,
            self::CFG_PRO_URL_SUBSTR,
            self::CFG_PART_URL,
            self::CFG_PREVIEW_TOKEN,
        ];
        foreach ($keys as $k) {
            Configuration::updateValue($k, Tools::getValue($k));
        }
        $this->_clearCache('*');
    }

    protected function isProEnvironment(): bool
    {
        $ctx = Context::getContext();
        $shopId = (int)$ctx->shop->id;

        $proShopId = (int)Configuration::get(self::CFG_PRO_SHOP_ID);
        if ($proShopId > 0 && $shopId === $proShopId) {
            return true;
        }

        $substr = trim((string)Configuration::get(self::CFG_PRO_URL_SUBSTR));
        if ($substr !== '') {
            $host = Tools::getShopDomainSsl(true);
            if (stripos($host, $substr) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function isEmployeeLogged(): bool
    {
        $emp = Context::getContext()->employee;
        return ($emp && (int)$emp->id > 0);
    }

    protected function hasValidPreviewBypass(): bool
    {
        $token = trim((string)Configuration::get(self::CFG_PREVIEW_TOKEN));
        if ($token === '') {
            return false;
        }

        // Query param -> set cookie (session)
        $q = (string)Tools::getValue('ckc_preview', '');
        if ($q !== '' && hash_equals($token, $q)) {
            // cookie FO
            $cookie = Context::getContext()->cookie;
            $cookie->__set('ckc_epinv_preview', 1);
            $cookie->write();
            return true;
        }

        // Cookie existant ?
        $cookie = Context::getContext()->cookie;
        return (isset($cookie->ckc_epinv_preview) && (int)$cookie->ckc_epinv_preview === 1);
    }

    protected function isIpAllowed(string $ip): bool
    {
        $raw = (string)Configuration::get(self::CFG_ALLOWED_IPS, '');
        if ($raw === '') return false;

        $lines = preg_split('/\r\n|\r|\n/', $raw);
        foreach ($lines as $line) {
            $rule = trim($line);
            if ($rule === '') continue;
            if ($this->ipMatches($ip, $rule)) {
                return true;
            }
        }
        return false;
    }

    protected function ipMatches(string $ip, string $rule): bool
    {
        if (strpos($rule, '/') !== false) {
            return $this->ipInCidr($ip, $rule);
        }
        // merci chatgtp
        if (strpos($rule, '*') !== false) {
            $pattern = '/^' . str_replace(['.', ':', '*'], ['\.', '\:', '.*'], $rule) . '$/i';
            return (bool)preg_match($pattern, $ip);
        }
        // Exact match
        return trim($ip) === $rule;
    }

    protected function ipInCidr(string $ip, string $cidr): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && preg_match('~^(\d{1,3}\.){3}\d{1,3}/\d{1,2}$~', $cidr)) {
            list($subnet, $mask) = explode('/', $cidr, 2);
            $mask = (int)$mask;
            if ($mask < 0 || $mask > 32) return false;
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - $mask);
            $subnetLong &= $maskLong;
            return ($ipLong & $maskLong) === $subnetLong;
        }
        return false;
    }
}
