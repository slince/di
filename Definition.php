<?php

/*
 * This file is part of the slince/di package.
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Slince\Di;

class Definition
{
    /**
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

    public function __construct($class, array $arguments = [], array $methods = [], array $properties = [])
    {
        $this->class = $class;
        $this->arguments = $arguments;
        $this->calls = $methods;
        $this->properties = $properties;
    }

    /**
     * Sets a argument
     * @param int|string $index
     * @param mixed $value
     * @return $this
     */
    public function setArgument($index, $value)
    {
        $this->arguments[$index] = $value;
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
     * @param int|string $index
     * @return mixed
     */
    public function getArgument($index)
    {
        return isset($this->arguments[$index]) ? $this->arguments[$index] : null;
    }

    /**
     * Adds a setter
     * @param string $method
     * @param string|array $arguments
     * @return $this
     */
    public function setMethodCall($method, $arguments)
    {
        $this->calls[$method] = (array)$arguments;
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
     * Add a method
     *
     * @param $method
     * @param $arguments
     * @return self
     */
    public function addMethodCall($method, $arguments)
    {
        $this->calls[] = [
            $method,
            $arguments
        ];
        return $this;
    }

    /**
     * Gets all methods
     *
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
     * @param array $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
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
     * Adds a property
     * @param int|string $name
     * @param mixed $value
     * @return $this
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
        return $this;
    }

    /**
     * Gets the property by given name
     * @param string $name
     * @return mixed
     */
    public function getProperty($name)
    {
        return isset($this->properties[$name]) ? $this->properties[$name] : null;
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
