<?php

namespace Ilex\Base\Model\Query;

use \MongoId;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Wrapper\QueryWrapper as QW;
use \Ilex\Base\Model\Entity\BaseEntity as BE;

/**
 * Class BaseQuery
 * Base class of query models of Ilex.
 * @package Ilex\Base\Model\Query
 */
abstract class BaseQuery
{

    private $queryWrapper = NULL;

    private $criterion = [];
    private $sortBy    = NULL;
    private $skip      = NULL;
    private $limit     = NULL;

    final public function __construct($collection_name, $entity_path)
    {
        Kit::ensureString($collection_name, TRUE);
        if (TRUE === is_null($collection_name)) {
            // throw new UserException('COLLECTION_NAME is not set.'); // @CAUTION
        } else {
            $this->queryWrapper = QW::getInstance($collection_name, $entity_path);
        }
        $this->clear();
    }

    final public function clear()
    {
        $this->criterion = [];
        $this->sortBy    = NULL;
        $this->skip      = NULL;
        $this->limit     = NULL;
        return $this;
    }

    final private function ensureInitialized()
    {
        if (FALSE === isset($this->queryWrapper)
            OR FALSE === $this->queryWrapper instanceof QW)
            throw new UserException('This query has not been initialized.');
        if (TRUE === is_null($this->criterion))
            throw new UserException('Criterion has not been initialized.');
    }

    final public function idIs($id)
    {
        if (FALSE === MDBC::isMongoId($id)) $id = MDBC::stringToMongoId($id);
        return $this->isEqualTo('_id', $id);
    }

    final protected function dataIs($field_name, $field_value)
    {
        return $this->isEqualTo("Data.${field_name}", $field_value);
    }

    final protected function infoIs($field_name, $field_value)
    {
        return $this->isEqualTo("Info.${field_name}", $field_value);
    }

    // @TODO: move it into ContentQuery
    // final public function signatureIs($signature)
    // {
    //     return $this->isEqualTo('Signature', $signature);
    // }

    final public function hasMultiReferenceTo(BE $entity, $name = NULL)
    {
        if (TRUE === is_null($name)) $name = $entity->getEntityName();
        else Kit::ensureString($name);
        return $this->isEqualTo("Reference.${name}IdList", $entity->getId());
    }
    
    final public function hasOneReferenceTo(BE $entity, $name = NULL)
    {
        if (TRUE === is_null($name)) $name = $entity->getEntityName();
        else Kit::ensureString($name);
        return $this->isEqualTo("Reference.${name}Id", $entity->getId());
    }

    final public function typeIs($type)
    {
        return $this->isEqualTo('Meta.Type', $type);
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
        Kit::ensureDict($criterion)
        if (TRUE === is_null($this->criterion)) $this->criterion = [];
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
        Kit::ensureInt($skip);
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