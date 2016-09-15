<?php
namespace Slince\Di\Tests;

use Slince\Di\Definition;

class DefinitionTest extends \PHPUnit_Framework_TestCase
{
    protected $class = '\Slince\Di\Tests\TestClass\Director';

    protected function createDefinition($className, $arguments = [], $methodCalls = [])
    {
        return new Definition($className, $arguments, $methodCalls);
    }
    
    public function testSetAndGetArgument()
    {
        $definition = $this->createDefinition($this->class);
        $definition->setArgument(0, 'LiAn');
        $this->assertEquals('LiAn', $definition->getArgument(0));
    }

    public function testSetAndGetArguments()
    {
        $definition = $this->createDefinition($this->class);
        $arguments = ['Jumi', 12];
        $definition->setArguments($arguments);
        $this->assertEquals($arguments, $definition->getArguments());
    }

    public function testSetAndGetMethodCall()
    {
        $definition = $this->createDefinition($this->class);
        $definition->setMethodCall('setName', ['LiAn']);
        $this->assertEquals(['LiAn'], $definition->getMethodCall('setName'));
    }

    public function testSetAndGetMethodCalls()
    {
        $definition = $this->createDefinition($this->class);
        $methodCalls = [
            'setName' => ['LiAn'],
            'setAge' => [18],
        ];
        $definition->setMethodCalls($methodCalls);
        $this->assertEquals($methodCalls, $definition->getMethodCalls());
    }
}
