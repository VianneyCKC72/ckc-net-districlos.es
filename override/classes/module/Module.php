<?php
abstract class Module extends ModuleCore
{

    // Cache statique en mémoire pour éviter même les appels au système de cache PrestaShop
    private static $module_id_by_name_cache = [];

    /**
     * Override de getModuleIdByName avec double cache (statique + PrestaShop)
     * Requête appelée massivement depuis de nombreux modules et contrôleurs
     */
    public static function getModuleIdByName($name)
    {
        // Clé de cache statique
        $static_key = pSQL($name);

        // Vérifier le cache statique en mémoire (ultra-rapide)
        if (isset(self::$module_id_by_name_cache[$static_key])) {
            return self::$module_id_by_name_cache[$static_key];
        }

        // Appeler la méthode parente qui utilise déjà Cache::store/retrieve
        $result = parent::getModuleIdByName($name);

        // Stocker dans le cache statique pour les appels suivants dans la même requête HTTP
        self::$module_id_by_name_cache[$static_key] = $result;

        return $result;
    }
    
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
