<?php
/**
 * Module : cartcombinationtemplate
 * Description : Gère les templates de combinaison des règles panier
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcCartCombinationTemplate extends Module
{
    public function __construct()
    {
        $this->name = 'ckccartcombinationtemplate';
        $this->version = '1.0.0';
        $this->author = 'ckc-net';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Template de combinaison de règles panier');
        $this->description = $this->l('Templates de cumul pour les règles panier.');
    }

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->initializeTemplatesFromExistingRules()
            && $this->registerHook('actionObjectCartRuleDeleteAfter');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDb();
    }

    private function installDb()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'cart_rule_combination_template_rule`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'cart_rule_combination_template`');

        return Db::getInstance()->execute('CREATE TABLE `'._DB_PREFIX_.'cart_rule_combination_template` (
            `id_cart_rule_template` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_cart_rule` INT UNSIGNED NOT NULL UNIQUE,
            `template_name` VARCHAR(255),
            `date_add` DATETIME,
            `date_upd` DATETIME,
            FOREIGN KEY (`id_cart_rule`) REFERENCES `'._DB_PREFIX_.'cart_rule`(`id_cart_rule`) ON DELETE CASCADE
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')
        && Db::getInstance()->execute('CREATE TABLE `'._DB_PREFIX_.'cart_rule_combination_template_rule` (
            `id_cart_rule` INT UNSIGNED NOT NULL,
            `id_combination_rule` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_cart_rule`, `id_combination_rule`),
            FOREIGN KEY (`id_cart_rule`) REFERENCES `'._DB_PREFIX_.'cart_rule`(`id_cart_rule`) ON DELETE CASCADE,
            FOREIGN KEY (`id_combination_rule`) REFERENCES `'._DB_PREFIX_.'cart_rule`(`id_cart_rule`) ON DELETE CASCADE
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8');
    }
    
    private function initializeTemplatesFromExistingRules()
    {
        $langId = (int)Configuration::get('PS_LANG_DEFAULT');
    
        $cartRules = Db::getInstance()->executeS('
            SELECT cr.id_cart_rule, crl.name
            FROM '._DB_PREFIX_.'cart_rule cr
            JOIN '._DB_PREFIX_.'cart_rule_lang crl ON cr.id_cart_rule = crl.id_cart_rule
            WHERE crl.id_lang = '.$langId
        );
    
        foreach ($cartRules as $rule) {
            $id_cart_rule = (int)$rule['id_cart_rule'];
            $name = pSQL($rule['name']);
    
            Db::getInstance()->execute('
                INSERT INTO '._DB_PREFIX_.'cart_rule_combination_template 
                (id_cart_rule, template_name, date_add, date_upd)
                VALUES ('.$id_cart_rule.', "'.$name.'", NOW(), NOW())
            ');
    
            $combinations = Db::getInstance()->executeS('
                SELECT id_cart_rule_2 FROM '._DB_PREFIX_.'cart_rule_combination
                WHERE id_cart_rule_1 = '.$id_cart_rule
            );
    
            foreach ($combinations as $combo) {
                $id_combination = (int)$combo['id_cart_rule_2'];
    
                Db::getInstance()->insert('cart_rule_combination_template_rule', [
                    'id_cart_rule' => $id_cart_rule,
                    'id_combination_rule' => $id_combination
                ]);
            }
        }
    
        return true;
    }

    private function uninstallDb()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'cart_rule_combination_template_rule`')
            && Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'cart_rule_combination_template`');
    }

    public function hookActionObjectCartRuleDeleteAfter($params)
    {
        $id = (int)$params['object']->id;
        Db::getInstance()->delete('cart_rule_combination_template_rule', 'id_cart_rule = '.$id.' OR id_combination_rule = '.$id);
        Db::getInstance()->delete('cart_rule_combination_template', 'id_cart_rule = '.$id);
    }

    public function applyTemplates()
    {
        $db = Db::getInstance();
        $db->execute('DELETE FROM '._DB_PREFIX_.'cart_rule_combination');

        $rows = $db->executeS('SELECT id_cart_rule, id_combination_rule FROM '._DB_PREFIX_.'cart_rule_combination_template_rule');
        $inserted = [];

        foreach ($rows as $row) {
            $a = (int)$row['id_cart_rule'];
            $b = (int)$row['id_combination_rule'];

            $key1 = "$a-$b";
            $key2 = "$b-$a";

            if (!isset($inserted[$key1]) && !isset($inserted[$key2])) {
                $db->insert('cart_rule_combination', [
                    'id_cart_rule_1' => $a,
                    'id_cart_rule_2' => $b
                ]);
                $db->insert('cart_rule_combination', [
                    'id_cart_rule_1' => $b,
                    'id_cart_rule_2' => $a
                ]);

                $inserted[$key1] = true;
                $inserted[$key2] = true;
            }
        }
    }

    public function getContent()
    {
        $output = '';
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        if (Tools::isSubmit('applyTemplates')) {
            $this->applyTemplates();
            $output .= $this->displayConfirmation($this->l('Templates appliqués avec succès.'));
        }

        if (Tools::isSubmit('deletecart_rule_combination_template') && Tools::getValue('id_cart_rule')) {
            $id = (int)Tools::getValue('id_cart_rule');
            Db::getInstance()->delete('cart_rule_combination_template_rule', 'id_cart_rule = '.$id);
            Db::getInstance()->delete('cart_rule_combination_template', 'id_cart_rule = '.$id);
            $output .= $this->displayConfirmation($this->l('Template supprimé.'));
        }

        if (Tools::isSubmit('saveCombinationTemplate')) {
            $id_cart_rule = (int)Tools::getValue('id_cart_rule');
            $combinations = Tools::getValue('combinations', []);
            $ruleName = Db::getInstance()->getValue('
                SELECT name FROM '._DB_PREFIX_.'cart_rule_lang
                WHERE id_cart_rule = '.$id_cart_rule.' AND id_lang = '.$default_lang
            );

            Db::getInstance()->delete('cart_rule_combination_template_rule', 'id_cart_rule = '.$id_cart_rule);
            Db::getInstance()->execute('
                INSERT INTO '._DB_PREFIX_.'cart_rule_combination_template (id_cart_rule, template_name, date_add, date_upd)
                VALUES ('.$id_cart_rule.', "'.pSQL($ruleName).'", NOW(), NOW())
                ON DUPLICATE KEY UPDATE template_name = VALUES(template_name), date_upd = NOW()
            ');

            foreach ($combinations as $target) {
                Db::getInstance()->insert('cart_rule_combination_template_rule', [
                    'id_cart_rule' => $id_cart_rule,
                    'id_combination_rule' => (int)$target
                ]);
            }

            return $this->displayConfirmation($this->l('Template enregistré.')).$this->renderTemplateList();
        }
        
        if (Tools::isSubmit('editCombinationTemplate')) {
            $id_cart_rule = (int)Tools::getValue('id_cart_rule');
            $combinations = Tools::getValue('combinations', []);
        
            Db::getInstance()->delete('cart_rule_combination_template_rule', 'id_cart_rule = '.$id_cart_rule);
        
            foreach ($combinations as $target) {
                Db::getInstance()->insert('cart_rule_combination_template_rule', [
                    'id_cart_rule' => $id_cart_rule,
                    'id_combination_rule' => (int)$target
                ]);
            }
        
            return $this->displayConfirmation($this->l('Template modifié.')).$this->renderTemplateList();
        }

        if (Tools::isSubmit('addTemplate') || (Tools::isSubmit('updatecart_rule_combination_template') && Tools::getValue('id_cart_rule'))) {
            return $this->renderForm();
        }

        $output .= $this->renderTemplateList();

        return $output;
    }

    public function renderForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $id_cart_rule_edit = Tools::getValue('id_cart_rule');
        $edit_values = [];

        $rules = Db::getInstance()->executeS('
            SELECT cr.id_cart_rule, crl.name 
            FROM '._DB_PREFIX_.'cart_rule cr 
            JOIN '._DB_PREFIX_.'cart_rule_lang crl ON cr.id_cart_rule = crl.id_cart_rule
            WHERE crl.id_lang = '.$default_lang.'
            AND cr.id_cart_rule NOT IN (SELECT id_cart_rule FROM '._DB_PREFIX_.'cart_rule_combination_template)'
        );

        $options = [];
        $optionsCombinaisons = [];
        foreach ($rules as $rule) {
            $options[] = [
                'id_option' => $rule['id_cart_rule'],
                'name' => $rule['name']
            ];
            
            $optionsCombinaisons[] = [
                'id_option' => $rule['id_cart_rule'],
                'name' => $rule['name']
            ];
        }
        
        $inputs = [
            [
                'type' => 'select',
                'label' => $this->l('Règle panier (template)'),
                'name' => 'id_cart_rule',
                'options' => [
                    'query' => $options,
                    'id' => 'id_option',
                    'name' => 'name'
                ],
                'disabled' => $id_cart_rule_edit ? true : false,
            ],
            [
                'type' => 'select',
                'label' => $this->l('Cumulable avec'),
                'name' => 'combinations[]',
                'multiple' => true,
                'class' => 'custom-select',
                'options' => [
                    'query' => $optionsCombinaisons,
                    'id' => 'id_option',
                    'name' => 'name'
                ]
            ]
        ];
        
        $submit = [
            'title' => $this->l('Enregistrer'),
            'name' => 'saveCombinationTemplate'
        ];

        if ($id_cart_rule_edit) {
            $combinationRules = Db::getInstance()->executeS('
                SELECT id_combination_rule FROM '._DB_PREFIX_.'cart_rule_combination_template_rule
                WHERE id_cart_rule = '.$id_cart_rule_edit
            );

            $edit_values['id_cart_rule'] = $id_cart_rule_edit;
            $edit_values['combinations[]'] = array_column($combinationRules, 'id_combination_rule');
            
            $template = Db::getInstance()->getRow('
                SELECT * FROM '._DB_PREFIX_.'cart_rule_combination_template
                WHERE id_cart_rule = '.$id_cart_rule_edit
            );
            
            $options[] = [
                'id_option' => $template['id_cart_rule'],
                'name' => $template['template_name']
            ];
            
            $submit = [
                'title' => $this->l('Modifier'),
                'name' => 'editCombinationTemplate'
            ];
            
            $inputs[] = [
                'type' => 'hidden',
                'name' => 'id_cart_rule',
                'default' => $id_cart_rule_edit,
            ];
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $id_cart_rule_edit ? $this->l('Modifier un template') : $this->l('Créer un template')
                ],
                'input' => $inputs,
                'submit' => $submit
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->fields_value = $edit_values;
        
        $output = '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("select.fixed-width-xl.custom-select").forEach(function(select) {
                select.classList.remove("fixed-width-xl");
                select.style.height = "200px"; 
            });
        });
        </script>
        ';

        return $helper->generateForm([$fields_form]).$output;
    }

    public function renderTemplateList()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $templates = Db::getInstance()->executeS('
            SELECT t.id_cart_rule, t.template_name
            FROM '._DB_PREFIX_.'cart_rule_combination_template t
            ORDER BY t.template_name ASC
        ');

        foreach ($templates as &$template) {
            $combinations = Db::getInstance()->executeS('
                SELECT crl.name
                FROM '._DB_PREFIX_.'cart_rule_combination_template_rule r
                JOIN '._DB_PREFIX_.'cart_rule_lang crl ON crl.id_cart_rule = r.id_combination_rule
                WHERE r.id_cart_rule = '.(int)$template['id_cart_rule'].' AND crl.id_lang = '.$default_lang
            );

            $template['combinations'] = implode(', ', array_column($combinations, 'name'));
        }

        $fields_list = [
            'id_cart_rule' => [
                'title' => $this->l('ID Règle'),
                'type' => 'int',
                'width' => 50,
            ],
            'template_name' => [
                'title' => $this->l('Nom du template'),
                'type' => 'text',
            ],
            'combinations' => [
                'title' => $this->l('Cumulable avec'),
                'type' => 'text',
            ],
        ];

        $helper = new HelperList();
        $helper->module = $this;
        $helper->title = $this->l('Templates de combinaison');
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id_cart_rule';
        $helper->table = 'cart_rule_combination_template';
        $helper->no_link = true;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->actions = ['edit', 'delete'];

        $create_url = AdminController::$currentIndex.'&configure='.$this->name.'&addTemplate=1&token='.Tools::getAdminTokenLite('AdminModules');
        $output = '<div style="display: flex;gap: 10px;"><a href="'.$create_url.'" class="btn btn-primary" style="margin-bottom:10px;">'.$this->l('Créer un nouveau template').'</a><form method="post"><button type="submit" name="applyTemplates" class="btn btn-primary">'.$this->l('Appliquer les templates').'</button></form></div>';
        $output .= '<br />';
        

        return $output.$helper->generateList($templates, $fields_list);
    }
}
