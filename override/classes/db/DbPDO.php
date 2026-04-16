<?php
class DbPDO extends DbPDOCore
{
    
    /*
    * module: scalizy
    * date: 2026-04-16 13:06:36
    * version: 3.16.2
    */
    private static $databaseStreamer = false;
    /*
    * module: scalizy
    * date: 2026-04-16 13:06:36
    * version: 3.16.2
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
    /**
     * Override executeS to sample row counts.
     * This is more reliable than PDOStatement::rowCount().
     *
     * @param string|\DbQuery $sql
     * @param bool $array
     * @param bool $use_cache
     *
     * @return array<int, array<string, mixed>>|false
     */
    /*
    * module: scalizy
    * date: 2026-04-16 13:06:36
    * version: 3.16.2
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
    /**
     * @return \Softizy\Module\Scalizy\Service\Profiler\Component\Database|null
     */
    /*
    * module: scalizy
    * date: 2026-04-16 13:06:36
    * version: 3.16.2
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
}
