<?php

class EntityManager_9a5be93 extends \Doctrine\ORM\EntityManager implements \ProxyManager\Proxy\VirtualProxyInterface
{
    private $valueHolder6f19b = null;
    private $initializer4ebb9 = null;
    private static $publicProperties092eb = [
        
    ];
    public function getConnection()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getConnection', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getConnection();
    }
    public function getMetadataFactory()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getMetadataFactory', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getMetadataFactory();
    }
    public function getExpressionBuilder()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getExpressionBuilder', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getExpressionBuilder();
    }
    public function beginTransaction()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'beginTransaction', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->beginTransaction();
    }
    public function getCache()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getCache', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getCache();
    }
    public function transactional($func)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'transactional', array('func' => $func), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->transactional($func);
    }
    public function wrapInTransaction(callable $func)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'wrapInTransaction', array('func' => $func), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->wrapInTransaction($func);
    }
    public function commit()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'commit', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->commit();
    }
    public function rollback()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'rollback', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->rollback();
    }
    public function getClassMetadata($className)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getClassMetadata', array('className' => $className), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getClassMetadata($className);
    }
    public function createQuery($dql = '')
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'createQuery', array('dql' => $dql), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->createQuery($dql);
    }
    public function createNamedQuery($name)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'createNamedQuery', array('name' => $name), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->createNamedQuery($name);
    }
    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'createNativeQuery', array('sql' => $sql, 'rsm' => $rsm), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->createNativeQuery($sql, $rsm);
    }
    public function createNamedNativeQuery($name)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'createNamedNativeQuery', array('name' => $name), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->createNamedNativeQuery($name);
    }
    public function createQueryBuilder()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'createQueryBuilder', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->createQueryBuilder();
    }
    public function flush($entity = null)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'flush', array('entity' => $entity), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->flush($entity);
    }
    public function find($className, $id, $lockMode = null, $lockVersion = null)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'find', array('className' => $className, 'id' => $id, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->find($className, $id, $lockMode, $lockVersion);
    }
    public function getReference($entityName, $id)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getReference', array('entityName' => $entityName, 'id' => $id), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getReference($entityName, $id);
    }
    public function getPartialReference($entityName, $identifier)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getPartialReference', array('entityName' => $entityName, 'identifier' => $identifier), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getPartialReference($entityName, $identifier);
    }
    public function clear($entityName = null)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'clear', array('entityName' => $entityName), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->clear($entityName);
    }
    public function close()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'close', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->close();
    }
    public function persist($entity)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'persist', array('entity' => $entity), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->persist($entity);
    }
    public function remove($entity)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'remove', array('entity' => $entity), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->remove($entity);
    }
    public function refresh($entity)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'refresh', array('entity' => $entity), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->refresh($entity);
    }
    public function detach($entity)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'detach', array('entity' => $entity), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->detach($entity);
    }
    public function merge($entity)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'merge', array('entity' => $entity), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->merge($entity);
    }
    public function copy($entity, $deep = false)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'copy', array('entity' => $entity, 'deep' => $deep), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->copy($entity, $deep);
    }
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'lock', array('entity' => $entity, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->lock($entity, $lockMode, $lockVersion);
    }
    public function getRepository($entityName)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getRepository', array('entityName' => $entityName), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getRepository($entityName);
    }
    public function contains($entity)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'contains', array('entity' => $entity), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->contains($entity);
    }
    public function getEventManager()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getEventManager', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getEventManager();
    }
    public function getConfiguration()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getConfiguration', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getConfiguration();
    }
    public function isOpen()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'isOpen', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->isOpen();
    }
    public function getUnitOfWork()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getUnitOfWork', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getUnitOfWork();
    }
    public function getHydrator($hydrationMode)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getHydrator', array('hydrationMode' => $hydrationMode), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getHydrator($hydrationMode);
    }
    public function newHydrator($hydrationMode)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'newHydrator', array('hydrationMode' => $hydrationMode), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->newHydrator($hydrationMode);
    }
    public function getProxyFactory()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getProxyFactory', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getProxyFactory();
    }
    public function initializeObject($obj)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'initializeObject', array('obj' => $obj), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->initializeObject($obj);
    }
    public function getFilters()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'getFilters', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->getFilters();
    }
    public function isFiltersStateClean()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'isFiltersStateClean', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->isFiltersStateClean();
    }
    public function hasFilters()
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, 'hasFilters', array(), $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        return $this->valueHolder6f19b->hasFilters();
    }
    public static function staticProxyConstructor($initializer)
    {
        static $reflection;
        $reflection = $reflection ?? new \ReflectionClass(__CLASS__);
        $instance   = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection, $instance->cache);
        }, $instance, 'Doctrine\\ORM\\EntityManager')->__invoke($instance);
        $instance->initializer4ebb9 = $initializer;
        return $instance;
    }
    protected function __construct(\Doctrine\DBAL\Connection $conn, \Doctrine\ORM\Configuration $config, \Doctrine\Common\EventManager $eventManager)
    {
        static $reflection;
        if (! $this->valueHolder6f19b) {
            $reflection = $reflection ?? new \ReflectionClass('Doctrine\\ORM\\EntityManager');
            $this->valueHolder6f19b = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection, $instance->cache);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
        }
        $this->valueHolder6f19b->__construct($conn, $config, $eventManager);
    }
    public function & __get($name)
    {
        $this->initializer4ebb9 && ($this->initializer4ebb9->__invoke($valueHolder6f19b, $this, '__get', ['name' => $name], $this->initializer4ebb9) || 1) && $this->valueHolder6f19b = $valueHolder6f19b;
        if (isset(self::$publicProperties092eb[$name])) {
            return $this->valueHolder6f19b->$name;
        }
        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');
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
        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');
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
        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');
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
        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');
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
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection, $instance->cache);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
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
