<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/CartWarningRule.php';
require_once __DIR__ . '/classes/CartWarningRuleRepository.php';
require_once __DIR__ . '/classes/CartWarningRuleInstaller.php';

class dtc_cartwarnings extends Module
{
    private const CFG_SCHEMA_READY = 'DTC_CARTWARNINGS_SCHEMA_READY';

    /** @var CartWarningEvaluator|null */
    private $evaluator;

    /** @var bool */
    private $schemaJustFixed = false;

    /** @var bool|null */
    private static $schemaReadyCache = null;

    public function __construct()
    {
        $this->name = 'dtc_cartwarnings';
        $this->tab = 'front_office_features';
        $this->version = '1.0.2';
        $this->author = 'districlos';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Avertissements panier');
        $this->description = $this->l('Affiche au panier les avertissements de produits manquants et d\'incompatibilités.');
    }

    public function install()
    {
        $ok = parent::install()
            && $this->installDb()
            && CartWarningRuleInstaller::seedDefaultRules();

        if ($ok) {
            Configuration::updateValue(self::CFG_SCHEMA_READY, 1);
        }

        return $ok;
    }

    public function uninstall()
    {
        CartWarningRuleRepository::clearCache();
        Configuration::deleteByName(self::CFG_SCHEMA_READY);
        self::$schemaReadyCache = null;

        return $this->uninstallDb() && parent::uninstall();
    }

    public function getContent()
    {
        $output = '';
        if (!$this->ensureDatabaseReady()) {
            $output .= $this->displayError(
                $this->l('Impossible de créer les tables :') . ' ' . Db::getInstance()->getMsgError()
            );
        } elseif ($this->schemaJustFixed) {
            $output .= $this->displayConfirmation($this->l('Tables et règles de base créées.'));
        }

        require_once __DIR__ . '/classes/CartWarningAdmin.php';

        return $output . (new CartWarningAdmin($this))->render();
    }

    /**
     * @return array<string, string>
     */
    public function assignCartWarningsToSmarty()
    {
        if (!$this->ensureDatabaseReady()) {
            return [];
        }

        return $this->getEvaluator()->assignToSmarty();
    }

    /**
     * @return bool
     */
    public function ensureDatabaseReady()
    {
        if (self::$schemaReadyCache === true) {
            return true;
        }

        if ((int) Configuration::get(self::CFG_SCHEMA_READY) === 1) {
            return self::$schemaReadyCache = true;
        }

        $created = false;
        if (!$this->tablesExist()) {
            if (!$this->installDb()) {
                return self::$schemaReadyCache = false;
            }
            $created = true;
        }

        $ruleCount = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'dtc_cart_warning_rule`'
        );
        $seeded = false;
        if ($ruleCount === 0) {
            $seeded = CartWarningRuleInstaller::seedDefaultRules();
        }

        if ($created || $seeded) {
            CartWarningRuleRepository::clearCache();
            $this->schemaJustFixed = true;
        }

        $ready = $this->tablesExist();
        if ($ready) {
            Configuration::updateValue(self::CFG_SCHEMA_READY, 1);
        }

        return self::$schemaReadyCache = $ready;
    }

    public function installDb()
    {
        foreach ($this->getInstallSql() as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public function uninstallDb()
    {
        foreach (['dtc_cart_warning_rule_product', 'dtc_cart_warning_rule_lang', 'dtc_cart_warning_rule'] as $table) {
            if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`')) {
                return false;
            }
        }

        return true;
    }

    private function tablesExist()
    {
        $table = _DB_PREFIX_ . 'dtc_cart_warning_rule';
        $rows = Db::getInstance()->executeS('SHOW TABLES LIKE \'' . pSQL($table) . '\'');

        return is_array($rows) && count($rows) > 0;
    }

    /**
     * @return string[]
     */
    private function getInstallSql()
    {
        $engine = defined('_MYSQL_ENGINE_') ? _MYSQL_ENGINE_ : 'InnoDB';

        return [
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_cart_warning_rule` (
                `id_rule` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_parent` INT UNSIGNED DEFAULT NULL,
                `rule_type` VARCHAR(32) NOT NULL,
                `active` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `position` INT NOT NULL DEFAULT 0,
                `missing_product_id` INT UNSIGNED DEFAULT NULL,
                `attribute_product_id` INT UNSIGNED DEFAULT NULL,
                `attribute_contains` VARCHAR(255) DEFAULT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_rule`),
                KEY `idx_parent` (`id_parent`),
                KEY `idx_type_active` (`rule_type`, `active`),
                KEY `idx_position` (`position`)
            ) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_cart_warning_rule_lang` (
                `id_rule` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `message_main` TEXT NOT NULL,
                `message_extra` TEXT DEFAULT NULL,
                PRIMARY KEY (`id_rule`, `id_lang`)
            ) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_cart_warning_rule_product` (
                `id_rule_product` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_rule` INT UNSIGNED NOT NULL,
                `id_product` INT UNSIGNED NOT NULL,
                `role` VARCHAR(16) NOT NULL,
                PRIMARY KEY (`id_rule_product`),
                KEY `idx_rule` (`id_rule`),
                KEY `idx_role` (`role`)
            ) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;',
        ];
    }

    /**
     * @param string $template
     * @param array $vars
     *
     * @return string
     */
    public function renderHookTemplate($template, array $vars)
    {
        $this->smarty->assign($vars);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/' . $template);
    }

    /**
     * @return CartWarningEvaluator
     */
    private function getEvaluator()
    {
        if ($this->evaluator === null) {
            require_once __DIR__ . '/classes/CartWarningEvaluator.php';
            $this->evaluator = new CartWarningEvaluator($this);
        }

        return $this->evaluator;
    }
}
