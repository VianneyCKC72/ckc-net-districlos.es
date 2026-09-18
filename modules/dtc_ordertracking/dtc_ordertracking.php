<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_ordertracking extends Module
{
    public const CONF_MAPPING = 'DTC_ORDERTRACKING_MAPPING';
    public const CONF_STEP_LABELS = 'DTC_ORDERTRACKING_STEP_LABELS';
    public const CONF_ALLOW_REF_ONLY = 'DTC_ORDERTRACKING_ALLOW_REF_ONLY';

    public function __construct()
    {
        $this->name = 'dtc_ordertracking';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'DTC';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DTC Suivi de commande');
        $this->description = $this->l('Page de suivi de commande publique avec timeline visuelle, accessible aux invités, clients et pros.');

        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $ok = true
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayOrderConfirmation')
            && $this->registerHook('moduleRoutes');

        if (!$ok) {
            return false;
        }

        $defaultLabels = [
            1 => 'Commande reçue',
            2 => 'Paiement accepté',
            3 => 'En préparation',
            4 => 'En cours de livraison',
            5 => 'Livrée',
        ];

        Configuration::updateValue(self::CONF_MAPPING, json_encode(new stdClass()));
        Configuration::updateValue(self::CONF_STEP_LABELS, json_encode($defaultLabels, JSON_UNESCAPED_UNICODE));
        Configuration::updateValue(self::CONF_ALLOW_REF_ONLY, 0);

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CONF_MAPPING);
        Configuration::deleteByName(self::CONF_STEP_LABELS);
        Configuration::deleteByName(self::CONF_ALLOW_REF_ONLY);

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDtcOrderTracking')) {
            $res = $this->postProcess();
            if (!empty($res['errors'])) {
                foreach ($res['errors'] as $msg) {
                    $output .= $this->displayError($msg);
                }
            } else {
                $output .= $this->displayConfirmation($this->l('Configuration enregistrée.'));
            }
        }

        $idLang = (int) $this->context->language->id;
        $states = OrderState::getOrderStates($idLang);
        $mapping = self::getMapping();
        $labels = self::getStepLabels();

        $this->context->smarty->assign([
            'token_admin_modules' => Tools::getAdminTokenLite('AdminModules'),
            'module_name' => $this->name,
            'currentIndex' => $this->context->link->getAdminLink('AdminModules', false),
            'states' => $states,
            'mapping' => $mapping,
            'labels' => $labels,
            'allow_ref_only' => (int) Configuration::get(self::CONF_ALLOW_REF_ONLY, 0),
        ]);

        $output .= $this->fetch('module:dtc_ordertracking/views/templates/admin/configure.tpl');
        return $output;
    }

    protected function postProcess(): array
    {
        $errors = [];

        $allowRefOnly = (int) Tools::getValue('DTC_ORDERTRACKING_ALLOW_REF_ONLY', 0);
        Configuration::updateValue(self::CONF_ALLOW_REF_ONLY, $allowRefOnly ? 1 : 0);

        $labels = [];
        for ($i = 1; $i <= 5; $i++) {
            $labels[$i] = trim((string) Tools::getValue('DTC_ORDERTRACKING_STEP_LABEL_' . $i, ''));
            if ($labels[$i] === '') {
                $errors[] = sprintf($this->l('Le libellé de l’étape %d est obligatoire.'), $i);
            }
        }

        if (empty($errors)) {
            Configuration::updateValue(self::CONF_STEP_LABELS, json_encode($labels, JSON_UNESCAPED_UNICODE));
        }

        $mapping = [];
        $postedMap = Tools::getValue('mapping', []);
        if (is_array($postedMap)) {
            foreach ($postedMap as $idState => $step) {
                $idState = (int) $idState;
                $step = (int) $step;
                if ($idState <= 0) {
                    continue;
                }
                if ($step < 1 || $step > 5) {
                    continue; // "Aucune étape"
                }
                $mapping[$idState] = $step;
            }
        }
        Configuration::updateValue(self::CONF_MAPPING, json_encode($mapping));

        return ['errors' => $errors];
    }

    public static function allowReferenceOnly(): bool
    {
        return (bool) (int) Configuration::get(self::CONF_ALLOW_REF_ONLY, 0);
    }

    public function hookDisplayCustomerAccount($params)
    {
        $url = $this->context->link->getPageLink('history', true);
        $this->context->smarty->assign([
            'dtc_tracking_history_url' => $url,
            'dtc_tracking_direct_url' => $this->context->link->getModuleLink($this->name, 'track', [], true),
        ]);

        return $this->fetch('module:dtc_ordertracking/views/templates/hook/customer_account.tpl');
    }

    public function hookDisplayOrderConfirmation($params)
    {
        $idOrder = (int)($params['order']['id'] ?? $params['id_order'] ?? 0);
        $order = $idOrder ? new Order($idOrder) : null;

        $trackingUrl = '';
        if ($order && Validate::isLoadedObject($order)) {
            $trackingUrl = self::allowReferenceOnly()
                ? self::getPublicTrackingUrl($order)
                : self::getTrackingUrl($order);
        }

        $this->context->smarty->assign([
            'dtc_tracking_url' => $trackingUrl,
        ]);

        return $this->fetch('module:dtc_ordertracking/views/templates/hook/order_confirmation.tpl');
    }

    /**
     * Route friendly URL : /suivi-commande
     * Permet d’éviter /module/dtc_ordertracking/track
     */
    public function hookModuleRoutes($params)
    {
        return [
            'module-dtc_ordertracking-track' => [
                'controller' => 'track',
                // URL : /tracking-commande?ref=XXXX
                'rule' => 'tracking-commande',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                    'controller' => 'track',
                ],
            ],
        ];
    }

    public static function getTrackingUrl(Order $order): string
    {
        $reference = (string) $order->reference;
        $token = hash('sha256', $reference . $order->secure_key . _COOKIE_KEY_);

        return Context::getContext()->link->getModuleLink(
            'dtc_ordertracking',
            'track',
            [
                'code' => $reference,
                'token' => $token,
            ],
            true
        );
    }

    /**
     * URL publique (moins sécurisée) : id + ref, sans token.
     */
    public static function getPublicTrackingUrl(Order $order): string
    {
        return Context::getContext()->link->getModuleLink(
            'dtc_ordertracking',
            'track',
            [
                'id' => (int) $order->id,
                'code' => (string) $order->reference,
            ],
            true
        );
    }

    public static function verifyToken(string $reference, string $token): bool
    {
        $order = self::getOrderByReference($reference);
        if (!$order) {
            return false;
        }

        $expected = hash('sha256', (string) $order->reference . $order->secure_key . _COOKIE_KEY_);
        return hash_equals($expected, (string) $token);
    }

    public static function getStepLabels(): array
    {
        $raw = (string) Configuration::get(self::CONF_STEP_LABELS, '');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = [
                1 => 'Commande reçue',
                2 => 'Paiement accepté',
                3 => 'En préparation',
                4 => 'En cours de livraison',
                5 => 'Livrée',
            ];
        }

        // Certains contenus peuvent être stockés avec des entités HTML (ex: re&ccedil;ue).
        // On les normalise pour l’affichage (UTF-8) puis on laisse Smarty échapper.
        $labels = [];
        foreach ($decoded as $k => $v) {
            $step = (int) $k;
            if ($step < 1 || $step > 5) {
                continue;
            }
            $labels[$step] = html_entity_decode((string) $v, ENT_QUOTES, 'UTF-8');
        }

        return $labels + [
            1 => 'Commande reçue',
            2 => 'Paiement accepté',
            3 => 'En préparation',
            4 => 'En cours de livraison',
            5 => 'Livrée',
        ];
    }

    public static function getMapping(): array
    {
        $raw = (string) Configuration::get(self::CONF_MAPPING, '');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function getOrderByReference(string $reference): ?Order
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        // Multi-shop : on limite la recherche aux shops du contexte courant
        // pour éviter qu'une boutique puisse voir une commande d'une autre.
        $shopList = Shop::getContextListShopID();
        $shopList = array_map('intval', is_array($shopList) ? $shopList : []);
        $shopFilter = '';
        if (!empty($shopList)) {
            $shopFilter = ' AND o.`id_shop` IN (' . implode(',', $shopList) . ')';
        }

        $idOrder = (int) Db::getInstance()->getValue(
            'SELECT o.`id_order`
             FROM `' . _DB_PREFIX_ . 'orders` o
             WHERE o.`reference` = \'' . pSQL($reference) . '\''
            . $shopFilter . '
             ORDER BY o.`id_order` DESC'
        );

        if ($idOrder <= 0) {
            return null;
        }

        $order = new Order($idOrder);
        return Validate::isLoadedObject($order) ? $order : null;
    }
}

