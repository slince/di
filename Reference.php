<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

class Reference
{
    /**
     * 服务名
     * @var string
     */
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
