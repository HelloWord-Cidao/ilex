<?php

namespace Ilex\Base\Model\Entity;

use \MongoId;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
// use \Ilex\Base\Model\BaseModel;
use \Ilex\Base\Model\Collection\MongoDBCollection;
use \Ilex\Base\Model\Wrapper\EntityWrapper;

/**
 * Class BaseEntity
 * Base class of entity models of Ilex.
 * @package Ilex\Base\Model\Entity
 */
abstract class BaseEntity// extends BaseModel
{
    // protected static $methodsVisibility = [
    //     self::V_PUBLIC => [
    //         'getName',
    //         'isInCollection',
    //         'document',
    //         'getId',
    //         'setSignature',
    //         'setData',
    //         'setInfo',
    //         'buildReference',
    //         'addToCollection',
    //         'updateToCollection',
    //     ],
    //     self::V_PROTECTED => [
    //         'isSameAsCollection',
    //         'getDocument',
    //         'getSignature',
    //         'getData',
    //         'getInfo',
    //         'setMeta',
    //         'getMeta',
    //     ],
    // ];

    private static $rootFieldNameList = [
        'Data',
        'Info',
        'Signature',
        'Reference',
        'Meta',
    ];

    private $entityWrapper = NULL;
    protected $name          = NULL;

    protected $isInCollection     = FALSE;
    protected $isSameAsCollection = FALSE; // @TODO private it

    private $document = NULL;

