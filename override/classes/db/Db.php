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
abstract class Db extends DbCore
{
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    protected $lastInsertId = null;
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    protected $inCacheInvalidation = false;
    /**
     * Executes a DELETE query.
     *
     * @param string $table Name of the table to delete
     * @param string $where WHERE clause on query
     * @param int $limit Number max of rows to delete
     * @param bool $use_cache Use cache or not
     * @param bool $add_prefix Add or not _DB_PREFIX_ before table name
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    public function delete($table, $where = '', $limit = 0, $use_cache = true, $add_prefix = true)
    {
        if ($add_prefix) {
            $table = _DB_PREFIX_ . $table;
        }
        $sql = 'DELETE FROM `' . bqSQL($table) . '`' . ($where ? ' WHERE ' . $where : '') . ($limit ? ' LIMIT ' . (int) $limit : '');
        return (bool) $this->q($sql, $use_cache);
    }
    /**
     * Executes a query.
     *
     * @param string|DbQuery $sql
     * @param bool $use_cache
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    public function execute($sql, $use_cache = true)
    {
        $this->result = $this->q($sql, $use_cache);
        return (bool) $this->result;
    }
    /**
     * Executes return the result of $sql as array.
     *
     * @param string|DbQuery $sql Query to execute
     * @param bool $array Return an array instead of a result object (deprecated since 1.5.0.1, use query method instead)
     * @param bool $use_cache
     *
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     *
     * @throws PrestaShopDatabaseException|PrestaShopException
     */
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    public function executeS($sql, $array = true, $use_cache = true)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }
        $regExp = '/^\s*SELECT\s+LAST_INSERT_ID\(\s*\)\s*/i';
        if (preg_match($regExp, $sql)) {
            $insertId = $this->Insert_ID();
            $sql = preg_replace($regExp, 'SELECT ' . (int) $insertId . ' ', $sql);
        }
        if (!preg_match('#^\s*\(?\s*(select|show|explain|describe|desc|checksum)\s#i', $sql)
            || stripos($sql, 'outfile') !== false
            || stripos($sql, 'dumpfile') !== false) {
            return $this->execute($sql, $use_cache);
        }
        $this->result = false;
        $this->last_query = $sql;
        if (method_exists(Cache::class, 'getOrCreate') && $use_cache && $this->is_cache_enabled && $array) {
            $result = Cache::getInstance()->getOrCreate($sql, $this);
        } else {
            $result = $this->executeQuery($sql, $array);
        }
        return $result;
    }
    /**
     * Execute a sql query and properly set the class flags
     *
     * @param string $sql
     * @param bool $array
     *
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     *
     * @throws PrestaShopDatabaseException
     */
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    public function executeQuery($sql, $array)
    {
        $this->result = $this->query($sql);
        if ($this->result === false) {
            return false;
        }
        if ($array) {
            return $this->getAll($this->result);
        } else {
            return $this->result;
        }
    }
    /**
     * Returns an associative array containing the first row of the query
     * This function automatically adds "LIMIT 1" to the query.
     *
     * @param string|DbQuery $sql the select query (without "LIMIT 1")
     * @param bool $use_cache Find it in cache first
     *
     * @return array|false
     *
     * @throws PrestaShopException
     */
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    public function getRow($sql, $use_cache = true)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }
        $sql = rtrim($sql, " \t\n\r\0\x0B;") . ' LIMIT 1';
        $result = $this->executeS($sql, true, $use_cache);
        return $result ? $result[0] : false;
    }
    /**
     * Get number of rows for last result.
     *
     * @return int
     */
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    public function numRows()
    {
        if ($this->result) {
            return $this->_numRows($this->result);
        }
        return 0;
    }
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    public static function isDDL($sql)
    {
        $normalizedQuery = strtolower(preg_replace('/\s+/', ' ', trim($sql)));
        $ddlKeywords = ['alter', 'drop', 'truncate', 'comment', 'rename'];
        foreach ($ddlKeywords as $keyword) {
            if (strpos($normalizedQuery, $keyword . ' ') === 0) {
                return true;
            }
        }
        return false;
    }
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    protected function q($sql, $use_cache = true)
    {
        if (!$this->inCacheInvalidation) {
            $this->lastInsertId = null;
        }
        $this->result = false;
        $result = $this->query($sql);
        if ($this->is_cache_enabled && !$this->inCacheInvalidation &&
            method_exists(Cache::class, 'isCacheEnabled') && Cache::getInstance()->isCacheEnabled()) {
            if ($result !== false && ($this->Affected_Rows() > 0 || self::isDDL($sql))) {
                $this->Insert_ID();
                $this->inCacheInvalidation = true;
                Cache::getInstance()->deleteQuery($sql);
                $this->inCacheInvalidation = false;
            }
        }
        if (_PS_DEBUG_SQL_) {
            $this->displayError($sql);
        }
        $this->result = $result;
        return $result;
    }
    /*
    * module: softizyquerycache
    * date: 2026-04-22 11:53:49
    * version: 2.30.5
    */
    public function disableCache()
    {
    }
}
