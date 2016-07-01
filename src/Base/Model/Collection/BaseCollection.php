<?php

namespace Ilex\Base\Model\Collection;

use \Exception;
use \Ilex\Lib\Kit;
use \Ilex\Lib\Loader;

/**
 * Class BaseCollection
 * Base class of collection models of Ilex.
 * @package Ilex\Base\Model\Collection
 */
abstract class BaseCollection
{

    // const COLLECTION_NAME = NULL; // should set in subclass
    // const ENTITY_PATH     = NULL; // should set in subclass

    public function __construct()
    {
        $collection_name = static::COLLECTION_NAME;
        $entity_path     = static::ENTITY_PATH;
        Kit::ensureString($collection_name, TRUE);
        Kit::ensureString($entity_path);
        $this->includeQuery();
        $this->includeEntity();
    }

    final public function createQuery()
    {
        $query_class_name = $this->queryClassName;
        Kit::ensureString($query_class_name);
        return new $query_class_name(static::COLLECTION_NAME, static::ENTITY_PATH);
    }

    final private function includeQuery()
    {
        try {
            $this->queryClassName = Loader::includeQuery(static::ENTITY_PATH);
        } catch (Exception $e) {
            $this->queryClassName = Loader::includeQuery('Base');
        }
    }

    final public function createEntity()
    {
        $entity_class_name = $this->entityClassName;
        Kit::ensureString($entity_class_name);
        return new $entity_class_name(static::COLLECTION_NAME, static::ENTITY_PATH, FALSE);
    }

    final private function includeEntity()
    {
        try {
            $this->entityClassName = Loader::includeEntity(static::ENTITY_PATH);
        } catch (Exception $e) {
            $this->entityClassName = Loader::includeEntity('Base');
        }
    }

}