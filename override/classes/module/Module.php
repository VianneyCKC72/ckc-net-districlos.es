<?php
abstract class Module extends ModuleCore
{
    /*
    * module: scalizy
    * date: 2026-04-16 13:06:36
    * version: 3.16.2
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
    * date: 2026-04-16 13:06:36
    * version: 3.16.2
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
    * date: 2026-04-16 13:06:36
    * version: 3.16.2
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
    * date: 2026-04-16 13:06:36
    * version: 3.16.2
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