    final public function __construct($entity_wrapper, $name, $is_in_collection, $document = NULL)
    {
        Kit::ensureObject($entity_wrapper);
        Kit::ensureString($name);
        Kit::ensureBoolean($is_in_collection);
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document, TRUE);
        if (TRUE === $is_in_collection AND
            (FALSE === isset($document['_id']) 
                OR FALSE === ($document['_id'] instanceof MongoId)))
            throw new UserException('_id is not set or is not a MongoId.', $this);
        $this->entityWrapper      = $entity_wrapper;
        $this->name               = $name;
        $this->isInCollection     = $is_in_collection;
        $this->isSameAsCollection = $is_in_collection;
        if (FALSE === is_null($document))
            $this->document = $document;
        else $this->document = [
            'Data'      => [ ],
            'Info'      => [ ],
            'Reference' => [ ],
            'Meta'      => [
                'Type' => $name,
            ],
        ];
    }

    final public function getName()
    {
        return $this->name;
    }

    final public function ensureInitialized()
    {
        if (FALSE === isset($this->entityWrapper)
            OR FALSE === $this->entityWrapper instanceof EntityWrapper)
            throw new UserException('This entity has not been initialized.', $this);
        return $this;
    }

    final public function checkIsInCollection()
    {
        return $this->isInCollection;
    }

    final public function ensureInCollection()
    {
        if (FALSE === $this->isInCollection)
            throw new UserException('This entity is not in collection.', $this);
        return $this;
    }

    final public function checkIsSameAsCollection()
    {
        return $this->isSameAsCollection;
    }

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

    final public function buildReference(BaseEntity $entity, $check_duplicate = TRUE)
    {
        Kit::ensureBoolean($check_duplicate);
        $field_name = $entity->getName() . 'IdList';
        $entity_id  = $entity->getId();
        $field_value = $this->getDocument('Reference', $field_name, FALSE, []);
        if (TRUE === $check_duplicate) {
            foreach ($field_value as $id) {
                if (strval($id) === strval($entity_id))
                    // return FALSE;
                    return $this;
            }
        }
        $field_value[] = $entity_id;
        $this->setDocument('Reference', $field_name, $field_value);
        // return TRUE;
        return $this;
    }

    final public function addToCollection()
    {
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

    final public function document()
    {
        // Kit::ensureDict($this->document); // @CAUTION
        return $this->document;
    }

    final public function getIdAndInfo($id_to_string = FALSE)
    {
        return [
            'Id'   => $this->getId($id_to_string),
            'Info' => $this->getInfo(),
        ];
    }

    final private function setId($_id)
    {
        if (FALSE === $_id instanceof MongoId)
            throw new UserException('$_id is not a MongoId.', [ $_id, $this ]);
        $this->set('_id', $_id, FALSE);
        return $this;
    }

    final private function deleteId()
    {
        $this->delete('_id');
        return $this;
    }

    final public function hasId()
    {
        return $this->has('_id');
    }

    final public function getId($id_to_string = FALSE)
    {
        $_id = $this->get('_id');
        if (TRUE === $id_to_string)
            $_id = MongoDBCollection::convertMongoIdToString($_id);
        return $_id;
    }

    final public function setSignature($signature)
    {
        $this->ensureHasNo('Signature');
        $this->setDocument('Signature', NULL, $signature, FALSE);
        return $this;
    }
    
    final public function getSignature()
    {
        return $this->getDocument('Signature', NULL);
    }
    
    final public function setData($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        $this->handleSet('Data', $arg1, $arg2);
        return $this;
    }

    final public function getData($name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->handleGet('Data', $name, $ensure_existence, $default);
    }

    final public function setInfo($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        $this->handleSet('Info', $arg1, $arg2);
        return $this;
    }

    final public function getInfo($name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->handleGet('Info', $name, $ensure_existence, $default);
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

    final private function handleSet($root_field_name, $arg1, $arg2)
    {
        if (TRUE === Kit::isVacancy($arg2))
            return $this->setDocument($root_field_name, NULL, $arg1);
        else {
            Kit::ensureString($arg1);
            return $this->setDocument($root_field_name, $arg1, $arg2);
        }
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
                Kit::ensureArray($field_value); // @CAUTION
            }
            return $this->set($root_field_name, $field_value);
        } else {
            $root_field_value = $this->get($root_field_name);
            $root_field_value[$field_name] = $field_value;
            return $this->set($root_field_name, $root_field_value);
        }
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
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_LIST ]); // @CAUTION
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_ARRAY ]);
        Kit::ensureString($path);
        // Kit::ensureDict($this->document); // @CAUTION
        // Kit::ensureArray($this->document);
        // if (TRUE === Kit::isString($path)) {
            if (TRUE === $ensure_existence) $this->ensureHas($path);
            if (FALSE === $ensure_existence) $this->ensureHasNo($path);
            $this->document[$path] = $value;
            $this->notSameAsCollection();
            return $value;
        // } else throw new UserException('Can not support list-type $path yet.', [ $path, $value ]);
    }

    final private function get($path, $ensure_existence = TRUE, $default = NULL)
    {
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_LIST ]); // @CAUTION
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_ARRAY ]);
        Kit::ensureString($path);
        // Kit::ensureDict($this->document); // @CAUTION
        // Kit::ensureArray($this->document);
        Kit::ensureBoolean($ensure_existence);
        // if (TRUE === Kit::isString($path)) {
            if (TRUE === $ensure_existence) $this->ensureHas($path);
            if (FALSE === $ensure_existence AND TRUE === is_null($this->document[$path]))
                return $default;
            return $this->document[$path];
        // } else throw new UserException('Can not support list-type $path yet.', $path);
    }

    final private function delete($path, $ensure_existence = TRUE)
    {
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_LIST ]); // @CAUTION
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_ARRAY ]);
        Kit::ensureString($path);
        // Kit::ensureDict($this->document); // @CAUTION
        // Kit::ensureArray($this->document);
        Kit::ensureBoolean($ensure_existence);
        // if (TRUE === Kit::isString($path)) {
            if (TRUE === $ensure_existence) $this->ensureHas($path);
            if (FALSE === $ensure_existence AND TRUE === is_null($this->document[$path]))
                return NULL;
            $value = $this->document[$path];
            unset($this->document[$path]);
            $this->notSameAsCollection();
            return $value;
        // } else throw new UserException('Can not support list-type $path yet.', $path);
    }

    final private function has($path)
    {
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_LIST ]); // @CAUTION
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_ARRAY ]);
        Kit::ensureString($path);
        // Kit::ensureDict($this->document); // @CAUTION
        // Kit::ensureArray($this->document);
        // if (TRUE === Kit::isString($path)) {
            return FALSE === is_null($this->document[$path]);
        // } else throw new UserException('Can not support list-type $path yet.', $path);
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