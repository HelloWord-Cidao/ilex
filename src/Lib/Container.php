<?php

namespace Ilex\Lib;

use \Ilex\Lib\Kit;

/**
 * Class Container
 * Implementation of an abstract container.
 * @package Ilex\Lib
 * 
 * @property private array $data
 * 
 * @method public         __construct(array $data)
 * @method public string  __toString()
 * @method public boolean has()
 * @method public mixed   __get(mixed $key)
 * @method public mixed   get(mixed $key, mixed $default)
 * @method public mixed   __set(mixed $key, mixed $value)
 * @method public mixed   let(mixed $key, mixed $value)
 * @method public         merge(array $data)
 * @method public         assign(array $data = [])
 */
class Container
{
    private $data;

    /**
     * @param array $data
     */
    public function __construct($data)
    {
        $this->assign($data);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Kit::toString($this->data);
    }

    /**
     * @return boolean
     */
    public function has()
    {
        foreach (func_get_args() as $key) {
            if (!isset($this->data[$key])) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key, NULL);
    }

    /**
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default)
    {
        return is_null($key) ?
            $this->data :
            (isset($this->data[$key]) ? $this->data[$key] : $default);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    public function __set($key, $value)
    {
        return $this->let($key, $value);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    public function let($key, $value)
    {
        return $this->data[$key] = $value;
    }

    /**
     * @param array $data
     */
    public function merge($data)
    {
        $this->assign(array_merge($this->data, $data));
    }

    /**
     * @param array $data
     */
    public function assign($data = [])
    {
        $this->data = $data;
    }
}