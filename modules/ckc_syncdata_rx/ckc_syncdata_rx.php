<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckc_Syncdata_Rx extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_syncdata_rx';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'ckc-net';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Synchronisation de données (RX)');
        $this->description = $this->l('Synchronise clients, commandes, paniers et devis entre deux boutiques.');
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue('CKC_SYNCDATA_RX_API_TOKEN', '');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('CKC_SYNCDATA_RX_API_TOKEN');
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit_syncreceiver')) {
            Configuration::updateValue('CKC_SYNCDATA_RX_API_TOKEN', Tools::getValue('CKC_SYNCDATA_RX_API_TOKEN'));
            $output .= $this->displayConfirmation($this->l('Configuration mise à jour.'));
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->fields_value = [
            'CKC_SYNCDATA_RX_API_TOKEN' => Configuration::get('CKC_SYNCDATA_RX_API_TOKEN'),
        ];

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('API Token'),
                        'name' => 'CKC_SYNCDATA_RX_API_TOKEN',
                        'required' => true
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $output .= $helper->generateForm([$form]);
        return $output;
    }
}
