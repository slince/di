<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di\Exception;

use Psr\Container\ContainerExceptionInterface;

class ConfigException extends \InvalidArgumentException implements ContainerExceptionInterface
{

}
