<?php

namespace Ilex\Base\Model;

use \Ilex\Base\Base;

/**
 * Class BaseModel
 * Base class of models.
 * @package Ilex\Base\Model
 */
class BaseModel extends Base
{
    protected function generateErrorInfo($description)
    {
        return [
            T_IS_ERROR => TRUE,
            'desc'     => $description,
            'trace'    => array_slice(debug_backtrace(), 1),
        ];
    }
}