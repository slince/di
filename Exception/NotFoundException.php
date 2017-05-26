<?php
/**
 * slince dependency injection library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di\Exception;

use Interop\Container\Exception\NotFoundException as BaseNotFoundException;

class NotFoundException extends \InvalidArgumentException implements BaseNotFoundException
{

}