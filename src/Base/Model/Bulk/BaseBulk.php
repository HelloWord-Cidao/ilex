<?php

namespace Ilex\Base\Model\Bulk;

use \Closure;
use \Iterator;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\BaseModel;
use \Ilex\Base\Model\Collection\MongoDBCursor;
use \Ilex\Base\Model\Wrapper\CollectionWrapper;
use \Ilex\Base\Model\Wrapper\EntityWrapper;

/**
 * Class BaseBulk
 * Base class of bulk models of Ilex.
 * @package Ilex\Base\Model\Bulk
 */
class BaseBulk extends BaseModel implements Iterator
{

    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            // 'getEntity',
            'rewind',
            'current',
            'key',
            'next',
            'valid',
            'toList',
            'count',
            'batch',
            'map',

        ],
        self::V_PROTECTED => [
        ],
    ];

    private $position = 0;

    private $collectionWrapper = NULL;

    private $entityList = [];

    final public function __construct(CollectionWrapper $collection_wrapper, MongoDBCursor $cursor)
    {
        $this->position = 0;
        $this->collectionWrapper = $collection_wrapper;
        foreach ($cursor as $document) {
            $this->entityList[] = $this->createEntityWithDocument($document);
        }
    }

    final protected function ensureInitialized()
    {
        if (FALSE === isset($this->collectionWrapper)
            OR FALSE === $this->collectionWrapper instanceof CollectionWrapper)
            throw new UserException('This bulk has not been initialized.');
    }

    final protected function createEntityWithDocument($document)
    {
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        $this->call('ensureInitialized');
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

    final protected function toList()
    {
        return $this->entityList;
    }

    final protected function count()
    {
        return Kit::len($this->entityList);
    }

    final protected function first()
    {
        if (0 === $this->call('count'))
            throw new UserException('Failed to get the first entity, because this bulk is empty.', $this);
        return $this->entityList[0];
    }

    final protected function last()
    {
        if (0 === $this->call('count'))
            throw new UserException('Failed to get the last entity, because this bulk is empty.', $this);
        return Kit::last($this->entityList);
    }

    final protected function batch()
    {
        $arg_list = func_get_args();
        $method_name = $arg_list[0];
        $arg_list = Kit::slice($arg_list, 1);
        $result = [];
        foreach ($this->entityList as $entity) {
            $result[] = call_user_func_array([ $entity, $method_name ], $arg_list);
        }
        return $result;
    }

    final protected function map(Closure $function)
    {
        $result = [];
        foreach ($this->entityList as $index => $entity) {
            $result[] = $function($entity, $index);
        }
        return $result;
    }

}