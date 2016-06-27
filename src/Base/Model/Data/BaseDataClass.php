<?php

namespace Ilex\Base\Model\Data;

use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseDataClass
 * Base class of data models of Ilex.
 * @package Ilex\Base\Model\Data
 */
abstract class BaseDataClass extends BaseModel
{

    // @TODO: load Config model

    final public function validateInput($method_name, $input)
    {
        return TRUE;
    }

    final public function sanitizeInput($method_name, $input, $input_validation_result)
    {
        return $input;
    }

    final public function validateServiceResult($method_name, $service_result)
    {
        return TRUE;
    }

    final public function sanitizeServiceResult($method_name, $service_result, $service_result_validation_result)
    {
        return $service_result;
    }

    final public function validateArgs($handler_suffix, $method_name, $arg_list)
    {
        return TRUE;
    }

    final public function sanitizeArgs($handler_suffix, $method_name, $arg_list, $args_validation_result)
    {
        return $arg_list;
    }

    final public function validateResult($handler_suffix, $method_name, $result)
    {
        return TRUE;
    }

    final public function sanitizeResult($handler_suffix, $method_name, $result, $result_validation_result)
    {
        return $result;
    }
}