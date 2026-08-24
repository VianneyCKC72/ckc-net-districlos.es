<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcProductExtraAdvice extends Module
{
    public function __construct()
    {
        $this->name = 'ckcproductextraadvice';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'CKC-Net';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Conseils supplémentaires sur le produit');
        $this->description = $this->l('Ajoute des champs de conseil et de maintenance dans la fiche produit');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('displayCkcProductExtraAdvice')
            && $this->installDB();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDB();
    }

    protected function installDB()
    {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ckc_product_extra` (
                `id_product` INT(10) UNSIGNED NOT NULL,
                `extra_advice_title` TEXT,
                `extra_advice_content` TEXT,
                `maintenance_title` TEXT,
                `maintenance_content` TEXT,
                PRIMARY KEY (`id_product`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;
        ');
    }

    protected function uninstallDB()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ckc_product_extra`');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = $params['id_product'];
        
        $data = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ckc_product_extra` WHERE id_product = '.$id_product);
        
        if (!$data) {
            $data = [
                'extra_advice_title'   => "",
                'extra_advice_content' => "",
                'maintenance_title'    => "",
                'maintenance_content'  => "",
            ];
        }
        
        $this->context->smarty->assign([
            'ckc_product_extra' => $data,
            'params_ckc_product_extra' => $id_product
        ]);

        
        return $this->display(__FILE__, 'views/templates/admin/product_extra.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        $id = isset($params['product']) ? (int)$params['product']->id : (int)Tools::getValue('id_product');
        if ($id > 0) {
            $this->saveExtraFields($id);
        }
    }

    public function hookActionProductAdd($params)
    {
        $id = isset($params['product']) ? (int)$params['product']->id : (int)Tools::getValue('id_product');
        if ($id > 0) {
            $this->saveExtraFields($id);
        }
    }

    private function saveExtraFields($id_product)
    {
    
        if (!Tools::getIsset('extra_advice_title')
            && !Tools::getIsset('extra_advice_content')
            && !Tools::getIsset('maintenance_title')
            && !Tools::getIsset('maintenance_content')

        ) {
            return;
        }
    
        $fields = [
            'extra_advice_title'   => pSQL(Tools::getValue('extra_advice_title'), true),
            'extra_advice_content' => pSQL(Tools::getValue('extra_advice_content'), true),
            'maintenance_title'    => pSQL(Tools::getValue('maintenance_title'), true),
            'maintenance_content'  => pSQL(Tools::getValue('maintenance_content'), true),
        ];
    
        $exists = Db::getInstance()->getValue('SELECT id_product FROM `'._DB_PREFIX_.'ckc_product_extra` WHERE id_product = '.$id_product);
        
        if ($exists) {
            Db::getInstance()->update('ckc_product_extra', $fields, 'id_product = '.$id_product);
        } else {
            $fields['id_product'] = $id_product;
            Db::getInstance()->insert('ckc_product_extra', $fields);
        }
    }
    
    public function hookDisplayCkcProductExtraAdvice($params)
    {
        if (!isset($params['product']) || !isset($params['product']['id_product'])) {
            return '';
        }
    
        $id_product = (int) $params['product']['id_product'];
    
        $extra = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ckc_product_extra` WHERE id_product = ' . $id_product);
    
        if (!$extra || ($extra['extra_advice_title'] == "" && $extra['maintenance_title'] == "")) {
            return '';
        }else{
            $this->context->smarty->assign([
                'extra_advice_title'   => $extra['extra_advice_title'],
                'extra_advice_content' => $extra['extra_advice_content'],
                'maintenance_title'    => $extra['maintenance_title'],
                'maintenance_content'  => $extra['maintenance_content'],
            ]);
        }
    
        return $this->display(__FILE__, 'views/templates/front/product_extra.tpl');
    }
}
