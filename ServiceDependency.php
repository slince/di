<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

class ServiceDependency implements DependencyInterface
{

    /**
     * 服务名
     * @var string
     */
    private $_name = '';

    /**
     * di容器
     * 
     * @var Container
     */
    private $_container;

    function __construct($name, $container)
    {
        $this->_name = $name;
        $this->_container = $container;
    }

    /**
     * 获取依赖容器
     */
    function getContainer()
    {
        return $this->_container;
    }

    /**
     * 设置容器
     */
    function setContainer(Container $container)
    {
        $this->_container = $container;
    }

    /**
     * 获取服务名
     * 
     * @return string
     */
    function getName()
    {
        return $this->_name;
    }

    /**
     * 设置服务名
     * 
     * @param string $name            
     */
    function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * 获取依赖
     */
    function getDependency()
    {
        return $this->_container->get($this->_name);
    }
}