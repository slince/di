<?php
/**
 * slince dependency injection component
 * @author Taosikai <taosikai@yeah.net>
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
     * 设置一个建造刮泥
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
            $create = function () use($create)
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
        if (isset($this->_store[$key])) {
            $instance = call_user_func($this->_store[$key]['callback']);
            if ($this->_store[$key]['shared']) {
                $this->_instances[$key] = $instance;
            }
            return $instance;
        } else {
            throw new DependencyInjectionException(sprintf('Can not find binds with class "%s"', $key));
        }
    }

    /**
     * 描述一个建造关系
     *
     * @param string $key            
     * @param boolean $shared            
     * @return Definition
     */
    function bind($key, $shared = false)
    {
        $definition = new Definition($key, $this);
        $callback = function () use($definition)
        {
            $definition->newInstance();
        };
        $this->set($key, $callback, $shared);
        return $definition;
    }

    /**
     * 自动获取实例并解决简单的依赖关系
     *
     * @param string $class            
     * @throws DependencyInjectionException
     * @return object
     */
    function newInstance($class)
    {
        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new DependencyInjectionException(sprintf('The class "%s" is invalid', $key));
        }
        $constructor = $reflection->getConstructor();
        if (! is_null($constructor)) {
            $params = $constructor->getParameters();
            $constructorArgs = [];
            foreach ($params as $param) {
                $class = $param->getClass();
                if (! is_null($class)) {
                    $constructorArgs[] = $class->newInstance();
                } elseif ($param->isOptional()) {
                    $constructorArgs[] = $class->getDefaultValue();
                }
            }
            return $reflection->newInstanceArgs($constructorArgs);
        } else {
            return $reflection->newInstanceWithoutConstructor();
        }
    }

    /**
     * 处理alias
     *
     * @param string $key            
     * @return string
     */
    private function _getKey($key)
    {
        return isset($this->_aliases[$key]) ? $this->_aliases[$key] : $key;
    }
}