<?php
namespace Slince\Di\Tests\TestClass;

class Director
{
    protected $name;

    protected $age;

    public function __construct($name = '', $age = 0)
    {
        $this->name = $name;
        $this->age = $age;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
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
    public function getAge()
    {
        return $this->age;
    }

    /**
     * @param mixed $age
     */
    public function setAge($age)
    {
        $this->age = $age;
    }

    public function direct($movieName)
    {
        return new Movie($this, $movieName, date('Y-m-d'));
    }

    public static function factory()
    {
        return new Director();
    }
}
