<?php
namespace Slince\Di\Tests;

use Slince\Di\Container;
use Slince\Di\Tests\TestClass\Actor;
use Slince\Di\Tests\TestClass\ActorInterface;
use Slince\Di\Tests\TestClass\Director;
use Slince\Di\Tests\TestClass\Movie;

class ContainerProTest extends \PHPUnit_Framework_TestCase
{
    public function getContainer()
    {
       return new Container();
    }

    /**
     * 自定义闭包或者工厂方法代理
     */
    public function testDelegate()
    {
        $container = $this->getContainer();
        $container->delegate('director1', function () {
            return new Director('张三', 26);
        });
        $this->assertInstanceOf(Director::class, $container->get('director1'));
        $this->getContainer()->delegate('director2', [Director::class, 'factory']); //或者 'Slince\Di\Tests\TestClass\Director::factory'
        $this->assertInstanceOf(Director::class, $container->get('director2'));
    }

    /**
     * 测试对象绑定，对象绑定结果是单例
     */
    public function testInstance()
    {
        $container = $this->getContainer();
        $director = new Director();
        $container->instance('director3', $director);
        $this->assertInstanceOf(Director::class, $this->getContainer()->get('director3'));
        $this->assertTrue($container->get('director3') === $director);
        //instance只能是单例
        $this->assertTrue($container->get('director3') === $container->get('director3'));
    }

    /**
     * 类名<=>别名，interface <=> implement绑定
     */
    public function testSimpleBind()
    {
        $container = $this->getContainer();
        //简单的别名绑定
        $container->bind('director4', Director::class);
        $this->assertInstanceOf(Director::class, $container->get('director4'));

    }

    /**
     * 绑定接口与实现
     */
    public function testBindInterface()
    {
        $container = $this->getContainer();
        //接口与实现；类绑定
        $container->bind(ActorInterface::class, Actor::class);
        $movie = $container->get(Movie::class);
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertInstanceOf(Actor::class, $movie->getActor());
    }

    /**
     * 带上下文绑定
     */
    public function testBindContext()
    {
        $container = $this->getContainer();
    }
}