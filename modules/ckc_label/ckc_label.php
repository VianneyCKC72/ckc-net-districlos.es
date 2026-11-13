<?php


if (!defined('_PS_VERSION_')) {
    exit;
}

class ckc_label extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_label';
        $this->tab = 'administration';
        $this->version = '1.1.1';
        $this->author = 'CKC-Net';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Module de gestion d\'étiquettes');
        $this->description = $this->l('Module permettant de gérer des étiquettes pour les produits.');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayCustomProductLabel') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->createDatabaseTables();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->deleteDatabaseTables();
    }

    private function createDatabaseTables()
    {

        $sqls = [];
        $sqls[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "ckc_labels` (
            `id_label` INT(11) NOT NULL AUTO_INCREMENT,
            `color` VARCHAR(7) NOT NULL,
            `background_color` VARCHAR(7) NOT NULL,
            `text` VARCHAR(255) NOT NULL,
            `show_discount_percent` TINYINT(1) NOT NULL DEFAULT 1,
            `start_date` DATETIME DEFAULT NULL,
            `end_date` DATETIME DEFAULT NULL,
            `apply_to` VARCHAR(50) NOT NULL DEFAULT 'all',
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_label`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        $sqls[] ="CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "ckc_labels_product`
        (`id_label_product` INT(11) NOT NULL AUTO_INCREMENT,
        `id_label` INT(11) NOT NULL,
        `id_product` INT(11) NOT NULL,
         PRIMARY KEY (`id_label_product`))
        ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        $sqls[] ="CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "ckc_labels_category`
        (`id_label_category` INT(11) NOT NULL AUTO_INCREMENT,
        `id_label` INT(11) NOT NULL,
        `id_category` INT(11) NOT NULL,
        PRIMARY KEY (`id_label_category`))
        ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        $sqls[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckc_labels_lang` (
            `id_label` INT(11) NOT NULL,
            `id_lang` INT(11) NOT NULL,
            `text` TEXT NOT NULL,
            PRIMARY KEY (`id_label`, `id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        foreach ($sqls as $sql){
            $result = Db::getInstance()->execute($sql);
        }

        return $result;
    }

    private function deleteDatabaseTables()
    {
        $sqls = [];
        $sqls[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "ckc_labels`;";
        $sqls[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "ckc_labels_product`;";
        $sqls[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "ckc_labels_category`;";
        $sqls[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "ckc_labels_lang`;";

        foreach ($sqls as $sql){
            $result = Db::getInstance()->execute($sql);
        }

        return $result;
    }

    public function getContent()
    {
        $this->ensureLangTable();

        $output = '';

        if (Tools::getValue('ajaxCkcLabelTranslate')) {
            $this->ajaxTranslateLabelText();
        }

        if (Tools::getValue('ckcLabelTranslateMissing')) {
            if (Module::isInstalled('ckctranslate') && Module::isEnabled('ckctranslate')) {
                Tools::redirectAdmin(
                    $this->context->link->getAdminLink('AdminCkcTranslate')
                    . '&run_job=ckc_labels&fresh_queue=1'
                );
            }
            $output .= $this->displayError($this->l('Le module CKC Translate n’est pas actif.'));
        }

        if (Tools::isSubmit('submitCkcLabelConfig')) {
            $errors = $this->processFormSubmission();

            if (empty($errors)) {
                $this->clearCache();
                $output .= $this->displayConfirmation($this->l('Étiquette enregistrée.'));
            } else {
                foreach ($errors as $error) {
                    $output .= $this->displayError($error);
                }

                $this->context->smarty->assign('fields_value', $_POST);
                $this->context->controller->addJS($this->_path . 'views/js/ckc_label.js');
                $this->context->controller->addCSS($this->_path . 'views/css/ckc_label_bo.css');
                $output .= $this->renderForm();
                $this->clearCache();
                return $output;
            }
        } elseif (Tools::isSubmit('deleteckc_labels')) {
            $this->deleteLabel(Tools::getValue('id_label'));
            $this->clearCache();
            $output .= $this->displayConfirmation($this->l('Étiquette supprimée.'));
        } elseif (Tools::isSubmit('duplicateckc_labels') && !Tools::getValue('noimage')) {
            $this->copyLabel(Tools::getValue('id_label'));
            $this->clearCache();
            $output .= $this->displayConfirmation($this->l('Étiquette dupliquée.'));
        } elseif (Tools::isSubmit('statusckc_labels')) {
            $this->activeLabel(Tools::getValue('id_label'));
            $this->clearCache();
            $output .= $this->displayConfirmation($this->l('Étiquette mise à jour.'));
        }

        if (Tools::getValue('addNewLabel') || Tools::isSubmit('updateckc_labels')) {
            $this->context->controller->addJS($this->_path . 'views/js/ckc_label.js');
            $this->context->controller->addCSS($this->_path . 'views/css/ckc_label_bo.css');
            $output .= $this->renderForm();
        } else {
            $output .= $this->renderLabelList();
        }

        return $output;
    }

    public function clearCache()
    {
        $this->_clearCache('module:ckc_label/views/templates/hook/product_labels.tpl');
        // Invalide le cache SQL front (version bump)
        Configuration::updateValue(
            'CKC_LABEL_CACHE_VER',
            (int) Configuration::get('CKC_LABEL_CACHE_VER') + 1
        );
    }

    private function renderLabelList()
    {
        $labels = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckc_labels');

        $fieldsList = [
            'id_label' => ['title' => $this->l('ID'), 'align' => 'center'],
            'color' => ['title' => $this->l('Couleur')],
            'text' => ['title' => $this->l('Texte')],
            'apply_to' => ['title' => $this->l('Application')],
            'start_date' => ['title' => $this->l('Date de début')],
            'end_date' => ['title' => $this->l('Date de fin')],
            'active' => ['title' => $this->l('Active'), 'active' => 'status'],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->actions = ['edit', 'delete', 'duplicate'];
        $helper->identifier = 'id_label';
        $helper->show_toolbar = true;
        $helper->toolbar_btn = [
            'new' => [
                'href' => $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&addNewLabel=1',
                'desc' => $this->l('Add New Label'),
            ],
        ];
        if (Module::isInstalled('ckctranslate') && Module::isEnabled('ckctranslate')) {
            $helper->toolbar_btn['cogs'] = [
                'href' => $this->context->link->getAdminLink('AdminModules', true)
                    . '&configure=' . $this->name . '&ckcLabelTranslateMissing=1',
                'desc' => $this->l('Traduire les textes manquants (EN)'),
            ];
        }
        $helper->title = $this->l('Étiquettes');
        $helper->no_link = true;
        $helper->module = $this;
        $helper->listTotal = count($labels);
        $helper->table = 'ckc_labels';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper->generateList($labels, $fieldsList);
    }

    private function renderForm()
    {
        $id_label = Tools::getValue('id_label');
        $selected_categories = [];
        if ($id_label) {
            $selected_categories = $this->getConfigFormValues()['category_ids[]'];
        }

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $id_label ? $this->l('Modifier une étiquette') : $this->l('Créer une étiquette'),
                ],
                'input' => [
                    [
                        'type' => 'color',
                        'label' => $this->l('Couleur du texte'),
                        'name' => 'color',
                        'required' => true,
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Couleur de fond'),
                        'name' => 'background_color',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Texte'),
                        'name' => 'text',
                        'lang' => true,
                        'required' => true,
                        'rows' => 3,
                        'cols' => 40,
                        'desc' => $this->l('Autorisé : <strong>, <em>, <br>, <span style="color: #FF0C4D;">, ↓. Un onglet par langue. Ex. : ↓ <strong><em>PRIX<br>DÉGRESSIF</em></strong>'),
                    ],
                    [
                        'type' => 'html',
                        'name' => 'ckc_label_translate_btn',
                        'html_content' => (Module::isInstalled('ckctranslate') && Module::isEnabled('ckctranslate'))
                            ? '<button type="button" class="btn btn-default" id="ckc-label-translate-en">'
                                . $this->l('Traduire le texte FR vers l’anglais (IA)')
                                . '</button>'
                                . '<p class="help-block">' . $this->l('Remplit l’onglet anglais à partir du texte français, sans écraser si vous avez déjà saisi une traduction.') . '</p>'
                            : '',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Afficher le pourcentage de réduction (ex. -10%)'),
                        'name' => 'show_discount_percent',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'show_discount_percent_on',
                                'value' => 1,
                                'label' => $this->l('Activé'),
                            ],
                            [
                                'id' => 'show_discount_percent_off',
                                'value' => 0,
                                'label' => $this->l('Désactivé'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'datetime',
                        'label' => $this->l('Date et heure de début'),
                        'name' => 'start_date',
                    ],
                    [
                        'type' => 'datetime',
                        'label' => $this->l('Date et heure de fin'),
                        'name' => 'end_date',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'active',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Activée'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Désactivée'),
                            ],
                        ],
                    ],
                    'apply_to' => [
                        'type' => 'select',
                        'label' => $this->l('Appliquer à'),
                        'name' => 'apply_to',
                        'options' => [
                            'query' => [
                                ['id' => 'all', 'name' => $this->l('Tous les produits')],
                                ['id' => 'products', 'name' => $this->l('Produits spécifiques')],
                                ['id' => 'categories', 'name' => $this->l('Catégories spécifiques')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                        'onchange' => 'toggleSelectionFields()',
                    ],
                    'product_ids' => [
                        'type' => 'select',
                        'class' => 'custom-select-full-width ckc-label-products',
                        'multiple' => true,
                        'label' => $this->l('Produits sélectionnés'),
                        'name' => 'product_ids[]',
                        'options' => array(
                            'query' => Product::getProducts($this->context->language->id, 0, 0, 'id_product', 'ASC'),
                            'id' => 'id_product',
                            'name' => 'name'
                        ),
                    ],
                    'category_ids' => [
                        'type' => 'categories',
                        'label' => $this->l('Catégorie'),
                        'name' => 'category_ids[]',
                        'class' => 'ckc-label-categories',
                        'tree' => array(
                            'id' => 'category_tree',
                            'selected_categories' => $selected_categories,
                            'use_checkbox' => true,
                            'use_search' => true,
                        ),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $this->fields_form = array();

        $languages = [];
        foreach (Language::getLanguages(false) as $lang) {
            $languages[] = [
                'id_lang' => (int) $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ((int) $lang['id_lang'] === (int) Configuration::get('PS_LANG_DEFAULT')),
            ];
        }

        $helper->languages = $languages;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->submit_action = 'submitCkcLabelConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name .
            ($id_label ? '&id_label=' . $id_label : '');
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    private function getConfigFormValues()
    {
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        $textByLang = [];
        foreach (Language::getLanguages(false) as $lang) {
            $textByLang[(int) $lang['id_lang']] = '';
        }

        if (!Tools::isSubmit('id_label') && !Tools::getValue('id_label')) {
            return [
                'color' => '#FFFFFF',
                'background_color' => '#FF144E',
                'text' => $textByLang,
                'show_discount_percent' => 1,
                'start_date' => '',
                'end_date' => '',
                'active' => 1,
                'apply_to' => 'all',
                'product_ids[]' => [],
                'category_ids[]' => [],
            ];
        }

        $id_label = (int) Tools::getValue('id_label');
        $label = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ckc_labels WHERE id_label = ' . (int) $id_label);
        if (!$label) {
            return [
                'color' => '#FFFFFF',
                'background_color' => '#FF144E',
                'text' => $textByLang,
                'show_discount_percent' => 1,
                'start_date' => '',
                'end_date' => '',
                'active' => 1,
                'apply_to' => 'all',
                'product_ids[]' => [],
                'category_ids[]' => [],
            ];
        }

        $langRows = Db::getInstance()->executeS(
            'SELECT id_lang, text FROM `' . _DB_PREFIX_ . 'ckc_labels_lang` WHERE id_label = ' . (int) $id_label
        );
        if (is_array($langRows)) {
            foreach ($langRows as $row) {
                $textByLang[(int) $row['id_lang']] = $row['text'];
            }
        }
        if (empty($textByLang[$idLangDefault]) && !empty($label['text'])) {
            $textByLang[$idLangDefault] = $label['text'];
        }

        if ($label['apply_to'] == 'products') {
            $product_ids_sql = Db::getInstance()->executeS('SELECT GROUP_CONCAT(DISTINCT `id_product` SEPARATOR ",") as id_product FROM ' . _DB_PREFIX_ . 'ckc_labels_product WHERE id_label = ' . (int) $id_label);
            $product_ids = $product_ids_sql[0]['id_product'];
            $category_ids = '';
        } elseif ($label['apply_to'] == 'categories') {
            $product_ids = '';
            $category_ids_sql = Db::getInstance()->executeS('SELECT GROUP_CONCAT(DISTINCT `id_category` SEPARATOR ",") as id_category FROM ' . _DB_PREFIX_ . 'ckc_labels_category WHERE id_label = ' . (int) $id_label);
            $category_ids = $category_ids_sql[0]['id_category'];
        } else {
            $product_ids = '';
            $category_ids = '';
        }

        return [
            'color' => $label['color'],
            'background_color' => $label['background_color'],
            'text' => $textByLang,
            'show_discount_percent' => (int) ($label['show_discount_percent'] ?? 1),
            'start_date' => $label['start_date'],
            'end_date' => $label['end_date'],
            'active' => $label['active'],
            'apply_to' => $label['apply_to'],
            'product_ids[]' => explode(',', $product_ids),
            'category_ids[]' => explode(',', $category_ids),
        ];
    }

    private function validateFormData($data)
    {
        $errors = [];

        if (empty($data['color'])) {
            $errors[] = $this->l('La couleur du texte est obligatoire.');
        }

        if (empty($data['background_color'])) {
            $errors[] = $this->l('La couleur de fond est obligatoire.');
        }

        if (empty($data['text'])) {
            $errors[] = $this->l('Le texte est obligatoire.');
        }

        if (!empty($data['start_date']) && !strtotime($data['start_date'])) {
            $errors[] = $this->l('La date de début est invalide.');
        }

        if (!empty($data['end_date']) && !strtotime($data['end_date'])) {
            $errors[] = $this->l('La date de fin est invalide.');
        }

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
                $errors[] = $this->l('La date de début doit être antérieure à la date de fin.');
            }
        }

        if ($data['apply_to'] == 'products' && empty($data['product_ids'])) {
            $errors[] = $this->l('Vous devez sélectionner au moins un produit.');
        }

        if ($data['apply_to'] == 'categories' && empty($data['category_ids'])) {
            $errors[] = $this->l('Vous devez sélectionner au moins une catégorie.');
        }

        return $errors;
    }

    /** HTML limité : strong/b/em/i/br/span(color) + flèche ↓ en tête agrandie. */
    private function sanitizeLabelText($text)
    {
        $text = strip_tags(preg_replace('/\r\n|\r|\n/', '<br>', (string) $text), '<strong><b><em><i><br><span>');
        $text = (string) preg_replace_callback(
            '#<\s*span\b([^>]*)>#i',
            static function ($matches) {
                if (!preg_match('/style\s*=\s*(["\'])(.*?)\1/i', $matches[1], $styleMatch)) {
                    return '<span>';
                }
                if (!preg_match('/^\s*color\s*:\s*(#[0-9a-fA-F]{3}|#[0-9a-fA-F]{6})\s*;?\s*$/i', $styleMatch[2], $colorMatch)) {
                    return '<span>';
                }

                return '<span style="color: ' . strtoupper($colorMatch[1]) . ';">';
            },
            $text
        );
        $text = trim($text);

        return preg_replace('/^↓\s*/u', '<b class="ckc-label-arrow">↓</b>', $text);
    }

    private function processFormSubmission()
    {
        $id_label = Tools::getValue('id_label');
        $categories = Tools::getValue('category_ids', []);
        $categoriesArray = [];
        foreach ($categories as $category) {
            $categoriesArray[] = $category[0];
        }


        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        $textByLang = [];
        foreach (Language::getLanguages(false) as $lang) {
            $idLang = (int) $lang['id_lang'];
            $textByLang[$idLang] = $this->sanitizeLabelText(Tools::getValue('text_' . $idLang, ''));
        }
        $defaultText = $textByLang[$idLangDefault] ?? reset($textByLang);

        $data = [
            'color' => Tools::getValue('color'),
            'background_color' => Tools::getValue('background_color'),
            'text' => $defaultText,
            'show_discount_percent' => (int) Tools::getValue('show_discount_percent', 1),
            'start_date' => Tools::getValue('start_date'),
            'end_date' => Tools::getValue('end_date'),
            'active' => (int) Tools::getValue('active'),
            'apply_to' => Tools::getValue('apply_to'),
            'product_ids' => implode(',', Tools::getValue('product_ids', [])),
            'category_ids' => implode(',', $categoriesArray),
        ];

        $errors = $this->validateFormData($data);

        if (!empty($errors)) {
            return $errors;
        }

        unset($data['product_ids'], $data['category_ids']);

        if ($id_label) {
            Db::getInstance()->update('ckc_labels', $data, 'id_label = ' . (int) $id_label);
        } else {
            Db::getInstance()->insert('ckc_labels', $data);
            $id_label = (int) Db::getInstance()->Insert_ID();
        }

        $this->saveLabelLangs((int) $id_label, $textByLang);

        $new_label = !Tools::getValue('id_label');

        if(count($categoriesArray)){

            if(!$new_label) {
                Db::getInstance()->delete('ckc_labels_category', 'id_label = '.$id_label);
            }
            foreach(array_unique($categoriesArray) as $id_category){
                $dataCategory = [
                    'id_label' => $id_label,
                    'id_category' => $id_category
                ];
                Db::getInstance()->insert('ckc_labels_category', $dataCategory);
            }
        }

        $productsArray = array_unique(Tools::getValue('product_ids', []));
        if(count($productsArray)){
            if(!$new_label) {
                Db::getInstance()->delete('ckc_labels_product', 'id_label = '.$id_label);
            }

            foreach($productsArray as $id_product){
                $dataProduct = [
                    'id_label' => $id_label,
                    'id_product' => $id_product
                ];
                Db::getInstance()->insert('ckc_labels_product', $dataProduct);
            }
        }

        return [];
    }

    private function deleteLabel($id_label)
    {
        Db::getInstance()->delete('ckc_labels', 'id_label = ' . (int) $id_label);
        Db::getInstance()->delete('ckc_labels_product', 'id_label = ' . (int) $id_label);
        Db::getInstance()->delete('ckc_labels_category', 'id_label = ' . (int) $id_label);
        Db::getInstance()->delete('ckc_labels_lang', 'id_label = ' . (int) $id_label);
    }

    private function copyLabel($id_label){

        $labelInfo = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckc_labels WHERE id_label = ' . (int) $id_label);
        $labelInfoProducts = Db::getInstance()->executeS('SELECT GROUP_CONCAT(DISTINCT `id_product` SEPARATOR ",") as id_product FROM ' . _DB_PREFIX_ . 'ckc_labels_product WHERE id_label = ' . (int) $id_label);
        $labelInfoCategories = Db::getInstance()->executeS('SELECT GROUP_CONCAT(DISTINCT `id_category` SEPARATOR ",") as id_category FROM ' . _DB_PREFIX_ . 'ckc_labels_category WHERE id_label = ' . (int) $id_label);

        $date = date('Y-m-d H:i:s');

        $update = Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ckc_labels (`id_label`, `color`, `background_color`, `text`, `show_discount_percent`,
         `start_date`, `end_date`, `apply_to`, `active`)
        VALUES (NULL, \'' . $labelInfo[0]['color'] . '\', \'' . $labelInfo[0]['background_color'] . '\', \'' . $labelInfo[0]['text'] . ' copie '.$date.'\',
        ' . (int) ($labelInfo[0]['show_discount_percent'] ?? 1) . ',
        \'' . $labelInfo[0]['start_date'] . '\', \'' . $labelInfo[0]['end_date'] . '\', \'' . $labelInfo[0]['apply_to'] . '\', 0)');

        $id_label_new_sql = Db::getInstance()->executeS('SELECT `id_label` FROM  `' . _DB_PREFIX_ . 'ckc_labels` 
            WHERE `color` LIKE "'.$labelInfo[0]["color"].'" AND 
            `background_color` LIKE "'.$labelInfo[0]["background_color"].'" AND
            `text` LIKE "'.$labelInfo[0]['text'] . ' copie '.$date.'" AND 
            `show_discount_percent` = ' . (int) ($labelInfo[0]['show_discount_percent'] ?? 1) . ' AND 
            `start_date` LIKE "'.$labelInfo[0]["start_date"].'" AND 
            `end_date` LIKE "'.$labelInfo[0]["end_date"].'" AND 
            `apply_to` LIKE "'.$labelInfo[0]["apply_to"].'"
            ');

        $id_label_new = $id_label_new_sql[0]['id_label'];

        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        $langRows = Db::getInstance()->executeS(
            'SELECT id_lang, text FROM `' . _DB_PREFIX_ . 'ckc_labels_lang` WHERE id_label = ' . (int) $id_label
        );
        if (is_array($langRows)) {
            foreach ($langRows as $langRow) {
                $copiedText = $langRow['text'];
                if ((int) $langRow['id_lang'] === $idLangDefault) {
                    $copiedText .= ' copie ' . $date;
                }
                Db::getInstance()->insert('ckc_labels_lang', [
                    'id_label' => (int) $id_label_new,
                    'id_lang' => (int) $langRow['id_lang'],
                    'text' => pSQL($copiedText, true),
                ]);
            }
        }

        if(isset($labelInfoCategories[0]['id_category'])){
            $categoriesArray = explode(',', $labelInfoCategories[0]['id_category']);

            if(count(array_unique($categoriesArray))){
                foreach(array_unique($categoriesArray) as $id_category){
                    $dataCategory = [
                        'id_label' => $id_label_new,
                        'id_category' => $id_category
                    ];
                    Db::getInstance()->insert('ckc_labels_category', $dataCategory);
                }
            }
        }

        if(isset($labelInfoProducts[0]['id_product'])){
            $productsArray = explode(',', $labelInfoProducts[0]['id_product']);

            if(count(array_unique($productsArray))){
                foreach(array_unique($productsArray) as $id_product){
                    $dataProduct = [
                        'id_label' => $id_label_new,
                        'id_product' => $id_product
                    ];
                    Db::getInstance()->insert('ckc_labels_product', $dataProduct);
                }
            }
        }

    }

    public function activeLabel($id_label)
    {
        $label = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ckc_labels WHERE id_label = ' . (int) $id_label);
        $active = $label['active'] == 1 ? 0 : 1;
        Db::getInstance()->update('ckc_labels', ['active' => $active], 'id_label = ' . (int) $id_label);
    }

    public function hookDisplayHeader()
    {
        $this->ensureLangTable();
        $this->context->controller->registerStylesheet(
            'ckc-label-front',
            'modules/' . $this->name . '/views/css/ckc_label.css',
            ['media' => 'all', 'priority' => 200]
        );
    }

    /**
     * Crée ps_ckc_labels_lang si l’upgrade 1.1.0 n’a pas encore été lancé.
     */
    public function ensureLangTable()
    {
        static $done = false;
        if ($done) {
            return true;
        }

        try {
            $table = _DB_PREFIX_ . 'ckc_labels_lang';
            $exists = (int) Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = \'' . pSQL($table) . '\''
            );
            if ($exists) {
                $done = true;
                return true;
            }

            $ok = Db::getInstance()->execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckc_labels_lang` (
                    `id_label` INT(11) NOT NULL,
                    `id_lang` INT(11) NOT NULL,
                    `text` TEXT NOT NULL,
                    PRIMARY KEY (`id_label`, `id_lang`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
            );
            if (!$ok) {
                return false;
            }

            $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
            if ($idLangDefault <= 0) {
                $idLangDefault = 1;
            }

            Db::getInstance()->execute(
                'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'ckc_labels_lang` (`id_label`, `id_lang`, `text`)
                 SELECT `id_label`, ' . $idLangDefault . ', `text`
                 FROM `' . _DB_PREFIX_ . 'ckc_labels`
                 WHERE `text` IS NOT NULL AND TRIM(`text`) != \'\''
            );
        } catch (Exception $e) {
            return false;
        }

        $done = true;

        return true;
    }

    /**
     * Catalogue labels actifs (1 SQL / requête HTTP), puis filtre produit/catégorie.
     */
    private function getActiveLabelsCatalog()
    {
        $this->ensureLangTable();

        static $catalogByLang = [];
        $idLang = (int) $this->context->language->id;
        if (isset($catalogByLang[$idLang])) {
            return $catalogByLang[$idLang];
        }

        $ver = (int) Configuration::get('CKC_LABEL_CACHE_VER');
        $idLang = (int) $this->context->language->id;
        $cacheId = 'ckc_label_catalog_' . $ver . '_' . $idLang . '_' . date('YmdH');

        if (Cache::isStored($cacheId)) {
            return $catalogByLang[$idLang] = Cache::retrieve($cacheId);
        }

        $currentDate = date('Y-m-d H:i:s');
        $idLang = (int) $this->context->language->id;
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        $whereActive = ' WHERE l.`active` = 1
            AND (l.`start_date` IS NULL OR l.`start_date` = "0000-00-00 00:00:00" OR l.`start_date` <= "' . pSQL($currentDate) . '")
            AND (l.`end_date` IS NULL OR l.`end_date` = "0000-00-00 00:00:00" OR l.`end_date` >= "' . pSQL($currentDate) . '")';
        $sqlWithLang = 'SELECT l.*,
                COALESCE(NULLIF(TRIM(ll.`text`), ""), llfb.`text`, l.`text`) AS `text`,
                p.`id_product` AS `map_id_product`, c.`id_category` AS `map_id_category`
            FROM `' . _DB_PREFIX_ . 'ckc_labels` l
            LEFT JOIN `' . _DB_PREFIX_ . 'ckc_labels_lang` ll
                ON ll.`id_label` = l.`id_label` AND ll.`id_lang` = ' . $idLang . '
            LEFT JOIN `' . _DB_PREFIX_ . 'ckc_labels_lang` llfb
                ON llfb.`id_label` = l.`id_label` AND llfb.`id_lang` = ' . $idLangDefault . '
            LEFT JOIN `' . _DB_PREFIX_ . 'ckc_labels_product` p ON p.`id_label` = l.`id_label`
            LEFT JOIN `' . _DB_PREFIX_ . 'ckc_labels_category` c ON c.`id_label` = l.`id_label`'
            . $whereActive;
        $sqlLegacy = 'SELECT l.*,
                l.`text`,
                p.`id_product` AS `map_id_product`, c.`id_category` AS `map_id_category`
            FROM `' . _DB_PREFIX_ . 'ckc_labels` l
            LEFT JOIN `' . _DB_PREFIX_ . 'ckc_labels_product` p ON p.`id_label` = l.`id_label`
            LEFT JOIN `' . _DB_PREFIX_ . 'ckc_labels_category` c ON c.`id_label` = l.`id_label`'
            . $whereActive;

        try {
            $rows = Db::getInstance()->executeS($sqlWithLang);
        } catch (Exception $e) {
            $rows = Db::getInstance()->executeS($sqlLegacy);
        }

        $catalog = [
            'all' => [],
            'by_product' => [],
            'by_category' => [],
        ];

        if (!is_array($rows)) {
            Cache::store($cacheId, $catalog);
            return $catalogByLang[$idLang] = $catalog;
        }

        $seenAll = [];
        foreach ($rows as $row) {
            $label = $row;
            unset($label['map_id_product'], $label['map_id_category']);
            $idLabel = (int) $label['id_label'];

            if ($label['apply_to'] === 'all') {
                if (!isset($seenAll[$idLabel])) {
                    $seenAll[$idLabel] = true;
                    $catalog['all'][] = $label;
                }
                continue;
            }

            if ($label['apply_to'] === 'products' && !empty($row['map_id_product'])) {
                $idProduct = (int) $row['map_id_product'];
                $catalog['by_product'][$idProduct][$idLabel] = $label;
            } elseif ($label['apply_to'] === 'categories' && !empty($row['map_id_category'])) {
                $idCategory = (int) $row['map_id_category'];
                $catalog['by_category'][$idCategory][$idLabel] = $label;
            }
        }

        Cache::store($cacheId, $catalog);
        return $catalogByLang[$idLang] = $catalog;
    }

    private function getActiveLabelsForProduct($idProduct, $idCategory)
    {
        static $memo = [];
        $idProduct = (int) $idProduct;
        $idCategory = (int) $idCategory;
        $key = $idProduct . '_' . $idCategory;

        if (isset($memo[$key])) {
            return $memo[$key];
        }

        $catalog = $this->getActiveLabelsCatalog();
        $byId = [];

        foreach ($catalog['all'] as $label) {
            $byId[(int) $label['id_label']] = $label;
        }
        if (!empty($catalog['by_product'][$idProduct])) {
            foreach ($catalog['by_product'][$idProduct] as $idLabel => $label) {
                $byId[(int) $idLabel] = $label;
            }
        }
        if (!empty($catalog['by_category'][$idCategory])) {
            foreach ($catalog['by_category'][$idCategory] as $idLabel => $label) {
                $byId[(int) $idLabel] = $label;
            }
        }

        return $memo[$key] = array_values($byId);
    }

    public function hookDisplayCustomProductLabel($params)
    {
        $product = $params['product'];
        $idProduct = (int) $product['id_product'];
        $idCategory = (int) $product['id_category_default'];
        $cacheId = 'ckc_label_'
            . (int) Configuration::get('CKC_LABEL_CACHE_VER')
            . '_' . (int) $this->context->language->id
            . '_' . $idProduct . '_' . $idCategory;

        if (!$this->isCached('views/templates/hook/product_labels.tpl', $cacheId)) {
            $labels = $this->getActiveLabelsForProduct($idProduct, $idCategory);
            if (is_array($labels)) {
                foreach ($labels as &$label) {
                    $label['show_discount_percent'] = (int) ($label['show_discount_percent'] ?? 1);
                }
                unset($label);
            }
            $this->context->smarty->assign([
                'ckc_labels' => $labels,
                'ckc_labels_product' => $product,
            ]);
        }

        return $this->display(__FILE__, 'views/templates/hook/product_labels.tpl', $cacheId);
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addJS($this->_path . 'views/js/ckc_label.js');
            $idLangEn = (int) Language::getIdByIso('en');
            Media::addJsDef([
                'ckcLabelBo' => [
                    'ajaxUrl' => $this->context->link->getAdminLink('AdminModules', true)
                        . '&configure=' . $this->name . '&ajaxCkcLabelTranslate=1',
                    'idLangDefault' => (int) Configuration::get('PS_LANG_DEFAULT'),
                    'idLangEn' => $idLangEn > 0 ? $idLangEn : 2,
                    'confirmOverwrite' => $this->l('L’onglet anglais n’est pas vide. Remplacer par la traduction IA ?'),
                    'errorEmpty' => $this->l('Saisissez d’abord le texte français.'),
                    'errorGeneric' => $this->l('Traduction impossible. Vérifiez CKC Translate (clé API).'),
                ],
            ]);
        }
    }

    private function saveLabelLangs($idLabel, array $textByLang)
    {
        $idLabel = (int) $idLabel;
        if ($idLabel <= 0) {
            return;
        }
        foreach ($textByLang as $idLang => $text) {
            $idLang = (int) $idLang;
            if ($idLang <= 0) {
                continue;
            }
            $exists = (int) Db::getInstance()->getValue(
                'SELECT id_label FROM `' . _DB_PREFIX_ . 'ckc_labels_lang`
                 WHERE id_label = ' . $idLabel . ' AND id_lang = ' . $idLang
            );
            $payload = [
                'text' => pSQL($text, true),
            ];
            if ($exists) {
                if ($text === '') {
                    Db::getInstance()->delete(
                        'ckc_labels_lang',
                        'id_label = ' . $idLabel . ' AND id_lang = ' . $idLang
                    );
                    continue;
                }
                Db::getInstance()->update(
                    'ckc_labels_lang',
                    $payload,
                    'id_label = ' . $idLabel . ' AND id_lang = ' . $idLang
                );
            } elseif ($text !== '') {
                $payload['id_label'] = $idLabel;
                $payload['id_lang'] = $idLang;
                Db::getInstance()->insert('ckc_labels_lang', $payload);
            }
        }
    }

    private function ajaxTranslateLabelText()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->context->employee || !$this->context->employee->isLoggedBack()) {
            die(json_encode(['success' => false, 'message' => 'Forbidden']));
        }
        $source = $this->sanitizeLabelText(Tools::getValue('text'));
        if ($source === '') {
            die(json_encode(['success' => false, 'message' => $this->l('Texte source vide.')]));
        }
        if (!Module::isInstalled('ckctranslate') || !Module::isEnabled('ckctranslate')) {
            die(json_encode(['success' => false, 'message' => $this->l('CKC Translate inactif.')]));
        }
        if (!class_exists('CkcTranslate', false)) {
            require_once _PS_MODULE_DIR_ . 'ckctranslate/ckctranslate.php';
        }
        $ai = new CkcTranslateAi(
            (string) Configuration::get(CkcTranslate::CONF_API_KEY),
            (string) Configuration::get(CkcTranslate::CONF_MODEL),
            (float) Configuration::get(CkcTranslate::CONF_TEMPERATURE),
            (int) Configuration::get(CkcTranslate::CONF_MAX_TOKENS),
            (string) Configuration::get(CkcTranslate::CONF_PROMPT)
        );
        $jobPrompt = (string) Configuration::get(CkcTranslate::CONF_PROMPT_CKC_LABELS);
        $system = 'You translate short PrestaShop product badge labels from French to English. '
            . 'Preserve HTML tags (<strong>, <b>, <em>, <i>, <br>), CSS classes and the ↓ character. '
            . 'Keep similar length. Do not invent promotions.'
            . ($jobPrompt !== '' ? "\n" . $jobPrompt : '');
        $prompt = "Return ONLY valid JSON:\n{\n  \"text\": \"\"\n}\n\nSource label HTML:\n" . $source;
        $data = $ai->requestJson($system, $prompt);
        if (!is_array($data) || empty($data['text'])) {
            die(json_encode(['success' => false, 'message' => $this->l('Réponse IA invalide.')]));
        }
        die(json_encode([
            'success' => true,
            'text' => $this->sanitizeLabelText($data['text']),
        ]));
    }
}
