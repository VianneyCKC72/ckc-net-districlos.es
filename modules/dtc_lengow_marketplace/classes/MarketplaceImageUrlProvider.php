<?php
/**
 * URLs d'images JPEG pour les flux marketplace (Lengow).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class MarketplaceImageUrlProvider
{
    public static function isActive()
    {
        return Module::isEnabled('dtc_lengow_marketplace');
    }

    /**
     * @param int $idImage
     * @param string $imageType ex. large_default
     */
    public static function getUrl($idImage, $imageType)
    {
        $idImage = (int) $idImage;
        $imageType = self::sanitizeImageType($imageType);

        return Context::getContext()->link->getModuleLink(
            'dtc_lengow_marketplace',
            'marketplaceimage',
            [
                'id_image' => $idImage,
                'type' => $imageType,
            ],
            true
        );
    }

    /**
     * @param string $imageType
     *
     * @return string
     */
    public static function sanitizeImageType($imageType)
    {
        $imageType = (string) $imageType;
        if (!preg_match('/^[a-z0-9_]+$/i', $imageType)) {
            return 'large_default';
        }

        return $imageType;
    }
}
