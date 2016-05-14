<?php

namespace Ilex\Base\Model\Data;

use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseData
 * @package Ilex\Base\Model\Data
 * 
 * @method protected array tryFetchArguments(array[] $argument_names)
 * @method protected array tryFetchData(array[] $field_names)
 * @method protected array fetchArguments(array[] $argument_names)
 * @method protected array fetchData(array[] $field_names)
 * @method protected       validateExistArguments(array[] $argument_names)
 * @method protected       validateExistFields(array[] $field_names)
 */
class BaseData extends BaseModel
{

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

}