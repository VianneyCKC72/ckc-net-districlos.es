<?php
class Ckc_RealisationMyrealisationaddModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /** Redirige vers la liste avec paramètres correctement ajoutés */
    private function redirectToList(array $query = [])
    {
        $url = $this->context->link->getModuleLink($this->module->name, 'myrealisation', $query, true);
        Tools::redirect($url);
    }

    public function postProcess()
    {
        // doit être connecté
        $c = $this->context->customer;
        if (!$c || !(int)$c->id) {
            Tools::redirect('index.php?controller=authentication&back=' .
                urlencode($this->context->link->getModuleLink($this->module->name, 'myrealisation', [], true)));
        }

        // CSRF
        if (Tools::getToken(false) !== Tools::getValue('token')) {
            return $this->redirectToList(['err' => 'token']);
        }

        // Fichier ?
        if (empty($_FILES['file']['name'])) {
            return $this->redirectToList(['err' => 'nofile']);
        }
        if ((int)$_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return $this->redirectToList(['err' => 'upload']);
        }

        // Vérifs MIME & taille
        $tmp  = $_FILES['file']['tmp_name'];
        $mime = null;
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) { $mime = finfo_file($f, $tmp); finfo_close($f); }
        }
        if (!$mime && function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmp);
        }
        if (!in_array($mime, ['image/jpeg','image/png'], true)) {
            return $this->redirectToList(['err' => 'mime']);
        }
        if ((int)$_FILES['file']['size'] > 5*1024*1024) {
            return $this->redirectToList(['err' => 'size']);
        }

        // Déplacement
        $idShop  = (int)$this->context->shop->id;
        $destDir = $this->module->storageDir($idShop); // méthode publique dans le module
        if (!is_dir($destDir)) { @mkdir($destDir, 0755, true); }
        @file_put_contents($destDir.'index.php', "<?php\n");

        $ext      = ($mime === 'image/png') ? '.png' : '.jpg';
        $filename = uniqid('real_', true).$ext;
        if (!@move_uploaded_file($tmp, $destDir.$filename)) {
            return $this->redirectToList(['err' => 'move']);
        }

        // Thumbnail
        $imgExt = Tools::strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        ImageManager::resize($destDir.$filename, $destDir.'thumb_'.$filename, 360, 0, $imgExt, false);

        // Enregistrement BDD
        require_once _PS_MODULE_DIR_.$this->module->name.'/classes/Realisation.php';
        $obj                = new Realisation();
        $obj->id_shop       = $idShop;
        $obj->id_customer   = (int)$c->id;
        $obj->source        = 'client';
        $obj->status        = 'pending';
        $obj->customer_type = $this->module->detectCustomerType((int)$c->id); // doit être public dans le module
        $obj->id_product    = null;
        $obj->keywords      = null;
        $obj->filename      = $filename;
        $obj->mime          = $mime ?: '';
        $obj->filesize      = (int)$_FILES['file']['size'];

        if (!$obj->add()) {
            @unlink($destDir.$filename);
            @unlink($destDir.'thumb_'.$filename);
            return $this->redirectToList(['err' => 'save']);
        }

        // Succès
        return $this->redirectToList(['ok' => 1]);
    }

    public function initContent()
    {
        // Accès direct : on renvoie vers la liste
        $this->redirectToList();
    }
}
