<?php

declare(strict_types=1);

/*
 * This file is part of the slince/di package.
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Slince\Di;

final class Definition
{
    /**
     * @var mixed
     */
    protected $concrete;

    /**
     * @var ?string
     */
    protected ?string $class = null;

    /**
     * Array of arguments.
     *
     * @var array
     */
    protected array $arguments = [];

    /**
     * Array of setters.
     *
     * @var array
     */
    protected array $calls = [];

    /**
     * Array of properties.
     *
     * @var array
     */
    protected array $properties = [];

    /**
     * ['@Foo\Bar', 'createBaz']
     * or
     * ['Foo\Bar', 'createBaz'].
     *
     * @var callable|array
     */
    protected $factory;

    /**
     * @var array
     */
    protected array $tags;

    /**
     * @var boolean
     */
    protected bool $autowired = true;

    /**
     * @var boolean
     */
    protected bool $shared = true;

    /**
     * @var ?object
     */
    protected ?object $resolved = null;

    public function __construct($concrete)
    {
        $this->concrete = $concrete;
    }

    /**
     * Set the concrete of the definition.
     *
     * @param mixed $concrete
     */
    public function setConcrete($concrete)
    {
        $this->concrete = $concrete;
    }

    /**
     * Get the concrete of the definition.
     *
     * @return mixed
     */
    public function getConcrete()
    {
        return $this->concrete;
    }

    /**
     * Set class for the definition.
     *
     * @param string $class
     *
     * @return $this
     */
    public function setClass(string $class): Definition
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Gets the class.
     *
     * @return ?string
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * @param callable|array $factory
     *
     * @return $this
     */
    public function setFactory($factory): Definition
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * @return callable|array
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * Gets all properties.
     *
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Adds a property.
     *
     * @param int|string $name
     * @param mixed      $value
     *
     * @return $this
     */
    public function setProperty(string $name, $value): Definition
    {
        $this->properties[$name] = $value;

        return $this;
    }

    /**
     * Gets the property by given name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getProperty(string $name)
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * add an argument.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function addArgument($value): Definition
    {
        $this->arguments[] = $value;

        return $this;
    }

    /**
     * Sets a specific argument.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setArgument(string $key, $value): Definition
    {
        $this->arguments[$key] = $value;

        return $this;
    }

    /**
     * Sets the arguments to pass to the service constructor/factory method.
     *
     * @param array $arguments
     *
     * @return $this
     */
    public function setArguments(array $arguments): Definition
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * Gets all arguments of constructor.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Gets the argument at the specified position of constructor.
     *
     * @param int|string $index
     *
     * @return mixed
     */
    public function getArgument($index)
    {
        return $this->arguments[$index] ?? null;
    }

    /**
     * Adds a method.
     *
     * @param string       $method
     * @param string|array $arguments
     *
     * @return $this
     */
    public function addMethodCall(string $method, $arguments): Definition
    {
        $this->calls[] = [
            $method,
            (array) $arguments,
        ];

        return $this;
    }

    /**
     * Sets the methods to call after service initialization.
     *
     * @param array $methods methods
     *
     * @return $this
     */
    public function setMethodCalls(array $methods): Definition
    {
        $this->calls = array();
        foreach ($methods as $call) {
            $this->addMethodCall($call[0], $call[1]);
        }

        return $this;
    }

    /**
     * Gets all methods.
     *
     * @return array
     */
    public function getMethodCalls(): array
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
    public function hasMethodCall(string $method): bool
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
     *
     * @return $this
     */
    public function setTags(array $tags): Definition
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Returns all tags.
     *
     * @return array An array of tags
     */
    public function getTags(): array
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
    public function getTag(string $name): array
    {
        return $this->tags[$name] ?? array();
    }

    /**
     * Adds a tag for this definition.
     *
     * @param string $name       The tag name
     * @param array  $attributes An array of attributes
     *
     * @return $this
     */
    public function addTag(string $name, array $attributes = array()): Definition
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
    public function hasTag(string $name): bool
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
    public function clearTag(string $name): Definition
    {
        unset($this->tags[$name]);

        return $this;
    }

    /**
     * Clears the tags for this definition.
     *
     * @return $this
     */
    public function clearTags(): Definition
    {
        $this->tags = array();

        return $this;
    }

    /**
     * Is the definition autowired?
     *
     * @return bool
     */
    public function isAutowired(): bool
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
    public function setAutowired(bool $autowired): Definition
    {
        $this->autowired = $autowired;

        return $this;
    }

    /**
     * Sets if the service must be shared or not.
     *
     * @param bool $shared Whether the service must be shared or not
     *
     * @return $this
     */
    public function setShared(bool $shared): Definition
    {
        $this->shared = $shared;

        return $this;
    }

    /**
     * Whether this service is shared.
     *
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * Get resolved instance of the definition.
     *
     * @return object
     */
    public function getResolved(): ?object
    {
        return $this->resolved;
    }

    /**
     * Set the resolved instance for the definition.
     *
     * @param object $resolved
     *
     * @return $this
     */
    public function setResolved(object $resolved): Definition
    {
        $this->resolved = $resolved;

        return $this;
    }
}
