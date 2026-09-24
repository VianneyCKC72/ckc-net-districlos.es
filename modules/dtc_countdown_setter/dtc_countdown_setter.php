<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class DTC_countdown_setter extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'dtc_countdown_setter';
        $this->tab = 'front_office_features';
        $this->version = '1.1.1';
        $this->author = 'Districlos';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Countdown setter (Districlos)');
        $this->description = $this->l('Module utiliser pour programmer des comptes à rebours type soldes');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDTC_countdown_setterModule')) {
            $id_dtc_countdown_setter = Tools::getValue('id_dtc_countdown_setter');

            // Récupérer la valeur des sélecteurs CSS
            $selectors = pSQL(Tools::getValue('selectors'));

            if ($id_dtc_countdown_setter) {
                // Récupérer les valeurs existantes
                $existing_data = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'dtc_countdown_setter WHERE id_dtc_countdown_setter = ' . (int)$id_dtc_countdown_setter);

                // Fusionner les nouvelles données avec les données existantes
                $data = array_merge($existing_data, array(
                    'label' => pSQL(Tools::getValue('label')),
                    'bg_color' => pSQL(Tools::getValue('bg_color')),
                    'txt_color' => pSQL(Tools::getValue('txt_color')),
                    'start_date' => Tools::getValue('start_date'),
                    'end_date' => Tools::getValue('end_date'),
                    'cta' => (int)Tools::getValue('cta'),
                    'cta_label' => pSQL(Tools::getValue('cta_label')),
                    'cta_link' => pSQL(Tools::getValue('cta_link')),
                    'cta_bg_color' => pSQL(Tools::getValue('cta_bg_color')),
                    'cta_txt_color' => pSQL(Tools::getValue('cta_txt_color')),
                    'active' => (int)Tools::getValue('active'),
                    'selectors' => $selectors,
                ));

                $this->updateCountdown($id_dtc_countdown_setter, $data);
                $output .= $this->displayConfirmation($this->l('Countdown updated successfully'));
            } else {
                $data = array(
                    'label' => pSQL(Tools::getValue('label')),
                    'bg_color' => pSQL(Tools::getValue('bg_color')),
                    'txt_color' => pSQL(Tools::getValue('txt_color')),
                    'start_date' => Tools::getValue('start_date'),
                    'end_date' => Tools::getValue('end_date'),
                    'cta' => (int)Tools::getValue('cta'),
                    'cta_label' => pSQL(Tools::getValue('cta_label')),
                    'cta_link' => pSQL(Tools::getValue('cta_link')),
                    'cta_bg_color' => pSQL(Tools::getValue('cta_bg_color')),
                    'cta_txt_color' => pSQL(Tools::getValue('cta_txt_color')),
                    'active' => (int)Tools::getValue('active'),
                    'selectors' => $selectors,
                );

                $this->addCountdown($data);
                $output .= $this->displayConfirmation($this->l('Countdown added successfully'));
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name);
        } elseif (Tools::isSubmit('deleteCountdown')) {
            $this->deleteCountdown(Tools::getValue('id_dtc_countdown_setter'));
            $output .= $this->displayConfirmation($this->l('Countdown deleted successfully'));
        }

        if (Tools::isSubmit('toggleCountdown')) {
            $this->toggleCountdown(Tools::getValue('id_dtc_countdown_setter'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&toggleSuccess=1');
        }

        if (Tools::isSubmit('editCountdown') || Tools::isSubmit('addNewCountdown')) {
            return $output . $this->renderForm(Tools::getValue('id_dtc_countdown_setter'));
        } else {
            // Afficher le message de confirmation si c'est une redirection après toggle
            if (Tools::getValue('toggleSuccess')) {
                $output .= $this->displayConfirmation($this->l('Countdown status changed successfully.'));
            }
            return $output . $this->renderCountdownList();
        }
    }





    protected function renderCountdownList()
    {
        $countdowns = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'dtc_countdown_setter ORDER BY id_dtc_countdown_setter DESC');

        $this->context->smarty->assign(array(
            'countdowns' => $countdowns,
            'link' => $this->context->link,
            'module_name' => $this->name,
        ));

        return $this->display(__FILE__, 'views/templates/admin/countdowns_list.tpl');
    }





    public function toggleCountdown($id_dtc_countdown_setter)
    {
        $countdown = Db::getInstance()->getRow('SELECT active FROM ' . _DB_PREFIX_ . 'dtc_countdown_setter WHERE id_dtc_countdown_setter = ' . (int)$id_dtc_countdown_setter);
        
        if (!$countdown) {
            return false;
        }
        
        $new_status = $countdown['active'] ? 0 : 1;
        return Db::getInstance()->update('dtc_countdown_setter', array('active' => $new_status), 'id_dtc_countdown_setter = ' . (int)$id_dtc_countdown_setter);
    }


    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm($id_dtc_countdown_setter = null)
    {
        // If $id_dtc_countdown_setter is provided, load existing data
        $countdown = array();
        if ($id_dtc_countdown_setter) {
            $countdown = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'dtc_countdown_setter WHERE id_dtc_countdown_setter = ' . (int)$id_dtc_countdown_setter);
            // Convert date format to dd/mm/yyyy for form display
            $countdown['start_date'] = date('d/m/Y H:i:s', strtotime($countdown['start_date']));
            $countdown['end_date'] = date('d/m/Y H:i:s', strtotime($countdown['end_date']));
        }

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDTC_countdown_setterModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules',true)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $hidden_id_field = $id_dtc_countdown_setter ? array(
            'type' => 'hidden',
            'name' => 'id_dtc_countdown_setter',
            'value' => $id_dtc_countdown_setter
        ) : array();

        $helper->tpl_vars = array(
            'fields_value' => array_merge($this->getConfigFormValues(), $countdown), // Pre-fill form with countdown data if available
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'hidden' => $hidden_id_field // Inclure le champ caché dans le formulaire
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }



    public function addCountdown($data)
    {
        $insert_data = array(
            'label' => pSQL($data['label']),
            'bg_color' => pSQL($data['bg_color']),
            'txt_color' => pSQL($data['txt_color']),
            'start_date' => pSQL($data['start_date']),
            'end_date' => pSQL($data['end_date']),
            'cta' => (int)$data['cta'],
            'cta_label' => pSQL($data['cta_label']),
            'cta_link' => pSQL($data['cta_link']),
            'cta_bg_color' => pSQL($data['cta_bg_color']),
            'cta_txt_color' => pSQL($data['cta_txt_color']),
            'active' => (int)$data['active'],
            'selectors' => pSQL($data['selectors'])
        );

        return Db::getInstance()->insert('dtc_countdown_setter', $insert_data);
    }

    public function updateCountdown($id_dtc_countdown_setter, $data)
    {
        // Formatage des dates au bon format avant la mise à jour
        if (!empty($data['start_date'])) {
            $data['start_date'] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $data['start_date'])));
        }
        if (!empty($data['end_date'])) {
            $data['end_date'] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $data['end_date'])));
        }

        return Db::getInstance()->update('dtc_countdown_setter', $data, 'id_dtc_countdown_setter = '.(int)$id_dtc_countdown_setter);
    }



    public function deleteCountdown($id_dtc_countdown_setter)
    {
        return Db::getInstance()->delete('dtc_countdown_setter', 'id_dtc_countdown_setter = '.(int)$id_dtc_countdown_setter);
    }



    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'tabs' => array(
                    'general' => $this->l('General'),
                    'CTA' => $this->l('Call to action'),
                ),
                'input' => array(
                    array(
                        'type' => 'hidden',
                        'name' => 'id_dtc_countdown_setter',
                    ),
                    array(
                        'type' => 'color',
                        'name' => 'bg_color',
                        'label' => $this->l('Couleur de fond du compteur'),
                        'tab' => 'general',
                    ),
                    array(
                        'type' => 'color',
                        'name' => 'txt_color',
                        'label' => $this->l('Couleur du texte'),
                        'tab' => 'general',
                    ),
                    array(
                        'type' => 'textarea',
                        'name' => 'label',
                        'label' => $this->l('Texte affiché sur le compteur'),
                        'autoload_rte' => true,
                        'tab' => 'general',
                    ),
                    array(
                        'type' => 'datetime',
                        'name' => 'start_date',
                        'label' => $this->l('Date de début du compte à rebours'),
                        'tab' => 'general',
                    ),
                    array(
                        'type' => 'datetime',
                        'name' => 'end_date',
                        'label' => $this->l('Date de fin du compte à rebours'),
                        'tab' => 'general',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Emplacements (sélecteurs CSS)'),
                        'name' => 'selectors',
                        'desc' => $this->l('Ex : #home-banner, #popup-countdown (séparés par des virgules)'),
                        'lang' => false,
                        'tab' => 'general',
                    ),
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'name' => 'cta',
                        'label' => $this->l('Afficher un bouton de CTA'),
                        'tab' => 'CTA',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Activé')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Désactivé')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'cta_label',
                        'label' => $this->l('Texte du bouton de CTA'),
                        'tab' => 'CTA',
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'cta_link',
                        'label' => $this->l('Lien du bouton de CTA'),
                        'tab' => 'CTA',
                    ),
                    array(
                        'type' => 'color',
                        'name' => 'cta_bg_color',
                        'label' => $this->l('Couleur de fond du bouton de CTA'),
                        'tab' => 'CTA',
                    ),
                    array(
                        'type' => 'color',
                        'name' => 'cta_txt_color',
                        'label' => $this->l('Couleur du texte du bouton de CTA'),
                        'tab' => 'CTA',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $defaults = array(
            'bg_color' => '',
            'txt_color' => '',
            'label' => '',
            'start_date' => '',
            'end_date' => '',
            'cta' => 0,
            'cta_label' => '',
            'cta_link' => '',
            'cta_bg_color' => '',
            'cta_txt_color' => '',
            'selectors' => '',
        );

        // Si l'id est passé, récupérez les valeurs réelles pour ce compte à rebours
        if ($id_dtc_countdown_setter = Tools::getValue('id_dtc_countdown_setter')) {
            $existing_data = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'dtc_countdown_setter WHERE id_dtc_countdown_setter = ' . (int)$id_dtc_countdown_setter);
            if ($existing_data) {
                return array_merge($defaults, $existing_data);
            }
        }

        return $defaults;
    }


    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
        $this->context->controller->addJS($this->_path.'views/js/countdown.js');

        // Récupérer les comptes à rebours actifs
        $countdowns = Db::getInstance()->executeS('
            SELECT * FROM ' . _DB_PREFIX_ . 'dtc_countdown_setter
            WHERE active = 1
            ORDER BY start_date DESC
        ');

        // Préparer les données pour JavaScript
        $data = array();
        foreach ($countdowns as $countdown) {
            $selectors = explode(',', $countdown['selectors']);
            foreach ($selectors as $selector) {
                $data[] = array(
                    'selector' => trim($selector),
                    'start_date' => $countdown['start_date'],
                    'end_date' => $countdown['end_date'],
                    'label' => $this->translateCountdownText($countdown['label']),
                    'bg_color' => $countdown['bg_color'],
                    'txt_color' => $countdown['txt_color'],
                    'cta' => $countdown['cta'],
                    'cta_label' => $this->translateCountdownText($countdown['cta_label']),
                    'cta_link' => $countdown['cta_link'],
                    'cta_bg_color' => $countdown['cta_bg_color'],
                    'cta_txt_color' => $countdown['cta_txt_color'],
                );
            }
        }

        Media::addJsDef(array(
            'countdownData' => $data,
            'countdownI18n' => array(
                'upcoming' => $this->trans('À venir', [], 'Shop.Theme.Customdtc'),
                'ended' => $this->trans('Terminé', [], 'Shop.Theme.Customdtc'),
                'days' => $this->trans('j', [], 'Shop.Theme.Customdtc'),
                'hours' => $this->trans('h', [], 'Shop.Theme.Customdtc'),
                'minutes' => $this->trans('m', [], 'Shop.Theme.Customdtc'),
                'seconds' => $this->trans('s', [], 'Shop.Theme.Customdtc'),
            ),
        ));
    }

    /**
     * Libellé BO (une langue) → catalogue thème, sans HTML.
     */
    protected function translateCountdownText($text)
    {
        $plain = trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plain = preg_replace('/\x{00A0}/u', ' ', $plain);
        $plain = trim(preg_replace('/\s+/u', ' ', $plain));
        if ($plain === '') {
            return $text;
        }

        $translated = $this->trans($plain, [], 'Shop.Theme.Customdtc');
        if ($translated !== $plain) {
            return $translated;
        }

        $withoutColon = rtrim($plain, " \t:");
        if ($withoutColon !== $plain) {
            $alt = $this->trans($withoutColon, [], 'Shop.Theme.Customdtc');
            if ($alt !== $withoutColon) {
                return $alt . ':';
            }
        }

        return $plain;
    }

     public function hookDisplayHeader() {
        return $this->hookHeader();
     }
//     public function hookDisplayHome()
//     {
//         // Récupérer tous les comptes à rebours actifs
//         $countdowns = Db::getInstance()->executeS('
//             SELECT * FROM ' . _DB_PREFIX_ . 'dtc_countdown_setter
//             WHERE active = 1
//             ORDER BY start_date DESC
//         ');
//
//         // Vérifier s'il existe des comptes à rebours actifs
//         if (!empty($countdowns)) {
//             // Assigner les comptes à rebours aux variables Smarty
//             $this->context->smarty->assign(array(
//                 'countdowns' => $countdowns,
//             ));
//
//             // Afficher le template avec les données assignées
//             return $this->display(__FILE__, 'views/templates/hook/dtc_countdown_setter.tpl');
//         }
//
//         // Si aucun compte à rebours n'est actif, retourner une chaîne vide
//         return '';
//     }


}
