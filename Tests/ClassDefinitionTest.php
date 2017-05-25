<?php
namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Di\ClassDefinition;
use Slince\Di\Tests\TestClass\Director;

class ClassDefinitionTest extends TestCase
{

    protected function createDefinition($class, $arguments = [], $methodCalls = [])
    {
        return new ClassDefinition($class, $arguments, $methodCalls);
    }
    
    public function testSetAndGetArgument()
    {
        $definition = $this->createDefinition(Director::class);
        $definition->setArgument(0, 'LiAn');
        $this->assertEquals('LiAn', $definition->getArgument(0));
    }

    public function testSetAndGetArguments()
    {
        $definition = $this->createDefinition(Director::class);
        $arguments = ['Jumi', 12];
        $definition->setArguments($arguments);
        $this->assertEquals($arguments, $definition->getArguments());
    }

    public function testSetAndGetMethodCall()
    {
        $definition = $this->createDefinition(Director::class);
        $definition->setMethodCall('setName', ['LiAn']);
        $this->assertEquals(['LiAn'], $definition->getMethodCall('setName'));
    }

    public function testSetAndGetMethodCalls()
    {
        $definition = $this->createDefinition(Director::class);
        $methodCalls = [
            'setName' => ['LiAn'],
            'setAge' => [18],
        ];
        $definition->setMethodCalls($methodCalls);
        $this->assertEquals($methodCalls, $definition->getMethodCalls());
    }
}
