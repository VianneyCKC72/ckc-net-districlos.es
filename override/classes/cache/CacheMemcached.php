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
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    public function __construct()
    {
        parent::__construct();
        Cache::__construct();
        $this->setMemcachedOptions();
    }
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    private function setMemcachedOptions()
    {
        if ($this->isConnected()) {
            $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $this->memcached->setOption(\Memcached::OPT_TCP_NODELAY, true);
            $this->memcached->setOption(\Memcached::OPT_NO_BLOCK, true);
            $this->memcached->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        } else {
            $this->cacheEnabled = false;
        }
    }
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
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
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
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
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
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
