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
    protected $class;

    /**
     * 构造参数
     * @var array
     */
    protected $arguments = [];

    /**
     * setter方法
     * @var array
     */
    protected $calls = [];

    /**
     * 属性赋值
     * @var array
     */
    protected $properties = [];

    public function __construct($class, array $arguments = [], array $calls = [], array $properties = [])
    {
        $this->class = $class;
        $this->arguments = $arguments;
        $this->calls = $calls;
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
     * 设置一个setter方法
     * @param string $method
     * @param array $arguments 方法所需要的参数，只需要给出标量依赖即可
     * @return $this
     */
    public function setMethodCall($method, array $arguments)
    {
        $this->calls[$method] = $arguments;
        return $this;
    }

    /**
     * 批量设置setter方法
     * @param array $calls
     * @return $this
     */
    public function setMethodCalls(array $calls)
    {
        $this->calls = array_merge($this->calls, $calls);
        return $this;
    }

    /**
     * 获取setter方法
     * @return array
     */
    public function getMethodCalls()
    {
        return $this->calls;
    }

    /**
     * 获取指定函数名下的方法
     * @param string $method
     * @return array|null
     */
    public function getMethodCall($method)
    {
        return isset($this->calls[$method]) ? $this->calls[$method] : null;
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
    public function getClass()
    {
        return $this->class;
    }
}
