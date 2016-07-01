<?php

namespace Ilex\Base\Model\Query;

use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\MongoDB\MongoDBId;
use \Ilex\Lib\MongoDB\QueryWrapper;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class BaseQuery
 * Base class of query models of Ilex.
 * @package Ilex\Base\Model\Query
 */
class BaseQuery
{

    private $queryWrapper = NULL;

    private $criterion = NULL;
    private $sortBy    = NULL;
    private $skip      = NULL;
    private $limit     = NULL;

    final public function __construct($collection_name, $entity_path)
    {
        Kit::ensureString($collection_name, TRUE);
        Kit::ensureString($entity_path);
        if (FALSE === is_null($collection_name))
            $this->queryWrapper = QueryWrapper::getInstance($collection_name, $entity_path);
        $this->clear();
    }

    final public function clear()
    {
        $this->criterion = NULL;
        $this->sortBy    = NULL;
        $this->skip      = NULL;
        $this->limit     = NULL;
        return $this;
    }

    final private function ensureInitialized()
    {
        if (FALSE === isset($this->queryWrapper)
            OR FALSE === $this->queryWrapper instanceof QueryWrapper)
            throw new UserException('This query has not been initialized.');
        if (TRUE === is_null($this->criterion))
            throw new UserException('Criterion has not been initialized.');
    }


    //==============================================================================


    final public function all()
    {
        return $this->mergeCriterion([ ]);
    }

    final public function idIs($id)
    {
        if (TRUE === Kit::isString($id))
            $id = new MongoDBId($id);
        elseif (FALSE === $id instanceof MongoDBId)
            throw new UserException('Invalid $id.', $id);
        // Now $id must be MongoDBId
        return $this->isEqualTo('_id', $id->toMongoId());
    }

    // @TODO: move it into ContentQuery
    final public function signatureIs($signature)
    {
        return $this->isEqualTo('Signature', $signature);
    }

    final protected function dataFieldIs($field_name, $field_value)
    {
        return $this->isEqualTo("Data.${field_name}", $field_value);
    }

    final protected function infoFieldIs($field_name, $field_value)
    {
        return $this->isEqualTo("Info.${field_name}", $field_value);
    }

    final public function hasMultiReferenceTo(BaseEntity $entity, $name = NULL)
    {
        if (TRUE === is_null($name)) $name = $entity->getEntityName();
        else Kit::ensureString($name);
        return $this->isEqualTo("Reference.${name}IdList", $entity->getId()->toMongoId());
    }
    
    final public function hasOneReferenceTo(BaseEntity $entity, $name = NULL)
    {
        if (TRUE === is_null($name)) $name = $entity->getEntityName();
        else Kit::ensureString($name);
        return $this->isEqualTo("Reference.${name}Id", $entity->getId()->toMongoId());
    }

    final public function typeIs($type)
    {
        Kit::ensureString($type);
        return $this->isEqualTo('Meta.Type', $type);
    }

    final public function stateIs($state)
    {
        Kit::ensureType($state, [ Kit::TYPE_INT, Kit::TYPE_STRING ]);
        return $this->isEqualTo('Meta.State', $state);
    }

    final public function isCreatedBefore($timestamp)
    {
        // @TODO: $timestamp
        return $this->isLessThan('Meta.CreationTime', $timestamp);
    }

    final public function isCreatedAfter($timestamp)
    {
        // @TODO: $timestamp
        return $this->isGreaterThan('Meta.CreationTime', $timestamp);
    }

    final public function isUpdatedBefore($timestamp)
    {
        // @TODO: $timestamp
        return $this->isLessThan('Meta.ModificationTime', $timestamp);
    }

    final public function isUpdatedAfter($timestamp)
    {
        // @TODO: $timestamp
        return $this->isGreaterThan('Meta.ModificationTime', $timestamp);
    }


    //==============================================================================
    

    final protected function isEqualTo($field_name, $field_value)
    {
        Kit::ensureString($field_name);
        $criterion = [
            $field_name => $field_value,
        ];
        return $this->mergeCriterion($criterion);
    }

