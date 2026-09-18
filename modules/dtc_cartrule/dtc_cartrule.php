<?php
/**
 * Module DTC Cart Rule
 *
 * Module "conteneur" pour les extensions custom des règles panier PrestaShop.
 * Chaque fonctionnalité (condition supplémentaire, action custom, etc.) est
 * isolée dans son propre service et, si besoin, sa propre table dédiée.
 *
 * Fonctionnalités actuelles :
 *  - Condition "nombre minimum de commandes validées du client"
 *    -> table : PREFIX_dtc_cartrule_minorders
 *    -> service : CartRuleMinOrdersService
 *    -> champ form admin : name="dtc_minimum_orders"
 *
 * La table `ps_cart_rule` du core n'est jamais modifiée.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/CartRuleMinOrdersService.php';

class Dtc_Cartrule extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_cartrule';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DTC Cart Rule');
        $this->description = $this->l('Extensions custom Districlos pour les règles panier (conditions, actions, etc.).');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!include __DIR__ . '/sql/install.php') {
            return false;
        }

        return $this->registerHook('actionObjectCartRuleAddAfter')
            && $this->registerHook('actionObjectCartRuleUpdateAfter')
            && $this->registerHook('actionObjectCartRuleDeleteAfter')
            && $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        include __DIR__ . '/sql/uninstall.php';
        return parent::uninstall();
    }

    /**
     * Hook : nouvelle règle panier créée depuis le BO.
     */
    public function hookActionObjectCartRuleAddAfter($params)
    {
        $this->saveSubmittedValue($params);
    }

    /**
     * Hook : règle panier mise à jour depuis le BO.
     */
    public function hookActionObjectCartRuleUpdateAfter($params)
    {
        $this->saveSubmittedValue($params);
    }

    /**
     * Hook : règle panier supprimée. On nettoie les tables dédiées.
     */
    public function hookActionObjectCartRuleDeleteAfter($params)
    {
        if (empty($params['object']) || empty($params['object']->id)) {
            return;
        }
        CartRuleMinOrdersService::delete((int) $params['object']->id);
    }

    /**
     * Récupère les valeurs soumises dans le form admin cart_rule et les persiste.
     * Chaque feature lit son propre champ et appelle son propre service.
     */
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
     * Hook : injection des champs DTC dans l'onglet "Conditions" du formulaire
     * de règle panier, uniquement sur AdminCartRules.
     *
     * Approche : injection via JS dans #cart_rule_conditions (id natif du
     * template controllers/cart_rules/conditions.tpl). Pas d'override du
     * controller ni du template, c'est totalement réversible.
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        if (Tools::getValue('controller') !== 'AdminCartRules') {
            return '';
        }

        $idCartRule = (int) Tools::getValue('id_cart_rule', 0);
        $currentMinOrders = CartRuleMinOrdersService::getMinimumOrders($idCartRule);

        $labelMinOrders = $this->l('Nombre minimum de commandes validées du client');
        $helpMinOrders = $this->l('Le client doit avoir au moins ce nombre de commandes validées pour utiliser cette règle. Ex : 1 = à partir de la 2e commande. 0 = pas de restriction.');

        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $labelJs = json_encode($labelMinOrders, $jsonFlags);
        $helpJs = json_encode($helpMinOrders, $jsonFlags);

        return '<script>
(function() {
    function injectFields() {
        var container = document.getElementById("cart_rule_conditions");
        if (!container) {
            return;
        }
        if (!document.getElementById("dtc_minimum_orders")) {
            var label = ' . $labelJs . ';
            var help = ' . $helpJs . ';
            var value = ' . $currentMinOrders . ';
            var wrapper = document.createElement("div");
            wrapper.className = "form-group";
            wrapper.innerHTML = ""
                + "<label class=\"control-label col-lg-3\">" + label + "</label>"
                + "<div class=\"col-lg-3\">"
                + "  <input type=\"number\" min=\"0\" step=\"1\" name=\"dtc_minimum_orders\""
                + "         id=\"dtc_minimum_orders\" class=\"form-control\" value=\"" + value + "\" />"
                + "</div>"
                + "<div class=\"col-lg-6\"><p class=\"help-block\">" + help + "</p></div>";
            container.appendChild(wrapper);
        }
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
