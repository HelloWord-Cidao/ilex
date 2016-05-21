<?php

namespace Ilex\Base;

use ReflectionClass;
use \Ilex\Core\Loader;

/**
 * Class Base
 * Base class of controllers and models.
 * @package Ilex\Base
 * 
 * @method final protected static object loadModel(IMPLICIT)
 */
abstract class Base
{
    /**
     * @param string $path  IMPLICIT
     * @param mixed  $param IMPLICIT MULTIPLE
     * @return object
     */
    final protected static function loadModel()
    {
        $param_list = [];
        foreach (func_get_args() as $index => $value) {
            if (0 === $index) {
                $path = $value;
            } else {
                $param_list[] = $value;
            }
        }
        $name = Loader::getHandlerFromPath($path);
        // $reflection_class = new ReflectionClass(get_called_class());
        // var_dump($reflection_class->)
        // exit();
        return (TRUE === is_null(static::$$name)) ?
            (static::$$name = Loader::model($path, $param_list)) :
            (static::$$name);
    }
}