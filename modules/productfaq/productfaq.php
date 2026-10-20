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
        $this->version = '1.1.0';
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
        return parent::install()
            && $this->registerHook('displayAdminProductsExtra')
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
        $ok = Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_faq` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `active` tinyint(1) NOT NULL DEFAULT 1,
            `question` text NOT NULL,
            `answer` text NOT NULL,
            PRIMARY KEY (`id`),
            KEY `id_product` (`id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
        );

        return $ok && self::ensureLangTable();
    }

    public static function ensureLangTable()
    {
        return (bool) Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_faq_lang` (
            `id_faq` INT(11) NOT NULL,
            `id_lang` INT UNSIGNED NOT NULL,
            `question` TEXT NOT NULL,
            `answer` TEXT NOT NULL,
            PRIMARY KEY (`id_faq`, `id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
    }

    private function dropTables()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_faq_lang`')
            && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_faq`');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int) $params['id_product'];
        if (!$id_product) {
            return '';
        }

        self::ensureLangTable();
        $faqs = $this->getFaqsByProduct($id_product);

        $this->context->smarty->assign([
            'faqs' => $faqs,
            'id_product' => $id_product,
            'module_dir' => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_faq.tpl');
    }

    public function hookDisplayProductExtraContent($params)
    {
        if ($this->context->controller) {
            $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        }

        return '';
    }

    public function getFaqsByProduct($id_product, $active_only = false, $id_lang = null)
    {
        self::ensureLangTable();
        if ($id_lang === null) {
            $id_lang = (int) Context::getContext()->language->id;
        }

        $sql = 'SELECT f.id, f.id_product, f.active,
                COALESCE(NULLIF(TRIM(l.question), \'\'), f.question) AS question,
                COALESCE(NULLIF(TRIM(l.answer), \'\'), f.answer) AS answer
            FROM `' . _DB_PREFIX_ . 'product_faq` f
            LEFT JOIN `' . _DB_PREFIX_ . 'product_faq_lang` l
              ON l.id_faq = f.id AND l.id_lang = ' . (int) $id_lang . '
            WHERE f.id_product = ' . (int) $id_product;

        if ($active_only) {
            $sql .= ' AND f.active = 1';
        }

        $results = Db::getInstance()->executeS($sql);

        return $results ? $results : [];
    }

    /**
     * @return array{question: string, answer: string}|false
     */
    public static function getFaqTexts($idFaq, $idLang)
    {
        self::ensureLangTable();
        $idFaq = (int) $idFaq;
        $idLang = (int) $idLang;
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        if ($idLang === $defaultLang) {
            return Db::getInstance()->getRow(
                'SELECT question, answer FROM `' . _DB_PREFIX_ . 'product_faq` WHERE id = ' . (int) $idFaq
            );
        }

        $row = Db::getInstance()->getRow(
            'SELECT question, answer FROM `' . _DB_PREFIX_ . 'product_faq_lang`
             WHERE id_faq = ' . (int) $idFaq . ' AND id_lang = ' . (int) $idLang
        );
        if ($row && trim((string) $row['question']) !== '') {
            return $row;
        }

        return Db::getInstance()->getRow(
            'SELECT question, answer FROM `' . _DB_PREFIX_ . 'product_faq` WHERE id = ' . (int) $idFaq
        );
    }

    public static function saveFaqTexts($idFaq, $idLang, $question, $answer)
    {
        self::ensureLangTable();
        $idFaq = (int) $idFaq;
        $idLang = (int) $idLang;
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $data = [
            'question' => pSQL($question, true),
            'answer' => pSQL($answer, true),
        ];

        if ($idLang === $defaultLang) {
            return Db::getInstance()->update('product_faq', $data, 'id = ' . (int) $idFaq);
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_faq FROM `' . _DB_PREFIX_ . 'product_faq_lang`
             WHERE id_faq = ' . (int) $idFaq . ' AND id_lang = ' . (int) $idLang
        );
        if ($exists) {
            return Db::getInstance()->update(
                'product_faq_lang',
                $data,
                'id_faq = ' . (int) $idFaq . ' AND id_lang = ' . (int) $idLang
            );
        }

        return Db::getInstance()->insert('product_faq_lang', array_merge($data, [
            'id_faq' => (int) $idFaq,
            'id_lang' => (int) $idLang,
        ]));
    }
}
