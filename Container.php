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

use Psr\Container\ContainerInterface;
use ReflectionException;
use Slince\Di\Exception\DependencyInjectionException;
use Slince\Di\Exception\NotFoundException;

class Container implements \ArrayAccess, ContainerInterface
{
    /**
     * @var array
     */
    protected array $aliases = [];

    /**
     * Array of Definitions.
     *
     * @var Definition[]
     */
    protected array $definitions = [];

    /**
     * @var array
     */
    protected array $instances;

    /**
     * Array of parameters.
     *
     * @var ParameterBag
     */
    protected ParameterBag $parameters;

    /**
     * @var Resolver
     */
    protected Resolver $resolver;

    /**
     * Defaults for the container.
     *
     * [
     *     'share' => true,
     *     'autowire' => true,
     *     'autoregister' => true
     * ]
     *
     * @var array
     */
    protected array $defaults = [
        'share' => true,
        'autowire' => true,
        'autoregister' => true
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
    public function offsetExists($key): bool
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
    public function offsetSet($key, $value): void
    {
        $this->register($key, $value);
    }

    /**
     * Unset the value at a given offset.
     *
     * @param string $key
     */
    public function offsetUnset($key): void
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
    public function register($id, $concrete = null): Definition
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

        return $this->setDefinition($id, $definition);
    }

    /**
     * Set a definition.
     *
     * @param string     $id
     * @param Definition $definition
     *
     * @return Definition
     */
    public function setDefinition(string $id, Definition $definition): Definition
    {
        unset($this->aliases[$id]);
        return $this->definitions[$id] = $definition;
    }

    /**
     * Adds the service definitions.
     *
     * @param Definition[] $definitions An array of service definitions
     */
    public function addDefinitions(array $definitions)
    {
        foreach ($definitions as $id => $definition) {
            $this->setDefinition($id, $definition);
        }
    }

    /**
     * Sets the service definitions.
     *
     * @param Definition[] $definitions An array of service definitions
     */
    public function setDefinitions(array $definitions)
    {
        $this->definitions = [];
        $this->addDefinitions($definitions);
    }

    /**
     * Sets an alias for an existing service.
     *
     * @param string $alias
     * @param string $id
     */
    public function setAlias(string $alias, string $id)
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
    public function getAlias(string $alias): ?string
    {
        return $this->aliases[$alias] ?? null;
    }

    /**
     * Get a service instance by specified ID.
     *
     * @param string $id
     *
     * @return object
     * @throws DependencyInjectionException|ReflectionException
     */
    public function get(string $id)
    {
        $id = $this->resolveAlias($id);

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        return $this->resolveInstance($id);
    }

    /**
     * Get a service instance by specified ID.
     *
     * @param string $id
     *
     * @return object
     */
    public function getNew(string $id): object
    {
        $id = $this->resolveAlias($id);

        return $this->resolveInstance($id);
    }

    protected function resolveAlias(string $id): string
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }
        return $id;
    }

    /**
     * @throws DependencyInjectionException|ReflectionException
     */
    protected function resolveInstance(string $id): object
    {
        if (!$this->has($id)) {
            //If there is no matching definition, creates a definition.
            if ($this->defaults['autoregister'] && class_exists($id)) {
                $this->register($id);
            } else {
                throw new NotFoundException(sprintf('There is no definition named "%s"', $id));
            }
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
    public function has($id): bool
    {
        return isset($this->definitions[$id]);
    }

    /**
     * Extends a definition.
     *
     * @param string $id
     *
     * @return Definition
     * @throws DependencyInjectionException
     */
    public function extend(string $id): Definition
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
    public function findTaggedServiceIds(string $name): array
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
    public function getParameters(): array
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
     * @param string $name
     * @param mixed $value
     */
    public function setParameter(string $name, $value)
    {
        $this->parameters->setParameter($name, $value);
    }

    /**
     * Gets a parameter by given name.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParameter(string $name, $default = null)
    {
        return $this->parameters->getParameter($name, $default);
    }

    /**
     * Gets a default option of the container.
     *
     * @param string $option
     *
     * @return mixed
     */
    public function getDefault(string $option)
    {
        return $this->defaults[$option] ?? null;
    }

    /**
     * Configure defaults.
     *
     * @param array $defaults
     *
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = array_merge($this->defaults, $defaults);
    }
}
