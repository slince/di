<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
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
    private $className;

    /**
     * 构造参数
     *
     * @var array
     */
    private $arguments = [];

    /**
     * setter函数
     *
     * @var array
     */
    private $methodCalls = [];

    function __construct($className, $arguments = [], $methodCalls = [])
    {
        $this->className = $className;
        $this->arguments = $arguments;
        $this->methodCalls = $methodCalls;
    }

    /**
     * 设置一个构造参数
     *
     * @param int $index            
     * @param mixed $arg            
     */
    function setArgument($index, $value)
    {
        $this->arguments[$index] = $arg;
        return $this;
    }

    /**
     * 批量设置构造参数
     *
     * @param array $arguments            
     */
    function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * 获取所有的构造参数
     *
     * @return array
     */
    function getArguments()
    {
        return $this->arguments;
    }

    /**
     * 获取指定次序上的参数
     * 
     * @param int $index            
     * @return mixed
     */
    function getArgument($index)
    {
        return isset($this->arguments[$index]) ? $this->arguments[$index] : null;
    }

    /**
     * 设置一个setter函数
     *
     * @param string $method            
     * @param mixed $value            
     */
    function setMethodCall($method, array $arguments)
    {
        $this->methodCalls[$method] = $arguments;
        return $this;
    }

    /**
     * 批量设置构造参数
     *
     * @param array $methodCalls            
     */
    function setMethodCalls(array $methodCalls)
    {
        $this->methodCalls = array_merge($this->methodCalls, $methodCalls);
        return $this;
    }

    /**
     * 获取setter函数
     *
     * @return array
     */
    function getMethodCalls()
    {
        return $this->methodCalls;
    }

    /**
     * 获取指定函数名下的参数
     * 
     * @param string $method            
     * @return array|null
     */
    function getMethodCall($method)
    {
        return isset($this->methodCalls[$method]) ? $this->methodCalls[$method] : null;
    }

    /**
     * 获取当前类名
     *
     * @return string
     */
    function getClassName()
    {
        return $this->className;
    }
}