<?php
namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Di\Definition;
use Slince\Di\Tests\TestClass\Director;
use Slince\Di\Tests\TestClass\Foo;

class DefinitionTest extends TestCase
{
    public function testConcrete()
    {
        $definition = new Definition(Director::class);
        $this->assertEquals(Director::class, $definition->getConcrete());
        $definition->setConcrete(Foo::class);
        $this->assertEquals(Foo::class, $definition->getConcrete());
    }
    public function testSetAndGetArgument()
    {
        $definition = new Definition(Director::class);
        $definition->setArgument(0, 'LiAn');
        $this->assertEquals('LiAn', $definition->getArgument(0));

        $arguments = ['Jumi', 12];
        $definition->setArguments($arguments);
        $this->assertEquals($arguments, $definition->getArguments());
    }

    public function testSetAndGetMethodCall()
    {
        $definition = new Definition(Director::class);
        $definition->addMethodCall('setName', ['LiAn']);
        $this->assertEquals(['setName', ['LiAn']], $definition->getMethodCalls()[0]);

        $definition->setMethodCalls([
            ['setName', ['LiAn']],
            ['setAge', [20]],
        ]);
        $this->assertTrue($definition->hasMethodCall('setName'));
        $this->assertTrue($definition->hasMethodCall('setAge'));
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
        $definition = new Definition('foo');
        $definition->setAutowired(true);
        $this->assertTrue($definition->isAutowired());
    }

    public function testShare()
    {
        $definition = new Definition('foo');
        $definition->setShared(true);
        $this->assertTrue($definition->isShared());
    }

    public function testTag()
    {
        $definition = new Definition('foo');
        $definition->addTag('my.tag');
        $this->assertEquals([[]], $definition->getTag('my.tag'));
        $definition->addTag('my.tag1');
        $this->assertEquals([
            'my.tag' => [[]],
            'my.tag1' => [[]],
        ], $definition->getTags());
        $definition->clearTag('my.tag');
        $this->assertFalse($definition->hasTag('my.tag'));

        $definition->clearTags();
        $this->assertFalse($definition->hasTag('my.tag1'));
    }
}