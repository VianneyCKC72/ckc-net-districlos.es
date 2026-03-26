<?php

class CacheStorageFile
{
    /** @var string */
    private $baseDir;

    /** @var int */
    private $ttlSeconds;

    public function __construct(string $baseDir, int $ttlSeconds)
    {
        $this->baseDir = rtrim($baseDir, '/\\');
        $this->ttlSeconds = (int) $ttlSeconds;

        if (!is_dir($this->baseDir)) {
            @mkdir($this->baseDir, 0775, true);
        }
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    /**
     * Stocke une valeur PHP sérialisable (idéalement un tableau) dans un fichier cache.
     * Écriture atomique : tmp + rename.
     *
     * @param string $keyHash hex sha256 (ex: "ab12...")
     * @param mixed $value
     */
    public function set(string $keyHash, $value): void
    {
        $path = $this->keyToPath($keyHash);
        $dir = dirname($path);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $tmp = $path . '.tmp_' . bin2hex(random_bytes(4));

        $payload = "<?php\nreturn " . var_export($value, true) . ";\n";

        // LOCK_EX : utile pendant la phase d’écriture du tmp
        $bytes = @file_put_contents($tmp, $payload, LOCK_EX);
        if ($bytes === false) {
            @unlink($tmp);
            return;
        }

        // rename est atomique sur la plupart des FS
        @rename($tmp, $path);
    }

    /**
     * Récupère une valeur depuis cache si présente et non expirée.
     * @param string $keyHash
     * @return mixed|null
     */
    public function get(string $keyHash)
    {
        $path = $this->keyToPath($keyHash);
        if (!is_file($path)) {
            return null;
        }

        $mtime = @filemtime($path);
        if ($mtime === false) {
            return null;
        }

        if ((time() - (int) $mtime) > $this->ttlSeconds) {
            return null;
        }

        // include = exécute le "return ..." du fichier. Ici le fichier est généré par nous.
        $data = @include $path;
        return $data;
    }

    /**
     * Supprime les fichiers trop vieux (anti-grossissement).
     * @param int $maxAgeSeconds
     */
    public function garbageCollectOldFiles(int $maxAgeSeconds): void
    {
        if (!is_dir($this->baseDir)) {
            return;
        }

        $maxAgeSeconds = (int) $maxAgeSeconds;
        $cutoff = time() - $maxAgeSeconds;

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile()) {
                $mtime = $file->getMTime();
                if ($mtime > 0 && (int)$mtime < $cutoff) {
                    @unlink($file->getPathname());
                }
            }
        }
    }

    /**
     * Convertit un hash (hex) vers un chemin.
     * sharding : ab/12/<fullhash>.php
     *
     * @param string $keyHash
     * @return string
     */
    private function keyToPath(string $keyHash): string
    {
        // Sécurité minimale : hash attendu hex. Sinon on fallback en “direct”.
        $keyHash = strtolower(trim($keyHash));
        if (strlen($keyHash) < 4) {
            return $this->baseDir . '/' . $keyHash . '.php';
        }

        $a = substr($keyHash, 0, 2);
        $b = substr($keyHash, 2, 2);

        return $this->baseDir . '/' . $a . '/' . $b . '/' . $keyHash . '.php';
    }
}