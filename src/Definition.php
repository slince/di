<?php
/**
 * slince dependency injection library
 * @author Taosikai <taosikai@yeah.net>
 */
namespace Slince\Di;

use Slince\Di\Exception\DependencyInjectionException;

class Definition
{

    private $_class;

    private $_args = [];

    private $_calls = [];

    private $_di;

    function __construct($class, Container $di)
    {
        $this->_class = $class;
        $this->_di = $di;
    }

    function setArg($varName, $arg)
    {
        $this->_args[$varName] = $arg;
    }

    function setArgs($args)
    {
        $this->_args = $args;
    }

    function withCall($methodName, $value)
    {
        $this->_calls[$methodName] = $value;
    }

    function withCalls($calls)
    {
        $this->_calls = $calls;
    }

    /**
     * 实例化该类，但必须提供所有的相关定义
     *
     * @throws DependencyInjectionException
     */
    function newInstance()
    {
        try {
            $reflection = new \ReflectionClass($this->_class);
        } catch (\ReflectionException $e) {
            throw new DependencyInjectionException(sprintf('The class "%s" is invalid', $this->_class));
        }
        $constructor = $reflection->getConstructor();
        $instance = '';
        if (! is_null($constructor)) {
            $constructorArgs = [];
            $params = $constructor->getParameters();
            foreach ($params as $param) {
                $varName = $param->getName();
                if (isset($this->_args[$varName])) {
                    $class = $param->getClass();
                    if (is_null($class) || $this->_args[$varName] instanceof $class->name) {
                        $constructorArgs[] = $this->_args[$varName];
                    } else {
                        throw new DependencyInjectionException(sprintf('The value for param "%s" must be instanceof "%s"', $varName, $class));
                    }
                } elseif ($param->isOptional()) {
                    $constructorArgs[] = $param->getDefaultValue();
                } else {
                    throw new DependencyInjectionException(sprintf('Param "%s" must be provided', $varName));
                }
            }
            $instance = $reflection->newInstanceArgs($constructorArgs);
        } else {
            $instance = $reflection->newInstanceWithoutConstructor();
        }
        if (! empty($this->_calls)) {
            foreach ($this->_calls as $method => $value) {
                try {
                    $methodReflection = $reflection->getMethod($method);
                } catch (\ReflectionException $e) {
                    throw new DependencyInjectionException(sprintf('Class "%s" dont have method "%s"', $this->_class, $method));
                }
                $methodReflection->invoke($instance, $value);
            }
        }
        return $instance;
    }
}