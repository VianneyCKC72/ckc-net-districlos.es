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
class CacheApc extends CacheApcCore
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
    }
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    public function isConnected()
    {
        return apcu_enabled();
    }
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    protected function shouldStoreResult()
    {
        return true;
    }
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    protected function _set($key, $value, $ttl = 0)
    {
        if (!$this->isConnected()) {
            return false;
        }
        return ($this->apcu) ? (bool) apcu_store($key, $value, $ttl) : (bool) apc_store($key, $value, $ttl);
    }
}
