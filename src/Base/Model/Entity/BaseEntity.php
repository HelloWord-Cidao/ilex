<?php

namespace Ilex\Base\Model\Entity;

use \ReflectionClass;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Lib\MongoDB\MongoDBId;
use \Ilex\Lib\MongoDB\MongoDBDate;
use \Ilex\Lib\MongoDB\EntityWrapper;

/**
 * Class BaseEntity
 * Base class of entity models of Ilex.
 * @package Ilex\Base\Model\Entity
 */
class BaseEntity
{

    private $collectionName     = NULL;
    private $entityPath         = NULL;
    private $name               = NULL;
    private $isInCollection     = FALSE;
    private $isSameAsCollection = FALSE;

    private static $rootFieldNameList = [
        'Signature',
        'Data',
        'Info',
        'Reference',
        'Meta',
    ];
    private $entityWrapper = NULL;
    private $document      = NULL;
    private $isReadOnly    = FALSE;

    final public function __construct($collection_name, $entity_path, $is_in_collection, $document = NULL)
    {
        Kit::ensureString($collection_name, TRUE);
        Kit::ensureString($entity_path);
        Kit::ensureBoolean($is_in_collection);
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document, TRUE);
        if (TRUE === $is_in_collection AND
            (FALSE === isset($document['_id']) 
                OR FALSE === ($document['_id'] instanceof MongoDBId)))
            throw new UserException('_id is not set or is not a MongoDBId.', $this);
        $this->collectionName = $collection_name;
        $this->entityPath     = $entity_path;
        if (FALSE === is_null($collection_name))
            $this->entityWrapper  = EntityWrapper::getInstance($collection_name, $entity_path);;
        $this->name               = Loader::getHandlerFromPath($entity_path);
        $this->isInCollection     = $is_in_collection;
        $this->isSameAsCollection = $is_in_collection;
        if (FALSE === is_null($document))
            $this->document = $document;
        else $this->document = [
            'Data'      => [ ],
            'Info'      => [ ],
            'Reference' => [ ],
            'Meta'      => [
                'Type' => $this->name,
            ],
        ];
    }

    final protected function loadCore($path)
    {
        $handler_name = Loader::getHandlerFromPath($path) . 'Core';
        return ($this->$handler_name = Loader::loadCore($path));
    }

    final private function ensureInitialized()
    {
        if (FALSE === isset($this->entityWrapper)
            OR FALSE === $this->entityWrapper instanceof EntityWrapper)
            throw new UserException('This entity has not been initialized.', $this);
        return $this;
    }

    final public function getEntityName()
    {
        return $this->name;
    }

    final public function isInCollection()
    {
        return $this->isInCollection;
    }

    final private function ensureInCollection()
    {
        if (FALSE === $this->isInCollection)
            throw new UserException('This entity is not in collection.', $this);
        return $this;
    }

    final public function setReadOnly()
    {
        $this->isReadOnly = TRUE;
        return $this;
    }

    final public function setNotReadOnly()
    {
        $this->isReadOnly = FALSE;
        return $this;
    }

    final public function isReadOnly()
    {
        return $this->isReadOnly;
    }

    final public function ensureNotReadOnly()
    {
        if (TRUE === $this->isReadOnly())
            throw new UserException("This entity({$this->name}) is read-only.");
        return $this;
    }

    //=======================================================================================


    final public function document()
    {
        // Kit::ensureDict($this->document); // @CAUTION
        return $this->document;
    }

    final public function is(BaseEntity $entity)
    {
        return $this->isIdEqualTo($entity->getId());
    }

    final public function getId($to_string = FALSE)
    {
        $id = $this->ensureInCollection()->get('_id');
        if (TRUE === $to_string) return $id->toString();
        else return $id;
    }

    final public function isIdEqualTo($id)
    {
        return $this->getId()->isEqualTo($id);
    }


    // ======================================= Signature =============================================
    

    final public function setSignature($signature)
    {
        $this->ensureHasNo('Signature')->setDocument('Signature', NULL, $signature, FALSE);
        return $this;
    }
    
    final public function getSignature()
    {
        return $this->getDocument('Signature', NULL);
    }

    
    // ======================================= Data =============================================
    

    final public function setData($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        $this->handleSet('Data', $arg1, $arg2);
        return $this;
    }

    final public function getData($data_name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->handleGet('Data', $data_name, $ensure_existence, $default);
    }


    // ======================================= Info =============================================


    final public function setName($name)
    {
        Kit::ensureString($name);
        return $this->setInfo('Name', $name);
    }

    final public function getName()
    {
        return $this->getInfo('Name');
    }

    final public function setInfo($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        $this->handleSet('Info', $arg1, $arg2);
        return $this;
    }

    final public function getInfo($info_name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->handleGet('Info', $info_name, $ensure_existence, $default);
    }


    // ======================================= Reference =============================================
    // ======================================= one =============================================
    
    final public function hasMultiReference($reference_name)
    {
        Kit::ensureString($reference_name);
        return $this->handleHas('Reference', $reference_name . 'IdList');
    }

    // O(N) when $check_duplicate is TRUE
    final public function buildMultiReferenceTo(BaseEntity $entity, $reference_name = NULL, $check_duplicate = FALSE)
    {
        Kit::ensureString($reference_name, TRUE);
        Kit::ensureBoolean($check_duplicate);
        if (TRUE === is_null($reference_name))
            $field_name  = $entity->getEntityName() . 'IdList';
        else $field_name = $reference_name . 'IdList';
        $entity_id   = $entity->getId();
        $field_value = $this->getDocument('Reference', $field_name, FALSE, []);
        if (TRUE === $check_duplicate) {
            foreach ($field_value as $id) {
                if (TRUE === $entity_id->isEqualTo(new MongoDBId($id)))
                    return $this;
            }
        }
        $field_value[] = $entity_id->toMongoId();
        $this->setDocument('Reference', $field_name, $field_value);
        return $this;
    }

    // @TODO: check efficiency
    final public function getEntitiesByMultiReference($reference_name, $entity_path)
    {
        Kit::ensureString($entity_path);
        return $this->loadCore($entity_path)
            ->getAllEntitiesByIdList($this->getMultiReference($reference_name));
    }

    // O(N)
    final public function hasMultiReferenceTo(BaseEntity $entity, $reference_name = NULL)
    {
        if (TRUE === is_null($reference_name))
            $reference_name = $entity->getEntityName();
        foreach ($this->getMultiReference($reference_name) as $id) {
            if ($entity->getId()->isEqualTo($id)) return TRUE;
        };
        return FALSE;
    }

    // O(N)
    final public function ensureHasMultiReferenceTo(BaseEntity $entity, $reference_name = NULL)
    {
        if (FALSE === $this->hasMultiReferenceTo($entity, $reference_name)) {
            $msg = 'This entity does not have multi reference to the entity.';
            throw new UserException($msg, [ $entity->getName(), $reference_name ]);
        }
        return $this;
    }

    /**
     * O(N) when $to_mongoDB_id is TRUE
     * @param string $reference_name
     * @param boolean $to_mongoDB_id
     * @return List of MongoId or MongoDBId when $to_mongoDB_id is TRUE
     */
    final public function getMultiReference($reference_name, $to_mongoDB_id = FALSE)//, $ensure_existence = TRUE, $default = NULL)
    {
        Kit::ensureString($reference_name);
        if (TRUE === $to_mongoDB_id) {
            $result = [ ];
            foreach ($this->getReference($reference_name . 'IdList') as $id) {
                $result[] = new MongoDBId($id);
            }
            return $result;
        } else return $this->getReference($reference_name . 'IdList');
    }

    // ======================================= one =============================================
    
    final public function hasOneReference($reference_name)
    {
        Kit::ensureString($reference_name);
        return $this->handleHas('Reference', $reference_name . 'Id');
    }

    final public function buildOneReferenceTo(BaseEntity $entity, $reference_name = NULL, $ensure_no_existence = FALSE)
    {
        Kit::ensureString($reference_name, TRUE);
        Kit::ensureBoolean($ensure_no_existence);
        if (TRUE === is_null($reference_name))
            $field_name  = $entity->getEntityName() . 'Id';
        else $field_name = $reference_name . 'Id';
        $entity_id   = $entity->getId();
        $field_value = $this->getDocument('Reference', $field_name, FALSE);
        if (TRUE === $ensure_no_existence AND FALSE === is_null($field_value)) {
            $msg = "Can not build reference($field_name) as " . $entity_id->toString()
                . ", old value is " . $field_value->toString() . ".";
            throw new UserException($msg);
        }
        $this->setDocument('Reference', $field_name, $entity_id->toMongoId());
        return $this;
    }

    final public function getEntityByOneReference($reference_name, $entity_path)
    {
        Kit::ensureString($entity_path);
        return $this->loadCore($entity_path)
            ->getTheOnlyOneEntityById($this->getOneReference($reference_name));
    }

    final public function hasOneReferenceTo(BaseEntity $entity, $reference_name = NULL)
    {
        if (TRUE === is_null($reference_name))
            $reference_name = $entity->getEntityName();
        return $entity->getId()->isEqualTo($this->getOneReference($reference_name));
    }

    final public function ensureHasOneReferenceTo(BaseEntity $entity, $reference_name = NULL)
    {
        if (FALSE === $this->hasOneReferenceTo($entity, $reference_name)) {
            $msg = 'This entity does not have one reference to the entity.';
            throw new UserException($msg, [ $entity->getName(), $reference_name ]);
        }
        return $this;
    }

    /**
     * @param string $reference_name
     * @return MongoDBId
     */
    final public function getOneReference($reference_name)//, $ensure_existence = TRUE, $default = NULL)
    {
        Kit::ensureString($reference_name);
        return new MongoDBId($this->getReference($reference_name . 'Id'));//, $ensure_existence, $default);
    }

    // ======================================= basic =============================================

    final private function getReference($reference_name)// = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->handleGet('Reference', $reference_name, TRUE, NULL);
    }

    final private function setReference($reference_name, $reference_value)
    {
        Kit::ensureString($reference_name);
        return $this->handleSet('Reference', $reference_name, $reference_value);
    }


    // ======================================= Meta =============================================


    final public function setState($state)
    {
        Kit::ensureType($state, [ Kit::TYPE_STRING, Kit::TYPE_INT ]);
        return $this->setMeta('State', $state);
    }

    final public function getState()
    {
        return $this->getMeta('State', FALSE);
    }

    final public function getType()
    {
        return $this->getMeta('Type');
    }

    final public function getCreationTimestamp()
    {
        return MongoDBDate::toTimestamp($this->getMeta('CreationTime'));
    }

    final public function getModificationTimestamp()
    {
        return MongoDBDate::toTimestamp($this->getMeta('ModificationTime'));
    }
    
    final public function setMeta($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        $this->handleSet('Meta', $arg1, $arg2);
        return $this;
    }

    final public function getMeta($name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->handleGet('Meta', $name, $ensure_existence, $default);
    }


    // ======================================= Wrapper =============================================


    final public function addToCollection()
    {
        $this->ensureNotReadOnly();
        if (TRUE === $this->isInCollection) {
            $msg = 'Can not add to collection, because the entity is already in the collection.';
            throw new UserException($msg, $this);
        }
        $document = $this->entityWrapper->addOneEntity($this);
        $this->setId($document['_id']);
        $this->setMeta('CreationTime', $document['Meta']['CreationTime']);
        $this->inCollection();
        return $this;
    }

    final public function updateToCollection()
    {
        $this->ensureNotReadOnly();
        if (FALSE === $this->isInCollection) {
            // var_dump([$this->isInCollection, $this->name, $this->document]);
            $msg = 'Can not update to collection, because the entity is not in the collection.';
            throw new UserException($msg, $this);
        }
        $document = $this->entityWrapper->updateTheOnlyOneEntity($this);
        $this->setMeta('ModificationTime', $document['Meta']['ModificationTime']);
        $this->sameAsCollection();
        return $this;
    }

    // ====================================================================================


    final private function inCollection()
    {
        $this->isInCollection     = TRUE;
        $this->isSameAsCollection = TRUE;
        return $this;
    }

    final private function notInCollection()
    {
        $this->isInCollection     = FALSE;
        $this->isSameAsCollection = FALSE;
        return $this;
    }

    final private function sameAsCollection()
    {
        $this->isSameAsCollection = TRUE;
        return $this;
    }

    final private function notSameAsCollection()
    {
        $this->isSameAsCollection = FALSE;
        return $this;
    }

    final private function setId($id)
    {
        if (FALSE === $id instanceof MongoDBId)
            throw new UserException('$id is not a MongoDBId.', [ $id, $this ]);
        $this->set('_id', $id, FALSE);
        return $this;
    }

    final private function deleteId()
    {
        $this->delete('_id');
        return $this;
    }

    final private function hasId()
    {
        return $this->has('_id');
    }

    final private function handleSet($root_field_name, $arg1, $arg2)
    {
        if (TRUE === Kit::isVacancy($arg2))
            return $this->setDocument($root_field_name, NULL, $arg1);
        else {
            Kit::ensureString($arg1);
            return $this->setDocument($root_field_name, $arg1, $arg2);
        }
    }

    final private function handleHas($root_field_name, $field_name)
    {
        return $this->hasDocument($root_field_name, $field_name);
    }

    final private function handleGet($root_field_name, $field_name, $ensure_existence, $default)
    {
        if (TRUE === is_null($field_name))
            return $this->getDocument($root_field_name, $field_name);
        else return $this->getDocument($root_field_name, $field_name, $ensure_existence, $default);
    }

    final private function setDocument($root_field_name, $field_name, $field_value, $ensure_dict = TRUE)
    {
        if (FALSE === Kit::in($root_field_name, self::$rootFieldNameList))
            throw new UserException('Invalid $root_field_name.', $root_field_name);
        Kit::ensureString($field_name, TRUE);
        if ('' === $field_name)
            throw new UserException('$field_name is a empty string.', [ $root_field_name, $field_value ]);
        if (TRUE === is_null($field_name)) {
            if (TRUE === $ensure_dict) {
                // Kit::ensureDict($field_value); // @CAUTION
                Kit::ensureArray($field_value);
            }
            return $this->set($root_field_name, $field_value);
        } else {
            $root_field_value = $this->get($root_field_name);
            $root_field_value[$field_name] = $field_value;
            return $this->set($root_field_name, $root_field_value);
        }
    }

    final private function hasDocument($root_field_name, $field_name)
    {
        if (FALSE === Kit::in($root_field_name, self::$rootFieldNameList))
            throw new UserException('Invalid $root_field_name.', $root_field_name);
        Kit::ensureString($field_name);
        $root_field_value = $this->get($root_field_name);
        return TRUE === isset($root_field_value[$field_name]);
    }

    final private function getDocument($root_field_name, $field_name, $ensure_existence = TRUE, $default = NULL)
    {
        if (FALSE === Kit::in($root_field_name, self::$rootFieldNameList))
            throw new UserException('Invalid $root_field_name.', $root_field_name);
        Kit::ensureString($field_name, TRUE);
        $root_field_value = $this->get($root_field_name);
        if (TRUE === is_null($field_name)) return $root_field_value;
        if (FALSE === isset($root_field_value[$field_name])) {
            if (TRUE === $ensure_existence) {
                $msg = "Field($field_name) does not exist in root field($root_field_name).";
                throw new UserException($msg, $root_field_value);
            } else return $default;
        } else return $root_field_value[$field_name];
    }

    final private function set($path, $value, $ensure_existence = NULL)
    {
        $this->ensureNotReadOnly();
        Kit::ensureString($path);
        Kit::ensureBoolean($ensure_existence, TRUE);
        if (TRUE === $ensure_existence) $this->ensureHas($path);
        if (FALSE === $ensure_existence) $this->ensureHasNo($path);
        $this->document[$path] = $value;
        $this->notSameAsCollection();
        return $value;
    }

    final private function get($path, $ensure_existence = TRUE, $default = NULL)
    {
        Kit::ensureString($path);
        Kit::ensureBoolean($ensure_existence);
        if (TRUE === $ensure_existence) $this->ensureHas($path);
        if (FALSE === $ensure_existence AND TRUE === is_null($this->document[$path]))
            return $default;
        return $this->document[$path];
    }

    final private function delete($path, $ensure_existence = TRUE)
    {
        $this->ensureNotReadOnly();
        Kit::ensureString($path);
        Kit::ensureBoolean($ensure_existence);
        if (TRUE === $ensure_existence) $this->ensureHas($path);
        if (FALSE === $ensure_existence AND TRUE === is_null($this->document[$path]))
            return NULL;
        $value = $this->document[$path];
        unset($this->document[$path]);
        $this->notSameAsCollection();
        return $value;
    }

    final private function has($path)
    {
        Kit::ensureString($path);
        return FALSE === is_null($this->document[$path]);
    }

    final private function hasNo($path)
    {
        return FALSE === $this->has($path);
    }

    final private function ensureHas($path)
    {
        if (FALSE === $this->has($path))
            throw new UserException("\$path($path) does not exist.", $this->document);
        return $this;
    }

    final private function ensureHasNo($path)
    {
        if (FALSE === $this->hasNo($path))
            throw new UserException("\$path($path) does exist.", $this->document);
        return $this;
    }
}