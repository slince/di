<?php
/**
 * slince dependency injection component
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

use Slince\Di\Exception\ConfigException;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Exception\NotFoundException;
use Interop\Container\ContainerInterface;

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

    /**
     * @var ClassDefinitionResolver
     */
    protected $classDefinitionResolver;

    public function __construct()
    {
        $this->parameters = new ParameterStore();
        $this->instance($this);
    }

    /**
     * Add a Definition class
     * @param string $name
     * @param string $class
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
            throw new ConfigException(sprintf("Call expects a valid callable or executable class::method string"));
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
            $this->define($name, $implementation);
        } else {
            if (is_array($context)) {
                list($contextClass, $contextMethod) = $context;
            } else {
                $contextClass = $context;
                $contextMethod = 'general';
            }
            isset($this->contextBindings[$contextClass][$contextMethod])
                || ($this->contextBindings[$contextClass][$contextMethod] = []);
            $this->contextBindings[$contextClass][$contextMethod][$name] = $implementation;
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
        } elseif (is_object($definition)) {
            $this->instance($name, $definition);
        } elseif (is_string($definition)) {
            $this->define($name, $definition);
        } else {
            throw new ConfigException(sprintf("Unexpected object definition type '%s'", gettype($definition)));
        }
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
     * Resolves all arguments for the function or method.
     * @param \ReflectionFunctionAbstract $method
     * @param array $arguments
     * @param array $contextBindings The context bindings for the function
     * @throws DependencyInjectionException
     * @return array
     */
    public function resolveFunctionArguments(\ReflectionFunctionAbstract $method, array $arguments, array $contextBindings = [])
    {
        $functionArguments = [];
        $arguments = $this->resolveParameters($arguments);
        //Checks whether the position is numeric
        $isNumeric = !empty($arguments) && is_numeric(key($arguments));
        foreach ($method->getParameters() as $parameter) {
            $index = $isNumeric ? $parameter->getPosition() : $parameter->name;
            //If the dependency is provided directly
            if (isset($arguments[$index])) {
                $functionArguments[] = $arguments[$index];
            } elseif (($dependency = $parameter->getClass()) != null) {
                $dependencyName = $dependency->name;
                //Use the new dependency if the dependency name has been replaced in array of context bindings
                isset($contextBindings[$dependencyName]) && $dependencyName = $contextBindings[$dependencyName];
                try {
                    $functionArguments[] = $this->get($dependencyName);
                } catch (NotFoundException $exception) {
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
     * Gets all context bindings for the class and method
     * [
     *     'User' => [
     *          'original' => 'SchoolInterface'
     *          'bind' => 'MagicSchool',
     *     ]
     * ]
     * @param string $contextClass
     * @param string $contextMethod
     * @return array
     */
    public function getContextBindings($contextClass, $contextMethod)
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

    protected function createInstanceFromDefinition($definition, array $arguments)
    {
        if (is_callable($definition)) {
            if ($arguments && ($definition instanceof \Closure || is_string($definition))) {
                $arguments = $this->resolveFunctionArguments(
                    new \ReflectionFunction($definition),
                    $arguments
                );
            }
            $arguments = $arguments ?: [$this];
            $instance = call_user_func_array($definition, $arguments);
        } elseif ($definition instanceof ClassDefinition) {
            $instance = $this->getClassDefinitionResolver()->resolve($definition, $arguments);
        } else {
            $instance = $definition;
        }
        return $instance;
    }

    protected function getClassDefinitionResolver()
    {
        if (!is_null($this->classDefinitionResolver)) {
            return $this->classDefinitionResolver;
        }
        return $this->classDefinitionResolver = new ClassDefinitionResolver($this);
    }

    /**
     * Resolves array of parameters
     * @param array $parameters
     * @return array
     */
    protected function resolveParameters($parameters)
    {
        return array_map(function ($parameter) {
            if (is_string($parameter)) {
                $parameter = $this->formatParameter($parameter);
            } elseif ($parameter instanceof Reference) {
                $parameter = $this->get($parameter->getName());
            } elseif (is_array($parameter)) {
                $parameter = $this->resolveParameters($parameter);
            }
            return $parameter;
        }, $parameters);
    }

    /**
     * Formats parameter value
     * @param string $value
     * @return string
     * @throws DependencyInjectionException
     */
    protected function formatParameter($value)
    {
        //%xx% return the parameter
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
}
