<?php
namespace Slince\Di\Tests\TestClass;

class Movie
{
    protected $name;

    protected $time;

    /**
     * 导演
     * @var Director
     */
    protected $director;

    /**
     * 男演员
     * @var ActorInterface
     */
    protected $actor;


    /**
     * 女演员
     * @var ActorInterface
     */
    protected $actress;

    public function __construct(Director $director, ActorInterface $actor)
    {
        $this->director = $director;
        $this->actor = $actor;
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

    /**
     * @return Director
     */
    public function getDirector()
    {
        return $this->director;
    }

    /**
     * 设置女演员
     * @param ActorInterface $actress
     */
    public function setActress(ActorInterface $actress) {
        $this->actress = $actress;
    }

    /**
     * @return ActorInterface
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * @return ActorInterface
     */
    public function getActress()
    {
        return $this->actress;
    }
}
