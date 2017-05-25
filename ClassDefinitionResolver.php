<?php
/**
 * slince dependency injection component
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

use Slince\Di\Exception\DependencyInjectionException;

class ClassDefinitionResolver
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param ClassDefinition $definition
     * @param array $arguments
     * @return mixed
     */
    public function resolve(ClassDefinition $definition, $arguments = [])
    {
        $arguments = array_replace($definition->getArguments(), $arguments);
        try {
            $reflection = new \ReflectionClass($definition->getClass());
        } catch (\ReflectionException $e) {
            throw new DependencyInjectionException(sprintf('Class "%s" is invalid', $definition->getClass()));
        }
        if (!$reflection->isInstantiable()) {
            throw new DependencyInjectionException(sprintf("Can not instantiate [%s]", $definition->getClass()));
        }
        $constructor = $reflection->getConstructor();
        if (is_null($constructor)) {
            $instance = $reflection->newInstanceWithoutConstructor();
        } else {
            $constructorArguments = $this->container->resolveFunctionArguments($constructor, $arguments,
                $this->container->getContextBindings($definition->getClass(), $constructor->getName())
            );
            $instance = $reflection->newInstanceArgs($constructorArguments);
        }
        $this->invokeMethods($definition, $instance, $reflection);
        $this->invokeProperties($definition, $instance);
        return $instance;
    }

    protected function invokeMethods(ClassDefinition $definition, $instance, \ReflectionClass $reflection)
    {
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
            $contextBindings = $this->container->getContextBindings($reflection->name, $method);
            $reflectionMethod->invokeArgs($instance, $this->container->resolveFunctionArguments($reflectionMethod,
                $methodArguments, $contextBindings
            ));
        }
    }

    protected function invokeProperties(ClassDefinition $definition, $instance)
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
}