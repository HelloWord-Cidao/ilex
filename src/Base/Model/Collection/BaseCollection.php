<?php

namespace Ilex\Base\Model\Collection;

use \Exception;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class BaseCollection
 * Base class of collection models of Ilex.
 * @package Ilex\Base\Model\Collection
 */
class BaseCollection
{

    protected $queryClassName  = NULL;
    protected $entityClassName = NULL;

    private $collectionName = NULL;
    private $entityPath     = NULL;

    public function __construct($collection_name, $entity_path)
    {
        Kit::ensureString($collection_name, TRUE);
        Kit::ensureString($entity_path);
        $this->collectionName = $collection_name;
        $this->entityPath     = $entity_path;
        $this->includeQuery();
        $this->includeEntity();
    }

    final private function includeQuery()
    {
        $this->queryClassName = Loader::includeQuery($this->entityPath);
    }

    final public function createQuery()
    {
        $query_class_name = $this->queryClassName;
        Kit::ensureString($query_class_name);
        return new $query_class_name($this->collectionName, $this->entityPath);
    }

    final private function includeEntity()
    {
        $this->entityClassName = Loader::includeEntity($this->entityPath);
    }

    final public function createEntity()
    {
        $entity_class_name = $this->entityClassName;
        Kit::ensureString($entity_class_name);
        return new $entity_class_name($this->collectionName, $this->entityPath, FALSE);
    }


    // ====================================================================================


    final public function checkExistsId($id)
    {
        return $this->createQuery()->idIs($id)->checkExistsOnlyOneEntity();
    }

    final public function checkExistsSignature($signature)
    {
        return $this->createQuery()->signatureIs($signature)->checkExistsOnlyOneEntity();
    }
    final public function countAll()
    {
        return $this->createQuery()->all()->countEntities();
    }
    final public function getAllEntities()
    {
        return $this->createQuery()->all()->getMultiEntities();
    }

    final public function getTheOnlyOneEntityById($id)
    {
        return $this->createQuery()->idIs($id)->getTheOnlyOneEntity();
    }

    final public function getTheOnlyOneEntityBySignature($signature)
    {
        return $this->createQuery()->signatureIs($signature)->getTheOnlyOneEntity();
    }
    
    final public function getAllEntitiesByMultiReference(BaseEntity $entity, $name = NULL)
    {
        return $this->createQuery()->hasMultiReferenceTo($entity)->getMultiEntities();
    }

    final public function getAllEntitiesByOneReference(BaseEntity $entity, $name = NULL)
    {
        return $this->createQuery()->hasOneReferenceTo($entity)->getMultiEntities();
    }

    final public function getAllEntitiesByType($type)
    {
        return $this->createQuery()->typeIs($type)->getMultiEntities();
    }

}