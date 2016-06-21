<?php

namespace Ilex\Base\Model\Entity;

use \MongoId;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\BaseModel;
use \Ilex\Base\Model\Wrapper\EntityWrapper;

/**
 * Class BaseEntity
 * Base class of entity models of Ilex.
 * @package Ilex\Base\Model\Entity
 */
abstract class BaseEntity extends BaseModel
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            'getName',
            'document',
            'setSignature',
            'setData',
            'setInfo',
            'buildReference',
            'addToCollection',
            'updateToCollection',
        ],
        self::V_PROTECTED => [
            'isInCollection',
            'isSameAsInCollection',
            'getId',
            'getDocument',
            'getSignature',
            'getData',
            'getInfo',
            'setMeta',
            'getMeta',
        ],
    ];

    private static $rootFieldNameList = [
        'Data',
        'Info',
        'Signature',
        'Reference',
        'Meta',
    ];

    private $entityWrapper = NULL;
    private $name          = NULL;

    private $isInCollection    = FALSE;
    private $isSameAsCollecton = FALSE;

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
        $this->entityWrapper     = $entity_wrapper;
        $this->name              = $name;
        $this->isInCollection    = $is_in_collection;
        $this->isSameAsCollecton = $is_in_collection;
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

    final protected function getName()
    {
        return $this->name;
    }

    final protected function ensureInitialized()
    {
        if (FALSE === isset($this->entityWrapper)
            OR FALSE === $this->entityWrapper instanceof EntityWrapper)
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

    final private function inCollection()
    {
        $this->isInCollecton     = TRUE;
        $this->isSameAsCollecton = TRUE;
    }

    final private function notInCollection()
    {
        $this->isInCollecton     = FALSE;
        $this->isSameAsCollecton = FALSE;
    }

    final private function sameAsCollection()
    {
        $this->isSameAsCollecton = TRUE;
    }

    final private function notSameAsCollection()
    {
        $this->isSameAsCollecton = FALSE;
    }

    final protected function buildReference(BaseEntity $entity, $check_duplicate = FALSE)
    {
        Kit::ensureBoolean($check_duplicate);
        $field_name = $entity->getName() . 'IdList';
        $entity_id  = $entity->getId();
        $field_value = $this->call('getDocument', 'Reference', FALSE, []);
        if (TRUE === $check_duplicate) {
            foreach ($field_value as $id) {
                if (strval($id) === strval($entity_id)) return FALSE;
            }
        }
        $field_value[] = $entity_id;
        $this->setDocument('Reference', $field_name, $field_value);
        return TRUE;
    }

    final protected function addToCollection()
    {
        if (TRUE === $this->call('isInCollection')) {
            $msg = 'Can not add to collection, because the entity is already in the collection.';
            throw new UserException($msg, $this);
        }
        $_id = $this->entityWrapper->addOneEntityThenGetId($this);
        $this->setId($_id);
        $this->inCollection();
        return $_id;
    }

    final protected function updateToCollection()
    {
        if (FALSE === $this->call('isInCollection')) {
            $msg = 'Can not update to collection, because the entity is not in the collection.';
            throw new UserException($msg, $this);
        }
        $this->entityWrapper->updateTheOnlyOneEntity($this);
        $this->sameAsCollection();
    }

    final protected function document()
    {
        Kit::ensureDict($this->document); // @CAUTION
        return $this->document;
    }

    final private function setId($_id)
    {
        if (FALSE === $_id instanceof MongoId)
            throw new UserException('$_id is not a MongoId.', [ $_id, $this ]);
        return $this->set('_id', $_id, FALSE);
    }

    final private function deleteId()
    {
        return $this->delete('_id');
    }

    final protected function hasId()
    {
        return $this->call('has', '_id');
    }

    final protected function getId()
    {
        return $this->call('get', '_id');
    }

    final protected function setSignature($signature)
    {
        $this->call('ensureHasNo', 'Signature');
        $this->setDocument('Signature', NULL, $signature, FALSE);
        return $this;
    }
    
    final protected function getSignature()
    {
        return $this->call('getDocument', 'Signature', NULL);
    }
    
    final protected function setData($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        $this->handleSet('Data', $arg1, $arg2);
        return $this;
    }

    final protected function getData($name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->call('handleGet', 'Data', $name, $ensure_existence, $default);
    }

    final protected function setInfo($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        $this->handleSet('Info', $arg1, $arg2);
        return $this;
    }

    final protected function getInfo($name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->call('handleGet', 'Info', $name, $ensure_existence, $default);
    }
    
    final protected function setMeta($arg1 = NULL, $arg2 = Kit::TYPE_VACANCY)
    {
        $this->handleSet('Meta', $arg1, $arg2);
        return $this;
    }

    final protected function getMeta($name = NULL, $ensure_existence = TRUE, $default = NULL)
    {
        return $this->call('handleGet', 'Meta', $name, $ensure_existence, $default);
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

    final protected function handleGet($root_field_name, $field_name, $ensure_existence, $default)
    {
        if (TRUE === is_null($name)) return $this->call('getDocument', $root_field_name);
        else return $this->call('getDocument', $root_field_name, $field_name, $ensure_existence, $default);
    }

    final private function setDocument($root_field_name, $field_name, $field_value, $ensure_dict = TRUE)
    {
        if (FALSE === Kit::in($root_field_name, self::$rootFieldNameList))
            throw new UserException('Invalid $root_field_name.', $root_field_name);
        Kit::ensureString($field_name, TRUE);
        if ('' === $field_name)
            throw new UserException('$field_name is a empty string.', [ $root_field_name, $field_value ]);
        if (TRUE === is_null($field_name)) {
            if (TRUE === $ensure_dict) Kit::ensureDict($field_value); // @CAUTION
            return $this->set($root_field_name, $field_value);
        } else {
            $root_field_value = $this->call('get', $root_field_name);
            $root_field_value[$field_name] = $field_value;
            return $this->set($root_field_name, $root_field_value);
        }
    }

    final protected function getDocument($root_field_name, $field_name, $ensure_existence = TRUE, $default = NULL)
    {
        if (FALSE === Kit::in($root_field_name, self::$rootFieldNameList))
            throw new UserException('Invalid $root_field_name.', $root_field_name);
        Kit::ensureString($field_name, TRUE);
        $root_field_value = $this->call('get', $root_field_name);
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
        Kit::ensureArray($this->document);
        if (TRUE === Kit::isString($path)) {
            if (TRUE === $ensure_existence) $this->call('ensureHas', $path);
            if (FALSE === $ensure_existence) $this->call('ensureHasNo', $path);
            $this->document[$path] = $value;
            $this->notSameAsCollection();
            return $value;
        } else throw new UserException('Can not support list-type $path yet.', [ $path, $value ]);
    }

    final protected function get($path, $ensure_existence = TRUE, $default = NULL)
    {
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_LIST ]); // @CAUTION
        // Kit::ensureType($path, [ Kit::TYPE_STRING, Kit::TYPE_ARRAY ]);
        Kit::ensureString($path);
        // Kit::ensureDict($this->document); // @CAUTION
        Kit::ensureArray($this->document);
        Kit::ensureBoolean($ensure_existence);
        if (TRUE === Kit::isString($path)) {
            if (TRUE === $ensure_existence) $this->call('ensureHas', $path);
            if (FALSE === $ensure_existence AND TRUE === is_null($this->document[$path]))
                return $default;
            return $this->document[$path];
        } else throw new UserException('Can not support list-type $path yet.', $path);
    }

    final private function delete($path, $ensure_existence = TRUE)
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
            $this->notSameAsCollection();
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
}