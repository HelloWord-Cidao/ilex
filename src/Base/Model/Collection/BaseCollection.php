<?php

namespace Ilex\Base\Model\Collection;

use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseCollection
 * Base class of collection models of Ilex.
 * @package Ilex\Base\Model\Collection
 */
abstract class BaseCollection extends BaseModel
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            'checkExists_id',
            'checkExistsSignature',
        ],
        self::V_PROTECTED => [
            'countAll',
            // 'getTheOnlyOneIdBySignature',
            // 'getTheOnlyOneId',
            // 'getTheOnlyOneField',
            // 'addOneWithTypeAndSignatureThenGetId',
            // 'addOneThenGetId',
            // 'updateOneWithAddToSetById',
            // 'getTheOnlyOneContent',
            // 'getTheOnlyOneData',
            // 'getTheOnlyOneInfo',
            // 'getTheOnlyOneMeta',
        ],
    ];

    private $hasIncludedEntity = FALSE;
    private $entityName        = NULL;
    private $entityClassName   = NULL;

    private $collection = NULL;

    final public function __construct()
    {
        $collection_name = static::COLLECTION_NAME;
        Kit::ensureString($collection_name, TRUE);
        if (TRUE === is_null($collection_name)) {
            // throw new UserException('COLLECTION_NAME is not set.');
        } else $this->collection = MongoDBCollection::getInstance($collection_name);
    }

    final protected function ensureInitialized()
    {
        if (FALSE === isset($this->collection))
            throw new UserException('This collection has not been initialized.');
    }

    final protected function createEntity()
    {
        $this->call('includeEntity');
        return new $this->entityClassName($this->collection, $this->entityName, FALSE);
    }

    final protected function createEntityWithDocument($document)
    {
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        $this->call('includeEntity');
        return new $this->entityClassName($this->collection, $this->entityName, TRUE, $document);
    }

    final protected function includeEntity()
    {
        if (TRUE === is_null(static::ENTITY_PATH)) {
            throw new UserException('ENTITY_PATH is not set.', static::COLLECTION_NAME);
        }
        if (FALSE === $this->hasIncludedEntity) {
            $entity_path = static::ENTITY_PATH;
            $this->entityName        = Loader::getHandlerFromPath($entity_path);
            $this->entityClassName   = Loader::includeEntity($entity_path);
            $this->hasIncludedEntity = TRUE;
        }
    }

    final protected function checkExists_id($_id)
    {
        return $this->call('checkExistsField', '_id', $_id);
    }

    final protected function checkExistsSignature($signature)
    {
        return $this->call('checkExistsField', 'Signature', $signature);
    }

    final protected function checkExistsField($path_of_field, $field_value)
    {
        $criterion = [
            $path_of_field => $field_value,
        ];
        return $this->collection->checkExistence($criterion);
    }

    final protected function countAll()
    {
        return $this->collection->count();
    }

    // final protected function getTheOnlyOneIdBySignature($signature)
    // {
    //     return $this->call('getTheOnlyOneIdByField', 'Signature', $signature);
    // }

    // final protected function getTheOnlyOneIdByField($path_of_field, $field_value)
    // {
    //     $criterion = [
    //         $path_of_field => $field_value,
    //     ];
    //     return $this->call('getTheOnlyOneId', $criterion);
    // }

    // final protected function getTheOnlyOneId($criterion)
    // {
    //     return $this->call('getTheOnlyOneField', $criterion, '_id');
    // }

    // final protected function getTheOnlyOneField($criterion, $path_of_field)
    // {
    //     $projection = [
    //         $path_of_field => 1,
    //     ];
    //     $document = $this->call('getTheOnlyOne', $criterion, $projection);
    //     $field_value = $document;
    //     foreach (Kit::split('.', $path_of_field) as $key) {
    //         if (FALSE === isset($field_value[$key]))
    //             throw new UserException("Can not find field with path($path_of_field).", $document);
    //         $field_value = $field_value[$key];
    //     }
    //     return $field_value;
    // }
    
    // final protected function getTheOnlyOneContent($criterion)
    // {
    //     $document = $this->call('getTheOnlyOne', $criterion);
    //     return $document['Content'];
    // }

    // final protected function getTheOnlyOneData($criterion)
    // {
    //     $document = $this->call('getTheOnlyOne', $criterion);
    //     return $document['Content']['Data'];
    // }

    // final protected function getTheOnlyOneInfo($criterion)
    // {
    //     $document = $this->call('getTheOnlyOne', $criterion);
    //     return $document['Content']['Info'];
    // }

    // final protected function getTheOnlyOneMeta($criterion)
    // {
    //     $document = $this->call('getTheOnlyOne', $criterion);
    //     return $document['Meta'];
    // }
    
}