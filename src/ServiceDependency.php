<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

class ServiceDependency
{
    private $_name = '';
    
    function __construct($name)
    {
        $this->_name = $name;
    }
    
    function getName()
    {
        return $this->_name;
    }
}