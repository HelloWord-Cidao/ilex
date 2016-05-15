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
        if (TRUE === is_array($return_value) AND TRUE === $return_value[T_IS_ERROR]) return TRUE;
        return FALSE;
    }
}