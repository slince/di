<?php
/**
 * slince dependency injection component
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Di;

use Dflydev\DotAccessData\Data;

class ParameterStore extends Data
{
    /**
     * 批量设置参数
     * @param $parameters
     */
    public function setParameters($parameters)
    {
        $this->data = $parameters;
    }

    /**
     * 添加预定义参数
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->data = array_replace($this->data, $parameters);
    }

    /**
     * 设置参数
     * @param $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * 获取参数
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function getParameter($name, $default = null)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return parent::get($name, $default);
    }

    /**
     * 获取全部参数
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }
}