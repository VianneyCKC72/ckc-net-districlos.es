<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ckc_smsoptin extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_smsoptin';
        $this->version = '1.0.1';
        $this->author = 'CKC';
        $this->tab = 'administration';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('SMS Opt-in');
        $this->description = $this->l('Ajoute une case pour recevoir les bons plans et promotions par SMS.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayCustomerAccountForm')
            && $this->registerHook('actionCustomerAccountAdd')
            && $this->registerHook('displayAdminCustomersForm')
            && $this->registerHook('displayAdminCustomers')
            && $this->registerHook('actionObjectCustomerAddAfter')
            && $this->registerHook('actionObjectCustomerUpdateAfter')
            && $this->registerHook('actionCustomerFormBuilderModifier')
            && $this->registerHook('actionObjectCustomerUpdateAfter')
            && $this->registerHook('actionCustomerGridDefinitionModifier')
            && $this->registerHook('actionCustomerGridQueryBuilderModifier')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->alterCustomerTable(true);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->alterCustomerTable(false);
    }

    private function alterCustomerTable($add = true)
    {
        $sql = $add
            ? 'ALTER TABLE `' . _DB_PREFIX_ . 'customer` ADD COLUMN IF NOT EXISTS `sms_optin` TINYINT(1) NOT NULL DEFAULT 0'
            : 'ALTER TABLE `' . _DB_PREFIX_ . 'customer` DROP COLUMN `sms_optin`';

        return Db::getInstance()->execute($sql);
    }

    public function hookDisplayCustomerAccountForm($params)
    {
        $sms_optin = Db::getInstance()->getValue(
            'SELECT sms_optin FROM `' . _DB_PREFIX_ . 'customer` WHERE id_customer = ' . (int)$this->context->customer->id
        );

        $this->context->smarty->assign([
            'sms_optin' => $sms_optin,
        ]);
        return $this->display(__FILE__, 'views/templates/hook/sms_optin_checkbox.tpl');
    }

    public function hookActionCustomerAccountAdd($params)
    {
        $sms_optin = Tools::getValue('sms_optin', 0);
        $params['newCustomer']->sms_optin = (int)$sms_optin;
        Db::getInstance()->update('customer', ['sms_optin' => (int)$sms_optin], 'id_customer = ' . (int)$params['newCustomer']->id);
    }

    public function hookDisplayAdminCustomers($params)
    {
        $sms_optin = Db::getInstance()->getValue(
            'SELECT sms_optin FROM `' . _DB_PREFIX_ . 'customer` WHERE id_customer = ' . (int)$this->context->customer->id
        );

        $status_text = $sms_optin ? 'Inscrit' : 'Non inscrit';
        $status_class = $sms_optin ? 'badge-success' : 'badge-danger';
        $icon = $sms_optin ? 'check' : 'close';

        $this->context->smarty->assign([
            'status_text' => $status_text,
            'status_class' => $status_class,
            'icon' => $icon,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/sms_optin_status.tpl');
    }

    public function hookActionAdminControllerSetMedia()
    {
        if ($this->context->controller->controller_name === 'AdminCustomers') {
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        }
    }

    public function hookActionCustomerFormBuilderModifier(array $params)
    {
        $customerId = (int) $params['id'];
        $smsOptin = Db::getInstance()->getValue(
            'SELECT sms_optin FROM ' . _DB_PREFIX_ . 'customer WHERE id_customer = ' . $customerId
        );

        $formBuilder = $params['form_builder'];

        $formBuilder->add('sms_optin', \PrestaShopBundle\Form\Admin\Type\SwitchType::class, [
            'label' => $this->l('Recevoir les bons plans et promotions par SMS'),
            'required' => false,
            'data' => (bool) $smsOptin,
            'disabled' => true,
        ]);
    }

    public function hookActionCustomerGridDefinitionModifier(array $params)
    {
        /** @var PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinition $definition */
        $definition = $params['definition'];
        $definition->getColumns()->addAfter(
            'active',
            (new \PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\StatusColumn('sms_optin'))
                ->setName($this->l('Promotions SMS'))
                ->setOptions([
                    'field' => 'sms_optin',
                    'clickable' => false
                ])
        );
    }

    public function hookActionCustomerGridQueryBuilderModifier(array $params)
    {
        /** @var Doctrine\DBAL\Query\QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];
        $searchQueryBuilder->addSelect('c.sms_optin');
    }


    public function hookActionObjectCustomerAddAfter($params)
    {
        $sms_optin = Tools::getValue('sms_optin', 0);
        Db::getInstance()->update('customer', ['sms_optin' => (int)$sms_optin], 'id_customer = ' . (int)$params['object']->id);
    }

    public function hookActionObjectCustomerUpdateAfter($params)
    {
        $sms_optin = Tools::getValue('sms_optin', 0);
        Db::getInstance()->update('customer', ['sms_optin' => (int)$sms_optin], 'id_customer = ' . (int)$params['object']->id);
    }

    private function updateSmsOptinField($customer)
    {
        $sms_optin = Tools::getValue('sms_optin', 0);
        Db::getInstance()->update('customer', ['sms_optin' => (int)$sms_optin], 'id_customer = ' . (int)$customer->id);
    }
}
