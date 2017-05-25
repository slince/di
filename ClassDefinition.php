<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

class ClassDefinition
{
    /**
     * Class
     * @var string
     */
    protected $class;

    /**
     * Array of arguments
     * @var array
     */
    protected $arguments = [];

    /**
     * Array of setters
     * @var array
     */
    protected $calls = [];

    /**
     * Array of properties
     * @var array
     */
    protected $properties = [];

    public function __construct($class, array $arguments = [], array $calls = [], array $properties = [])
    {
        $this->class = $class;
        $this->arguments = $arguments;
        $this->calls = $calls;
        $this->properties = $properties;
    }

    /**
     * Sets a argument
     * @param int|string $indexOrName
     * @param mixed $argument
     * @return $this
     */
    public function setArgument($indexOrName, $argument)
    {
        $this->arguments[$indexOrName] = $argument;
        return $this;
    }


    /**
     * Sets array of arguments
     * @param array $arguments
     * @return $this
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Gets all arguments of constructor
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Gets the argument at the specified position of constructor
     * @param int|string $indexOrName
     * @return mixed
     */
    public function getArgument($indexOrName)
    {
        return isset($this->arguments[$indexOrName]) ? $this->arguments[$indexOrName] : null;
    }

    /**
     * Adds a setter
     * @param string $method
     * @param array $arguments
     * @return $this
     */
    public function setMethodCall($method, array $arguments)
    {
        $this->calls[$method] = $arguments;
        return $this;
    }

    /**
     * Sets array of setter
     * @param array $calls
     * @return $this
     */
    public function setMethodCalls(array $calls)
    {
        $this->calls = array_merge($this->calls, $calls);
        return $this;
    }

    /**
     * Gets all setter
     * @return array
     */
    public function getMethodCalls()
    {
        return $this->calls;
    }

    /**
     * Gets the parameters of one setter
     * @param string $method
     * @return array|null
     */
    public function getMethodCall($method)
    {
        return isset($this->calls[$method]) ? $this->calls[$method] : null;
    }

    /**
     * Gets all properties
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Gets the class
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
