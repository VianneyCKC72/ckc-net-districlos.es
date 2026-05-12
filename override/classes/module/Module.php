<?php
abstract class Module extends ModuleCore
{
    private static $module_id_by_name_cache = [];
    public static function getModuleIdByName($name)
    {
        $static_key = pSQL($name);
        if (isset(self::$module_id_by_name_cache[$static_key])) {
            return self::$module_id_by_name_cache[$static_key];
        }
        $result = parent::getModuleIdByName($name);
        self::$module_id_by_name_cache[$static_key] = $result;
        return $result;
    }
    
    /*
    * module: scalizy
    * date: 2026-05-12 09:39:30
    * version: 3.17.1
    */
    public function installOverrides()
    {
        if (\Softizy\Module\Scalizy\Service\ScalizyConfiguration::getInstance()->isModuleOverrideInstallationDisabled()) {
            return true;
        }
        return parent::installOverrides();
    }
    /*
    * module: scalizy
    * date: 2026-05-12 09:39:30
    * version: 3.17.1
    */
    public function uninstallOverrides()
    {
        if (\Softizy\Module\Scalizy\Service\ScalizyConfiguration::getInstance()->isModuleOverrideInstallationDisabled()) {
            return true;
        }
        return parent::uninstallOverrides();
    }
    /*
    * module: scalizy
    * date: 2026-05-12 09:39:30
    * version: 3.17.1
    */
    public function addOverride($classname)
    {
        if (\Softizy\Module\Scalizy\Service\ScalizyConfiguration::getInstance()->isModuleOverrideInstallationDisabled()) {
            return true;
        }
        \Softizy\Module\Scalizy\Install\OverrideInstaller::removeOverride($classname);
        $result = parent::addOverride($classname);
        \Softizy\Module\Scalizy\Install\OverrideInstaller::installOverride($classname);
        return $result;
    }
    /*
    * module: scalizy
    * date: 2026-05-12 09:39:30
    * version: 3.17.1
    */
    public function removeOverride($classname)
    {
        if (\Softizy\Module\Scalizy\Service\ScalizyConfiguration::getInstance()->isModuleOverrideInstallationDisabled()) {
            return true;
        }
        \Softizy\Module\Scalizy\Install\OverrideInstaller::removeOverride($classname);
        $result = parent::removeOverride($classname);
        \Softizy\Module\Scalizy\Install\OverrideInstaller::installOverride($classname);
        return $result;
    }
}
