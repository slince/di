<?php
namespace Slince\Di\Tests;

use Slince\Di\Container;
use Slince\Di\Definition;
use Slince\Di\ServiceDependency;
use Slince\Di\Tests\TestClass\Director;

class ContainerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Container
     */
    protected $container;

    function setUp()
    {
        $this->container = new Container();
    }

    function testCreate()
    {
        $class = '\Slince\Di\Tests\TestClass\Director';
        $this->assertInstanceOf($class, $this->container->create($class, ['ZhangSan', 26]));
    }

    function testAlias()
    {
        $class = '\Slince\Di\Tests\TestClass\Movie';
        $this->container->alias('movie', $class);
        $this->assertInstanceOf($class, $this->container->get('movie'));
    }

    function testSet()
    {
        $this->container->set('director', function(){
            return new Director('张三', 26);
        });
        $this->assertInstanceOf('\Slince\Di\Tests\TestClass\Director', $this->container->get('director'));
    }

    function testShare()
    {
        $this->container->set('director', function(){
            return new Director('张三', 26);
        });
        $this->assertFalse($this->container->get('director') === $this->container->get('director'));
        $this->container->share('director', function(){
            return new Director('张三', 26);
        });
        $this->assertTrue($this->container->get('director') === $this->container->get('director'));
    }

    function testSimpleGet()
    {
        $class = '\Slince\Di\Tests\TestClass\Movie';
        $this->assertInstanceOf($class, $this->container->get($class));
    }

    function testGetWithDefinition()
    {
        $class = '\Slince\Di\Tests\TestClass\Director';
        $this->container->setDefinition('director', new Definition($class, ['LieJie', 16]));
        $this->assertInstanceOf($class, $this->container->get('director'));
    }

    function testGetWithDefinitionDependency()
    {
        $directorClass = '\Slince\Di\Tests\TestClass\Director';
        $movieClass = '\Slince\Di\Tests\TestClass\Movie';
        $this->container->setDefinition('director', new Definition($directorClass, ['LieJie', 16]));
        $this->container->setDefinition('movie', new Definition($movieClass, [
            new ServiceDependency('director', $this->container)
        ]));
        $this->assertInstanceOf($movieClass, $this->container->get('movie'));
    }

}