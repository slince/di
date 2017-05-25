<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di\Exception;

use Psr\Container\ContainerExceptionInterface;

class DependencyInjectionException extends \InvalidArgumentException implements ContainerExceptionInterface
{
}
