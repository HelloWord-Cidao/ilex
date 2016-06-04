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
            // 'getContent',
            // 'getInfo',
            // 'get_id',
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

    final protected function get_id()
    {

    }
    
    final protected function getContent()
    {

    }

    final protected function getData()
    {

    }

    final protected function getInfo()
    {

    }

    final protected function getMeta()
    {

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