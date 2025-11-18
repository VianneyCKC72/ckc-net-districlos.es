<?php

require_once _PS_MODULE_DIR_ . 'ckc_espacepro/classes/front/ProductsService.php';

class ckc_espaceproguidesfichesModuleFrontController extends ModuleFrontController
{
   public function initContent()
   {

        parent::initContent();

         $idProduct = (int)Tools::getValue('download_product');
         if ($idProduct) {
            $this->downloadProductZip($idProduct);
            return; 
         }

        $service = new ProductsService();

        $sections = $service->getSections();

        $this->context->smarty->assign(compact('sections'));

        $this->setTemplate('module:ckc_espacepro/views/templates/front/guides-fiches/guides-fiches.tpl');

   }

   
   /**
     * Génère et envoie un ZIP contenant tous les documents d’un produit
     */
    private function downloadProductZip(int $idProduct)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            die('Produit invalide');
        }

        $service = new ProductsService();
        $sections = $service->getSections();

        // Recherche le produit dans toutes les sections
        $product = null;
        foreach ($sections as $section) {
            foreach ($section['products'] as $p) {
                if ((int)$p['id'] === $idProduct) {
                    $product = $p;
                    break 2; 
                }
            }
        }

        if (!$product || empty($product['documents'])) {
            die('Aucun document trouvé pour ce produit.');
        }

        // Nom du ZIP basé sur le nom du produit
        $productNameSafe = Tools::str2url($product['name']);
        if (empty($productNameSafe)) {
            $productNameSafe = 'product_' . $idProduct;
        }

        // Création du fichier temporaire ZIP
        $zip = new \ZipArchive();
        $zipFilename = tempnam(sys_get_temp_dir(), 'product_docs_') . '.zip';

        if ($zip->open($zipFilename, \ZipArchive::CREATE) !== true) {
            die('Impossible de créer l’archive ZIP.');
        }

        foreach ($product['documents'] as $doc) {
            $attachment = new Attachment($doc['id_attachment']);
            if (empty($attachment->file)) {
                continue;
            }

            $filePath = _PS_DOWNLOAD_DIR_ . $attachment->file;
            if (is_file($filePath)) {
                // Nom unique dans le ZIP : on ajoute l'ID de l'attachment
                $safeDocName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $doc['name']);
                $safeDocName = $safeDocName . '_' . $doc['id_attachment'] . '.pdf';

                $zip->addFile($filePath, $safeDocName);
            }
        }

        $zip->close();

        // Envoi du ZIP au navigateur
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $productNameSafe . '.zip"');
        header('Content-Length: ' . filesize($zipFilename));

        readfile($zipFilename);

        // Nettoyage du fichier temporaire
        unlink($zipFilename);
        exit;
    }

}