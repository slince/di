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

use Psr\Container\ContainerInterface;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Exception\NotFoundException;

class Container implements \ArrayAccess, ContainerInterface
{
    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * Array of Definitions.
     *
     * @var Definition[]
     */
    protected $definitions = [];

    /**
     * @var array
     */
    protected $instances;

    /**
     * Array of parameters.
     *
     * @var ParameterBag
     */
    protected $parameters;

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * Defaults for the container.
     *
     * [
     *     'share' => true,
     *     'autowire' => true
     * ]
     *
     * @var array
     */
    protected $defaults = [
        'share' => true,
        'autowire' => true,
    ];

    public function __construct()
    {
        $this->parameters = new ParameterBag();
        $this->resolver = new Resolver($this);
        $this->register($this);
    }

    /**
     * Determine if a given offset exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function offsetSet($key, $value)
    {
        $this->register($key, $value);
    }

    /**
     * Unset the value at a given offset.
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        unset($this->definitions[$key], $this->instances[$key]);
    }

    /**
     * Register a definition.
     *
     * @param string|object $id
     * @param mixed         $concrete
     *
     * @return Definition
     */
    public function register($id, $concrete = null)
    {
        if (null === $concrete) {
            $concrete = $id;
        }
        if (is_object($id)) {
            $id = get_class($id);
        }
        //Apply defaults.
        $definition = (new Definition($concrete))
            ->setShared($this->defaults['share'])
            ->setAutowired($this->defaults['autowire']);

        $definition = $this->setDefinition($id, $definition);

        return $definition;
    }

    /**
     * Set a definition.
     *
     * @param string     $id
     * @param Definition $definition
     *
     * @return Definition
     */
    public function setDefinition($id, Definition $definition)
    {
        $id = (string) $id;

        return $this->definitions[$id] = $definition;
    }

    /**
     * Sets an alias for an existing service.
     *
     * @param string $alias
     * @param string $id
     */
    public function setAlias($alias, $id)
    {
        $this->aliases[$alias] = $id;
    }

    /**
     * Get id of the alias.
     *
     * @param string $alias
     *
     * @return string|null
     */
    public function getAlias($alias)
    {
        return isset($this->aliases[$alias]) ? $this->aliases[$alias] : null;
    }

    /**
     * Get a service instance by specified ID.
     *
     * @param string $id
     *
     * @return object
     */
    public function get($id)
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        //If there is no matching definition, creates a definition.
        if (!$this->has($id) && class_exists($id)) {
            $this->register($id);
        }
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('There is no definition named "%s"', $id));
        }
        // resolve instance.
        $instance = $this->resolver->resolve($this->definitions[$id]);
        if ($this->definitions[$id]->isShared()) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return isset($this->definitions[$id]);
    }

    /**
     * Extends a definition.
     *
     * @param string $id
     *
     * @return Definition
     */
    public function extend($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('There is no definition named "%s"', $id));
        }
        $definition = $this->definitions[$id];
        if ($definition->getResolved()) {
            throw new DependencyInjectionException(sprintf('Cannot override frozen service "%s".', $id));
        }

        return $definition;
    }

    /**
     * Returns service ids for a given tag.
     *
     * Example:
     *
     *     $container->register('foo')->addTag('my.tag', array('hello' => 'world'));
     *
     *     $serviceIds = $container->findTaggedServiceIds('my.tag');
     *     foreach ($serviceIds as $serviceId => $tags) {
     *         foreach ($tags as $tag) {
     *             echo $tag['hello'];
     *         }
     *     }
     *
     * @param string $name
     *
     * @return array
     */
    public function findTaggedServiceIds($name)
    {
        $tags = array();
        foreach ($this->definitions as $id => $definition) {
            if ($definition->hasTag($name)) {
                $tags[$id] = $definition->getTag($name);
            }
        }

        return $tags;
    }

    /**
     * Gets all global parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters->toArray();
    }

    /**
     * Sets array of parameters.
     *
     * @param array $parameterStore
     */
    public function setParameters(array $parameterStore)
    {
        $this->parameters->setParameters($parameterStore);
    }

    /**
     * Add some parameters.
     *
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->parameters->addParameters($parameters);
    }

    /**
     * Sets a parameter with its name and value.
     *
     * @param $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters->setParameter($name, $value);
    }

    /**
     * Gets a parameter by given name.
     *
     * @param $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        return $this->parameters->getParameter($name, $default);
    }

    /**
     * Gets a default option of the container.
     *
     * @param string $option
     *
     * @return mixed|null|boolean
     */
    public function getDefault($option)
    {
        return isset($this->defaults[$option]) ? $this->defaults[$option] : null;
    }

    /**
     * Configure defaults.
     *
     * @param array $defaults
     *
     * @return array
     */
    public function setDefaults(array $defaults)
    {
        return $this->defaults = array_merge($this->defaults, $defaults);
    }
}
