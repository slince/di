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
    private $_args = [];

    /**
     * setter函数
     *
     * @var array
     */
    private $_calls = [];

    function __construct($className)
    {
        $this->_className = $className;
    }

    /**
     * 设置一个构造参数
     *
     * @param string $varName            
     * @param mixed $arg            
     */
    function withArg($varName, $arg)
    {
        $this->_args[$varName] = $arg;
        return $this;
    }

    /**
     * 批量设置构造参数
     *
     * @param array $args            
     */
    function withArgs($args)
    {
        $this->_args = $args;
        return $this;
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
        return $this;
    }

    /**
     * 批量设置构造参数
     *
     * @param array $calls            
     */
    function withCalls($calls)
    {
        $this->_calls = $calls;
        return $this;
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

    /**
     * 获取所有的构造参数
     *
     * @return array
     */
    function getArgs()
    {
        return $this->_args;
    }

    /**
     * 获取setter函数
     *
     * @return array
     */
    function getCalls()
    {
        return $this->_calls;
    }
}