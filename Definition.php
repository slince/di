<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

class Definition
{
    /**
     * 类名
     * @var string
     */
    protected $className;

    /**
     * 构造参数
     * @var array
     */
    protected $arguments = [];

    /**
     * setter函数
     * @var array
     */
    protected $methodCalls = [];

    /**
     * 属性赋值
     * @var array
     */
    protected $properties = [];

    public function __construct($className, array $arguments = [], array $methodCalls = [], array $properties = [])
    {
        $this->className = $className;
        $this->arguments = $arguments;
        $this->methodCalls = $methodCalls;
        $this->properties = $properties;
    }

    /**
     * 设置一个构造参数
     * @param int|string $indexOrName 参数名或者参数索引
     * @param mixed $argument
     * @return $this
     */
    public function setArgument($indexOrName, $argument)
    {
        $this->arguments[$indexOrName] = $argument;
        return $this;
    }


    /**
     * 批量设置构造参数
     * @param array $arguments
     * @return $this
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * 获取所有的构造参数
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * 获取指定次序上的参数
     * @param int|string $indexOrName
     * @return mixed
     */
    public function getArgument($indexOrName)
    {
        return isset($this->arguments[$indexOrName]) ? $this->arguments[$indexOrName] : null;
    }

    /**
     * 设置一个setter函数
     * @param string $method
     * @param array $arguments
     * @return $this
     */
    public function setMethodCall($method, array $arguments)
    {
        $this->methodCalls[$method] = $arguments;
        return $this;
    }

    /**
     * 批量设置构造参数
     * @param array $methodCalls
     * @return $this
     */
    public function setMethodCalls(array $methodCalls)
    {
        $this->methodCalls = array_merge($this->methodCalls, $methodCalls);
        return $this;
    }

    /**
     * 获取setter函数
     * @return array
     */
    public function getMethodCalls()
    {
        return $this->methodCalls;
    }

    /**
     * 获取指定函数名下的参数
     * @param string $method
     * @return array|null
     */
    public function getMethodCall($method)
    {
        return isset($this->methodCalls[$method]) ? $this->methodCalls[$method] : null;
    }

    /**
     * 获取所有的预定义属性值对
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * 获取当前类名
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}
