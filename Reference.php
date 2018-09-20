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

class Reference
{
    /**
     * Service name
     * @var string
     */
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get service name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set service name
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
