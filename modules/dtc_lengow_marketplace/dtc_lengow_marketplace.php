<?php
/**
 * Images JPEG pour flux marketplace (Lengow / Mirakl) tout en conservant le WebP boutique.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/MarketplaceImageService.php';
require_once __DIR__ . '/classes/MarketplaceImageUrlProvider.php';

class Dtc_lengow_marketplace extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_lengow_marketplace';
        $this->tab = 'export';
        $this->version = '1.0.0';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Districlos - Images marketplace Lengow');
        $this->description = $this->l('Expose des URLs JPEG pour les marketplaces lorsque la boutique utilise le WebP.');
        $this->ps_versions_compliancy = ['min' => '1.7.5.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionObjectImageAddAfter')
            && $this->registerHook('actionObjectImageUpdateAfter')
            && $this->registerHook('actionObjectImageDeleteAfter')
            && $this->ensureCacheDirectory();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookActionObjectImageAddAfter($params)
    {
        $this->purgeImageCache($params);
    }

    public function hookActionObjectImageUpdateAfter($params)
    {
        $this->purgeImageCache($params);
    }

    public function hookActionObjectImageDeleteAfter($params)
    {
        $this->purgeImageCache($params);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function purgeImageCache($params)
    {
        if (!isset($params['object']) || !($params['object'] instanceof Image)) {
            return;
        }

        MarketplaceImageService::purgeCacheForImage((int) $params['object']->id);
    }

    private function ensureCacheDirectory()
    {
        $dir = MarketplaceImageService::getCacheDirectory();

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                return false;
            }
        }

        $index = $dir . 'index.php';
        if (!file_exists($index)) {
            @file_put_contents($index, "<?php\nheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');\nheader('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');\nheader('Cache-Control: no-store, no-cache, must-revalidate');\nheader('Cache-Control: post-check=0, pre-check=0', false);\nheader('Pragma: no-cache');\nheader('Location: ../');\nexit;\n");
        }

        return true;
    }
}
