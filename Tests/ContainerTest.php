<?php
namespace Slince\Di\Tests;

use Slince\Di\Container;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Tests\TestClass\Actor;
use Slince\Di\Tests\TestClass\ActorInterface;
use Slince\Di\Tests\TestClass\Actress;
use Slince\Di\Tests\TestClass\Director;
use Slince\Di\Tests\TestClass\Movie;

error_reporting(E_ALL ^ E_USER_DEPRECATED);

class ContainerTest extends \PHPUnit_Framework_TestCase
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
            return new Director('James', 26);
        });
        $this->assertInstanceOf(Director::class, $container->get('director1'));
        $container->delegate('director2', [Director::class, 'factory']); //或者 'Slince\Di\Tests\TestClass\Director::factory'
        $this->assertInstanceOf(Director::class, $container->get('director2'));
    }

    /**
     * 测试对象绑定，对象绑定结果是单例
     */
    public function testInstance()
    {
        $container = $this->getContainer();
        $director = new Director();
        $container->instance('director', $director);
        $this->assertInstanceOf(Director::class, $container->get('director'));
        $this->assertTrue($container->get('director') === $director);
        //instance只能是单例
        $this->assertTrue($container->get('director') === $container->get('director'));
    }

    /**
     * 类名<=>别名，interface <=> implement绑定
     */
    public function testSimpleBind()
    {
        $container = $this->getContainer();
        //简单的别名绑定
        $container->bind('director', Director::class);
        $this->assertInstanceOf(Director::class, $container->get('director'));
        //兼容旧版本别名绑定
        $container->alias('director2', Director::class);
        $this->assertInstanceOf(Director::class, $container->get('director2'));
    }

    /**
     * 绑定接口与实现
     */
    public function testInterfaceBind()
    {
        $container = $this->getContainer();
        //接口与实现；类绑定
        $container->bind(ActorInterface::class, Actor::class);
        //直接获取接口实例
        $this->assertInstanceOf(Actor::class, $container->get(ActorInterface::class));
        //获取依赖该接口的类实例
        $movie = $container->get(Movie::class);
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertInstanceOf(Actor::class, $movie->getActor());
    }

    /**
     * 为类设置接口依赖
     */
    public function testClassContextBind()
    {

        $container = $this->getContainer();
        //为Movie类声明接口实际指向
        $container->bind(ActorInterface::class, Actor::class, Movie::class);

        //获取依赖该接口的类实例，由于构造方法与setter皆是类依赖故container可以自动解决
        $container->define('movie', Movie::class, [], [
            'setActress' => []
        ]);

        $movie = $container->get('movie');
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertInstanceOf(Actor::class, $movie->getActor());
        $this->assertInstanceOf(Actor::class, $movie->getActress());

        //直接获取接口实例,会报出异常
        $this->setExpectedException(DependencyInjectionException::class);
        $this->assertInstanceOf(Actor::class, $container->get(ActorInterface::class));
    }

    /**
     * 为类方法设置接口依赖
     */
    public function testClassMethodContextBind()
    {
        $container = $this->getContainer();
        //为Movie类声明接口依赖
        $container->bind(ActorInterface::class, Actor::class, [Movie::class, '__construct']); //构造函数
        $container->bind(ActorInterface::class, Actress::class, [Movie::class, 'setActress']); //setter方法

        //获取依赖该接口的类实例，由于构造方法与setter皆是类依赖故container可以自动解决
        $container->define('movie', Movie::class, [], [
            'setActress' => []
        ]);
        $movie = $container->get('movie');
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertInstanceOf(Actor::class, $movie->getActor());
        $this->assertInstanceOf(Actress::class, $movie->getActress());
    }

    public function testShare()
    {
        $container = $this->getContainer();
        $container->delegate('director', function () {
            return new Director('James', 26);
        });
        $container->share('director');
        $this->assertInstanceOf(Director::class, $container->get('director'));
        $this->assertTrue($container->get('director') === $container->get('director'));

        //兼容旧的api,已提示废除
        $container->share('director2', function () {
            return new Director('James', 26);
        });
        $this->assertTrue($container->get('director2') === $container->get('director2'));
    }

    public function testGetWithArguments()
    {
        $container = $this->getContainer();
        $director = $container->get(Director::class, [
            'age' => 26
        ]);
        //变量名索引
        $this->assertEquals(26, $director->getAge());
        //数字索引
        $director = $container->get(Director::class, [
            'age' => 18
        ]);
        $this->assertEquals(18, $director->getAge());
    }

    public function testSimpleGlobalParameter()
    {
        $container = $this->getContainer();
        $container->setParameters([
            'directorName' => 'James'
        ]);
        $container->delegate('director', function (Container $container) {
            return new Director($container->getParameter('directorName'), 26);
        });
        $this->assertEquals('James', $container->get('director')->getName());
    }

    public function testGlobalParameter()
    {
        $container = $this->getContainer();
        $container->setParameters([
            'directorName' => 'James',
            'director' => [ //支持点号获取深度数据结构
                'age' => 26
            ]
        ]);
        //支持点号访问
        $container->define('director', Director::class, [
            '%directorName%',
            '%director.age%'
        ]);
        $this->assertEquals('James', $container->get('director')->getName());
        $this->assertEquals(26, $container->get('director')->getAge());

        //在get操作时传入参数
        $this->assertEquals(26, $container->get(Director::class, [
            'age' => '%director.age%'
        ])->getAge());
    }
}
