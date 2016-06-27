<?php

namespace Ilex\Base\Model\Bulk;

use \Closure;
use \Iterator;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Collection\MongoDBCursor;
use \Ilex\Base\Model\Collection\BaseCollection;
use \Ilex\Base\Model\Entity\BaseEntity;
use \Ilex\Base\Model\Wrapper\CollectionWrapper;
use \Ilex\Base\Model\Wrapper\EntityWrapper;

/**
 * Class BaseEntityBulk
 * Base class of entity bulk models of Ilex.
 * @package Ilex\Base\Model\Bulk
 */
class BaseEntityBulk implements Iterator
{

    private $position = 0;

    private $collectionWrapper = NULL;

    private $entityList = [];

    final public function __construct($cursor_or_id_list, $collection_or_wrapper)
    {
        if (TRUE === $cursor_or_id_list instanceof MongoDBCursor
            AND TRUE === $collection_or_wrapper instanceof CollectionWrapper) {
            $this->position = 0;
            $this->collectionWrapper = $collection_or_wrapper;
            foreach ($cursor_or_id_list as $document) {
                $this->entityList[] = $this->createEntityWithDocument($document);
            }
        } elseif (TRUE === Kit::isArray($cursor_or_id_list)
            AND TRUE === $collection_or_wrapper instanceof BaseCollection) {
            $this->position = 0;
            foreach ($cursor_or_id_list as $id) {
                $this->entityList[] = $collection_or_wrapper->getTheOnlyOneEntityById($id);
            }
        } else throw new UserException('Invalid args.',
            [ $cursor_or_id_list, $collection_or_wrapper ]);
        
        
    }

    final private function ensureInitialized()
    {
        if (FALSE === isset($this->collectionWrapper)
            OR FALSE === $this->collectionWrapper instanceof CollectionWrapper)
            throw new UserException('This entity bulk has not been initialized.');
    }

    final private function createEntityWithDocument($document)
    {
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        $this->ensureInitialized();
        $entity_name       = $this->collectionWrapper->getEntityName();
        $entity_class_name = $this->collectionWrapper->getEntityClassName();
        $collection_name   = $this->collectionWrapper->getCollectionName();
        $entity_wrapper    = EntityWrapper::getInstance($collection_name, $entity_class_name);
        return new $entity_class_name($entity_wrapper, $entity_name, TRUE, $document);
    }

    final public function rewind() {
        $this->position = 0;
    }

    final public function current() {
        return $this->entityList[$this->position];
    }

    final public function key() {
        return $this->position;
    }

    final public function next() {
        ++$this->position;
    }

    final public function valid() {
        return TRUE === isset($this->entityList[$this->position]);
    }

    final public function getEntityList()
    {
        return $this->entityList;
    }

    final public function count()
    {
        return Kit::len($this->entityList);
    }

    final public function first()
    {
        if (0 === $this->count())
            throw new UserException('Failed to get the first entity, because this entity bulk is empty.', $this);
        return $this->entityList[0];
    }

    final public function last()
    {
        if (0 === $this->count())
            throw new UserException('Failed to get the last entity, because this entity bulk is empty.', $this);
        return Kit::last($this->entityList);
    }

    final public function batch($method_name)
    {
        Kit::ensureString($method_name);
        $arg_list = func_get_args();
        if (count($arg_list) > 1) $arg_list = Kit::slice($arg_list, 1); else $arg_list = [];
        $is_return_entity = NULL;
        $result = [];
        foreach ($this->entityList as $index => $entity) {
            $result[] = ($item = call_user_func_array([ $entity, $method_name ],
                array_merge($arg_list, [ $index ])));
            if (TRUE === is_null($is_return_entity)) $is_return_entity = ($item instanceof BaseEntity);
            if ($is_return_entity !== $item instanceof BaseEntity)
                throw new UserException('Inconsistent behavior of method.');
        }
        if (TRUE === $is_return_entity) return $this;
        else return $result;
    }

    final public function map(Closure $function)
    {
        Kit::map
    }

    final public function iterMap(Closure $function, $context)
    {
        $arg_list = func_get_args();
        if (count($arg_list) > 2) $arg_list = Kit::slice($arg_list, 2); else $arg_list = [];
        foreach ($this->entityList as $index => $entity) {
            $context = call_user_func_array($function,
                array_merge([ $entity, $context ], $arg_list, [ $index ]));
        }
        return $context;
    }

    final public function filter(Closure $function)
    {
        if (count($arg_list) > 1) $arg_list = Kit::slice($arg_list, 1); else $arg_list = [];
        $result = [];
    }

}