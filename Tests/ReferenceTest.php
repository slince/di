<?php
namespace Slince\Di\Tests;

use Slince\Di\Reference;

class ReferenceTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $reference = new Reference('di');
        $this->assertEquals('di', $reference->getName());
    }

    public function testSetName()
    {
        $reference = new Reference('name1');
        $this->assertEquals('name1', $reference->getName());
        $reference->setName('name2');
        $this->assertEquals('name2', $reference->getName());
    }
}
