<?php

namespace Ilex\Base;

use \Ilex\Core\Loader;

/**
 * Class Base
 * @package Ilex\Base
 * 
 * @method protected object loadModel(IMPLICIT)
 */
class Base
{
    /**
     * @todo check use of this method in other project!
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
         * @todo definitely Loader::model()? not Loader::controller()?
         * maybe should implement loadController(),
         * and add suffix to $name, i.e., 'About' => 'AboutController'?
         */
        return is_null($this->$name) ? ($this->$name = Loader::model($path, $params)) : $this->$name;
    }
}