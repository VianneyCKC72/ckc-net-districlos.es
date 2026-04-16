<?php

$orig = _PS_OVERRIDE_DIR_ . 'classes/HookScalizyOverride.php';
if (is_file($orig)) {
    require_once $orig;
} else {
    // If renamed file is not present yet, fall back to HookCore
    if (!class_exists('HookScalizyOverride', false)) {
        class_alias('HookCore', 'HookScalizyOverride');
    }
}

class Hook extends HookScalizyOverride
{
    /** @var \Softizy\Module\Scalizy\Service\Profiler\Component\Software|false|null */
    private static $softwareStreamer = false;
    /**
     * @var bool|null
     */
    private static $dynamicFrontOfficeEnabled = null;

    /**
     * @var array|null
     */
    private static $hooksManaged;

<<<<<<< HEAD
=======
    /**
     * Cache statique pour getAllHookIds - évite appels répétés au cache PrestaShop
     * @var array
     */
    private static $all_hook_ids_cache = [];

    /**
     * Override de getAllHookIds avec cache statique pour optimiser les 40+ appels répétés
     * Requête UNION lourde appelée massivement (232ms pour 40 appels)
     */
    private static function getAllHookIds(bool $withAliases = false, bool $refreshCache = false): array
    {
        // Clé de cache statique
        $static_key = $withAliases ? 'with_aliases' : 'no_aliases';

        // Vérifier le cache statique en mémoire (ultra-rapide)
        if (!$refreshCache && isset(self::$all_hook_ids_cache[$static_key])) {
            return self::$all_hook_ids_cache[$static_key];
        }

        // Appeler la méthode parente qui utilise déjà Cache::store/retrieve
        $result = parent::getAllHookIds($withAliases, $refreshCache);

        // Stocker dans le cache statique pour les appels suivants dans la même requête HTTP
        self::$all_hook_ids_cache[$static_key] = $result;

        return $result;
    }

>>>>>>> origin/dev
    public static function coreCallHook($module, $method, $params)
    {
        $timeStart = microtime(true);
        $memoryStart = memory_get_usage();

        if (null === self::$hooksManaged && class_exists('Scalizy') && Scalizy::$isLoaded === true) {
            self::$hooksManaged = \Softizy\Module\Scalizy\Model\ScalizyHookManager::getAll();
        }

        if (null !== self::$hooksManaged) {
            foreach (self::$hooksManaged as $hookManaged) {
                if (strpos($hookManaged['hook_name'], $method)) {
                    Hook::exec('actionRegisterScalizyEvent', [
                        $hookManaged['event_name'],
                        $hookManaged['event_category'],
                        ['params' => \Softizy\Module\Scalizy\Model\ScalizyHookManager::filterHookParams($params, $hookManaged['hook_params'])],
                    ]);
                }
            }
        }

        $result = parent::coreCallHook($module, $method, $params);

        $timeEnd = microtime(true);
        $memoryEnd = memory_get_usage();

        if ($softwareStreamer = self::getSoftwareStreamer()) {
            $timeToExecute = $timeEnd - $timeStart;
            $memoryPeak = $memoryEnd - $memoryStart;
            $hookName = substr($method, 4);

            $softwareStreamer->process($hookName, $module->name, $method, $params, $timeToExecute, $memoryPeak, $timeStart);
        }

        if (self::isDynamicFrontOfficeEnabled()) {
            if (!empty($result) && !is_array($result) && strpos($method, 'hookDisplay') === 0) {
                return \Softizy\Module\DynamicFrontOffice\Service\HookDisplayWrapper::generateDynamicContent($result, $method, $params, $module);
            }
        }

        return $result;
    }

    public static function coreRenderWidget($module, $registeredHookName, $params, $forceResult = false)
    {
        $timeStart = microtime(true);
        $memoryStart = memory_get_usage();

        $result = parent::coreRenderWidget($module, $registeredHookName, $params);

        $timeEnd = microtime(true);
        $memoryEnd = memory_get_usage();

        /*
         * It's not a hook which has been triggered but
         * it's a widget
         */
        if (empty($registeredHookName)) {
            $registeredHookName = 'renderWidget';
        }

        if ($softwareStreamer = self::getSoftwareStreamer()) {
            $moduleName = $module->name;
            $timeToExecute = $timeEnd - $timeStart;
            $memoryPeak = $memoryEnd - $memoryStart;
            $hookName = substr($registeredHookName, 4);

            $softwareStreamer->process($hookName, $moduleName, $registeredHookName, $params, $timeToExecute, $memoryPeak, $timeStart);
        }

        if (self::isDynamicFrontOfficeEnabled()) {
            if (!$forceResult && !empty($result) && strpos($registeredHookName, 'display') === 0) {
                return \Softizy\Module\DynamicFrontOffice\Service\HookDisplayWrapper::generateDynamicContent($result, $registeredHookName, $params, $module);
            }
        }

        return $result;
    }

    /**
     * @return \Softizy\Module\Scalizy\Service\Profiler\Component\Software|null
     */
    private static function getSoftwareStreamer(): ?Softizy\Module\Scalizy\Service\Profiler\Component\Software
    {
        // false = not checked yet, null = module not found (cached), object = found (cached)
        if (self::$softwareStreamer === false) {
            // Don't cache if module not loaded yet - it might load later in the request
            if (!class_exists('Scalizy') || Scalizy::$isLoaded !== true) {
                return null;
            }

            /** @var Scalizy|false $scalizy */
            $scalizy = Module::getInstanceByName('scalizy');

            if (false === $scalizy) {
                // Cache null only when module truly doesn't exist (avoid repeated getInstanceByName)
                self::$softwareStreamer = null;

                return null;
            }

            /**
             * @var \Softizy\Module\Scalizy\Service\Profiler\Streamer|null $streamManager
             * @var Scalizy $scalizy
             */
            $streamManager = $scalizy->getStreamManager();
            if (null !== $streamManager && false !== $streamManager->isStarted()) {
                $component = $streamManager
                    ->streamComponents
                    ->offsetGet(\Softizy\Module\Scalizy\Service\Profiler\Component\Software::getIndex());

                if ($component instanceof \Softizy\Module\Scalizy\Service\Profiler\Component\Software) {
                    self::$softwareStreamer = $component;
                }
            }
            // If stream not started yet, keep $softwareStreamer as false (will retry next time)
        }

        // Return null if false (not started) or null (module not found), otherwise return the component
        return self::$softwareStreamer === false ? null : self::$softwareStreamer;
    }

    private static function isDynamicFrontOfficeEnabled(): bool
    {
        if (!class_exists('SoftizyDynamicFrontOffice', false)) {
            return false;
        }

        if (null === self::$dynamicFrontOfficeEnabled) {
            self::$dynamicFrontOfficeEnabled = (bool) Module::isEnabled('softizydynamicfrontoffice');

            if (true === self::$dynamicFrontOfficeEnabled) {
                $module = Module::getInstanceByName('softizydynamicfrontoffice');
                self::$dynamicFrontOfficeEnabled = (bool) version_compare($module->version, '1.2.0', '>=');
            }
        }

        return self::$dynamicFrontOfficeEnabled;
    }
}
