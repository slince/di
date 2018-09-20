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

    /**
     * ['@Foo\Bar', 'createBaz']
     * or
     * ['Foo\Bar', 'createBaz']
     * @var \callable
     */
    protected $factory;

    /**
     * @var array
     */
    protected $tags;

    /**
     * @var boolean
     */
    protected $autowired;

    /**
     * @var boolean
     */
    protected $shared;

    /**
     * @var boolean
     */
    protected $public;

    public function __construct(
        $class = null,
        array $arguments = []
    ) {
        $this->class = $class;
        $this->arguments = $arguments;
    }

    /**
     * Set class for the definition.
     *
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * Gets the class
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param callable $factory
     * @return $this
     */
    public function setFactory($factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @return callable
     */
    public function getFactory()
    {
        return $this->factory;
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
     * add an argument
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function addArgument($value)
    {
        $this->arguments[] = $value;
        return $this;
    }

    /**
     * Sets a specific argument.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setArgument($key, $value)
    {
        $this->arguments[$key] = $value;
        return $this;
    }

    /**
     * Sets the arguments to pass to the service constructor/factory method.
     *
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
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Gets the argument at the specified position of constructor
     *
     * @param int|string $index
     * @return mixed
     */
    public function getArgument($index)
    {
        return isset($this->arguments[$index]) ? $this->arguments[$index] : null;
    }

    /**
     * Adds a method
     *
     * @param string $method
     * @param string|array $arguments
     * @return $this
     */
    public function addMethodCall($method, $arguments)
    {
        $this->calls[] = [
            $method,
            (array)$arguments
        ];
        return $this;
    }

    /**
     * Sets the methods to call after service initialization.
     *
     * @param array methods
     * @return $this
     */
    public function setMethodCalls(array $methods)
    {
        $this->calls = array();
        foreach ($methods as $call) {
            $this->addMethodCall($call[0], $call[1]);
        }

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
     * Check if the current definition has a given method to call after service initialization.
     *
     * @param string $method The method name to search for
     *
     * @return bool
     */
    public function hasMethodCall($method)
    {
        foreach ($this->calls as $call) {
            if ($call[0] === $method) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets tags for this definition.
     *
     * @param array $tags
     * @return $this
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Returns all tags.
     *
     * @return array An array of tags
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Gets a tag by name.
     *
     * @param string $name The tag name
     *
     * @return array An array of attributes
     */
    public function getTag($name)
    {
        return isset($this->tags[$name]) ? $this->tags[$name] : array();
    }

    /**
     * Adds a tag for this definition.
     *
     * @param string $name       The tag name
     * @param array  $attributes An array of attributes
     *
     * @return $this
     */
    public function addTag($name, array $attributes = array())
    {
        $this->tags[$name][] = $attributes;

        return $this;
    }

    /**
     * Whether this definition has a tag with the given name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasTag($name)
    {
        return isset($this->tags[$name]);
    }

    /**
     * Clears all tags for a given name.
     *
     * @param string $name The tag name
     *
     * @return $this
     */
    public function clearTag($name)
    {
        unset($this->tags[$name]);

        return $this;
    }

    /**
     * Clears the tags for this definition.
     *
     * @return $this
     */
    public function clearTags()
    {
        $this->tags = array();

        return $this;
    }

    /**
     * Is the definition autowired?
     *
     * @return bool
     */
    public function isAutowired()
    {
        return $this->autowired;
    }

    /**
     * Enables/disables autowiring.
     *
     * @param bool $autowired
     *
     * @return $this
     */
    public function setAutowired($autowired)
    {
        $this->autowired = (bool) $autowired;

        return $this;
    }

    /**
     * Sets if the service must be shared or not.
     *
     * @param bool $shared Whether the service must be shared or not
     *
     * @return $this
     */
    public function setShared($shared)
    {
        $this->changes['shared'] = true;

        $this->shared = (bool) $shared;

        return $this;
    }

    /**
     * Whether this service is shared.
     *
     * @return bool
     */
    public function isShared()
    {
        return $this->shared;
    }

    /**
     * Sets the visibility of this service.
     *
     * @param bool $boolean
     *
     * @return $this
     */
    public function setPublic($boolean)
    {
        $this->public = (bool) $boolean;

        return $this;
    }

    /**
     * Whether this service is public facing.
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->public;
    }
}
