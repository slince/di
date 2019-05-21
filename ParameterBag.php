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

use Dflydev\DotAccessData\Data;

class ParameterBag extends Data
{
    /**
     * Sets array of parameters.
     *
     * @param $parameters
     */
    public function setParameters($parameters)
    {
        $this->data = $parameters;
    }

    /**
     * Adds array of parameters.
     *
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->data = array_replace($this->data, $parameters);
    }

    /**
     * Sets parameter with given name and value.
     *
     * @param int|string $name
     * @param mixed      $value
     */
    public function setParameter($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Gets the parameter by its name.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return parent::get($name, $default);
    }

    /**
     * Gets all parameters.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }
}
