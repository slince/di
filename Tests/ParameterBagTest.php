<?php
namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Di\ParameterBag;

class ParameterBagTest extends TestCase
{
    public function testParameter()
    {
        $parameters = new ParameterBag();
        $this->assertEquals([], $parameters->toArray());
        $parameters->setParameters([
            'foo' => 'bar'
        ]);
        $this->assertEquals([ 'foo' => 'bar'], $parameters->toArray());
        $this->assertEquals('bar', $parameters->getParameter('foo'));
        $parameters->setParameter('foo', 'baz');
        $this->assertEquals('baz', $parameters->getParameter('foo'));

        $parameters->addParameters([
            'foo' => 'bar',
            'bar' => 'baz'
        ]);
        $this->assertEquals([
            'foo' => 'bar',
            'bar' => 'baz'
        ],  $parameters->toArray());
    }
}