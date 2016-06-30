<?php

namespace Ilex\Base\Model\Collection;

use \MongoId;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Wrapper\CollectionWrapper;
use \Ilex\Base\Model\Wrapper\QueryWrapper;
use \Ilex\Base\Model\Wrapper\EntityWrapper;

/**
 * Class BaseCollection
 * Base class of collection models of Ilex.
 * @package Ilex\Base\Model\Collection
 */
abstract class BaseCollection
{

    private $collectionWrapper = NULL;

    // const COLLECTION_NAME = NULL; // should set in subclass
    // const ENTITY_PATH     = NULL; // should set in subclass

    public function __construct()
    {
        $collection_name = static::COLLECTION_NAME;
        $entity_path     = static::ENTITY_PATH;
        Kit::ensureString($collection_name, TRUE);
        if (TRUE === is_null($collection_name)) {
            // throw new UserException('COLLECTION_NAME is not set.'); // @CAUTION
        } else {
            $this->collectionWrapper = CollectionWrapper::getInstance($collection_name, $entity_path);
        }
    }

    final private function ensureInitialized()
    {
        if (FALSE === isset($this->collectionWrapper)
            OR FALSE === $this->collectionWrapper instanceof CollectionWrapper)
            throw new UserException('This collection has not been initialized.');
    }

    final public function createQuery()
    {
        $this->ensureInitialized();
        $query_name        = $this->collectionWrapper->getQueryName();
        $query_class_name  = $this->collectionWrapper->getQueryClassName();
        $collection_name   = $this->collectionWrapper->getCollectionName();
        $query_wrapper     = QueryWrapper::getInstance($collection_name, $query_class_name);
        return new $query_class_name($query_wrapper, $query_name, FALSE);
    }

    final public function createEntity()
    {
        $this->ensureInitialized();
        $entity_name       = $this->collectionWrapper->getEntityName();
        $entity_class_name = $this->collectionWrapper->getEntityClassName();
        $collection_name   = $this->collectionWrapper->getCollectionName();
        $entity_wrapper    = EntityWrapper::getInstance($collection_name, $entity_class_name);
        return new $entity_class_name($entity_wrapper, $entity_name, FALSE);
    }

    final public function getEntityBulkClassName()
    {
        return $this->collectionWrapper->getEntityBulkClassName();
    }

}