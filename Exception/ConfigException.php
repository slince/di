<?php

/*
 * This file is part of the slince/di package.
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Slince\Di\Exception;

use Interop\Container\Exception\ContainerException;

class ConfigException extends \InvalidArgumentException implements ContainerException
{
}
