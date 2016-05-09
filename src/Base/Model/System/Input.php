<?php

namespace Ilex\Base\Model\System;

use \Ilex\Base\Model\BaseModel;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;

/**
 * Class Input
 * Encapsulation of system input, such as $_GET, $_POST.
 * @package Ilex\Base\Model\System
 * 
 * @property private \Ilex\Lib\Container $getData
 * @property private \Ilex\Lib\Container $postData
 * 
 * @method public         __construct()
 * @method public string  __toString()
 * @method public this    clear(string $name = '')
 * @method public mixed   get(string $key = NULL, mixed $default = NULL)
 * @method public boolean hasGet(array $keys)
 * @method public boolean hasPost(array $keys)
 * @method public this    merge(string $name, array $data = [])
 * @method public array   missGet(array $keys)
 * @method public array   missPost(array $keys)
 * @method public mixed   post(string $key = NULL, mixed $default = NULL)
 */
class Input extends BaseModel
{
    private $getData;
    private $postData;

    /**
     * Encapsulates global variables.
     */
    public function __construct()
    {
        $this->getData  = new Container($_GET);
        $this->postData = new Container($_POST);
        $data = json_decode(file_get_contents('php://input'), TRUE);
        if (!is_null($data)) $this->merge('post', $data);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Kit::toString([
            'get'  => $this->getData,
            'post' => $this->postData
        ]);
    }

    /**
     * @return array
     */
    protected function fetchAllArguments()
    {
        $arguments = $this->Input->get();
        unset($arguments['_url']);
        return $arguments;
    }

    /**
     * @return array
     */
    protected function fetchAllPostData()
    {
        return $this->Input->post();
    }

    /**
     * @param array[] $argument_names
     * @return array
     */
    protected function fetchArguments($argument_names)
    {
        $this->validateExistArguments($argument_names);
        $arguments = [];
        foreach ($argument_names as $argument_name)
            $arguments[$argument_name] = $this->Input->get($argument_name);
        return $arguments;
    }

    /**
     * @param array[] $argument_names
     */
    protected function validateExistArguments($argument_names)
    {
        if (!$this->Input->hasGet($argument_names)) {
            $arguments = $this->Input->get();
            unset($arguments['_url']);
            $err_info = [
                'missingArguments' => $this->Input->missGet($argument_names),
                'givenArguments'   => $arguments,
            ];
            $this->terminate('Missing arguments.', $err_info);
        }
    }

    /**
     * @param array[] $field_names
     * @return array
     */
    protected function fetchPostData($field_names)
    {
        $this->validateExistFields($field_names);
        $post_data = [];
        foreach ($field_names as $field_name)
            $post_data[$field_name] = $this->Input->post($field_name);
        return $post_data;
    }

    /**
     * @param array[] $field_names
     */
    protected function validateExistFields($field_names)
    {
        if (!$this->Input->hasPost($field_names)) {
            $err_info = [
                'missingFields' => $this->Input->missPost($field_names),
                'givenFields'   => array_keys($this->Input->post()),
            ];
            $this->terminate('Missing fields.', $err_info);
        }
    }

    /**
     * @param array[] $argument_names
     * @return array
     */
    protected function tryFetchArguments($argument_names)
    {
        $arguments = [];
        foreach ($argument_names as $argument_name)
            if ($this->Input->hasGet([$argument_name]))
                $arguments[$argument_name] = $this->Input->get($argument_name);
        return $arguments;
    }

    /**
     * @param array[] $field_names
     * @return array
     */
    protected function tryFetchPostData($field_names)
    {
        $post_data = [];
        foreach ($field_names as $field_name)
            if ($this->Input->hasPost([$field_name]))
                $post_data[$field_name] = $this->Input->post($field_name);
        return $post_data;
    }

    /**
     * If $name is NOT assigned, $getData and $postData will both be cleared.
     * @param string $name
     * @return this
     */
    public function clear($name = '')
    {
        if ($name) {
            $name .= 'Data';
            $this->$name->assign();
        } else {
            $this->getData->assign();
            $this->postData->assign();
        }
        return $this;
    }
    
    /**
     * @param array $keys
     * @return boolean
     */
    public function hasGet($keys)
    {
        return call_user_func_array([$this->getData, 'has'], $keys);
    }

    /**
     * @param array $keys
     * @return boolean
     */
    public function hasPost($keys)
    {
        return call_user_func_array([$this->postData, 'has'], $keys);
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get($key = NULL, $default = NULL)
    {
        return $this->getData->get($key, $default);
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function post($key = NULL, $default = NULL)
    {
        return $this->postData->get($key, $default);
    }

    /**
     * @param string $name
     * @param array  $data
     * @return this
     */
    public function merge($name, $data = [])
    {
        $name .= 'Data';
        $this->$name->merge($data);
        return $this;
    }

    /**
     * @param array $keys
     * @return array
     */
    public function missGet($keys)
    {
        return $this->getData->miss($keys);
    }

    /**
     * @param array $keys
     * @return array
     */
    public function missPost($keys)
    {
        return $this->postData->miss($keys);
    }
}
