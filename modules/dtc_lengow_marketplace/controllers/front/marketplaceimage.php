<?php
/**
 * Sert une image produit en JPEG (conversion à la volée si WebP sur disque).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'dtc_lengow_marketplace/classes/MarketplaceImageService.php';
require_once _PS_MODULE_DIR_ . 'dtc_lengow_marketplace/classes/MarketplaceImageUrlProvider.php';

class Dtc_lengow_marketplaceMarketplaceimageModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function init()
    {
        parent::init();

        $idImage = (int) Tools::getValue('id_image');
        $imageType = MarketplaceImageUrlProvider::sanitizeImageType(
            (string) Tools::getValue('type', 'large_default')
        );

        if ($idImage <= 0 || !Validate::isUnsignedId($idImage)) {
            $this->notFound();

            return;
        }

        if (!$this->isValidProductImageType($imageType)) {
            $this->notFound();

            return;
        }

        $service = new MarketplaceImageService();
        $cachePath = $service->getOrCreateJpegPath($idImage, $imageType);

        if ($cachePath === false || !is_readable($cachePath)) {
            $this->notFound();

            return;
        }

        $length = (int) filesize($cachePath);
        $mtime = (int) filemtime($cachePath);

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . $length);
        header('Cache-Control: public, max-age=86400');
        if ($mtime > 0) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        }
        header('X-Content-Type-Options: nosniff');

        readfile($cachePath);
        exit;
    }

    /**
     * @param string $imageType
     */
    private function isValidProductImageType($imageType)
    {
        static $allowed = null;

        if ($allowed === null) {
            $allowed = [];
            $types = ImageType::getImagesTypes('products');
            if (is_array($types)) {
                foreach ($types as $type) {
                    if (!empty($type['name'])) {
                        $allowed[$type['name']] = true;
                    }
                }
            }
            if (empty($allowed)) {
                $allowed = [
                    'large_default' => true,
                    'medium_default' => true,
                    'small_default' => true,
                    'home_default' => true,
                    'cart_default' => true,
                ];
            }
        }

        return isset($allowed[$imageType]);
    }

    private function notFound()
    {
        header('HTTP/1.1 404 Not Found');
        exit;
    }
}
