<?php
/**
 * 2007-2025 PrestaShop
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
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ckcstorespresentation extends Module implements WidgetInterface
{
    protected $config_form = false;
    protected $_postErrors = [];
    protected $_output = '';

    protected $templateFile;

    public function __construct()
    {
        $this->name = 'ckcstorespresentation';
        $this->tab = 'front_office_features';
        $this->version = '1.2.0';
        $this->author = 'ckc-net';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ckc Stores Presentation');
        $this->description = $this->l('Optimisez la gestion de vos magasins avec ce module Prestashop. Créez, modifiez et administrez facilement vos points de vente en leur attribuant des coordonnées précises et en les rattachant à une boutique spécifique');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:ckcstorespresentation/views/templates/front/nos-magasins.tpl';
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CKCSTORESPRESENTATION_LIVE_MODE', false);
        Configuration::updateValue('GOOGLE_API_KEY', '');
        Configuration::updateValue('CKCSTORESPRESENTATION_CMS_LISTING_ID', 11);
        require_once __DIR__ . '/sql/install.php'; // sql querries

        return parent::install() &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('displayLeftColumn') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerStoreReviewsHook();
    }

    public function uninstall()
    {
        Configuration::deleteByName('CKCSTORESPRESENTATION_LIVE_MODE');
        Configuration::deleteByName('GOOGLE_API_KEY');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->registerStoreReviewsHook();

        /**
         * If values have been submitted in the form, process.
         */

        if (((bool)Tools::isSubmit('edit_store')) == true) {
            $this->context->smarty->assign('module_dir', $this->_path);

            $idStore = Tools::getValue('ID');

            $this->_output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/form.tpl');
            $this->_output .= $this->renderForm($idStore);

        } elseif (((bool)Tools::isSubmit('create_store')) == true) {
            $this->context->smarty->assign('module_dir', $this->_path);

            $this->_output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/form.tpl');
            $this->_output .= $this->renderForm();

        } elseif (((bool)Tools::isSubmit('ReturnToList')) == true) {

            Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]));

        } elseif (((bool)Tools::isSubmit('submitCkcstorespresentationSettings')) == true) {
            Configuration::updateValue('GOOGLE_API_KEY', Tools::getValue('GOOGLE_API_KEY'));
            Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]));
        } elseif (((bool)Tools::isSubmit('submitCkcstorespresentationModule')) == true) {

            $idStore = Tools::getValue('CKCSTORESPRESENTATION_ID') == "" ? null : Tools::getValue('CKCSTORESPRESENTATION_ID');

            $this->postValidation();
            if (!count($this->_postErrors)) {
                $this->postProcess();
                Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]));
            } else {

                foreach ($this->_postErrors as $err) {
                    $this->_output = $this->displayError($err);
                }
                $this->context->smarty->assign('module_dir', $this->_path);

                $this->_output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/form.tpl');
                $this->_output .= $this->renderForm($idStore);
            }
        } elseif(((bool)Tools::isSubmit('delete_store')) == true) {

            $idStore = Tools::getValue('ID');

            $delete = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckcstorespresentation` WHERE `id_ckcstorespresentation` = '.$idStore);

            Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]));

        } else {

            $stores = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckcstorespresentation');

            $this->context->smarty->assign([
                'module_dir' => $this->_path,
                'stores' => $stores,
                'current_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
            ]);

            $this->_output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
            $this->_output .= $this->renderSettingsForm();
        }

        return $this->_output;

    }

    public function getGoogleApiKey()
    {
        return (string) Configuration::get('GOOGLE_API_KEY', '');
    }

    protected function renderSettingsForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCkcstorespresentationSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => ['GOOGLE_API_KEY' => $this->getGoogleApiKey()],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];
        $syncUrl = htmlspecialchars($this->context->link->getModuleLink(
            $this->name,
            'SyncGoogleReviews',
            ['token' => substr(Tools::encrypt('ckcstorespresentation/syncgooglereviews'), 0, 10)],
            true
        ), ENT_QUOTES, 'UTF-8');

        return $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->l('Paramètres'), 'icon' => 'icon-cogs'],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Clé API Google'),
                        'name' => 'GOOGLE_API_KEY',
                        'size' => 40,
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->l('Avis Google'),
                        'name' => 'sync_google_reviews',
                        'html_content' => '<a href="' . $syncUrl . '" target="_blank" class="btn btn-default">'
                            . $this->l('Synchroniser les avis Google') . '</a>'
                            . '<p class="help-block">' . $this->l('Creative Elements : hook') . ' <code>displayCkcStoreReviews</code></p>',
                    ],
                ],
                'submit' => ['title' => $this->l('Enregistrer')],
            ],
        ]]);
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm($idStore = null)
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCkcstorespresentationModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues($idStore), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm($idStore)));
    }

    protected function postValidation()
    {
        if (Tools::isSubmit('submitCkcstorespresentationModule')) {

            if (!Tools::getValue('CKCSTORESPRESENTATION_DEPARTEMENT')) {
                $this->_postErrors[] = $this->trans(
                    'This field is required.',
                    [],
                    'Modules.ckcstorespresentation.Admin'
                );
            } elseif (!Tools::getValue('CKCSTORESPRESENTATION_ADRESSE')) {
                $this->_postErrors[] = $this->trans(
                    'This field is required.',
                    [],
                    'Modules.ckcstorespresentation.Admin'
                );
            } elseif (!Tools::getValue('CKCSTORESPRESENTATION_VILLE')) {
                $this->_postErrors[] = $this->trans(
                    'This field is required.',
                    [],
                    'Modules.ckcstorespresentation.Admin'
                );
            } elseif (!Tools::getValue('CKCSTORESPRESENTATION_CODE_POSTAL')) {
                $this->_postErrors[] = $this->trans(
                    'This field is required.',
                    [],
                    'Modules.ckcstorespresentation.Admin'
                );
            } elseif (!Tools::getValue('CKCSTORESPRESENTATION_EMAIL')) {
                $this->_postErrors[] = $this->trans(
                    'This field is required.',
                    [],
                    'Modules.ckcstorespresentation.Admin'
                );
            }
            if (!Tools::getValue('CKCSTORESPRESENTATION_TELEPHONE')) {
                $this->_postErrors[] = $this->trans(
                    'This field is required.',
                    [],
                    'Modules.ckcstorespresentation.Admin'
                );
            } elseif (!Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_JOURS')) {
                $this->_postErrors[] = $this->trans(
                    'This field is required.',
                    [],
                    'Modules.ckcstorespresentation.Admin'
                );
            } elseif (!Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_HEURES')) {
                $this->_postErrors[] = $this->trans(
                    'This field is required.',
                    [],
                    'Modules.ckcstorespresentation.Admin'
                );
            } elseif (!Tools::getValue('CKCSTORESPRESENTATION_IMG_PATH') && !Tools::getValue('CKCSTORESPRESENTATION_ID')) {
                $this->_postErrors[] = $this->trans(
                    'The image is required.',
                    [],
                    'Modules.ckcstorespresentation.Admin'
                );
            }
        }
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm($id = null)
    {

        $multiBoutiqueSelect = \Db::getInstance()->ExecuteS('SELECT `id_store` as id, `name` FROM `' . _DB_PREFIX_ . 'ckcstockstores_stores`');
        $cmsPageSelect = \Db::getInstance()->ExecuteS('SELECT cl.id_cms as id, cl.meta_title as name FROM `' . _DB_PREFIX_ . 'cms_lang` cl
        LEFT JOIN `' . _DB_PREFIX_ . 'cms` c ON c.id_cms = cl.id_cms
        WHERE c.id_cms_category = 5');

        if(isset($id)) {
            $background_image = Db::getInstance()->executeS('SELECT img_path FROM ' . _DB_PREFIX_ . 'ckcstorespresentation WHERE id_ckcstorespresentation = '.$id);
            $image_url = $background_image[0]["img_path"] ? _MODULE_DIR_.'ckcstorespresentation'. DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR.$background_image[0]["img_path"] : '';
            $image = '<div class="col-lg-6"><img src="' . $image_url . '" class="img-thumbnail"></div>';
        } else {
            $image = '';
        }

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Création de la présentation'),
                    'icon' => 'icon-cogs',
                ),
                'buttons' => array(
                    'Return'=> array(
                        'type' => 'submit',
                        'name' => 'ReturnToList',
                        'title' => $this->l('Annuler'),
                        'class' => 'btn btn-default pull-left',
                        'id'   => 'mybutton',
                        'icon' => 'icon-foo',
                    ),
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'CKCSTORESPRESENTATION_DEPARTEMENT',
                        'label' => $this->l('Département'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Adresse'),
                        'name' => 'CKCSTORESPRESENTATION_ADRESSE',
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'CKCSTORESPRESENTATION_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'CKCSTORESPRESENTATION_VILLE',
                        'label' => $this->l('Ville'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'CKCSTORESPRESENTATION_CODE_POSTAL',
                        'label' => $this->l('Code postal'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'CKCSTORESPRESENTATION_TELEPHONE',
                        'label' => $this->l('Téléphone'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Horaires Jours'),
                        'name' => 'CKCSTORESPRESENTATION_HORAIRES_JOURS',
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Horaires Heures'),
                        'name' => 'CKCSTORESPRESENTATION_HORAIRES_HEURES',
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Horaires Samedi'),
                        'name' => 'CKCSTORESPRESENTATION_HORAIRES_SAMEDI',
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Informations Supplémentaires'),
                        'name' => 'CKCSTORESPRESENTATION_HORAIRES_INFO_SUPPL',
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Latitude'),
                        'name' => 'CKCSTORESPRESENTATION_HORAIRES_LATITUDE',
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Longitude'),
                        'name' => 'CKCSTORESPRESENTATION_HORAIRES_LONGITUDE',
                    ),
                    array(
                        'type' => 'file',
                        'label' => $this->l('Image magasin'),
                        'name' => 'CKCSTORESPRESENTATION_IMG_PATH',
                        'desc' => $this->l('Maximum image size: '),
                        'display_image' => true,
                        'image' => $image  // Afficher l'image
                    ),
                    array(
                        'type'    => 'select',
                        'label'   => $this->l('Sélectionner le magasin concerné'),
                        'name'    => 'CKCSTORESPRESENTATION_MULTIBOUTIQUE_ID',
                        'options' => [
                            'query' => $multiBoutiqueSelect,
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                    ),
                    array(
                        'type'    => 'select',
                        'label'   => $this->l('Page de détails'),
                        'name'    => 'CKCSTORESPRESENTATION_PAGE_DETAIL',
                        'options' => [
                            'query' => $cmsPageSelect,
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'CKCSTORESPRESENTATION_ID',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Enregistrer'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues(&$idStore = null)
    {
        if(!isset($idStore)) {
            $data = array(
                'CKCSTORESPRESENTATION_DEPARTEMENT' => "",
                'CKCSTORESPRESENTATION_ADRESSE' => "",
                'CKCSTORESPRESENTATION_VILLE' => "",
                'CKCSTORESPRESENTATION_CODE_POSTAL' => "",
                'CKCSTORESPRESENTATION_EMAIL' => "",
                'CKCSTORESPRESENTATION_TELEPHONE' => "",
                'CKCSTORESPRESENTATION_HORAIRES_LATITUDE' => "",
                'CKCSTORESPRESENTATION_HORAIRES_LONGITUDE' => "",
                'CKCSTORESPRESENTATION_HORAIRES_JOURS' => "",
                'CKCSTORESPRESENTATION_HORAIRES_HEURES' => "",
                'CKCSTORESPRESENTATION_HORAIRES_SAMEDI' => "",
                'CKCSTORESPRESENTATION_HORAIRES_INFO_SUPPL' => "",
                'CKCSTORESPRESENTATION_IMG_PATH' => "",
                'CKCSTORESPRESENTATION_MULTIBOUTIQUE_ID' => "",
                'CKCSTORESPRESENTATION_ID' => "",
                'CKCSTORESPRESENTATION_PAGE_DETAIL' => "",
            );
        } else {
            $query =  Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckcstorespresentation WHERE id_ckcstorespresentation = '.$idStore);

            $data = array(
                'CKCSTORESPRESENTATION_DEPARTEMENT' => $query[0]["departement"],
                'CKCSTORESPRESENTATION_ADRESSE' => $query[0]["adresse"],
                'CKCSTORESPRESENTATION_VILLE' => $query[0]["ville"],
                'CKCSTORESPRESENTATION_CODE_POSTAL' => $query[0]["code_postal"],
                'CKCSTORESPRESENTATION_EMAIL' => $query[0]["email"],
                'CKCSTORESPRESENTATION_TELEPHONE' => $query[0]["telephone"],
                'CKCSTORESPRESENTATION_HORAIRES_LATITUDE' => $query[0]["latitude"],
                'CKCSTORESPRESENTATION_HORAIRES_LONGITUDE' => $query[0]["longitude"],
                'CKCSTORESPRESENTATION_HORAIRES_JOURS' => $query[0]["horaires_jours"],
                'CKCSTORESPRESENTATION_HORAIRES_HEURES' => $query[0]["horaires_heures"],
                'CKCSTORESPRESENTATION_HORAIRES_SAMEDI' => $query[0]["horaires_samedi"],
                'CKCSTORESPRESENTATION_HORAIRES_INFO_SUPPL' => $query[0]["horaires_info_suppl"],
                'CKCSTORESPRESENTATION_IMG_PATH' => $query[0]["img_path"],
                'CKCSTORESPRESENTATION_MULTIBOUTIQUE_ID' => $query[0]["id_multiboutique"],
                'CKCSTORESPRESENTATION_ID' => $idStore,
                'CKCSTORESPRESENTATION_PAGE_DETAIL' => $query[0]["id_cms_detail"]
            );
        }

        return $data;
    }


    /** @var array|false|null */
    private $cmsStoreCache = false;

    protected function getCmsStoreForPage(): ?array
    {
        if ($this->cmsStoreCache !== false) {
            return $this->cmsStoreCache ?: null;
        }
        $this->cmsStoreCache = null;
        if (!($this->context->controller instanceof CmsController) || !isset($this->context->controller->cms->id)) {
            return null;
        }
        $this->cmsStoreCache = Db::getInstance()->getRow(
            'SELECT s.*, p.`place_id`, p.`google_rating`, p.`google_rating_count`
            FROM `' . _DB_PREFIX_ . 'ckcstorespresentation` s
            LEFT JOIN `' . _DB_PREFIX_ . 'presentation_place` p
                ON p.`id_ckcstorespresentation` = s.`id_ckcstorespresentation`
            WHERE s.`id_cms_detail` = ' . (int) $this->context->controller->cms->id
        ) ?: null;

        return $this->cmsStoreCache;
    }

    public function renderWidget($hookName, array $configuration)
    {
        $idCms = ($this->context->controller instanceof CmsController && isset($this->context->controller->cms->id))
            ? (int) $this->context->controller->cms->id : 0;
        $listingCmsId = (int) Configuration::get('CKCSTORESPRESENTATION_CMS_LISTING_ID') ?: 11;
        if ($this->getCmsStoreForPage() || $idCms !== $listingCmsId) {
            return '';
        }

        static $storeSelectorRendered = false;
        if ($storeSelectorRendered) {
            return '';
        }
        $storeSelectorRendered = true;

        $key = 'ckcstorespresentation|' . $hookName;
        $template = $this->templateFile;

        if (!$this->isCached($template, $this->getCacheId($key))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($template, $this->getCacheId($key));
    }

    /**
     * @param string $hookName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $magasins = Db::getInstance()->executeS(
            'SELECT s.*, p.`place_id`, p.`google_rating`, p.`google_rating_count`
            FROM `' . _DB_PREFIX_ . 'ckcstorespresentation` s
            LEFT JOIN `' . _DB_PREFIX_ . 'presentation_place` p
                ON p.`id_ckcstorespresentation` = s.`id_ckcstorespresentation`'
        );

        return [
            'modulePath' => $this->context->link->getModuleLink('ckcstorespresentation', 'NosMagasins'),
            'lang' => Context::getContext()->language->iso_code,
            'elements' => $magasins,
        ];
    }

    public function getCoordinatesFromAddress($address)
    {
        $googleApiKey = $this->getGoogleApiKey();
        if (!empty($googleApiKey)) {
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='
                . urlencode($address)
                . '&key=' . urlencode($googleApiKey);
            $response = Tools::file_get_contents($url);
            if ($response !== false) {
                $data = json_decode($response, true);
                if (!empty($data['results'][0]['geometry']['location'])) {
                    return [
                        'latitude' => $data['results'][0]['geometry']['location']['lat'],
                        'longitude' => $data['results'][0]['geometry']['location']['lng'],
                    ];
                }
                if (!empty($data['error_message'])) {
                    return ['error' => $data['error_message']];
                }
            }

            return ['error' => 'Impossible de récupérer les données via Google Geocoding'];
        }

        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address);
        $options = [
            "http" => [
                "header" => "User-Agent: MyGeocoder/1.0"
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            return ["error" => "Impossible de récupérer les données"];
        }
        $data = json_decode($response, true);
        if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
            return [
                'latitude' => $data[0]['lat'],
                'longitude' => $data[0]['lon']
            ];
        }
        return ["error" => "Adresse introuvable"];
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {

        $CKCSTORESPRESENTATION_DEPARTEMENT = Tools::getValue('CKCSTORESPRESENTATION_DEPARTEMENT');
        $CKCSTORESPRESENTATION_ADRESSE = Tools::getValue('CKCSTORESPRESENTATION_ADRESSE');
        $CKCSTORESPRESENTATION_VILLE = Tools::getValue('CKCSTORESPRESENTATION_VILLE');
        $CKCSTORESPRESENTATION_CODE_POSTAL = Tools::getValue('CKCSTORESPRESENTATION_CODE_POSTAL');
        $CKCSTORESPRESENTATION_EMAIL = Tools::getValue('CKCSTORESPRESENTATION_EMAIL');
        $CKCSTORESPRESENTATION_TELEPHONE = Tools::getValue('CKCSTORESPRESENTATION_TELEPHONE');
        $CKCSTORESPRESENTATION_HORAIRES_JOURS = Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_JOURS');
        $CKCSTORESPRESENTATION_HORAIRES_HEURES = Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_HEURES');
        $CKCSTORESPRESENTATION_HORAIRES_SAMEDI = Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_SAMEDI') == "" ? null : Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_SAMEDI');
        $CKCSTORESPRESENTATION_HORAIRES_INFO_SUPPL = Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_INFO_SUPPL') == "" ? null : Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_INFO_SUPPL');

        $address = $CKCSTORESPRESENTATION_ADRESSE.' '.$CKCSTORESPRESENTATION_CODE_POSTAL.' '.$CKCSTORESPRESENTATION_VILLE;

        if(empty(Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_LATITUDE')) OR empty(Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_LONGITUDE'))){
            $coordinates = $this->getCoordinatesFromAddress($address);

            $CKCSTORESPRESENTATION_HORAIRES_LATITUDE = $coordinates["latitude"];
            $CKCSTORESPRESENTATION_HORAIRES_LONGITUDE = $coordinates["longitude"];
        } else {
            $CKCSTORESPRESENTATION_HORAIRES_LATITUDE = Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_LATITUDE');
            $CKCSTORESPRESENTATION_HORAIRES_LONGITUDE = Tools::getValue('CKCSTORESPRESENTATION_HORAIRES_LONGITUDE');
        }

        $CKCSTORESPRESENTATION_MULTIBOUTIQUE_ID = Tools::getValue('CKCSTORESPRESENTATION_MULTIBOUTIQUE_ID');
        $CKCSTORESPRESENTATION_ID = Tools::getValue('CKCSTORESPRESENTATION_ID') == "" ? null : Tools::getValue('CKCSTORESPRESENTATION_ID');
        $CKCSTORESPRESENTATION_PAGE_DETAIL =  Tools::getValue('CKCSTORESPRESENTATION_PAGE_DETAIL');

        if(isset($CKCSTORESPRESENTATION_ID)){

            if(isset($_FILES['CKCSTORESPRESENTATION_IMG_PATH']) && strlen($_FILES['CKCSTORESPRESENTATION_IMG_PATH']['name']) > 0) {
                $file_name = $_FILES['CKCSTORESPRESENTATION_IMG_PATH']['name'];

                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);

                $file_base_name = preg_replace('/[^\w\-]/u', '-', $file_base_name); // Remplace les caractères non alphanumériques
                $file_base_name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $file_base_name); // Convertit les caractères accentués en non accentués

                $file_name = $file_base_name . '.' . $file_extension;

                $checkIfTheSameName = Db::getInstance()->getValue('SELECT `img_path` FROM `' . _DB_PREFIX_ . 'ckcstorespresentation`
                WHERE id_ckcstorespresentation = '.$CKCSTORESPRESENTATION_ID.' AND img_path LIKE "'.$file_name.'"');
                while ($checkIfTheSameName) {
                    $randomSuffix = rand(1000, 9999);
                    $newFileName = $file_base_name . '-' . $randomSuffix . '.' . $file_extension;

                    // Revérifier avec le nouveau nom de fichier
                    $checkIfTheSameName = Db::getInstance()->getValue(
                        'SELECT `img_path` FROM `' . _DB_PREFIX_ . 'ckcstorespresentation`
                        WHERE id_ckcstorespresentation = '.$CKCSTORESPRESENTATION_ID.' AND img_path LIKE "'.$newFileName.'"'
                    );
                }

                $file_name = isset($newFileName) ? $newFileName : $file_name;


                if($file_name) {
                    if (is_uploaded_file($_FILES['CKCSTORESPRESENTATION_IMG_PATH']['tmp_name'])) {
                        // Vérifier si le dossier 'uploads' existe, sinon le créer
                        $uploadsDir =  __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
                        if (!file_exists($uploadsDir)) {
                            mkdir($uploadsDir, 0755, true); //
                        }
                        $moveResult = move_uploaded_file($_FILES['CKCSTORESPRESENTATION_IMG_PATH']['tmp_name'], $uploadsDir . DIRECTORY_SEPARATOR . $file_name);

                        if ($moveResult) {
                            $upload = true;
                        } else {
                            throw new Exception('Le document n\'a pas pu être enregistré.');
                        }
                    } else {
                        throw new FileNotFoundException('Error while file upload');
                    }
                }
                $updateStoreInfo = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckcstorespresentation` 
                SET `departement` = \''.$CKCSTORESPRESENTATION_DEPARTEMENT.'\',
                `adresse` = \''.$CKCSTORESPRESENTATION_ADRESSE.'\',
                `ville` = \''.$CKCSTORESPRESENTATION_VILLE.'\',
                `code_postal` = \''.$CKCSTORESPRESENTATION_CODE_POSTAL.'\',
                `email` = \''.$CKCSTORESPRESENTATION_EMAIL.'\',
                `telephone` = \''.$CKCSTORESPRESENTATION_TELEPHONE.'\',
                `horaires_jours` = \''.$CKCSTORESPRESENTATION_HORAIRES_JOURS.'\',
                `horaires_heures` = \''.$CKCSTORESPRESENTATION_HORAIRES_HEURES.'\',
                `horaires_samedi` = \''.$CKCSTORESPRESENTATION_HORAIRES_SAMEDI.'\',
                `horaires_info_suppl` = \''.$CKCSTORESPRESENTATION_HORAIRES_INFO_SUPPL.'\',
                `img_path` = \''.$file_name.'\',
                `latitude` = \''.$CKCSTORESPRESENTATION_HORAIRES_LATITUDE.'\',
                `longitude` = \''.$CKCSTORESPRESENTATION_HORAIRES_LONGITUDE.'\',
                `id_multiboutique` = '.$CKCSTORESPRESENTATION_MULTIBOUTIQUE_ID.',
                `id_cms_detail` = '.$CKCSTORESPRESENTATION_PAGE_DETAIL.' 
                WHERE id_ckcstorespresentation = '.$CKCSTORESPRESENTATION_ID);
            } else {
                $updateStoreInfo = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckcstorespresentation` 
                SET `departement` = \''.$CKCSTORESPRESENTATION_DEPARTEMENT.'\',
                `adresse` = \''.$CKCSTORESPRESENTATION_ADRESSE.'\',
                `ville` = \''.$CKCSTORESPRESENTATION_VILLE.'\',
                `code_postal` = \''.$CKCSTORESPRESENTATION_CODE_POSTAL.'\',
                `email` = \''.$CKCSTORESPRESENTATION_EMAIL.'\',
                `telephone` = \''.$CKCSTORESPRESENTATION_TELEPHONE.'\',
                `horaires_jours` = \''.$CKCSTORESPRESENTATION_HORAIRES_JOURS.'\',
                `horaires_heures` = \''.$CKCSTORESPRESENTATION_HORAIRES_HEURES.'\',
                `horaires_samedi` = \''.$CKCSTORESPRESENTATION_HORAIRES_SAMEDI.'\',
                `horaires_info_suppl` = \''.$CKCSTORESPRESENTATION_HORAIRES_INFO_SUPPL.'\',
                `latitude` = \''.$CKCSTORESPRESENTATION_HORAIRES_LATITUDE.'\',
                `longitude` = \''.$CKCSTORESPRESENTATION_HORAIRES_LONGITUDE.'\',
                `id_multiboutique` = '.$CKCSTORESPRESENTATION_MULTIBOUTIQUE_ID.',
                `id_cms_detail` = '.$CKCSTORESPRESENTATION_PAGE_DETAIL.' 
                WHERE id_ckcstorespresentation = '.$CKCSTORESPRESENTATION_ID);
            }

        } else {

            $file_name = $_FILES['CKCSTORESPRESENTATION_IMG_PATH']['name'];

            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);

            $file_base_name = preg_replace('/[^\w\-]/u', '-', $file_base_name); // Remplace les caractères non alphanumériques
            $file_base_name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $file_base_name); // Convertit les caractères accentués en non accentués

            $file_name = $file_base_name . '.' . $file_extension;

            if($file_name) {
                if (is_uploaded_file($_FILES['CKCSTORESPRESENTATION_IMG_PATH']['tmp_name'])) {
                    // Vérifier si le dossier 'uploads' existe, sinon le créer
                    $uploadsDir =  __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
                    if (!file_exists($uploadsDir)) {
                        mkdir($uploadsDir, 0755, true); //
                    }
                    $moveResult = move_uploaded_file($_FILES['CKCSTORESPRESENTATION_IMG_PATH']['tmp_name'], $uploadsDir . DIRECTORY_SEPARATOR . $file_name);

                    if ($moveResult) {
                        $upload = true;
                    } else {
                        throw new Exception('Le document n\'a pas pu être enregistré.');
                    }
                } else {
                    throw new FileNotFoundException('Error while file upload');
                }
            }

            $createStoreInfo = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckcstorespresentation` 
            (`id_ckcstorespresentation`, `departement`, `adresse`,`ville`, `code_postal`,`email`, `telephone`, `horaires_jours`, `horaires_heures`, `horaires_samedi`, `horaires_info_suppl`, `img_path`, `latitude`, `longitude`, `id_multiboutique`,`id_cms_detail`, `date_add`)
            VALUES (NULL, \''.$CKCSTORESPRESENTATION_DEPARTEMENT.'\', \''.$CKCSTORESPRESENTATION_ADRESSE.'\',\''.$CKCSTORESPRESENTATION_VILLE.'\',
            \''.$CKCSTORESPRESENTATION_CODE_POSTAL.'\', \''.$CKCSTORESPRESENTATION_EMAIL.'\',
            \''.$CKCSTORESPRESENTATION_TELEPHONE.'\', \''.$CKCSTORESPRESENTATION_HORAIRES_JOURS.'\', \''.$CKCSTORESPRESENTATION_HORAIRES_HEURES.'\', \''.$CKCSTORESPRESENTATION_HORAIRES_SAMEDI.'\',
            \''.$CKCSTORESPRESENTATION_HORAIRES_INFO_SUPPL.'\', \''.$file_name.'\', \''.$CKCSTORESPRESENTATION_HORAIRES_LATITUDE.'\', \''.$CKCSTORESPRESENTATION_HORAIRES_LONGITUDE.'\',
            '.$CKCSTORESPRESENTATION_MULTIBOUTIQUE_ID.','.$CKCSTORESPRESENTATION_PAGE_DETAIL.' , CURRENT_TIMESTAMP())');
        }
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

    public function hookDisplayHeader()
    {
        if ($this->getCmsStoreForPage()) {
            return;
        }
        $idCms = ($this->context->controller instanceof CmsController && isset($this->context->controller->cms->id))
            ? (int) $this->context->controller->cms->id : 0;
        if ($idCms === ((int) Configuration::get('CKCSTORESPRESENTATION_CMS_LISTING_ID') ?: 11)
            || $this->context->controller instanceof CkcstorespresentationNosMagasinsModuleFrontController) {
            $this->context->controller->addJS($this->_path . 'views/js/front.js');
            $this->context->controller->addCSS($this->_path . 'views/css/front.css');
            $this->context->controller->addCSS($this->_path . 'views/css/store-rating.css');
        }
    }

    /** Creative Elements : widget Module → hook displayCkcStoreReviews */
    public function hookDisplayCkcStoreReviews(array $params)
    {
        $store = $this->getCmsStoreForPage();
        if (!$store) {
            return '';
        }
        $reviews = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'presentation_reviews`
            WHERE `id_ckcstorespresentation` = ' . (int) $store['id_ckcstorespresentation'] . '
            AND `rating` = 5 ORDER BY `review_date` DESC'
        ) ?: [];
        if (!$reviews && empty($store['google_rating'])) {
            return '';
        }
        $placeId = trim((string) ($store['place_id'] ?? ''));
        $mapsUrl = '';
        if ($placeId !== '') {
            $query = trim(($store['departement'] ?? '') . ' ' . ($store['adresse'] ?? '') . ' '
                . ($store['code_postal'] ?? '') . ' ' . ($store['ville'] ?? ''));
            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query !== '' ? $query : $placeId)
                . '&query_place_id=' . rawurlencode($placeId);
        }
        $this->context->controller->addCSS($this->_path . 'views/css/store-reviews.css');
        $this->context->smarty->assign(['store' => $store, 'reviews' => $reviews, 'maps_url' => $mapsUrl]);

        return $this->fetch('module:ckcstorespresentation/views/templates/hook/store-reviews.tpl');
    }

    protected function registerStoreReviewsHook(): bool
    {
        if (!Hook::getIdByName('displayCkcStoreReviews')) {
            $hook = new Hook();
            $hook->name = 'displayCkcStoreReviews';
            $hook->title = 'Ckc Stores - Avis Google';
            $hook->position = true;
            if (!$hook->add()) {
                return false;
            }
        }
        if (!$this->isRegisteredInHook('displayCkcStoreReviews') && !$this->registerHook('displayCkcStoreReviews')) {
            return false;
        }
        if ($this->isRegisteredInHook('displayCMSDisputeInformation')) {
            $this->unregisterHook('displayCMSDisputeInformation');
        }

        return true;
    }
}
