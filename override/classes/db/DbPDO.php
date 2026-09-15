<?php
class DbPDO extends DbPDOCore
{
    
    /*
    * module: scalizy
    * date: 2026-05-12 09:39:30
    * version: 3.17.1
    */
    private static $databaseStreamer = false;
    /*
    * module: scalizy
    * date: 2026-05-12 09:39:30
    * version: 3.17.1
    */
    protected function _query($sql)
    {
        $databaseStreamer = $this->getDatabaseStreamer();
        if (null === $databaseStreamer) {
            return parent::_query($sql);
        }
        $start = microtime(true);
        $queryResult = parent::_query($sql);
        $end = microtime(true);
        $timeToExecute = $end - $start;
        $databaseStreamer->addQuery($sql, $timeToExecute);
        return $queryResult;
    }
    /*
    * module: scalizy
    * date: 2026-05-12 09:39:30
    * version: 3.17.1
    */
    public function executeS($sql, $array = true, $use_cache = true)
    {
        $result = parent::executeS($sql, $array, $use_cache);
        $databaseStreamer = $this->getDatabaseStreamer();
        if (null !== $databaseStreamer && is_array($result)) {
            $sqlString = ($sql instanceof \DbQuery) ? $sql->build() : $sql;
            $databaseStreamer->sampleRowCountFromResult($sqlString, count($result));
        }
        return $result;
    }
    /*
    * module: scalizy
    * date: 2026-05-12 09:39:30
    * version: 3.17.1
    */
    private function getDatabaseStreamer(): ?Softizy\Module\Scalizy\Service\Profiler\Component\Database
    {
        if (self::$databaseStreamer === false) {
            if (!class_exists('Scalizy') || Scalizy::$isLoaded !== true) {
                return null;
            }
            
            $scalizy = Module::getInstanceByName('scalizy');
            if (false === $scalizy) {
                self::$databaseStreamer = null;
                return null;
            }
            
            $streamManager = $scalizy->getStreamManager();
            if (null !== $streamManager && false !== $streamManager->isStarted()) {
                $component = $streamManager
                    ->streamComponents
                    ->offsetGet(\Softizy\Module\Scalizy\Service\Profiler\Component\Database::getIndex());
                if ($component instanceof \Softizy\Module\Scalizy\Service\Profiler\Component\Database) {
                    self::$databaseStreamer = $component;
                }
            }
        }
        return self::$databaseStreamer === false ? null : self::$databaseStreamer;
    }
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public $lastInsertId;
    /*
    * module: softizyquerycache
    * date: 2026-09-02 18:22:21
    * version: 2.32.2
    */
    public function Insert_ID()
    {
        if ($this->lastInsertId === null) {
            $this->lastInsertId = $this->link->lastInsertId();
        }
        return $this->lastInsertId;
    }
}
