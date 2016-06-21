<?php

namespace Ilex\Base\Model\Entity;

use \MongoId;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseEntity
 * Base class of entity models of Ilex.
 * @package Ilex\Base\Model\Entity
 */
abstract class BaseEntity extends BaseModel
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            'addToCollection',
            'updateToCollection',
            'isInCollection',
            'isSameAsInCollection',
        ],
    ];

    private $entityWrapper = NULL;
    private $name          = NULL;

    private $isInCollection    = FALSE;
    private $isSameAsCollecton = FALSE;

    private $document = NULL

    final public function __construct($entity_wrapper, $name, $is_in_collection, $document = [])
    {
        Kit::ensureObject($entity_wrapper);
        Kit::ensureString($name);
        Kit::ensureBoolean($is_in_collection);
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        if (TRUE === $is_in_collection AND
            (FALSE === isset($document['_id']) 
                OR FALSE === ($document['_id'] instanceof MongoId)))
            throw new UserException('_id is not set or is not a MongoId.', $this);
        $this->entityWrapper     = $entity_wrapper;
        $this->name              = $name;
        $this->isInCollection    = $is_in_collection;
        $this->isSameAsCollecton = $is_in_collection;
        $this->document          = $document;
    }

    final protected function ensureInitialized()
    {
        if (FALSE === isset($this->entityWrapper))
            throw new UserException('This entity has not been initialized.', $this);
    }

    final protected function isInCollection()
    {
        return $this->isInCollection;
    }

    final protected function ensureInCollection()
    {
        if (FALSE === $this->call('isInCollection'))
            throw new UserException('This entity is not in collection.', $this);
    }

    final protected function isSameAsCollecton()
    {
        return $this->isSameAsCollecton;
    }

    final protected function inCollection()
    {
        $this->isInCollecton     = TRUE;
        $this->isSameAsCollecton = TRUE;
    }

    final protected function notInCollection()
    {
        $this->isInCollecton     = FALSE;
        $this->isSameAsCollecton = FALSE;
    }

    final protected function sameAsCollection()
    {
        $this->isSameAsCollecton = TRUE;
    }

    final protected function notSameAsCollection()
    {
        $this->isSameAsCollecton = FALSE;
    }

    final protected function getDocument()
    {
        Kit::ensureDict($this->document); // @CAUTION
        return $this->document;
    }

    final protected function setId($_id)
    {
        if (FALSE === $_id instanceof MongoId)
            throw new UserException('$_id is not a MongoId.', [ $_id, $this ]);
        return $this->call('set', '_id', $_id, FALSE);
    }

    final protected function deleteId()
    {
        return $this->call('delete', '_id');
    }

    final protected function hasId()
    {
        return $this->call('has', '_id');
    }

    final protected function getId()
    {
        return $this->call('get', '_id');
    }

    final protected function set($path, $value, $ensure_existence = NULL)
    {
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_LIST ]); // @CAUTION
        Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_ARRAY ]);
        // Kit::ensureDict($this->document); // @CAUTION
        Kit::ensureArray($this->document);
        if (TRUE === Kit::isString($path)) {
            if (TRUE === $ensure_existence) $this->call('ensureHas', $path);
            if (FALSE === $ensure_existence) $this->call('ensureHasNo', $path);
            $this->document[$path] = $value
            $this->call('notSameAsCollection');
            return $value;
        }
        else throw new UserException('Can not support list-type $path yet.', [ $path, $value ]);
    }

    final protected function get($path, $ensure_existence = TRUE, $default = NULL)
    {
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_LIST ]); // @CAUTION
        Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_ARRAY ]);
        // Kit::ensureDict($this->document); // @CAUTION
        Kit::ensureArray($this->document);
        Kit::ensureBoolean($ensure_existence);
        if (TRUE === Kit::isString($path)) {
            if (TRUE === $ensure_existence) $this->call('ensureHas', $path);
            if (FALSE === $ensure_existence AND TRUE === is_null($this->document[$path]))
                return $default;
            return $this->document[$path];
        }
        else throw new UserException('Can not support list-type $path yet.', $path);
    }

    final protected function delete($path, $ensure_existence = TRUE)
    {
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_LIST ]); // @CAUTION
        Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_ARRAY ]);
        // Kit::ensureDict($this->document); // @CAUTION
        Kit::ensureArray($this->document);
        Kit::ensureBoolean($ensure_existence);
        if (TRUE === Kit::isString($path)) {
            if (TRUE === $ensure_existence) $this->call('ensureHas', $path);
            if (FALSE === $ensure_existence AND TRUE === is_null($this->document[$path]))
                return NULL;
            $value = $this->document[$path];
            unset($this->document[$path]);
            $this->call('notSameAsCollection');
            return $value;
        }
        else throw new UserException('Can not support list-type $path yet.', $path);
    }

    final protected function has($path)
    {
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_LIST ]); // @CAUTION
        Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_ARRAY ]);
        // Kit::ensureDict($this->document); // @CAUTION
        Kit::ensureArray($this->document);
        if (TRUE === Kit::isString($path)) {
            return FALSE === is_null($this->document[$path]);
        }
        else throw new UserException('Can not support list-type $path yet.', $path);
    }

    final protected function hasNo($path)
    {
        return FALSE === $this->call('has', $path);
    }

    final protected function ensureHas($path)
    {
        if (FALSE === $this->call('has', $path))
            throw new UserException("\$path($path) does not exist.", $this->document);
    }

    final protected function ensureHasNo($path)
    {
        if (FALSE === $this->call('hasNo', $path))
            throw new UserException("\$path($path) does exist.", $this->document);
    }

    final protected function addToCollection()
    {
        if (TRUE === $this->call('isInCollection')) {
            $msg = 'Can not add to collection, because the entity is already in the collection.';
            throw new UserException($msg, $this);
        }
        $_id = $this->entityWrapper->addOneAndGetId($this->call('getDocument'));
        $this->call('setId', $_id);
        $this->call('inCollection');
    }

    final protected function updateToCollection()
    {
        if (FALSE === $this->call('isInCollection')) {
            $msg = 'Can not update to collection, because the entity is not in the collection.';
            throw new UserException($msg, $this);
        }
        $_id = $this->getId();
        $document = $this->document;
        unset($document['_id']);
        $this->entityWrapper->updateTheOnlyOneEntity($_id, $document);
        $this->call('sameAsCollection');
    }
}