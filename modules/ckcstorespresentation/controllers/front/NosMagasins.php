<?php
/**
* 2007-2024 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcstorespresentationNosMagasinsModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
    }

    public function initContent()
    {

        $magasins = Db::getInstance()->executeS('SELECT * from `' . _DB_PREFIX_ . 'ckcstorespresentation`');

        $this->context->controller->addJquery();


        $this->context->smarty->assign(array(
            'magasins' => $magasins,
            'modulePath' => $this->context->link->getModuleLink('ckcstorespresentation', 'NosMagasins'),
            'lang' => Context::getContext()->language->iso_code,
        ));

        if (preg_match("/^1.6/", _PS_VERSION_)) {
            $this->setTemplate('nos-magasins.tpl');
        } else {
            $this->setTemplate('module:ckcstorespresentation/views/templates/front/nos-magasins.tpl');
        };

        return parent::initContent();
    }

    public function postProcess()
    {
        $query = Tools::getValue('query');

        if ($query) {
            $search = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ckcstorespresentation`
            WHERE id_ckcstorespresentation LIKE "%' . pSQL($query) . '%"
            OR departement LIKE "%' . pSQL($query) . '%"
            OR adresse LIKE "%' . pSQL($query) . '%"
            OR ville LIKE "%' . pSQL($query) . '%"
            OR code_postal LIKE "%' . pSQL($query) . '%"
            OR email LIKE "%' . pSQL($query) . '%"
            OR telephone LIKE "%' . pSQL($query) . '%"
            OR horaires_jours LIKE "%' . pSQL($query) . '%"
            OR horaires_heures LIKE "%' . pSQL($query) . '%"');

            $this->context->smarty->assign([
                'magasins' => $search,
                'modulePath' => $this->context->link->getModuleLink('ckcstorespresentation', 'NosMagasins'),
                'lang' => Context::getContext()->language->iso_code,
                'elements' => $search
            ]);

            $html = $this->context->smarty->fetch('module:ckcstorespresentation/views/templates/front/ajax.tpl');

            die($html);
        } elseif ($query == '') {
            $search = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ckcstorespresentation`');

            $this->context->smarty->assign([
                'magasins' => $search,
                'modulePath' => $this->context->link->getModuleLink('ckcstorespresentation', 'NosMagasins'),
                'lang' => Context::getContext()->language->iso_code,
                'elements' => $search
            ]);

            $html = $this->context->smarty->fetch('module:ckcstorespresentation/views/templates/front/ajax.tpl');

            die($html);
        }
    }

}