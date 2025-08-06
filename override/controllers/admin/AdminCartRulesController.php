<?php
/**
 * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
 */
 
class AdminCartRulesController extends AdminCartRulesControllerCore
{
    /*
    * module: ets_promotion
    * date: 2025-04-03 10:54:57
    * version: 1.1.6
    */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAddcart_rule') || Tools::isSubmit('submitAddcart_ruleAndStay')) {
            $code = Tools::getValue('code');
            if($code && Validate::isCleanHtml($code))
            {
                if(Module::getInstanceByName('ets_promotion')->checkExitCode($code,$this->errors))
                    return true;
            }
        }
        if(Module::isEnabled('etsdiscountcombinations'))
            Module::getInstanceByName('etsdiscountcombinations')->postProcessCartRule();
        return parent::postProcess();
    }
}