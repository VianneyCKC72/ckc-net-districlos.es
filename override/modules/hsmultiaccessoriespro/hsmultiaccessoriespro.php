<?php
/**
 * Minimal override: localize accessory names used in accessoriesTablePrice JSON.
 * Group titles + HTML labels are handled via theme Smarty modifiers (ckc_hsma_i18n).
 *
 * Resilient: FR display must work even if ckc_hsma_i18n is missing / not installed.
 * This file survives hsmultiaccessoriespro updates (re-clear cache / class index after upgrade).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class HsMultiAccessoriesProOverride extends HsMultiAccessoriesPro
{
    /** @var bool|null */
    protected static $helperAvailable = null;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSmartyModifiers();
    }

    public function hookDisplayHeader()
    {
        $this->ensureSmartyModifiers();

        return parent::hookDisplayHeader();
    }

    public function hookDisplayRightColumnProduct()
    {
        $this->ensureSmartyModifiers();

        return parent::hookDisplayRightColumnProduct();
    }

    public function hookDisplayProductButtons()
    {
        $this->ensureSmartyModifiers();

        return parent::hookDisplayProductButtons();
    }

    public function hookDisplayMultiAccessoriesProduct()
    {
        $this->ensureSmartyModifiers();

        return parent::hookDisplayMultiAccessoriesProduct();
    }

    public function hookDisplayProductTabContent()
    {
        $this->ensureSmartyModifiers();

        return parent::hookDisplayProductTabContent();
    }

    /**
     * @param array $accessory
     *
     * @return array
     */
    protected function formatAccessory(array $accessory = array())
    {
        $this->ensureSmartyModifiers();

        if (isset($accessory['id_accessory']) && self::loadHelper()) {
            $accessory['name'] = CkcHsmaI18nHelper::localizeProductName(
                isset($accessory['name']) ? $accessory['name'] : '',
                (int) $accessory['id_accessory']
            );
        }

        return parent::formatAccessory($accessory);
    }

    /**
     * Soft-load helper: never fatal if module folder is absent on prod.
     *
     * @return bool
     */
    protected static function loadHelper()
    {
        if (self::$helperAvailable !== null) {
            return self::$helperAvailable;
        }

        if (class_exists('CkcHsmaI18nHelper', false)) {
            self::$helperAvailable = true;

            return true;
        }

        $path = _PS_MODULE_DIR_ . 'ckc_hsma_i18n/classes/CkcHsmaI18nHelper.php';
        if (is_file($path)) {
            require_once $path;
        }

        self::$helperAvailable = class_exists('CkcHsmaI18nHelper', false);

        return self::$helperAvailable;
    }

    /**
     * Register ckc_hsma_* modifiers if missing (unknown modifier breaks the whole block).
     * Prefer real helper; otherwise identity (enough for default FR language).
     *
     * @return void
     */
    protected function ensureSmartyModifiers()
    {
        static $registered = false;
        if ($registered || !isset($this->context->smarty) || !is_object($this->context->smarty)) {
            return;
        }
        $registered = true;

        $smarty = $this->context->smarty;
        $useHelper = self::loadHelper();

        $plugins = array(
            'ckc_hsma_product_name' => $useHelper
                ? array('CkcHsmaI18nHelper', 'smartyProductName')
                : array(__CLASS__, 'smartyIdentityProductName'),
            'ckc_hsma_group_name' => $useHelper
                ? array('CkcHsmaI18nHelper', 'smartyGroupName')
                : array(__CLASS__, 'smartyIdentity'),
            'ckc_hsma_table_price' => $useHelper
                ? array('CkcHsmaI18nHelper', 'smartyTablePrice')
                : array(__CLASS__, 'smartyIdentity'),
        );

        foreach ($plugins as $name => $callback) {
            try {
                $already = isset($smarty->registered_plugins['modifier'][$name]);
            } catch (Exception $e) {
                $already = false;
            }

            if ($already) {
                continue;
            }

            try {
                $smarty->registerPlugin('modifier', $name, $callback);
            } catch (Exception $e) {
                // Already registered by another module in this request.
            }
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public static function smartyIdentity($value)
    {
        return $value;
    }

    /**
     * @param string $name
     * @param int $idProduct
     *
     * @return string
     */
    public static function smartyIdentityProductName($name, $idProduct = 0)
    {
        return $name;
    }
}
