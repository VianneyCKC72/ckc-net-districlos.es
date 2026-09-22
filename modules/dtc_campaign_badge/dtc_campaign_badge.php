<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class dtc_campaign_badge extends Module
{
    private const DEFAULTS = [
        'DTC_CAMPAIGN_BADGE_ENABLED' => 1,
        'DTC_CAMPAIGN_BADGE_TITLE' => 'PROJET EN FÊTE',
        'DTC_CAMPAIGN_BADGE_TEXT' => '75€ offert tous les 500€ d\'achat !',
        'DTC_CAMPAIGN_BADGE_LINK' => '',
    ];

    public function __construct()
    {
        $this->name = 'dtc_campaign_badge';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Badge campagne');
        $this->description = $this->l('Petit badge promotionnel fixe en bas à droite.');
    }

    public function install()
    {
        foreach (self::DEFAULTS as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBeforeBodyClosingTag')
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
        $output = '';

        if (Tools::isSubmit('submitDtcCampaignBadge')) {
            Configuration::updateValue('DTC_CAMPAIGN_BADGE_ENABLED', (int) Tools::getValue('DTC_CAMPAIGN_BADGE_ENABLED'));
            Configuration::updateValue('DTC_CAMPAIGN_BADGE_TITLE', trim((string) Tools::getValue('DTC_CAMPAIGN_BADGE_TITLE')));
            Configuration::updateValue('DTC_CAMPAIGN_BADGE_TEXT', trim((string) Tools::getValue('DTC_CAMPAIGN_BADGE_TEXT')));
            Configuration::updateValue('DTC_CAMPAIGN_BADGE_LINK', trim((string) Tools::getValue('DTC_CAMPAIGN_BADGE_LINK')));
            $output .= $this->displayConfirmation($this->l('Enregistré.'));
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->submit_action = 'submitDtcCampaignBadge';
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

        return $output . $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->l('Badge campagne'), 'icon' => 'icon-tag'],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Afficher'),
                        'name' => 'DTC_CAMPAIGN_BADGE_ENABLED',
                        'is_bool' => true,
                        'values' => $switch,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Titre'),
                        'name' => 'DTC_CAMPAIGN_BADGE_TITLE',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Texte'),
                        'name' => 'DTC_CAMPAIGN_BADGE_TEXT',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Lien (optionnel)'),
                        'name' => 'DTC_CAMPAIGN_BADGE_LINK',
                        'desc' => $this->l('Laisser vide pour un badge non cliquable.'),
                    ],
                ],
                'submit' => ['title' => $this->l('Enregistrer')],
            ],
        ]]);
    }

    public function hookDisplayHeader(array $params)
    {
        if ((int) $this->cfg('DTC_CAMPAIGN_BADGE_ENABLED') === 0 || !$this->isAllowedPage()) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'dtc-campaign-badge',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );
    }

    public function hookDisplayBeforeBodyClosingTag(array $params)
    {
        if ((int) $this->cfg('DTC_CAMPAIGN_BADGE_ENABLED') === 0 || !$this->isAllowedPage()) {
            return '';
        }

        $text = $this->trans($this->cfg('DTC_CAMPAIGN_BADGE_TEXT'), [], 'Shop.Theme.Customdtc');
        $text = preg_replace(
            '/(\d+\s*€|€\s*\d+)/u',
            '<span class="dtc-campaign-badge__amount">$1</span>',
            $text
        );

        $this->context->smarty->assign('dtc_campaign_badge', [
            'title' => $this->trans($this->cfg('DTC_CAMPAIGN_BADGE_TITLE'), [], 'Shop.Theme.Customdtc'),
            'text' => $text,
            'link' => $this->cfg('DTC_CAMPAIGN_BADGE_LINK'),
            'bg' => $this->_path . 'views/img/banderole.png',
        ]);

        return $this->display(__FILE__, 'views/templates/hook/badge.tpl');
    }

    private function isAllowedPage()
    {
        $controller = $this->context->controller;
        if (!$controller || !isset($controller->php_self)) {
            return false;
        }

        return in_array($controller->php_self, ['index', 'product', 'category'], true);
    }

    private function cfg($key)
    {
        $value = Configuration::get($key);

        return ($value === false || $value === '') ? self::DEFAULTS[$key] : $value;
    }
}
