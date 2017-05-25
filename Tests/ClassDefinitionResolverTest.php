<?php
namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Di\ClassDefinition;
use Slince\Di\ClassDefinitionResolver;
use Slince\Di\Container;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Tests\TestClass\ActorInterface;
use Slince\Di\Tests\TestClass\Director;

class ClassDefinitionResolverTest extends TestCase
{
    public function testResolve()
    {
        $container = new Container();
        $resolver =  new ClassDefinitionResolver($container);
        $definition = new ClassDefinition(Director::class);
        $this->assertInstanceOf(Director::class, $resolver->resolve($definition));
    }

    public function testResolveInvalidClass()
    {
        $container = new Container();
        $resolver =  new ClassDefinitionResolver($container);
        $definition = new ClassDefinition('invalid-class');
        $this->expectException(DependencyInjectionException::class);
        $resolver->resolve($definition);
    }

    public function testResolveNotInstantiateClass()
    {
        $container = new Container();
        $resolver =  new ClassDefinitionResolver($container);
        $definition = new ClassDefinition(ActorInterface::class);
        $this->expectException(DependencyInjectionException::class);
        $resolver->resolve($definition);
    }

    public function testResolveWithNotExistMethod()
    {
        $container = new Container();
        $resolver =  new ClassDefinitionResolver($container);
        $definition = new ClassDefinition(Director::class);
        $definition->setMethodCall('not_exists_method', 'foo');
        $this->expectException(DependencyInjectionException::class);
        $resolver->resolve($definition);
    }

    public function testResolveWithProperty()
    {
        $container = new Container();
        $resolver =  new ClassDefinitionResolver($container);
        $definition = new ClassDefinition(Director::class);
        $definition->setProperties([
            'gender' => 'male'
        ]);
        $this->assertEquals('male', $resolver->resolve($definition)->gender);
        $definition->setProperty('no-exist-property', 'foo');
        $this->expectException(DependencyInjectionException::class);
        $resolver->resolve($definition);
    }
}