<?php
namespace Slince\Di\Tests;

use Slince\Di\Container;
use Slince\Di\Definition;
use Slince\Di\Reference;
use Slince\Di\Tests\TestClass\Director;

error_reporting(E_ALL ^ E_USER_DEPRECATED);

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $container;

    const DIRECTOR_CLASS = '\Slince\Di\Tests\TestClass\Director';

    const ACTOR_CLASS = '\Slince\Di\Tests\TestClass\Actor';

    const MOVIE_CLASS = '\Slince\Di\Tests\TestClass\Movie';

    public function setUp()
    {
        $this->container = new Container();
    }

    public function testCreate()
    {
        $this->assertInstanceOf(static::DIRECTOR_CLASS, $this->container->create(static::DIRECTOR_CLASS, ['ZhangSan', 26]));
    }

    public function testAlias()
    {
        $this->container->alias('movie', static::MOVIE_CLASS);
        $this->assertInstanceOf(static::MOVIE_CLASS, $this->container->get('movie'));
    }

    public function testSet()
    {
        $this->container->set('director', function () {
            return new Director('张三', 26);
        });
        $this->assertInstanceOf(static::DIRECTOR_CLASS, $this->container->get('director'));
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
        $this->assertFalse($this->container->get('director', true) === $this->container->get('director', true));
    }

    public function testSimpleGet()
    {
        $this->assertInstanceOf(static::MOVIE_CLASS, $this->container->get(static::MOVIE_CLASS));
    }

    public function testGetWithDefinition()
    {
        $this->container->setDefinition('director', new Definition(static::DIRECTOR_CLASS, ['LieJie', 16]));
        $this->assertInstanceOf(static::DIRECTOR_CLASS, $this->container->get('director'));
    }

    public function testGetWithDefinitionReference()
    {
        $this->container->setDefinition('director', new Definition(static::DIRECTOR_CLASS, ['LieJie', 16]));
        $this->container->setDefinition('movie', new Definition(static::MOVIE_CLASS, [
            new Reference('director')
        ]));
        $this->assertInstanceOf(static::MOVIE_CLASS, $this->container->get('movie'));
    }

    public function testParameters()
    {
        $this->container->setParameters([
            'foo' => 'bar'
        ]);
        $this->assertEquals('bar', $this->container->getParameter('foo'));
        $this->container->addParameters([
           'foo' => 'bar1'
        ]);
        $this->assertEquals('bar1', $this->container->getParameter('foo'));
        $this->container->setParameter('foo', 'bar2');
        $this->assertEquals('bar2', $this->container->getParameter('foo'));
    }

    public function testGetWithArguments()
    {
        $this->container->setParameters([
            'name' => 'LiAn',
            'age' => 48
        ]);
        $this->container->setDefinition('director', new Definition(static::DIRECTOR_CLASS, [
            '%name%',
            '%age%'
        ]));
        $director = $this->container->get('director');
        $this->assertEquals('LiAn', $director->getName());
        $this->assertEquals(48, $director->getAge());
    }

    public function testGetWithMethodCallArguments()
    {
        $this->container->setParameters([
            'name' => 'LiAn',
            'age' => 48
        ]);
        $this->container->setDefinition('director', new Definition(static::DIRECTOR_CLASS))
            ->setMethodCall('setName', ['%name%'])
            ->setMethodCall('setAge', ['%age%']);
        $director = $this->container->get('director');
        $this->assertEquals('LiAn', $director->getName());
        $this->assertEquals(48, $director->getAge());
    }

    public function testRecursiveParameters()
    {
        $this->container->setParameter('actor.profile.firstname', 'Jack');
        $this->container->setParameter('actor.profile.lastname', 'Chen');
        $this->container->setParameter('actor.profile.username', '%actor.profile.firstname% %actor.profile.lastname%');
        $this->container->setParameter('actor.profile.name', 'Jack');
        $this->container->setDefinition('actor', new Definition(static::ACTOR_CLASS, [
            [
                'fistname' => '%actor.profile.firstname%',
                'username' => '%actor.profile.username%',
            ]
        ]));
        $profile = $this->container->get('actor')->getProfile();
        $this->assertEquals('Jack', $profile['fistname']);
        $this->assertEquals('Jack Chen', $profile['username']);
    }
}
