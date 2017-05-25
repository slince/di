<?php
/**
 * slince dependency injection component
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

use Psr\Container\ContainerInterface;
use Slince\Di\Exception\ConfigException;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Exception\NotFoundException;

class Container implements ContainerInterface
{
    /**
     * Array of singletons
     * @var array
     */
    protected $shares = [];

    /**
     * Array pf definitions, support instance,callable,Definition, class
     * @var array
     */
    protected $definitions = [];

    /**
     * Array of interface bindings
     * @var array
     */
    protected $contextBindings = [];

    /**
     * Array of parameters
     * @var ParameterStore
     */
    protected $parameters;

    public function __construct()
    {
        $this->parameters = new ParameterStore();
    }

    /**
     * Add a Definition class
     * @param string $name
     * @param string $class 类名
     * @return ClassDefinition
     */
    public function define($name, $class)
    {
        $definition = new ClassDefinition($class);
        $this->definitions[$name] = $definition;
        return $definition;
    }

    /**
     * Bind an callable to the container with its name
     * @param string $name
     * @param mixed $creation A invalid callable
     * @throws ConfigException
     * @return $this
     */
    public function call($name, $creation)
    {
        if (!is_callable($creation)) {
            throw new ConfigException(sprintf("Delegate expects a valid callable or executable class::method string at Argument 2"));
        }
        $this->definitions[$name] = $creation;
        return $this;
    }

    /**
     * Bind an instance to the container with its name
     * ```
     * $container->instance('user', new User());
     * //Or just give instance
     * $container->instance(new User());
     *
     * ```
     * @param string $name
     * @param object $instance
     * @throws ConfigException
     * @return $this
     */
    public function instance($name, $instance = null)
    {
        if (func_num_args() == 1) {
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
     * Binds an interface or abstract class to its implementation;
     * It's also be used to bind a service name to an existing class
     * @param string $name
     * @param string $implementation
     * @param string|array $context the specified context to bind
     * @throws ConfigException
     * @return $this
     */
    public function bind($name, $implementation, $context = null)
    {
        if (is_null($context)) {
            $this->definitions[$name] = $implementation;
        } else {
            if (is_array($context)) {
                list($contextClass, $contextMethod) = $context;
            } else {
                list($contextClass, $contextMethod) = explode('::', $context);
            }
            $contextMethod || $contextMethod = 'general';
            isset($this->contextBindings[$contextClass][$contextMethod])
                || ($this->contextBindings[$contextClass][$contextMethod] = []);
            $this->contextBindings[$contextClass][$contextMethod][$name] = $implementation;
        }
        return $this;
    }

    /**
     * Add a definition to the container
     * ```
     * //Add an instance like "instance" method
     * $container->set('student', new Student());
     *
     * //Add a callable definition
     * $container->set('student', 'StudentFactory::create');
     * $container->set('student', function(){
     *     return new Student();
     * });
     *
     * //Add an instance of "Slince\Di\Definition"
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
     * //Add a class definition
     * $container->set('student', Foo\Bar\StudentClass);
     * ```
     * @param string $name
     * @param mixed $definition
     * @throws ConfigException
     * @return $this
     */
    public function set($name, $definition)
    {
        if (is_callable($definition)) {
            $this->call($name, $definition);
        } elseif ($definition instanceof ClassDefinition) {
            $this->definitions[$name] = $definition;
        } elseif (is_object($definition)) {
            $this->instance($name, $definition);
        } elseif (is_string($definition)) {
            $this->bind($name, $definition);
        } else {
            throw new ConfigException(sprintf("Unexpected object definition type '%s'", gettype($definition)));
        }
        return $this;
    }

    /**
     * Share the service by given name
     * @param string $name
     * @return $this
     */
    public function share($name)
    {
        $this->shares[$name] = null;
        return $this;
    }

    /**
     * Get a service instance by specified name
     * @param string $name
     * @param array $arguments
     * @return object
     */
    public function get($name, $arguments = [])
    {
        //If service is singleton, return instance directly.
        if (isset($this->shares[$name])) {
            return $this->shares[$name];
        }
        //If there is no matching definition, creates an definition automatically
        if (!isset($this->definitions[$name])) {
            if (class_exists($name)) {
                $this->bind($name, $name);
            } else {
                throw new NotFoundException(sprintf('There is no definition for "%s"', $name));
            }
        }
        $instance = $this->createInstanceFromDefinition($this->definitions[$name], $arguments);
        //If the service be set as singleton mode, stores its instance
        if (array_key_exists($name, $this->shares)) {
            $this->shares[$name] = $instance;
        }
        return $instance;
    }

    protected function createInstanceFromDefinition($definition, array $arguments)
    {
        if (is_callable($definition)) {
            $instance = call_user_func($definition, $this, $arguments);
        } elseif ($definition instanceof ClassDefinition) {
            $instance = $this->createFromDefinition($definition, $arguments);
        } elseif (is_object($definition)) {
            $instance = $definition;
        } else {
            list(, $instance) = $this->createReflectionAndInstance($definition, $arguments);
        }
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        if (isset($this->shares[$name])) {
            return true;
        }
        if (!isset($this->definitions[$name]) && class_exists($name)) {
            $this->bind($name, $name);
        }
        return isset($this->definitions[$name]);
    }

    /**
     * Gets all global parameters
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters->toArray();
    }

    /**
     * Sets array of parameters
     * @param array $parameterStore
     */
    public function setParameters(array $parameterStore)
    {
        $this->parameters->setParameters($parameterStore);
    }

    /**
     * Add some parameters
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->parameters->addParameters($parameters);
    }

    /**
     * Sets a parameter with its name and value
     * @param $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters->setParameter($name, $value);
    }

    /**
     * Gets a parameter by given name
     * @param $name
     * @param mixed $default
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        return $this->parameters->getParameter($name, $default);
    }

    /**
     * 根据definition创建实例
     * @param ClassDefinition $definition
     * @param array $arguments
     * @throws DependencyInjectionException
     * @return object
     */
    protected function createFromDefinition(ClassDefinition $definition, array $arguments)
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

    protected function prepareInstance(\ReflectionClass $reflection, $instance, ClassDefinition $definition)
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
            $contextBindings = $this->getContextBindings($reflection->name, $method);
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
            $index = $isNumeric ? $parameter->getPosition() : $parameter->name;
            // 如果定义过依赖 则直接获取
            if (isset($arguments[$index])) {
                $functionArguments[] = $arguments[$index];
            } elseif (($dependency = $parameter->getClass()) != null) {
                $dependencyName = $dependency->name;
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
                    $parameter->name,
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
            if ($parameter = $this->parameters->getParameter($key)) {
                return $parameter;
            }
            throw new DependencyInjectionException(sprintf("Parameter [%s] is not defined", $key));
        }
        //"fool%bar%baz"
        return preg_replace_callback("#%([^%\s]+)%#", function ($matches) {
            $key = $matches[1];
            if ($parameter = $this->parameters->getParameter($key)) {
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