    final protected function isGreaterThan($field_name, $field_value)
    {
        Kit::ensureString($field_name);
        $criterion = [
            $field_name => [ '$gt' => $field_value ],
        ];
        return $this->mergeCriterion($criterion);
    }

    final protected function isGreaterThanOrEqualTo($field_name, $field_value)
    {
        Kit::ensureString($field_name);
        $criterion = [
            $field_name => [ '$gte' => $field_value ],
        ];
        return $this->mergeCriterion($criterion);
    }

    final protected function isLessThan($field_name, $field_value)
    {
        Kit::ensureString($field_name);
        $criterion = [
            $field_name => [ '$lt' => $field_value ],
        ];
        return $this->mergeCriterion($criterion);
    }

    final protected function isLessThanOrEqualTo($field_name, $field_value)
    {
        Kit::ensureString($field_name);
        $criterion = [
            $field_name => [ '$lte' => $field_value ],
        ];
        return $this->mergeCriterion($criterion);
    }

    final public function getCriterion()
    {
        return $this->criterion;
    }

    final private function mergeCriterion($criterion)
    {
        Kit::ensureDict($criterion);
        if (TRUE === is_null($this->criterion)) $this->criterion = [ ];
        Kit::update($this->criterion, $criterion);
        return $this;
    }

    //==============================================================================

    // final public function sortBy()
    // {
    //     return $this->sortBy;
    // }

    // final private function mergeSortBy($sort_by)
    // {
    //     Kit::ensureDict($sort_by);
    //     Kit::update($this->sortBy, $sort_by);
    //     return $this;
    // }

    final public function skip($skip = NULL)
    {
        if (TRUE === is_null($skip)) return $this->skip;
        Kit::ensureInt($skip, FALSE, FALSE);
        if ($skip < 0) throw new UserException("\$skip($skip) is negative.");
        $this->skip = $skip;
        return $this;
    }

    final public function limit($limit = NULL)
    {
        if (TRUE === is_null($limit)) return $this->limit;
        Kit::ensureInt($limit);
        $this->limit = $limit;
        return $this;
    }

    //==============================================================================


    final public function checkExistEntities()
    {
        $this->ensureInitialized();
        $result = $this->queryWrapper->checkExistEntities($this->criterion);
        $this->clear();
        return $result;
    }

    final public function ensureExistEntities()
    {
        $this->ensureInitialized();
        $this->queryWrapper->ensureExistEntities($this->criterion);
        $this->clear();
        return $this;
    }

    final public function checkExistsOnlyOneEntity()
    {
        $this->ensureInitialized();
        $result = $this->queryWrapper->checkExistsOnlyOneEntity($this->criterion);
        $this->clear();
        return $this;
    }

    final public function ensureExistsOnlyOneEntity()
    {
        $this->ensureInitialized();
        $this->queryWrapper->ensureExistsOnlyOneEntity($this->criterion);
        $this->clear();
        return $this;
    }
     
    final public function countEntities()
    {
        $this->ensureInitialized();
        $result = $this->queryWrapper->countEntities(
            $this->criterion,
            $this->skip,
            $this->limit
        );
        $this->clear();
        return $result;
    }

    final public function getMultiEntities()
    {
        $this->ensureInitialized();
        $result = $this->queryWrapper->getMultiEntities(
            $this->criterion,
            $this->sortBy,
            $this->skip,
            $this->limit
        );
        $this->clear();
        return $result;
    }

    final public function getTheOnlyOneEntity()
    {
        $this->ensureInitialized();
        $result = $this->queryWrapper->getTheOnlyOneEntity($this->criterion);
        $this->clear();
        return $result;
    }

    final public function getOneEntity()
    {
        $this->ensureInitialized();
        $result = $this->queryWrapper->getOneEntity(
            $this->criterion,
            $this->sortBy,
            $this->skip,
            $this->limit
        );
        $this->clear();
        return $result;
    }

}