<?php

namespace Ilex\Base\Model\Wrapper;

use \Exception;
use \Ilex\Core\Loader;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Collection\MongoDBCollection;

/**
 * Class CollectionWrapper
 * @package Ilex\Base\Model\Wrapper
 */
final class CollectionWrapper extends MongoDBCollection
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            'getEntityName',
            'getEntityClassName',
            'checkExistEntities',
            'ensureExistEntities',
            'checkExistsOnlyOneEntity',
            'ensureExistsOnlyOneEntity',
            'countEntities',
            'getMultiEntities',
            'getTheOnlyOneEntity',
            'getOneEntity',
        ],
        self::V_PROTECTED => [
        ],
    ];

    private static $collectionWrapperContainer = NULL;

    private $entityName        = NULL;
    private $entityClassName   = NULL;
    private $hasIncludedEntity = FALSE;

    private $bulkClassName     = NULL;
    private $hasIncludedBulk   = FALSE;

    final public static function getInstance($collection_name, $entity_path)
    {
        Kit::ensureString($collection_name);
        Kit::ensureString($entity_path);
        if (FALSE === isset(self::$collectionWrapperContainer))
            self::$collectionWrapperContainer = new Container();
        if (TRUE === self::$collectionWrapperContainer->has($entity_path)) 
            return self::$collectionWrapperContainer->get($entity_path);
        else return (self::$collectionWrapperContainer->set(
            $entity_path, new static($collection_name, $entity_path)));
    }

    final protected function __construct($collection_name, $entity_path)
    {
        parent::__construct($collection_name);
        if (TRUE === is_null($entity_path)) {
            // throw new UserException('ENTITY_PATH is not set.'); // @CAUTION
        } else {
            $this->call('includeEntity', $entity_path);
            $this->call('includeBulk', $entity_path);
        }
    }

    final protected function includeEntity($entity_path)
    {
        $collection_name = $this->call('getCollectionName');
        if (TRUE === is_null($entity_path)) {
            throw new UserException("ENTITY_PATH is not set in collection($collection_name).");
        }
        if (FALSE === $this->hasIncludedEntity) {
            $this->entityName        = Loader::getHandlerFromPath($entity_path);
            $this->entityClassName   = Loader::includeEntity($entity_path);
            $this->hasIncludedEntity = TRUE;
        }
    }

    final protected function includeBulk($entity_path)
    {
        $collection_name = $this->call('getCollectionName');
        if (TRUE === is_null($entity_path)) {
            throw new UserException("ENTITY_PATH is not set in collection($collection_name).");
        }
        if (FALSE === $this->hasIncludedBulk) {
            try {
                $this->bulkClassName = Loader::includeBulk($entity_path);
            } catch (Exception $e) {
                $this->bulkClassName = Loader::includeBulk('Base');
            }
            $this->hasIncludedBulk = TRUE;
        }
    }

    final protected function getEntityName()
    {
        $collection_name = $this->call('getCollectionName');
        if (FALSE === isset($this->entityName))
            throw new UserException("Enity has not been included in this collection($collection_name)");
        return $this->entityName;
    }

    final protected function getEntityClassName()
    {
        $collection_name = $this->call('getCollectionName');
        if (FALSE === isset($this->entityClassName))
            throw new UserException("Enity has not been included in this collection($collection_name)");
        return $this->entityClassName;
    }

    final protected function getBulkClassName()
    {
        $collection_name = $this->call('getCollectionName');
        if (FALSE === isset($this->bulkClassName))
            throw new UserException("Bulk has not been included in this collection($collection_name)");
        return $this->bulkClassName;
    }

    final protected function createEntityWithDocument($document)
    {
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        $entity_name       = $this->call('getEntityName');
        $entity_class_name = $this->call('getEntityClassName');
        $collection_name   = $this->call('getCollectionName');
        $entity_wrapper    = EntityWrapper::getInstance($collection_name, $entity_class_name);
        return new $entity_class_name($entity_wrapper, $entity_name, TRUE, $document);
    }

    final protected function checkExistEntities($criterion)
    {
        return $this->call('checkExistence', $criterion);
    }

    final protected function ensureExistEntities($criterion)
    {
        $this->call('ensureExistence', $criterion);
    }

    final protected function checkExistsOnlyOneEntity($criterion)
    {
        return $this->call('checkExistsOnlyOnce', $criterion);
    }

    final protected function ensureExistsOnlyOneEntity($criterion)
    {
        $this->call('ensureExistsOnlyOnce', $criterion);
    }
     
    final protected function countEntities($criterion = [], $skip = NULL, $limit = NULL)
    {
        return $this->call('count', $criterion, $skip, $limit);
    }

    final protected function getMultiEntities($criterion, $sort_by = NULL, $skip = NULL, $limit = NULL)
    {
        $cursor = $this->call('getMulti', $criterion, [], $sort_by, $skip, $limit);
        $bulk_class_name = $this->call('getBulkClassName');
        return new $bulk_class_name($this, $cursor);
    }

    final protected function getTheOnlyOneEntity($criterion)
    {
        $document = $this->call('getTheOnlyOne', $criterion);
        return $this->call('createEntityWithDocument', $document);
    }

    final protected function getOneEntity($criterion, $sort_by = NULL, $skip = NULL, $limit = NULL)
    {
        $document = $this->call('getOne', $criterion, [], $sort_by, $skip, $limit);
        return $this->call('createEntityWithDocument', $document);
    }
}