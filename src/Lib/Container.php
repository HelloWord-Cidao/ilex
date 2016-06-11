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
 * @method public         __construct(array $data = [])
 * @method public mixed   __get(mixed $key)
 * @method public mixed   __set(mixed $key, mixed $value)
 * @method public string  __toString()
 * @method public self    assign(array $data = [])
 * @method public self    clear()
 * @method public mixed   get(mixed $key = NULL, mixed $default = NULL)
 * @method public boolean has(IMPLICIT)
 * @method public self    merge(array $data)
 * @method public array   miss(array $key_list)
 * @method public mixed   set(mixed $key, mixed $value)
 * @method public boolean delete(mixed $key)
 */
final class Container
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
        return Kit::j($this->data);
    }

    /**
     * @param array $data
     * @return self
     */
    public function clear()
    {
        $this->assign();
        return $this;
    }

    /**
     * Returns the key names that do not exist as keys in $this->data.
     * @param array $key_list
     * @return array
     */
    public function miss($key_list)
    {
        $missing_key_list = [];
        foreach ($key_list as $key) {
            if (FALSE === isset($this->data[$key])) $missing_key_list[] = $key;
        }
        return $missing_key_list;
    }

    /**
     * @param array $data
     * @return self
     */
    public function merge($data)
    {
        // @todo: use array_merge or '+' operator?
        // $this->assign($this->data + $data);
        $this->assign(array_merge($this->data, $data));
        return $this;
    }

    /**
     * @param array $data
     * @return self
     */
    public function assign($data = [])
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Checks if all the key names exist as keys in $this->data.
     * @param mixed $key IMPLICIT MULTIPLE
     * @return boolean
     */
    public function has()
    {
        foreach (func_get_args() as $key) {
            if (FALSE === isset($this->data[$key])) return FALSE;
        }
        return TRUE;
    }

    /**
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key = NULL, $default = NULL)
    {
        if (TRUE === is_null($key))
            return $this->data;
        elseif (TRUE === isset($this->data[$key]))
            return $this->data[$key];
        else return $default;
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

    /**
     * @param mixed $key
     * @return boolean
     */
    public function delete($key)
    {
        if (FALSE === isset($this->data[$key])) return FALSE;
        unset($this->data[$key]);
        return TRUE;
    }
}