<?php
namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Di\ClassDefinition;
use Slince\Di\Tests\TestClass\Director;

class ClassDefinitionTest extends TestCase
{
    public function testSetAndGetArgument()
    {
        $definition = new ClassDefinition(Director::class);
        $definition->setArgument(0, 'LiAn');
        $this->assertEquals('LiAn', $definition->getArgument(0));
    }

    public function testSetAndGetArguments()
    {
        $definition = new ClassDefinition(Director::class);
        $arguments = ['Jumi', 12];
        $definition->setArguments($arguments);
        $this->assertEquals($arguments, $definition->getArguments());
    }

    public function testSetAndGetMethodCall()
    {
        $definition = new ClassDefinition(Director::class);
        $definition->setMethodCall('setName', ['LiAn']);
        $this->assertEquals(['LiAn'], $definition->getMethodCall('setName'));
    }

    public function testSetAndGetMethodCalls()
    {
        $definition = new ClassDefinition(Director::class);
        $methodCalls = [
            'setName' => ['LiAn'],
            'setAge' => [18],
        ];
        $definition->setMethodCalls($methodCalls);
        $this->assertEquals($methodCalls, $definition->getMethodCalls());
    }

    public function testProperty()
    {
        $definition = new ClassDefinition(Director::class);
        $this->assertEmpty($definition->getProperties());
        $definition->setProperties([
            'foo' => 'bar'
        ]);
        $this->assertEquals(['foo'=>'bar'], $definition->getProperties());
        $definition->setProperty('bar', 'baz');
        $this->assertEquals('baz', $definition->getProperty('bar'));
    }
}
