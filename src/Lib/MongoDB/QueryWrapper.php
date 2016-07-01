<?php

namespace Ilex\Lib\MongoDB;

use \Exception;
use \MongoId;
use \Ilex\Core\Loader;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * Class QueryWrapper
 * @package Ilex\Lib\MongoDB
 */
final class QueryWrapper extends MongoDBCollection
{
    
    private static $queryWrapperContainer = NULL;

    private $entityPath          = NULL;
    private $entityClassName     = NULL;
    private $entityBulkClassName = NULL;

    final public static function getInstance($collection_name, $entity_path)
    {
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
        // @TODO: check when $collection_name and $entity_path can be null
        parent::__construct($collection_name);
        $this->entityPath = $entity_path;
        if (TRUE === is_null($entity_path))
            throw new UserException('ENTITY_PATH is not set.'); // @CAUTION
        $this->includeEntity();
        $this->includeEntityBulk();
    }

    final private function includeEntity()
    {
        try {
            $this->entityClassName = Loader::includeEntity($this->entityPath);
        } catch (Exception $e) {
            $this->entityClassName = Loader::includeEntity('Base');
        }
    }

    final private function includeEntityBulk()
    {
        try {
            $this->entityBulkClassName = Loader::includeEntityBulk($this->entityPath);
        } catch (Exception $e) {
            $this->entityBulkClassName = Loader::includeEntityBulk('Base');
        }
    }

    //===============================================================================================

    final public function checkExistEntities($criterion)
    {
        $this->ensureCriterionHasProperId($criterion);
        return $this->checkExistence($criterion);
    }

    final public function ensureExistEntities($criterion)
    {
        $this->ensureCriterionHasProperId($criterion);
        $this->ensureExistence($criterion);
    }

    final public function checkExistsOnlyOneEntity($criterion)
    {
        $this->ensureCriterionHasProperId($criterion);
        return $this->checkExistsOnlyOnce($criterion);
    }

    final public function ensureExistsOnlyOneEntity($criterion)
    {
        $this->ensureCriterionHasProperId($criterion);
        $this->ensureExistsOnlyOnce($criterion);
    }
     
    final public function countEntities($criterion = [], $skip = NULL, $limit = NULL)
    {
        $this->ensureCriterionHasProperId($criterion);
        return $this->count($criterion, $skip, $limit);
    }

    final public function getMultiEntities($criterion, $sort_by = NULL, $skip = NULL, $limit = NULL)
    {
        $this->ensureCriterionHasProperId($criterion);
        $cursor = $this->getMulti($criterion, [ ], $sort_by, $skip, $limit);
        $entity_bulk_class_name = $this->entityBulkClassName;
        Kit::ensureString($entity_bulk_class_name);
        return new $entity_bulk_class_name($cursor,
            $this->collectionName, $this->entityPath, $this->entityClassName);
    }

    final public function getTheOnlyOneEntity($criterion)
    {
        $this->ensureCriterionHasProperId($criterion);
        $document = $this->getTheOnlyOne($criterion);
        return $this->createEntityWithDocument($document);
    }

    final public function getOneEntity($criterion, $sort_by = NULL, $skip = NULL, $limit = NULL)
    {
        $this->ensureCriterionHasProperId($criterion);
        $document = $this->getOne($criterion, [ ], $sort_by, $skip, $limit);
        return $this->createEntityWithDocument($document);
    }

    //===============================================================================================

    final private function ensureCriterionHasProperId(&$criterion)
    {
        Kit::ensureArray($criterion);
        if (TRUE === isset($criterion['_id']) AND FALSE === $criterion['_id'] instanceof MongoId)
            throw new UserException('$criterion has improper _id.', $criterion);
    }

    final private function createEntityWithDocument($document)
    {
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        if (FALSE === isset($document['_id']) OR FALSE === $document['_id'] instanceof MongoId)
            throw new UserException('_id is not set or proper in $document.', $document);
        $document['_id']   = new MongoDBId($document['_id']);
        $entity_class_name = $this->entityClassName;
        Kit::ensureString($entity_class_name);
        return new $entity_class_name($this->collectionName, $this->entityPath, TRUE, $document);
    }
}