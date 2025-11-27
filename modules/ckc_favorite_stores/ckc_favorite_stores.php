<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckc_Favorite_Stores extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_favorite_stores';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'CKC-Net';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Magasins favoris');
        $this->description = $this->l('Permet aux clients d’ajouter un magasin en favoris.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayCustomerAccount')
            && $this->installDb();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDb() && $this->registerHook('displayCustomerAccount');
    }

    private function installDb()
    {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckc_favorite_stores` (
                `id_favorite_store` INT AUTO_INCREMENT PRIMARY KEY,
                `id_customer` INT NOT NULL,
                `id_store` int(11) NOT NULL,
                `date_add` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;
        ');
    }

    private function uninstallDb()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ckc_favorite_stores`');
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->registerJavascript(
            'module-ckc-favorite-store',
            $this->_path . 'js/favorite_store.js',
            ['position' => 'bottom', 'priority' => 150]
        );
    }

    public function hookDisplayCustomerAccount()
    {
        return $this->fetch('module:ckc_favorite_stores/views/templates/front/favorite_store.tpl');
    }
}
