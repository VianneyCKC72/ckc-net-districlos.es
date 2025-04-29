<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class ckc_oney_simulator extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_oney_simulator';
        $this->version = '1.0.0';
        $this->author = 'CKC';
        $this->logo_path     = $this->_path . 'logo.png';
        parent::__construct();
        $this->displayName = 'Simulateur Oney';
        $this->description = 'Ajoute un simulateur oney dans le panier.';
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayShoppingCartFooter') &&
            $this->registerHook('header');
    }

    public function hookHeader()
    {
        $controller = Tools::getValue('controller');
    
        if ($controller === 'cart') {
            $this->context->controller->registerStylesheet(
                'oneysimulator-css',
                'modules/' . $this->name . '/views/css/oneysimulator.css',
                [
                    'media' => 'all',
                    'priority' => 150,
                ]
            );
            $this->context->controller->registerJavascript(
                'oneysimulator-js',
                'modules/' . $this->name . '/views/js/oneysimulator.js',
                [
                    'position' => 'bottom',
                    'priority' => 150,
                ]
            );
        }
        
        if ($controller === 'product') {
            $this->context->controller->registerJavascript(
                'oney-loader',
                'https://assets.oney.io/build/loader.min.js',
                [
                    'server' => 'remote',
                    'position' => 'bottom',
                    'priority' => 100,
                ]
            );
            $this->context->controller->registerJavascript(
                'oneysimulator-js-product',
                'modules/' . $this->name . '/views/js/oneysimulator-product.js',
                [
                    'position' => 'bottom',
                    'priority' => 150,
                ]
            );
        }
    }


    public function hookDisplayShoppingCartFooter($params)
    {
        return $this->display(__FILE__, 'views/templates/hook/shopping_cart_footer.tpl');
    }

}
