<?php
/**
 * slince dependency injection component
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

use Slince\Di\Exception\ConfigException;
use Slince\Di\Exception\DependencyInjectionException;

class Container
{
    /**
     * 别名数组
     * @var array
     */
    protected $aliases = [];

    /**
     * 单例实例数组
     * @var array
     */
    protected $singletons = [];

    /**
     * 所有需要分享的类或者类别名
     * @var array
     */
    protected $shares = [];

    /**
     * 类实例化工作的
     * @var array
     */
    protected $delegates = [];

    /**
     * 全局接口与类绑定关系
     * @var array
     */
    protected $bindings = [];

    /**
     * 参数集合
     * @var array
     */
    protected $parameters;

    /**
     * 给指定类或者别名指向类设置实例化向导
     * @param string $name 被定义的类名或者别名
     * @param $arguments
     * @param array $properties
     * @param array $methodCalls
     * @return Definition
     */
    public function define($name, $arguments, array $properties = [], array $methodCalls = [])
    {
        $definition = new Definition($name, $arguments, $properties, $methodCalls);
        $this->setDefinition($name, $definition);
        return $definition;
    }

    /**
     * 设置指定类的实例化过程的代理
     * @param string $name
     * @param mixed $creation 闭包及其它合法可调用的语法结构
     * @throws ConfigException
     * @return $this
     */
    public function delegate($name, $creation)
    {
        if (!is_callable($creation)) {
            throw new ConfigException(sprintf("Delegate expects a valid callable or executable class::method string at Argument 2"));
        }
        $this->delegates[$name] = $creation;
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
     * 给指定类或者类别名设置实例化指令
     * @param string $name
     * @param mixed $creation
     * @param boolean $share
     * @return Container
     */
    public function set($name, $creation, $share = false)
    {
        if (is_callable($creation)) {
            $this->delegate($name, $creation);
            $share && $this->share($name);
        } elseif (is_object($creation)) {
            $this->share($creation); //如果creation是实例的话则只能单例
        }
        return $this;
    }

    /**
     * 设置接口与类的绑定
     * @param string $original 接口、抽象类或者一个常见的类
     * @param string $class 一个可被实例化的类名
     * @throws ConfigException
     */
    public function bind($original, $class)
    {
        if (is_subclass_of($class, $original)) {
            $this->bindings[$original] = $class;
        }
        throw new ConfigException(sprintf("Class '%s' must be subclass of '%s'", $class, $original));
    }

    /**
     * 设置类或者类实例的分享
     * @param string|Object $nameOrInstance
     * @return $this
     */
    public function share($nameOrInstance)
    {
        if (is_object($nameOrInstance)) {
            $this->shareInstance($nameOrInstance);
        } else {
            $this->shares[$nameOrInstance] = null;
        }
        return $this;
    }

    /**
     * 分享类实例
     * @param Object $instance
     */
    protected function shareInstance($instance)
    {
        $class = get_class($instance);
        $this->shares[$class] = $instance;
    }

    /**
     * 为指定类设置一个别名
     * @param string $original
     * @param string $alias
     */
    public function alias($original, $alias)
    {
        $this->aliases[$alias] = $original;
    }

    /**
     * 以获取一个实例
     * @param string $name
     * @param array $arguments 传递给类的构造参数，会覆盖预先定义的同名参数
     * @return object
     */
    public function get($name, array $arguments = [])
    {
        $class = $this->resolveAlias($name);
        //如果单例的话直接返回实例结果
        if (isset($this->singletons[$class])) {
            return $this->singletons[$class];
        }
        //如果没有设置实例化指令的代理则为其设置代理
        if (!isset($this->delegates[$class])) {
            $this->delegate($class, function () use ($class, $arguments) {
                return $this->create($class, $arguments);
            });
        }
        //执行实例化指令
        $instance = call_user_func($this->delegates[$class], $this);
        return $instance;
    }

    /**
     * 执行指定类或别名的实例化指令以获取一个实例（饥饿模式）
     * @param string $name 指定类或者类别名
     * @param array $arguments 构造参数，覆盖预定义参数
     * @throws DependencyInjectionException
     * @return object
     */
    public function create($name, $arguments = [])
    {
        $class = $this->resolveAlias($name);
        $reflection = $this->reflectClass($class);
        $constructor = $reflection->getConstructor();
        if (!is_null($constructor)) {
            $constructorArgs = $this->resolveConstructArguments($constructor, $this->resolveParameters($arguments));
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
    protected function createFromDefinition(Definition $definition)
    {
        $arguments = $definition->getArguments();
        $reflection = $this->reflectClass($definition->getClassName());
        $constructor = $reflection->getConstructor();
        if (!is_null($constructor)) {
            $constructorArgs = $this->resolveConstructArguments($constructor, $this->resolveParameters($arguments));
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
                    "Class '%s' has no method '%s'",
                    $definition->getClassName(),
                    $method
                ));
            }
            $methodReflection->invokeArgs($instance, $this->resolveParameters($methodArguments));
        }
        // 触发属性
        foreach ($definition->getProperties() as $propertyName => $propertyValue) {
            if (property_exists($instance, $propertyName)) {
                $instance->$propertyName = $propertyValue;
            } else {
                throw new DependencyInjectionException(sprintf(
                    "Class '%s' has no property '%s'",
                    $definition->getClassName(),
                    $propertyName
                ));
            }
        }
        return $instance;
    }

    /**
     * 获取所有预定义参数
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * 设置预定义参数
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * 添加预定义参数
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->parameters = array_replace($this->parameters, $parameters);
    }

    /**
     * 设置参数
     * @param $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * 获取参数
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function getParameter($name, $default = null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }

    /**
     * 预处理参数
     * @param $parameters
     * @return array
     */
    protected function resolveParameters($parameters)
    {
        return array_map(function ($parameter) {
            //字符类型参数处理下预定义参数的情况
            if (is_string($parameter)) {
                $parameter = $this->resolveString($parameter);
            } elseif ($parameter instanceof Reference) { //服务依赖
                $parameter = $this->get($parameter->getName());
            } elseif (is_array($parameter)) {
                $parameter = $this->resolveParameters($parameter);
            }
            return $parameter;
        }, $parameters);
    }

    /**
     * 处理字符串
     * @param $value
     * @return mixed
     * @throws DependencyInjectionException
     */
    protected function resolveString($value)
    {
        //%xx%类型的直接返回对应的参数
        if (preg_match("#^%([^%\s]+)%$#", $value, $match)) {
            $key = $match[1];
            if (isset($this->parameters[$key])) {
                return $this->parameters[$key];
            }
            throw new DependencyInjectionException(sprintf("Parameter [%s] is not defined", $key));
        }
        return preg_replace_callback("#%([^%\s]+)%#", function ($matches) {
            $key = $matches[1];
            if (isset($this->parameters[$key])) {
                return $this->parameters[$key];
            }
            throw new DependencyInjectionException(sprintf("Parameter [%s] is not defined", $key));
        }, $value);
    }

    /**
     * 处理构造方法所需要的参数
     * @param \ReflectionMethod $constructor
     * @param array $arguments
     * @throws DependencyInjectionException
     * @return array
     */
    protected function resolveConstructArguments(\ReflectionMethod $constructor, array $arguments)
    {
        $constructorArgs = [];
        $arguments = $this->resolveParameters($arguments);
        foreach ($constructor->getParameters() as $parameter) {
            $index = $parameter->getPosition();
            // 如果定义过依赖 则直接获取
            if (isset($arguments[$index])) {
                $constructorArgs[] = $arguments[$index];
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
     * 处理alias，或者别名指向的真实类名
     * @param string $alias
     * @return string
     */
    protected function resolveAlias($alias)
    {
        return isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;
    }
}
