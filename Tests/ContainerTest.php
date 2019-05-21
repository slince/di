<?php

namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Di\Container;
use Slince\Di\Exception\ConfigException;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Exception\NotFoundException;
use Slince\Di\Tests\TestClass\Actor;
use Slince\Di\Tests\TestClass\ActorInterface;
use Slince\Di\Tests\TestClass\Bar;
use Slince\Di\Tests\TestClass\Director;
use Slince\Di\Tests\TestClass\Foo;
use Slince\Di\Tests\TestClass\Movie;

class ContainerTest extends TestCase
{
    public function testArrayAccess()
    {
        $container = new Container();
        $this->assertFalse($container->has('foo'));
        $container['foo'] = Foo::class;
        $this->assertTrue(isset($container['foo']));
        $this->assertInstanceOf(Foo::class, $container['foo']);
    }

    public function testFactory()
    {
        $container = new Container();
        $container->register('director1', function () {
            return new Director('James', 26);
        });
        $this->assertInstanceOf(Director::class, $container->get('director1'));
    }

    public function testArrayFactory()
    {
        $container = new Container();
        $container->register('director', [Director::class, 'factory'])
            ->setArguments(['James', 18]);

        $director = $container->get('director');
        $this->assertInstanceOf(Director::class, $director);
        $this->assertEquals('James', $director->getName());
        $this->assertEquals(18, $director->getAge());

        // ['@service', 'factory']
        $container->register('foo', Foo::class);
        $container->register('director2', ['@foo', 'createDirector'])
            ->setArguments(['James', 18]);

        $director2 = $container->get('director2');
        $this->assertEquals('James', $director2->getName());
        $this->assertEquals(18, $director2->getAge());

        $container->register('director2', ['@foo', 'createDirector'])
            ->setArguments([1 => 18, 0 => 'James']);

        $director2 = $container->get('director2');
        $this->assertEquals('James', $director2->getName());
        $this->assertEquals(18, $director2->getAge());
    }

    public function testFactoryWithParameters()
    {
        $container = new Container();
        $container->register('director', function ($age, $name) {
            return new Director($name, $age);
        })
            ->setArguments([1 => 18, 0 => 'James']);
        $director = $container->get('director');
        $this->assertEquals('James', $director->getName());
        $this->assertEquals(18, $director->getAge());
    }

    public function testInstance()
    {
        $container = new Container();
        $director = new Director();
        $container->register('director', $director);
        $this->assertTrue($container->has('director'));
        $this->assertInstanceOf(Director::class, $container->get('director'));
        $this->assertTrue($container->get('director') === $director);
        $this->assertTrue($container->get('director') === $container->get('director'));

        $container->register(new Director());
        $this->assertTrue($container->has(Director::class));
    }

    public function testRegister()
    {
        $container = new Container();
        $container->register('director', Director::class)
            ->setArguments([0 => 'Bob', 1 => 45]);
        $this->assertInstanceOf(Director::class, $director = $container->get('director'));
        $this->assertEquals('Bob', $director->getName());
        $this->assertEquals(45, $director->getAge());
    }

    public function testBind()
    {
        $container = new Container();
        $container->register('director', Director::class);
        $this->assertInstanceOf(Director::class, $container->get('director'));
    }

    public function testInterfaceBind()
    {
        $container = new Container();
        $container->register(ActorInterface::class, Actor::class);
        $this->assertInstanceOf(ActorInterface::class, $container->get(ActorInterface::class));
        $this->assertInstanceOf(Actor::class, $container->get(ActorInterface::class));

        $movie = $container->get(Movie::class);
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertInstanceOf(Actor::class, $movie->getActor());
    }

    public function testRegisterWithNumericArguments()
    {
        $container = new Container();
        $container->register('director', function ($name, $age) {
            return new Director($name, $age);
        })->addArgument('foo')
            ->addArgument('bar');
        $director = $container->get('director');
        $this->assertEquals('foo', $director->getName());
        $this->assertEquals('bar', $director->getAge());
    }

    public function testRegisterWithMethodCalls()
    {
        $container = new Container();
        $container->register(Director::class)
            ->addMethodCall('setAge', [20]);
        $director = $container->get(Director::class);
        $this->assertEquals(20, $director->getAge());

        $container->register('director2', Director::class)
            ->setMethodCalls([
                ['setAge', ['age' => 25]],
                ['setName', ['foo']],
            ]);

        $director = $container->get('director2');
        $this->assertEquals('foo', $director->getName());
        $this->assertEquals(25, $director->getAge());
    }

    public function testHas()
    {
        $container = new Container();
        $this->assertFalse($container->has('not_exists_class'));
        $this->assertFalse($container->has(Director::class));

        $container->register(new Director());
        $container->get(Director::class);
        $this->assertTrue($container->has(Director::class));

        $container->register(ActorInterface::class, Actor::class);
        $this->assertTrue($container->has(ActorInterface::class));

        $container = new Container();
        $container->register(new Director());
        $this->assertTrue($container->has(Director::class));
    }

    public function testGetWithMissingRequiredParameters()
    {
        $container = new Container();
        $container->register('bar', Bar::class);
        $this->expectException(DependencyInjectionException::class);
        $container->get('bar');
    }

    public function testGetWithMissingOptionalClassDependency()
    {
        $container = new Container();
        $container->register('director', function ($name, $age, ActorInterface $actor = null) {
            $this->assertNull($actor);

            return new Director($name, $age);
        })->setArguments([
            'name' => 'bob',
            'age' => 12,
        ]);
        $container->get('director');
    }

