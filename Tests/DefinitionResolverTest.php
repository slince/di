<?php
namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Di\Definition;
use Slince\Di\DefinitionResolver;
use Slince\Di\Container;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Tests\TestClass\ActorInterface;
use Slince\Di\Tests\TestClass\Director;

class DefinitionResolverTest extends TestCase
{
    public function testResolve()
    {
        $container = new Container();
        $resolver =  new DefinitionResolver($container);
        $definition = new Definition(Director::class);
        $this->assertInstanceOf(Director::class, $resolver->resolve($definition));
    }

    public function testResolveInvalidClass()
    {
        $container = new Container();
        $resolver =  new DefinitionResolver($container);
        $definition = new Definition('invalid-class');
        $this->expectException(DependencyInjectionException::class);
        $resolver->resolve($definition);
    }

    public function testResolveNotInstantiateClass()
    {
        $container = new Container();
        $resolver =  new DefinitionResolver($container);
        $definition = new Definition(ActorInterface::class);
        $this->expectException(DependencyInjectionException::class);
        $resolver->resolve($definition);
    }

    public function testResolveWithNotExistMethod()
    {
        $container = new Container();
        $resolver =  new DefinitionResolver($container);
        $definition = new Definition(Director::class);
        $definition->addMethodCall('not_exists_method', 'foo');
        $this->expectException(DependencyInjectionException::class);
        $resolver->resolve($definition);
    }

    public function testResolveWithProperty()
    {
        $container = new Container();
        $resolver =  new DefinitionResolver($container);
        $definition = new Definition(Director::class);
        $definition->setProperties([
            'gender' => 'male'
        ]);
        $this->assertEquals('male', $resolver->resolve($definition)->gender);
        $definition->setProperty('no-exist-property', 'foo');
        $this->expectException(DependencyInjectionException::class);
        $resolver->resolve($definition);
    }
}