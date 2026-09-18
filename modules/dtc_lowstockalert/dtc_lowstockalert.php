<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_Lowstockalert extends Module
{
    public const CONFIG_ENABLED = 'DTC_LOWSTOCK_ENABLED';
    public const CONFIG_THRESHOLD = 'DTC_LOWSTOCK_THRESHOLD';
    public const CONFIG_EMAILS = 'DTC_LOWSTOCK_EMAILS';

    public function __construct()
    {
        $this->name = 'dtc_lowstockalert';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Alerte stock bas');
        $this->description = $this->l('Envoie un e-mail lorsque le stock national d\'un produit passe sous un seuil.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue(self::CONFIG_ENABLED, 1)
            && Configuration::updateValue(self::CONFIG_THRESHOLD, 10)
            && Configuration::updateValue(self::CONFIG_EMAILS, Configuration::get('PS_SHOP_EMAIL'))
            && $this->installDb();
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CONFIG_ENABLED);
        Configuration::deleteByName(self::CONFIG_THRESHOLD);
        Configuration::deleteByName(self::CONFIG_EMAILS);

        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dtc_lowstock_alert`');

        return parent::uninstall();
    }

    private function installDb(): bool
    {
        return (bool) Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_lowstock_alert` (
                `id_product` INT(10) UNSIGNED NOT NULL,
                `id_product_attribute` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `alert_sent` TINYINT(1) NOT NULL DEFAULT 0,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_product`, `id_product_attribute`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDtcLowstockalert')) {
            Configuration::updateValue(self::CONFIG_ENABLED, (int) Tools::getValue(self::CONFIG_ENABLED));
            Configuration::updateValue(self::CONFIG_THRESHOLD, max(0, (int) Tools::getValue(self::CONFIG_THRESHOLD)));
            Configuration::updateValue(self::CONFIG_EMAILS, pSQL(Tools::getValue(self::CONFIG_EMAILS)));
            $output .= $this->displayConfirmation($this->l('Paramètres enregistrés.'));
        }

        if (Tools::isSubmit('submitDtcLowstockalertRun')) {
            $count = $this->processLowStockAlerts();
            $output .= $this->displayConfirmation(sprintf($this->l('Vérification terminée : %d nouvelle(s) alerte(s).'), $count));
        }

        return $output . $this->renderForm();
    }

    private function renderForm(): string
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Alerte stock bas'),
                    'icon' => 'icon-envelope',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activer'),
                        'name' => self::CONFIG_ENABLED,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Seuil de stock'),
                        'name' => self::CONFIG_THRESHOLD,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Alerte si le stock national (tous magasins) est inférieur ou égal à cette valeur.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('E-mails destinataires'),
                        'name' => self::CONFIG_EMAILS,
                        'desc' => $this->l('Séparez plusieurs adresses par une virgule.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'name' => 'submitDtcLowstockalert',
                ],
                'buttons' => [
                    [
                        'title' => $this->l('Lancer une vérification maintenant'),
                        'name' => 'submitDtcLowstockalertRun',
                        'type' => 'submit',
                        'class' => 'btn btn-default pull-right',
                        'icon' => 'process-icon-refresh',
                    ],
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submitDtcLowstockalert';
        $helper->fields_value = [
            self::CONFIG_ENABLED => Configuration::get(self::CONFIG_ENABLED),
            self::CONFIG_THRESHOLD => Configuration::get(self::CONFIG_THRESHOLD),
            self::CONFIG_EMAILS => Configuration::get(self::CONFIG_EMAILS),
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * Vérifie les stocks et envoie un digest si de nouvelles alertes.
     *
     * @return int Nombre de déclinaisons nouvellement alertées
     */
    public function processLowStockAlerts(): int
    {
        if (!(bool) Configuration::get(self::CONFIG_ENABLED)) {
            return 0;
        }

        $threshold = (int) Configuration::get(self::CONFIG_THRESHOLD);
        if ($threshold < 0) {
            return 0;
        }

        $emails = $this->getRecipientEmails();
        if (empty($emails)) {
            return 0;
        }

        $this->resetAlertsAboveThreshold($threshold);
        $rows = $this->getNewLowStockRows($threshold);
        if (empty($rows)) {
            return 0;
        }

        if ($this->sendDigestMail($rows, $threshold, $emails)) {
            $this->markAlertsSent($rows);
        }

        return count($rows);
    }

    /**
     * @return string[]
     */
    private function getRecipientEmails(): array
    {
        $raw = (string) Configuration::get(self::CONFIG_EMAILS);
        $emails = [];
        foreach (preg_split('/[,;\s]+/', $raw) as $email) {
            $email = trim($email);
            if ($email !== '' && Validate::isEmail($email)) {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }

    private function resetAlertsAboveThreshold(int $threshold): void
    {
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'dtc_lowstock_alert` a
            INNER JOIN (
                SELECT id_product, id_product_attribute, SUM(quantity) AS qty
                FROM `' . _DB_PREFIX_ . 'ckcstockstores_stocks`
                WHERE id_product > 0 AND id_store != 19
                GROUP BY id_product, id_product_attribute
                HAVING qty > ' . (int) $threshold . '
            ) s ON s.id_product = a.id_product AND s.id_product_attribute = a.id_product_attribute
            SET a.alert_sent = 0, a.date_upd = NOW()
            WHERE a.alert_sent = 1'
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getNewLowStockRows(int $threshold): array
    {
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $idShop = (int) Context::getContext()->shop->id;

        $sql = 'SELECT s.id_product, s.id_product_attribute, SUM(s.quantity) AS qty,
                       MAX(pl.name) AS name,
                       MAX(IFNULL(NULLIF(pa.reference, \'\'), p.reference)) AS reference
                FROM `' . _DB_PREFIX_ . 'ckcstockstores_stocks` s
                INNER JOIN `' . _DB_PREFIX_ . 'product` p
                    ON p.id_product = s.id_product AND p.active = 1
                INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON pl.id_product = p.id_product
                    AND pl.id_lang = ' . $idLang . '
                    AND pl.id_shop = ' . $idShop . '
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
                    ON pa.id_product_attribute = s.id_product_attribute
                LEFT JOIN `' . _DB_PREFIX_ . 'dtc_lowstock_alert` a
                    ON a.id_product = s.id_product
                    AND a.id_product_attribute = s.id_product_attribute
                WHERE s.id_product > 0
                  AND s.id_store != 19
                  AND (a.alert_sent IS NULL OR a.alert_sent = 0)
                GROUP BY s.id_product, s.id_product_attribute
                HAVING qty <= ' . (int) $threshold . '
                ORDER BY qty ASC, name ASC
                LIMIT 200';

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param string[] $emails
     */
    private function sendDigestMail(array $rows, int $threshold, array $emails): bool
    {
        $htmlItems = '';
        $textItems = '';
        foreach ($rows as $row) {
            $label = trim((string) $row['name']);
            $ref = trim((string) $row['reference']);
            if ($ref !== '') {
                $label .= ' [' . $ref . ']';
            }
            if ((int) $row['id_product_attribute'] > 0) {
                $label .= ' (attr #' . (int) $row['id_product_attribute'] . ')';
            }
            $line = $label . ' : ' . (int) $row['qty'];
            $htmlItems .= '<li>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>';
            $textItems .= '- ' . $line . "\n";
        }

        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $templateVars = [
            '{threshold}' => (string) $threshold,
            '{products_list}' => $htmlItems,
            '{products_text}' => $textItems,
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{shop_url}' => Context::getContext()->link->getBaseLink(),
        ];

        $templatePath = _PS_MODULE_DIR_ . $this->name . '/mails/';
        $subject = $this->l('Alerte stock bas');
        $ok = true;

        foreach ($emails as $email) {
            $sent = Mail::Send(
                $idLang,
                'lowstock',
                $subject,
                $templateVars,
                $email,
                null,
                (string) Configuration::get('PS_SHOP_EMAIL'),
                (string) Configuration::get('PS_SHOP_NAME'),
                null,
                null,
                $templatePath,
                false,
                (int) Context::getContext()->shop->id
            );
            $ok = $ok && (bool) $sent;
        }

        return $ok;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function markAlertsSent(array $rows): void
    {
        foreach ($rows as $row) {
            Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'dtc_lowstock_alert`
                    (`id_product`, `id_product_attribute`, `alert_sent`, `date_upd`)
                 VALUES (
                    ' . (int) $row['id_product'] . ',
                    ' . (int) $row['id_product_attribute'] . ',
                    1,
                    NOW()
                 )
                 ON DUPLICATE KEY UPDATE `alert_sent` = 1, `date_upd` = NOW()'
            );
        }
    }
}
