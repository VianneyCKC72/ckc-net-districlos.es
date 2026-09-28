<?php
/**
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
class ResponsiveGallery extends Module
{

    const GALLERIES = 'galleries';
    const ITEMS = 'items';

    public $controllers = array(
        'galleries',
        'galleryitems',
    );

    public static function includeDependencies()
    {
        require_once 'classes/ResponsiveGalleryGallery.php';
        require_once 'classes/ResponsiveGalleryItem.php';
    }

    public function __construct()
    {
        $this->name = 'responsivegallery';
        $this->tab = 'front_office_features';
        $this->version = '4.2.16';
        $this->author = 'Studio Kiwik';
        $this->module_key = 'b94c904928f4485af02385d609251189';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Photo Gallery Responsive');
        $this->description = $this->l('Display your photos in this fully responsive grid gallery using infinite
            scorlling on it.');
        $this->secure_key = Tools::encrypt($this->name);
        $this->ps_versions_compliancy = array('min' => '1.6.1', 'max' => _PS_VERSION_ . '.99');
        $this->number_item_per_page = Configuration::get('RG_NB_ITEM_PER_PAGE');

        self::includeDependencies();
    }

    public function install()
    {
        if (self::isVersion17()) {
            $this->installModuleTab17();
        } else {
            $this->installModuleTab();
        }

        $result = true;
        return $result
        && parent::install()
        && $this->initialiseData()
        && $this->registerHook('moduleRoutes')
        && $this->registerHook('displayHome')
        && $this->registerHook('displayHeader')
        && $this->registerHook('gSitemapAppendUrls')
        && ResponsiveGalleryItem::installTables()
        && ResponsiveGalleryGallery::installTables();
    }

    public function uninstall()
    {
        $result = true;
        return $result && parent::uninstall()
        && $this->uninstallModuleTab()
        && $this->unsetDatas()
        && ResponsiveGalleryItem::uninstallTable()
        && ResponsiveGalleryGallery::uninstallTables()
        && $this->deleteAllImages();
    }

    public function deleteAllImages()
    {
        $upload_path_galleries = _PS_MODULE_DIR_ . implode(
            DIRECTORY_SEPARATOR,
            array('responsivegallery', 'views', 'img', 'galleries')
        );
        $upload_path_items = _PS_MODULE_DIR_ . implode(
            DIRECTORY_SEPARATOR,
            array('responsivegallery', 'views', 'img', 'items')
        );

        $files_galleries = scandir($upload_path_galleries);
        $files_items = scandir($upload_path_items);
        foreach ($files_galleries as $file) {
            if ($file != '.' && $file != '..' && $file != 'index.php') {
                if (file_exists($upload_path_galleries . DIRECTORY_SEPARATOR . $file) &&
                    !unlink($upload_path_galleries . DIRECTORY_SEPARATOR . $file)) {
                    return false;
                }
            }
        }
        foreach ($files_items as $file) {
            if ($file != '.' && $file != '..' && $file != 'index.php') {
                if (file_exists($upload_path_items . DIRECTORY_SEPARATOR . $file) &&
                    !unlink($upload_path_items . DIRECTORY_SEPARATOR . $file)) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function isVersion17()
    {
        return Tools::version_compare(_PS_VERSION_, '1.7', '>');
    }

    public function installModuleTab17()
    {
        $tab = new Tab();
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $tab->name[$id_lang] = $this->l('Galleries photo');
        $tab->class_name = 'AdminResponsiveGallery';
        $tab->id_parent = Tab::getIdFromClassName('IMPROVE');
        $tab->module = $this->name;
        $tab->icon = 'image';
        $tab->add();

        $tab = new Tab();
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $tab->name[$id_lang] = $this->l('Manage Gallery');
        $tab->class_name = 'AdminResponsiveGalleryGallery';
        $tab->id_parent = Tab::getIdFromClassName('AdminResponsiveGallery');
        $tab->module = $this->name;
        $tab->add();

        $tab = new Tab();
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $tab->name[$id_lang] = $this->l('Manage Image Gallery');
        $tab->class_name = 'AdminResponsiveGalleryItem';
        $tab->id_parent = Tab::getIdFromClassName('AdminResponsiveGallery');
        $tab->module = $this->name;
        $tab->add();

        $tab = new Tab();
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $tab->name[$id_lang] = $this->l('Manage Gallery Multi upload');
        $tab->class_name = 'AdminResponsiveGalleryItemMultiUpload';
        $tab->id_parent = Tab::getIdFromClassName('AdminResponsiveGallery');
        $tab->module = $this->name;
        $tab->active = 0;
        $tab->add();

        return true;
    }

    public function installModuleTab()
    {
        $tab = new Tab();
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $tab->name[$id_lang] = $this->l('Manage Gallery');
        $tab->class_name = 'AdminResponsiveGalleryGallery';
        $tab->id_parent = Tab::getIdFromClassName('AdminParentModules');
        $tab->module = $this->name;
        $tab->add();

        $tab = new Tab();
        $tab->name[$id_lang] = $this->l('Manage Image Gallery');
        $tab->class_name = 'AdminResponsiveGalleryItem';
        $tab->id_parent = Tab::getIdFromClassName('AdminParentModules');
        $tab->module = $this->name;
        $tab->add();

        $tab = new Tab();
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $tab->name[$id_lang] = $this->l('Manage Gallery Multi upload');
        $tab->class_name = 'AdminResponsiveGalleryItemMultiUpload';
        $tab->id_parent = Tab::getIdFromClassName('AdminResponsiveGalleryItem');
        $tab->module = $this->name;
        $tab->add();

        return true;
    }

    public function uninstallModuleTab()
    {
        $tab = new Tab((int) Tab::getIdFromClassName('AdminResponsiveGalleryItemMultiUpload'));
        $tab->delete();

        $tab = new Tab((int) Tab::getIdFromClassName('AdminResponsiveGalleryGallery'));
        $tab->delete();

        $tab = new Tab((int) Tab::getIdFromClassName('AdminResponsiveGalleryItem'));
        $tab->delete();

        if (self::isVersion17()) {
            $tab = new Tab((int) Tab::getIdFromClassName('AdminResponsiveGallery'));
            $tab->delete();
        }

        return true;
    }

    public function initialiseData()
    {
        $tab_title = array();
        $tab_description = array();
        $tab_title_page = array();
        $tab_description_page = array();
        foreach (Language::getLanguages() as $language) {
            $id_lang = $language['id_lang'];
            $tab_title[$id_lang] = $this->l('Title galleries home');
            $tab_description[$id_lang] = $this->l('Description galleries home');
            $tab_title_page[$id_lang] = $this->l('Title galleries page');
            $tab_description_page[$id_lang] = $this->l('Description galleries page');
        }
        Configuration::updateValue('RG_TITLE_HOME', $tab_title);
        Configuration::updateValue('RG_DESCRIPTION_HOME', $tab_description);
        Configuration::updateValue('RG_TITLE_PAGE', $tab_title_page);
        Configuration::updateValue('RG_DESCRIPTION_PAGE', $tab_description_page);
        Configuration::updateValue('RG_ACTIVE_ON_HOMEPAGE', json_encode(false));
        Configuration::updateValue('RG_ORDER_ACS_IMAGE', json_encode(true));
        Configuration::updateValue('RG_GALLERY_SELECT', 1);

        return true;
    }

    public function unsetDatas()
    {
        return Configuration::deleteByName('RG_TITLE_HOME')
        && Configuration::deleteByName('RG_DESCRIPTION_HOME')
        && Configuration::deleteByName('RG_TITLE_PAGE')
        && Configuration::deleteByName('RG_DESCRIPTION_PAGE')
        && Configuration::deleteByName('RG_ACTIVE_ON_HOMEPAGE')
        && Configuration::deleteByName('RG_GALLERY_SELECT');
    }

    public function hookgSitemapAppendUrls($params)
    {
        $array_img = array();
        $id_lang = $params['lang']['id_lang'];
        $path_img = Context::getContext()->shop->getBaseURL(Tools::usingSecureMode());
        $path_img .= 'modules/responsivegallery/views/img/';
        $path_img_gallery = $path_img.'galleries/';
        $path_img_items = $path_img.'items/';

        $all_galleries = ResponsiveGalleryGallery::getGalleries();
        foreach ($all_galleries as $gallery) {
            $img_name_gallery = $path_img_gallery.$gallery['id_responsive_gallery_gallery'].'-'.$gallery['image'];
            $array_img[]= array(
                'page' => 'galleries',
                'link' => Context::getContext()->link->getModuleLink($this->name, 'galleries'),
                'image' => array(
                    'title_img' => $gallery['image'],
                    'caption' => $gallery['alt'],
                    'link' => $img_name_gallery
                ),
            );

            $o_gallery = new ResponsiveGalleryGallery($gallery['id_responsive_gallery_gallery']);
            $all_items = $o_gallery->getGalleryItemRange(0, 1000, true, $id_lang);
            foreach ($all_items as $item) {
                $img_name_item = $path_img_items.$item['id_responsive_gallery_item'].'-'.$item['image'];
                $array_img[]= array(
                    'page' => 'galleryitems',
                    'link' => $this->getGalleryLink($gallery['id_responsive_gallery_gallery'], true),
                    'image' => array(
                        'title_img' => $item['image'],
                        'caption' => $item['alt'],
                        'link' => $img_name_item
                    ),
                );
            }
        }
        return $array_img;
    }

    public function hookDisplayHome($params)
    {
        if (!(int) Configuration::get('RG_ACTIVE_ON_HOMEPAGE')) {
            return null;
        }

        $id_gallery = (int) Configuration::get('RG_GALLERY_SELECT');
        $id_lang = Context::getContext()->language->id;
        $id_shop = Context::getContext()->shop->id;
        $o_responsive_gallery_gallery = new ResponsiveGalleryGallery($id_gallery, $id_lang, $id_shop);
        if ($o_responsive_gallery_gallery->active) {
            $o_responsive_gallery_gallery->assignMultipleConstant($this->context, true);
            $gallery_items = $o_responsive_gallery_gallery->getGalleryPage(1);
            $path_module = $this->getPathUri();
            $path_image = $path_module . 'views/img/items/';
            foreach ($gallery_items as $key => $item) {
                $gallery_items[$key]['path_image'] =
                $path_image . $item['id_responsive_gallery_item'] . '-' . $item['image'];
            }
            $this->context->smarty->assign('gallery_items', $gallery_items);
            if (self::isVersion17()) {
                return $this->display(__FILE__, 'views/templates/hook/hookDisplayHome_1_7.tpl');
            } else {
                return $this->display(__FILE__, 'views/templates/hook/hookDisplayHome_1_6.tpl');
            }
        } else {
            return null;
        }
    }

    public function hookDisplayHeader($params)
    {
        if (Context::getContext()->controller instanceof IndexController
            && Configuration::get('RG_ACTIVE_ON_HOMEPAGE')) {
            $this->context->controller->addjqueryPlugin('fancybox');
            $id_gallery = (int) Configuration::get('RG_GALLERY_SELECT');
            $id_lang = Context::getContext()->language->id;
            $id_shop = Context::getContext()->shop->id;
            $o_responsive_gallery_gallery = new ResponsiveGalleryGallery($id_gallery, $id_lang, $id_shop);
            if ($o_responsive_gallery_gallery->active) {
                $css_path = $this->_path . 'views/css/responsivegallerygallery.css';
                $js_path = $this->_path . 'views/js/responsivegalleryitem.js';
                $this->context->controller->addJS($js_path);
                $this->context->controller->addCSS($css_path);
            }
        }
    }

    public function updateSeoUrl()
    {
        $id_shop = Context::getContext()->shop->id;
        $id_lang = Context::getContext()->language->id;
        //gallery
        $id_meta = Meta::getMetaByPage('module-' . $this->name . '-galleries', $id_lang)['id_meta'];
        $o_meta_gallery = new Meta($id_meta);
        ///item
        $id_meta = Meta::getMetaByPage('module-' . $this->name . '-galleryitems', $id_lang)['id_meta'];
        $o_meta_item = new Meta($id_meta, null, $id_shop);

        $errors = array();
        foreach (Language::getLanguages() as $language) {
            $id_lang = $language['id_lang'];
            $o_meta_gallery->url_rewrite[$id_lang] = Tools::str2url(
                Tools::getValue('SEO_' . self::GALLERIES . '_' . $id_lang)
            );
            $o_meta_item->url_rewrite[$id_lang] = Tools::str2url(
                Tools::getValue('SEO_' . self::ITEMS . '_' . $id_lang)
            );
            if ($o_meta_item->url_rewrite[$id_lang] == $o_meta_gallery->url_rewrite[$id_lang] &&
                !empty($o_meta_item->url_rewrite[$id_lang])) {
                $errors[] = sprintf(
                    $this->l('can\'t set the same rewrite gallery and item => lang : %s'),
                    $language['name']
                );
            }
        }
        if (count($errors) == 0) {
            $o_meta_gallery->save();
            $o_meta_item->save();
            return false;
        } else {
            return $this->displayError($errors);
        }
    }

    public function getContent()
    {
        $errors = false;
        if (Tools::isSubmit('submitFormChamps')) {
            $errors = $this->updateSeoUrl();
            $tab_title_home = array();
            $tab_description_home = array();
            $tab_title_page = array();
            $tab_description_page = array();
            foreach (Language::getLanguages() as $language) {
                $id_lang = $language['id_lang'];
                $tab_title_home[$id_lang] = Tools::getValue(
                    'RG_TITLE_HOME_' . $id_lang,
                    Configuration::get('RG_TITLE_HOME', $id_lang)
                );
                $tab_description_home[$id_lang] = Tools::getValue(
                    'RG_DESCRIPTION_HOME_' . $id_lang,
                    Configuration::get('RG_DESCRIPTION_HOME', $id_lang)
                );
                $tab_title_page[$id_lang] = Tools::getValue(
                    'RG_TITLE_PAGE_' . $id_lang,
                    Configuration::get('RG_TITLE_PAGE', $id_lang)
                );
                $tab_description_page[$id_lang] = Tools::getValue(
                    'RG_DESCRIPTION_PAGE_' . $id_lang,
                    Configuration::get('RG_DESCRIPTION_PAGE', $id_lang)
                );
            }
            Configuration::updateValue('RG_TITLE_HOME', $tab_title_home);
            Configuration::updateValue('RG_DESCRIPTION_HOME', $tab_description_home, true);
            Configuration::updateValue('RG_TITLE_PAGE', $tab_title_page);
            Configuration::updateValue('RG_DESCRIPTION_PAGE', $tab_description_page, true);
            Configuration::updateValue(
                'RG_ACTIVE_ON_HOMEPAGE',
                Tools::getValue('RG_ACTIVE_ON_HOMEPAGE'),
                Configuration::get('RG_ACTIVE_ON_HOMEPAGE')
            );
            Configuration::updateValue(
                'RG_ORDER_ACS_IMAGE',
                Tools::getValue('RG_ORDER_ACS_IMAGE'),
                Configuration::get('RG_ORDER_ACS_IMAGE')
            );
            Configuration::updateValue(
                'RG_GALLERY_SELECT',
                Tools::getValue('RG_GALLERY_SELECT'),
                Configuration::get('RG_GALLERY_SELECT')
            );
        }
        return $errors . $this->renderForm() . $this->getFooter();
    }

    /**
     * Récupération du pied de page
     *
     * @return string footer
     */
    public function getFooter()
    {
        return $this->getTranslatedAdminTemplate('footer');
    }

    public function getTranslatedAdminTemplate($template, $default_lang_iso_code = 'en')
    {
        $template = Tools::strtolower($template);
        $bootstrap_ext = isset($this->bootstrap) && $this->bootstrap ? '.bootstrap' : '.no_bootstrap';
        $iso_codes = array_filter(
            array(
                Tools::strtolower(Context::getContext()->language->iso_code),
                Tools::strtolower($default_lang_iso_code),
            )
        );

        foreach ($iso_codes as $lang_iso_code) {
            $path = '/views/templates/admin/' . $template . '_' . $lang_iso_code . $bootstrap_ext . '.tpl';

            if (file_exists(dirname(__FILE__) . $path)) {
                $this->smarty->assign(array(
                    'module' => $this,
                    'module_path' => Context::getContext()->shop->getBaseUrL(true) . '/modules/' . $this->name,
                ));

                return $this->display(__FILE__, $path);
            }
        }
        return '';
    }

    public function getSeoUrl($name)
    {
        $id_shop = Context::getContext()->shop->id;
        $tab_seo_url = array();
        foreach (Language::getLanguages() as $language) {
            if ($name == self::GALLERIES) {
                $name_controller = 'galleries';
            } elseif ($name == self::ITEMS) {
                $name_controller = 'galleryitems';
            }
            $sql = 'SELECT ml.url_rewrite
                        FROM ' . _DB_PREFIX_ . 'meta m
                        LEFT JOIN ' . _DB_PREFIX_ . 'meta_lang ml
                        ON m.id_meta = ml.id_meta
                        WHERE ml.id_shop = ' . (int)$id_shop . '
                        AND ml.id_lang = ' . (int)$language['id_lang'] . '
                        AND m.page = "module-' . pSQL($this->name) . '-' . pSQL($name_controller) . '"';
            $tab_seo_url[$name][$language['id_lang']] = Db::getInstance()->getValue($sql);
        }
        return $tab_seo_url;
    }

    public function renderForm()
    {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $galleries = ResponsiveGalleryGallery::getGalleries(true);

        if (!$galleries) {
            $galleries = array(
                array('id_responsive_gallery_gallery' => 0, 'title' => $this->l('No gallery create.')),
            );
        }

        $id_lang = Context::getContext()->language->id;
        $seo_url_form_galleries = '';
        $this->context->smarty->assign(array(
            'id_lang_context' => $id_lang,
            'languages' => Language::getLanguages(),
            'type_name' => self::GALLERIES,
            'seo_url' => $this->getSeoUrl(self::GALLERIES),
        ));
        $seo_url_form_galleries .= $this->display(__FILE__, 'views/templates/admin/formseourl.tpl');

        $seo_url_form_items = '';
        $this->context->smarty->assign(array(
            'id_lang_context' => $id_lang,
            'languages' => Language::getLanguages(),
            'type_name' => self::ITEMS,
            'seo_url' => $this->getSeoUrl(self::ITEMS),
        ));
        $seo_url_form_items .= $this->display(__FILE__, 'views/templates/admin/formseourl.tpl');

        $fields_form = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->l('Parameters'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                'Title_home' => array(
                    'type' => 'html',
                    'html_content' => '<h1>'.$this->l('Home page').'</h1>',
                    'name' => 'home_page',
                ),
                'RG_ACTIVE_ON_HOMEPAGE' => array(
                    'type' => 'switch',
                    'label' => $this->l('Display a gallery on homepage'),
                    'name' => 'RG_ACTIVE_ON_HOMEPAGE',
                    'is_bool' => true,
                    'choices' => array(
                        'yes',
                        'no',
                    ),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ),
                    ),
                ),
                'RG_TITLE_HOME' => array(
                    'label' => $this->l('Title of gallery for home.'),
                    'type' => 'text',
                    'lang' => true,
                    'name' => 'RG_TITLE_HOME',
                ),
                'RG_DESCRIPTION_HOME' => array(
                    'type' => 'textarea',
                    'label' => $this->l('Description of gallery for home.'),
                    'lang' => true,
                    'name' => 'RG_DESCRIPTION_HOME',
                    'class' => 'rte',
                    'autoload_rte' => true,
                ),
                'RG_GALLERY_SELECT' => array(
                    'type' => 'select',
                    'label' => $this->l('Displayed gallery'),
                    'name' => 'RG_GALLERY_SELECT',
                    'options' => array(
                        'query' => $galleries,
                        'id' => 'id_responsive_gallery_gallery',
                        'name' => 'title',
                    ),
                ),
                'Title_galleries' => array(
                    'type' => 'html',
                    'html_content' => '<h1>'.$this->l('Galleries page').'</h1>',
                    'name' => 'galleries_page',
                ),
                'RG_TITLE_PAGE' => array(
                    'label' => $this->l('Title of gallery for page Galleries.'),
                    'type' => 'text',
                    'lang' => true,
                    'name' => 'RG_TITLE_PAGE',
                ),
                'RG_DESCRIPTION_PAGE' => array(
                    'type' => 'textarea',
                    'label' => $this->l('Description of gallery for page Galleries'),
                    'lang' => true,
                    'name' => 'RG_DESCRIPTION_PAGE',
                    'class' => 'rte',
                    'autoload_rte' => true,
                ),
                'RG_ORDER_ACS_IMAGE' => array(
                    'type' => 'switch',
                    'label' => $this->l('Display image by order ascendant'),
                    'name' => 'RG_ORDER_ACS_IMAGE',
                    'is_bool' => true,
                    'choices' => array(
                        'yes',
                        'no',
                    ),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ),
                    ),
                ),
                'RG_SEO_URL_GALLERIES' => array(
                    'type' => 'html',
                    'html_content' => $seo_url_form_galleries,
                    'name' => 'null',
                    'label' => $this->l('Link rewrite galleries'),
                ),
                'RG_SEO_URL_ITEMS' => array(
                    'type' => 'html',
                    'html_content' => $seo_url_form_items,
                    'name' => 'null',
                    'label' => $this->l('Link rewrite items'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'name' => 'submitFormChamps',
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0),
            );
        }
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitFormChamps';

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    public function getConfigFieldsValues()
    {
        $tab_values = array();
        foreach (Language::getLanguages() as $language) {
            $id_lang = $language['id_lang'];
            $tab_values['RG_TITLE_HOME'][$id_lang] = Tools::getValue(
                'RG_TITLE_HOME_' . $id_lang,
                Configuration::get('RG_TITLE_HOME', $id_lang)
            );
            $tab_values['RG_DESCRIPTION_HOME'][$id_lang] = Tools::getValue(
                'RG_DESCRIPTION_HOME_' . $id_lang,
                Configuration::get('RG_DESCRIPTION_HOME', $id_lang)
            );
            $tab_values['RG_TITLE_PAGE'][$id_lang] = Tools::getValue(
                'RG_TITLE_PAGE_' . $id_lang,
                Configuration::get('RG_TITLE_PAGE', $id_lang)
            );
            $tab_values['RG_DESCRIPTION_PAGE'][$id_lang] = Tools::getValue(
                'RG_DESCRIPTION_PAGE_' . $id_lang,
                Configuration::get('RG_DESCRIPTION_PAGE', $id_lang)
            );
        }
        $tab_values['RG_ACTIVE_ON_HOMEPAGE'] = Tools::getValue(
            'RG_ACTIVE_ON_HOMEPAGE',
            json_decode(Configuration::get('RG_ACTIVE_ON_HOMEPAGE'))
        );
        $tab_values['RG_ORDER_ACS_IMAGE'] = Tools::getValue(
            'RG_ORDER_ACS_IMAGE',
            json_decode(Configuration::get('RG_ORDER_ACS_IMAGE'))
        );
        $tab_values['RG_GALLERY_SELECT'] = Tools::getValue(
            'RG_GALLERY_SELECT',
            Configuration::get('RG_GALLERY_SELECT')
        );

        return $tab_values;
    }

    public function deleteLogoAndThumb($id, $directory)
    {
        if ($directory == self::GALLERIES) {
            $o_responsive_gallery = new ResponsiveGalleryGallery($id);
            $name_image = $o_responsive_gallery->image;
        } elseif ($directory == self::ITEMS) {
            $o_responsive_gallery = new ResponsiveGalleryItem($id);
            $name_image = $o_responsive_gallery->image;
        }
        if ($name_image) {
            $path_image = _PS_MODULE_DIR_ . implode(
                DIRECTORY_SEPARATOR,
                array('responsivegallery', 'views', 'img', $directory, $id . '-' . $name_image)
            );

            $path_thumb = _PS_MODULE_DIR_ . implode(
                DIRECTORY_SEPARATOR,
                array('responsivegallery', 'views', 'img', $directory, 'thumb-' . $id . '-' . $name_image)
            );

            if (file_exists($path_image) && !unlink($path_image)) {
                return false;
            }
            if (file_exists($path_thumb) && !unlink($path_thumb)) {
                return false;
            }
            $o_responsive_gallery->image = '';
            $o_responsive_gallery->save();
        }
        return true;
    }

    public function deleteImageStartById($id, $upload_path, $thumb)
    {
        if ($thumb) {
            $regex = $upload_path . 'thumb-' . $id . '-*';
        } else {
            $regex = $upload_path . $id . "-*";
        }
        $tab_file_remove = glob($regex);
        foreach ($tab_file_remove as $file) {
            if (file_exists($file) && !unlink($file)) {
                return false;
            }
        }
    }

    public function postImageResponsiveGallery($file, $id, $directory)
    {
        $upload_path = _PS_MODULE_DIR_ . implode(
            DIRECTORY_SEPARATOR,
            array('responsivegallery', 'views', 'img', $directory)
        ) . DIRECTORY_SEPARATOR;

        $this->deleteImageStartById($id, $upload_path, false);
        $this->deleteImageStartById($id, $upload_path, true);
        $extension = Tools::substr($file['name'], strrpos($file['name'], '.') + 1);
        $filename = str_replace('.' . $extension, '', $file['name']);

        if (!file_exists($upload_path . $id . '-' . $filename . '.' . $extension)) {
            ImageManager::resize(
                $file['tmp_name'],
                $upload_path . $id . '-' . $filename . '.' . $extension,
                null,
                null,
                $extension
            );

            ImageManager::resize(
                $upload_path . $id . '-' . $filename . '.' . $extension,
                $upload_path . 'thumb-' . $id . '-' . $filename . '.' . $extension,
                200,
                150,
                $extension
            );
            return true;
        }
        return false;
    }

    public function getGalleryLink($id_gallery, $full = false)
    {
        $id_lang = $this->context->cookie->id_lang;
        $id_shop = Context::getContext()->shop->id;
        $o_responsive_gallery_gallery = new ResponsiveGalleryGallery($id_gallery, $id_lang, $id_shop);
        $parameters = array(
            'id_gallery' => $id_gallery,
            'rewrite' => Tools::str2url($o_responsive_gallery_gallery->title),
        );

        $link = $this->getLangLink($id_lang, null, $id_shop) . Dispatcher::getInstance()->createUrl(
            'responsivegallery_rules',
            $id_lang,
            $parameters
        );

        return $full ? $this->context->link->getBaseLink() . $link : '/' . $link;
    }

    public function getLangLink($id_lang = null, Context $context = null, $id_shop = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        if (!in_array($id_shop, array($context->shop->id, null)) ||
            !Language::isMultiLanguageActivated($id_shop) ||
            !(int) Configuration::get('PS_REWRITING_SETTINGS', null, null, $id_shop)) {
            return '';
        }
        if (!$id_lang) {
            $id_lang = $context->language->id;
        }

        return Language::getIsoById($id_lang) . '/';
    }

    public function hookModuleRoutes($params)
    {
        $id_lang = Context::getContext()->cookie->id_lang;
        $rewrite = Db::getInstance()->getValue(
            'SELECT url_rewrite
                    FROM ' . _DB_PREFIX_ . 'meta m
                    LEFT JOIN ' . _DB_PREFIX_ . 'meta_lang ml ON m.id_meta=ml.id_meta
                    WHERE id_lang=' . (int)$id_lang . ' AND page="module-responsivegallery-galleryitems"'
        );

        if (!$rewrite) {
            $rewrite = 'gallerie';
        }

        return array('responsivegallery_rules' => array(
            'controller' => 'galleryitems',
            'rule' => $rewrite . '/{id_gallery}/{rewrite}',
            'keywords' => array(
                'id_gallery' => array('regexp' => '[0-9]+', 'param' => 'id_gallery'),
                'rewrite' => array('regexp' => '[_a-zA-Z0-9\pL\pS-]*'),
            ),
            'params' => array(
                'module' => 'responsivegallery',
                'fc' => 'module',
            ),
        ));
    }
}
