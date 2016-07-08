<?php

namespace Ilex\Base\Model\Core;

use \ReflectionClass;
use \Ilex\Lib\Kit;
use \Ilex\Core\Loader;

/**
 * Class BaseCore
 * Base class of core models of Ilex.
 * @package Ilex\Base\Model\Core
 */
abstract class BaseCore
{
    const S_OK = 'ok';
    protected $ok = [ self::S_OK => TRUE ];

    final protected function loadCore($path)
    {
        $handler_name = Loader::getHandlerFromPath($path) . 'Core';
        return ($this->$handler_name = Loader::loadCore($path));
    }

    final protected function loadCollection($path)
    {
        $handler_name = Loader::getHandlerFromPath($path) . 'Collection';
        return ($this->$handler_name = Loader::loadCollection($path));
    }
}