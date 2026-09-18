<?php
/**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registred Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Dtc_Popup_Gift extends Module implements WidgetInterface
{
    private $templateFile;
    private const CONF_POPUP_VARIANT = 'DTC_POPUP_GIFT_VARIANT';
    private const VARIANT_WHEEL = 'wheel';
    private const VARIANT_NOEL = 'noel';

    public function __construct()
    {
        $this->name = 'dtc_popup_gift';
        $this->tab = 'front_office_features';
        $this->author = 'DISTRICLOS';
        $this->version = '1.0.0';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('DTC popup gift', array(), 'Modules.DtcPopupGift.Admin');
        $this->description = $this->trans('Popup avec animation.', array(), 'Modules.DtcPopupGift.Admin');

        $this->ps_versions_compliancy = array('min' => '1.7.4.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:dtc_popup_gift/views/templates/hook/dtc_popup_gift.tpl';
    }

    public function install()
    {
        return $this->runInstallSteps()
            && $this->installFixtures();
    }

    public function runInstallSteps()
    {
        return parent::install()
            && $this->registerHook('displayHome')
            && $this->registerHook('displayAfterBodyOpeningTag')
            && Configuration::updateValue(self::CONF_POPUP_VARIANT, self::VARIANT_WHEEL);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName(self::CONF_POPUP_VARIANT);
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDtcPopupGiftModule')) {
            $variant = (string) Tools::getValue(self::CONF_POPUP_VARIANT, self::VARIANT_WHEEL);
            if (!in_array($variant, [self::VARIANT_WHEEL, self::VARIANT_NOEL], true)) {
                $variant = self::VARIANT_WHEEL;
            }

            Configuration::updateValue(self::CONF_POPUP_VARIANT, $variant);
            $output .= $this->displayConfirmation($this->trans('Configuration enregistrée.', [], 'Modules.DtcPopupGift.Admin'));
        }

        return $output . $this->renderForm();
    }

    private function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDtcPopupGiftModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => [
                self::CONF_POPUP_VARIANT => (string) Configuration::get(self::CONF_POPUP_VARIANT, self::VARIANT_WHEEL),
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => (int) $this->context->language->id,
        ];

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Réglages', [], 'Modules.DtcPopupGift.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->trans('Popup à afficher', [], 'Modules.DtcPopupGift.Admin'),
                        'name' => self::CONF_POPUP_VARIANT,
                        'options' => [
                            'query' => [
                                ['id' => self::VARIANT_WHEEL, 'name' => $this->trans('Roue', [], 'Modules.DtcPopupGift.Admin')],
                                ['id' => self::VARIANT_NOEL, 'name' => $this->trans('Noël (cadeau)', [], 'Modules.DtcPopupGift.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Enregistrer', [], 'Admin.Actions'),
                ],
            ],
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        $templateFile = $this->getTemplateForVariant();
        
        if (!$this->isCached($templateFile, $this->getCacheId('dtc_popup_gift'))) {
            $variables = $this->getWidgetVariables($hookName, $configuration);
            $variables['module_dir'] = $this->_path;
            $this->smarty->assign($variables);
        }

        return $this->fetch($templateFile, $this->getCacheId('dtc_popup_gift'));
    }
    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        return array(
            'tmcms10blockinfos' => array(),
        );
    }

    private function getTemplateForVariant()
    {
        $variant = (string) Configuration::get(self::CONF_POPUP_VARIANT, self::VARIANT_WHEEL);
        if ($variant === self::VARIANT_NOEL) {
            return 'module:dtc_popup_gift/views/templates/hook/dtc_popup_gift_noel.tpl';
        }

        return 'module:dtc_popup_gift/views/templates/hook/mini-wheel.tpl';
    }

    public function installFixtures()
    {
        return true;
    }

    public function hookDisplayAfterBodyOpeningTag($params)
    {
        return $this->fetch($this->getTemplateForVariant());
    }
}

