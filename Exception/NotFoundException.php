<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di\Exception;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \InvalidArgumentException implements NotFoundExceptionInterface
{

}
