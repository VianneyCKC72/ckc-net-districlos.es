<?php
/*
*
* Copyright (c) SOFTIZY SARL
*
* All rights reserved.
*
* This software is the exclusive property of SOFTIZY SARL.
* It is protected by copyright laws and other international intellectual
* property laws. Any reproduction, modification, distribution, transmission,
* display, or usage of this software without the explicit written permission
* of SOFTIZY SARL is strictly prohibited.
*
*  @copyright Softizy SARL
*  @license   commercial
*  International Registred Trademark & Property of Softizy SARL
*
* Softizy Query Cache — plain-PHP override, NEVER ionCube-encoded.
*
* This class is autoloaded by PrestaShop on every single request, so it must be
* runnable no matter what: if it were encoded, a missing ionCube loader or an
* unreadable licence file would take the whole shop down, back office included.
*
* It therefore owns the single decision "can the query cache run here?" and
* delegates everything else to the encoded engine
* (Softizy\Module\QueryCache\Cache\QueryCacheEngine). With no engine, every
* method falls back to plain PrestaShop behaviour and the query cache is simply
* disabled.
*
* Two rules constrain what may be written here:
*  - no code outside the class body: Module::addOverride() eval()s this file at
*    installation time to annotate its methods;
*  - no `use` and no `implements`: those are resolved when the class is declared,
*    long before the module's Composer autoloader is registered.
*/
abstract class Cache extends CacheCore
{
    /**
     * Marker injected at the top of ionCube-encoded release files
     * (custom/comments.txt). Same marker as LicenseManager::isModuleEncoded().
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    const SOFTIZY_ENCODED_MARKER = 'Encoded classes';
    /**
     * Bytes scanned when looking for the marker: it sits in the comment header
     * prepended to encoded files, the first bytes are always enough.
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    const SOFTIZY_ENCODED_MARKER_SCAN_BYTES = 8192;
    /**
     * Proof that a file of the PrestaShop root belongs to this module. Kept in
     * sync with RootCallbackGuard::OWNERSHIP_BANNER, which cannot be referenced
     * here: this class is declared long before the module autoloader exists.
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    const SOFTIZY_OWNERSHIP_BANNER = 'This file is part of the Softizy Query Cache module';
    /**
     * Seconds between two degradation log lines. This class is instantiated once
     * per request, so a shop that lost its loader would otherwise write one line
     * per request forever — swapping a dead shop for a full disk.
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    const SOFTIZY_LOG_THROTTLE_SECONDS = 3600;
    /**
     * The encoded query cache engine, or null when it cannot run here.
     *
     * @var \Softizy\Module\QueryCache\Cache\QueryCacheEngine|null
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    protected $queryCacheEngine;
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function __construct()
    {
        $this->queryCacheEngine = $this->bootQueryCacheEngine();
    }
    /**
     * Load the engine, or return null when the runtime cannot run it.
     *
     * @return \Softizy\Module\QueryCache\Cache\QueryCacheEngine|null
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    protected function bootQueryCacheEngine()
    {
        $moduleDir = _PS_MODULE_DIR_ . 'softizyquerycache/';
        $engineFile = $moduleDir . 'src/Cache/QueryCacheEngine.php';
        if (!static::isIoncubeLoaderLoaded()) {
            if (static::isFileEncoded($engineFile)) {
                static::throttledLog('Softizy Query Cache: ionCube loader is missing, query cache disabled.');
                return null;
            }
        } elseif (!static::isRootCallbackCompatible() && static::isFileEncoded($engineFile)) {
            static::throttledLog(
                'Softizy Query Cache: ' . _PS_ROOT_DIR_ . '/callback.php belongs to another product and does not'
                . ' route softizyquerycache events, so a licence failure could not be handled safely.'
                . ' Query cache disabled — see doc/shared-ioncube-callback.md.'
            );
            return null;
        }
        try {
            if (!class_exists('Softizy\Module\QueryCache\Cache\QueryCacheEngine', false)) {
                $autoloadFile = $moduleDir . 'vendor/autoload.php';
                if (!is_file($autoloadFile)) {
                    static::throttledLog('Softizy Query Cache: module dependencies are missing, query cache disabled.');
                    return null;
                }
                require_once $autoloadFile;
            }
            if (!class_exists('Softizy\Module\QueryCache\Cache\QueryCacheEngine')) {
                static::throttledLog('Softizy Query Cache: engine could not be loaded, query cache disabled.');
                return null;
            }
            return new \Softizy\Module\QueryCache\Cache\QueryCacheEngine($this);
        } catch (\Throwable $throwable) {
            static::throttledLog('Softizy Query Cache: engine could not be loaded (' . $throwable->getMessage() . '), query cache disabled.');
            return null;
        }
    }
    /**
     * error_log() at most once per SOFTIZY_LOG_THROTTLE_SECONDS.
     *
     * Same guard as callback.php, duplicated on purpose: that file is executed by
     * the ionCube loader, never included from here, so its helper cannot be
     * assumed to exist. The marker is namespaced by module path so that two shops
     * sharing a server (or a temp dir owned by another user) cannot silence each
     * other's diagnostics.
     *
     * @param string $message
     *
     * @return void
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    protected static function throttledLog($message)
    {
        $marker = sys_get_temp_dir() . '/softizyquerycache_degraded_' . md5(_PS_MODULE_DIR_) . '.marker';
        if (is_file($marker) && (int) @filemtime($marker) > time() - self::SOFTIZY_LOG_THROTTLE_SECONDS) {
            return;
        }
        @touch($marker);
        error_log($message);
    }
    /**
     * @return bool
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    protected static function isIoncubeLoaderLoaded()
    {
        if (extension_loaded('ionCube Loader')) {
            return true;
        }
        foreach (get_loaded_extensions() as $extension) {
            if (stripos($extension, 'ioncube') !== false) {
                return true;
            }
        }
        return false;
    }
    /**
     * Whether _PS_ROOT_DIR_/callback.php can absorb a loader event raised on one
     * of this module's encoded files.
     *
     * The ionCube loader runs exactly one handler for the whole shop, and the
     * graceful degradation of this class depends on it returning quietly rather
     * than terminating the request. Three states are compatible:
     *  - no root callback at all: the loader keeps its own default handling and
     *    never reaches a foreign die();
     *  - the file is ours (full ownership banner);
     *  - a merchant-merged handler that explicitly routes softizyquerycache
     *    events, whoever wrote it.
     *
     * Only the header is read, within the same budget as the encoded marker:
     * this runs once per request, from the constructor, and the shipped callback
     * is ~3 KB so the read stops at EOF well before the cap. Measured at 0.022 ms
     * per request — against the 0.195 ms per page saved by memoizing the licence
     * check in the engine.
     *
     * Keep this call FIRST in the caller's condition: on an encoded shop the
     * callback is ours, this returns true, and the engine file is never read.
     * Swapping the operands would read both files on every request instead of one.
     *
     * @return bool
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    protected static function isRootCallbackCompatible()
    {
        $rootCallback = _PS_ROOT_DIR_ . '/callback.php';
        if (!is_file($rootCallback) || !is_readable($rootCallback)) {
            return true;
        }
        $header = (string) file_get_contents($rootCallback, false, null, 0, self::SOFTIZY_ENCODED_MARKER_SCAN_BYTES);
        return strpos($header, self::SOFTIZY_OWNERSHIP_BANNER) !== false
            || strpos($header, 'softizyquerycache') !== false;
    }
    /**
     * @param string $file
     *
     * @return bool
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    protected static function isFileEncoded($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            return true;
        }
        $header = file_get_contents($file, false, null, 0, self::SOFTIZY_ENCODED_MARKER_SCAN_BYTES);
        return strpos((string) $header, self::SOFTIZY_ENCODED_MARKER) !== false;
    }
    /**
     * @return bool whether the query cache engine runs in this process
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function hasQueryCacheEngine()
    {
        return $this->queryCacheEngine !== null;
    }
    /**
     * @return bool
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function canStoreResult()
    {
        return $this->shouldStoreResult();
    }
    /**
     * @param string $table
     *
     * @return bool
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function isTableBlacklisted($table)
    {
        return isset($this->blacklist[$table]);
    }
    /**
     * The blacklist is a CacheCore property used by its own code paths
     * (setQuery()), so the engine pushes its own list here to keep both in sync.
     *
     * @param array $blacklist
     *
     * @return void
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function setBlacklistedTables(array $blacklist)
    {
        $this->blacklist = $blacklist;
    }
    /**
     * Clear the CacheCore state a full reset must drop.
     *
     * @return void
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function resetBackendState()
    {
        $this->sql_tables_cached = [];
        $this->keys = [];
        $this->adjustTableCacheSize = false;
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function isConnected()
    {
        return false;
    }
    /**
     * @throws Exception
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    protected function shouldStoreResult()
    {
        throw new Exception('shouldStoreResult() must be implemented');
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function getTables($string)
    {
        if ($this->queryCacheEngine === null) {
            return parent::getTables($string);
        }
        return $this->queryCacheEngine->getTables($string);
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    protected function isBlacklist($query, $checkUncacheable = false)
    {
        if ($this->queryCacheEngine === null) {
            return parent::isBlacklist($query);
        }
        return $this->queryCacheEngine->isBlacklist($query, $checkUncacheable);
    }
    /**
     * @throws PrestaShopDatabaseException
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function deleteQuery($query)
    {
        if ($this->queryCacheEngine === null) {
            parent::deleteQuery($query);
            return;
        }
        $this->queryCacheEngine->deleteQuery($query);
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    protected function updateQueryCacheStatistics()
    {
        if ($this->queryCacheEngine === null) {
            parent::updateQueryCacheStatistics();
        }
    }
    /**
     * @throws PrestaShopDatabaseException
     * @throws Exception
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function getOrCreate($sql, Db $db)
    {
        if ($this->queryCacheEngine === null) {
            return $db->executeQuery($sql, true);
        }
        return $this->queryCacheEngine->getOrCreate($sql, $db);
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function isCacheEnabled()
    {
        return $this->queryCacheEngine !== null && $this->queryCacheEngine->isCacheEnabled();
    }
    /**
     * Whether the licence the loader granted this shop still authorises the engine.
     *
     * An engine that loaded can still be turned off by the licence — typically when
     * the shop domain is not one of those the licence covers, which the loader only
     * reports through ionCube's own checks, from inside the encoded engine. Without
     * this, that shop silently loses its query cache with nothing shown in the back
     * office: hasQueryCacheEngine() answers true and the module's own expiry check
     * (FILE_EXPIRY) is still in the future.
     *
     * Answers true when there is no engine: that case has its own, more precise
     * report through hasQueryCacheEngine(), and duplicating it here would show the
     * merchant two warnings for one cause.
     *
     * @return bool
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function hasValidQueryCacheLicence()
    {
        return $this->queryCacheEngine === null || $this->queryCacheEngine->isLicenceValid();
    }
    /**
     * Whether cache invalidation (table-counter bumping) must run in the current process.
     *
     * Unlike isCacheEnabled(), this intentionally does NOT require the cache backend to be
     * connected. Invalidation is DB-backed (counters in softizy_query_cache_table_config), so
     * it must happen even from processes where the backend is unavailable — typically CLI/cron
     * where APCu is disabled (apcu.enable_cli=0) and isConnected() returns false. Otherwise
     * writes performed from CLI (e.g. stock imports) would never invalidate the cache served to
     * web (FPM) requests, leaving stale data until a manual flush.
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function isInvalidationEnabled()
    {
        return $this->queryCacheEngine !== null && $this->queryCacheEngine->isInvalidationEnabled();
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function resetCache()
    {
        if ($this->queryCacheEngine === null) {
            return false;
        }
        return $this->queryCacheEngine->resetCache();
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function discoverTables()
    {
        if ($this->queryCacheEngine === null) {
            return false;
        }
        return $this->queryCacheEngine->discoverTables();
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function getCacheKey($sql)
    {
        if ($this->queryCacheEngine === null) {
            return $this->getQueryHash($sql);
        }
        return $this->queryCacheEngine->getCacheKey($sql);
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function getQueryHashPrefix($query)
    {
        if ($this->queryCacheEngine === null) {
            return '';
        }
        return $this->queryCacheEngine->getQueryHashPrefix($query);
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function getTTL($query)
    {
        if ($this->queryCacheEngine === null) {
            return 0;
        }
        return $this->queryCacheEngine->getTTL($query);
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function getLocalResultCache()
    {
        if ($this->queryCacheEngine === null) {
            return [];
        }
        return $this->queryCacheEngine->getLocalResultCache();
    }
    /**
     * @throws PrestaShopDatabaseException
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function getFromLocalCache($sql)
    {
        if ($this->queryCacheEngine === null) {
            return null;
        }
        return $this->queryCacheEngine->getFromLocalCache($sql);
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function storeInLocalCache($sql, $result)
    {
        if ($this->queryCacheEngine === null) {
            return;
        }
        $this->queryCacheEngine->storeInLocalCache($sql, $result);
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function forceByPassCache()
    {
        return !empty($_SERVER['HTTP_X_BYPASS_QUERY_CACHE']);
    }
}
