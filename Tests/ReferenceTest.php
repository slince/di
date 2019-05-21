<?php

namespace Slince\Di\Tests;

use PHPUnit\Framework\TestCase;

use Slince\Di\Reference;

class ReferenceTest extends TestCase
{
    public function testConstructor()
    {
        $reference = new Reference('di');
        $this->assertEquals('di', $reference->getId());
    }
}