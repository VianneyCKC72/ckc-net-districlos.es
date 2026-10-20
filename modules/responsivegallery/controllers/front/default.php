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

class ResponsiveGalleryDefaultModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        Tools::redirect($this->context->link->getModuleLink($this->module->name, 'galleries'));
    }
}
