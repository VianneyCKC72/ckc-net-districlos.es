<?php
/**
 * Images JPEG pour flux marketplace (Lengow / Mirakl) tout en conservant le WebP boutique.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/MarketplaceImageService.php';
require_once __DIR__ . '/classes/MarketplaceImageUrlProvider.php';
require_once __DIR__ . '/classes/MarketplaceDescriptionRepository.php';
require_once __DIR__ . '/classes/MarketplaceCarrierResolver.php';

class Dtc_lengow_marketplace extends Module
{
    /** @var bool Guard pour eviter les sauvegardes en chaine declenchees par d'autres modules (ex: pm_advancedpack). */
    private static $marketplaceDescriptionSaved = false;

    public function __construct()
    {
        $this->name = 'dtc_lengow_marketplace';
        $this->tab = 'export';
        $this->version = '1.3.0';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Districlos - Marketplace Lengow');
        $this->description = $this->l('Expose des donnees dediees aux marketplaces pour Lengow.');
        $this->ps_versions_compliancy = ['min' => '1.7.5.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionObjectImageAddAfter')
            && $this->registerHook('actionObjectImageUpdateAfter')
            && $this->registerHook('actionObjectImageDeleteAfter')
            && $this->registerHook('displayAdminProductsMainStepLeftColumnBottom')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionObjectProductDeleteAfter')
            && $this->ensureCacheDirectory()
            && $this->installCarrierConfiguration();
    }

    public function uninstall()
    {
        foreach (array_keys($this->getCarrierConfigurationDefaults()) as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    /**
     * Valeurs par defaut du mapping transporteur marketplace (desactive jusqu'a validation en BO).
     *
     * @return array<string, string>
     */
    public function getCarrierConfigurationDefaults()
    {
        return [
            MarketplaceCarrierResolver::CONF_ENABLED => '0',
            MarketplaceCarrierResolver::CONF_CARRIER_GATE => '1584',
            MarketplaceCarrierResolver::CONF_CARRIER_WICKET => '1582',
            MarketplaceCarrierResolver::CONF_CARRIER_OTHER => '1096',
            MarketplaceCarrierResolver::CONF_PRIORITY => MarketplaceCarrierResolver::DEFAULT_PRIORITY,
            MarketplaceCarrierResolver::CONF_PRODUCTS_GATE => '',
            MarketplaceCarrierResolver::CONF_PRODUCTS_WICKET => '',
            MarketplaceCarrierResolver::CONF_PRODUCTS_OTHER => '',
        ];
    }

    public function installCarrierConfiguration()
    {
        foreach ($this->getCarrierConfigurationDefaults() as $key => $value) {
            if (!Configuration::hasKey($key)) {
                Configuration::updateValue($key, $value);
            }
        }

        return true;
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDtcMarketplaceCarriers')) {
            $errors = $this->saveCarrierConfiguration();
            if (!empty($errors)) {
                $output .= $this->displayError(implode('<br />', $errors));
            } else {
                $output .= $this->displayConfirmation(
                    $this->trans('Configuration des transporteurs marketplace enregistree.', [], 'Modules.Dtclengowmarketplace.Admin')
                );
            }
        }

        return $output . $this->renderCarrierConfigurationForm();
    }

    /**
     * @return string[] messages d'erreur, vide si la sauvegarde a eu lieu
     */
    private function saveCarrierConfiguration()
    {
        $errors = [];
        $carrierReferences = $this->getAvailableCarrierReferences();

        $carrierValues = [];
        foreach ($this->getCarrierFieldLabels() as $key => $label) {
            $idReference = (int) Tools::getValue($key);
            if ($idReference > 0 && !isset($carrierReferences[$idReference])) {
                $errors[] = $this->trans(
                    'Le transporteur selectionne pour "%field%" n\'est plus actif.',
                    ['%field%' => $label],
                    'Modules.Dtclengowmarketplace.Admin'
                );
                continue;
            }
            $carrierValues[$key] = (string) $idReference;
        }

        $productValues = [];
        foreach (array_keys($this->getProductFieldLabels()) as $key) {
            $selected = Tools::getValue($key, []);
            $productValues[$key] = MarketplaceCarrierResolver::formatIdList(is_array($selected) ? $selected : []);
        }

        $priority = (string) Tools::getValue(MarketplaceCarrierResolver::CONF_PRIORITY);
        $allowedPriorities = [];
        foreach ($this->getPriorityPermutations() as $permutation) {
            $allowedPriorities[] = implode(',', $permutation);
        }
        if (!in_array($priority, $allowedPriorities, true)) {
            $errors[] = $this->trans(
                'L\'ordre de priorite selectionne est invalide.',
                [],
                'Modules.Dtclengowmarketplace.Admin'
            );
        }

        $enabled = (bool) Tools::getValue(MarketplaceCarrierResolver::CONF_ENABLED);
        if ($enabled) {
            foreach ($this->getCarrierFieldLabels() as $key => $label) {
                if (empty($carrierValues[$key])) {
                    $errors[] = $this->trans(
                        'Le transporteur "%field%" est obligatoire lorsque la regle est activee.',
                        ['%field%' => $label],
                        'Modules.Dtclengowmarketplace.Admin'
                    );
                }
            }
            if (implode('', $productValues) === '') {
                $errors[] = $this->trans(
                    'Selectionnez au moins un produit lorsque la regle est activee.',
                    [],
                    'Modules.Dtclengowmarketplace.Admin'
                );
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        Configuration::updateValue(MarketplaceCarrierResolver::CONF_ENABLED, $enabled ? '1' : '0');
        Configuration::updateValue(MarketplaceCarrierResolver::CONF_PRIORITY, $priority);
        foreach ($carrierValues as $key => $value) {
            Configuration::updateValue($key, $value);
        }
        foreach ($productValues as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private function getCarrierFieldLabels()
    {
        return [
            MarketplaceCarrierResolver::CONF_CARRIER_GATE => $this->trans('Portail ou pack portail + portillon', [], 'Modules.Dtclengowmarketplace.Admin'),
            MarketplaceCarrierResolver::CONF_CARRIER_WICKET => $this->trans('Portillon seul', [], 'Modules.Dtclengowmarketplace.Admin'),
            MarketplaceCarrierResolver::CONF_CARRIER_OTHER => $this->trans('Autres produits', [], 'Modules.Dtclengowmarketplace.Admin'),
        ];
    }

    /**
     * @return string
     */
    private function renderCarrierConfigurationForm()
    {
        $carriers = [];
        foreach ($this->getAvailableCarrierReferences() as $idReference => $name) {
            $carriers[] = [
                'id' => $idReference,
                'name' => $name . ' (ref. ' . $idReference . ')',
            ];
        }

        $carrierFields = [];
        foreach ($this->getCarrierFieldLabels() as $key => $label) {
            $carrierFields[] = [
                'key' => $key,
                'label' => $label,
                'value' => (int) Configuration::get($key),
            ];
        }

        $productHelp = [
            MarketplaceCarrierResolver::CONF_PRODUCTS_GATE => $this->trans(
                'Lister aussi les packs portail + portillon : la marketplace commande le SKU du pack, pas ses composants.',
                [],
                'Modules.Dtclengowmarketplace.Admin'
            ),
            MarketplaceCarrierResolver::CONF_PRODUCTS_WICKET => '',
            MarketplaceCarrierResolver::CONF_PRODUCTS_OTHER => $this->trans(
                'Facultatif : un produit absent des trois listes est deja traite comme "Autres produits". A remplir uniquement pour rendre la regle explicite dans l\'ordre de priorite.',
                [],
                'Modules.Dtclengowmarketplace.Admin'
            ),
        ];

        $productFields = [];
        foreach ($this->getProductFieldLabels() as $key => $label) {
            $selected = MarketplaceCarrierResolver::getConfiguredProductIds($key);
            $productFields[] = [
                'key' => $key,
                'label' => $label,
                'help' => $productHelp[$key],
                // indexe par id : le template teste l'appartenance sans reboucler sur la selection
                'selected' => array_fill_keys($selected, true),
            ];
        }

        // hors contexte AdminModules, on laisse le formulaire poster sur l'URL courante
        $formAction = AdminController::$currentIndex
            ? AdminController::$currentIndex . '&configure=' . $this->name
                . '&token=' . Tools::getAdminTokenLite('AdminModules')
            : '';

        $this->context->smarty->assign([
            'form_action' => $formAction,
            'keys' => [
                'enabled' => MarketplaceCarrierResolver::CONF_ENABLED,
                'priority' => MarketplaceCarrierResolver::CONF_PRIORITY,
            ],
            'values' => [
                'enabled' => (bool) Configuration::get(MarketplaceCarrierResolver::CONF_ENABLED),
                'priority' => implode(',', MarketplaceCarrierResolver::getPriority()),
            ],
            'carriers' => $carriers,
            'carrier_fields' => $carrierFields,
            'priorities' => $this->getPriorityOptions(),
            'products' => $this->getProductOptions(),
            'product_fields' => $productFields,
            'labels' => [
                'legend' => $this->trans('Transporteur des commandes marketplace', [], 'Modules.Dtclengowmarketplace.Admin'),
                'enabled' => $this->trans('Activer la regle transporteur', [], 'Modules.Dtclengowmarketplace.Admin'),
                'enabled_help' => $this->trans(
                    'Lorsque la regle est desactivee, Lengow applique son propre transporteur par defaut.',
                    [],
                    'Modules.Dtclengowmarketplace.Admin'
                ),
                'priority' => $this->trans('Ordre de priorite', [], 'Modules.Dtclengowmarketplace.Admin'),
                'priority_help' => $this->trans(
                    'Determine le transporteur retenu quand une commande melange plusieurs types de produits.',
                    [],
                    'Modules.Dtclengowmarketplace.Admin'
                ),
                'product_placeholder' => $this->trans('Rechercher un produit...', [], 'Modules.Dtclengowmarketplace.Admin'),
                'none' => $this->trans('-- Aucun --', [], 'Modules.Dtclengowmarketplace.Admin'),
                'yes' => $this->trans('Oui', [], 'Admin.Global'),
                'no' => $this->trans('Non', [], 'Admin.Global'),
                'save' => $this->trans('Enregistrer', [], 'Admin.Actions'),
            ],
        ]);

        return $this->context->smarty->fetch(
            $this->local_path . 'views/templates/admin/carrier_configuration.tpl'
        );
    }

    /**
     * Transporteurs actifs de la boutique, indexes par id_reference (stable entre les editions).
     *
     * @return array<int, string>
     */
    private function getAvailableCarrierReferences()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT c.`id_reference`, c.`name`
            FROM `' . _DB_PREFIX_ . 'carrier` c
            INNER JOIN `' . _DB_PREFIX_ . 'carrier_shop` cs ON cs.`id_carrier` = c.`id_carrier`
            WHERE c.`deleted` = 0 AND c.`active` = 1 AND cs.`id_shop` = ' . (int) $this->getCurrentShopId() . '
            ORDER BY c.`id_carrier` DESC'
        );

        $carriers = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $idReference = (int) $row['id_reference'];
                if (!isset($carriers[$idReference])) {
                    $carriers[$idReference] = trim((string) $row['name']);
                }
            }
        }

        asort($carriers);

        return $carriers;
    }

    /**
     * @return array<string, string>
     */
    private function getProductFieldLabels()
    {
        return [
            MarketplaceCarrierResolver::CONF_PRODUCTS_GATE => $this->trans('Produits "portail"', [], 'Modules.Dtclengowmarketplace.Admin'),
            MarketplaceCarrierResolver::CONF_PRODUCTS_WICKET => $this->trans('Produits "portillon"', [], 'Modules.Dtclengowmarketplace.Admin'),
            MarketplaceCarrierResolver::CONF_PRODUCTS_OTHER => $this->trans('Produits "autres"', [], 'Modules.Dtclengowmarketplace.Admin'),
        ];
    }

    /**
     * Les 6 ordres possibles, pour eviter toute priorite incomplete ou en doublon.
     *
     * @return array<int, array<string, string>>
     */
    private function getPriorityOptions()
    {
        $labels = [];
        foreach ($this->getCarrierFieldLabels() as $key => $label) {
            $labels[$key] = $label;
        }
        $typeLabels = [
            MarketplaceCarrierResolver::TYPE_GATE => $labels[MarketplaceCarrierResolver::CONF_CARRIER_GATE],
            MarketplaceCarrierResolver::TYPE_WICKET => $labels[MarketplaceCarrierResolver::CONF_CARRIER_WICKET],
            MarketplaceCarrierResolver::TYPE_OTHER => $labels[MarketplaceCarrierResolver::CONF_CARRIER_OTHER],
        ];

        $options = [];
        foreach ($this->getPriorityPermutations() as $permutation) {
            $names = [];
            foreach ($permutation as $type) {
                $names[] = $typeLabels[$type];
            }
            $options[] = [
                'id' => implode(',', $permutation),
                'name' => implode(' > ', $names),
            ];
        }

        return $options;
    }

    /**
     * @return array<int, string[]>
     */
    private function getPriorityPermutations()
    {
        $types = MarketplaceCarrierResolver::getTypes();
        $permutations = [];
        foreach ($types as $first) {
            foreach ($types as $second) {
                if ($second === $first) {
                    continue;
                }
                foreach ($types as $third) {
                    if ($third === $first || $third === $second) {
                        continue;
                    }
                    $permutations[] = [$first, $second, $third];
                }
            }
        }

        return $permutations;
    }

    /**
     * Produits proposes dans les listes. Les produits desactives deja selectionnes sont conserves,
     * sinon les retirer de la liste effacerait la configuration a la sauvegarde.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getProductOptions()
    {
        $configured = [];
        foreach (array_keys($this->getProductFieldLabels()) as $key) {
            $configured = array_merge($configured, MarketplaceCarrierResolver::getConfiguredProductIds($key));
        }

        $where = 'ps.`active` = 1';
        if (!empty($configured)) {
            $where .= ' OR p.`id_product` IN (' . implode(',', array_unique($configured)) . ')';
        }

        $rows = Db::getInstance()->executeS(
            'SELECT p.`id_product`, p.`reference`, ps.`active`, pl.`name`
            FROM `' . _DB_PREFIX_ . 'product` p
            INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                ON ps.`id_product` = p.`id_product`
                AND ps.`id_shop` = ' . (int) $this->getCurrentShopId() . '
            INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                ON pl.`id_product` = p.`id_product`
                AND pl.`id_lang` = ' . (int) $this->context->language->id . '
                AND pl.`id_shop` = ' . (int) $this->getCurrentShopId() . '
            WHERE ' . $where . '
            ORDER BY pl.`name` ASC'
        );

        $options = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $name = $row['name'];
                if (trim((string) $row['reference']) !== '') {
                    $name .= ' — ' . $row['reference'];
                }
                $name .= ' [' . (int) $row['id_product'] . ']';
                if (!(int) $row['active']) {
                    $name .= ' ' . $this->trans('(desactive)', [], 'Modules.Dtclengowmarketplace.Admin');
                }
                $options[] = [
                    'id' => (int) $row['id_product'],
                    'name' => $name,
                ];
            }
        }

        return $options;
    }

    public function hookActionObjectImageAddAfter($params)
    {
        $this->purgeImageCache($params);
    }

    public function hookActionObjectImageUpdateAfter($params)
    {
        $this->purgeImageCache($params);
    }

    public function hookActionObjectImageDeleteAfter($params)
    {
        $this->purgeImageCache($params);
    }

    public function hookDisplayAdminProductsMainStepLeftColumnBottom($params)
    {
        return $this->renderMarketplaceDescriptionBlock($params);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return string
     */
    private function renderMarketplaceDescriptionBlock($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : (int) Tools::getValue('id_product');
        $idShop = $this->getCurrentShopId();
        $languages = Language::getLanguages(false);
        $repository = new MarketplaceDescriptionRepository();
        $descriptions = $idProduct > 0 ? $repository->getForProduct($idProduct, $idShop) : [];
        $tableMissing = !MarketplaceDescriptionRepository::tableExists();

        $html = '<div class="card mt-2" id="dtc-marketplace-description-block" data-id-product="' . (int) $idProduct . '">';
        $html .= '<h3 class="card-header">Description Marketplace</h3>';
        $html .= '<div class="card-body">';

        if ($tableMissing) {
            $html .= '<div class="alert alert-warning">'
                . 'La table <code>' . _DB_PREFIX_ . MarketplaceDescriptionRepository::TABLE_NAME . '</code>'
                . ' n\'existe pas encore. Crees-la en base avant d\'enregistrer ce champ.'
                . '</div>';
        }

        $html .= '<p class="text-muted">'
            . 'Ce texte est reserve aux exports marketplace / Lengow et ne modifie pas la description produit de la boutique.'
            . '</p>';

        if ($idProduct <= 0) {
            $html .= '<div class="alert alert-info">'
                . 'Enregistrez le produit une premiere fois pour pouvoir saisir la description marketplace.'
                . '</div>';
        } else {
            foreach ($languages as $language) {
                $idLang = (int) $language['id_lang'];
                $iso = isset($language['iso_code']) ? (string) $language['iso_code'] : '';
                $value = isset($descriptions[$idLang]) ? (string) $descriptions[$idLang] : '';

                $html .= '<div class="form-group">';
                $html .= '<label for="dtc_description_marketplace_' . $idLang . '">'
                    . 'Description Marketplace (' . htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') . ')'
                    . '</label>';
                $html .= '<textarea'
                    . ' id="dtc_description_marketplace_' . $idLang . '"'
                    . ' name="dtc_description_marketplace[' . $idLang . ']"'
                    . ' class="form-control"'
                    . ' rows="6">'
                    . htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                    . '</textarea>';
                $html .= '</div>';
            }
        }

        $html .= '</div></div>';

        return $html;
    }

    public function hookActionProductAdd($params)
    {
        $this->saveMarketplaceDescription($params);
    }

    public function hookActionProductUpdate($params)
    {
        $this->saveMarketplaceDescription($params);
    }

    public function hookActionObjectProductDeleteAfter($params)
    {
        if (!isset($params['object']) || !($params['object'] instanceof Product)) {
            return;
        }

        $repository = new MarketplaceDescriptionRepository();
        $repository->deleteForProduct((int) $params['object']->id);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function saveMarketplaceDescription($params)
    {
        if (self::$marketplaceDescriptionSaved) {
            return;
        }

        if (!Tools::getIsset('dtc_description_marketplace')) {
            return;
        }

        $values = Tools::getValue('dtc_description_marketplace', []);
        if (!is_array($values)) {
            return;
        }

        $idProductFromHook = 0;
        if (isset($params['product']) && $params['product'] instanceof Product) {
            $idProductFromHook = (int) $params['product']->id;
        } elseif (isset($params['id_product'])) {
            $idProductFromHook = (int) $params['id_product'];
        }

        $idProductFromRequest = (int) Tools::getValue('id_product');

        if ($idProductFromRequest > 0
            && $idProductFromHook > 0
            && $idProductFromRequest !== $idProductFromHook
        ) {
            return;
        }

        $idProduct = $idProductFromRequest > 0 ? $idProductFromRequest : $idProductFromHook;
        if ($idProduct <= 0) {
            return;
        }

        $repository = new MarketplaceDescriptionRepository();
        $repository->saveForProduct($idProduct, $this->getCurrentShopId(), $values);

        self::$marketplaceDescriptionSaved = true;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function purgeImageCache($params)
    {
        if (!isset($params['object']) || !($params['object'] instanceof Image)) {
            return;
        }

        MarketplaceImageService::purgeCacheForImage((int) $params['object']->id);
    }

    private function ensureCacheDirectory()
    {
        $dir = MarketplaceImageService::getCacheDirectory();

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                return false;
            }
        }

        $index = $dir . 'index.php';
        if (!file_exists($index)) {
            @file_put_contents($index, "<?php\nheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');\nheader('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');\nheader('Cache-Control: no-store, no-cache, must-revalidate');\nheader('Cache-Control: post-check=0, pre-check=0', false);\nheader('Pragma: no-cache');\nheader('Location: ../');\nexit;\n");
        }

        return true;
    }

    /**
     * @return int
     */
    private function getCurrentShopId()
    {
        $idShop = (int) $this->context->shop->id;

        return $idShop > 0 ? $idShop : (int) Configuration::get('PS_SHOP_DEFAULT');
    }
}
