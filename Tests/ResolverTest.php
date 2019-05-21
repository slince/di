<?php

namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Di\Definition;
use Slince\Di\Resolver;
use Slince\Di\Container;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Tests\TestClass\ActorInterface;
use Slince\Di\Tests\TestClass\Director;

class ResolverTest extends TestCase
{
    public function testResolve()
    {
        $container = new Container();
        $resolver = new Resolver($container);
        $definition = new Definition(Director::class);
        $this->assertInstanceOf(Director::class, $resolver->resolve($definition));
    }

    public function testResolveInvalidClass()
    {
        $container = new Container();
        $resolver = new Resolver($container);
        $definition = new Definition('invalid-class');
        $this->expectException(DependencyInjectionException::class);
        $resolver->resolve($definition);
    }

    public function testResolveNotInstantiateClass()
    {
        $container = new Container();
        $resolver = new Resolver($container);
        $definition = new Definition(ActorInterface::class);
        $this->expectException(DependencyInjectionException::class);
        $resolver->resolve($definition);
    }

    public function testResolveWithProperty()
    {
        $container = new Container();
        $resolver = new Resolver($container);
        $definition = new Definition(Director::class);
        $definition->setProperties([
            'gender' => 'male',
        ]);
        $this->assertEquals('male', $resolver->resolve($definition)->gender);
        $definition->setProperty('no_exist_property', 'foo');
        $this->assertEquals('foo', $resolver->resolve($definition)->no_exist_property);
    }
}
