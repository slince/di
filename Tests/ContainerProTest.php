<?php
namespace Slince\Di\Tests;

use Slince\Di\Container;
use Slince\Di\Definition;
use Slince\Di\Reference;
use Slince\Di\Tests\TestClass\Director;

class ContainerProTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $container;

    public function setUp()
    {
        $this->container = new Container();
    }

    /**
     * 自定义闭包或者工厂方法代理
     */
    public function testDelegate()
    {
        $this->container->set('director1', function () {
            return new Director('张三', 26);
        });
        $this->assertInstanceOf(Director::class, $this->container->get('director1'));
        $this->container->set('director2', [Director::class, 'factory']); //或者 'Slince\Di\Tests\TestClass\Director::factory'
        $this->assertInstanceOf(Director::class, $this->container->get('director2'));
    }

    public function testInstance()
    {

    }
}