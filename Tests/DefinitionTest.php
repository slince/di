<?php
namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Di\Definition;
use Slince\Di\Tests\TestClass\Director;

class DefinitionTest extends TestCase
{
    public function testSetAndGetArgument()
    {
        $definition = new Definition(Director::class);
        $definition->setArgument(0, 'LiAn');
        $this->assertEquals('LiAn', $definition->getArgument(0));
    }

    public function testSetAndGetArguments()
    {
        $definition = new Definition(Director::class);
        $arguments = ['Jumi', 12];
        $definition->setArguments($arguments);
        $this->assertEquals($arguments, $definition->getArguments());
    }

    public function testSetAndGetMethodCall()
    {
        $definition = new Definition(Director::class);
        $definition->addMethodCall('setName', ['LiAn']);
        $this->assertEquals(['setName', ['LiAn']], $definition->getMethodCalls()[0]);
    }

    public function testProperty()
    {
        $definition = new Definition(Director::class);
        $this->assertEmpty($definition->getProperties());
        $definition->setProperties([
            'foo' => 'bar'
        ]);
        $this->assertEquals(['foo'=>'bar'], $definition->getProperties());
        $definition->setProperty('bar', 'baz');
        $this->assertEquals('baz', $definition->getProperty('bar'));
    }

    public function testAutowire()
    {
        $definition = new Definition();
        $this->assertNull($definition->isAutowired());
        $definition->setAutowired(true);
        $this->assertTrue($definition->isAutowired());
    }

    public function testShare()
    {
        $definition = new Definition();
        $this->assertNull($definition->isShared());
        $definition->setShared(true);
        $this->assertTrue($definition->isShared());
    }
}
