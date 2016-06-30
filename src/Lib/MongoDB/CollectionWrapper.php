<?php

namespace Ilex\Base\Model\Wrapper;

use \Exception;
use \Ilex\Core\Loader;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;

/**
 * Class CollectionWrapper
 * @package Ilex\Base\Model\Wrapper
 */
final class CollectionWrapper extends MongoDBCollection
{
    
    private static $collectionWrapperContainer = NULL;

    private $entityName        = NULL;
    private $entityClassName   = NULL;
    private $hasIncludedEntity = FALSE;

    private $entityBulkClassName     = NULL;
    private $hasIncludedEntityBulk   = FALSE;

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
            $this->includeEntity($entity_path);
            $this->includeEntityBulk($entity_path);
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

}