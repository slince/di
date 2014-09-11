<?php
/**
 * slince dependency injection library
 * @author Taosikai <taosikai@yeah.net>
 */
namespace Slince\Di;

use Slince\Di\Exception\DependencyInjectionException;

class Definition
{

    /**
     * 当前类
     *
     * @var string
     */
    private $_class;

    /**
     * 构造参数
     *
     * @var array
     */
    private $_args = [];

    /**
     * setter函数
     *
     * @var array
     */
    private $_calls = [];

    /**
     * di容器
     *
     * @var Container
     */
    private $_di;

    function __construct($class, Container $di)
    {
        $this->_class = $class;
        $this->_di = $di;
    }

    /**
     * 设置一个构造参数
     *
     * @param string $varName            
     * @param mixed $arg            
     */
    function setArg($varName, $arg)
    {
        $this->_args[$varName] = $arg;
    }

    /**
     * 批量设置构造参数
     *
     * @param array $args            
     */
    function setArgs($args)
    {
        $this->_args = $args;
    }

    /**
     * 设置一个setter函数
     *
     * @param string $methodName            
     * @param mixed $value            
     */
    function withCall($methodName, $value)
    {
        $this->_calls[$methodName] = $value;
    }

    /**
     * 批量设置构造参数
     *
     * @param array $calls            
     */
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
            foreach ($constructor->getParameters() as $param) {
                $varName = $param->getName();
                // 如果定义过依赖 则直接获取
                if (isset($this->_args[$varName])) {
                    $constructorArgs[] = $this->_args[$varName];
                } elseif (($dependency = $param->getClass()) != null) {
                    $constructorArgs[] = $this->_di->get($dependency->getName());
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