<?php
namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Di\Container;
use Slince\Di\Definition;
use Slince\Di\Exception\ConfigException;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Exception\NotFoundException;
use Slince\Di\Reference;
use Slince\Di\Tests\TestClass\Actor;
use Slince\Di\Tests\TestClass\ActorInterface;
use Slince\Di\Tests\TestClass\Actress;
use Slince\Di\Tests\TestClass\Director;
use Slince\Di\Tests\TestClass\Movie;

error_reporting(E_ALL ^ E_USER_DEPRECATED);

class ContainerTest extends TestCase
{
    public function testCall()
    {
        $container = new Container();
        $container->call('director1', function () {
            return new Director('James', 26);
        });
        $this->assertInstanceOf(Director::class, $container->get('director1'));
        $container->call('director2', [Director::class, 'factory']); //或者 'Slince\Di\Tests\TestClass\Director::factory'
        $this->assertInstanceOf(Director::class, $container->get('director2'));
        $this->expectException(ConfigException::class);
        $container->call('director', 'not-exists-function');
    }

    public function testCallParameters()
    {
        $container = new Container();
        $container->call('director', function ($age, Container $container, $name) {
            $this->assertInstanceOf(Container::class, $container);
            return new Director($name, $age);
        });
        $director = $container->get('director', ['name' => 'James', 'age' => 26]);
        $this->assertEquals('James', $director->getName());
        $this->assertEquals(26, $director->getAge());
    }

    public function testInstance()
    {
        $container = new Container();
        $director = new Director();
        $container->instance('director', $director);
        $this->assertTrue($container->has('director'));
        $this->assertInstanceOf(Director::class, $container->get('director'));
        $this->assertTrue($container->get('director') === $director);
        $this->assertTrue($container->get('director') === $container->get('director'));

        $container->instance(new Director());
        $this->assertTrue($container->has(Director::class));

        $this->expectException(ConfigException::class);
        $container->instance('not-an-object');

    }

    public function testDefine()
    {
        $container = new Container();
        $container->define('director', Director::class)
            ->setArguments( [0 => 'Bob', 1 => 45]);
        $this->assertInstanceOf(Director::class, $director = $container->get('director'));
        $this->assertEquals('Bob', $director->getName());
        $this->assertEquals(45, $director->getAge());
    }

    public function testSimpleBind()
    {
        $container = new Container();
        $container->bind('director', Director::class);
        $this->assertInstanceOf(Director::class, $container->get('director'));
    }

    public function testInterfaceBind()
    {
        $container = new Container();
        $container->bind(ActorInterface::class, Actor::class);
        $this->assertInstanceOf(ActorInterface::class, $container->get(ActorInterface::class));
        $this->assertInstanceOf(Actor::class, $container->get(ActorInterface::class));

        $movie = $container->get(Movie::class);
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertInstanceOf(Actor::class, $movie->getActor());
    }

    public function testInterfaceBindForClassContext()
    {
        $container = new Container();

        $container->bind(ActorInterface::class, Actor::class, Movie::class);
        $container->define('movie', Movie::class)->setMethodCalls([
            'setActress' => []
        ]);

        $movie = $container->get('movie');
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertInstanceOf(Actor::class, $movie->getActor());
        $this->assertInstanceOf(Actor::class, $movie->getActress());

        $this->expectException(NotFoundException::class);
        $this->assertInstanceOf(Actor::class, $container->get(ActorInterface::class));
    }

    public function testInterfaceBindForClassMethodContext()
    {
        $container = new Container();

        $container->bind(ActorInterface::class, Actor::class, [Movie::class, '__construct']); //构造函数
        $container->bind(ActorInterface::class, Actress::class, [Movie::class, 'setActress']); //setter方法

        $container->define('movie', Movie::class)->setMethodCalls([
            'setActress' => []
        ]);

        $movie = $container->get('movie');
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertInstanceOf(Actor::class, $movie->getActor());
        $this->assertInstanceOf(Actress::class, $movie->getActress());
    }

    public function testGetContextBindings()
    {
        $container = new Container();
        $container->bind(ActorInterface::class, Actor::class, Movie::class); //构造函数
        $this->assertEquals([ActorInterface::class => Actor::class], $container->getContextBindings(Movie::class, '__construct'));
        $this->assertEquals([ActorInterface::class => Actor::class], $container->getContextBindings(Movie::class, 'no_exists_method'));


        $container = new Container();
        $container->bind(ActorInterface::class, Actor::class, [Movie::class,  'setActress']); //构造函数
        $this->assertEquals([], $container->getContextBindings(Movie::class, '__construct'));
        $this->assertEquals([ActorInterface::class => Actor::class], $container->getContextBindings(Movie::class, 'setActress'));
    }

    public function testHas()
    {
        $container = new Container();
        $this->assertFalse($container->has('not_exists_class'));
        $this->assertTrue($container->has(Director::class));

        $container->instance(new Director());
        $container->get(Director::class);
        $this->assertTrue($container->has(Director::class));

        $container->bind(ActorInterface::class, Actor::class);
        $this->assertTrue($container->has(ActorInterface::class));

        $container = new Container();
        $container->instance(new Director());
        $this->assertTrue($container->has(Director::class));

    }

    public function testShare()
    {
        $container = new Container();
        $container->call('director', function () {
            return new Director('James', 26);
        });
        $container->share('director');
        $this->assertInstanceOf(Director::class, $container->get('director'));
        $this->assertTrue($container->get('director') === $container->get('director'));
    }

