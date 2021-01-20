<?php

declare(strict_types=1);

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
     *
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
        } elseif (is_array($concrete) || $concrete instanceof \Closure) {
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
                $arguments = $this->resolveDependencies($constructor->getParameters(), $arguments);
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
     *
     * @return object
     */
    protected function createFromFactory(Definition $definition)
    {
        $factory = $definition->getFactory();
        if (is_array($factory)) {
            $factory = $this->resolveArguments($factory);
        }
        return call_user_func_array($factory,
            $this->resolveArguments($definition->getArguments()) ?: [$this->container]
        );
    }

    /**
     * @param Definition $definition
     * @param object     $instance
     */
    protected function invokeMethods(Definition $definition, $instance)
    {
        foreach ($definition->getMethodCalls() as $method) {
            call_user_func_array([$instance, $method[0]], $this->resolveArguments($method[1]));
        }
    }

    /**
     * @param Definition $definition
     * @param object     $instance
     */
    protected function invokeProperties(Definition $definition, $instance)
    {
        $properties = $this->resolveArguments($definition->getProperties());
        foreach ($properties as $name => $value) {
            $instance->$name = $value;
        }
    }

    /**
     * Resolve dependencies.
     *
     * @param \ReflectionParameter[] $dependencies
     * @param array $arguments
     * @return array
     * @throws DependencyInjectionException
     */
    protected function resolveDependencies($dependencies, $arguments)
    {
        $solved = [];
        foreach ($dependencies as $dependency) {
            if (isset($arguments[$dependency->getPosition()])) {
                $solved[] = $arguments[$dependency->getPosition()];
                continue;
            }

            if (isset($arguments[$dependency->getName()])) {
                $solved[] = $arguments[$dependency->getName()];
                continue;
            }

            if (null !== ($type = $dependency->getType()) && !$type->isBuiltin()) {
                $solved[] = $this->container->get($type->getName());
                continue;
            }

            if ($dependency->isOptional()) {
                $solved[] = $dependency->getDefaultValue();
                continue;
            }

            throw new DependencyInjectionException(sprintf(
                'Unresolvable dependency resolving "%s" in class "%s"',
                $dependency->name,
                $dependency->getDeclaringClass()->getName()
            ));
        }
        return $solved;
    }

    /**
     * Resolves array of parameters.
     *
     * @param array $arguments
     *
     * @return array
     */
    protected function resolveArguments($arguments)
    {
        foreach ($arguments as &$argument) {
            if ($argument instanceof Reference) {
                $argument = $this->container->get($argument->getId());
                continue;
            }
        }
        return $arguments;
    }
}
