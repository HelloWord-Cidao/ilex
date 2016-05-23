<?php

namespace Ilex\Base\Model\Core;

use ReflectionClass;
use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseFeature
 * Base class of feature models of Ilex.
 * @package Ilex\Base\Model\Feature
 */
abstract class BaseFeature extends BaseModel
{

    final public function __call($method_name, $args)
    {
        new ReflectionClass(get_called_class())
        call_user_func([$this, $method_name], param_arr)
    }

    final protected static function checkError($return_value)
    {
        if (TRUE === is_array($return_value) AND TRUE === $return_value[T_IS_ERROR]) return TRUE;
        else return FALSE;
    }
}