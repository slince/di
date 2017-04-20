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
     * 所有需要分享的类及其实例
     * @var array
     */
    protected $shares = [];

    /**
     * 预定义的依赖,支持instance、callable、Definition、class
     * @var array
     */
    protected $definitions = [];

    /**
     * 全局接口与类绑定关系
     * @var array
     */
    protected $contextBindings = [];

    /**
     * 参数集合
     * @var ParameterStore
     */
    protected $parameterStore;

    public function __construct()
    {
        //全局参数存储
        $this->parameterStore = new ParameterStore();
    }

    /**
     * 给指定类或者别名指向类设置实例化向导
     * @param string $name
     * @param string $class 类名
     * @param array $arguments 构造函数
     * @param array $methodCalls setter注入
     * @param array $properties 属性注入
     * @return Definition
     */
    public function define($name, $class, array $arguments, array $methodCalls = [], array $properties = [])
    {
        $definition = new Definition($class, $arguments, $methodCalls, $properties);
        $this->setDefinition($name, $definition);
        return $definition;
    }

    /**
     * 设置指定类的实例化代理
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
        $this->definitions[$name] = $creation;
        return $this;
    }

    /**
     * 绑定实例
     * ```
     * $container->instance('user', $user);
     * //或者直接提供实例
     * $container->instance($user);
     *
     * ```
     * @param $name
     * @param $instance
     * @throws ConfigException
     * @return $this
     */
    public function instance($name, $instance)
    {
        if (func_get_args() == 1) {
            if (!is_object($name)) {
                throw new ConfigException(sprintf("Instance expects a valid object"));
            }
            $instance = $name;
            $name = get_class($instance);
        }
        $this->definitions[$name] = $instance;
        $this->share($name);
        return $this;
    }

    /**
     * 将name直接绑定到某个指定存在的类（可用来绑定接口或者抽象类与实现类）
     * @param string $name
     * @param string $class 一个可被实例化的类名
     * @param string|array $context 为指定的上下文设置绑定指令
     * @throws ConfigException
     * @return $this
     */
    public function bind($name, $class, $context = null)
    {
        if (is_null($context)) {
            $this->definitions[$name] = $class;
        } else {
            if (is_array($context)) {
                list($contextClass, $contextMethod) = $context;
            } else {
                $contextClass = $context;
                $contextMethod = 'general';
            }
            isset($this->contextBindings[$contextClass][$contextMethod])
                || ($this->contextBindings[$contextClass][$contextMethod] = []);
            $this->contextBindings[$contextClass][$contextMethod][$name] = $class;
        }
        return $this;
    }

    /**
     * 给指定类或者类别名设置实例化指令
     * ```
     * //直接绑定实例
     * $container->set('student', $student);
     *
     * //绑定闭包或者其它可调用结构
     * $container->set('student', 'StudentFactory::create');
     * $container->set('student', function(){
     *     return new Student();
     * });
     *
     * //绑定预定义
     * $container->set('student', new Definition('Foo\Bar\StudentClass', [
     *      'gender' => 'boy',
     *      'school' => new Reference('school')
     * ], [
     *     'setAge' => [18]
     * ], [
     *     'father' => 'James',
     *     'mather' => 'Sophie'
     * ]));
     *
     * //绑定到指定类
     * $container->set('student', Foo\Bar\StudentClass);
     * ```
     * @param string $name
     * @param mixed $definition
     * @param boolean $share
     * @throws ConfigException
     * @return $this
     */
    public function set($name, $definition, $share = false)
    {
        if (is_callable($definition)) {
            $this->delegate($name, $definition);
            $share && $this->share($name);
        } elseif ($definition instanceof Definition) {
            $this->setDefinition($name, $definition, $share);
        } elseif (is_object($definition)) {
            $this->instance($name, $definition); //如果$definition是实例的话则只能单例
        } elseif (is_string($definition)) {
            $this->bind($name, $definition);
            $share && $this->share($name);
        } else {
            throw new ConfigException(sprintf("Unexpected object definition type '%s'", gettype($definition)));
        }
        return $this;
    }

    /**
     * 如果不能简单获取，则使用设置定义的方式
     * @param string $name
     * @param Definition $definition
     * @param boolean $share
     * @return Definition
     * @deprecated Will be protected, Use define & share or set instead.
     */
    public function setDefinition($name, Definition $definition, $share = false)
    {
        $this->definitions[$name] = $definition;
        $share && $this->share($name);
        return $definition;
    }

    /**
     * 设置类或者类实例的分享
     * @param string $name
     * @return $this
     */
    public function share($name)
    {
        //兼容旧的api，给出移除提示
        if (func_num_args() == 2) {
            trigger_error("Use set instead, Now share only expects one argument", E_USER_DEPRECATED);
            $arguments = func_get_args();
            $arguments[] = true;
            return call_user_func_array([$this, 'set'], $arguments);
        }
        $this->shares[$name] = null;
        return $this;
    }

    /**
     * 为指定类设置一个别名
     * @param string $alias
     * @param string $original
     * @return $this
     * @deprecated duplication,use bind instead
     */
    public function alias($alias, $original)
    {
        return $this->bind($alias, $original);
    }

    /**
     * 以获取一个实例
     * @param string $name
     * @param array $arguments 传递给类的构造参数，会覆盖预先定义的同名参数
     * @return object
     */
    public function get($name, $arguments = [])
    {
        //兼容旧的api
        if (is_bool($arguments)) {
            trigger_error("Argument 'new' has been deprecated", E_USER_DEPRECATED);
            $forceNewInstance  = $arguments;
            $arguments = [];
        } else {
            $forceNewInstance = false;
        }
        //如果单例的话直接返回实例结果
        if (isset($this->shares[$name]) && !$forceNewInstance) {
            return $this->shares[$name];
        }
        //如果没有设置实例化指令的代理则认为当前提供的即是class，为其创建一条指向自身的bind
        if (!isset($this->definitions[$name])) {
            $this->bind($name, $name);
        }
        $definition = $this->definitions[$name];
        if (is_callable($definition)) {
            $instance = call_user_func($definition, $this, $arguments);
        } elseif ($definition instanceof Definition) {
            $instance = $this->createFromDefinition($definition, $arguments);
        } elseif (is_object($definition)) {
            $instance = $definition;
        } else {
            list(, $instance) = $this->createReflectionAndInstance($definition, $arguments);
        }
        //如果设置了单例则缓存实例
        if (array_key_exists($name, $this->shares)) {
            $this->shares[$name] = $instance;
        }
        return $instance;
    }

    /**
     * 分析类结构并自动解决依赖生成一个类实例
     * @param string $name 指定类或者类别名
     * @param array $arguments 构造参数，覆盖预定义参数
     * @throws DependencyInjectionException
     * @return object
     * @deprecated
     */
    public function create($name, $arguments = [])
    {
        list(, $instance) = $this->createReflectionAndInstance($name, $arguments);
        return $instance;
    }

    /**
     * 获取所有预定义参数
     * @return array
     */
    public function getParameters()
    {
        return $this->parameterStore->toArray();
    }

    /**
     * 设置预定义参数
     * @param array $parameterStore
     */
    public function setParameters(array $parameterStore)
    {
        $this->parameterStore->setParameters($parameterStore);
    }

    /**
     * 添加预定义参数
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->parameterStore->addParameters($parameters);
    }

    /**
     * 设置参数
     * @param $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->parameterStore->setParameter($name, $value);
    }

    /**
     * 获取参数
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function getParameter($name, $default = null)
    {
        return $this->parameterStore->getParameter($name, $default);
    }

    /**
     * 根据definition创建实例
     * @param Definition $definition
     * @param array $arguments
     * @throws DependencyInjectionException
     * @return object
     */
    protected function createFromDefinition(Definition $definition, array $arguments)
    {
        $arguments = array_replace($definition->getArguments(), $arguments);
        list($reflection, $instance) = $this->createReflectionAndInstance($definition->getClass(), $arguments);
        $this->prepareInstance($reflection, $instance, $definition);
        return $instance;
    }

    /**
     * 构建实例
     * @param string $class
     * @param array $arguments
     * @throws DependencyInjectionException
     * @return array
     */
    protected function createReflectionAndInstance($class, array $arguments)
    {
        $reflection = $this->reflectClass($class);
        if (!$reflection->isInstantiable()) {
            throw new DependencyInjectionException(sprintf("Can not instantiate [%s]", $class));
        }
        $constructor = $reflection->getConstructor();
        if (!is_null($constructor)) {
            $constructorArgs = $this->resolveFunctionArguments(
                $constructor,
                $this->resolveParameters($arguments),
                $this->getContextBindings($class, $constructor->getName())
            );
            $instance = $reflection->newInstanceArgs($constructorArgs);
        } else {
            $instance = $reflection->newInstanceWithoutConstructor();
        }
        return [$reflection, $instance];
    }

    protected function prepareInstance(\ReflectionClass $reflection, $instance, Definition $definition)
    {
        // 触发setter函数
        foreach ($definition->getMethodCalls() as $method => $methodArguments) {
            try {
                $reflectionMethod = $reflection->getMethod($method);
            } catch (\ReflectionException $e) {
                throw new DependencyInjectionException(sprintf(
                    "Class '%s' has no method '%s'",
                    $definition->getClass(),
                    $method
                ));
            }
            //获取该方法下所有可用的绑定
            $contextBindings = $this->getContextBindings($reflection->getName(), $method);
            $reflectionMethod->invokeArgs(
                $instance,
                $this->resolveFunctionArguments($reflectionMethod, $methodArguments, $contextBindings)
            );
        }
        // 触发属性
        foreach ($definition->getProperties() as $propertyName => $propertyValue) {
            if (property_exists($instance, $propertyName)) {
                $instance->$propertyName = $propertyValue;
            } else {
                throw new DependencyInjectionException(sprintf(
                    "Class '%s' has no property '%s'",
                    $definition->getClass(),
                    $propertyName
                ));
            }
        }
    }

    /**
     * 处理方法所需要的参数
     * @param \ReflectionFunctionAbstract $method
     * @param array $arguments
     * @param array $contextBindings 该方法定义的所有依赖绑定
     * @throws DependencyInjectionException
     * @return array
     */
    protected function resolveFunctionArguments(\ReflectionFunctionAbstract $method, array $arguments, array $contextBindings = [])
    {
        $functionArguments = [];
        $arguments = $this->resolveParameters($arguments);
        $isNumeric = !empty($arguments) && is_numeric(key($arguments));
        foreach ($method->getParameters() as $parameter) {
            //如果提供的是数字索引按照参数位置处理否则按照参数名
            $index = $isNumeric ? $parameter->getPosition() : $parameter->getName();
            // 如果定义过依赖 则直接获取
            if (isset($arguments[$index])) {
                $functionArguments[] = $arguments[$index];
            } elseif (($dependency = $parameter->getClass()) != null) {
                $dependencyName = $dependency->getName();
                //如果该依赖已经重新映射到新的依赖上则修改依赖为新指向
                isset($contextBindings[$dependencyName]) && $dependencyName = $contextBindings[$dependencyName];
                try {
                    $functionArguments[] = $this->get($dependencyName);
                } catch (DependencyInjectionException $exception) {
                    if ($parameter->isOptional()) {
                        $functionArguments[] = $parameter->getDefaultValue();
                    } else {
                        throw $exception;
                    }
                }
            } elseif ($parameter->isOptional()) {
                $functionArguments[] = $parameter->getDefaultValue();
            } else {
                throw new DependencyInjectionException(sprintf(
                    'Missing required parameter "%s" when calling "%s"',
                    $parameter->getName(),
                    $method->getName()
                ));
            }
        }
        return $functionArguments;
    }

    /**
     * 获取指定类的绑定关系
     * [
     *     'User' => [
     *          'original' => 'SchoolInterface'
     *          'bind' => 'MagicSchool',
     *     ]
     * ]
     * @param string $contextClass
     * @param string $contextMethod
     * @return mixed
     */
    protected function getContextBindings($contextClass, $contextMethod)
    {
        if (!isset($this->contextBindings[$contextClass])) {
            return [];
        }
        $contextBindings = isset($this->contextBindings[$contextClass]['general'])
            ? $this->contextBindings[$contextClass]['general'] : [];
        if (isset($this->contextBindings[$contextClass][$contextMethod])) {
            $contextBindings = array_merge($contextBindings, $this->contextBindings[$contextClass][$contextMethod]);
        }
        return $contextBindings;
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
            if ($parameter = $this->parameterStore->getParameter($key)) {
                return $parameter;
            }
            throw new DependencyInjectionException(sprintf("Parameter [%s] is not defined", $key));
        }
        //"fool%bar%baz"
        return preg_replace_callback("#%([^%\s]+)%#", function ($matches) {
            $key = $matches[1];
            if ($parameter = $this->parameterStore->getParameter($key)) {
                return $parameter;
            }
            throw new DependencyInjectionException(sprintf("Parameter [%s] is not defined", $key));
        }, $value);
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
}
