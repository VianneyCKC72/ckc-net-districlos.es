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

class AdminResponsiveGalleryItemController extends ModuleAdminController
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

        $this->_select = 'a.image as see_image';
        $this->_join = 'LEFT JOIN ' . _DB_PREFIX_ . 'responsive_gallery_gallery c
        ON a.id_responsive_gallery_gallery = c.id_responsive_gallery_gallery ';
        $this->_join .= 'LEFT JOIN ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop d
        ON d.id_responsive_gallery_gallery = c.id_responsive_gallery_gallery ';
        $this->_where = 'AND d.id_shop = ' . Context::getContext()->shop->id;
        $id_responsive_gallery_gallery = Tools::getValue('id_responsive_gallery_gallery');
        if ($id_responsive_gallery_gallery) {
            $this->_where .= ' AND a.id_responsive_gallery_gallery = ' . $id_responsive_gallery_gallery;
        }

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ),
        );

        $all_title_galleries = ResponsiveGalleryGallery::getGalleries();
        self::$name_galleries = array_column($all_title_galleries, 'title', 'id_responsive_gallery_gallery');
        if (!isset(self::$name_galleries) || empty(self::$name_galleries)) {
            Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminResponsiveGalleryGallery'));
        }

        $this->fields_list = array(
            'id_responsive_gallery_item' => array(
                'title' => $this->l('Id'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'search' => false,
            ),
            'id_responsive_gallery_gallery' => array(
                'title' => $this->l('Title gallery'),
                'type' => 'select',
                'align' => 'center',
                'class' => 'fixed-width-s',
                'callback' => 'getTitleGallery',
                'filter_key' => 'a!id_responsive_gallery_gallery',
                'list' => self::$name_galleries,
                'orderby' => true,
            ),
            'active' => array(
                'title' => $this->l('Activate'),
                'align' => 'center',
                'active' => 'status',
                'class' => 'fixed-width-xs',
                'type' => 'bool',
            ),
            'see_image' => array(
                'title' => $this->l('See Image'),
                'align' => 'text-center',
                'callback' => 'seeImage',
                'orderby' => false,
                'search' => false,
                'remove_onclick' => true,
            ),
            'position' => array(
                'title' => $this->l('Position'),
                'align' => 'center',
                'search' => false,
                'position' => 'position',
                'class' => 'fixed-width-sm',
            ),
        );
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_item'] = array(
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->l('Add new item'),
                'icon' => 'process-icon-new',
            );

            //adding multiupload
            if ($url_preview =
                Context::getContext()->link->getAdminLink('AdminResponsiveGalleryItemMultiUpload', true)) {
                $this->page_header_toolbar_btn['new_multi_item'] = array(
                    'href' => $url_preview . '&addresponsive_gallery_item',
                    'desc' => $this->l('Multi Upload'),
                    'target' => true,
                    'icon' => 'process-icon-new',
                );
            }
        }
        parent::initPageHeaderToolbar();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $js_path = $this->module->getPathUri().'views/js/adminresponsivegalleryposition.js';
        $this->addJS($js_path);
    }

    public function ajaxProcessUpdatePositions()
    {
        $id_item_to_move = (int) Tools::getValue('id');
        $positions = Tools::getValue('responsive_gallery_item');
        $way = Tools::getValue('way');
        if (is_array($positions)) {
            foreach ($positions as $key => $value) {
                $tab_pos = explode('_', $value);
                if (isset($tab_pos[2]) && $tab_pos[2] == $id_item_to_move && isset($tab_pos[3])) {
                    $new_position = $key;
                    $old_position = $tab_pos[3];
                    break;
                }
            }
        }

        // Si on est dans une version qui commence les positions à 0, on décrémente la position
        //        if (version_compare(_PS_VERSION_, '1.6.0.4', '<')) {
        //            $new_position = $new_position - 1;
        //        }

        $item_gallery = new ResponsiveGalleryItem($id_item_to_move);
        if (Validate::isLoadedObject($item_gallery)) {
            if (isset($new_position) &&
                isset($old_position) &&
                $item_gallery->updatePosition($new_position, $old_position, $way)) {
                die(true);
            } else {
                die('{"hasError" : true, errors : "Cannot update gallery position"}');
            }
        } else {
            die('{"hasError" : true, "errors" : "This gallery cannot be loaded"}');
        }
    }

    public function seeImage($image_item, $tr)
    {
        $url_image = '';
        if ($image_item) {
            $url_image = _MODULE_DIR_ . implode(
                DIRECTORY_SEPARATOR,
                array(
                    'responsivegallery',
                    'views',
                    'img',
                    'items',
                    'thumb-' . $tr['id_responsive_gallery_item'] . '-' . $image_item,
                )
            );
        }
        $this->context->smarty->assign('url_image', $url_image);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . implode(
                DIRECTORY_SEPARATOR,
                array($this->module->name, 'views', 'templates', 'admin', 'seeImage.tpl')
            )
        );
    }

    public function getTitleGallery($id_gallery)
    {
        if (self::$name_galleries) {
            return self::$name_galleries[$id_gallery];
        }
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function renderForm()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        if (Validate::isLoadedObject($this->object)) {
            $this->display = 'edit';
            $id_item = Tools::getValue('id_responsive_gallery_item');
            $url_current = 'id_responsive_gallery_item=' . $id_item;
            $image_name = ResponsiveGalleryItem::getNameImageById($id_item);
            if ($image_name) {
                $thumbnail = _MODULE_DIR_ . implode(
                    DIRECTORY_SEPARATOR,
                    array(
                        'responsivegallery',
                        'views',
                        'img',
                        ResponsiveGallery::ITEMS,
                        'thumb-' . $id_item . '-' . $image_name,
                    )
                );
            }
        } else {
            $this->display = 'add';
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
                'title' => $this->l('Add or edit Item'),
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
                    'image' => (isset($thumbnail) && $thumbnail ? '<img src="' . $thumbnail . '">' : false),
                    'delete_url' => (isset($thumbnail) && $thumbnail ? self::$currentIndex .
                        "&" . $url_current . "&token=" . $this->token . '&deleteImage' : false),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Image alt'),
                    'name' => 'alt',
                    'lang' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Image Link'),
                    'name' => 'link',
                    'lang' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Legend image'),
                    'name' => 'legend',
                    'lang' => true,
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

    public function beforeAdd($object)
    {
        if (!ResponsiveGallery::isVersion17() && isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
            $object->image = $_FILES['image']['name'];
        }
        $object->setLastPosition();
    }

    public function afterAdd($object)
    {
        if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
            $this->module->postImageResponsiveGallery($_FILES['image'], $object->id, ResponsiveGallery::ITEMS);
        }
    }

    public function processUpdate()
    {
        ResponsiveGalleryItem::reOrderAllItemsInAllGalleries();
        //si changement de Gallery alors mettre à jour la position
        $position = $this->getLastPositionIfChangeGallery();
        
        //Récupère le nom de l'image avant la MAJ de l'item.
        $id_responsive_gallery_item = Tools::getValue('id_responsive_gallery_item');
        $image_name = ResponsiveGalleryItem::getNameImageById($id_responsive_gallery_item);

        parent::processUpdate();

        //Dans le cas d'un prestashop 1.7 le champ input garde le même nom que donné dans le form alors
        //quand on soumet le formulaire sans image il remplace le champs image par du vide.
        $o_item = new ResponsiveGalleryItem($id_responsive_gallery_item);

        $o_item->position = $position;
        if ($new_image_name = $_FILES['image']['name']) {
            //si il y a une nouvelle image alors changer le nom et save.
            $o_item->image = $new_image_name;
        } else {
            //si il n'y pas de nouvelle image alors récup l'ancien nom.
            $o_item->image = $image_name;
        }
        $o_item->save();
    }

    public function afterUpdate($object)
    {
        if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
            $this->module->postImageResponsiveGallery($_FILES['image'], $object->id, ResponsiveGallery::ITEMS);
        }
    }

    public function postProcess()
    {
        if (Tools::getIsset("deleteImage")) {
            $id_item = Tools::getValue('id_responsive_gallery_item');
            $this->module->deleteLogoAndThumb($id_item, ResponsiveGallery::ITEMS);
            $o_gallery = new ResponsiveGalleryItem($id_item);
            $o_gallery->image_name = '';
            $o_gallery->save();
        }
        parent::postProcess();
    }

    public function getLastPositionIfChangeGallery()
    {
        $o_item = new ResponsiveGalleryItem(Tools::getValue('id_responsive_gallery_item'));
        $old_id_gallery = $o_item->id_responsive_gallery_gallery;
        $new_id_gallery = Tools::getValue('id_responsive_gallery_gallery');
        if ($old_id_gallery != $new_id_gallery) {
            return ResponsiveGalleryItem::getLastPositionStatic($new_id_gallery);
        }
        return $o_item->position;
    }

    public function processDelete()
    {
        $id_object = $this->loadObject()->id;
        $upload_path = _PS_MODULE_DIR_ . implode(
            DIRECTORY_SEPARATOR,
            array('responsivegallery', 'views', 'img', 'items', '')
        );
        $this->module->deleteImageStartById($id_object, $upload_path, true);
        $this->module->deleteImageStartById($id_object, $upload_path, false);
        return parent::processDelete();
    }
}
