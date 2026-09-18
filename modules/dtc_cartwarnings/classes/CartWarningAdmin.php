<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CartWarningAdmin
{
    /** @var dtc_cartwarnings */
    private $module;

    public function __construct(dtc_cartwarnings $module)
    {
        $this->module = $module;
    }

    public function render()
    {
        $output = '';

        if (Tools::isSubmit('submitDtcCartWarningRule')) {
            $errors = $this->processRuleForm();
            if (empty($errors)) {
                CartWarningRuleRepository::clearCache();
                $output .= $this->module->displayConfirmation($this->module->l('Règle enregistrée.'));
            } else {
                foreach ($errors as $error) {
                    $output .= $this->module->displayError($error);
                }
            }
        }

        if (Tools::isSubmit('submitDtcCartWarningChild')) {
            $errors = $this->processChildRuleForm();
            if (empty($errors)) {
                CartWarningRuleRepository::clearCache();
                $output .= $this->module->displayConfirmation($this->module->l('Produit manquant enregistré.'));
            } else {
                foreach ($errors as $error) {
                    $output .= $this->module->displayError($error);
                }
            }
        }

        if (Tools::isSubmit('deletedtc_cart_warning_rule')) {
            $rule = new CartWarningRule((int) Tools::getValue('id_rule'));
            if (Validate::isLoadedObject($rule) && $rule->delete()) {
                CartWarningRuleRepository::clearCache();
                $output .= $this->module->displayConfirmation($this->module->l('Règle supprimée.'));
            }
        }

        if (Tools::isSubmit('deletedtc_cart_warning_child')) {
            $child = new CartWarningRule((int) Tools::getValue('id_rule'));
            if (Validate::isLoadedObject($child) && (int) $child->id_parent > 0 && $child->delete()) {
                CartWarningRuleRepository::clearCache();
                $output .= $this->module->displayConfirmation($this->module->l('Produit manquant supprimé.'));
            }
        }

        if (Tools::isSubmit('statusdtc_cart_warning_rule')) {
            $rule = new CartWarningRule((int) Tools::getValue('id_rule'));
            if (Validate::isLoadedObject($rule)) {
                $rule->active = !(bool) $rule->active;
                $rule->update();
                CartWarningRuleRepository::clearCache();
                $output .= $this->module->displayConfirmation($this->module->l('Statut mis à jour.'));
            }
        }

        if (Tools::getValue('addRuleType') || Tools::isSubmit('updatedtc_cart_warning_rule')) {
            $output .= $this->renderRuleForm();
            if ((int) Tools::getValue('id_rule') > 0) {
                $rule = new CartWarningRule((int) Tools::getValue('id_rule'));
                if (Validate::isLoadedObject($rule) && $rule->rule_type === CartWarningRule::TYPE_MISSING_MULTIPLE) {
                    $output .= $this->renderChildrenSection($rule);
                }
            }
        } else {
            $output .= $this->renderRulesToolbar();
            $output .= $this->renderRulesList();
        }

        return $output;
    }

    private function renderRulesToolbar()
    {
        $base = Context::getContext()->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name
            . '&tab_module=' . $this->module->tab
            . '&module_name=' . $this->module->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $types = [
            CartWarningRule::TYPE_INCOMPATIBILITY => $this->module->l('Incompatibilité'),
            CartWarningRule::TYPE_MISSING_SIMPLE => $this->module->l('Produit manquant'),
            CartWarningRule::TYPE_MISSING_MULTIPLE => $this->module->l('Produits manquants multiples'),
        ];

        $html = '<div class="panel"><div class="panel-heading">' . $this->module->l('Ajouter une règle') . '</div><div class="panel-body">';
        foreach ($types as $type => $label) {
            $html .= '<a class="btn btn-default" style="margin-right:8px;margin-bottom:8px;" href="'
                . $base . '&addRuleType=' . urlencode($type) . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
        }
        $html .= '</div></div>';

        return $html;
    }

    private function renderRulesList()
    {
        $rules = CartWarningRuleRepository::getTopLevelRulesForList();
        foreach ($rules as &$rule) {
            $rule['rule_type_label'] = $this->getRuleTypeLabel($rule['rule_type']);
            $rule['message_preview'] = Tools::substr(strip_tags((string) $rule['message_main']), 0, 80);
        }
        unset($rule);

        $fieldsList = [
            'id_rule' => ['title' => $this->module->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'rule_type_label' => ['title' => $this->module->l('Type'), 'orderby' => false, 'search' => false],
            'position' => ['title' => $this->module->l('Position'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'message_preview' => ['title' => $this->module->l('Message'), 'orderby' => false, 'search' => false],
            'active' => ['title' => $this->module->l('Actif'), 'active' => 'status', 'type' => 'bool', 'align' => 'center', 'class' => 'fixed-width-sm'],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->actions = ['edit', 'delete'];
        $helper->identifier = 'id_rule';
        $helper->show_toolbar = false;
        $helper->title = $this->module->l('Règles d\'avertissement panier');
        $helper->no_link = true;
        $helper->module = $this->module;
        $helper->listTotal = count($rules);
        $helper->table = 'dtc_cart_warning_rule';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = Context::getContext()->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name
            . '&tab_module=' . $this->module->tab
            . '&module_name=' . $this->module->name;

        return $helper->generateList($rules, $fieldsList);
    }

    private function renderRuleForm()
    {
        $idRule = (int) Tools::getValue('id_rule');
        $ruleType = Tools::getValue('addRuleType');
        $rule = new CartWarningRule($idRule);

        if ($idRule > 0 && Validate::isLoadedObject($rule)) {
            $ruleType = $rule->rule_type;
        }

        if (!in_array($ruleType, [
            CartWarningRule::TYPE_INCOMPATIBILITY,
            CartWarningRule::TYPE_MISSING_SIMPLE,
            CartWarningRule::TYPE_MISSING_MULTIPLE,
        ], true)) {
            return $this->module->displayError($this->module->l('Type de règle invalide.'));
        }

        $products = $idRule > 0 ? CartWarningRule::getProductIdsByRole($idRule) : [
            CartWarningRule::ROLE_TRIGGER => [],
            CartWarningRule::ROLE_ALL_OF => [],
            CartWarningRule::ROLE_ANY_OF => [],
            CartWarningRule::ROLE_MESSAGE_ON => [],
        ];

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->getRuleTypeLabel($ruleType),
                ],
                'input' => $this->getRuleFormInputs($ruleType),
                'submit' => [
                    'title' => $this->module->l('Enregistrer'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = 'dtc_cart_warning_rule';
        $helper->module = $this->module;
        $helper->default_form_language = (int) Context::getContext()->language->id;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->identifier = 'id_rule';
        $helper->submit_action = 'submitDtcCartWarningRule';
        $helper->currentIndex = Context::getContext()->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name
            . '&tab_module=' . $this->module->tab
            . '&module_name=' . $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getRuleFormValues($rule, $ruleType, $products),
        ];

        if ($idRule <= 0) {
            $helper->fields_value['rule_type'] = $ruleType;
        }

        return $helper->generateForm([$fieldsForm]);
    }

    private function getRuleFormInputs($ruleType)
    {
        $common = [
            [
                'type' => 'hidden',
                'name' => 'rule_type',
            ],
            [
                'type' => 'switch',
                'label' => $this->module->l('Actif'),
                'name' => 'active',
                'is_bool' => true,
                'values' => [
                    ['id' => 'active_on', 'value' => 1, 'label' => $this->module->l('Oui')],
                    ['id' => 'active_off', 'value' => 0, 'label' => $this->module->l('Non')],
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->module->l('Position'),
                'name' => 'position',
                'class' => 'fixed-width-xs',
                'desc' => $this->module->l('Ordre d\'évaluation (0 = en premier). Les incompatibilités s\'arrêtent à la première règle correspondante.'),
            ],
        ];

        if ($ruleType === CartWarningRule::TYPE_INCOMPATIBILITY) {
            return array_merge($common, [
                [
                    'type' => 'text',
                    'label' => $this->module->l('Produits requis (tous)'),
                    'name' => 'all_of_products',
                    'desc' => $this->module->l('IDs produits séparés par des virgules.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Produits requis (au moins un)'),
                    'name' => 'any_of_products',
                    'desc' => $this->module->l('IDs produits séparés par des virgules.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Produits affichant le message'),
                    'name' => 'message_on_products',
                    'desc' => $this->module->l('IDs produits séparés par des virgules.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Produit pour contrôle d\'attribut'),
                    'name' => 'attribute_product_id',
                    'class' => 'fixed-width-xs',
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Texte recherché dans les attributs'),
                    'name' => 'attribute_contains',
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Message'),
                    'name' => 'message_main',
                    'lang' => true,
                    'rows' => 4,
                    'required' => true,
                ],
            ]);
        }

        if ($ruleType === CartWarningRule::TYPE_MISSING_SIMPLE) {
            return array_merge($common, [
                [
                    'type' => 'text',
                    'label' => $this->module->l('Produits déclencheurs'),
                    'name' => 'trigger_products',
                    'desc' => $this->module->l('IDs produits séparés par des virgules.'),
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Produit manquant'),
                    'name' => 'missing_product_id',
                    'class' => 'fixed-width-xs',
                    'required' => true,
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Message principal'),
                    'name' => 'message_main',
                    'lang' => true,
                    'rows' => 3,
                    'required' => true,
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Message complémentaire'),
                    'name' => 'message_extra',
                    'lang' => true,
                    'rows' => 2,
                ],
            ]);
        }

        return array_merge($common, [
            [
                'type' => 'text',
                'label' => $this->module->l('Produit déclencheur'),
                'name' => 'trigger_products',
                'desc' => $this->module->l('ID produit déclencheur.'),
                'required' => true,
            ],
            [
                'type' => 'free',
                'name' => 'children_help',
                'desc' => $this->module->l('Enregistrez la règle puis ajoutez les produits manquants associés ci-dessous.'),
            ],
        ]);
    }

    private function getRuleFormValues(CartWarningRule $rule, $ruleType, array $products)
    {
        $values = [
            'id_rule' => (int) $rule->id,
            'rule_type' => $ruleType,
            'active' => (int) Tools::getValue('active', $rule->id ? (int) $rule->active : 1),
            'position' => (int) Tools::getValue('position', $rule->id ? (int) $rule->position : 0),
            'all_of_products' => Tools::getValue('all_of_products', CartWarningRule::formatProductCsv($products[CartWarningRule::ROLE_ALL_OF])),
            'any_of_products' => Tools::getValue('any_of_products', CartWarningRule::formatProductCsv($products[CartWarningRule::ROLE_ANY_OF])),
            'message_on_products' => Tools::getValue('message_on_products', CartWarningRule::formatProductCsv($products[CartWarningRule::ROLE_MESSAGE_ON])),
            'trigger_products' => Tools::getValue('trigger_products', CartWarningRule::formatProductCsv($products[CartWarningRule::ROLE_TRIGGER])),
            'missing_product_id' => (int) Tools::getValue('missing_product_id', (int) $rule->missing_product_id),
            'attribute_product_id' => (int) Tools::getValue('attribute_product_id', (int) $rule->attribute_product_id),
            'attribute_contains' => Tools::getValue('attribute_contains', (string) $rule->attribute_contains),
            'children_help' => '',
        ];

        foreach (Language::getLanguages(false) as $language) {
            $idLang = (int) $language['id_lang'];
            $messageMain = is_array($rule->message_main) ? $rule->message_main : [];
            $messageExtra = is_array($rule->message_extra) ? $rule->message_extra : [];
            $values['message_main'][$idLang] = Tools::getValue(
                'message_main_' . $idLang,
                isset($messageMain[$idLang]) ? $messageMain[$idLang] : ''
            );
            $values['message_extra'][$idLang] = Tools::getValue(
                'message_extra_' . $idLang,
                isset($messageExtra[$idLang]) ? $messageExtra[$idLang] : ''
            );
        }

        return $values;
    }

    private function processRuleForm()
    {
        $errors = [];
        $idRule = (int) Tools::getValue('id_rule');
        $ruleType = (string) Tools::getValue('rule_type');
        $rule = new CartWarningRule($idRule);

        if ($idRule > 0 && !Validate::isLoadedObject($rule)) {
            return [$this->module->l('Règle introuvable.')];
        }

        if ($idRule <= 0) {
            $rule = new CartWarningRule();
            $rule->id_parent = null;
            $rule->rule_type = $ruleType;
        }

        $rule->active = (bool) Tools::getValue('active');
        $rule->position = (int) Tools::getValue('position');
        $rule->attribute_product_id = (int) Tools::getValue('attribute_product_id') ?: null;
        $rule->attribute_contains = trim((string) Tools::getValue('attribute_contains')) ?: null;

        foreach (Language::getLanguages(false) as $language) {
            $idLang = (int) $language['id_lang'];
            $rule->message_main[$idLang] = trim((string) Tools::getValue('message_main_' . $idLang));
            $rule->message_extra[$idLang] = trim((string) Tools::getValue('message_extra_' . $idLang));
        }

        if ($ruleType === CartWarningRule::TYPE_MISSING_SIMPLE) {
            $rule->missing_product_id = (int) Tools::getValue('missing_product_id');
            if ($rule->missing_product_id <= 0) {
                $errors[] = $this->module->l('Le produit manquant est obligatoire.');
            }
        } elseif ($ruleType === CartWarningRule::TYPE_MISSING_MULTIPLE) {
            $rule->missing_product_id = null;
            if (empty($rule->message_main[(int) Configuration::get('PS_LANG_DEFAULT')])) {
                $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
                $rule->message_main[$defaultLang] = '-';
            }
        } else {
            $rule->missing_product_id = null;
        }

        if (!empty($errors)) {
            return $errors;
        }

        $saved = $idRule > 0 ? $rule->update() : $rule->add();
        if (!$saved) {
            return [$this->module->l('Impossible d\'enregistrer la règle.')];
        }

        $productsByRole = [];
        if ($ruleType === CartWarningRule::TYPE_INCOMPATIBILITY) {
            $productsByRole = [
                CartWarningRule::ROLE_ALL_OF => CartWarningRule::parseProductCsv(Tools::getValue('all_of_products')),
                CartWarningRule::ROLE_ANY_OF => CartWarningRule::parseProductCsv(Tools::getValue('any_of_products')),
                CartWarningRule::ROLE_MESSAGE_ON => CartWarningRule::parseProductCsv(Tools::getValue('message_on_products')),
            ];
        } elseif (in_array($ruleType, [CartWarningRule::TYPE_MISSING_SIMPLE, CartWarningRule::TYPE_MISSING_MULTIPLE], true)) {
            $productsByRole = [
                CartWarningRule::ROLE_TRIGGER => CartWarningRule::parseProductCsv(Tools::getValue('trigger_products')),
            ];
        }
        CartWarningRule::saveProductIdsByRole((int) $rule->id, $productsByRole);

        Tools::redirectAdmin(
            Context::getContext()->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name
            . '&tab_module=' . $this->module->tab
            . '&module_name=' . $this->module->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules')
            . '&conf=4'
            . ($ruleType === CartWarningRule::TYPE_MISSING_MULTIPLE ? '&updatedtc_cart_warning_rule&id_rule=' . (int) $rule->id : '')
        );

        return $errors;
    }

    private function processChildRuleForm()
    {
        $errors = [];
        $idParent = (int) Tools::getValue('id_parent');
        $idChild = (int) Tools::getValue('id_child');
        $parent = new CartWarningRule($idParent);

        if (!Validate::isLoadedObject($parent) || $parent->rule_type !== CartWarningRule::TYPE_MISSING_MULTIPLE) {
            return [$this->module->l('Règle parente invalide.')];
        }

        $child = new CartWarningRule($idChild);
        if ($idChild <= 0) {
            $child = new CartWarningRule();
            $child->id_parent = $idParent;
            $child->rule_type = CartWarningRule::TYPE_MISSING_ITEM;
            $child->position = count(CartWarningRule::getChildren($idParent));
        }

        $child->active = true;
        $child->missing_product_id = (int) Tools::getValue('child_missing_product_id');
        if ($child->missing_product_id <= 0) {
            $errors[] = $this->module->l('Le produit manquant est obligatoire.');
        }

        foreach (Language::getLanguages(false) as $language) {
            $idLang = (int) $language['id_lang'];
            $child->message_main[$idLang] = trim((string) Tools::getValue('child_message_main_' . $idLang));
            $child->message_extra[$idLang] = trim((string) Tools::getValue('child_message_extra_' . $idLang));
        }

        if (!empty($errors)) {
            return $errors;
        }

        $saved = $idChild > 0 ? $child->update() : $child->add();
        if (!$saved) {
            return [$this->module->l('Impossible d\'enregistrer le produit manquant.')];
        }

        Tools::redirectAdmin(
            Context::getContext()->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name
            . '&tab_module=' . $this->module->tab
            . '&module_name=' . $this->module->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules')
            . '&updatedtc_cart_warning_rule&id_rule=' . $idParent
            . '&conf=4'
        );

        return $errors;
    }

    private function renderChildrenSection(CartWarningRule $parent)
    {
        $children = CartWarningRule::getChildren((int) $parent->id);
        $idChild = (int) Tools::getValue('edit_child');
        $child = new CartWarningRule($idChild);

        $html = '<div class="panel"><div class="panel-heading">' . $this->module->l('Produits manquants associés') . '</div>';
        $html .= '<table class="table"><thead><tr>';
        $html .= '<th>' . $this->module->l('ID') . '</th><th>' . $this->module->l('Produit manquant') . '</th><th>' . $this->module->l('Message') . '</th><th></th>';
        $html .= '</tr></thead><tbody>';

        foreach ($children as $item) {
            $idLang = (int) Context::getContext()->language->id;
            $message = isset($item->message_main[$idLang]) ? $item->message_main[$idLang] : '';
            $base = Context::getContext()->link->getAdminLink('AdminModules', false)
                . '&configure=' . $this->module->name
                . '&tab_module=' . $this->module->tab
                . '&module_name=' . $this->module->name
                . '&token=' . Tools::getAdminTokenLite('AdminModules')
                . '&updatedtc_cart_warning_rule&id_rule=' . (int) $parent->id;

            $html .= '<tr>';
            $html .= '<td>' . (int) $item->id . '</td>';
            $html .= '<td>' . (int) $item->missing_product_id . '</td>';
            $html .= '<td>' . htmlspecialchars(Tools::substr($message, 0, 80), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td class="text-right">';
            $html .= '<a class="btn btn-default" href="' . $base . '&edit_child=' . (int) $item->id . '"><i class="icon-edit"></i></a> ';
            $html .= '<a class="btn btn-default" href="' . $base . '&deletedtc_cart_warning_child=1&id_rule=' . (int) $item->id . '" onclick="return confirm(\'' . addslashes($this->module->l('Supprimer ce produit manquant ?')) . '\');"><i class="icon-trash"></i></a>';
            $html .= '</td></tr>';
        }

        if (empty($children)) {
            $html .= '<tr><td colspan="4">' . $this->module->l('Aucun produit manquant associé.') . '</td></tr>';
        }

        $html .= '</tbody></table></div>';

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $idChild > 0 ? $this->module->l('Modifier un produit manquant') : $this->module->l('Ajouter un produit manquant'),
                ],
                'input' => [
                    ['type' => 'hidden', 'name' => 'id_parent'],
                    ['type' => 'hidden', 'name' => 'id_child'],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Produit manquant'),
                        'name' => 'child_missing_product_id',
                        'class' => 'fixed-width-xs',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->module->l('Message principal'),
                        'name' => 'child_message_main',
                        'lang' => true,
                        'rows' => 3,
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->module->l('Message complémentaire'),
                        'name' => 'child_message_extra',
                        'lang' => true,
                        'rows' => 2,
                    ],
                ],
                'submit' => ['title' => $this->module->l('Enregistrer le produit manquant')],
            ],
        ];

        $values = [
            'id_parent' => (int) $parent->id,
            'id_child' => $idChild,
            'child_missing_product_id' => (int) Tools::getValue('child_missing_product_id', (int) $child->missing_product_id),
        ];
        foreach (Language::getLanguages(false) as $language) {
            $idLang = (int) $language['id_lang'];
            $values['child_message_main'][$idLang] = Tools::getValue(
                'child_message_main_' . $idLang,
                isset($child->message_main[$idLang]) ? $child->message_main[$idLang] : ''
            );
            $values['child_message_extra'][$idLang] = Tools::getValue(
                'child_message_extra_' . $idLang,
                isset($child->message_extra[$idLang]) ? $child->message_extra[$idLang] : ''
            );
        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = 'dtc_cart_warning_child';
        $helper->module = $this->module;
        $helper->default_form_language = (int) Context::getContext()->language->id;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->submit_action = 'submitDtcCartWarningChild';
        $helper->currentIndex = Context::getContext()->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name
            . '&tab_module=' . $this->module->tab
            . '&module_name=' . $this->module->name
            . '&updatedtc_cart_warning_rule&id_rule=' . (int) $parent->id;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = ['fields_value' => $values];

        return $html . $helper->generateForm([$fieldsForm]);
    }

    private function getRuleTypeLabel($ruleType)
    {
        $labels = [
            CartWarningRule::TYPE_INCOMPATIBILITY => $this->module->l('Incompatibilité'),
            CartWarningRule::TYPE_MISSING_SIMPLE => $this->module->l('Produit manquant'),
            CartWarningRule::TYPE_MISSING_MULTIPLE => $this->module->l('Produits manquants multiples'),
            CartWarningRule::TYPE_MISSING_ITEM => $this->module->l('Produit manquant associé'),
        ];

        return isset($labels[$ruleType]) ? $labels[$ruleType] : $ruleType;
    }
}
