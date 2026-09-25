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

class ResponsiveGalleryGalleriesModuleFrontController extends ModuleFrontController
{

    public function init()
    {
        parent::init();
        $this->display_column_left = false;
        //si une gallerie alors redirection vers la gallerie
        $count_gallery = count(ResponsiveGalleryGallery::getGalleries(true));
        if ($count_gallery == 0) {
            Tools::redirect($this->context->link->getPageLink('index'));
        } elseif ($count_gallery == 1) {
            $id_gallery = ResponsiveGalleryGallery::getGalleries(true)[0]['id_responsive_gallery_gallery'];
            Tools::redirect($this->context->link->getModuleLink(
                $this->module->name,
                'galleryitems',
                array('id_gallery' => $id_gallery)
            ));
        }
    }

    public function initContent()
    {
        parent::initContent();
        $all_galleries = ResponsiveGalleryGallery::getGalleries(1);
        $path_module = $this->module->getPathUri();
        $path_img_galleries = $path_module . 'views/img/galleries/';

        if (ResponsiveGallery::isVersion17()) {
            $breadcrumb = $this->context->controller->getBreadcrumb();
            $breadcrumb['links'][] = array(
                'title' => $this->module->l('ResponsiveGallery', 'galleries'),
                'url' => $this->context->link->getModuleLink($this->module->name, 'galleries')
            );
            $this->context->smarty->assign('breadcrumb', $breadcrumb);
        }

        foreach ($all_galleries as $id => $gallery) {
            $all_galleries[$id]['link'] =
            $this->module->getGalleryLink($gallery['id_responsive_gallery_gallery'], true);
        }

        $id_lang = $this->context->language->id;
        $id_shop = $this->context->shop->id;

        $this->context->smarty->assign(array(
            'RG_TITLE_PAGE' => Configuration::get('RG_TITLE_PAGE', $id_lang, null, $id_shop),
            'RG_DESCRIPTION_PAGE' => Configuration::get('RG_DESCRIPTION_PAGE', $id_lang, null, $id_shop),
            'all_galleries' => $all_galleries,
            'path_img_galleries' => $path_img_galleries,
        ));

        if (ResponsiveGallery::isVersion17()) {
            $this->setTemplate('module:responsivegallery/views/templates/front/galleries_1_7.tpl');
        } else {
            $this->setTemplate('galleries_1_6.tpl');
        }
    }

    public function setMedia()
    {
        parent::setMedia();
        $css_path = $this->module->getPathUri().'views/css/responsivegallerygallery.css';

        if (ResponsiveGallery::isVersion17()) {
            $this->registerStylesheet(sha1($css_path), $css_path, array('priority' => 80));
        } else {
            $this->addCSS($css_path);
        }
    }
}
