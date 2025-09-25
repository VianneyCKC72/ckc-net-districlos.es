<?php
/**
 * Module Product FAQ
 * Ajoute des FAQ personnalisées aux produits
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductFaq extends Module
{
    public function __construct()
    {
        $this->name = 'productfaq';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'FAQ Produits';
        $this->description = 'Ajoutez des FAQ personnalisées à vos produits.';
        $this->confirmUninstall = 'Êtes-vous sûr de vouloir désinstaller ce module ?';
    }

    public function install()
    {
        // D'abord, supprimer la table existante si elle existe
        $this->dropTables();
        
        return parent::install()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('displayProductExtraContent')
            && $this->createTables();
    }

    public function enable($force_all = false)
    {
        return parent::enable($force_all)
            && $this->registerHook('displayProductExtraContent');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->dropTables();
    }

    private function createTables()
    {
        // Vérifier si la table existe et la supprimer
        $sql = "SHOW TABLES LIKE '" . _DB_PREFIX_ . "product_faq'";
        $result = Db::getInstance()->executeS($sql);
        
        if ($result) {
            echo "Table existante détectée, suppression...\n";
            $this->dropTables();
        }
        
        $sql = 'CREATE TABLE `' . _DB_PREFIX_ . 'product_faq` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `active` tinyint(1) NOT NULL DEFAULT 1,
            `question` text NOT NULL,
            `answer` text NOT NULL,
            PRIMARY KEY (`id`),
            KEY `id_product` (`id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $result = Db::getInstance()->execute($sql);
        if ($result) {
            echo "Table créée avec succès\n";
        } else {
            echo "Erreur lors de la création de la table: " . Db::getInstance()->getMsgError() . "\n";
        }
        
        return $result;
    }

    private function dropTables()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_faq`');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int)$params['id_product'];
        if (!$id_product) {
            return '';
        }

        $faqs = $this->getFaqsByProduct($id_product);

        $this->context->smarty->assign([
            'faqs' => $faqs,
            'id_product' => $id_product,
            'module_dir' => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_faq.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        // Les FAQ sont sauvegardées via AJAX, pas via ce hook
        return;
    }

    public function hookDisplayProductExtraContent($params)
    {
        if ($this->context->controller) {
            $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        }
        return '';
    }

    public function getFaqsByProduct($id_product, $active_only = false)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'product_faq` WHERE id_product = ' . (int)$id_product;
        
        if ($active_only) {
            $sql .= ' AND active = 1';
        }

        $results = Db::getInstance()->executeS($sql);
        
        if (!$results) {
            return [];
        }

        return $results;
    }
}
