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
            'addOneDocument',
            'countAllDocument',
            'checkExistenceDocument',
            'updateOneDocument',
            'getOneDocumentId',
            'getOneDocument',
            'getOneContent',
            'getOneInfo',
            'getOneData'
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->loadModel('Config/BaseConfig');
        $this->loadModel('Data/BaseData');
    }

    final protected function addOneDocument($content, $meta)
    {
        $meta['CreationTime'] = new MongoDate(time());
        $document = [
            'Content' => $content,
            'Meta'    => $meta,
        ];
        return $this->call('add', [ $document ]);
    }

    final protected function countAllDocument()
    {
        return $this->call('count', [ [], NULL, NULL ]);
    }

    final protected function getOneDocumentId($criterion)
    {
        $document = $this->call('getOne', [ $criterion, ['_id'] ]);
        return $document['_id'];
    }

    final protected function getOneDocument($criterion)
    {
        return $this->call('getOne', [ $criterion, [] ]);
    }
    
    final protected function getOneContent($criterion)
    {
        $document = $this->call('getOne', [ $criterion, [] ]);
        return $document['Content'];
    }

    final protected function getOneData($criterion)
    {
        $document = $this->call('getOne', [ $criterion, [] ]);
        return $document['Data'];
    }

    final protected function getOneInfo($criterion)
    {
        $document = $this->call('getOne', [ $criterion, [] ]);
        return $document['Info'];
    }

    final protected function getOneMeta($criterion)
    {
        $document = $this->call('getOne', [ $criterion, [] ]);
        return $document['Meta'];
    }

    final protected function checkExistenceDocument($criterion)
    {
        return $this->call('checkExistence', [ $criterion ]);
    }

    final protected function updateOneDocument($criterion, $update)
    {
        return $this->call('update', [ $criterion, $update ]);
    }
    
}