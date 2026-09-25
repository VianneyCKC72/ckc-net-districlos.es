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
class AdminResponsiveGalleryGalleryController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->context = Context::getcontext();
        $this->table = 'responsive_gallery_gallery';
        $this->identifier = 'id_responsive_gallery_gallery';
        $this->className = 'ResponsiveGalleryGallery';
        $this->id_lang = $this->context->language->id;
        $this->default_form_language = $this->id_lang;
        $this->_defaultOrderBy = 'id_responsive_gallery_gallery';
        $this->lang = true;
        $this->path = _MODULE_DIR_ . 'responsivegallery';
        $this->position_identifier = 'id_responsive_gallery_gallery';
        $this->bootstrap = true;
        $this->_select = 'a.id_responsive_gallery_gallery as see_items';
        $this->_select = 'a.id_responsive_gallery_gallery as see_items, c.position';
        $this->_join .= 'LEFT JOIN ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop c
        ON c.id_responsive_gallery_gallery = a.id_responsive_gallery_gallery';
        $this->_where .= ' AND c.id_shop = ' . $this->context->shop->id;

        Shop::addTableAssociation($this->table, array('type' => 'shop'));
        Shop::addTableAssociation($this->table . '_lang', array('type' => 'fk_shop'));

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ),
        );

        $this->fields_list = array(
            'id_responsive_gallery_gallery' => array(
                'title' => $this->l('Id'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'search' => false,
            ),
            'title' => array(
                'title' => $this->l('title'),
                'align' => 'center',
                'class' => 'fixed-width-lg',
                'search' => true,
            ),

            'active' => array(
                'title' => $this->l('Activate'),
                'align' => 'center',
                'active' => 'status',
                'class' => 'fixed-width-xs',
                'type' => 'bool',
            ),
            'position' => array(
                'title' => $this->l('Position'),
                'align' => 'center',
                'search' => false,
                'position' => 'position',
                'class' => 'fixed-width-sm',
                'filter_key' => 'c!position',
            ),
            'see_items' => array(
                'title' => $this->l('See Items'),
                'align' => 'text-center',
                'callback' => 'seeItemGallery',
                'orderby' => false,
                'search' => false,
                'remove_onclick' => true,
            ),
        );
    }

    public function seeItemGallery($id_gallery)
    {
        $this->context->smarty->assign('id_gallery', $id_gallery);
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . implode(
            DIRECTORY_SEPARATOR,
            array($this->module->name, 'views', 'templates', 'admin', 'buttonSeeItem.tpl')
        ));
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_gallery'] = array(
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->l('Add new gallerie'),
                'icon' => 'process-icon-new',
            );

            // adding button for preview
            if ($url_preview = Context::getContext()->link->getModuleLink('responsivegallery', 'galleries')) {
                $this->page_header_toolbar_btn['preview'] = array(
                    'href' => $url_preview,
                    'desc' => $this->l('Preview'),
                    'target' => true,
                    'class' => 'previewUrl',
                );
            }
        }
        parent::initPageHeaderToolbar();
    }

    public function renderForm()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        if (Validate::isLoadedObject($this->object)) {
            $this->display = 'edit';
            $id_gallery = Tools::getValue('id_responsive_gallery_gallery');
            $url_current = 'id_responsive_gallery_gallery=' . $id_gallery;
            $image_name = ResponsiveGalleryGallery::getNameImageById($id_gallery);
            if ($image_name) {
                $thumbnail = _MODULE_DIR_ . implode(
                    DIRECTORY_SEPARATOR,
                    array(
                        'responsivegallery',
                        'views',
                        'img',
                        ResponsiveGallery::GALLERIES,
                        'thumb-' . $id_gallery . '-' . $image_name,
                    )
                );
            }
        } else {
            $this->display = 'add';
        }

        if (isset($thumbnail) && $thumbnail) {
            $delete_url = self::$currentIndex . "&" . $url_current . "&token=" . $this->token . '&deleteImage';
        } else {
            $delete_url = false;
        }

        $this->fields_form = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->l('Add or edit gallery'),
                'icon' => 'icon-file',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Gallery name'),
                    'name' => 'title',
                    'lang' => true,
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Gallery description'),
                    'name' => 'description',
                    'lang' => true,
                    'autoload_rte' => true,
                    'class' => 'rte',
                ),
                array(
                    'type' => 'file',
                    'label' => $this->l('Cover image'),
                    'name' => 'image',
                    'image' => (isset($thumbnail) && $thumbnail ? '<img src="' . $thumbnail . '">' : false),
                    'delete_url' => $delete_url,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Image alt'),
                    'name' => 'alt',
                    'lang' => true,
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Legend on hover'),
                    'name' => 'legend_on_photo',
                    'lang' => false,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'legend_on_photo_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'legend_on_photo_off',
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
                array(
                    'type' => 'text',
                    'label' => $this->l('The fadein speed when loading is done'),
                    'name' => 'fadein_speed',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('The number of item per page'),
                    'name' => 'nb_item_per_page',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('The horizontal margin between your images'),
                    'name' => 'horizontal_margin',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('The vertical margin between your images'),
                    'name' => 'vertical_margin',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('The max width for the first screen size (e.g. smartphone).'),
                    'name' => 'breakpoint_1',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Number of item per line for the first screen size.'),
                    'name' => 'nb_item_per_line_1',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('The max width for the second screen size (e.g. tablet).'),
                    'name' => 'breakpoint_2',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Number of item per line for the second screen size.'),
                    'name' => 'nb_item_per_line_2',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('The max width for the third screen size (e.g. laptop).'),
                    'name' => 'breakpoint_3',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Number of item per line for the third screen size.'),
                    'name' => 'nb_item_per_line_3',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('The max width for the fourth screen size (e.g. desktop).'),
                    'name' => 'breakpoint_4',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Number of item per line for the fourth screen size.'),
                    'name' => 'nb_item_per_line_4',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('The max width for the fifth screen size (e.g. widescreen).'),
                    'name' => 'breakpoint_5',
                    'class' => 'option-advanced',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Number of item per line for the fifth screen size.'),
                    'name' => 'nb_item_per_line_5',
                    'class' => 'option-advanced',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association'),
                'name' => 'checkBoxShopAsso',
            );
        }

        $this->default_form_language = $this->id_lang;

        return parent::renderForm();
    }

    public function ajaxProcessUpdatePositions()
    {
        $id_gallery_to_move = (int) Tools::getValue('id');
        $positions = Tools::getValue('responsive_gallery_gallery');
        $way = Tools::getValue('way');
        if (is_array($positions)) {
            foreach ($positions as $key => $value) {
                $tab_pos = explode('_', $value);
                if (isset($tab_pos[2]) && $tab_pos[2] == $id_gallery_to_move && isset($tab_pos[3])) {
                    $new_position = $key;
                    $old_position = $tab_pos[3];
                    break;
                }
            }
        }

        $o_gallery = new ResponsiveGalleryGallery($id_gallery_to_move);
        if (Validate::isLoadedObject($o_gallery)) {
            if (isset($new_position) &&
                isset($old_position) &&
                $o_gallery->updatePosition($new_position, $old_position, $way)) {
                die(true);
            } else {
                die('{"hasError" : true, errors : "Cannot update gallery position"}');
            }
        } else {
            die('{"hasError" : true, "errors" : "This gallery cannot be loaded"}');
        }
    }

    public function postProcess()
    {
        if (Tools::getIsset("deleteImage")) {
            $id_gallery = Tools::getValue('id_responsive_gallery_gallery');
            $this->module->deleteLogoAndThumb($id_gallery, ResponsiveGallery::GALLERIES);
            $o_gallery = new ResponsiveGalleryGallery($id_gallery);
            $o_gallery->image = '';
            $o_gallery->save();
        }
        parent::postProcess();
    }

    public function getList(
        $id_lang,
        $order_by = null,
        $order_way = null,
        $start = 0,
        $limit = null,
        $id_lang_shop = false
    ) {
        $id_lang_shop = Context::getContext()->shop->id ? Context::getContext()->shop->id : $id_lang_shop;
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $js_path_advanced_config = $this->module->getPathUri().'views/js/adminresponsivegalleryadvancedconfig.js';
        $css_path = $this->module->getPathUri().'views/css/adminresponsivegallery.css';
        $this->addJS($js_path_advanced_config);
        $this->addCSS($css_path);
    }

    public function beforeAdd($object)
    {
        if (!ResponsiveGallery::isVersion17() && isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
            $object->image = $_FILES['image']['name'];
        }
    }

    public function afterAdd($object)
    {
        if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
            $this->module->postImageResponsiveGallery($_FILES['image'], $object->id, ResponsiveGallery::GALLERIES);
        }
    }

    public function updateAssoShop($id_object)
    {
        parent::updateAssoShop($id_object);
        //gérer les positions des boutiques
        $ids_shop = Tools::getValue('checkBoxShopAsso_responsive_gallery_gallery');
        if (!$ids_shop) {
            $ids_shop = array($this->context->shop->id);
        }
        foreach ($ids_shop as $id_shop) {
            $this->setLastPositionByShop($id_object, $id_shop);
        }
    }

    public function setLastPositionByShop($id_gallery, $id_shop)
    {
        $sql_position = 'SELECT position
                            FROM ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop
                            WHERE id_responsive_gallery_gallery = ' . (int)$id_gallery . '
                            AND id_shop = ' . (int)$id_shop;
        $position = Db::getInstance()->getValue($sql_position);
        if ($position == 0) {
            $sql_number_position = 'SELECT count(position)
                                    FROM ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop
                                    WHERE id_shop = ' . (int)$id_shop;
            if ((int) Db::getInstance()->getValue($sql_number_position) > 1) {
                $sql_max_position = 'SELECT max(position) as pos
                        FROM ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop
                        WHERE id_shop = ' . (int)$id_shop;
                $max_position = (int) Db::getInstance()->getValue($sql_max_position) + 1;
                $sql_update_position = 'UPDATE ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop
                                        SET position = ' . (int)$max_position . '
                                        WHERE id_shop = ' . (int)$id_shop . '
                                        AND id_responsive_gallery_gallery = ' . (int)$id_gallery;
                Db::getInstance()->execute($sql_update_position);
            }
        }
    }

    public function processUpdate()
    {
        //Récupère le nom de l'image avant la MAJ de la galerie.
        $id_responsive_gallery_gallery = Tools::getValue('id_responsive_gallery_gallery');
        $image_name = ResponsiveGalleryGallery::getNameImageById($id_responsive_gallery_gallery);

        parent::processUpdate();

        //Dans le cas d'un prestashop 1.7 le champ input garde le même nom que donné dans le form alors
        //quand on soumet le formulaire sans image il remplace le champs image par du vide.
        $o_gallery = new ResponsiveGalleryGallery($id_responsive_gallery_gallery);
        if ($new_image_name = $_FILES['image']['name']) {
            //si il y a une nouvelle image alors changer le nom et save.
            $o_gallery->image = $new_image_name;
        } else {
            //si il n'y pas de nouvelle image alors récup l'ancien nom.
            $o_gallery->image = $image_name;
        }
        $o_gallery->save();
    }

    public function afterUpdate($object)
    {
        if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
            $this->module->postImageResponsiveGallery($_FILES['image'], $object->id, ResponsiveGallery::GALLERIES);
        }
    }

    public function processDelete()
    {
        $id_object = $this->loadObject()->id;
        $upload_path = _PS_MODULE_DIR_ . implode(
            DIRECTORY_SEPARATOR,
            array('responsivegallery', 'views', 'img', 'galleries', '')
        );
        $this->module->deleteImageStartById($id_object, $upload_path, true);
        $this->module->deleteImageStartById($id_object, $upload_path, false);
        return parent::processDelete();
    }
}
