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
        $this->version = '1.0.0';
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
            $this->registerHook('displayBackOfficeHeader') &&
            $this->createDatabaseTables();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->deleteDatabaseTables();
    }

    private function createDatabaseTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "ckc_labels` (
            `id_label` INT(11) NOT NULL AUTO_INCREMENT,
            `color` VARCHAR(7) NOT NULL,
            `background_color` VARCHAR(7) NOT NULL,
            `text` VARCHAR(255) NOT NULL,
            `start_date` DATETIME DEFAULT NULL,
            `end_date` DATETIME DEFAULT NULL,
            `apply_to` VARCHAR(50) NOT NULL DEFAULT 'all',
            `product_ids` TEXT DEFAULT NULL,
            `category_ids` TEXT DEFAULT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_label`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        return Db::getInstance()->execute($sql);
    }

    private function deleteDatabaseTables()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "ckc_labels`;";

        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {
        $output = '';

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
                $this->context->controller->addCSS($this->_path . 'views/css/ckc_label.css');
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
            $this->context->controller->addCSS($this->_path . 'views/css/ckc_label.css');
            $output .= $this->renderForm();
        } else {
            $output .= $this->renderLabelList();
        }

        return $output;
    }

    public function clearCache()
    {
        $this->_clearCache('module:ckc_label/views/templates/hook/product_labels.tpl');
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
                        'type' => 'text',
                        'label' => $this->l('Texte'),
                        'name' => 'text',
                        'required' => true,
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

        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
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
        if (Tools::isSubmit('id_label')) {
            $id_label = Tools::getValue('id_label');
            $label = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ckc_labels WHERE id_label = ' . (int) $id_label);

            return [
                'color' => $label['color'],
                'background_color' => $label['background_color'],
                'text' => $label['text'],
                'start_date' => $label['start_date'],
                'end_date' => $label['end_date'],
                'active' => $label['active'],
                'apply_to' => $label['apply_to'],
                'product_ids[]' => explode(',', $label['product_ids']),
                'category_ids[]' => explode(',', $label['category_ids']),
            ];
        }
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

    private function processFormSubmission()
    {
        $id_label = Tools::getValue('id_label');
        $categories = Tools::getValue('category_ids', []);
        $categoriesArray = [];
        foreach ($categories as $category) {
            $categoriesArray[] = $category[0];
        }
        $data = [
            'color' => Tools::getValue('color'),
            'background_color' => Tools::getValue('background_color'),
            'text' => Tools::getValue('text'),
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

        if ($id_label) {
            Db::getInstance()->update('ckc_labels', $data, 'id_label = ' . (int) $id_label);
        } else {
            Db::getInstance()->insert('ckc_labels', $data);
        }

        return [];
    }

    private function deleteLabel($id_label)
    {
        Db::getInstance()->delete('ckc_labels', 'id_label = ' . (int) $id_label);
    }

    private function copyLabel($id_label){

        $labelInfo = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckc_labels WHERE id_label = ' . (int) $id_label);

        $date = date('Y-m-d H:i:s');

        $update = Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'ckc_labels (`id_label`, `color`, `background_color`, `text`,
         `start_date`, `end_date`, `apply_to`, `product_ids`, `category_ids`, `active`)
        VALUES (NULL, \'' . $labelInfo[0]['color'] . '\', \'' . $labelInfo[0]['background_color'] . '\', \'' . $labelInfo[0]['text'] . ' copie '.$date.'\',
        \'' . $labelInfo[0]['start_date'] . '\', \'' . $labelInfo[0]['end_date'] . '\', \'' . $labelInfo[0]['apply_to'] . '\', \'' . $labelInfo[0]['product_ids'] . '\', \'' . $labelInfo[0]['category_ids'] . '\', 0)');

    }

    public function activeLabel($id_label)
    {
        $label = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ckc_labels WHERE id_label = ' . (int) $id_label);
        $active = $label['active'] == 1 ? 0 : 1;
        Db::getInstance()->update('ckc_labels', ['active' => $active], 'id_label = ' . (int) $id_label);
    }

    public function hookActionAdminControllerSetMedia()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/ckc_label.css');
        $this->context->controller->addJS($this->_path . 'views/js/ckc_label.js');
    }

    public function hookDisplayCustomProductLabel($params)
    {
        $this->clearCache();
        $isLogged = $this->context->customer->logged;
        $env_pro = $isLogged && in_array(10, $this->context->customer->getGroups());

        $product = $params['product'];
        $currentDate = date('Y-m-d H:i:s');
        $idProduct = (int) $product['id_product'];
        $idCategory = (int) $product['id_category_default'];
        $sql = 'SELECT * 
        FROM ' . _DB_PREFIX_ . 'ckc_labels 
        WHERE active = 1
          AND (
              (apply_to = "products" AND FIND_IN_SET(' . $idProduct . ', product_ids)) 
              OR 
              (apply_to = "categories" AND FIND_IN_SET(' . $idCategory . ', category_ids))
              OR
              (apply_to = "all")
          )
          AND (start_date IS NULL OR start_date <= "' . pSQL($currentDate) . '")
          AND (end_date IS NULL OR end_date >= "' . pSQL($currentDate) . ' 
          LIMIT 1")';
        $labels = Db::getInstance()->executeS($sql);

        if ($env_pro) {
            $labels = [];
        }

        $this->context->smarty->assign('ckc_labels_sql', $sql);

        $this->context->smarty->assign('ckc_labels', $labels);
        $this->context->smarty->assign('ckc_labels_product', $product);

        return $this->display(__FILE__, 'views/templates/hook/product_labels.tpl');
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
        }
    }

}
