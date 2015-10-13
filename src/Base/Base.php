<?php

namespace Ilex\Base;

use \Ilex\Core\Loader;

/**
 * Class Base
 * @package Ilex\Base
 * 
 * @method protected object loadModel()
 */
class Base
{
    /**
     * @todo: what?
     * Protected method that can be called by the controllers in APPPATH/Controller.
     * @return object
     */
    protected function loadModel()
    {
        $params = [];
        foreach (func_get_args() as $index => $n) {
            if ($index === 0) {
                $path = $n;
            } else {
                $params[] = $n;
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