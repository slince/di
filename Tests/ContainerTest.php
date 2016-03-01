<?php
include __DIR__ . '/classes.php';

use Slince\Di\Container;
use Slince\Di\Definition;

class ContainerTest extends \PHPUnit_Framework_TestCase
{

    private $_container;

    function setUp()
    {
        $this->_container = new Container();
    }

    function tearDown()
    {
        unset($this->_container);
    }

    function testSetInstance()
    {
        $instance = new ClassA();
        $this->_container->set('ClassA', $instance);
        $_instance = $this->_container->get('ClassA');
        $this->assertInstanceOf('ClassA', $_instance);
        $this->assertEquals($instance, $_instance);
    }

    function testSetCallback()
    {
        $this->_container->set('ClassA', function ()
        {
            return new ClassA();
        });
        $instance = $this->_container->get('ClassA');
        $this->assertInstanceOf('ClassA', $instance);
    }
    
    function testSetNewInstance()
    {
        $this->_container->set('ClassC', function (){
            return $this->_container->create('ClassC');
        });
        $instance = $this->_container->get('ClassC');
        $this->assertInstanceOf('ClassC', $instance);
    }
    
    function testAutoGet()
    {
        $instance = $this->_container->get('ClassC');
        $this->assertInstanceOf('ClassC', $instance);
    }
    
    function testDefinition()
    {
        $this->_container->setDefinition('classd', new Definition(
            'ClassD',
            [],
            ['setStr2' => ['world']]
        ));
        $instance = $this->_container->get('classd');
        $this->assertNotEmpty($instance->echoStr());
        $this->assertInstanceOf('ClassD', $instance);
    }
    
    function testException()
    {
        $this->setExpectedException('Slince\Di\Exception\DependencyInjectionException');
        $arr = $this->_container->setDefinition('ClassD', new Definition('ClassD'))
           ->setMethodCall('setStr3', ['world'])
            ->getMethodCalls();
        $instance = $this->_container->get('ClassD');
    }

    function testShare()
    {
        $this->_container->share('ClassA', function ()
        {
            return new ClassA();
        });
        $instance = $this->_container->get('ClassA');
        $instance2 = $this->_container->get('ClassA');
        $this->assertInstanceOf('ClassA', $instance);
        $this->assertInstanceOf('ClassA', $instance2);
        $this->assertEquals($instance, $instance2);
    }

    function testAlias()
    {
        $this->_container->set('ClassA', function ()
        {
            return new ClassA();
        });
        $this->_container->alias('aliasA', 'ClassA');
        $instance = $this->_container->get('ClassA');
        $instance2 = $this->_container->get('aliasA');
        $this->assertEquals($instance, $instance2);
    }
}