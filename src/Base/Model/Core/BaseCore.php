<?php

namespace Ilex\Base\Model\Core;

use \Ilex\Core\Loader;
use \Ilex\Base\Model\BaseModel;
use \Ilex\Base\Model\Entity\User\UserEntity;

/**
 * Class BaseCore
 * Base class of core models of Ilex.
 * @package Ilex\Base\Model\Core
 */
abstract class BaseCore //extends BaseModel
{
    const S_OK = 'ok';
    protected $ok = [ self::S_OK => TRUE ];

    final protected function loadCollection($path)
    {
        $handler_name = Loader::getHandlerFromPath($path) . 'Collection';
        return ($this->$handler_name = Loader::loadCollection($path));
    }
}