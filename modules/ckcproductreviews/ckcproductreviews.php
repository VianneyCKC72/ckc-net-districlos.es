<?php

if (!defined('_PS_VERSION_')) exit;

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class CkcProductReviews extends Module implements WidgetInterface
{
    const CONFIG_MIN_RATING = 'CKC_REVIEWS_MIN_RATING';

    public function __construct()
    {
        $this->name = 'ckcproductreviews';
        $this->author = 'Vianney - CKC NET';
        $this->version = '1.0.0';
        $this->bootstrap = true;
        $this->tab = 'front_office_features';
        $this->need_instance = true;
        $this->multishop_context = true;

        parent::__construct();
        $this->_path_assets = $this->_path.'views/assets/';

        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
        $this->displayName = $this->l('CKC Product Reviews');
        $this->description = $this->l('Affiche un carrousel personnalisé des avis clients.');
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue(self::CONFIG_MIN_RATING, 4)
            && Configuration::updateValue('CKC_REVIEWS_ENABLE_CAROUSEL', 1)
            && $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('CKC_REVIEWS_ENABLE_CAROUSEL')
            && Configuration::deleteByName(self::CONFIG_MIN_RATING);
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitCkcProductReviews')) {
            $minRating = (int) Tools::getValue(self::CONFIG_MIN_RATING);
            $enableCarousel = (int) Tools::getValue('CKC_REVIEWS_ENABLE_CAROUSEL');
            Configuration::updateValue(self::CONFIG_MIN_RATING, $minRating);
            Configuration::updateValue('CKC_REVIEWS_ENABLE_CAROUSEL', $enableCarousel);
            $output .= $this->displayConfirmation($this->l('Configuration enregistrée.'));
        }

        $minRating = (int) Configuration::get(self::CONFIG_MIN_RATING);
        $enableCarousel = (int) Configuration::get('CKC_REVIEWS_ENABLE_CAROUSEL');

        return $output.$this->renderForm($minRating, $enableCarousel);
    }

    private function renderForm($minRating, $enableCarousel)
    {
        $form = new HelperForm();

        $form->module = $this;
        $form->name_controller = $this->name;
        $form->token = Tools::getAdminTokenLite('AdminModules');
        $form->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $form->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $form->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $form->title = $this->displayName;
        $form->show_cancel_button = false;
        $form->submit_action = 'submitCkcProductReviews';

        $fields_form = [
            'form' => [
                'legend' => ['title' => $this->l('Paramètres du carrousel d’avis')],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Note minimale à afficher'),
                        'name' => self::CONFIG_MIN_RATING,
                        'options' => [
                            'query' => [
                                ['id' => 1, 'name' => '1 étoile et plus'],
                                ['id' => 2, 'name' => '2 étoiles et plus'],
                                ['id' => 3, 'name' => '3 étoiles et plus'],
                                ['id' => 4, 'name' => '4 étoiles et plus'],
                                ['id' => 5, 'name' => '5 étoiles uniquement'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activer le carrousel Swiper'),
                        'name' => 'CKC_REVIEWS_ENABLE_CAROUSEL',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')],
                        ],
                    ]
                ],

                'submit' => [
                    'title' => $this->l('Sauvegarder'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $form->fields_value = [self::CONFIG_MIN_RATING => $minRating, 'CKC_REVIEWS_ENABLE_CAROUSEL' => $enableCarousel];

        return $form->generateForm([$fields_form]);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        $minRating = (int) Configuration::get(self::CONFIG_MIN_RATING);

        $sql = "SELECT customer_name, rating, review, date_add 
            FROM "._DB_PREFIX_."av_products_reviews 
            WHERE active = 1 
              AND rating >= ".(int)$minRating."
            ORDER BY date_add DESC 
            LIMIT 10";

        $reviews = Db::getInstance()->executeS($sql);

        $this->context->controller->addCSS($this->_path_assets.'ckcproductreviews.css');
        $this->context->controller->addJS($this->_path_assets.'ckcproductreviews.js');

        $this->smarty->assign([
            'reviews' => $reviews,
        ]);


        return $this->fetch('module:ckcproductreviews/views/templates/hook/displayProductReviews.tpl');
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        return [];
    }

    public function hookDisplayHome($params)
    {
        $minRating = (int) Configuration::get(self::CONFIG_MIN_RATING);
        $enableCarousel = (int) Configuration::get('CKC_REVIEWS_ENABLE_CAROUSEL');


        $sql = "SELECT *
            FROM "._DB_PREFIX_."av_products_reviews
            WHERE rate >= $minRating
            ORDER BY horodate DESC
            LIMIT 10";

        $results = Db::getInstance()->executeS($sql);

        // Préparation des avis
        $reviews = [];
        foreach ($results as $row) {
            $row['date_experience'] = $row['horodate_order']; // On ajoute la date de commande avec un nom plus parlant
            $reviews[] = $row;
        }

        $this->context->controller->addCSS($this->_path_assets.'ckcproductreviews.css');
        $this->context->controller->addJS($this->_path_assets.'ckcproductreviews.js');
        $this->context->smarty->assign([
            'reviews' => $reviews,
            'enable_carousel' => $enableCarousel,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/displayProductReviews.tpl');
    }

}