    public function testSet()
    {
        $container = new Container();
        //Similar to call
        $container->set('director', function () {
            return new Director('James', 26);
        });
        $this->assertInstanceOf(Director::class, $container->get('director'));
        $this->assertFalse($container->get('director') === $container->get('director'));

        //Similar to define
        $container->set('director2', Director::class);
        $this->assertInstanceOf(Director::class, $container->get('director2'));
        $this->assertFalse($container->get('director2') === $container->get('director2'));

        //Similar to instance
        $container->set('director3', new Director());
        $this->assertInstanceOf(Director::class, $container->get('director3'));
        $this->assertTrue($container->get('director3') === $container->get('director3'));

        $this->expectException(ConfigException::class);
        $container->set('service', ['hello' => 'world']);
    }

    public function testSetWithShare()
    {
        $container = new Container();
        //Similar to delegate
        $container->set('director', function () {
            return new Director('James', 26);
        });
        $container->share('director');
        $this->assertInstanceOf(Director::class, $container->get('director'));
        $this->assertTrue($container->get('director') === $container->get('director'));

        //Similar to bind
        $container->set('director2', Director::class);
        $container->share('director2');
        $this->assertInstanceOf(Director::class, $container->get('director2'));
        $this->assertTrue($container->get('director2') === $container->get('director2'));

        //Similar to instance
        $container->set('director3', new Director());
        $container->share('director3');
        $this->assertInstanceOf(Director::class, $container->get('director3'));
        $this->assertTrue($container->get('director3') === $container->get('director3'));
    }

    public function testGetWithArguments()
    {
        $container = new Container();
        $director = $container->get(Director::class, [
            'age' => 26
        ]);
        //变量名索引
        $this->assertEquals(26, $director->getAge());
        //数字索引
        $director = $container->get(Director::class, [
            1 => 18
        ]);
        $this->assertEquals(18, $director->getAge());
    }

    public function testGetWithReference()
    {
        $container = new Container();
        $container->set('director', new Director('Bob', 45));
        $container->bind(ActorInterface::class, Actor::class);

        $movie = $container->get(Movie::class);
        $this->assertEquals(18, $movie->getDirector()->getAge()); //Defaults age

        $movie = $container->get(Movie::class, [
            new Reference('director')
        ]);
        $this->assertEquals('Bob', $movie->getDirector()->getName());
        $this->assertEquals(45, $movie->getDirector()->getAge());
    }

    public function testGetWithMissingRequiredParameters()
    {
        $container = new Container();
        $container->call('director', function($name, $age){
            return new Director($name, $age);
        });
        $this->expectException(DependencyInjectionException::class);
        $container->get('director', [1 => 18]);
    }

    public function testGetWithMissingOptionalClassDependency()
    {
        $container = new Container();
        $container->call('director', function($name, $age, ActorInterface $actor = null){
            $this->assertNull($actor);
            return new Director($name, $age);
        });
        $container->get('director', [0 => 'James', 1 => 18]);
    }

    public function testParameters()
    {
        $container = new Container();
        $container->setParameters([
            'foo' => 'bar'
        ]);
        $this->assertEquals('bar', $container->getParameter('foo'));
        $container->addParameters([
            'foo' => 'baz',
            'bar' => 'baz'
        ]);
        $this->assertEquals(['foo' => 'baz', 'bar' => 'baz'], $container->getParameters());
        $container->setParameter('bar', 'baz');
        $this->assertEquals('baz', $container->getParameter('bar'));
    }

    public function testResolveParameters()
    {
        $container = new Container();
        $container->setParameters([
            'foo' => 'James',
            'bar' => 45
        ]);
        $container->call('director', function(array $profile){
            return new Director($profile['name'], $profile['age']);
        });
        $director = $container->get('director', [
            'profile' => [
                 'name' => '%foo% Bob',
                 'age' => '%bar%',
            ]
        ]);
        $this->assertEquals('James Bob', $director->getName());
        $this->assertEquals(45, $director->getAge());

        try {
             $container->get('director', [
                'profile' => [
                    'name' => '%foo% Bob',
                    'age' => '%baz%',
                ]
            ]);
            $this->fail();
        } catch (\Exception $exception) {
            $this->assertContains('is not defined', $exception->getMessage());
        }

        try {
            $container->get('director', [
                'profile' => [
                    'name' => '%baz% Bob',
                    'age' => '%bar%',
                ]
            ]);
            $this->fail();
        } catch (\Exception $exception) {
            $this->assertContains('is not defined', $exception->getMessage());
        }
    }

    public function testSimpleGlobalParameter()
    {
        $container = new Container();
        $container->setParameters([
            'directorName' => 'James'
        ]);
        $container->call('director', function (Container $container) {
            return new Director($container->getParameter('directorName'), 26);
        });
        $this->assertEquals('James', $container->get('director')->getName());
    }

    public function testGlobalParameter()
    {
        $container = new Container();
        $container->setParameters([
            'directorName' => 'James',
            'director' => [
                'age' => 26
            ]
        ]);
        $container->define('director', Director::class)->setArguments([
            '%directorName%',
            '%director.age%'
        ]);
        $this->assertEquals('James', $container->get('director')->getName());
        $this->assertEquals(26, $container->get('director')->getAge());

        $this->assertEquals(26, $container->get(Director::class, [
            'age' => '%director.age%'
        ])->getAge());
    }
}
