<?php

class ModuleRepository_091bb2f extends \PrestaShop\PrestaShop\Core\Module\ModuleRepository implements \ProxyManager\Proxy\VirtualProxyInterface
{
    private $valueHolder6f19b = null;
    private $initializer4ebb9 = null;
    private static $publicProperties092eb = [
        
    ];
    public function getList() : \PrestaShop\PrestaShop\Core\Module\ModuleCollection
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getList', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getList();
    }
    public function getInstalledModules() : \PrestaShop\PrestaShop\Core\Module\ModuleCollection
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getInstalledModules', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getInstalledModules();
    }
    public function getMustBeConfiguredModules() : \PrestaShop\PrestaShop\Core\Module\ModuleCollection
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getMustBeConfiguredModules', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getMustBeConfiguredModules();
    }
    public function getUpgradableModules() : \PrestaShop\PrestaShop\Core\Module\ModuleCollection
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getUpgradableModules', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getUpgradableModules();
    }
    public function getModule(string $moduleName) : \PrestaShop\PrestaShop\Core\Module\ModuleInterface
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getModule', array('moduleName' => $moduleName), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getModule($moduleName);
    }
    public function getModulePath(string $moduleName) : ?string
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getModulePath', array('moduleName' => $moduleName), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getModulePath($moduleName);
    }
    public function setActionUrls(\PrestaShop\PrestaShop\Core\Module\ModuleCollection $collection) : \PrestaShop\PrestaShop\Core\Module\ModuleCollection
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'setActionUrls', array('collection' => $collection), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->setActionUrls($collection);
    }
    public function clearCache(?string $moduleName = null, bool $allShops = false) : bool
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'clearCache', array('moduleName' => $moduleName, 'allShops' => $allShops), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->clearCache($moduleName, $allShops);
    }
    public static function staticProxyConstructor($initializer)
    {
        static $reflection;
        $reflection = $reflection ?? new \ReflectionClass(__CLASS__);
        $instance   = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\PrestaShop\PrestaShop\Core\Module\ModuleRepository $instance) {
            unset($instance->moduleDataProvider, $instance->adminModuleDataProvider, $instance->hookManager, $instance->cacheProvider, $instance->modulePath, $instance->installedModules, $instance->modulesFromHook, $instance->contextLangId);
        }, $instance, 'PrestaShop\\PrestaShop\\Core\\Module\\ModuleRepository')->__invoke($instance);
        $instance->initializer4ebb9 = $initializer;
        return $instance;
    }
    public function __construct(\PrestaShop\PrestaShop\Adapter\Module\ModuleDataProvider $moduleDataProvider, \PrestaShop\PrestaShop\Adapter\Module\AdminModuleDataProvider $adminModuleDataProvider, \Doctrine\Common\Cache\CacheProvider $cacheProvider, \PrestaShop\PrestaShop\Adapter\HookManager $hookManager, string $modulePath, int $contextLangId)
    {
        static $reflection;
        if (! $this->valueHolder6f19b) {
            $reflection = $reflection ?? new \ReflectionClass('PrestaShop\\PrestaShop\\Core\\Module\\ModuleRepository');
            $this->valueHolder6f19b = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\PrestaShop\PrestaShop\Core\Module\ModuleRepository $instance) {
            unset($instance->moduleDataProvider, $instance->adminModuleDataProvider, $instance->hookManager, $instance->cacheProvider, $instance->modulePath, $instance->installedModules, $instance->modulesFromHook, $instance->contextLangId);
        }, $this, 'PrestaShop\\PrestaShop\\Core\\Module\\ModuleRepository')->__invoke($this);
        }
        $this->valueHolder6f19b->__construct($moduleDataProvider, $adminModuleDataProvider, $cacheProvider, $hookManager, $modulePath, $contextLangId);
    }
    public function & __get($name)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, '__get', ['name' => $name], $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        if (isset(self::$publicProperties092eb[$name])) {
            return $this->valueHolder6f19b->$name;
        }
        $realInstanceReflection = new \ReflectionClass('PrestaShop\\PrestaShop\\Core\\Module\\ModuleRepository');
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder6f19b;
            $backtrace = debug_backtrace(false, 1);
            trigger_error(
                sprintf(
                    'Undefined property: %s::$%s in %s on line %s',
                    $realInstanceReflection->getName(),
                    $name,
                    $backtrace[0]['file'],
                    $backtrace[0]['line']
                ),
                \E_USER_NOTICE
            );
            return $targetObject->$name;
        }
        $targetObject = $this->valueHolder6f19b;
        $accessor = function & () use ($targetObject, $name) {
            return $targetObject->$name;
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __set($name, $value)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, '__set', array('name' => $name, 'value' => $value), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        $realInstanceReflection = new \ReflectionClass('PrestaShop\\PrestaShop\\Core\\Module\\ModuleRepository');
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder6f19b;
            $targetObject->$name = $value;
            return $targetObject->$name;
        }
        $targetObject = $this->valueHolder6f19b;
        $accessor = function & () use ($targetObject, $name, $value) {
            $targetObject->$name = $value;
            return $targetObject->$name;
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __isset($name)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, '__isset', array('name' => $name), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        $realInstanceReflection = new \ReflectionClass('PrestaShop\\PrestaShop\\Core\\Module\\ModuleRepository');
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder6f19b;
            return isset($targetObject->$name);
        }
        $targetObject = $this->valueHolder6f19b;
        $accessor = function () use ($targetObject, $name) {
            return isset($targetObject->$name);
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __unset($name)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, '__unset', array('name' => $name), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        $realInstanceReflection = new \ReflectionClass('PrestaShop\\PrestaShop\\Core\\Module\\ModuleRepository');
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder6f19b;
            unset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder6f19b;
        $accessor = function () use ($targetObject, $name) {
            unset($targetObject->$name);
            return;
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $accessor();
    }
    public function __clone()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, '__clone', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        $this->valueHolder6f19b = clone $this->valueHolder6f19b;
    }
    public function __sleep()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, '__sleep', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return array('valueHolder6f19b');
    }
    public function __wakeup()
    {
        \Closure::bind(function (\PrestaShop\PrestaShop\Core\Module\ModuleRepository $instance) {
            unset($instance->moduleDataProvider, $instance->adminModuleDataProvider, $instance->hookManager, $instance->cacheProvider, $instance->modulePath, $instance->installedModules, $instance->modulesFromHook, $instance->contextLangId);
        }, $this, 'PrestaShop\\PrestaShop\\Core\\Module\\ModuleRepository')->__invoke($this);
    }
    public function setProxyInitializer(\Closure $initializer = null) : void
    {
        $this->initializer4ebb9 = $initializer;
    }
    public function getProxyInitializer() : ?\Closure
    {
        return $this->initializer4ebb9;
    }
    public function initializeProxy() : bool
    {
        return $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'initializeProxy', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
    }
    public function isProxyInitialized() : bool
    {
        return null !== $this->valueHolder6f19b;
    }
    public function getWrappedValueHolderValue()
    {
        return $this->valueHolder6f19b;
    }
}
