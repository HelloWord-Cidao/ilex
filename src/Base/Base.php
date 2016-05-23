<?php

namespace Ilex\Base;

use ReflectionClass;
use \Ilex\Core\Loader;

/**
 * Class Base
 * Base class of controllers and models.
 * @package Ilex\Base
 * 
 * @method final protected static object loadModel(string $path, array $param_list)
 */
abstract class Base
{
    /**
     * @param string $path
     * @param array  $param_list 
     * @return object
     */
    final protected function loadModel($path, $param_list = [])
    {
        $name  = Loader::getHandlerFromPath($path);
        if (TRUE === is_null(static::$$name)) {
            return (static::$$name = Loader::model($path, $param_list));
        } else {
            return static::$$name;
        }
    }
}