<?php
/**
 * CKC — i18n bridge for hsmultiaccessoriespro (no vendor patch).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/CkcHsmaI18nHelper.php';

class Ckc_hsma_i18n extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_hsma_i18n';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Agence CKC';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('HS MultiAccessories i18n');
        $this->description = $this->l('Localizes MultiAccessories group/product names on FO without patching the vendor module.');
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionFrontControllerInitAfter')
            && $this->registerHook('displayHeader')
            && $this->refreshClassIndex();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * @return bool
     */
    protected function refreshClassIndex()
    {
        if (class_exists('PrestaShopAutoload', false) && method_exists('PrestaShopAutoload', 'getInstance')) {
            try {
                PrestaShopAutoload::getInstance()->generateIndex();
            } catch (Exception $e) {
                // BO clear-cache also regenerates the index.
            }
        }

        return true;
    }

    public function hookActionFrontControllerInitAfter($params)
    {
        $this->registerSmartyPlugins();
    }

    public function hookDisplayHeader($params)
    {
        $this->registerSmartyPlugins();

        return '';
    }

    protected function registerSmartyPlugins()
    {
        static $registered = false;
        if ($registered || !isset($this->context->smarty)) {
            return;
        }
        $registered = true;

        $smarty = $this->context->smarty;
        $plugins = array(
            'ckc_hsma_product_name' => array('CkcHsmaI18nHelper', 'smartyProductName'),
            'ckc_hsma_group_name' => array('CkcHsmaI18nHelper', 'smartyGroupName'),
            'ckc_hsma_table_price' => array('CkcHsmaI18nHelper', 'smartyTablePrice'),
        );

        foreach ($plugins as $name => $callback) {
            // Override may have registered identity fallbacks earlier — replace with real helpers.
            if (isset($smarty->registered_plugins['modifier'][$name])) {
                $smarty->unregisterPlugin('modifier', $name);
            }
            $smarty->registerPlugin('modifier', $name, $callback);
        }
    }
}
