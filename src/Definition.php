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
    private $_className;

    /**
     * 构造参数
     *
     * @var array
     */
    private $_arguments = [];

    /**
     * setter函数
     *
     * @var array
     */
    private $_methodCalls = [];

    function __construct($className, $arguments = [], $methodCalls = [])
    {
        $this->_className = $className;
        $this->_arguments = $arguments;
        $this->_methodCalls = $methodCalls;
    }

    /**
     * 设置一个构造参数
     *
     * @param int $index            
     * @param mixed $arg            
     */
    function setArgument($index, $value)
    {
        $this->_arguments[$index] = $arg;
        return $this;
    }

    /**
     * 批量设置构造参数
     *
     * @param array $arguments            
     */
    function setArguments(array $arguments)
    {
        $this->_arguments = $arguments;
        return $this;
    }

    /**
     * 获取所有的构造参数
     *
     * @return array
     */
    function getArguments()
    {
        return $this->_arguments;
    }

    /**
     * 获取指定次序上的参数
     * 
     * @param int $index            
     * @return mixed
     */
    function getArgument($index)
    {
        return isset($this->_arguments[$index]) ? $this->_arguments[$index] : null;
    }

    /**
     * 设置一个setter函数
     *
     * @param string $method            
     * @param mixed $value            
     */
    function setMethodCall($method, array $arguments = [])
    {
        $this->_methodCalls[$method] = $arguments;
    }

    /**
     * 批量设置构造参数
     *
     * @param array $methodCalls            
     */
    function setMethodCalls(array $methodCalls = [])
    {
        $this->_methodCalls = array_merge($this->_methodCalls, $methodCalls);
    }

    /**
     * 获取setter函数
     *
     * @return array
     */
    function getMethodCalls()
    {
        return $this->_methodCalls;
    }

    /**
     * 获取指定函数名下的参数
     * 
     * @param string $method            
     * @return array|null
     */
    function getMethodCall($method)
    {
        return isset($this->_methodCalls[$method]) ? $this->_methodCalls[$method] : null;
    }

    /**
     * 获取当前类名
     *
     * @return string
     */
    function getClassName()
    {
        return $this->_className;
    }
}