<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

class Reference
{
    /**
     * Service name
     * @var string
     */
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get service name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set service name
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
