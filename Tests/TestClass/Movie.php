<?php
namespace Slince\Di\Tests\TestClass;

class Movie
{
    protected $name;

    protected $time;

    protected $director;

    function __construct(Director $director)
    {
        $this->director = $director;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }
}