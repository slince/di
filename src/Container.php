<?php
/**
 * slince dependency injection component
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

use Slince\Di\Exception\DependencyInjectionException;

class Container
{

    /**
     * 别名数组
     *
     * @var array
     */
    private $_aliases = [];

    /**
     * 实例数组
     *
     * @var array
     */
    private $_instances = [];

    /**
     * 实例创建关系数组
     *
     * @var array
     */
    private $_store = [];

    /**
     * 设置一个建造关系
     *
     * @param string $key            
     * @param object|\Closure $create            
     * @param boolean $shared            
     * @return Container
     */
    function set($key, $create, $shared = false)
    {
        $callback = '';
        if (! $create instanceof \Closure) {
            $create = function () use ($create)
            {
                return $create;
            };
        }
        $this->_store[$key] = [
            'callback' => $create,
            'shared' => $shared
        ];
        return $this;
    }

    /**
     * 设置一个共享关系
     *
     * @param string $key            
     * @param object|\Closure $create            
     */
    function share($key, $create)
    {
        $this->set($key, $create, true);
    }

    /**
     * 设置一个别名
     *
     * @param string $alias            
     * @param string $key            
     */
    function alias($alias, $key)
    {
        $this->_aliases[$alias] = $key;
    }

    /**
     * 获取一个实例
     *
     * @param string $key            
     * @return object
     */
    function get($key)
    {
        $key = $this->_getKey($key);
        if (isset($this->_instances[$key])) {
            return $this->_instances[$key];
        }
        if (! isset($this->_store[$key])) {
            $this->set($key, function () use($key)
            {
                return $this->create($key);
            });
        }
        $instance = call_user_func($this->_store[$key]['callback']);
        if ($this->_store[$key]['shared']) {
            $this->_instances[$key] = $instance;
        }
        return $instance;
    }

    /**
     * 描述一个建造关系
     *
     * @param string $key            
     * @param boolean $shared            
     * @return Definition
     */
    function describe($key, $shared = false)
    {
        $definition = new Definition($key);
        $callback = function () use($definition)
        {
            return $this->createFromDefinition($definition);
        };
        $this->set($key, $callback, $shared);
        return $definition;
    }

    /**
     * 自动获取实例并解决简单的依赖关系
     * 并不能解决非类依赖，如有需要请使用类定义
     *
     * @param string $className            
     * @throws DependencyInjectionException
     * @return object
     */
    function create($className, $params = [])
    {
        $reflection = $this->reflectClass($className);
        $constructor = $reflection->getConstructor();
        if (! is_null($constructor)) {
            $constructorArgs = $this->_resolveConstructArgs($constructor, $params);
            return $reflection->newInstanceArgs($constructorArgs);
        } else {
            return $reflection->newInstanceWithoutConstructor();
        }
    }
    
    function createFromDefinition(Definition $definition)
    {
        $params = $definition->getArgs();
        $instance = $this->create($definition->getClassName(), $params);
        // 触发setter函数
        foreach ($definition->getCalls() as $method => $value) {
            try {
                $methodReflection = $reflection->getMethod($method);
            } catch (\ReflectionException $e) {
                throw new DependencyInjectionException(sprintf('Class "%s" dont have method "%s"', $definition->getClassName(), $method));
            }
            $methodReflection->invoke($instance, $value);
        }
        return $instance;
    }

    /**
     * 处理构造方法所需要的参数
     * @param \ReflectionMethod $constructor
     * @param array $params
     * @throws DependencyInjectionException
     * @return array
     */
    protected function _resolveConstructArgs(\ReflectionMethod $constructor, array $params)
    {
        $constructorArgs = [];
        foreach ($constructor->getParameters() as $param) {
            $varName = $param->getName();
            // 如果定义过依赖 则直接获取
            if (isset($params[$varName])) {
                $constructorArgs[] = $params[$varName];
            } elseif ($param instanceof DependencyInterface) {
                $constructorArgs[] = $param->getDependency();
            } elseif (($dependency = $param->getClass()) != null) {
                $constructorArgs[] = $this->get($dependency->getName());
            } elseif ($param->isOptional()) {
                $constructorArgs[] = $param->getDefaultValue();
            } else {
                throw new DependencyInjectionException(sprintf('Param "%s" must be provided', $varName));
            }
        }
        return $constructorArgs;
    }
    
    /**
     * 获取类的反射对象
     *
     * @param string $className            
     * @throws DependencyInjectionException
     * @return \ReflectionClass
     */
    protected function _reflectClass($className)
    {
        try {
            $reflection = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new DependencyInjectionException(sprintf('Class "%s" is invalid', $className));
        }
        return $reflection;
    }

    /**
     * 处理alias
     *
     * @param string $key            
     * @return string
     */
    protected function _getKey($key)
    {
        return isset($this->_aliases[$key]) ? $this->_aliases[$key] : $key;
    }
}