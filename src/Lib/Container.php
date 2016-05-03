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
 * @method public         __construct(array $data = [])
 * @method public mixed   __get(mixed $key)
 * @method public mixed   __set(mixed $key, mixed $value)
 * @method public string  __toString()
 * @method public this    assign(array $data = [])
 * @method public mixed   get(mixed $key, mixed $default = NULL)
 * @method public boolean has(IMPLICIT)
 * @method public this    merge(array $data)
 * @method public array   miss(array $keys)
 * @method public mixed   set(mixed $key, mixed $value)
 */
class Container
{
    private $data;

    /**
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->assign($data);
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
     * @param mixed $value
     * @return mixed
     */
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Kit::toString($this->data);
    }

    /**
     * @param array $data
     * @return this
     */
    public function assign($data = [])
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = NULL)
    {
        return is_null($key) ?
            $this->data :
            (isset($this->data[$key]) ? $this->data[$key] : $default);
    }

    /**
     * Checks if all the params exist as keys in $this->data.
     * @param mixed $key IMPLICIT MULTIPLE
     * @return boolean
     */
    public function has()
    {
        foreach (func_get_args() as $key) {
            if (isset($this->data[$key]) === FALSE) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * @param array $data
     * @return this
     */
    public function merge($data)
    {
        $this->assign(array_merge($this->data, $data));
        return $this;
    }

    /**
     * Returns the params that do not exist as keys in $this->data.
     * @param array $keys
     * @return array
     */
    public function miss($keys)
    {
        $missing_keys = [];
        foreach ($keys as $key) {
            if (isset($this->data[$key]) === FALSE) {
                $missing_keys[] = $key;
            }
        }
        return $missing_keys;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value)
    {
        return ($this->data[$key] = $value);
    }
}