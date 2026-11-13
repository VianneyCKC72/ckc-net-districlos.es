<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class dtc_bandeau_soldes extends Module
{
    private const DEFAULTS = [
        'DTC_BANDEAU_SOLDES_ENABLED' => 1,
        'DTC_BANDEAU_SOLDES_BADGE' => 'BONS PLANS',
        'DTC_BANDEAU_SOLDES_BADGE_COLOR_BG' => '#026473',
        'DTC_BANDEAU_SOLDES_BADGE_COLOR_TEXT' => '#FFFFFF',
        'DTC_BANDEAU_SOLDES_BADGE_POSITION' => 'start',
        'DTC_BANDEAU_SOLDES_TEXT' => '<strong>PROJET EN FÊTE</strong> - <strong>75€</strong> tous les <strong>500€</strong> d\'achat',
        'DTC_BANDEAU_SOLDES_LINK' => '/400-bons-plans',
        'DTC_BANDEAU_SOLDES_DATE_END' => '',
        'DTC_BANDEAU_SOLDES_COLOR_BG' => '#E7FBD8',
        'DTC_BANDEAU_SOLDES_COLOR_TEXT' => '#79C046',
        'DTC_BANDEAU_SOLDES_NEXT_DATE' => '',
        'DTC_BANDEAU_SOLDES_NEXT_BADGE' => '',
        'DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_BG' => '',
        'DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_TEXT' => '',
        'DTC_BANDEAU_SOLDES_NEXT_TEXT' => '',
        'DTC_BANDEAU_SOLDES_NEXT_LINK' => '',
        'DTC_BANDEAU_SOLDES_NEXT_COLOR_BG' => '',
        'DTC_BANDEAU_SOLDES_NEXT_COLOR_TEXT' => '',
    ];

    /** Clé programmation => clé contenu actuel */
    private const NEXT_MAP = [
        'DTC_BANDEAU_SOLDES_NEXT_BADGE' => 'DTC_BANDEAU_SOLDES_BADGE',
        'DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_BG' => 'DTC_BANDEAU_SOLDES_BADGE_COLOR_BG',
        'DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_TEXT' => 'DTC_BANDEAU_SOLDES_BADGE_COLOR_TEXT',
        'DTC_BANDEAU_SOLDES_NEXT_TEXT' => 'DTC_BANDEAU_SOLDES_TEXT',
        'DTC_BANDEAU_SOLDES_NEXT_LINK' => 'DTC_BANDEAU_SOLDES_LINK',
        'DTC_BANDEAU_SOLDES_NEXT_COLOR_BG' => 'DTC_BANDEAU_SOLDES_COLOR_BG',
        'DTC_BANDEAU_SOLDES_NEXT_COLOR_TEXT' => 'DTC_BANDEAU_SOLDES_COLOR_TEXT',
    ];

    private const ALLOWED_HTML_TAGS = '<strong><b><em><i><br><span>';

    public function __construct()
    {
        $this->name = 'dtc_bandeau_soldes';
        $this->tab = 'front_office_features';
        $this->version = '1.0.7';
        $this->author = 'Districlos';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Bandeau Bons plans');
        $this->description = $this->l('Bandeau promotionnel en haut du site.');
    }

    public function install()
    {
        foreach (self::DEFAULTS as $key => $value) {
            Configuration::updateGlobalValue($key, $value, true);
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
        Configuration::deleteByName('DTC_BANDEAU_SOLDES_DATE_START');

        return parent::uninstall();
    }

    /**
     * Migration unique 1.0.4 → 1.0.5.
     */
    public function upgradeTo105()
    {
        $this->clearShopOverrides();

        foreach (self::DEFAULTS as $key => $value) {
            if (Configuration::get($key) === false) {
                Configuration::updateGlobalValue($key, $value, true);
            }
        }

        Configuration::deleteByName('DTC_BANDEAU_SOLDES_DATE_START');

        return true;
    }

    public function upgradeTo107()
    {
        foreach ([
            'DTC_BANDEAU_SOLDES_BADGE_COLOR_BG',
            'DTC_BANDEAU_SOLDES_BADGE_COLOR_TEXT',
            'DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_BG',
            'DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_TEXT',
        ] as $key) {
            if (Configuration::get($key) === false) {
                Configuration::updateGlobalValue($key, self::DEFAULTS[$key]);
            }
        }

        return true;
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDtcBandeauSoldes')) {
            $this->clearShopOverrides();
            $this->persistRequest();
            $output .= $this->displayConfirmation($this->l('Enregistré.'));
        }

        $this->promoteScheduledIfDue();

        return $output . $this->renderForm();
    }

    public function hookDisplayBandeauSoldes(array $params)
    {
        if ((int) $this->cfg('DTC_BANDEAU_SOLDES_ENABLED') === 0) {
            return '';
        }

        if ($this->isDatePassed('DTC_BANDEAU_SOLDES_DATE_END')) {
            return '';
        }

        $this->promoteScheduledIfDue();

        $badge = $this->sanitizeBannerHtml(
            htmlspecialchars_decode(
                $this->trans($this->cfg('DTC_BANDEAU_SOLDES_BADGE'), [], 'Shop.Theme.Customdtc'),
                ENT_QUOTES
            )
        );
        $badgeLabel = trim((string) preg_replace(
            '/\s+/',
            ' ',
            strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], ' ', $badge))
        ));

        $this->context->smarty->assign('dtc_bandeau', [
            'badge' => $badge,
            'badge_label' => $badgeLabel,
            'badge_color_bg' => $this->sanitizeColor(
                $this->cfg('DTC_BANDEAU_SOLDES_BADGE_COLOR_BG'),
                self::DEFAULTS['DTC_BANDEAU_SOLDES_BADGE_COLOR_BG']
            ),
            'badge_color_text' => $this->sanitizeColor(
                $this->cfg('DTC_BANDEAU_SOLDES_BADGE_COLOR_TEXT'),
                self::DEFAULTS['DTC_BANDEAU_SOLDES_BADGE_COLOR_TEXT']
            ),
            'badge_position' => $this->sanitizeBadgePosition(
                $this->cfg('DTC_BANDEAU_SOLDES_BADGE_POSITION')
            ),
            'text' => $this->sanitizeBannerHtml(
                htmlspecialchars_decode(
                    $this->trans($this->cfg('DTC_BANDEAU_SOLDES_TEXT'), [], 'Shop.Theme.Customdtc'),
                    ENT_QUOTES
                )
            ),
            'link' => $this->localizeStorefrontLink((string) $this->cfg('DTC_BANDEAU_SOLDES_LINK')),
            'color_bg' => $this->sanitizeColor(
                $this->cfg('DTC_BANDEAU_SOLDES_COLOR_BG'),
                self::DEFAULTS['DTC_BANDEAU_SOLDES_COLOR_BG']
            ),
            'color_text' => $this->sanitizeColor(
                $this->cfg('DTC_BANDEAU_SOLDES_COLOR_TEXT'),
                self::DEFAULTS['DTC_BANDEAU_SOLDES_COLOR_TEXT']
            ),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/bandeau.tpl');
    }

    /**
     * Bascule auto : copie NEXT → actuel puis vide la programmation.
     */
    public function promoteScheduledIfDue()
    {
        if (!$this->isDateReached('DTC_BANDEAU_SOLDES_NEXT_DATE')) {
            return false;
        }

        foreach (self::NEXT_MAP as $nextKey => $currentKey) {
            $value = trim((string) Configuration::get($nextKey));
            if (strpos($nextKey, 'COLOR') !== false) {
                $value = $this->sanitizeColor($value, '');
            } elseif (in_array($nextKey, ['DTC_BANDEAU_SOLDES_NEXT_BADGE', 'DTC_BANDEAU_SOLDES_NEXT_TEXT'], true)) {
                $value = $this->sanitizeBannerHtml($value);
            }

            if ($value !== '') {
                Configuration::updateGlobalValue($currentKey, $value, true);
            }
            Configuration::updateGlobalValue($nextKey, '', true);
        }

        Configuration::updateGlobalValue('DTC_BANDEAU_SOLDES_NEXT_DATE', '');

        return true;
    }

    private function persistRequest()
    {
        Configuration::updateGlobalValue('DTC_BANDEAU_SOLDES_ENABLED', (int) Tools::getValue('DTC_BANDEAU_SOLDES_ENABLED'));
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_BADGE',
            $this->sanitizeBannerHtml(Tools::getValue('DTC_BANDEAU_SOLDES_BADGE')),
            true
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_BADGE_COLOR_BG',
            $this->sanitizeColor(
                Tools::getValue('DTC_BANDEAU_SOLDES_BADGE_COLOR_BG'),
                self::DEFAULTS['DTC_BANDEAU_SOLDES_BADGE_COLOR_BG']
            )
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_BADGE_COLOR_TEXT',
            $this->sanitizeColor(
                Tools::getValue('DTC_BANDEAU_SOLDES_BADGE_COLOR_TEXT'),
                self::DEFAULTS['DTC_BANDEAU_SOLDES_BADGE_COLOR_TEXT']
            )
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_BADGE_POSITION',
            $this->sanitizeBadgePosition(Tools::getValue('DTC_BANDEAU_SOLDES_BADGE_POSITION'))
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_TEXT',
            $this->sanitizeBannerHtml(Tools::getValue('DTC_BANDEAU_SOLDES_TEXT')),
            true
        );
        Configuration::updateGlobalValue('DTC_BANDEAU_SOLDES_LINK', trim((string) Tools::getValue('DTC_BANDEAU_SOLDES_LINK')), true);
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_DATE_END',
            $this->sanitizeDatetime(Tools::getValue('DTC_BANDEAU_SOLDES_DATE_END'))
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_COLOR_BG',
            $this->sanitizeColor(Tools::getValue('DTC_BANDEAU_SOLDES_COLOR_BG'), self::DEFAULTS['DTC_BANDEAU_SOLDES_COLOR_BG'])
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_COLOR_TEXT',
            $this->sanitizeColor(Tools::getValue('DTC_BANDEAU_SOLDES_COLOR_TEXT'), self::DEFAULTS['DTC_BANDEAU_SOLDES_COLOR_TEXT'])
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_NEXT_DATE',
            $this->sanitizeDatetime(Tools::getValue('DTC_BANDEAU_SOLDES_NEXT_DATE'))
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_NEXT_BADGE',
            $this->sanitizeBannerHtml(Tools::getValue('DTC_BANDEAU_SOLDES_NEXT_BADGE')),
            true
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_BG',
            $this->sanitizeColor(Tools::getValue('DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_BG'), '')
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_TEXT',
            $this->sanitizeColor(Tools::getValue('DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_TEXT'), '')
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_NEXT_TEXT',
            $this->sanitizeBannerHtml(Tools::getValue('DTC_BANDEAU_SOLDES_NEXT_TEXT')),
            true
        );
        Configuration::updateGlobalValue('DTC_BANDEAU_SOLDES_NEXT_LINK', trim((string) Tools::getValue('DTC_BANDEAU_SOLDES_NEXT_LINK')), true);
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_NEXT_COLOR_BG',
            $this->sanitizeColor(Tools::getValue('DTC_BANDEAU_SOLDES_NEXT_COLOR_BG'), '')
        );
        Configuration::updateGlobalValue(
            'DTC_BANDEAU_SOLDES_NEXT_COLOR_TEXT',
            $this->sanitizeColor(Tools::getValue('DTC_BANDEAU_SOLDES_NEXT_COLOR_TEXT'), '')
        );
    }

    private function renderForm()
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->submit_action = 'submitDtcBandeauSoldes';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $fieldsValue = [];
        foreach (array_keys(self::DEFAULTS) as $key) {
            $value = Configuration::get($key);
            $fieldsValue[$key] = ($value === false) ? self::DEFAULTS[$key] : $value;
        }
        $fieldsValue['DTC_BANDEAU_SOLDES_DATE_END'] = $this->sanitizeDatetime($fieldsValue['DTC_BANDEAU_SOLDES_DATE_END']);
        $fieldsValue['DTC_BANDEAU_SOLDES_NEXT_DATE'] = $this->sanitizeDatetime($fieldsValue['DTC_BANDEAU_SOLDES_NEXT_DATE']);

        $helper->tpl_vars = [
            'fields_value' => $fieldsValue,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => (int) $this->context->language->id,
        ];

        $switch = [
            ['id' => 'on', 'value' => 1, 'label' => $this->l('Oui')],
            ['id' => 'off', 'value' => 0, 'label' => $this->l('Non')],
        ];
        $htmlHelp = $this->trans(
            'Gras : <strong>mot</strong> — Italique : <em>mot</em> — Couleur : <span style="color: #FF0C4D;">mot</span>.',
            [],
            'Modules.Dtcbandeausoldes.Admin'
        );

        return $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->l('Bandeau Bons plans'), 'icon' => 'icon-tag'],
                'input' => [
                    [
                        'type' => 'html',
                        'name' => 'section_current',
                        'html_content' => '<h3>' . $this->l('Contenu actuel') . '</h3>'
                            . '<p class="help-block">' . $this->l('Affiché maintenant. Reste visible jusqu\'à la bascule ou la date de fin.') . '</p>',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Afficher'),
                        'name' => 'DTC_BANDEAU_SOLDES_ENABLED',
                        'is_bool' => true,
                        'values' => $switch,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Badge'),
                        'name' => 'DTC_BANDEAU_SOLDES_BADGE',
                        'desc' => $htmlHelp,
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->trans('Couleur de fond du badge', [], 'Modules.Dtcbandeausoldes.Admin'),
                        'name' => 'DTC_BANDEAU_SOLDES_BADGE_COLOR_BG',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->trans('Couleur du texte du badge', [], 'Modules.Dtcbandeausoldes.Admin'),
                        'name' => 'DTC_BANDEAU_SOLDES_BADGE_COLOR_TEXT',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Position du badge', [], 'Modules.Dtcbandeausoldes.Admin'),
                        'name' => 'DTC_BANDEAU_SOLDES_BADGE_POSITION',
                        'options' => [
                            'query' => [
                                ['id' => 'start', 'name' => $this->trans('Au début du texte', [], 'Modules.Dtcbandeausoldes.Admin')],
                                ['id' => 'end', 'name' => $this->trans('À la fin du texte', [], 'Modules.Dtcbandeausoldes.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Texte'),
                        'name' => 'DTC_BANDEAU_SOLDES_TEXT',
                        'cols' => 60,
                        'rows' => 3,
                        'desc' => $htmlHelp,
                    ],
                    ['type' => 'color', 'label' => $this->l('Couleur du texte'), 'name' => 'DTC_BANDEAU_SOLDES_COLOR_TEXT'],
                    ['type' => 'color', 'label' => $this->l('Couleur de fond'), 'name' => 'DTC_BANDEAU_SOLDES_COLOR_BG'],
                    ['type' => 'text', 'label' => $this->l('Lien'), 'name' => 'DTC_BANDEAU_SOLDES_LINK'],
                    [
                        'type' => 'datetime',
                        'label' => $this->l('Date de fin'),
                        'name' => 'DTC_BANDEAU_SOLDES_DATE_END',
                        'desc' => $this->l('Masque le bandeau après cette date. Vide = pas de fin.'),
                    ],
                    [
                        'type' => 'html',
                        'name' => 'section_next',
                        'html_content' => '<hr><h3>' . $this->l('Campagne suivante') . '</h3>'
                            . '<p class="help-block">' . $this->l('À la date de bascule, remplace le contenu actuel. Avant, le bandeau actuel reste affiché.') . '</p>',
                    ],
                    [
                        'type' => 'datetime',
                        'label' => $this->l('Date de bascule'),
                        'name' => 'DTC_BANDEAU_SOLDES_NEXT_DATE',
                        'desc' => $this->l('Vide = pas de campagne.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Nouveau badge'),
                        'name' => 'DTC_BANDEAU_SOLDES_NEXT_BADGE',
                        'desc' => $this->l('Vide = conserver le badge actuel.') . ' ' . $htmlHelp,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Nouvelle couleur de fond du badge', [], 'Modules.Dtcbandeausoldes.Admin'),
                        'name' => 'DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_BG',
                        'desc' => $this->trans('Vide = conserver la couleur actuelle.', [], 'Modules.Dtcbandeausoldes.Admin'),
                        'class' => 'fixed-width-md',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Nouvelle couleur du texte du badge', [], 'Modules.Dtcbandeausoldes.Admin'),
                        'name' => 'DTC_BANDEAU_SOLDES_NEXT_BADGE_COLOR_TEXT',
                        'desc' => $this->trans('Vide = conserver la couleur actuelle.', [], 'Modules.Dtcbandeausoldes.Admin'),
                        'class' => 'fixed-width-md',
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Nouveau texte'),
                        'name' => 'DTC_BANDEAU_SOLDES_NEXT_TEXT',
                        'cols' => 60,
                        'rows' => 3,
                        'desc' => $htmlHelp,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Nouvelle couleur du texte'),
                        'name' => 'DTC_BANDEAU_SOLDES_NEXT_COLOR_TEXT',
                        'desc' => $this->l('Ex. #79C046. Vide = conserver.'),
                        'class' => 'fixed-width-md',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Nouvelle couleur de fond'),
                        'name' => 'DTC_BANDEAU_SOLDES_NEXT_COLOR_BG',
                        'desc' => $this->l('Ex. #E7FBD8. Vide = conserver.'),
                        'class' => 'fixed-width-md',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Nouveau lien'),
                        'name' => 'DTC_BANDEAU_SOLDES_NEXT_LINK',
                        'desc' => $this->l('Vide = conserver le lien actuel.'),
                    ],
                ],
                'submit' => ['title' => $this->l('Enregistrer')],
            ],
        ]]);
    }

    private function cfg($key)
    {
        $value = Configuration::get($key);
        if ($value === false || $value === '') {
            return self::DEFAULTS[$key];
        }

        return $value;
    }

    /** true si maintenant >= date (bascule). */
    private function isDateReached($key)
    {
        $ts = $this->configTimestamp($key);

        return $ts !== null && time() >= $ts;
    }

    /** true si maintenant > date (fin exclusive). */
    private function isDatePassed($key)
    {
        $ts = $this->configTimestamp($key);

        return $ts !== null && time() > $ts;
    }

    private function configTimestamp($key)
    {
        $date = $this->sanitizeDatetime(Configuration::get($key));
        if ($date === '') {
            return null;
        }

        $ts = strtotime($date);

        return ($ts === false) ? null : $ts;
    }

    /**
     * Supprime les overrides boutique (FO lisait shop, BO écrivait global).
     */
    public function clearShopOverrides()
    {
        $names = array_merge(array_keys(self::DEFAULTS), ['DTC_BANDEAU_SOLDES_DATE_START']);
        $in = "'" . implode("','", array_map('pSQL', $names)) . "'";

        $rows = Db::getInstance()->executeS(
            'SELECT id_configuration FROM `' . _DB_PREFIX_ . 'configuration`
             WHERE `name` IN (' . $in . ')
               AND (id_shop IS NOT NULL OR id_shop_group IS NOT NULL)'
        );

        if (empty($rows)) {
            return true;
        }

        foreach ($rows as $row) {
            $id = (int) $row['id_configuration'];
            Db::getInstance()->delete('configuration_lang', 'id_configuration = ' . $id);
            Db::getInstance()->delete('configuration', 'id_configuration = ' . $id);
        }

        Configuration::resetStaticCache();

        return true;
    }

    private function sanitizeBannerHtml($html)
    {
        $html = strip_tags((string) $html, self::ALLOWED_HTML_TAGS);

        return (string) preg_replace_callback(
            '#<\s*(/?)\s*(strong|b|em|i|br|span)\b([^>]*)>#i',
            static function ($matches) {
                $closing = ($matches[1] === '/');
                $tag = Tools::strtolower($matches[2]);
                if ($tag === 'br') {
                    return '<br>';
                }
                if ($closing) {
                    return '</' . $tag . '>';
                }
                if ($tag === 'span') {
                    $color = dtc_bandeau_soldes::extractInlineColorStyle($matches[3]);

                    return $color !== '' ? '<span style="color: ' . $color . ';">' : '<span>';
                }

                return '<' . $tag . '>';
            },
            $html
        );
    }

    private function sanitizeBadgePosition($value)
    {
        return ($value === 'end') ? 'end' : 'start';
    }

    /**
     * Autorise uniquement style="color: #RGB|#RRGGBB" (admin-controlled HTML).
     */
    private static function extractInlineColorStyle($attrs)
    {
        if (!preg_match('/style\s*=\s*(["\'])(.*?)\1/i', (string) $attrs, $styleMatch)) {
            return '';
        }
        if (!preg_match('/^\s*color\s*:\s*(#[0-9a-fA-F]{3}|#[0-9a-fA-F]{6})\s*;?\s*$/i', $styleMatch[2], $colorMatch)) {
            return '';
        }

        return strtoupper($colorMatch[1]);
    }

    private function sanitizeDatetime($value)
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        $ts = strtotime($value);

        return ($ts === false) ? '' : date('Y-m-d H:i:s', $ts);
    }

    private function sanitizeColor($value, $fallback)
    {
        $value = trim((string) $value);

        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) ? strtoupper($value) : $fallback;
    }

    private function localizeStorefrontLink($link)
    {
        $link = trim((string) $link);
        if ($link === '') {
            return $link;
        }

        $path = (string) parse_url($link, PHP_URL_PATH);
        if ($path === '') {
            $path = $link;
        }

        if (preg_match('#/content/(\d+)#', $path, $m)) {
            return $this->context->link->getCMSLink((int) $m[1]);
        }
        if (preg_match('#/(\d+)-#', $path, $m)) {
            return $this->context->link->getCategoryLink((int) $m[1]);
        }
        if (preg_match('#/module/([^/]+)/([^/?]+)#', $path, $m)) {
            return $this->context->link->getModuleLink($m[1], $m[2]);
        }

        return $link;
    }
}
