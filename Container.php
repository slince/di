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

use Slince\Di\Exception\ConfigException;
use Slince\Di\Exception\NotFoundException;
use Interop\Container\ContainerInterface;

class Container implements ContainerInterface
{

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * Array of singletons
     * @var array
     */
    protected $services = [];

    /**
     * Array of Definitions
     *
     * @var Definition[]
     */
    protected $definitions = [];

    /**
     * Array of parameters
     * @var ParameterBag
     */
    protected $parameters;

    /**
     * @var DefinitionResolver
     */
    protected $definitionResolver;

    /**
     * Defaults for the container.
     *
     * [
     *     'share' => true,
     *     'autowire' => true
     * ]
     * @var array
     */
    protected $defaults = [
        'share' => true,
        'autowire' => true
    ];

    public function __construct()
    {
        $this->parameters = new ParameterBag();
        $this->definitionResolver = new DefinitionResolver($this);
        $this->register($this);
    }

    /**
     * Register a definition.
     *
     * @param string $id
     * @param mixed $concrete
     * @return Definition
     */
    public function register($id, $concrete = null)
    {
        if (is_object($id)) {
            $concrete = $id;
            $id = get_class($id);
        }

        //Apply defaults.
        $definition = (new Definition())
            ->setShared($this->defaults['share'])
            ->setAutowired($this->defaults['autowire']);

        if (null === $concrete) {
            $definition->setClass($id);
        } elseif (is_string($concrete)) {
            $definition->setClass($concrete);
        } elseif ($concrete instanceof \Closure || is_array($concrete)) {
            $definition->setFactory($concrete);
        } elseif (is_object($concrete)) {
            $definition->setFactory(function() use ($concrete){
                    return $concrete;
                })
                ->setClass(get_class($concrete))
                ->setShared(true);
        } else {
            throw new ConfigException('expects a valid concrete');
        }

        $definition = $this->setDefinition($id, $definition);
        return $definition;
    }

    /**
     * Set a definition.
     *
     * @param string $id
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
     * @return string|null
     */
    public function getAlias($alias)
    {
        return isset($this->aliases[$alias]) ? $this->aliases[$alias] : null;
    }

    /**
     * Get a service instance by specified ID
     *
     * @param string $id
     * @return object
     */
    public function get($id)
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        //If service is singleton, return instance directly.
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        //If there is no matching definition, creates an definition automatically
        if (!isset($this->definitions[$id])) {
            if (class_exists($id)) {
                $this->register($id);
            } else {
                throw new NotFoundException(sprintf('There is no definition named "%s"', $id));
            }
        }

        // resolve instance.
        $instance = $this->definitionResolver->resolve($this->definitions[$id]);

        //If the service be set as singleton mode, stores its instance
        if ($this->definitions[$id]->isShared()) {
            $this->services[$id] = $instance;
        }
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        if (isset($this->services[$id])) {
            return true;
        }
        if (!isset($this->definitions[$id]) && class_exists($id)) {
            $this->register($id);
        }
        return isset($this->definitions[$id]);
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
     * @param string $name
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
     * Gets all global parameters
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters->toArray();
    }

    /**
     * Sets array of parameters
     * @param array $parameterStore
     */
    public function setParameters(array $parameterStore)
    {
        $this->parameters->setParameters($parameterStore);
    }

    /**
     * Add some parameters
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->parameters->addParameters($parameters);
    }

    /**
     * Sets a parameter with its name and value
     *
     * @param $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters->setParameter($name, $value);
    }

    /**
     * Gets a parameter by given name
     * @param $name
     * @param mixed $default
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
     * @return array
     */
    public function setDefaults(array $defaults)
    {
        return $this->defaults = array_merge($this->defaults, $defaults);
    }
}
