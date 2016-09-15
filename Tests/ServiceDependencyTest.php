<?php
namespace Slince\Di\Tests;

use Slince\Di\Container;
use Slince\Di\ServiceDependency;

class ServiceDependencyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceDependency
     */
    protected $serviceDependency;

    protected $dependencyName = 'dependency';

    public function setUp()
    {
        $this->serviceDependency = new ServiceDependency($this->dependencyName, new Container());
    }

    public function testGetName()
    {
        $this->assertEquals($this->dependencyName, $this->serviceDependency->getName());
    }

    public function testSetName()
    {
        $this->serviceDependency->setName('dependency2');
        $this->assertEquals('dependency2', $this->serviceDependency->getName());
    }
}
