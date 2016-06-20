<?php

namespace Ilex\Base\Model\Collection;

use \MongoDate;
use \Ilex\Base\Model\Collection\MongoDBCollection;

/**
 * Class BaseCollection
 * Base class of collection models of Ilex.
 * @package Ilex\Base\Model\Collection
 */
abstract class BaseCollection extends MongoDBCollection
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
        return $this->call('checkExistence', $criterion);
    }

    final protected function countAll()
    {
        return $this->call('count');
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

    // final protected function addOneWithTypeAndSignatureThenGetId($type, $signature, $content, $meta = [])
    // {
    //     $meta['Type'] = $type;
    //     return $this->call('addOneWithSignatureThenGetId', $signature, $content, $meta);
    // }

    // final protected function addOneWithSignatureThenGetId($signature, $content, $meta = [])
    // {

    // }

    // final protected function addOneThenGetId($document)
    // {
    //     return $this->call('addOne', $document)['_id'];
    // }

    // final protected function updateOneWithAddToSetById($_id, $path_of_set, $element)
    // {
    //     $criterion = [
    //         '_id' => $_id,
    //     ];
    //     $update = [
    //         '$addToSet' => [
    //             $path_of_set => $element,
    //         ],
    //     ];
    //     return $this->call('updateOne', $criterion, $update);
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