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
            'add',
            'getContent',
            'getInfo',
            'get_id',
        ],
    ];

    final protected function add($content, $meta)
    {
        $meta['CreationTime'] = new MongoDate(time());
        $document = [
            'Content' => $content,
            'Meta'    => $meta,
        ];
        $this->call('add', [ $document ], TRUE);
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
    
}