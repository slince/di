<?php

/*
 * This file is part of the slince/di package.
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Slince\Di;

use Slince\Di\Exception\ConfigException;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Exception\NotFoundException;

class DefinitionResolver
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Definition $definition
     * @return mixed
     */
    public function resolve(Definition $definition)
    {
        if (null !== $definition->getFactory()) {
            $instance = $this->createFromFactory($definition);
            $reflection = new \ReflectionObject($instance);
        } else {
            list($reflection, $instance) = $this->createFromClass($definition);
        }

        $this->invokeMethods($definition, $instance, $reflection);
        $this->invokeProperties($definition, $instance);

        return $instance;
    }

    protected function createFromClass(Definition $definition)
    {
        $class = $definition->getClass();
        if ($class === null) {
            throw new DependencyInjectionException('You must set a class or factory for definition.');
        }
        try {
            $reflection = new \ReflectionClass($definition->getClass());
        } catch (\ReflectionException $e) {
            throw new DependencyInjectionException(sprintf('Class "%s" is invalid', $definition->getClass()));
        }
        if (!$reflection->isInstantiable()) {
            throw new DependencyInjectionException(sprintf('Can not instantiate "%s"', $definition->getClass()));
        }
        $constructor = $reflection->getConstructor();
        if (is_null($constructor)) {
            $instance = $reflection->newInstanceWithoutConstructor();
        } else {
            $arguments = $this->resolveFunctionArguments($definition, $constructor, $definition->getArguments());
            $instance = $reflection->newInstanceArgs($arguments);
        }
        return [$reflection, $instance];
    }

    /**
     * @param Definition $definition
     * @return object
     */
    protected function createFromFactory(Definition $definition)
    {
        $factory = $definition->getFactory();
        try {
            $reflection = is_array($factory)
                ? new \ReflectionMethod($factory[0], $factory[1])
                : new \ReflectionFunction($factory);

            if ($reflection->getNumberOfParameters() > 0) {
                $arguments = $this->resolveFunctionArguments($definition, $reflection, $definition->getArguments());
            } else {
                $arguments = [];
            }
            return call_user_func_array($factory, $arguments);

        } catch (\ReflectionException $exception) {
            throw new ConfigException('The factory is invalid.');
        }
    }

    /**
     * @param Definition $definition
     * @param object $instance
     * @param \ReflectionClass $reflection
     */
    protected function invokeMethods(Definition $definition, $instance, \ReflectionClass $reflection)
    {
        foreach ($definition->getMethodCalls() as $method) {
            try {
                $reflectionMethod = $reflection->getMethod($method[0]);
            } catch (\ReflectionException $e) {
                throw new DependencyInjectionException(sprintf(
                    'Class "%s" has no method "%s"',
                    $definition->getClass(),
                    $method[0]
                ));
            }
            $reflectionMethod->invokeArgs($instance, $this->resolveFunctionArguments(
                $definition,
                $reflectionMethod,
                $method[1]
            ));
        }
    }

    /**
     * @param Definition $definition
     * @param object $instance
     */
    protected function invokeProperties(Definition $definition, $instance)
    {
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
     * Resolves all arguments for the function or method.
     *
     * @param Definition $definition
     * @param \ReflectionFunctionAbstract $method
     * @param array $arguments
     * @throws DependencyInjectionException
     * @return array
     */
    public function resolveFunctionArguments(
        Definition $definition,
        \ReflectionFunctionAbstract $method,
        array $arguments
    ) {
        $solvedArguments = [];
        $arguments = $this->resolveParameters($arguments);
        $autowired = $definition->isAutowired(); //autowired
        foreach ($method->getParameters() as $parameter) {
            //If the dependency is provided directly
            if (isset($arguments[$parameter->getPosition()])) {
                $solvedArguments[] = $arguments[$parameter->getPosition()];
            } elseif (isset($arguments[$parameter->name])) {
                $solvedArguments[] = $arguments[$parameter->name];
            } elseif ($autowired && ($dependency = $parameter->getClass()) != null) {
                $dependencyName = $dependency->name;
                try {
                    $solvedArguments[] = $this->container->get($dependencyName);
                } catch (NotFoundException $exception) {
                    if ($parameter->isOptional()) {
                        $solvedArguments[] = $parameter->getDefaultValue();
                    } else {
                        throw $exception;
                    }
                }
            } elseif ($parameter->isOptional()) {
                $solvedArguments[] = $parameter->getDefaultValue();
            } else {
                throw new DependencyInjectionException(sprintf(
                    'Missing required parameter "%s" when calling "%s"',
                    $parameter->name,
                    $method->getName()
                ));
            }
        }
        return $solvedArguments;
    }

    /**
     * Resolves array of parameters
     * @param array $parameters
     * @return array
     */
    protected function resolveParameters($parameters)
    {
        return array_map(function($parameter) {
            if (is_array($parameter)) {
                return $this->resolveParameters($parameter);
            } else {
                return $this->resolveParameter($parameter);
            }
        }, $parameters);
    }

    /**
     * Formats parameter value
     *
     * @param string|Reference $value
     * @return string
     * @throws DependencyInjectionException
     */
    protected function resolveParameter($value)
    {
        //Reference
        if ($value instanceof Reference) {
            return $this->container->get($value->getId());
        }
        if (is_string($value) && ($len = strlen($value)) > 0) {
            if ($len >= 2 && '@' === $value[0]) {
                return $this->container->get(substr($value, 1));
            }
            //"fool%bar%baz"
            return preg_replace_callback("#%([^%\s]+)%#", function($matches) {
                $key = $matches[1];
                if ($parameter = $this->container->getParameter($key)) {
                    return $parameter;
                }
                throw new DependencyInjectionException(sprintf("Parameter [%s] is not defined", $key));
            }, $value);
        }
        return $value;
    }
}