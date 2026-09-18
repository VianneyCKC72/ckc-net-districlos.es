<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/DtcPalierService.php';
require_once __DIR__ . '/classes/DtcCartRuleManager.php';

class dtc_paliers_remise extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_paliers_remise';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.1';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Paliers de remises professionnels', [], 'Modules.Dtcpaliersremise.Admin');
        $this->description = $this->trans(
            'Remises automatiques par paliers pour les clients professionnels (CA HT 12 mois glissants).',
            [],
            'Modules.Dtcpaliersremise.Admin'
        );

        // Migration one-shot (évite du travail à chaque requête une fois terminée)
        if ($this->id) {
            $this->ensureHooks();
        }
    }

    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        $sql = str_replace('PREFIX_', _DB_PREFIX_, file_get_contents(__DIR__ . '/sql/install.sql'));
        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        Configuration::updateValue('DTC_PALIER_TIERS', json_encode(DtcPalierService::getDefaultTiers()));
        Configuration::updateValue('DTC_PALIER_PERIOD_MONTHS', 12);

        DtcCartRuleManager::ensureSharedResources();

        return $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->registerHook('displayDtcCustomerPalier')
            && $this->registerHook('displayDtcClubProAvantages')
            && $this->registerHook('actionCartSave')
            && $this->registerHook('actionFrontControllerSetVariables')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayDtcOrderConfirmationClubPro')
            && $this->finalizeHooksConfig();
    }

    public function uninstall(): bool
    {
        $sql = str_replace('PREFIX_', _DB_PREFIX_, file_get_contents(__DIR__ . '/sql/uninstall.sql'));
        Db::getInstance()->execute($sql);

        foreach ([
            'DTC_PALIER_TIERS',
            'DTC_PALIER_PERIOD_MONTHS',
            'DTC_PALIER_READY',
            'DTC_PALIER_HOOK_V2',
            'DTC_PALIER_CART_HOOKS',
            'DTC_PALIER_NO_CART_BANNER',
            'DTC_PALIER_ROUTES',
            'DTC_PALIER_AVANTAGES_HOOK',
            'DTC_PALIER_GROUPS',
            'DTC_PALIER_RULES',
            'DTC_PALIER_SHARED_READY',
            'DTC_PALIER_MAIL_COLS',
        ] as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    /**
     * Aligne les hooks une seule fois (installs existantes).
     */
    private function ensureHooks(): void
    {
        if (!$this->isRegisteredInHook('actionFrontControllerSetVariables')) {
            $this->registerHook('actionFrontControllerSetVariables');
        }
        if (!$this->isRegisteredInHook('displayHeader')) {
            $this->registerHook('displayHeader');
        }
        if (!$this->isRegisteredInHook('displayDtcOrderConfirmationClubPro')) {
            $this->registerHook('displayDtcOrderConfirmationClubPro');
        }
        if ($this->isRegisteredInHook('actionPresentCart')) {
            $this->unregisterHook('actionPresentCart');
        }

        // Recalcul APRÈS valid=1 (actionOrderStatusUpdate est trop tôt)
        if ($this->isRegisteredInHook('actionOrderStatusUpdate')) {
            $this->unregisterHook('actionOrderStatusUpdate');
        }
        if (!$this->isRegisteredInHook('actionOrderStatusPostUpdate')) {
            $this->registerHook('actionOrderStatusPostUpdate');
        }

        $this->ensureMailSchema();

        // Migration vers 3 règles partagées + groupes
        if ((int) Configuration::get('DTC_PALIER_SHARED_READY') !== 1) {
            DtcCartRuleManager::ensureSharedResources();
        }

        if ((int) Configuration::get('DTC_PALIER_READY') === 1) {
            return;
        }

        $this->unregisterHook('displayCustomerAccount');
        $this->unregisterHook('displayShoppingCart');
        $this->unregisterHook('moduleRoutes');

        $this->registerHook('displayDtcCustomerPalier');
        $this->registerHook('displayDtcClubProAvantages');
        $this->registerHook('actionCartSave');

        $this->finalizeHooksConfig();
    }

    /**
     * Colonnes tracking e-mails (installs existantes).
     */
    public function ensureMailSchema(): void
    {
        if ((int) Configuration::get('DTC_PALIER_MAIL_COLS') === 1) {
            return;
        }

        $table = _DB_PREFIX_ . 'dtc_customer_palier';
        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . $table . '`');
        if (!$columns) {
            return;
        }

        $names = array_map(static function ($col) {
            return $col['Field'];
        }, $columns);

        $alters = [];
        if (!in_array('last_notified_tier', $names, true)) {
            $alters[] = 'ADD COLUMN `last_notified_tier` VARCHAR(30) DEFAULT NULL';
        }
        if (!in_array('warning_from_tier', $names, true)) {
            $alters[] = 'ADD COLUMN `warning_from_tier` VARCHAR(30) DEFAULT NULL';
        }
        if (!in_array('warning_sent_at', $names, true)) {
            $alters[] = 'ADD COLUMN `warning_sent_at` DATETIME DEFAULT NULL';
        }

        if ($alters) {
            Db::getInstance()->execute('ALTER TABLE `' . $table . '` ' . implode(', ', $alters));
        }

        Configuration::updateValue('DTC_PALIER_MAIL_COLS', 1);
    }

    private function finalizeHooksConfig(): bool
    {
        Configuration::updateValue('DTC_PALIER_READY', 1);
        Configuration::deleteByName('DTC_PALIER_HOOK_V2');
        Configuration::deleteByName('DTC_PALIER_CART_HOOKS');
        Configuration::deleteByName('DTC_PALIER_NO_CART_BANNER');
        Configuration::deleteByName('DTC_PALIER_ROUTES');
        Configuration::deleteByName('DTC_PALIER_AVANTAGES_HOOK');

        return true;
    }

    private function registerClubProCss(): void
    {
        $this->context->controller->registerStylesheet(
            'dtc-club-pro',
            'modules/' . $this->name . '/views/css/front/club-pro.css',
            ['media' => 'all', 'priority' => 150]
        );
    }

    private function getHeaderBadgeLevelCached(): ?string
    {
        static $level;
        static $resolved = false;

        if ($resolved) {
            return $level;
        }

        $resolved = true;
        $customer = $this->context->customer;
        $level = ($customer && $customer->id)
            ? DtcPalierService::getHeaderBadgeLevel((int) $customer->id)
            : null;

        return $level;
    }

    public function hookDisplayHeader($params): string
    {
        $controller = $this->context->controller;
        if ($controller && isset($controller->php_self) && $controller->php_self === 'order-confirmation') {
            $this->registerClubProCss();
        }

        if (!$this->getHeaderBadgeLevelCached()) {
            return '';
        }

        $controller->registerStylesheet(
            'dtc-club-pro-badge',
            'modules/' . $this->name . '/views/css/front/header-badge.css',
            ['media' => 'all', 'priority' => 160]
        );

        return '';
    }

    public function hookActionFrontControllerSetVariables($params): array
    {
        return [
            'club_badge' => $this->getHeaderBadgeLevelCached(),
        ];
    }

    public function hookDisplayDtcOrderConfirmationClubPro($params): string
    {
        $order = $this->resolveConfirmationOrder();
        if (!$order) {
            return '';
        }

        $idCustomer = (int) $order->id_customer;
        $customer = $this->context->customer;
        if (!$customer || !(int) $customer->id || (int) $customer->id !== $idCustomer) {
            return '';
        }

        $status = DtcPalierService::getOrderConfirmationStatus($idCustomer, $order);
        if (!$status) {
            return '';
        }

        $discount = (float) $status['discount'];
        $discountDisplay = rtrim(rtrim(number_format($discount, 2, '.', ''), '0'), '.') . '%';

        $this->registerClubProCss();
        $this->smarty->assign([
            'dtc_confirm' => [
                'mode' => $status['mode'],
                'label' => $status['label'],
                'level' => $status['level'],
                'discount_display' => $discountDisplay,
                'status_url' => $this->context->link->getPageLink('discount'),
            ],
        ]);

        return $this->display(__FILE__, 'order-confirmation.tpl');
    }

    private function resolveConfirmationOrder(): ?Order
    {
        $controller = $this->context->controller;
        if ($controller instanceof OrderConfirmationController && !empty($controller->order)) {
            $order = $controller->order;
            if (Validate::isLoadedObject($order)) {
                return $order;
            }
        }

        $idOrder = (int) Tools::getValue('id_order');
        if (!$idOrder && $controller && !empty($controller->id_order)) {
            $idOrder = (int) $controller->id_order;
        }
        if (!$idOrder) {
            $idCart = (int) Tools::getValue('id_cart');
            if ($idCart) {
                $idOrder = (int) Order::getIdByCartId($idCart);
            }
        }
        if (!$idOrder) {
            return null;
        }

        $order = new Order($idOrder);

        return Validate::isLoadedObject($order) ? $order : null;
    }

    public function hookActionValidateOrder($params): void
    {
        $order = $params['order'] ?? null;
        if ($order && $order->id_customer) {
            $this->recalculateCustomer((int) $order->id_customer);
        }
    }

    public function hookActionOrderStatusPostUpdate($params): void
    {
        $idOrder = (int) ($params['id_order'] ?? 0);
        if (!$idOrder) {
            return;
        }

        $order = new Order($idOrder);
        if (Validate::isLoadedObject($order)) {
            $this->recalculateCustomer((int) $order->id_customer);
        }
    }

    public function hookDisplayDtcCustomerPalier($params): string
    {
        $customer = $this->context->customer;
        if (!$customer || !$customer->id || !DtcPalierService::isProCustomer((int) $customer->id)) {
            return '';
        }

        $idCustomer = (int) $customer->id;
        if (!DtcPalierService::getCustomerTier($idCustomer)) {
            DtcCartRuleManager::syncCartRule($idCustomer, DtcPalierService::recompute($idCustomer));
        }

        $this->registerClubProCss();
        $this->smarty->assign(['dtc_club' => DtcPalierService::getClubProViewData($idCustomer)]);

        return $this->display(__FILE__, 'customer-account.tpl');
    }

    public function hookDisplayDtcClubProAvantages($params): string
    {
        $this->registerClubProCss();
        $this->smarty->assign(['dtc_club_tiers' => DtcPalierService::getDisplayTiers()]);

        return $this->display(__FILE__, 'avantages-pro.tpl');
    }

    public function hookActionCartSave($params): void
    {
        static $processing = false;
        if ($processing) {
            return;
        }

        $cart = $params['cart'] ?? $this->context->cart;
        $customer = $this->context->customer;
        if (!$cart || !$cart->id || !$customer || !$customer->id) {
            return;
        }

        $idCustomer = (int) $customer->id;
        if (!DtcPalierService::isProCustomer($idCustomer)) {
            return;
        }

        $processing = true;
        try {
            $tierData = DtcPalierService::getCustomerTier($idCustomer);
            if (!$tierData) {
                $tier = DtcPalierService::recompute($idCustomer);
            } else {
                $tier = ((string) $tierData['current_tier'] !== 'none' && (float) $tierData['discount_percent'] > 0)
                    ? ['name' => $tierData['current_tier'], 'discount' => (float) $tierData['discount_percent']]
                    : null;
            }
            DtcCartRuleManager::syncCartRule($idCustomer, $tier);
            DtcCartRuleManager::ensureAppliedToCart($cart, $idCustomer);
        } finally {
            $processing = false;
        }
    }

    private function recalculateCustomer(int $idCustomer): void
    {
        if (!DtcPalierService::isProCustomer($idCustomer)) {
            return;
        }

        require_once __DIR__ . '/classes/DtcPalierMailer.php';
        DtcPalierMailer::processCustomer($idCustomer);
    }

    public function getContent(): string
    {
        $this->ensureHooks();

        $output = '';
        if (Tools::isSubmit('submitDtcPalierConfig')) {
            $output .= $this->postProcess();
        }

        return $output . $this->renderForm();
    }

    private function postProcess(): string
    {
        $names = Tools::getValue('tier_name', []);
        $mins = Tools::getValue('tier_min', []);
        $discounts = Tools::getValue('tier_discount', []);

        $tiers = [];
        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            $min = (float) ($mins[$i] ?? -1);
            $discount = (float) ($discounts[$i] ?? 0);

            if ($name === '' || $min < 0 || $discount <= 0) {
                continue;
            }

            $tiers[] = [
                'min' => $min,
                'discount' => $discount,
                'name' => $name,
            ];
        }

        usort($tiers, static fn($a, $b) => $a['min'] <=> $b['min']);

        if (!$tiers) {
            return $this->displayError($this->trans('Veuillez configurer au moins un palier.', [], 'Modules.Dtcpaliersremise.Admin'));
        }

        Configuration::updateValue('DTC_PALIER_TIERS', json_encode($tiers));
        Configuration::updateValue('DTC_PALIER_PERIOD_MONTHS', (int) Tools::getValue('period_months', 12));
        DtcPalierService::clearCache();
        DtcCartRuleManager::ensureSharedResources();

        return $this->displayConfirmation($this->trans('Configuration enregistrée.', [], 'Modules.Dtcpaliersremise.Admin'));
    }

    private function renderForm(): string
    {
        DtcCartRuleManager::ensureSharedResources();

        $this->context->smarty->assign([
            'tiers' => DtcPalierService::getTiers(),
            'period' => (int) Configuration::get('DTC_PALIER_PERIOD_MONTHS') ?: 12,
            'group_map' => DtcCartRuleManager::getGroupMap(),
            'rule_map' => DtcCartRuleManager::getRuleMap(),
            'form_action' => AdminController::$currentIndex
                . '&configure=' . $this->name
                . '&token=' . Tools::getAdminTokenLite('AdminModules'),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }
}
