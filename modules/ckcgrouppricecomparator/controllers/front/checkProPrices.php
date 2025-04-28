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

class CkcgrouppricecomparatorCheckProPricesModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        $allproducts = Db::getInstance()->executeS('SELECT id_product FROM '._DB_PREFIX_.'product');
        
        $id_shop = Context::getContext()->shop->id;
        $id_currency = Context::getContext()->currency->id;
        $id_country = Context::getContext()->country->id;
        $id_lang = Context::getContext()->language->id;
        $productsResult = [];

        foreach($allproducts as $key => $product) {
            $specificPrice = new SpecificPrice();
            $pricePro = $specificPrice->getSpecificPrice($product['id_product'], $id_shop, $id_currency, $id_country, 10, 1, null);
            // dump($pricePro);
            $productInstance = new Product($product['id_product']);

            if(null != $pricePro && $pricePro['price'] > $productInstance->price && $pricePro['id_group'] == 10) {
                
                $productsResult[$key]['id_product'] = $product['id_product'];
                // $productsResult[$key]['name'] = $productInstance->name[$id_lang];
                // $productsResult[$key]['reduction'] = $productInstance->price - $pricePro['price'];
                // $productsResult[$key]['pricePro'] = $pricePro['price'];
                // $productsResult[$key]['priceStandard'] = $productInstance->price;
            }
        }

        
        // if (preg_match("/^1.6/", _PS_VERSION_)) {
        //     $path_mail_alert = 'ckcgrouppricecomparator/mails/';
        // } else {
        //     $path_mail_alert = 'ckcgrouppricecomparator/mails';
        // }

        if(null != $productsResult) {

            Mail::Send(
                (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
                'mailalert', // email template file to be use
                'Alert Module Tarifs Pro Elevés', // email subject
                array(
                    '{email}' => Configuration::get('PS_SHOP_EMAIL'), // sender email address
                    '{message}' => 'Alert Module Tarifs Pro Elevés',
                ),
                Configuration::get('CKCGROUPPRICECOMPARATOR_ALERT_EMAIL'), // receiver email address
                NULL, //receiver name
                NULL, //from email address
                NULL,  //from name
                NULL, //file attachment
                NULL, //mode smtp
                _PS_MODULE_DIR_ . 'ckcgrouppricecomparator/mails' //custom template path
            );

            Mail::Send(
                (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
                'mailalert', // email template file to be use
                'Alert Module Tarifs Pro Elevés', // email subject
                array(
                    '{email}' => Configuration::get('PS_SHOP_EMAIL'), // sender email address
                    '{message}' => 'Alert Module Tarifs Pro Elevés',
                ),
                Configuration::get('CKCGROUPPRICECOMPARATOR_ALERT_EMAIL_2'), // receiver email address
                NULL, //receiver name
                NULL, //from email address
                NULL,  //from name
                NULL, //file attachment
                NULL, //mode smtp
                _PS_MODULE_DIR_ . 'ckcgrouppricecomparator/mails' //custom template path
            );
                  
        }

        $this->setTemplate('module:ckcgrouppricecomparator/views/templates/front/checkProPrices.tpl');

        return parent::initContent();
    }
}