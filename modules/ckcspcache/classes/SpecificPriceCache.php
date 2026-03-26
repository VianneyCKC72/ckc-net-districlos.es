<?php

class SpecificPriceCache
{
    /** @var Module */
    private $module;

    /** @var CacheStorageFile */
    private $storage;

    public function __construct(Module $module)
    {
        $this->module = $module;

        require_once __DIR__ . '/CacheStorageFile.php';

        $baseDir = _PS_MODULE_DIR_ . $this->module->name . '/cache/sp/';

        $ttl = (int) Configuration::get('CKC_SP_CACHE_TTL', 120);
        if ($ttl < 60) {
            $ttl = 60;
        } elseif ($ttl > 300) {
            $ttl = 300;
        }

        $this->storage = new CacheStorageFile($baseDir, $ttl);
    }

    private function getNamespaceVersion(): int
    {
        return (int) Configuration::get('CKC_SP_CACHE_NS_VERSION', 1);
    }

    private function getTtl(): int
    {
        return (int) Configuration::get('CKC_SP_CACHE_TTL', 120);
    }

    private function normalizeKeyParts(array $parts): array
    {
        // Pour éviter les collisions "null vs 0" dans les clés.
        // On force les champs attendus à des entiers.
        return [
            'id_product' => (int)($parts['id_product'] ?? 0),
            'id_shop' => (int)($parts['id_shop'] ?? 0),
            'id_currency' => (int)($parts['id_currency'] ?? 0),
            'id_country' => (int)($parts['id_country'] ?? 0),
            'id_group' => (int)($parts['id_group'] ?? 0),
            'quantity' => (int)($parts['quantity'] ?? 0),
            'id_product_attribute' => (int)($parts['id_product_attribute'] ?? 0),
            'id_customer' => (int)($parts['id_customer'] ?? 0),
            'id_cart' => (int)($parts['id_cart'] ?? 0),
            'real_quantity' => (int)($parts['real_quantity'] ?? 0),
        ];
    }

    private function makeKeyHash(array $keyParts): string
    {
        $ns = $this->getNamespaceVersion();

        // Un "namespace" (version) pour flush global rapide.
        $keyParts['ns'] = $ns;

        // Stable key
        ksort($keyParts);
        $raw = json_encode($keyParts, JSON_UNESCAPED_SLASHES);
        return hash('sha256', $raw);
    }

    public function getCachedRow(array $keyParts)
    {
        $normalized = $this->normalizeKeyParts($keyParts);
        $hash = $this->makeKeyHash($normalized);

        return $this->storage->get($hash);
    }

    public function setCachedRow(array $keyParts, $row): void
    {
        $normalized = $this->normalizeKeyParts($keyParts);
        $hash = $this->makeKeyHash($normalized);

        $this->storage->set($hash, $row);
    }

    public function garbageCollectOldFiles(): void
    {
        // Anti-grossissement : on supprime "largement après" la TTL
        $maxAge = $this->getTtl() * 5;

        $this->storage->garbageCollectOldFiles($maxAge);
    }
}