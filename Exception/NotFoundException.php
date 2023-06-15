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

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends DependencyInjectionException implements NotFoundExceptionInterface
{
}
