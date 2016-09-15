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
     * @var array
     */
    protected $aliases = [];

    /**
     * 实例数组
     * @var array
     */
    protected $instances = [];

    /**
     * 实例创建关系数组
     * @var array
     */
    protected $store = [];

    /**
     * 参数集合
     * @var \ArrayObject
     */
    protected $parameters;

    public function __construct()
    {
        $this->parameters = new \ArrayObject();
    }

    /**
     * 设置一个建造关系
     * @param string $key
     * @param object|\Closure $create
     * @param boolean $share
     * @return Container
     */
    public function set($key, $create, $share = false)
    {
        if (!$create instanceof \Closure) {
            $create = function () use ($create) {
                return $create;
            };
        }
        $this->store[$key] = [
            'callback' => $create,
            'share' => $share
        ];
        return $this;
    }

    /**
     * 如果不能简单获取，则使用设置定义的方式
     * @param string $key
     * @param Definition $definition
     * @param boolean $share
     * @return Definition
     */
    public function setDefinition($key, Definition $definition, $share = false)
    {
        $callback = function () use ($definition) {
            return $this->createFromDefinition($definition);
        };
        $this->set($key, $callback, $share);
        return $definition;
    }

    /**
     * 设置一个共享关系
     * @param string $key
     * @param object|\Closure $create
     */
    public function share($key, $create)
    {
        $this->set($key, $create, true);
    }

    /**
     * 设置一个别名
     * @param string $alias
     * @param string $key
     */
    public function alias($alias, $key)
    {
        $this->aliases[$alias] = $key;
    }

    /**
     * 获取一个实例
     * @param string $key
     * @return object
     */
    public function get($key)
    {
        $key = $this->getKey($key);
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }
        if (!isset($this->store[$key])) {
            $this->share($key, function () use ($key) {
                return $this->create($key);
            });
        }
        $instance = call_user_func($this->store[$key]['callback'], $this);
        if ($this->store[$key]['share']) {
            $this->instances[$key] = $instance;
        }
        return $instance;
    }

    /**
     * 自动获取实例并解决简单的依赖关系
     * @param string $className
     * @param array $arguments
     * @throws DependencyInjectionException
     * @return object
     */
    public function create($className, $arguments = [])
    {
        $reflection = $this->reflectClass($className);
        $constructor = $reflection->getConstructor();
        if (!is_null($constructor)) {
            $constructorArgs = $this->resolveConstructArguments($constructor, $arguments);
            $instance = $reflection->newInstanceArgs($constructorArgs);
        } else {
            $instance = $reflection->newInstanceWithoutConstructor();
        }
        return $instance;
    }

    /**
     * 根据definition创建实例
     * @param Definition $definition
     * @throws DependencyInjectionException
     * @return object
     */
    public function createFromDefinition(Definition $definition)
    {
        $arguments = $definition->getArguments();
        $reflection = $this->reflectClass($definition->getClassName());
        $constructor = $reflection->getConstructor();
        if (!is_null($constructor)) {
            $constructorArgs = $this->resolveConstructArguments($constructor, $arguments);
            $instance = $reflection->newInstanceArgs($constructorArgs);
        } else {
            $instance = $reflection->newInstanceWithoutConstructor();
        }
        // 触发setter函数
        foreach ($definition->getMethodCalls() as $method => $methodArguments) {
            try {
                $methodReflection = $reflection->getMethod($method);
            } catch (\ReflectionException $e) {
                throw new DependencyInjectionException(sprintf(
                    'Class "%s" don\'t have method "%s"',
                    $definition->getClassName(),
                    $method
                ));
            }
            $methodReflection->invokeArgs($instance, $methodArguments);
        }
        return $instance;
    }

    /**
     * 处理构造方法所需要的参数
     * @param \ReflectionMethod $constructor
     * @param array $parameters
     * @throws DependencyInjectionException
     * @return array
     */
    protected function resolveConstructArguments(\ReflectionMethod $constructor, array $parameters)
    {
        $constructorArgs = [];
        foreach ($constructor->getParameters() as $parameter) {
            $index = $parameter->getPosition();
            // 如果定义过依赖 则直接获取
            if (isset($parameters[$index])) {
                if ($parameters[$index] instanceof DependencyInterface) {
                    $constructorArgs[] = $parameters[$index]->getDependency();
                } else {
                    $constructorArgs[] = $parameters[$index];
                }
            } elseif (($dependency = $parameter->getClass()) != null) {
                $constructorArgs[] = $this->get($dependency->getName());
            } elseif ($parameter->isOptional()) {
                $constructorArgs[] = $parameter->getDefaultValue();
            } else {
                throw new DependencyInjectionException(sprintf(
                    'Parameter "%s" must be provided',
                    $parameter->getName()
                ));
            }
        }
        return $constructorArgs;
    }

    /**
     * 获取类的反射对象
     * @param string $className
     * @throws DependencyInjectionException
     * @return \ReflectionClass
     */
    protected function reflectClass($className)
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
     * @param string $key
     * @return string
     */
    protected function getKey($key)
    {
        return isset($this->aliases[$key]) ? $this->aliases[$key] : $key;
    }
}
