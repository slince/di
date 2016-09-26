<?php
namespace Slince\Di\Tests\TestClass;

class Actor
{
    protected $profile;

    public function __construct($profile)
    {
        $this->profile = $profile;
    }

    /**
     * @return mixed
     */
    public function getProfile()
    {
        return $this->profile;
    }
}