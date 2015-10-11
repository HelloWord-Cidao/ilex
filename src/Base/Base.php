<?php

namespace Ilex\Base;

use Ilex\Core\Loader;

/**
 * Class Base
 * @package Ilex\Base
 * 
 * @method protected object load_model()
 */
class Base
{
    /**
     * @todo: what?
     * Protected method that can be called by the controllers in APPPATH/Controller.
     * @return object
     */
    protected function load_model()
    {
        $params = [];
        foreach (func_get_args() as $index => $n) {
            if ($index == 0) {
                $path = $n;
            } else {
                $params[] = $n;
            }
        }
        $name = Loader::getHandlerFromPath($path);
        return is_null($this->$name) ? ($this->$name = Loader::model($path, $params)) : $this->$name;
    }
}