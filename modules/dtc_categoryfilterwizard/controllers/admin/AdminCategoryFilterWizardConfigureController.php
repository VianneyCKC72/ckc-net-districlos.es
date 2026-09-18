<?php
/**
 * Configuration du module Category Filter Wizard
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'dtc_categoryfilterwizard/dtc_categoryfilterwizard.php';

class AdminCategoryFilterWizardConfigureController extends ModuleAdminController
{
    /** @var dtc_categoryfilterwizard */
    private $module;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('dtc_categoryfilterwizard');
        if (!$this->module) {
            throw new PrestaShopException('Module dtc_categoryfilterwizard not found');
        }
        $this->bootstrap = true;
        $this->context = Context::getContext();
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();
        $this->content .= $this->renderForm();
        $this->context->smarty->assign('content', $this->content);
    }

    private function renderForm()
    {
        $this->processSubmit();
        $enabledIds = $this->module->getEnabledCategoryIds();
        $categoryIdsStr = implode(', ', $enabledIds);
        $categoriesConfig = [];
        foreach ($enabledIds as $id) {
            $steps = Configuration::get(dtc_categoryfilterwizard::CONFIG_STEPS_PREFIX . $id);
            $shortcircuit = Configuration::get(dtc_categoryfilterwizard::CONFIG_SHORTCIRCUIT_PREFIX . $id);
            $provider = new WizardConfigProvider($this->module);
            $productMap = $provider->getProductMapFromDb($id);
            $categoriesConfig[$id] = [
                'steps' => $steps ?: '[]',
                'shortcircuit' => $shortcircuit ?: '[]',
                'product_map_json' => json_encode($productMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ];
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Configuration du wizard', 'CategoryFilterWizard'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Activer le wizard', 'CategoryFilterWizard'),
                        'name' => 'DTC_CATEGORYFILTERWIZARD_ENABLED',
                        'values' => [['id' => 'active_on', 'value' => 1], ['id' => 'active_off', 'value' => 0]],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('IDs des catégories (séparés par des virgules)', 'CategoryFilterWizard'),
                        'name' => 'DTC_CATEGORYFILTERWIZARD_CATEGORY_IDS',
                        'desc' => $this->module->l('Ex. : 215,438', 'CategoryFilterWizard'),
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Enregistrer', 'CategoryFilterWizard'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = 'id';
        $helper->submit_action = 'submitCategoryFilterWizardConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminCategoryFilterWizardConfigure', false);
        $helper->token = Tools::getAdminTokenLite('AdminCategoryFilterWizardConfigure');
        $helper->tpl_vars = [
            'fields_value' => [
                'DTC_CATEGORYFILTERWIZARD_ENABLED' => Configuration::get(dtc_categoryfilterwizard::CONFIG_ENABLED),
                'DTC_CATEGORYFILTERWIZARD_CATEGORY_IDS' => Configuration::get(dtc_categoryfilterwizard::CONFIG_CATEGORY_IDS),
            ],
        ];

        $out = $helper->generateForm([$fields_form]);

        $out .= '<div class="panel"><h3>' . $this->module->l('Configuration par catégorie', 'CategoryFilterWizard') . '</h3>';
        $out .= '<p class="help-block">' . $this->module->l('Pour chaque catégorie listée ci-dessus, vous pouvez éditer les étapes (JSON), les règles d\'arrêt anticipé (JSON) et le mapping produits (JSON). Sauvegardez chaque catégorie avec le bouton dédié.', 'CategoryFilterWizard') . '</p>';

        foreach ($enabledIds as $id) {
            $out .= $this->renderCategoryForm($id, $categoriesConfig[$id] ?? ['steps' => '[]', 'shortcircuit' => '[]', 'product_map_json' => '{}']);
        }
        $out .= '</div>';
        return $out;
    }

    private function renderCategoryForm($id_category, array $config)
    {
        $form = new HelperForm();
        $form->show_toolbar = false;
        $form->submit_action = 'submitCategoryFilterWizardCategory_' . $id_category;
        $form->currentIndex = $this->context->link->getAdminLink('AdminCategoryFilterWizardConfigure', false);
        $form->token = Tools::getAdminTokenLite('AdminCategoryFilterWizardConfigure');
        $form->tpl_vars = ['fields_value' => [
            'steps_' . $id_category => $config['steps'],
            'shortcircuit_' . $id_category => $config['shortcircuit'],
            'product_map_' . $id_category => $config['product_map_json'],
        ]];

        $fields = [
            'form' => [
                'legend' => ['title' => $this->module->l('Catégorie', 'CategoryFilterWizard') . ' ' . $id_category],
                'input' => [
                    [
                        'type' => 'textarea',
                        'label' => $this->module->l('Étapes (JSON)', 'CategoryFilterWizard'),
                        'name' => 'steps_' . $id_category,
                        'rows' => 8,
                        'cols' => 80,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->module->l('Règles d\'arrêt anticipé (JSON)', 'CategoryFilterWizard'),
                        'name' => 'shortcircuit_' . $id_category,
                        'rows' => 4,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->module->l('Product map (JSON)', 'CategoryFilterWizard'),
                        'name' => 'product_map_' . $id_category,
                        'rows' => 15,
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Sauvegarder la catégorie', 'CategoryFilterWizard') . ' ' . $id_category,
                    'class' => 'btn btn-default',
                ],
            ],
        ];
        return '<div class="panel">' . $form->generateForm([$fields]) . '</div>';
    }

    private function processSubmit()
    {
        if (Tools::isSubmit('submitCategoryFilterWizardConfig')) {
            Configuration::updateValue(dtc_categoryfilterwizard::CONFIG_ENABLED, (bool) Tools::getValue('DTC_CATEGORYFILTERWIZARD_ENABLED'));
            Configuration::updateValue(dtc_categoryfilterwizard::CONFIG_CATEGORY_IDS, trim(Tools::getValue('DTC_CATEGORYFILTERWIZARD_CATEGORY_IDS')));
            $this->confirmations[] = $this->module->l('Configuration enregistrée.', 'CategoryFilterWizard');
        }
        $enabledIds = $this->module->getEnabledCategoryIds();
        foreach ($enabledIds as $id) {
            if (Tools::isSubmit('submitCategoryFilterWizardCategory_' . $id)) {
                $steps = Tools::getValue('steps_' . $id);
                $shortcircuit = Tools::getValue('shortcircuit_' . $id);
                $productMap = Tools::getValue('product_map_' . $id);
                if ($steps !== false) {
                    Configuration::updateValue(dtc_categoryfilterwizard::CONFIG_STEPS_PREFIX . $id, $steps);
                }
                if ($shortcircuit !== false) {
                    Configuration::updateValue(dtc_categoryfilterwizard::CONFIG_SHORTCIRCUIT_PREFIX . $id, $shortcircuit);
                }
                if ($productMap !== false) {
                    $this->module->saveProductMapFromJson($id, $productMap);
                }
                $this->confirmations[] = $this->module->l('Catégorie', 'CategoryFilterWizard') . ' ' . $id . ' ' . $this->module->l('enregistrée.', 'CategoryFilterWizard');
            }
        }
    }
}
