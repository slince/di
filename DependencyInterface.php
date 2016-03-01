<?php
/**
 * slince dependency injection component
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

interface DependencyInterface
{
    
    /**
     * 获取依赖
     */
    function getDependency();
    
}