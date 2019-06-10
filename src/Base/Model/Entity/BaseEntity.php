<?php

namespace Ilex\Base\Model\Entity;

use \ReflectionClass;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Lib\MongoDB\MongoDBId;
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
    private $entityWrapper      = NULL;
    private $name               = NULL;
    private $document           = NULL;
    private $isInCollection     = FALSE;
    private $isSameAsCollection = FALSE;
    private $isReadOnly         = FALSE;
    private $canBeRollbacked    = TRUE;

    private static $rootFieldNameList = [
        'Signature',
        'Data',
        'Info',
        'Reference',
        'Meta',
    ];

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

    final public function getCollectionName()
    {
        return $this->collectionName;
    }

    final public function getEntityPath()
    {
        return $this->entityPath;
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

    final public function doNotRollback()
    {
        $this->canBeRollbacked = FALSE;
        return $this;
    }

    final public function canBeRollbacked()
    {
        return $this->canBeRollbacked;
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

    final public function ensureIs(BaseEntity $entity)
    {
        if (FALSE === $this->is($entity))
            throw new UserException('This entity is not same as $entity', [ $this->document(), $entity->document() ]);
        return $this;
    }

    final public function getId($to_string = FALSE, $to_mongo_id = FALSE)
    {
        $id = $this->ensureInCollection()->getPath('_id');
        if (TRUE === $to_string) return $id->toString();
        elseif (TRUE === $to_mongo_id) return $id->toMongoId();
        else return $id;
    }

    final public function isIdEqualTo($id)
    {
        return $this->getId()->isEqualTo($id);
    }


    // ======================================= Signature =============================================
    

    final public function setSignature($signature)
    {
        return $this->ensureHasNo('Signature')->setDocument('Signature', NULL, $signature, FALSE);
    }
    
    final public function getSignature()
    {
        return $this->getDocument('Signature', NULL);
    }

    
    // ======================================= Data =============================================
    

    final public function setData($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        return $this->handleSet('Data', $arg1, $arg2);
    }

    final public function getData($data_name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->handleGet('Data', $data_name, $ensure_existence, $default);
    }


    // ======================================= Info =============================================


    final public function getIdentity($id_to_string = TRUE)
    {
        Kit::ensureBoolean($id_to_string);
        $result = [
            'Name' => $this->getName(),
        ];
        if (FALSE === $id_to_string) {
            return $result + [
                'Id' => $this->getId()->toMongoId(),
            ];
        } else {
            return $result + [
                'Id' => $this->getId(TRUE),
            ];
        }
    }

    final public function setName($name)
    {
        Kit::ensureString($name, TRUE);
        return $this->setInfo('Name', $name);
    }

    final public function getName()
    {
        return $this->getInfo('Name', FALSE, NULL);
    }

    final public function setInfo($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        return $this->handleSet('Info', $arg1, $arg2);
    }

    final public function getInfo($info_name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->handleGet('Info', $info_name, $ensure_existence, $default);
    }


    // ======================================= Reference Multi =============================================
    
    final public function hasMultiReference($reference_name)
    {
        Kit::ensureString($reference_name);
        return $this->handleHas('Reference', $reference_name . 'IdList');
    }

    final public function countMultiReference($reference_name, $ensure_existence = FALSE)
    {
        Kit::ensureString($reference_name);
        Kit::ensureBoolean($ensure_existence);
        return Kit::len($this->getMultiReference($reference_name, FALSE, $ensure_existence));
    }

    // O(N) when $check_duplicate is TRUE
    // @TODO: add $ensure_not_duplicate, raise Exception when duplicate
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
        return $this->setDocument('Reference', $field_name, $field_value);
    }

    final protected function copyMultiReferenceFrom(BaseEntity $entity, $reference_name)
    {
        Kit::ensureString($reference_name);
        if (TRUE === $this->hasMultiReference($reference_name))
            throw new UserException('Can not overwrite existing multi reference.', $this->document());
        $reference = $entity->getMultiReference($reference_name);
        return $this->setDocument('Reference', $reference_name . 'IdList', $reference);
    }

    // O(N)
    final public function deleteMultiReferenceTo(BaseEntity $entity, $reference_name = NULL)
    {
        Kit::ensureString($reference_name, TRUE);
        if (TRUE === is_null($reference_name))
            $field_name  = $entity->getEntityName() . 'IdList';
        else $field_name = $reference_name . 'IdList';
        $entity_id   = $entity->getId();
        $field_value = $this->getDocument('Reference', $field_name);
        foreach ($field_value as $index => $id) {
            if ($entity->getId()->isEqualTo($id)) {
                $reference = array_merge(Kit::slice($field_value, 0, $index), Kit::slice($field_value, $index + 1));
                return $this->setDocument('Reference', $field_name, $reference);
            }
        }
        throw new UserException('$entity id not found in multi reference.', [ $entity->document(), $field_value ]);
    }

    // @TODO: check efficiency
    final public function getEntitiesByMultiReference($reference_name, $entity_path, $ensure_existence = FALSE)
    {
        Kit::ensureString($reference_name);
        Kit::ensureString($entity_path);
        Kit::ensureBoolean($ensure_existence);
        return $this->loadCore($entity_path)
            ->getAllEntitiesByIdList($this->getMultiReference($reference_name, FALSE, $ensure_existence));
    }

    // O(N)
    final public function hasMultiReferenceTo(BaseEntity $entity, $reference_name = NULL, $ensure_existence = FALSE) // @CAUTION
    {
        Kit::ensureString($reference_name, TRUE);
        Kit::ensureBoolean($ensure_existence);
        if (TRUE === is_null($reference_name))
            $reference_name = $entity->getEntityName();
        $reference = $this->getMultiReference($reference_name, FALSE, $ensure_existence); // List
        foreach ($reference as $id) {
            if ($entity->getId()->isEqualTo($id)) return TRUE;
        }
        return FALSE;
    }

    // O(N)
    final public function ensureHasMultiReferenceTo(BaseEntity $entity, $reference_name = NULL)
    {
        Kit::ensureString($reference_name, TRUE);
        if (FALSE === $this->hasMultiReferenceTo($entity, $reference_name)) {
            $msg = 'This entity does not have multi reference to the entity.';
            throw new UserException($msg, [ $entity->getName(), $reference_name ]);
        }
        return $this;
    }

    // O(N)
    final public function ensureNotHasMultiReferenceTo(BaseEntity $entity, $reference_name = NULL)
    {
        Kit::ensureString($reference_name, TRUE);
        if (TRUE === $this->hasMultiReferenceTo($entity, $reference_name)) {
            $msg = 'This entity does have multi reference to the entity.';
            throw new UserException($msg, [ $entity->getName(), $reference_name ]);
        }
        return $this;
    }

    /**
     * O(N) when $to_mongoDB_id is TRUE
     * @param string $reference_name
     * @param boolean $to_mongoDB_id
     * @param boolean $ensure_existence
     * @return List of MongoId or MongoDBId when $to_mongoDB_id is TRUE
     */
    final public function getMultiReference($reference_name, $to_mongoDB_id = FALSE, $ensure_existence = TRUE)
    {
        Kit::ensureString($reference_name);
        Kit::ensureBoolean($to_mongoDB_id);
        Kit::ensureBoolean($ensure_existence);
        $reference = $this->getReference($reference_name . 'IdList', $ensure_existence);
        if (TRUE === is_null($reference)) {
            if (FALSE === $ensure_existence)
                $reference = [ ]; // @CAUTION
            else throw new UserException('Invalid case.');
        }
        Kit::ensureArray($reference);
        if (TRUE === $to_mongoDB_id) {
            $result = [ ];
            foreach ($reference as $id) {
                $result[] = new MongoDBId($id);
            }
            return $result;
        } else return $reference;
    }

    // ======================================= Reference One =============================================
    
    final public function hasOneReference($reference_name)
    {
        Kit::ensureString($reference_name);
        return $this->handleHas('Reference', $reference_name . 'Id');
    }

    final public function buildOneReferenceTo(BaseEntity $entity, $reference_name = NULL, $ensure_no_existence = TRUE)
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
                . ", old value is " . $field_value->__toString() . ".";
            throw new UserException($msg);
        }
        return $this->setDocument('Reference', $field_name, $entity_id->toMongoId());
    }

    final protected function copyOneReferenceFrom(BaseEntity $entity, $reference_name)
    {
        Kit::ensureString($reference_name);
        if (TRUE === $this->hasOneReference($reference_name))
            throw new UserException('Can not overwrite existing one reference.', $this->document());
        $reference = $entity->getOneReference($reference_name)->toMongoId();
        return $this->setDocument('Reference', $reference_name . 'Id', $reference);
    }

    final public function deleteOneReferenceTo(BaseEntity $entity, $reference_name)
    {
        Kit::ensureString($reference_name);
        $field_name  = $reference_name . 'Id';
        $entity_id   = $entity->getId();
        $field_value = $this->getDocument('Reference', $field_name);
        if (FALSE === $entity_id->isEqualTo($field_value))
            throw new UserException('$entity id is not the same as the old reference.',
                [ $entity->document(), $field_value ]);
        $reference = $this->getDocument('Reference');
        unset($reference[$field_name]);
        return $this->setDocument('Reference', NULL, $reference, FALSE);
    }

    final public function getEntityByOneReference($reference_name, $entity_path, $ensure_existence = TRUE)
    {
        Kit::ensureString($reference_name);
        Kit::ensureString($entity_path);
        Kit::ensureBoolean($ensure_existence);
        $reference = $this->getOneReference($reference_name, $ensure_existence);
        if (TRUE === is_null($reference))
            return NULL; // @CAUTION
        else return $this->loadCore($entity_path)->getTheOnlyOneEntityById($reference);
    }

    final public function hasOneReferenceTo(BaseEntity $entity, $reference_name = NULL, $ensure_existence = TRUE)
    {
        Kit::ensureString($reference_name, TRUE);
        Kit::ensureBoolean($ensure_existence);
        if (TRUE === is_null($reference_name))
            $reference_name = $entity->getEntityName();
        $reference = $this->getOneReference($reference_name, $ensure_existence);
        if (TRUE === is_null($reference))
            return FALSE; // @CAUTION
        else return $entity->getId()->isEqualTo($reference);
    }

    final public function ensureHasOneReferenceTo(BaseEntity $entity, $reference_name = NULL)
    {
        Kit::ensureString($reference_name, TRUE);
        if (FALSE === $this->hasOneReferenceTo($entity, $reference_name)) {
            $msg = 'This entity does not have one reference to the entity.';
            throw new UserException($msg, [ $entity->getName(), $reference_name ]);
        }
        return $this;
    }

    final public function ensureNotHasOneReferenceTo(BaseEntity $entity, $reference_name = NULL)
    {
        Kit::ensureString($reference_name, TRUE);
        if (TRUE === $this->hasOneReferenceTo($entity, $reference_name)) {
            $msg = 'This entity does have one reference to the entity.';
            throw new UserException($msg, [ $entity->getName(), $reference_name ]);
        }
        return $this;
    }

    /**
     * @param string $reference_name
     * @return MongoDBId|NULL
     */
    final public function getOneReference($reference_name, $ensure_existence = TRUE)
    {
        Kit::ensureString($reference_name);
        Kit::ensureBoolean($ensure_existence);
        $reference = $this->getReference($reference_name . 'Id', $ensure_existence);
        if (TRUE === is_null($reference)) {
            if (FALSE === $ensure_existence)
                return NULL; // @CAUTION
            else throw new UserException('Invalid case.');
        } else return new MongoDBId($reference);
    }

    // ======================================= Reference Basic =============================================

    final private function getReference($reference_name, $ensure_existence = TRUE, $default = NULL)
    {
        Kit::ensureString($reference_name, TRUE);
        Kit::ensureBoolean($ensure_existence);
        return $this->handleGet('Reference', $reference_name, $ensure_existence, $default);
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
        return Kit::toTimestamp($this->getMeta('CreationTime'));
    }

    final public function getModificationTimestamp()
    {
        if (TRUE === is_null($this->getMeta('ModificationTime', FALSE, NULL))) {
            return 0;
        } else return Kit::toTimestamp($this->getMeta('ModificationTime'));
    }

    final public function remove()
    {
        return $this
            ->setMeta('IsRemoved', TRUE)
            ->setMeta('RemovalTime', Kit::now());
    }

    final public function isRemoved()
    {
        return $this->getMeta('IsRemoved', FALSE, FALSE);
    }
    
    final public function setMeta($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        return $this->handleSet('Meta', $arg1, $arg2);
    }

    final public function getMeta($name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        Kit::ensureString($name, TRUE);
        Kit::ensureBoolean($ensure_existence);
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
        return $this
            ->setId($document['_id'])
            ->setMeta('CreationTime', $document['Meta']['CreationTime'])
            ->inCollection();
    }

    final public function updateToCollection()
    {
        $this->ensureNotReadOnly();
        if (FALSE === $this->isInCollection) {
            $msg = 'Can not update to collection, because the entity is not in the collection.';
            throw new UserException($msg, $this);
        }
        $document = $this->entityWrapper->updateTheOnlyOneEntity($this);
        return $this
            ->setMeta('ModificationTime', $document['Meta']['ModificationTime'])
            ->sameAsCollection();
    }

    final public function removeFromCollection()
    {
        $this->ensureNotReadOnly();
        if (FALSE === $this->isInCollection) {
            $msg = 'Can not remove from collection, because the entity is not in the collection.';
            throw new UserException($msg, $this);
        }
        $status = $this->entityWrapper->removeTheOnlyOneEntity($this);
        $meta = $this->getMeta();
        unset($meta['CreationTime']);
        unset($meta['ModificationTime']);
        return $this
            ->deleteId()
            ->setMeta($meta)
            ->notInCollection();
    }

    // ===================================== Private ===============================================


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
        return $this->setPath('_id', $id, FALSE);
    }

    final private function deleteId()
    {
        return $this->deletePath('_id');
    }

    final private function handleSet($root_field_name, $arg1, $arg2)
    {
        Kit::ensureString($root_field_name);
        if (TRUE === Kit::isVacancy($arg2))
            return $this->setDocument($root_field_name, NULL, $arg1);
        else {
            Kit::ensureString($arg1);
            return $this->setDocument($root_field_name, $arg1, $arg2);
        }
    }

    final private function handleHas($root_field_name, $field_name)
    {
        Kit::ensureString($root_field_name);
        Kit::ensureString($field_name);
        return $this->hasDocument($root_field_name, $field_name);
    }

    final private function handleGet($root_field_name, $field_name, $ensure_existence, $default)
    {
        Kit::ensureString($root_field_name);
        Kit::ensureString($field_name, TRUE);
        Kit::ensureBoolean($ensure_existence);
        if (TRUE === is_null($field_name))
            return $this->getDocument($root_field_name, $field_name);
        else return $this->getDocument($root_field_name, $field_name, $ensure_existence, $default);
    }

    final private function setDocument($root_field_name, $field_name, $field_value, $ensure_dict = TRUE)
    {
        Kit::ensureIn($root_field_name, self::$rootFieldNameList);
        Kit::ensureString($field_name, TRUE);
        Kit::ensureBoolean($ensure_dict);
        if ('' === $field_name)
            throw new UserException('$field_name is a empty string.', [ $root_field_name, $field_value ]);
        if (TRUE === is_null($field_name)) {
            if (TRUE === $ensure_dict) {
                // Kit::ensureDict($field_value); // @CAUTION
                Kit::ensureArray($field_value);
            }
            return $this->setPath($root_field_name, $field_value);
        } else {
            $root_field_value = $this->getPath($root_field_name);
            $root_field_value[$field_name] = $field_value;
            return $this->setPath($root_field_name, $root_field_value);
        }
    }

    final private function hasDocument($root_field_name, $field_name)
    {
        Kit::ensureIn($root_field_name, self::$rootFieldNameList);
        Kit::ensureString($field_name);
        $root_field_value = $this->getPath($root_field_name);
        return TRUE === isset($root_field_value[$field_name]);
    }

    final private function getDocument($root_field_name, $field_name, $ensure_existence = TRUE, $default = NULL)
    {
        Kit::ensureIn($root_field_name, self::$rootFieldNameList);
        Kit::ensureString($field_name, TRUE);
        Kit::ensureBoolean($ensure_existence);
        $root_field_value = $this->getPath($root_field_name);
        if (TRUE === is_null($field_name)) return $root_field_value;
        if (FALSE === isset($root_field_value[$field_name])) {
            if (TRUE === $ensure_existence) {
                $msg = "Field($field_name) does not exist in root field($root_field_name).";
                throw new UserException($msg, $root_field_value);
            } else return $default;
        } else return $root_field_value[$field_name];
    }

    final private function setPath($path, $value, $ensure_existence = NULL)
    {
        $this->ensureNotReadOnly();
        Kit::ensureString($path);
        Kit::ensureBoolean($ensure_existence, TRUE);
        if (TRUE === $ensure_existence) $this->ensureHas($path);
        if (FALSE === $ensure_existence) $this->ensureHasNo($path);
        $this->document[$path] = $value;
        return $this->notSameAsCollection();
    }

    final private function getPath($path, $ensure_existence = TRUE, $default = NULL)
    {
        Kit::ensureString($path);
        Kit::ensureBoolean($ensure_existence);
        if (TRUE === $ensure_existence) $this->ensureHas($path);
        if (FALSE === $ensure_existence AND TRUE === is_null($this->document[$path]))
            return $default;
        return $this->document[$path];
    }

    final private function deletePath($path, $ensure_existence = TRUE)
    {
        $this->ensureNotReadOnly();
        Kit::ensureString($path);
        Kit::ensureBoolean($ensure_existence);
        if (TRUE === $ensure_existence) $this->ensureHas($path);
        if (FALSE === $ensure_existence AND TRUE === is_null($this->document[$path]))
            return NULL;
        unset($this->document[$path]);
        return $this->notSameAsCollection();
    }

    final private function hasPath($path)
    {
        Kit::ensureString($path);
        return FALSE === is_null($this->document[$path]);
    }

    final private function hasNo($path)
    {
        return FALSE === $this->hasPath($path);
    }

    final private function ensureHas($path)
    {
        if (FALSE === $this->hasPath($path))
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