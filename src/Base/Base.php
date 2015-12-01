<?php

namespace Ilex\Base;

use \Ilex\Core\Loader;

/**
 * Class Base
 * Base class of controllers and models.
 * @package Ilex\Base
 * 
 * @method protected object loadModel(IMPLICIT)
 */
class Base
{
    /**
     * Protected method that can be called by the controllers in APPPATH/Controller.
     * @param string $path  IMPLICIT
     * @param mixed  $param IMPLICIT MULTIPLE
     * @return object
     */
    protected function loadModel()
    {
        $params = [];
        foreach (func_get_args() as $index => $value) {
            if ($index === 0) {
                $path = $value;
            } else {
                $params[] = $value;
            }
        }
        $name = Loader::getHandlerFromPath($path);
        /**
         * @todo maybe should add suffix to $name, i.e., 'Session' => 'SessionModel'?
         */
        return is_null($this->$name) ? ($this->$name = Loader::model($path, $params)) : $this->$name;
    }
}