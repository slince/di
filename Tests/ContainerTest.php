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

    public function setUp()
    {
        $this->container = new Container();
    }

    public function testCreate()
    {
        $class = '\Slince\Di\Tests\TestClass\Director';
        $this->assertInstanceOf($class, $this->container->create($class, ['ZhangSan', 26]));
    }

    public function testAlias()
    {
        $class = '\Slince\Di\Tests\TestClass\Movie';
        $this->container->alias('movie', $class);
        $this->assertInstanceOf($class, $this->container->get('movie'));
    }

    public function testSet()
    {
        $this->container->set('director', function () {
            return new Director('张三', 26);
        });
        $this->assertInstanceOf('\Slince\Di\Tests\TestClass\Director', $this->container->get('director'));
    }

    public function testShare()
    {
        $this->container->set('director', function () {
            return new Director('张三', 26);
        });
        $this->assertFalse($this->container->get('director') === $this->container->get('director'));
        $this->container->share('director', function () {
            return new Director('张三', 26);
        });
        $this->assertTrue($this->container->get('director') === $this->container->get('director'));
    }

    public function testSimpleGet()
    {
        $class = '\Slince\Di\Tests\TestClass\Movie';
        $this->assertInstanceOf($class, $this->container->get($class));
    }

    public function testGetWithDefinition()
    {
        $class = '\Slince\Di\Tests\TestClass\Director';
        $this->container->setDefinition('director', new Definition($class, ['LieJie', 16]));
        $this->assertInstanceOf($class, $this->container->get('director'));
    }

    public function testGetWithDefinitionDependency()
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
