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

class ResponsiveGalleryGalleryItemsModuleFrontController extends ModuleFrontController
{

    public function init()
    {
        parent::init();
        $this->display_column_left = false;
        $id_gallery = (int) Tools::getValue('id_gallery');
        if (!$id_gallery) {
            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'galleries'));
        } else {
            if (!ResponsiveGalleryGallery::inThisShop($id_gallery)) {
                Tools::redirect($this->context->link->getModuleLink($this->module->name, 'galleries'));
            }
            //TODO voir avec sousdomaine supplémentaire $this->context->shop->physical_uri
            $this->redirectCanonical($id_gallery, urldecode($_SERVER['REQUEST_URI']));
        }
    }

    public function redirectCanonical($id_gallery, $canonical_url = '')
    {
        if (!$canonical_url ||
            !Configuration::get('PS_CANONICAL_REDIRECT')
            || Tools::strtoupper($_SERVER['REQUEST_METHOD']) != 'GET'
            || Tools::getValue('live_edit')) {
            return;
        }

        $physical_uri = Tools::substr($this->context->shop->physical_uri, 0, -1);
        if (!Tools::getValue('ajax') && $canonical_url != $physical_uri.$this->module->getGalleryLink($id_gallery)) {
            $redirect_type = Configuration::get('PS_CANONICAL_REDIRECT') == 2 ? '301' : '302';
            header('HTTP/1.0 ' . $redirect_type . ' Moved');
            header('Cache-Control: no-cache');
            Tools::redirectLink($this->module->getGalleryLink($id_gallery, true));
        }
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addjqueryPlugin('fancybox');
        // URLs web : toujours des / (DIRECTORY_SEPARATOR casse les assets sous Windows)
        $js_path = $this->module->getPathUri() . 'views/js/responsivegalleryitem.js';
        $css_path = $this->module->getPathUri() . 'views/css/responsivegallerygallery.css';

        $this->addJS($js_path);
        $this->addCSS($css_path);
    }

    public function initContent()
    {
        parent::initContent();
        $id_gallery = (int) Tools::getValue('id_gallery');
        $id_lang = $this->context->language->id;
        $id_shop = $this->context->shop->id;
        $o_responsive_gallery_gallery = new ResponsiveGalleryGallery($id_gallery, $id_lang, $id_shop);

        if (ResponsiveGallery::isVersion17()) {
            $breadcrumb = $this->context->controller->getBreadcrumb();
            $breadcrumb['links'][] = array(
                'title' => $this->module->l('ResponsiveGallery', 'galleryitems'),
                'url' => $this->context->link->getModuleLink($this->module->name, 'galleries')
            );
            $breadcrumb['links'][] = array('title' => $o_responsive_gallery_gallery->title, 'url' => null);
            $this->context->smarty->assign('breadcrumb', $breadcrumb);
        }

        //ajout du méta title avec le titre de la galerie
        if (!ResponsiveGallery::isVersion17()) {
            $this->context->smarty->assign('meta_title', $o_responsive_gallery_gallery->title);
        }

        if (!$o_responsive_gallery_gallery->active) {
            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'galleries'));
        }

        if (Tools::getValue('ajax', 0) == 1 && Tools::getValue('page', false) !== false) {
            $this->getOtherPageGallery();
        } else {
            //ajout des variables JS
            $o_responsive_gallery_gallery->assignMultipleConstant($this->context, false);
            $gallery_items = $o_responsive_gallery_gallery->getGalleryPage(1);

            $path_module = $this->module->getPathUri();
            $path_image = $path_module . 'views/img/items/';
            foreach ($gallery_items as $key => $item) {
                $gallery_items[$key]['path_image'] =
                $path_image . $item['id_responsive_gallery_item'] . '-' . $item['image'];
            }
            $this->context->smarty->assign('gallery_items', $gallery_items);

            if (ResponsiveGallery::isVersion17()) {
                $this->setTemplate('module:responsivegallery/views/templates/front/galleryitems_1_7.tpl');
            } else {
                $this->setTemplate('galleryitems_1_6.tpl');
            }
        }
    }

    public function getOtherPageGallery()
    {
        $id_gallery = (int) Tools::getValue('id_gallery');
        $id_lang = $this->context->cookie->id_lang;
        $o_responsive_gallery_gallery = new ResponsiveGalleryGallery($id_gallery, $id_lang);

        $page = (int) Tools::getValue('page', false);
        //Charger les images suivantes (retourner un tableau au format qu'il faut, avec image, url...)
        $gallery_items = $o_responsive_gallery_gallery->getGalleryPage($page);
        $result_html = '';
        $path_module = $this->module->getPathUri();
        $path_image = $path_module . 'views/img/items/';
        foreach ($gallery_items as $item) {
            $item['path_image'] = $path_image . $item['id_responsive_gallery_item'] . '-' . $item['image'];
            $this->context->smarty->assign(
                array(
                    'item' => $item,
                    'page' => $page,
                )
            );

            $result_html .= $this->module->display(_MODULE_DIR_ . $this->module->name, 'galleryoneitem.tpl');
        }

        echo $result_html;
    }
}