    public function testProperties()
    {
        $container = new Container();
        $container->register('director', Director::class);
        $container->register('foo1', Foo::class)->setProperty('director', '@director');

        $this->assertSame($container->get('director'), $container->get('foo1')->director);

        $container->register('foo2', Foo::class)->setProperty('director', '@director');
        $this->assertSame($container->get('director'), $container->get('foo2')->director);
    }

    public function testShare()
    {
        $container = new Container();
        $container->register('director', function () {
            return new Director('James', 26);
        })->setShared(true);
        $this->assertTrue($container->get('director') === $container->get('director'));

        $container->register('director2', function () {
            return new Director('James', 26);
        })->setShared(false);
        $this->assertFalse($container->get('director2') === $container->get('director2'));
    }

    public function testConfigureShare()
    {
        $container = new Container();
        $container->setDefaults([
            'share' => false,
        ]);
        $container->register('director', function () {
            return new Director('James', 26);
        });
        $this->assertFalse($container->get('director') === $container->get('director'));
    }

    public function testAutowire()
    {
        $container = new Container();
        $container->register(Movie::class)
            ->setAutowired(false);

        try {
            $container->get(Movie::class);
            $this->fail();
        } catch (\Exception $exception) {
            $this->assertInstanceOf(ConfigException::class, $exception);
        }

        $container->register(Movie::class)
            ->addArgument(new Director())
            ->addArgument(new Actor());
        $movie = $container->get(Movie::class);
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertInstanceOf(Director::class, $movie->getDirector());
        $this->assertInstanceOf(Actor::class, $movie->getActor());
    }

    public function testConfigureAutowire()
    {
        $container = new Container();
        $container->setDefaults([
            'autowire' => false,
        ]);
        $container->register(Movie::class);

        try {
            $container->get(Movie::class);
            $this->fail();
        } catch (\Exception $exception) {
            $this->assertInstanceOf(ConfigException::class, $exception);
        }
    }

    public function testReference()
    {
        $container = new Container();
        $container->register('director', Director::class);
        $container->register('actor', Actor::class);
        $container->register(Movie::class)
            ->addArgument('@director')
            ->addArgument('@actor');

        $movie = $container->get(Movie::class);
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertSame($container->get('director'), $movie->getDirector());
        $this->assertSame($container->get('actor'), $movie->getActor());
    }

    public function testParameters()
    {
        $container = new Container();
        $container->setParameters([
            'foo' => 'bar',
        ]);
        $this->assertEquals('bar', $container->getParameter('foo'));
        $container->addParameters([
            'foo' => 'baz',
            'bar' => 'baz',
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
            'bar' => 45,
        ]);

        $container->register('director', function (array $profile) {
            return new Director($profile['name'], $profile['age']);
        })->setArguments([
            'profile' => [
                'name' => '%foo% Bob',
                'age' => '%bar%',
            ],
        ]);
        $director = $container->get('director');
        $this->assertEquals('James Bob', $director->getName());
        $this->assertEquals(45, $director->getAge());

        try {
            $container->register('director2', function (array $profile) {
                return new Director($profile['name'], $profile['age']);
            })->setArguments([
                'profile' => [
                    'name' => '%baz% Bob',
                    'age' => '%bar%',
                ],
            ]);
            $container->get('director2');
            $this->fail();
        } catch (\Exception $exception) {
            $this->assertContains('is not defined', $exception->getMessage());
        }
    }

    public function testSimpleGlobalParameter()
    {
        $container = new Container();
        $container->setParameters([
            'directorName' => 'James',
        ]);
        $container->register('director', function (Container $container) {
            return new Director($container->getParameter('directorName'), 26);
        });
        $this->assertEquals('James', $container->get('director')->getName());
    }

    public function testGlobalParameterUseDotAccess()
    {
        $container = new Container();
        $container->setParameters([
            'directorName' => 'James',
            'director' => [
                'age' => 26,
            ],
        ]);
        $container->register('director', Director::class)->setArguments([
            '%directorName%',
            '%director.age%',
        ]);
        $this->assertEquals('James', $container->get('director')->getName());
        $this->assertEquals(26, $container->get('director')->getAge());
    }

    public function testAlias()
    {
        $container = new Container();

        $container->register('director', function (array $profile) {
            return new Director($profile['name'], $profile['age']);
        })->setArguments([
            'profile' => [
                'name' => 'James',
                'age' => 45,
            ],
        ]);
        $container->setAlias('director-alias', 'director');
        $this->assertEquals('director', $container->getAlias('director-alias'));
        $this->assertSame($container->get('director'), $container->get('director-alias'));
    }

    public function testTags()
    {
        $container = new Container();
        $container->register('director', Director::class)
            ->addTag('my.tag', array('hello' => 'world'));

        $serviceIds = $container->findTaggedServiceIds('my.tag');
        $this->assertEquals([
              'director' => [['hello' => 'world']],
         ], $serviceIds);
    }

    public function testExtend()
    {
        $container = new Container();
        $container->register('director', Director::class)
            ->setArguments(['James', 18]);
        $container->extend('director')
            ->setArguments(['Bob', 19]);
        $this->assertEquals('Bob', $container->get('director')->getName());
        $this->assertEquals(19, $container->get('director')->getAge());

        try {
            $container->extend('director');
            $this->fail();
        } catch (\Exception $exception) {
            $this->assertInstanceOf(DependencyInjectionException::class, $exception);
        }

        try {
            $container->extend('a_non_exists_id');
            $this->fail();
        } catch (\Exception $exception) {
            $this->assertInstanceOf(NotFoundException::class, $exception);
        }
    }
}
