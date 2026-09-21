<?php
/**
 * Module DTC Cart Rule
 *
 * Conteneur pour les extensions custom des règles panier PrestaShop.
 *
 * Fonctionnalités :
 *  - Condition "nombre minimum de commandes validées" (par règle panier)
 *  - Remise répétitive configurable : -X € tous les Y € (sans plafond)
 *
 * La table `ps_cart_rule` du core n'est jamais altérée structurellement.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/CartRuleMinOrdersService.php';
require_once __DIR__ . '/classes/CartRuleRepeatDiscountService.php';

class Dtc_Cartrule extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_cartrule';
        $this->tab = 'pricing_promotion';
        $this->version = '1.1.0';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DTC Cart Rule');
        $this->description = $this->l('Extensions Districlos pour les règles panier (conditions, remise répétitive).');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');

        if ($this->id) {
            $this->ensureUpgrade();
        }
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!include __DIR__ . '/sql/install.php') {
            return false;
        }

        $this->installDefaultConfig();

        return $this->registerHook('actionObjectCartRuleAddAfter')
            && $this->registerHook('actionObjectCartRuleUpdateAfter')
            && $this->registerHook('actionObjectCartRuleDeleteAfter')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('actionCartSave');
    }

    public function uninstall()
    {
        CartRuleRepeatDiscountService::deactivateCartRule();

        foreach ([
            CartRuleRepeatDiscountService::CFG_ACTIVE,
            CartRuleRepeatDiscountService::CFG_STEP,
            CartRuleRepeatDiscountService::CFG_DISCOUNT,
            CartRuleRepeatDiscountService::CFG_TAX,
            CartRuleRepeatDiscountService::CFG_ID_CART_RULE,
            'DTC_CARTRULE_REPEAT_NO_HIGHLIGHT',
        ] as $key) {
            Configuration::deleteByName($key);
        }

        include __DIR__ . '/sql/uninstall.php';

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDtcCartruleRepeat')) {
            $step = (float) str_replace(',', '.', (string) Tools::getValue(CartRuleRepeatDiscountService::CFG_STEP));
            $discount = (float) str_replace(',', '.', (string) Tools::getValue(CartRuleRepeatDiscountService::CFG_DISCOUNT));
            $active = (int) Tools::getValue(CartRuleRepeatDiscountService::CFG_ACTIVE);
            $tax = (int) Tools::getValue(CartRuleRepeatDiscountService::CFG_TAX);

            if ($step <= 0 || $discount <= 0) {
                $output .= $this->displayError($this->l('Le palier et la remise doivent être des montants strictement positifs.'));
            } else {
                Configuration::updateValue(CartRuleRepeatDiscountService::CFG_ACTIVE, $active ? 1 : 0);
                Configuration::updateValue(CartRuleRepeatDiscountService::CFG_STEP, $step);
                Configuration::updateValue(CartRuleRepeatDiscountService::CFG_DISCOUNT, $discount);
                Configuration::updateValue(CartRuleRepeatDiscountService::CFG_TAX, $tax ? 1 : 0);

                CartRuleRepeatDiscountService::ensureCartRule();

                $output .= $this->displayConfirmation($this->l('Configuration enregistrée.'));
            }
        }

        return $output . $this->renderRepeatConfigForm();
    }

    private function renderRepeatConfigForm(): string
    {
        $defaults = CartRuleRepeatDiscountService::getDefaults();
        $idCartRule = CartRuleRepeatDiscountService::getIdCartRule();

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Remise répétitive (sans plafond)'),
                    'icon' => 'icon-money',
                ],
                'description' => $this->l('Exemple : 75 € de remise tous les 500 € de panier produits. Le montant est multiplié automatiquement (1000 € → 150 €, etc.).'),
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activer'),
                        'name' => CartRuleRepeatDiscountService::CFG_ACTIVE,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Montant du palier'),
                        'name' => CartRuleRepeatDiscountService::CFG_STEP,
                        'prefix' => '€',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Ex. : 500'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Remise par palier'),
                        'name' => CartRuleRepeatDiscountService::CFG_DISCOUNT,
                        'prefix' => '€',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Ex. : 75'),
                        'required' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Calculer en TTC'),
                        'name' => CartRuleRepeatDiscountService::CFG_TAX,
                        'is_bool' => true,
                        'desc' => $this->l('Oui = total produits TTC / remise TTC. Non = HT.'),
                        'values' => [
                            ['id' => 'tax_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'tax_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'html',
                        'name' => 'dtc_repeat_info',
                        'html_content' => '<div class="alert alert-info" style="margin:0">'
                            . $this->l('Règle panier liée') . ' : '
                            . ($idCartRule > 0
                                ? '#' . (int) $idCartRule
                                : $this->l('sera créée à l\'enregistrement'))
                            . '</div>',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDtcCartruleRepeat';
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value = [
            CartRuleRepeatDiscountService::CFG_ACTIVE => (int) (
                Configuration::get(CartRuleRepeatDiscountService::CFG_ACTIVE) !== false
                    ? Configuration::get(CartRuleRepeatDiscountService::CFG_ACTIVE)
                    : $defaults['active']
            ),
            CartRuleRepeatDiscountService::CFG_STEP => (float) (
                Configuration::get(CartRuleRepeatDiscountService::CFG_STEP) !== false
                    ? Configuration::get(CartRuleRepeatDiscountService::CFG_STEP)
                    : $defaults['step']
            ),
            CartRuleRepeatDiscountService::CFG_DISCOUNT => (float) (
                Configuration::get(CartRuleRepeatDiscountService::CFG_DISCOUNT) !== false
                    ? Configuration::get(CartRuleRepeatDiscountService::CFG_DISCOUNT)
                    : $defaults['discount']
            ),
            CartRuleRepeatDiscountService::CFG_TAX => (int) (
                Configuration::get(CartRuleRepeatDiscountService::CFG_TAX) !== false
                    ? Configuration::get(CartRuleRepeatDiscountService::CFG_TAX)
                    : $defaults['tax']
            ),
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    private function installDefaultConfig(): void
    {
        $defaults = CartRuleRepeatDiscountService::getDefaults();
        Configuration::updateValue(CartRuleRepeatDiscountService::CFG_ACTIVE, $defaults['active']);
        Configuration::updateValue(CartRuleRepeatDiscountService::CFG_STEP, $defaults['step']);
        Configuration::updateValue(CartRuleRepeatDiscountService::CFG_DISCOUNT, $defaults['discount']);
        Configuration::updateValue(CartRuleRepeatDiscountService::CFG_TAX, $defaults['tax']);
    }

    /**
     * Migration douce (hooks / config) sans réinstallation.
     */
    private function ensureUpgrade(): void
    {
        if (!Configuration::hasKey(CartRuleRepeatDiscountService::CFG_STEP)) {
            $this->installDefaultConfig();
        }

        $this->registerHook('actionCartSave');

        // Désactive le highlight sur la règle existante (évite le bloc promo orange < 500 €).
        if ((int) Configuration::get('DTC_CARTRULE_REPEAT_NO_HIGHLIGHT') !== 1) {
            $idCartRule = CartRuleRepeatDiscountService::getIdCartRule();
            if ($idCartRule > 0) {
                Db::getInstance()->update(
                    'cart_rule',
                    ['highlight' => 0],
                    'id_cart_rule = ' . (int) $idCartRule
                );
            }
            Configuration::updateValue('DTC_CARTRULE_REPEAT_NO_HIGHLIGHT', 1);
        }
    }

    public function hookActionCartSave($params)
    {
        if (empty($params['cart']) || !($params['cart'] instanceof Cart)) {
            return;
        }

        try {
            CartRuleRepeatDiscountService::syncToCart($params['cart']);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('dtc_cartrule actionCartSave failed: ' . $e->getMessage(), 2);
        }
    }

    public function hookActionObjectCartRuleAddAfter($params)
    {
        $this->saveSubmittedValue($params);
    }

    public function hookActionObjectCartRuleUpdateAfter($params)
    {
        $this->saveSubmittedValue($params);
    }

    public function hookActionObjectCartRuleDeleteAfter($params)
    {
        if (empty($params['object']) || empty($params['object']->id)) {
            return;
        }

        $idCartRule = (int) $params['object']->id;
        CartRuleMinOrdersService::delete($idCartRule);

        if (CartRuleRepeatDiscountService::isRepeatRule($idCartRule)) {
            Configuration::updateValue(CartRuleRepeatDiscountService::CFG_ID_CART_RULE, 0);
            Configuration::updateValue(CartRuleRepeatDiscountService::CFG_ACTIVE, 0);
        }
    }

    private function saveSubmittedValue(array $params): void
    {
        if (empty($params['object']) || empty($params['object']->id)) {
            return;
        }

        $idCartRule = (int) $params['object']->id;

        if (Tools::isSubmit('dtc_minimum_orders')) {
            CartRuleMinOrdersService::save(
                $idCartRule,
                (int) Tools::getValue('dtc_minimum_orders', 0)
            );
        }
    }

    /**
     * Injection du champ "min commandes" sur AdminCartRules.
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        if (Tools::getValue('controller') !== 'AdminCartRules') {
            return '';
        }

        $idCartRule = (int) Tools::getValue('id_cart_rule', 0);
        $currentMinOrders = CartRuleMinOrdersService::getMinimumOrders($idCartRule);

        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $labelJs = json_encode($this->l('Nombre minimum de commandes validées du client'), $jsonFlags);
        $helpJs = json_encode(
            $this->l('Le client doit avoir au moins ce nombre de commandes validées pour utiliser cette règle. Ex : 1 = à partir de la 2e commande. 0 = pas de restriction.'),
            $jsonFlags
        );

        return '<script>
(function() {
    function injectFields() {
        var container = document.getElementById("cart_rule_conditions");
        if (!container || document.getElementById("dtc_minimum_orders")) {
            return;
        }
        var wrapper = document.createElement("div");
        wrapper.className = "form-group";
        wrapper.innerHTML = ""
            + "<label class=\"control-label col-lg-3\">" + ' . $labelJs . ' + "</label>"
            + "<div class=\"col-lg-3\">"
            + "  <input type=\"number\" min=\"0\" step=\"1\" name=\"dtc_minimum_orders\""
            + "         id=\"dtc_minimum_orders\" class=\"form-control\" value=\"' . (int) $currentMinOrders . '\" />"
            + "</div>"
            + "<div class=\"col-lg-6\"><p class=\"help-block\">" + ' . $helpJs . ' + "</p></div>";
        container.appendChild(wrapper);
    }
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", injectFields);
    } else {
        injectFields();
    }
})();
</script>';
    }
}
