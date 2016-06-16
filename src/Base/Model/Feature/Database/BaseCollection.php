<?php

namespace Ilex\Base\Model\Feature\Database;

use \MongoDate;
use \Ilex\Base\Model\Feature\Database\MongoDBCollection;

/**
 * Class BaseCollection
 * Base class of collection feature models of Ilex.
 * @package Ilex\Base\Model\Feature\Database
 */
abstract class BaseCollection extends MongoDBCollection
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            'checkExistsSignature',
        ],
        self::V_PROTECTED => [
            'countAll',
            'getTheOnlyOneIdBySignature',
            'getTheOnlyOneId',
            'getTheOnlyOneField',
            'addOneAndGetId',
            'addToSetById',
            // 'getTheOnlyOneContent',
            // 'getTheOnlyOneData',
            // 'getTheOnlyOneInfo',
            // 'getTheOnlyOneMeta',
        ],
    ];

    final protected function checkExistsSignature($signature)
    {
        $criterion = [
            'Content.Info.Signature' => $signature,
        ];
        return $this->call('checkExistence', $criterion);
    }

    final protected function countAll()
    {
        return $this->call('count');
    }

    final protected function getTheOnlyOneIdBySignature($signature)
    {
        $criterion = [
            'Content.Info.Signature' => $signature,
        ];
        return $this->call('getTheOnlyOneId', $criterion);
    }

    final protected function getTheOnlyOneId($criterion)
    {
        return $this->call('getTheOnlyOneField', $criterion, '_id');
    }

    final protected function getTheOnlyOneField($criterion, $path_of_field)
    {
        $projection = [
            $path_of_field => 1,
        ];
        $document = $this->call('getTheOnlyOne', $criterion, $projection);
        $field_value = $document;
        foreach (explode('.', $path_of_field) as $key) {
            if (FALSE === isset($field_value[$key]))
                throw new UserException("Can not find field with path($path_of_field).", $document);
            $field_value = $field_value[$key];
        }
        return $field_value;
    }

    final protected function addOneAndGetId($content, $meta)
    {
        return $this->call('addOne', $content, $meta)['_id'];
    }

    final protected function addToSetById($_id, $path_of_set, $element)
    {
        $criterion = [
            '_id' => $_id,
        ];
        $update = [
            '$addToSet' => [
                $path_of_set => $element,
            ],
        ];
        return $this->call('updateOne', $criterion, $update);
    }
    
    // final protected function getTheOnlyOneContent($criterion)
    // {
    //     $document = $this->call('getTheOnlyOne', $criterion);
    //     return $document['Content'];
    // }

    // final protected function getTheOnlyOneData($criterion)
    // {
    //     $document = $this->call('getTheOnlyOne', $criterion);
    //     return $document['Data'];
    // }

    // final protected function getTheOnlyOneInfo($criterion)
    // {
    //     $document = $this->call('getTheOnlyOne', $criterion);
    //     return $document['Info'];
    // }

    // final protected function getTheOnlyOneMeta($criterion)
    // {
    //     $document = $this->call('getTheOnlyOne', $criterion);
    //     return $document['Meta'];
    // }
    
}