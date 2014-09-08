<?php
/**
 * slince dependency injection component
 * @author Taosikai <taosikai@yeah.net>
 */
namespace Slince\Di;

class Container
{
    private $_aliases = [];
    private $_instances = [];
    private $_store = [];

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
    
    function alias($alias, $key)
    {
        $this->_aliases[$alias] = $key;
    }

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
		}
	}
	function bind($key, $shared = false)
	{
		$definition = new Definition($key, $this);
		$callback = function () use ($definition) {
		    $definition->newInstance();
		};
		$this->set($key, $callback, $shared);
		return $definition;
	}
	private function _getKey($key)
	{
		return isset($this->_aliases[$alias]) ?  $this->_aliases[$alias] : $key;
	}
	
	/**
	 * 自动获取类实例
	 */
	function newInstance($class)
	{
	    try {
		    $reflection = new \ReflectionClass($key);
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
}