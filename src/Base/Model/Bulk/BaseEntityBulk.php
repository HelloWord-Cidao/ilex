<?php

namespace Ilex\Base\Model\Bulk;

use \Closure;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Collection\MongoDBCursor as MDBCur;
use \Ilex\Base\Model\Entity\BaseEntity as BE;
use \Ilex\Base\Model\Wrapper\EntityWrapper as EW;
use \Ilex\Base\Model\Wrapper\QueryWrapper as QW;

/**
 * Class BaseEntityBulk
 * Base class of entity bulk models of Ilex.
 * @package Ilex\Base\Model\Bulk
 */
class BaseEntityBulk implements BaseBulk
{

    private $queryWrapper = NULL;

    public function __construct(MDBCur $cursor, QW $query_wrapper)
    {
        $entity_list = [];
        $this->queryWrapper = $query_wrapper;
        foreach ($cursor as $document) {
            $entity_list[] = $this->createEntityWithDocument($document);
        }
        parent::__construct($entity_list);
    }

    final private function createEntityWithDocument($document)
    {
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        $entity_name       = $this->queryWrapper->getEntityName();
        $entity_class_name = $this->queryWrapper->getEntityClassName();
        $collection_name   = $this->queryWrapper->getCollectionName();
        $entity_wrapper    = EW::getInstance($collection_name, $entity_class_name);
        return new $entity_class_name($entity_wrapper, $entity_name, TRUE, $document);
    }

    final public function getEntityList()
    {
        return $this->getItemList();
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
            if (TRUE === is_null($is_return_entity)) $is_return_entity = ($item instanceof BE);
            if ($is_return_entity !== $item instanceof BE)
                throw new UserException('Inconsistent behavior of method.');
        }
        if (TRUE === $is_return_entity) return $this;
        else return $result;
    }

    final public function map(Closure $function)
    {
        // Kit::map
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