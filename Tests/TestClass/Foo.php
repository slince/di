<?php

namespace Slince\Di\Tests\TestClass;

class Foo
{
    /**
     * @var Director
     */
    public $director;

    public function createDirector($name, $age)
    {
        return new Director($name, $age);
    }
}