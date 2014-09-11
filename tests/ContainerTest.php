<?php
include __DIR__ . '/classes.php';

use Slince\Di\Container;

class ContainerTest extends \PHPUnit_Framework_TestCase
{

    private $_fixture;

    function setUp()
    {
        $this->_fixture = new Container();
    }

    function tearDown()
    {
        unset($this->_fixture);
    }

    function testSetInstance()
    {
        $instance = new ClassA();
        $this->_fixture->set('ClassA', $instance);
        $_instance = $this->_fixture->get('ClassA');
        $this->assertInstanceOf('ClassA', $_instance);
        $this->assertEquals($instance, $_instance);
    }

    function testSetCallback()
    {
        $this->_fixture->set('ClassA', function ()
        {
            return new ClassA();
        });
        $instance = $this->_fixture->get('ClassA');
        $this->assertInstanceOf('ClassA', $instance);
    }
    
    function testSetNewInstance()
    {
        $this->_fixture->set('ClassC', function (){
            return $this->_fixture->newInstance('ClassC');
        });
        $instance = $this->_fixture->get('ClassC');
        $this->assertInstanceOf('ClassC', $instance);
    }
    
    function testAutoGet()
    {
        $instance = $this->_fixture->get('ClassC');
        $this->assertInstanceOf('ClassC', $instance);
    }
    
    function testDefinition()
    {
        $this->_fixture->describe('ClassD')->withCall('setStr2', 'world');
        $instance = $this->_fixture->get('ClassD');
        $this->assertNotEmpty($instance->echoStr());
        $this->assertInstanceOf('ClassD', $instance);
    }

    function testShare()
    {
        $this->_fixture->share('ClassA', function ()
        {
            return new ClassA();
        });
        $instance = $this->_fixture->get('ClassA');
        $instance2 = $this->_fixture->get('ClassA');
        $this->assertInstanceOf('ClassA', $instance);
        $this->assertInstanceOf('ClassA', $instance2);
        $this->assertEquals($instance, $instance2);
    }

    function testAlias()
    {
        $this->_fixture->set('ClassA', function ()
        {
            return new ClassA();
        });
        $this->_fixture->alias('aliasA', 'ClassA');
        $instance = $this->_fixture->get('ClassA');
        $instance2 = $this->_fixture->get('aliasA');
        $this->assertEquals($instance, $instance2);
    }
}