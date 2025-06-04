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

class Ckcimagesupload extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ckcimagesupload';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'ckc-net';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ckc-net images upload');
        $this->description = $this->l('Allows you to upload images to your server without using an FTP client.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall ?');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {


        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = $this->postProcess();

        $this->context->smarty->assign([
            'uploaded_images' => $this->getUploadedImages(),
            'module_dir' => $this->_path,
            'domain' =>  Tools::getShopDomain(true)
        ]);

        return $output . $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    // protected function renderForm()
    // {
    //     $form = [
    //         'form' => [
    //             'title' => 'Téléversement d\'images',
    //         ],
    //         'input' =>  [
    //             [
    //                 'type' => 'file',
    //                 'label' => 'Choisir des images',
    //                 'name' => 'module_image[]',
    //                 'multiple' => true,
    //                 'desc' => 'Formats autorisés : jpeg, JPG, png, webp'
    //             ],

    //         ],
    //         'submit' => [
    //             'title' => $this->trans('Enregistrer', [], 'Admin.Actions')
    //         ]

    //     ];

    //     $helper = new HelperForm();
    //     $helper->module = $this;
    //     $helper->name_controller = $this->name;
    //     $helper->token = Tools::getAdminTokenLite('AdminModules');
    //     $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
    //     $helper->submit_action = 'submit_'.$this->name;

    //     return $helper->generateForm([$form]);
    // }

    protected function getUploadedImages()
    {
        $uploadDir = _PS_MODULE_DIR_.$this->name.'/uploads/';
        $images = [];

        foreach (glob($uploadDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $filePath) {
            $images[] = basename($filePath);
        }

        return $images;
    }

    protected function handleImageUpload()
    {
        $errors = [];

        if (isset($_FILES['module_images']) && !empty($_FILES['module_images']['name'][0])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxSize = 500 * 1024; // 500 Ko
            $maxFiles = 8;

            $fileCount = count($_FILES['module_images']['name']);

            if ($fileCount > $maxFiles) {
                $errors[] = "Vous ne pouvez pas uploader plus de $maxFiles fichiers à la fois.";
                return $errors;
            }

            foreach ($_FILES['module_images']['name'] as $key => $name) {
                $tmpName = $_FILES['module_images']['tmp_name'][$key];
                $type = $_FILES['module_images']['type'][$key];
                $size = $_FILES['module_images']['size'][$key];

                if (!in_array($type, $allowedTypes)) {
                    $errors[] = "Le fichier $name a un format non autorisé.";
                    continue;
                }

                if ($size > $maxSize) {
                    $errors[] = "Le fichier $name dépasse la taille maximale autorisée de 500 Ko.";
                    continue;
                }

                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $newName = uniqid() . '.' . $ext;
                $uploadDir = _PS_MODULE_DIR_ . $this->name . '/uploads/';

                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $destination = $uploadDir . $newName;
                if (!move_uploaded_file($tmpName, $destination)) {
                    $errors[] = "Échec de l’upload du fichier $name.";
                }
            }
        }
        return $errors;
    }


    protected function handleImageDeletion($images)
    {
        $uploadDir = _PS_MODULE_DIR_ . $this->name . '/uploads/';

        foreach ($images as $image) {
            $filePath = $uploadDir . basename($image);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }


    protected function postProcess() {
        if (Tools::isSubmit('submit_' . $this->name)) {
            $errors = $this->handleImageUpload();
            if (!empty($errors)) {
                return $this->displayError(implode('<br>', $errors));
            }
            return $this->displayConfirmation('Images téléchargées avec succès.');
        }

        if (Tools::isSubmit('delete_one')) {
            $image = Tools::getValue('delete_one');
            $this->handleImageDeletion([$image]);
            return $this->displayConfirmation('Image supprimée avec succès.');
        }

        if (Tools::getValue('images_to_delete')) {
            $this->handleImageDeletion(Tools::getValue('images_to_delete'));
            return $this->displayConfirmation('Images supprimées avec succès.');
        }

        if (Tools::isSubmit('delete_all')) {
            $this->handleDeleteAll();
            return $this->displayConfirmation('Toutes les images ont été supprimées.');
        }

        return '';
    }

    protected function handleDeleteAll()
    {
        $uploadDir = _PS_MODULE_DIR_ . $this->name . '/uploads/';
        $images = glob($uploadDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);

        foreach ($images as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
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

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
}
