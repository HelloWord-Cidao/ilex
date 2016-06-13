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
        self::V_PROTECTED => [
            'countAll',
            'getOneId',
            'getOneContent',
            'getOneData',
            'getOneInfo',
            'getOneMeta',
            'getTheOnlyOneId',
            'getTheOnlyOneContent',
            'getTheOnlyOneData',
            'getTheOnlyOneInfo',
            'getTheOnlyOneMeta',
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->loadModel('Config/BaseConfig');
        $this->loadModel('Data/BaseData');
    }


    final protected function countAll()
    {
        return $this->call('count');
    }

    final protected function getOneId($criterion)
    {
        $document = $this->call('getOne', $criterion);
        return $document['_id'];
    }
    
    final protected function getOneContent($criterion)
    {
        $document = $this->call('getOne', $criterion);
        return $document['Content'];
    }

    final protected function getOneData($criterion)
    {
        $document = $this->call('getOne', $criterion);
        return $document['Data'];
    }

    final protected function getOneInfo($criterion)
    {
        $document = $this->call('getOne', $criterion);
        return $document['Info'];
    }

    final protected function getOneMeta($criterion)
    {
        $document = $this->call('getOne', $criterion);
        return $document['Meta'];
    }

    final protected function getTheOnlyOneId($criterion)
    {
        $document = $this->call('getTheOnlyOne', $criterion);
        return $document['_id'];
    }
    
    final protected function getTheOnlyOneContent($criterion)
    {
        $document = $this->call('getTheOnlyOne', $criterion);
        return $document['Content'];
    }

    final protected function getTheOnlyOneData($criterion)
    {
        $document = $this->call('getTheOnlyOne', $criterion);
        return $document['Data'];
    }

    final protected function getTheOnlyOneInfo($criterion)
    {
        $document = $this->call('getTheOnlyOne', $criterion);
        return $document['Info'];
    }

    final protected function getTheOnlyOneMeta($criterion)
    {
        $document = $this->call('getTheOnlyOne', $criterion);
        return $document['Meta'];
    }
    
}