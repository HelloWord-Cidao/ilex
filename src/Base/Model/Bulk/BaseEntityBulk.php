<?php

namespace Ilex\Base\Model\Bulk;

use \Closure;
use \MongoId;
use \Ilex\Lib\Bulk;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Lib\MongoDB\MongoDBId;
use \Ilex\Lib\MongoDB\MongoDBCursor;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class BaseEntityBulk
 * Base class of entity bulk models of Ilex.
 * @package Ilex\Base\Model\Bulk
 */
class BaseEntityBulk extends Bulk
{

    final public function __construct(MongoDBCursor $cursor, $collection_name, $entity_path, $entity_class_name)
    {
        Kit::ensureString($collection_name);
        Kit::ensureString($entity_path);
        Kit::ensureString($entity_class_name);
        $this->collectionName  = $collection_name;
        $this->entityPath      = $entity_path;
        $this->entityClassName = $entity_class_name;
        $entity_list = [];
        foreach ($cursor as $document) {
            $entity_list[] = $this->createEntityWithDocument($document);
        }
        parent::__construct($entity_list);
    }

    final private function createEntityWithDocument($document)
    {
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        if (FALSE === isset($document['_id']) OR FALSE === $document['_id'] instanceof MongoId)
            throw new UserException('_id is not set or proper in $document.', $document);
        $document['_id']   = new MongoDBId($document['_id']);
        $entity_class_name = $this->entityClassName;
        return new $entity_class_name($this->collectionName, $this->entityPath, TRUE, $document);
    }

    //===============================================================================================
    
    final public function getEntityList()
    {
        return $this->getItemList();
    }

    final private function setEntityList($entity_list)
    {
        return $this->setItemList($entity_list);
    }

    final private function getTheOnlyOneEntity()
    {
        if (1 !== $this->count())
            throw new UserException('This bulk has no or more than one entities.', $this->getEntityList());
        return $this->getEntityList()[0];
    }

    final public function batch($method_name)
    {
        Kit::ensureString($method_name);
        $arg_list = func_get_args();
        if (count($arg_list) > 1) $arg_list = Kit::slice($arg_list, 1); else $arg_list = [];
        $is_return_entity = NULL;
        $result = [];
        foreach ($this->getEntityList() as $index => $entity) {
            $result[] = ($item = call_user_func_array([ $entity, $method_name ],
                array_merge($arg_list, [ $index ])));
            if (TRUE === is_null($is_return_entity)) $is_return_entity = ($item instanceof BaseEntity);
            if ($is_return_entity !== $item instanceof BaseEntity)
                throw new UserException('Inconsistent behavior of method.');
        }
        if (TRUE === $is_return_entity) {
            return $this->setEntityList($result);
        }
        else return $result;
    }

    final public function map(Closure $function)
    {
        $arg_list = func_get_args();
        if (count($arg_list) > 1) $arg_list = Kit::slice($arg_list, 1); else $arg_list = [];
        $result = [];
        foreach ($this->getEntityList() as $index => $entity) {
            $result[] = call_user_func_array($function,
                array_merge([ $entity ], $arg_list, [ $index ]));
        }
        return $result;
    }

    final public function aggregate(Closure $function, $context)
    {
        $arg_list = func_get_args();
        if (count($arg_list) > 2) $arg_list = Kit::slice($arg_list, 2); else $arg_list = [];
        foreach ($this->getEntityList() as $index => $entity) {
            $context = call_user_func_array($function,
                array_merge([ $entity, $context ], $arg_list, [ $index ]));
        }
        return $context;
    }

    final public function filter(Closure $function)
    {
        $arg_list = func_get_args();
        if (count($arg_list) > 1) $arg_list = Kit::slice($arg_list, 1); else $arg_list = [];
        $result = [];
        foreach ($this->getEntityList() as $index => $entity) {
            if (TRUE === call_user_func_array($function,
                array_merge([ $entity ], $arg_list, [ $index ]))) {
                $result[] = $entity;
            }
        }
        return $this->setEntityList($result);
    }

    final public function filterById($id)
    {
        $detail = $this->batch('getName');
        $result = $this->filter(function ($entity, $id) {
            return $entity->isIdEqualTo($id);
        }, $id);
        if (1 !== $result->count())
            throw new UserException('$id not found in this bulk.', $detail);
        else return $result->getTheOnlyOneEntity();
    }

    final public function filterByRandomlySelect($num)
    {
        return $this->setEntityList(Kit::randomlySelect($this->getEntityList(), $num));
    }

    final public function shuffle()
    {
        return $this->setEntityList(Kit::shuffled($this->getEntityList()));
    }

}