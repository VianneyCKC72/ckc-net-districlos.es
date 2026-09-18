<?php
/**
 * Virement bancaire Districlos — RIB dynamique par magasin.
 *
 * Fork métier de ps_wirepayment (AFL-3.0), adapté Districlos.
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_Wirepayment extends PaymentModule
{
    public const FLAG_DISPLAY_PAYMENT_INVITE = 'DTC_WIRE_PAYMENT_INVITE';

    protected $_html = '';
    protected $_postErrors = [];

    /** @var string|null */
    public $details;
    /** @var string|null */
    public $owner;
    /** @var string|null */
    public $address;
    /** @var array */
    public $extra_mail_vars;
    /** @var int */
    public $is_eu_compatible;
    /** @var false|int */
    public $reservation_days;

    public function __construct()
    {
        $this->name = 'dtc_wirepayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->author = 'Districlos';
        $this->controllers = ['payment', 'validation'];
        $this->is_eu_compatible = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;

        $config = Configuration::getMultiple([
            'DTC_WIRE_DETAILS',
            'DTC_WIRE_OWNER',
            'DTC_WIRE_ADDRESS',
            'DTC_WIRE_RESERVATION_DAYS',
        ]);
        if (!empty($config['DTC_WIRE_OWNER'])) {
            $this->owner = $config['DTC_WIRE_OWNER'];
        }
        if (!empty($config['DTC_WIRE_DETAILS'])) {
            $this->details = $config['DTC_WIRE_DETAILS'];
        }
        if (!empty($config['DTC_WIRE_ADDRESS'])) {
            $this->address = $config['DTC_WIRE_ADDRESS'];
        }
        if (!empty($config['DTC_WIRE_RESERVATION_DAYS'])) {
            $this->reservation_days = $config['DTC_WIRE_RESERVATION_DAYS'];
        }

        parent::__construct();

        $this->displayName = $this->l('Virement bancaire (Districlos)');
        $this->description = $this->l('Virement bancaire avec RIB du magasin lié à la commande.');
        $this->confirmUninstall = $this->l('Supprimer la configuration du virement bancaire ?');

        if ((!isset($this->owner) || !isset($this->details) || !isset($this->address)) && $this->active) {
            $this->warning = $this->l('Configurez le titulaire et les détails du compte avant utilisation.');
        }
        if (!count(Currency::checkPaymentCurrencies($this->id)) && $this->active) {
            $this->warning = $this->l('Aucune devise n\'a été définie pour ce module.');
        }

        $wire = $this->getFallbackWireDetails();
        $this->extra_mail_vars = [
            '{bankwire_owner}' => $wire['owner'],
            '{bankwire_details}' => Tools::nl2br($wire['details']),
            '{bankwire_address}' => Tools::nl2br($wire['address']),
        ];
    }

    public function install()
    {
        $this->migrateConfigFromPsWirepayment();

        $ok = parent::install()
            && $this->registerHook('displayPaymentReturn')
            && $this->registerHook('paymentOptions')
            && Configuration::updateValue(self::FLAG_DISPLAY_PAYMENT_INVITE, true);

        if ($ok) {
            $this->copyPaymentRestrictionsFromPsWirepayment();
            $psWire = Module::getInstanceByName('ps_wirepayment');
            if ($psWire && $psWire->active) {
                $psWire->disable();
            }
        }

        return (bool) $ok;
    }

    protected function copyPaymentRestrictionsFromPsWirepayment()
    {
        $psWire = Module::getInstanceByName('ps_wirepayment');
        if (!$psWire || !$psWire->id || !$this->id) {
            return;
        }

        $tables = [
            'module_currency' => ['id_shop', 'id_currency'],
            'module_country' => ['id_shop', 'id_country'],
            'module_group' => ['id_shop', 'id_group'],
        ];

        foreach ($tables as $table => $columns) {
            Db::getInstance()->delete($table, 'id_module = ' . (int) $this->id);
            $rows = Db::getInstance()->executeS(
                'SELECT `' . implode('`, `', $columns) . '` FROM `' . _DB_PREFIX_ . bqSQL($table) . '` WHERE `id_module` = ' . (int) $psWire->id
            );
            foreach ((array) $rows as $row) {
                $data = ['id_module' => (int) $this->id];
                foreach ($columns as $column) {
                    $data[$column] = (int) $row[$column];
                }
                Db::getInstance()->insert($table, $data);
            }
        }
    }

    public function uninstall()
    {
        return Configuration::deleteByName('DTC_WIRE_CUSTOM_TEXT')
            && Configuration::deleteByName('DTC_WIRE_DETAILS')
            && Configuration::deleteByName('DTC_WIRE_OWNER')
            && Configuration::deleteByName('DTC_WIRE_ADDRESS')
            && Configuration::deleteByName('DTC_WIRE_RESERVATION_DAYS')
            && Configuration::deleteByName(self::FLAG_DISPLAY_PAYMENT_INVITE)
            && parent::uninstall();
    }

    protected function migrateConfigFromPsWirepayment()
    {
        $map = [
            'BANK_WIRE_OWNER' => 'DTC_WIRE_OWNER',
            'BANK_WIRE_DETAILS' => 'DTC_WIRE_DETAILS',
            'BANK_WIRE_ADDRESS' => 'DTC_WIRE_ADDRESS',
            'BANK_WIRE_RESERVATION_DAYS' => 'DTC_WIRE_RESERVATION_DAYS',
            'BANK_WIRE_PAYMENT_INVITE' => self::FLAG_DISPLAY_PAYMENT_INVITE,
        ];
        foreach ($map as $from => $to) {
            if (!Configuration::get($to) && Configuration::get($from) !== false && Configuration::get($from) !== null) {
                Configuration::updateValue($to, Configuration::get($from));
            }
        }

        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $custom = Configuration::get('BANK_WIRE_CUSTOM_TEXT', $idLang);
            if ($custom && !Configuration::get('DTC_WIRE_CUSTOM_TEXT', $idLang)) {
                Configuration::updateValue('DTC_WIRE_CUSTOM_TEXT', [$idLang => $custom]);
            }
        }

        if (!Configuration::get('DTC_WIRE_DETAILS')) {
            Configuration::updateValue(
                'DTC_WIRE_DETAILS',
                "IBAN : {iban}\nBIC-ADRESSE SWIFT : {bic}"
            );
        }
    }

    /**
     * @return array{owner: string, details: string, address: string}
     */
    public function getFallbackWireDetails(): array
    {
        return [
            'owner' => (string) ($this->owner ?: Configuration::get('DTC_WIRE_OWNER')),
            'details' => (string) ($this->details ?: Configuration::get('DTC_WIRE_DETAILS')),
            'address' => (string) ($this->address ?: Configuration::get('DTC_WIRE_ADDRESS')),
        ];
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->postValidation();
            if (!count($this->_postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->displayBankWire();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    protected function postValidation()
    {
        if (!Tools::getValue('DTC_WIRE_DETAILS')) {
            $this->_postErrors[] = $this->l('Les détails du compte sont obligatoires.');
        }
        if (!Tools::getValue('DTC_WIRE_OWNER')) {
            $this->_postErrors[] = $this->l('Le titulaire du compte est obligatoire.');
        }
        if (!Tools::getValue('DTC_WIRE_ADDRESS')) {
            $this->_postErrors[] = $this->l('L\'adresse de la banque est obligatoire.');
        }
    }

    protected function postProcess()
    {
        Configuration::updateValue('DTC_WIRE_DETAILS', Tools::getValue('DTC_WIRE_DETAILS'));
        Configuration::updateValue('DTC_WIRE_OWNER', Tools::getValue('DTC_WIRE_OWNER'));
        Configuration::updateValue('DTC_WIRE_ADDRESS', Tools::getValue('DTC_WIRE_ADDRESS'));
        Configuration::updateValue('DTC_WIRE_RESERVATION_DAYS', (int) Tools::getValue('DTC_WIRE_RESERVATION_DAYS'));
        Configuration::updateValue(self::FLAG_DISPLAY_PAYMENT_INVITE, (int) Tools::getValue(self::FLAG_DISPLAY_PAYMENT_INVITE));

        $customText = [];
        foreach (Language::getLanguages(false) as $lang) {
            $customText[$lang['id_lang']] = Tools::getValue('DTC_WIRE_CUSTOM_TEXT_' . $lang['id_lang']);
        }
        Configuration::updateValue('DTC_WIRE_CUSTOM_TEXT', $customText);

        $this->_html .= $this->displayConfirmation($this->l('Paramètres enregistrés.'));
    }

    protected function displayBankWire()
    {
        return $this->display(__FILE__, 'views/templates/hook/infos.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active || !$this->checkCurrency($params['cart'])) {
            return [];
        }

        $this->smarty->assign($this->getTemplateVarInfos());

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->l('Payer par virement bancaire'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
            ->setAdditionalInformation($this->fetch('module:dtc_wirepayment/views/templates/hook/payment_intro.tpl'));

        return [$newOption];
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active || !Configuration::get(self::FLAG_DISPLAY_PAYMENT_INVITE)) {
            return;
        }

        $order = $params['order'];
        $wire = OrderStoreHelper::getWireBankDetailsForOrder((int) $order->id, $this->getFallbackWireDetails());

        $totalToPaid = $order->getOrdersTotalPaid() - $order->getTotalPaid();
        $this->smarty->assign([
            'shop_name' => $this->context->shop->name,
            'total' => $this->context->getCurrentLocale()->formatPrice(
                $totalToPaid,
                (new Currency($order->id_currency))->iso_code
            ),
            'bankwireDetails' => Tools::nl2br($wire['details']),
            'bankwireAddress' => Tools::nl2br($wire['address']),
            'bankwireOwner' => $wire['owner'],
            'status' => 'ok',
            'reference' => $order->reference,
            'contact_url' => $this->context->link->getPageLink('contact', true),
        ]);

        return $this->fetch('module:dtc_wirepayment/views/templates/hook/payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currencyOrder = new Currency($cart->id_currency);
        $currenciesModule = $this->getCurrency($cart->id_currency);
        if (is_array($currenciesModule)) {
            foreach ($currenciesModule as $currencyModule) {
                if ($currencyOrder->id == $currencyModule['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function renderForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Coordonnées du compte (fallback)'),
                    'icon' => 'icon-envelope',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Titulaire du compte'),
                        'name' => 'DTC_WIRE_OWNER',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Détails du compte'),
                        'name' => 'DTC_WIRE_DETAILS',
                        'desc' => $this->l('Utilisez {iban} et {bic} : remplacés par le RIB du magasin de la commande.'),
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Adresse de la banque'),
                        'name' => 'DTC_WIRE_ADDRESS',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];

        $fieldsCustomization = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Personnalisation'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Durée de réservation (jours)'),
                        'name' => 'DTC_WIRE_RESERVATION_DAYS',
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Information au client'),
                        'name' => 'DTC_WIRE_CUSTOM_TEXT',
                        'lang' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Afficher les infos sur la page de confirmation'),
                        'name' => self::FLAG_DISPLAY_PAYMENT_INVITE,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fieldsForm, $fieldsCustomization]);
    }

    public function getConfigFieldsValues()
    {
        $customText = [];
        foreach (Language::getLanguages(false) as $lang) {
            $customText[$lang['id_lang']] = Tools::getValue(
                'DTC_WIRE_CUSTOM_TEXT_' . $lang['id_lang'],
                Configuration::get('DTC_WIRE_CUSTOM_TEXT', $lang['id_lang'])
            );
        }

        return [
            'DTC_WIRE_DETAILS' => Tools::getValue('DTC_WIRE_DETAILS', $this->details),
            'DTC_WIRE_OWNER' => Tools::getValue('DTC_WIRE_OWNER', $this->owner),
            'DTC_WIRE_ADDRESS' => Tools::getValue('DTC_WIRE_ADDRESS', $this->address),
            'DTC_WIRE_RESERVATION_DAYS' => Tools::getValue('DTC_WIRE_RESERVATION_DAYS', $this->reservation_days),
            'DTC_WIRE_CUSTOM_TEXT' => $customText,
            self::FLAG_DISPLAY_PAYMENT_INVITE => Tools::getValue(
                self::FLAG_DISPLAY_PAYMENT_INVITE,
                Configuration::get(self::FLAG_DISPLAY_PAYMENT_INVITE)
            ),
        ];
    }

    public function getTemplateVarInfos()
    {
        $cart = $this->context->cart;
        $total = sprintf(
            $this->l('%s (TTC)'),
            $this->context->getCurrentLocale()->formatPrice(
                $cart->getOrderTotal(true, Cart::BOTH),
                $this->context->currency->iso_code
            )
        );

        $wire = OrderStoreHelper::getWireBankDetailsForCart($cart, $this->getFallbackWireDetails());
        $reservationDays = $this->reservation_days;
        if (false === $reservationDays) {
            $reservationDays = 7;
        }

        $customText = Tools::nl2br(Configuration::get('DTC_WIRE_CUSTOM_TEXT', $this->context->language->id) ?: '');

        return [
            'total' => $total,
            'bankwireDetails' => Tools::nl2br($wire['details']),
            'bankwireAddress' => Tools::nl2br($wire['address']),
            'bankwireOwner' => $wire['owner'],
            'bankwireReservationDays' => (int) $reservationDays,
            'bankwireCustomText' => $customText,
        ];
    }
}
