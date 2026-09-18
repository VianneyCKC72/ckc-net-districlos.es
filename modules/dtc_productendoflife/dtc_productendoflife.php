<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_ProductEndOfLife extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_productendoflife';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'DTC';
        
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Gestion fin de vie des déclinaisons');
        $this->description = $this->l('Permet de marquer des déclinaisons de produits comme "fin de vie" et d\'afficher un message spécial dans le displayStock.');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
    }

    public function getContent()
    {
        $output = '';
        
        // Récupérer toutes les déclinaisons en fin de vie
        $endOfLifeProducts = $this->getEndOfLifeList();
        
        $this->context->smarty->assign([
            'end_of_life_products' => $endOfLifeProducts,
            'total_count' => count($endOfLifeProducts),
            'link' => $this->context->link,
        ]);
        
        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function install()
    {
        return parent::install()
            && $this->createTable()
            && $this->registerHook('displayAdminProductsExtra');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->dropTable();
    }

    private function createTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_attribute_end_of_life` (
            `id_product_attribute` int(11) NOT NULL,
            `end_of_life` tinyint(1) NOT NULL DEFAULT 0,
            `stock_threshold` int(10) UNSIGNED DEFAULT 1,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_product_attribute`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    private function dropTable()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_attribute_end_of_life`';
        return Db::getInstance()->execute($sql);
    }


    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int)$params['id_product'];
        
        // Vérifier que le produit existe
        if (!$id_product) {
            return '';
        }
        
        // Récupérer toutes les déclinaisons du produit avec les méthodes natives
        $product = new Product($id_product, false, $this->context->language->id);
        
        // Vérifier que le produit est valide
        if (!Validate::isLoadedObject($product)) {
            return '';
        }
        
        $combinations = $product->getAttributesResume($this->context->language->id);
        
        // Vérifier si le produit a des déclinaisons
        if (empty($combinations)) {
            return ''; // Ne pas afficher le module si pas de déclinaisons
        }
        
        // Formater les données pour le template
        $formattedCombinations = [];
        foreach ($combinations as $combination) {
            $formattedCombinations[] = [
                'id_product_attribute' => $combination['id_product_attribute'],
                'reference' => isset($combination['reference']) ? $combination['reference'] : '',
                'ean13' => isset($combination['ean13']) ? $combination['ean13'] : '',
                'upc' => isset($combination['upc']) ? $combination['upc'] : '',
                'isbn' => isset($combination['isbn']) ? $combination['isbn'] : '',
                'weight' => isset($combination['weight']) ? $combination['weight'] : 0,
                'unit_price_impact' => isset($combination['unit_price_impact']) ? $combination['unit_price_impact'] : 0,
                'attributes' => isset($combination['attribute_designation']) ? $combination['attribute_designation'] : 'Aucun attribut',
            ];
        }
        
        // Récupérer les statuts fin de vie existants
        $endOfLifeStatus = $this->getEndOfLifeStatus($id_product);
        
        // Pas de traitement de formulaire ici, on utilise AJAX
        
        $this->context->smarty->assign([
            'id_product' => $id_product,
            'combinations' => $formattedCombinations,
            'end_of_life_status' => $endOfLifeStatus,
            'module_dir' => $this->_path,
        ]);

        return $this->fetch('module:dtc_productendoflife/views/templates/hook/admin_link.tpl');
    }



    private function getEndOfLifeStatus($id_product)
    {
        $sql = 'SELECT pa.id_product_attribute, eol.end_of_life
                FROM ' . _DB_PREFIX_ . 'product_attribute pa
                LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute_end_of_life eol ON pa.id_product_attribute = eol.id_product_attribute
                WHERE pa.id_product = ' . (int)$id_product;

        $results = Db::getInstance()->executeS($sql);
        $status = [];
        
        foreach ($results as $result) {
            $status[$result['id_product_attribute']] = (bool)$result['end_of_life'];
        }

        return $status;
    }


    public function isEndOfLife($id_product_attribute)
    {
        $sql = 'SELECT end_of_life FROM ' . _DB_PREFIX_ . 'product_attribute_end_of_life WHERE id_product_attribute = ' . (int)$id_product_attribute;
        return (bool)Db::getInstance()->getValue($sql);
    }

    private function getEndOfLifeList()
    {
        // Récupérer les déclinaisons en fin de vie (sans doublons)
        $sql = 'SELECT 
                    eol.id_product_attribute,
                    eol.end_of_life,
                    eol.stock_threshold,
                    MAX(eol.date_add) as date_add,
                    MAX(eol.date_upd) as date_upd,
                    pl.name as product_name,
                    pa.reference,
                    pa.id_product,
                    (SELECT GROUP_CONCAT(CONCAT(agl2.name, ": ", al2.name) SEPARATOR ", ")
                     FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac2
                     LEFT JOIN ' . _DB_PREFIX_ . 'attribute a2 ON pac2.id_attribute = a2.id_attribute
                     LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al2 ON a2.id_attribute = al2.id_attribute AND al2.id_lang = ' . (int)$this->context->language->id . '
                     LEFT JOIN ' . _DB_PREFIX_ . 'attribute_group_lang agl2 ON a2.id_attribute_group = agl2.id_attribute_group AND agl2.id_lang = ' . (int)$this->context->language->id . '
                     WHERE pac2.id_product_attribute = eol.id_product_attribute
                    ) as attributes
                FROM ' . _DB_PREFIX_ . 'product_attribute_end_of_life eol
                LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON eol.id_product_attribute = pa.id_product_attribute
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON pa.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . '
                WHERE eol.end_of_life = 1
                GROUP BY eol.id_product_attribute
                ORDER BY MAX(eol.date_upd) DESC';
        
        return Db::getInstance()->executeS($sql);
    }
}
