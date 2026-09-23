<?php
/**
 * Product Guide Module
 * Ajoute un guide personnalisé sur la page produit
 *
 * @author DISTRICLOS
 * @version 1.1.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductGuide extends Module
{
    public function __construct()
    {
        $this->name = 'productguide';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Guide produit');
        $this->description = $this->l('Ajoute un guide personnalisé sur la page produit avec un panneau latéral');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        if (Tools::getValue('ajax') && Tools::getValue('action') == 'saveGuide') {
            $this->ajaxProcessSaveGuide();
        }
    }

    public function ajaxProcessSaveGuide()
    {
        header('Content-Type: application/json');

        $id_product = (int) Tools::getValue('id_product');
        $active = (int) Tools::getValue('guide_active');
        $content = Tools::getValue('guide_content');
        $id_lang = (int) Tools::getValue('id_lang', Context::getContext()->language->id);
        if ($id_lang <= 0) {
            $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        if (!$id_product) {
            die(json_encode(['success' => false, 'message' => 'ID produit manquant']));
        }

        $result = $this->saveProductGuide($id_product, $active, $content, $id_lang);

        if ($result) {
            die(json_encode(['success' => true, 'message' => 'Guide enregistré avec succès']));
        }

        die(json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']));
    }

    public function install()
    {
        return $this->installSQL()
            && parent::install()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('displayAfterProductThumbs')
            && $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallSQL();
    }

    private function installSQL()
    {
        $sql = file_get_contents(dirname(__FILE__) . '/sql/install.sql');
        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);

        return Db::getInstance()->execute($sql) && self::ensureLangTable();
    }

    private function uninstallSQL()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_guide_lang`')
            && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_guide`');
    }

    public static function ensureLangTable()
    {
        return (bool) Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_guide_lang` (
            `id_product` INT(11) NOT NULL,
            `id_lang` INT UNSIGNED NOT NULL,
            `guide_content` TEXT NOT NULL,
            PRIMARY KEY (`id_product`, `id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int) $params['id_product'];
        $guide = $this->getProductGuide($id_product);

        $this->context->smarty->assign([
            'guide_active' => $guide ? $guide['active'] : 0,
            'guide_content' => $guide ? $guide['guide_content'] : '',
            'id_product' => $id_product,
            'link' => $this->context->link,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function hookDisplayAfterProductThumbs($params)
    {
        $id_product = (int) $params['product']['id_product'];
        $guide = $this->getProductGuide($id_product);

        if (!$guide || !$guide['active']) {
            return '';
        }

        $this->context->smarty->assign([
            'guide_content' => $guide['guide_content'],
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product_guide.tpl');
    }

    public function hookDisplayHeader($params)
    {
        if ($this->context->controller instanceof ProductController) {
            $id_product = (int) Tools::getValue('id_product');
            $guide = $this->getProductGuide($id_product);

            if ($guide && $guide['active']) {
                $this->context->controller->registerStylesheet(
                    'module-productguide-css',
                    'modules/' . $this->name . '/views/css/front.css',
                    ['media' => 'all', 'priority' => 150]
                );

                $this->context->controller->registerJavascript(
                    'module-productguide-js',
                    'modules/' . $this->name . '/views/js/front.js',
                    ['position' => 'bottom', 'priority' => 150]
                );
            }
        }
    }

    private function getProductGuide($id_product, $id_lang = null)
    {
        self::ensureLangTable();
        if ($id_lang === null) {
            $id_lang = (int) Context::getContext()->language->id;
        }

        return Db::getInstance()->getRow(
            'SELECT g.id_product_guide, g.id_product, g.active, g.date_add, g.date_upd,
                    COALESCE(NULLIF(TRIM(l.guide_content), \'\'), g.guide_content) AS guide_content
             FROM `' . _DB_PREFIX_ . 'product_guide` g
             LEFT JOIN `' . _DB_PREFIX_ . 'product_guide_lang` l
               ON l.id_product = g.id_product AND l.id_lang = ' . (int) $id_lang . '
             WHERE g.id_product = ' . (int) $id_product
        );
    }

    private function saveProductGuide($id_product, $active, $content, $id_lang = null)
    {
        self::ensureLangTable();
        $id_product = (int) $id_product;
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        if ($id_lang === null) {
            $id_lang = (int) Context::getContext()->language->id;
        }
        $id_lang = (int) $id_lang;

        $existing = (int) Db::getInstance()->getValue(
            'SELECT id_product FROM `' . _DB_PREFIX_ . 'product_guide` WHERE id_product = ' . (int) $id_product
        );

        $main = [
            'active' => (int) $active,
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        if ($id_lang === $defaultLang) {
            $main['guide_content'] = pSQL($content, true);
        }

        if ($existing) {
            $ok = Db::getInstance()->update('product_guide', $main, 'id_product = ' . (int) $id_product);
        } else {
            $main['id_product'] = (int) $id_product;
            $main['date_add'] = date('Y-m-d H:i:s');
            if (!isset($main['guide_content'])) {
                $main['guide_content'] = pSQL($content, true);
            }
            $ok = Db::getInstance()->insert('product_guide', $main);
        }

        if (!$ok) {
            return false;
        }

        if ($id_lang !== $defaultLang) {
            return self::saveGuideContent($id_product, $id_lang, $content);
        }

        return true;
    }

    public static function getGuideContent($idProduct, $idLang)
    {
        self::ensureLangTable();
        $idProduct = (int) $idProduct;
        $idLang = (int) $idLang;
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        if ($idLang === $defaultLang) {
            return (string) Db::getInstance()->getValue(
                'SELECT guide_content FROM `' . _DB_PREFIX_ . 'product_guide` WHERE id_product = ' . (int) $idProduct
            );
        }

        $row = (string) Db::getInstance()->getValue(
            'SELECT guide_content FROM `' . _DB_PREFIX_ . 'product_guide_lang`
             WHERE id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang
        );
        if (trim($row) !== '') {
            return $row;
        }

        return (string) Db::getInstance()->getValue(
            'SELECT guide_content FROM `' . _DB_PREFIX_ . 'product_guide` WHERE id_product = ' . (int) $idProduct
        );
    }

    public static function saveGuideContent($idProduct, $idLang, $content)
    {
        self::ensureLangTable();
        $idProduct = (int) $idProduct;
        $idLang = (int) $idLang;
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $data = ['guide_content' => pSQL($content, true)];

        if ($idLang === $defaultLang) {
            return Db::getInstance()->update('product_guide', $data, 'id_product = ' . (int) $idProduct);
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_product FROM `' . _DB_PREFIX_ . 'product_guide_lang`
             WHERE id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang
        );
        if ($exists) {
            return Db::getInstance()->update(
                'product_guide_lang',
                $data,
                'id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang
            );
        }

        return Db::getInstance()->insert('product_guide_lang', array_merge($data, [
            'id_product' => (int) $idProduct,
            'id_lang' => (int) $idLang,
        ]));
    }
}
