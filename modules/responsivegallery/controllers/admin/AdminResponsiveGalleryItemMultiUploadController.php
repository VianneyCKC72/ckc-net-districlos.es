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

class AdminResponsiveGalleryItemMultiUploadController extends ModuleAdminController
{
    public static $name_galleries;

    public function __construct()
    {
        parent::__construct();

        $this->context = Context::getcontext();
        $this->table = 'responsive_gallery_item';
        $this->identifier = 'id_responsive_gallery_item';
        $this->className = 'ResponsiveGalleryItem';
        $this->id_lang = $this->context->language->id;
        $this->default_form_language = $this->id_lang;
        $this->_defaultOrderBy = 'id_responsive_gallery_item';
        $this->lang = true;
        $this->path = _MODULE_DIR_ . 'responsivegallery';
        $this->position_identifier = 'id_responsive_gallery_item';
        $this->bootstrap = true;

        $all_title_galleries = ResponsiveGalleryGallery::getGalleries();
        self::$name_galleries = array_column($all_title_galleries, 'title', 'id_responsive_gallery_gallery');
        if (!isset(self::$name_galleries) || empty(self::$name_galleries)) {
            Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminResponsiveGalleryGallery'));
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        
        $js_path = $this->module->getPathUri().'views/js/adminresponsivegallerymultiupload.js';
        $css_path = $this->module->getPathUri().'views/css/adminresponsivegallery.css';
        $this->addJS($js_path);
        $this->addCSS($css_path);
    }

    public function renderForm()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        if (self::$name_galleries) {
            $all_galleries = array();
            foreach (self::$name_galleries as $id_gallery => $name_gallery) {
                $all_galleries[] = array('id_responsive_gallery_gallery' => $id_gallery, 'title' => $name_gallery);
            }
        }

        $this->fields_form = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->l('Add Items'),
                'icon' => 'icon-file',
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('On gallery'),
                    'name' => 'id_responsive_gallery_gallery',
                    'options' => array(
                        'query' => $all_galleries,
                        'id' => 'id_responsive_gallery_gallery',
                        'name' => 'title',
                    ),
                ),
                array(
                    'type' => 'file',
                    'label' => $this->l('Cover image'),
                    'name' => 'image',
                    'multiple' => 'multiple',
                    'id' => 'multiple_image',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('toCloneLabel'),
                    'name' => 'toClone',
                    'lang' => true,
                    'class' => 'toClone',
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Legend on hover'),
                    'name' => 'legend_on_hover',
                    'lang' => false,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'legend_on_hover_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'legend_on_hover_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'lang' => false,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        $this->default_form_language = $this->id_lang;

        return parent::renderForm();
    }

    public function processAdd()
    {
        if (ResponsiveGallery::isVersion17()) {
            $files_name = Tools::getValue('image');
        } else {
            $files_name = Tools::getValue('filename');
        }
        if ($files_name) {
            $tab_all_image = explode(', ', $files_name);
            foreach ($tab_all_image as $key => $image_name) {
                //récup valeur de l'image
                $file_info = array();
                $file_info['name'] = $image_name;
                $file_info['type'] = $_FILES['image']['type'][$key];
                $file_info['tmp_name'] = $_FILES['image']['tmp_name'][$key];
                $file_info['error'] = $_FILES['image']['error'][$key];
                $file_info['size'] = $_FILES['image']['size'][$key];

                $o_responsive_item = new ResponsiveGalleryItem();
                foreach (Language::getLanguages() as $language) {
                    $id_lang = $language['id_lang'];
                    $o_responsive_item->alt[$id_lang] = Tools::getValue('alt_' . $key . '_' . $id_lang);
                    $o_responsive_item->legend[$id_lang] = Tools::getValue('legend_' . $key . '_' . $id_lang);
                    $o_responsive_item->link[$id_lang] = Tools::getValue('link_' . $key . '_' . $id_lang);
                }
                $o_responsive_item->image = $image_name;
                $o_responsive_item->id_responsive_gallery_gallery = Tools::getValue('id_responsive_gallery_gallery');
                $o_responsive_item->active = Tools::getValue('active');
                $o_responsive_item->legend_on_hover = Tools::getValue('legend_on_hover');
                $o_responsive_item->setLastPosition();
                if ($o_responsive_item->save()) {
                    $this->module->postImageResponsiveGallery(
                        $file_info,
                        $o_responsive_item->id,
                        ResponsiveGallery::ITEMS
                    );
                } else {
                    //@TODO gérer les erreurs !!! en utilisant les id error de $_FILES
                    Tools::redirectAdmin(
                        Context::getContext()->link->getAdminLink(
                            'AdminResponsiveGalleryItemMultiUpload',
                            true
                        )
                        . '&addresponsive_gallery_item'
                    );
                }
            }
            Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminResponsiveGalleryItem', true));
        } else {
            //@TODO gérer les erreurs !!! => aucune image !!!
            Tools::redirectAdmin(
                Context::getContext()->link->getAdminLink(
                    'AdminResponsiveGalleryItemMultiUpload',
                    true
                )
                . '&addresponsive_gallery_item'
            );
        }
    }
}
