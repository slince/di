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
    protected $name = '';

    /**
     * di容器
     *
     * @var Container
     */
    private $container;

    public function __construct($name, $container)
    {
        $this->name = $name;
        $this->container = $container;
    }

    /**
     * 获取依赖容器
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * 设置容器
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * 获取服务名
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 设置服务名
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * 获取依赖
     */
    public function getDependency()
    {
        return $this->container->get($this->name);
    }
}
