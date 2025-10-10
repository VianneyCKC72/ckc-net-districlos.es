<?php
class Ckc_RealisationMyrealisationaddModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function postProcess()
    {
        if (!Tools::isSubmit('submitReal')) { Tools::redirect($this->context->link->getModuleLink($this->module->name,'myrealisation')); }

        if (!Tools::getToken() || Tools::getToken() !== Tools::getValue('token')) {
            $this->errors[] = $this->module->l('Token invalide');
            return;
        }

        $idCustomer = (int)$this->context->customer->id;
        $idShop = (int)$this->context->shop->id;


        if (empty($_FILES['file']['name'])) {
            $this->errors[] = $this->module->l('Aucun fichier');
            return;
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->module->l('Erreur upload');
            return;
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg','image/png'])) {
            $this->errors[] = $this->module->l('Format non supporté');
            return;
        }
        if ($file['size'] > 5*1024*1024) {
            $this->errors[] = $this->module->l('Fichier trop volumineux');
            return;
        }

        $dir = Realisation::storageDir($idShop);
        @mkdir($dir, 0755, true);
        $basename = uniqid('real_', true).($mime==='image/png'?'.png':'.jpg');
        $dest = $dir.$basename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->errors[] = $this->module->l('Échec de l’upload');
            return;
        }
        ImageManager::thumbnail($dest, 'thumb_'.$basename, 360, 'jpg', true, $dir);

        $real = new Realisation();
        $real->id_shop = $idShop;
        $real->id_store = $idStore;
        $real->id_customer = $idCustomer;
        $real->filename = $basename;
        $real->mime = $mime;
        $real->filesize = (int)$file['size'];
        $real->source = 'client';
        $real->status = 'pending';

        foreach (Language::getLanguages(false) as $lang) {
            $real->title[$lang['id_lang']] = Tools::getValue('title_'.$lang['id_lang']);
            $real->alt[$lang['id_lang']]   = Tools::getValue('alt_'.$lang['id_lang']);
        }

        if (!$real->add()) {
            @unlink($dest);
            @unlink($dir.'thumb_'.$basename);
            $this->errors[] = $this->module->l('Erreur enregistrement');
        } else {
            $this->success[] = $this->module->l('Photo envoyée, en attente de validation.');
        }

        Tools::redirect($this->context->link->getModuleLink($this->module->name,'myrealisation'));
    }
}
