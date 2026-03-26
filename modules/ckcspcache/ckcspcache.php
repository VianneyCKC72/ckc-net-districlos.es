<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckcspcache extends Module
{
    public function __construct()
    {
        $this->name = 'ckcspcache';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'you';

        parent::__construct();

        $this->displayName = 'SpecificPrice cache (file + TTL)';
        $this->description = 'Cache court et invalidation via hooks pour specific_price.';
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue('CKC_SP_CACHE_TTL', 120)
            && Configuration::updateValue('CKC_SP_CACHE_NS_VERSION', 1);
    }

    public function getContent()
    {
        $out = '';

        if (Tools::isSubmit('submitCkcSpCache')) {
            $ttl = (int) Tools::getValue('CKC_SP_CACHE_TTL', 120);
            if ($ttl < 60) $ttl = 60;
            if ($ttl > 300) $ttl = 300;

            Configuration::updateValue('CKC_SP_CACHE_TTL', $ttl);
            $out .= $this->displayConfirmation('TTL mis à jour.');
        }

        $ttl = (int) Configuration::get('CKC_SP_CACHE_TTL', 120);

        $out .= '
            <form method="post">
                <label>TTL (60-300s)</label>
                <input type="number" name="CKC_SP_CACHE_TTL" value="'.(int)$ttl.'" min="60" max="300" />
                <button type="submit" name="submitCkcSpCache" class="btn btn-default">Enregistrer</button>
            </form>
        ';

        return $out;
    }

    private function bumpNamespaceVersion(): void
    {
        $v = (int) Configuration::get('CKC_SP_CACHE_NS_VERSION', 1);
        $v++;
        Configuration::updateValue('CKC_SP_CACHE_NS_VERSION', $v);

        // Optionnel : nettoyage paresseux (facultatif, voir stratégie anti-grossissement)
        $this->getSpecificPriceCache()->garbageCollectOldNamespaces($v);
    }

    private function getSpecificPriceCache()
    {
        require_once __DIR__ . '/classes/SpecificPriceCache.php';
        return new SpecificPriceCache($this);
    }

    public function hookActionSpecificPriceAdd($params)
    {
        $this->bumpNamespaceVersion();
    }

    public function hookActionSpecificPriceUpdate($params)
    {
        $this->bumpNamespaceVersion();
    }

    public function hookActionSpecificPriceDelete($params)
    {
        $this->bumpNamespaceVersion();
    }
}