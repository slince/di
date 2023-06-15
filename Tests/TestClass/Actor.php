<?php

namespace Slince\Di\Tests\TestClass;

class Actor implements ActorInterface
{
    private $birthday;

    public function __construct(Foo $foo = null, ?\DateTime $birthday = null)
    {
        $this->birthday = $birthday;
    }

    /**
     * @return \DateTime|null
     */
    public function getBirthday(): ?\DateTime
    {
        return $this->birthday;
    }
}
