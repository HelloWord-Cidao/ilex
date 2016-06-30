<?php

namespace Ilex\Base\Model\Wrapper;

use \Exception;
use \Ilex\Core\Loader;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Wrapper\EntityWrapper as QW;

/**
 * Class QueryWrapper
 * @package Ilex\Base\Model\Wrapper
 */
final class QueryWrapper extends MongoDBCollection
{
    
    private static $queryWrapperContainer = NULL;

    private $entityName        = NULL;
    private $entityClassName   = NULL;
    private $hasIncludedEntity = FALSE;

    private $entityBulkClassName     = NULL;
    private $hasIncludedEntityBulk   = FALSE;

    final public static function getInstance($collection_name, $entity_path)
    {
        Kit::ensureString($collection_name);
        Kit::ensureString($entity_path);
        if (FALSE === isset(self::$queryWrapperContainer))
            self::$queryWrapperContainer = new Container();
        if (TRUE === self::$queryWrapperContainer->has($entity_path)) 
            return self::$queryWrapperContainer->get($entity_path);
        else return (self::$queryWrapperContainer->set(
            $entity_path, new static($collection_name, $entity_path)));
    }

    final protected function __construct($collection_name, $entity_path)
    {
        parent::__construct($collection_name);
        if (TRUE === is_null($entity_path)) {
            // throw new UserException('ENTITY_PATH is not set.'); // @CAUTION
        } else {
            $this->includeEntity($entity_path);
            $this->includeEntityBulk($entity_path);
        }
    }

    final private function includeEntity($entity_path)
    {
        $collection_name = $this->collectionName;
        if (TRUE === is_null($entity_path)) {
            throw new UserException("ENTITY_PATH is not set in collection($collection_name).");
        }
        if (FALSE === $this->hasIncludedEntity) {
            try {
                $this->entityClassName = Loader::includeEntity($entity_path);
            } catch (Exception $e) {
                $this->entityClassName = Loader::includeEntity('Base');
            }
            $this->entityName = Loader::getHandlerFromPath($entity_path);
            $this->hasIncludedEntity = TRUE;
        }
    }

    final private function includeEntityBulk($entity_path)
    {
        $collection_name = $this->collectionName;
        if (TRUE === is_null($entity_path)) {
            throw new UserException("ENTITY_PATH is not set in collection($collection_name).");
        }
        if (FALSE === $this->hasIncludedEntityBulk) {
            try {
                $this->entityBulkClassName = Loader::includeEntityBulk($entity_path);
            } catch (Exception $e) {
                $this->entityBulkClassName = Loader::includeEntityBulk('Base');
            }
            $this->hasIncludedEntityBulk = TRUE;
        }
    }

    final public function getEntityName()
    {
        $collection_name = $this->collectionName;
        if (FALSE === isset($this->entityName))
            throw new UserException("Enity has not been included in this collection($collection_name)");
        return $this->entityName;
    }

    final public function getEntityClassName()
    {
        $collection_name = $this->collectionName;
        if (FALSE === isset($this->entityClassName))
            throw new UserException("Enity has not been included in this collection($collection_name)");
        return $this->entityClassName;
    }

    final public function getEntityBulkClassName()
    {
        $collection_name = $this->collectionName;
        if (FALSE === isset($this->entityBulkClassName))
            throw new UserException("Entity bulk has not been included in this collection($collection_name)");
        return $this->entityBulkClassName;
    }

    final public function checkExistEntities($criterion)
    {
        return $this->checkExistence($criterion);
    }

    final public function ensureExistEntities($criterion)
    {
        $this->ensureExistence($criterion);
    }

    final public function checkExistsOnlyOneEntity($criterion)
    {
        return $this->checkExistsOnlyOnce($criterion);
    }

    final public function ensureExistsOnlyOneEntity($criterion)
    {
        $this->ensureExistsOnlyOnce($criterion);
    }
     
    final public function countEntities($criterion = [], $skip = NULL, $limit = NULL)
    {
        return $this->count($criterion, $skip, $limit);
    }

    final public function getMultiEntities($criterion, $sort_by = NULL, $skip = NULL, $limit = NULL)
    {
        $cursor = $this->getMulti($criterion, [], $sort_by, $skip, $limit);
        $entity_bulk_class_name = $this->getEntityBulkClassName();
        return new $entity_bulk_class_name($cursor, $this);
    }

    final public function getTheOnlyOneEntity($criterion)
    {
        $document = $this->getTheOnlyOne($criterion);
        return $this->createEntityWithDocument($document);
    }

    final public function getOneEntity($criterion, $sort_by = NULL, $skip = NULL, $limit = NULL)
    {
        $document = $this->getOne($criterion, [], $sort_by, $skip, $limit);
        return $this->createEntityWithDocument($document);
    }

    final private function createEntityWithDocument($document)
    {
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        $entity_class_name = $this->getEntityClassName();
        $entity_wrapper    = EW::getInstance($this->collectionName, $entity_class_name);
        return new $entity_class_name($entity_wrapper, $this->getEntityName(), TRUE, $document);
    }
}