<?php

namespace Ilex\Base\Model\Core;

use ReflectionClass;
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
        $handler_name    = Loader::getHandlerFromPath($path) . 'Collection';
        $core_class      = new ReflectionClass(Loader::includeCore($path));
        $collection_name = $core_class->getConstant('COLLECTION_NAME');
        $entity_path     = $core_class->getConstant('ENTITY_PATH');
        Kit::ensureString($collection_name, TRUE);
        Kit::ensureString($entity_path);
        return ($this->$handler_name = Loader::loadCollection($path,
            [ $collection_name, $entity_path ]));
    }
}