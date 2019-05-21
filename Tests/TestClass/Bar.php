<?php

namespace Slince\Di\Tests\TestClass;

class Bar
{
    protected $baz;

    public function __construct($baz)
    {
        $this->baz = $baz;
    }
}