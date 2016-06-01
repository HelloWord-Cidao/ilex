<?php

namespace Ilex\Base;

use \Ilex\Core\Loader;

/**
 * Class Base
 * Base class of controllers and models.
 * @package Ilex\Base
 * 
 * @method final protected static object loadModel(string $path, array $arg_list = [])
 */
abstract class Base
{
    /**
     * @param string $path
     * @param array  $arg_list 
     * @return object
     */
    final protected function loadModel($path, $arg_list = [])
    {
        $handler_name = Loader::getHandlerFromPath($path);
        if (TRUE === is_null($this->$handler_name))
            return ($this->$handler_name = Loader::model($path, $arg_list));
        else return $this->$handler_name;
    }
}