<?php

namespace Ilex\Lib;

use \Ilex\Lib\Kit;

/**
 * @todo: method arg type validate
 * Class Container
 * Implementation of an abstract container.
 * @package Ilex\Lib
 * 
 * @property private array $data
 * 
 * @method final public         __construct(array $data = [])
 * @method final public mixed   __get(mixed $key)
 * @method final public mixed   __set(mixed $key, mixed $value)
 * @method final public string  __toString()
 * @method final public self    assign(array $data = [])
 * @method final public mixed   get(mixed $key = NULL, mixed $default = NULL)
 * @method final public boolean has(IMPLICIT)
 * @method final public self    merge(array $data)
 * @method final public array   miss(array $key_list)
 * @method final public mixed   set(mixed $key, mixed $value)
 * @method final public boolean delete(mixed $key)
 */
final class Container
{
    private $data;

    /**
     * @param array $data
     */
    final public function __construct($data = [])
    {
        $this->assign($data);
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    final public function __get($key)
    {
        return $this->get($key, NULL);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    final public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * @return string
     */
    final public function __toString()
    {
        // @todo: use json_encode
        return Kit::toString($this->data);
    }

    /**
     * Returns the params that do not exist as key_list in $this->data.
     * @param array $key_list
     * @return array
     */
    final public function miss($key_list)
    {
        $missing_key_list = [];
        foreach ($key_list as $key) {
            if (FALSE === isset($this->data[$key])) {
                $missing_key_list[] = $key;
            }
        }
        return $missing_key_list;
    }

    /**
     * @param array $data
     * @return self
     */
    final public function merge($data)
    {
        // @todo: use array_merge or '+' operator?
        $this->assign($this->data + $data);
        return $this;
    }

    /**
     * @param array $data
     * @return self
     */
    final public function assign($data = [])
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Checks if all the params exist as key_list in $this->data.
     * @param mixed $key IMPLICIT MULTIPLE
     * @return boolean
     */
    final public function has()
    {
        foreach (func_get_args() as $key) {
            if (FALSE === isset($this->data[$key])) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    final public function get($key = NULL, $default = NULL)
    {
        return TRUE === is_null($key) ?
            $this->data :
            (TRUE === isset($this->data[$key]) ? $this->data[$key] : $default);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    final public function set($key, $value)
    {
        return ($this->data[$key] = $value);
    }

    /**
     * @param mixed $key
     * @return boolean
     */
    final public function delete($key)
    {
        if (FALSE === isset($this->data[$key])) return FALSE;
        else unset($this->data[$key]);
        return TRUE;
    }
}