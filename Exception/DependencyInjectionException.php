<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di\Exception;

use Interop\Container\Exception\ContainerException;

class DependencyInjectionException extends \InvalidArgumentException implements ContainerException
{
}
