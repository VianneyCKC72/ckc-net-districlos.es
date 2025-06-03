<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class ckc_honeypot extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_honeypot';
        $this->version = '1.0.0';
        $this->author = 'CKC';
        $this->logo_path     = $this->_path . 'logo.png';
        parent::__construct();
        $this->displayName = 'Honeypot for Registration';
        $this->description = 'Ajoute un champ honeypot dans le formulaire d\'inscription pour bloquer les bots.';
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('additionalCustomerFormFields') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('actionObjectCustomerAddBefore');
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->registerStylesheet(
            'honeypotmodule-css',
            'modules/' . $this->name . '/views/css/honeypotmodule.css',
            [
                'media' => 'all',
                'priority' => 150,
            ]
        );
    }

    public function hookAdditionalCustomerFormFields($params)
    {
        $customFields = [];

        $honeypotField = (new FormField())
            ->setName('honeypot')
            ->setType('text')
            ->setLabel('')
            ->setRequired(false);

        $customFields[] = $honeypotField;

        return $customFields;
    }

    public function hookActionObjectCustomerAddBefore($params)
    {
        if (!empty(Tools::getValue('honeypot'))) {
            $this->context->controller->errors[] = $this->l('Une erreur est survenue lors de la création du compte.');

            Tools::redirect('index.php?controller=authentication&back=my-account');
        }
    }

}
