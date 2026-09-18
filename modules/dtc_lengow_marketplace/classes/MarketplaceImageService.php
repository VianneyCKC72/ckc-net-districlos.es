<?php
/**
 * Conversion WebP/PNG → JPEG avec cache disque pour les marketplaces.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class MarketplaceImageService
{
    /** @var int Qualité JPEG (0-100) */
    const JPEG_QUALITY = 90;

    /**
     * @return string
     */
    public static function getCacheDirectory()
    {
        return _PS_MODULE_DIR_ . 'dtc_lengow_marketplace/cache/';
    }

    /**
     * @param int $idImage
     * @param string $imageType
     *
     * @return string|false Chemin du fichier JPEG en cache, ou false
     */
    public function getOrCreateJpegPath($idImage, $imageType)
    {
        $idImage = (int) $idImage;
        $imageType = MarketplaceImageUrlProvider::sanitizeImageType($imageType);

        $image = new Image($idImage);
        if (!Validate::isLoadedObject($image)) {
            return false;
        }

        $sourcePath = $this->resolveSourcePath($image, $imageType);
        if ($sourcePath === null) {
            return false;
        }

        $cachePath = $this->getCachePath($idImage, $imageType);
        $sourceMtime = @filemtime($sourcePath) ?: 0;

        if (is_readable($cachePath) && @filemtime($cachePath) >= $sourceMtime) {
            return $cachePath;
        }

        $jpegBinary = $this->convertFileToJpegBinary($sourcePath);
        if ($jpegBinary === null) {
            return false;
        }

        $dir = self::getCacheDirectory();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return false;
        }

        if (@file_put_contents($cachePath, $jpegBinary) === false) {
            return false;
        }

        return $cachePath;
    }

    /**
     * @param Image $image
     * @param string $imageType
     *
     * @return string|null
     */
    public function resolveSourcePath(Image $image, $imageType)
    {
        $base = _PS_PRODUCT_IMG_DIR_ . $image->getExistingImgPath();
        $candidates = [
            $base . '-' . $imageType . '.jpg',
            $base . '-' . $imageType . '.webp',
            $base . '.jpg',
            $base . '.webp',
        ];

        foreach ($candidates as $path) {
            if (is_readable($path) && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param int $idImage
     * @param string $imageType
     *
     * @return string
     */
    public function getCachePath($idImage, $imageType)
    {
        $imageType = MarketplaceImageUrlProvider::sanitizeImageType($imageType);

        return self::getCacheDirectory() . (int) $idImage . '-' . $imageType . '.jpg';
    }

    /**
     * @param string $sourcePath
     *
     * @return string|null Contenu binaire JPEG
     */
    public function convertFileToJpegBinary($sourcePath)
    {
        $mime = $this->detectMimeType($sourcePath);

        if ($mime === 'image/jpeg') {
            $data = @file_get_contents($sourcePath);

            return $data !== false ? $data : null;
        }

        if ($mime === 'image/webp') {
            return $this->encodeJpegFromWebp($sourcePath);
        }

        if ($mime === 'image/png') {
            return $this->encodeJpegFromPng($sourcePath);
        }

        if ($mime === 'image/gif') {
            return $this->encodeJpegFromGif($sourcePath);
        }

        return null;
    }

    /**
     * @param string $path
     *
     * @return string|null
     */
    private function detectMimeType($path)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return null;
        }
        $header = fread($handle, 16);
        fclose($handle);

        if ($header === false || strlen($header) < 4) {
            return null;
        }

        if (strncmp($header, "\xFF\xD8\xFF", 3) === 0) {
            return 'image/jpeg';
        }
        if (strncmp($header, 'RIFF', 4) === 0 && substr($header, 8, 4) === 'WEBP') {
            return 'image/webp';
        }
        if (strncmp($header, "\x89PNG", 4) === 0) {
            return 'image/png';
        }
        if (strncmp($header, 'GIF8', 4) === 0) {
            return 'image/gif';
        }

        return null;
    }

    /**
     * @param string $sourcePath
     *
     * @return string|null
     */
    private function encodeJpegFromWebp($sourcePath)
    {
        if (!function_exists('imagecreatefromwebp')) {
            return null;
        }

        $resource = @imagecreatefromwebp($sourcePath);
        if ($resource === false) {
            return null;
        }

        return $this->resourceToJpegBinary($resource);
    }

    /**
     * @param string $sourcePath
     *
     * @return string|null
     */
    private function encodeJpegFromPng($sourcePath)
    {
        if (!function_exists('imagecreatefrompng')) {
            return null;
        }

        $resource = @imagecreatefrompng($sourcePath);
        if ($resource === false) {
            return null;
        }

        imagealphablending($resource, true);
        imagesavealpha($resource, true);
        $flattened = imagecreatetruecolor(imagesx($resource), imagesy($resource));
        if ($flattened === false) {
            imagedestroy($resource);

            return null;
        }
        $white = imagecolorallocate($flattened, 255, 255, 255);
        imagefill($flattened, 0, 0, $white);
        imagecopy($flattened, $resource, 0, 0, 0, 0, imagesx($resource), imagesy($resource));
        imagedestroy($resource);

        return $this->resourceToJpegBinary($flattened);
    }

    /**
     * @param string $sourcePath
     *
     * @return string|null
     */
    private function encodeJpegFromGif($sourcePath)
    {
        if (!function_exists('imagecreatefromgif')) {
            return null;
        }

        $resource = @imagecreatefromgif($sourcePath);
        if ($resource === false) {
            return null;
        }

        return $this->resourceToJpegBinary($resource);
    }

    /**
     * @param resource|\GdImage $resource
     *
     * @return string|null
     */
    private function resourceToJpegBinary($resource)
    {
        ob_start();
        $ok = imagejpeg($resource, null, self::JPEG_QUALITY);
        imagedestroy($resource);
        $data = ob_get_clean();

        if (!$ok || $data === false || $data === '') {
            return null;
        }

        return $data;
    }

    /**
     * @param int $idImage
     */
    public static function purgeCacheForImage($idImage)
    {
        $idImage = (int) $idImage;
        if ($idImage <= 0) {
            return;
        }

        $pattern = self::getCacheDirectory() . $idImage . '-*.jpg';
        foreach (glob($pattern) ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
