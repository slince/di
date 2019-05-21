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

class Resolver
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
        $this->parseConcrete($definition);

        if (null !== $definition->getFactory()) {
            $instance = $this->createFromFactory($definition);
        } elseif (null !== $definition->getClass()) {
            $instance = $this->createFromClass($definition);
        } elseif (null !== $definition->getResolved()) {
            $instance = $definition->getResolved();
        } else {
            throw new ConfigException('The definition is not invalid.');
        }
        $this->invokeMethods($definition, $instance);
        $this->invokeProperties($definition, $instance);
        $definition->setResolved($instance);

        return $instance;
    }

    protected function parseConcrete(Definition $definition)
    {
        $concrete = $definition->getConcrete();
        if (is_string($concrete)) {
            $definition->setClass($concrete);
        } elseif (is_callable($concrete)) {
            $definition->setFactory($concrete);
        } elseif (is_object($concrete)) {
            $definition->setResolved($concrete)
                ->setShared(true);
        } else {
            throw new ConfigException('The concrete of the definition is invalid');
        }
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
            $arguments = $this->resolveArguments($definition->getArguments());
            if ($definition->isAutowired()) {
                $arguments = $this->resolveReflectionArguments($constructor, $arguments);
            }
            if (count($arguments) < $constructor->getNumberOfRequiredParameters()) {
                throw new ConfigException(sprintf('Too few arguments for class "%s"', $class));
            }
            $instance = $reflection->newInstanceArgs($arguments);
        }
        return $instance;
    }

    /**
     * @param Definition $definition
     * @return object
     */
    protected function createFromFactory(Definition $definition)
    {
        $factory = $definition->getFactory();
        if (is_array($factory)) {
            $factory = $this->resolveArguments($factory);
        }
        return call_user_func_array($factory, $this->resolveArguments($definition->getArguments()));
    }

    /**
     * @param Definition $definition
     * @param object $instance
     */
    protected function invokeMethods(Definition $definition, $instance)
    {
        foreach ($definition->getMethodCalls() as $method) {
            call_user_func_array([$instance, $method[0]], $this->resolveArguments($method[1]));
        }
    }

    /**
     * @param Definition $definition
     * @param object $instance
     */
    protected function invokeProperties(Definition $definition, $instance)
    {
        $properties = $this->resolveArguments($definition->getProperties());
        foreach ($properties as $name => $value) {
            $instance->$name = $value;
        }
    }

    /**
     * Resolves all arguments for the function or method.
     *
     * @param \ReflectionFunctionAbstract $method
     * @param array $arguments
     * @throws
     * @return array
     */
    public function resolveReflectionArguments(
        \ReflectionFunctionAbstract $method,
        array $arguments
    ) {
        $solvedArguments = [];
        foreach ($method->getParameters() as $parameter) {
            //If the dependency is provided directly
            if (isset($arguments[$parameter->getPosition()])) {
                $solvedArguments[] = $arguments[$parameter->getPosition()];
            } elseif (isset($arguments[$parameter->name])) {
                $solvedArguments[] = $arguments[$parameter->name];
            } elseif (($dependency = $parameter->getClass()) != null) {
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
     * @param array $arguments
     * @return array
     */
    protected function resolveArguments($arguments)
    {
        return array_map(function($argument) {
            if (is_array($argument)) {
                return $this->resolveArguments($argument);
            } else {
                return $this->formatArgument($argument);
            }
        }, $arguments);
    }

    /**
     * Formats argument value
     *
     * @param string $value
     * @return string
     * @throws DependencyInjectionException
     */
    protected function formatArgument($value)
    {
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