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
*
*  @author Jocelyn Fournier <jocelyn.fournier@softizy.com>
*  @copyright Softizy SARL
*  @license    commercial
*  International Registred Trademark & Property of Softizy SARL
*/
class CacheMemcached extends CacheMemcachedCore
{
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function __construct()
    {
        parent::__construct();
        if (method_exists(Cache::class, 'bootQueryCacheEngine')) {
            Cache::__construct();
        }
        $this->setMemcachedOptions();
    }
    /**
     * An unreachable memcached is NOT a reason to turn the query cache off as a
     * whole: isCacheEnabled() already requires isConnected(), so reads and writes
     * to the store are covered, while invalidation is DB-backed and must keep
     * running (see Cache::isInvalidationEnabled()). Disabling everything here
     * meant a CLI/cron process with memcached down stopped bumping the table
     * counters, leaving web requests serving stale data until a manual flush.
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    private function setMemcachedOptions()
    {
        if (!$this->isConnected()) {
            return;
        }
        $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        $this->memcached->setOption(\Memcached::OPT_TCP_NODELAY, true);
        $this->memcached->setOption(\Memcached::OPT_NO_BLOCK, true);
        $this->memcached->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    protected function shouldStoreResult()
    {
        $memcachedResultCode = $this->memcached->getResultCode();
        return $memcachedResultCode === \Memcached::RES_NOTFOUND ||
            $memcachedResultCode === \Memcached::RES_DELETED;
    }
    /**
     * Get list of memcached servers.
     *
     * @return array
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public static function getMemcachedServers()
    {
        return Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'softizy_query_cache_memcached_servers', true, false);
    }
    /**
     * Connect to memcached server.
     */
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function connect()
    {
        if (class_exists('Memcached') && extension_loaded('memcached')) {
            $this->memcached = new Memcached();
        } else {
            return;
        }
        $servers = self::getMemcachedServers();
        if (!$servers) {
            return;
        }
        foreach ($servers as $server) {
            $this->memcached->addServer($server['ip'], $server['port'], (int) $server['weight']);
        }
        $versions = $this->memcached->getVersion();
        $this->is_connected = in_array('255.255.255', $versions, true) === false;
    }
}
