<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminCarrierWizardController extends AdminCarrierWizardControllerCore
{
    public function getStepOneFieldsValues($carrier)
    {
        $values = parent::getStepOneFieldsValues($carrier);

        if (Module::isEnabled('dtc_carrier_badge')) {
            $module = Module::getInstanceByName('dtc_carrier_badge');
            $values = array_merge($values, $module->getCarrierBadgeFieldsValues($carrier));
        }

        return $values;
    }

    public function renderGenericForm($fields_form, $fields_value, $tpl_vars = [])
    {
        if (Module::isEnabled('dtc_carrier_badge')
            && isset($fields_form['form']['form']['id_form'])
            && $fields_form['form']['form']['id_form'] === 'step_carrier_general'
        ) {
            $module = Module::getInstanceByName('dtc_carrier_badge');
            $fields_form['form']['form']['input'][] = $module->getCarrierBadgeFormField();
        }

        return parent::renderGenericForm($fields_form, $fields_value, $tpl_vars);
    }
}
