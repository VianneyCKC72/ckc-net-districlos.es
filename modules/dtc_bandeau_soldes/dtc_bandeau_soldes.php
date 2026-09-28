<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class dtc_bandeau_soldes extends Module
{
    private const DEFAULTS = [
        'DTC_BANDEAU_SOLDES_ENABLED' => 1,
        'DTC_BANDEAU_SOLDES_BADGE' => 'BONS PLANS',
        'DTC_BANDEAU_SOLDES_TEXT' => '<strong>PROJET EN FÊTE</strong> - <strong>75€</strong> tous les <strong>500€</strong> d\'achat',
        'DTC_BANDEAU_SOLDES_LINK' => '/400-bons-plans',
    ];

    public function __construct()
    {
        $this->name = 'dtc_bandeau_soldes';
        $this->tab = 'front_office_features';
        $this->version = '1.0.4';
        $this->author = 'Districlos';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Bandeau Bons plans');
        $this->description = $this->l('Bandeau promotionnel en haut du site.');
    }

    public function install()
    {
        foreach (self::DEFAULTS as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        return parent::install()
            && $this->registerHook('displayBandeauSoldes')
            && $this->repairModuleGroupRestrictions();
    }

    /**
     * Avec PS_GROUP_FEATURE_ACTIVE, PrestaShop filtre les modules sur ps_module_group.
     * Sans lignes pour ce module, Hook::exec n'exécute pas le module en FO.
     */
    public function repairModuleGroupRestrictions()
    {
        if (!class_exists('Group') || !Group::isFeatureActive()) {
            return true;
        }

        $idModule = (int) $this->id;
        if ($idModule <= 0) {
            return false;
        }

        Group::truncateRestrictionsByModule($idModule);

        $shops = Shop::getCompleteListOfShopsID();
        if (empty($shops)) {
            $shops = [(int) Configuration::get('PS_SHOP_DEFAULT')];
        }

        $ok = (bool) Group::addRestrictionsForModule($idModule, $shops);

        if ($ok && class_exists('Hook')) {
            Cache::clean(Hook::MODULE_LIST_BY_HOOK_KEY . '*');
        }

        return $ok;
    }

    public function uninstall()
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitDtcBandeauSoldes')) {
            Configuration::updateValue('DTC_BANDEAU_SOLDES_ENABLED', (int) Tools::getValue('DTC_BANDEAU_SOLDES_ENABLED'));
            Configuration::updateValue('DTC_BANDEAU_SOLDES_BADGE', trim((string) Tools::getValue('DTC_BANDEAU_SOLDES_BADGE')), true);
            Configuration::updateValue('DTC_BANDEAU_SOLDES_TEXT', (string) Tools::getValue('DTC_BANDEAU_SOLDES_TEXT'), true);
            Configuration::updateValue('DTC_BANDEAU_SOLDES_LINK', trim((string) Tools::getValue('DTC_BANDEAU_SOLDES_LINK')), true);
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->submit_action = 'submitDtcBandeauSoldes';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $fields_value = [];
        foreach (array_keys(self::DEFAULTS) as $key) {
            $fields_value[$key] = $this->cfg($key);
        }

        $helper->tpl_vars = [
            'fields_value' => $fields_value,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => (int) $this->context->language->id,
        ];

        $switch = [
            ['id' => 'on', 'value' => 1, 'label' => $this->l('Oui')],
            ['id' => 'off', 'value' => 0, 'label' => $this->l('Non')],
        ];

        return (Tools::isSubmit('submitDtcBandeauSoldes') ? $this->displayConfirmation($this->l('Enregistré.')) : '')
            . $helper->generateForm([[
                'form' => [
                    'legend' => ['title' => $this->l('Bandeau Bons plans'), 'icon' => 'icon-tag'],
                    'input' => [
                        ['type' => 'switch', 'label' => $this->l('Afficher'), 'name' => 'DTC_BANDEAU_SOLDES_ENABLED', 'is_bool' => true, 'values' => $switch],
                        ['type' => 'text', 'label' => $this->l('Badge'), 'name' => 'DTC_BANDEAU_SOLDES_BADGE'],
                        ['type' => 'textarea', 'label' => $this->l('Texte'), 'name' => 'DTC_BANDEAU_SOLDES_TEXT', 'cols' => 60, 'rows' => 3],
                        ['type' => 'text', 'label' => $this->l('Lien'), 'name' => 'DTC_BANDEAU_SOLDES_LINK'],
                    ],
                    'submit' => ['title' => $this->l('Enregistrer')],
                ],
            ]]);
    }

    public function hookDisplayBandeauSoldes(array $params)
    {
        if ((int) $this->cfg('DTC_BANDEAU_SOLDES_ENABLED') === 0) {
            return '';
        }

        $this->context->smarty->assign('dtc_bandeau', [
            'badge' => $this->trans($this->cfg('DTC_BANDEAU_SOLDES_BADGE'), [], 'Shop.Theme.Customdtc'),
            // HTML (<strong>) autorisé : trans() échappe, on décode pour le FO
            'text' => htmlspecialchars_decode(
                $this->trans($this->cfg('DTC_BANDEAU_SOLDES_TEXT'), [], 'Shop.Theme.Customdtc'),
                ENT_QUOTES
            ),
            'link' => $this->localizeStorefrontLink((string) $this->cfg('DTC_BANDEAU_SOLDES_LINK')),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/bandeau.tpl');
    }

    private function cfg($key)
    {
        $value = Configuration::get($key);

        return ($value === false || $value === '') ? self::DEFAULTS[$key] : $value;
    }

    /**
     * Réécrit un chemin FR figé (/400-bons-plans, /content/21-…) dans la langue courante.
     */
    private function localizeStorefrontLink(string $link): string
    {
        $link = trim($link);
        if ($link === '') {
            return $link;
        }

        $path = (string) parse_url($link, PHP_URL_PATH);
        if ($path === '') {
            $path = $link;
        }

        if (preg_match('#/content/(\d+)#', $path, $matches)) {
            return $this->context->link->getCMSLink((int) $matches[1]);
        }

        if (preg_match('#/(\d+)-#', $path, $matches)) {
            return $this->context->link->getCategoryLink((int) $matches[1]);
        }

        if (preg_match('#/module/([^/]+)/([^/?]+)#', $path, $matches)) {
            return $this->context->link->getModuleLink($matches[1], $matches[2]);
        }

        return $link;
    }
}
