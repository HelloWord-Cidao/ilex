<?php

namespace Ilex\Base\Model\Core;

use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseCore
 * Base class of core models of Ilex.
 * @package Ilex\Base\Model\Core
 */
class BaseCore extends BaseModel
{
    protected function checkError($return_value)
    {
        if (is_array($return_value) && $return_value[T_IS_ERROR] === TRUE) return TRUE;
        return FALSE;
    }
}