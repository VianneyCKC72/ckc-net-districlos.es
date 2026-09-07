<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckc_Shipping_Estimator extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_shipping_estimator';
        $this->version = '1.0.1';
        $this->author = 'CKC-Net';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Estimation des frais de livraison');
        $this->description = $this->l('Module pour estimer les frais de livraison');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayFooterProduct')
            && $this->registerHook('displayHeader');
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->registerStylesheet(
            'ckc_shipping_estimator-css',
            'modules/' . $this->name . '/assets/css/shipping_estimator.css',
            ['media' => 'all', 'priority' => 150]
        );

        $this->context->controller->registerJavascript(
            'ckc_shipping_estimator-js',
            'modules/' . $this->name . '/assets/js/shipping_estimator.js',
            ['position' => 'bottom', 'priority' => 150]
        );
    }

    public function hookDisplayFooterProduct($params)
    {
        return $this->display(__FILE__, 'views/templates/front/product/modal_shipping_estimator.tpl');
    }
}
