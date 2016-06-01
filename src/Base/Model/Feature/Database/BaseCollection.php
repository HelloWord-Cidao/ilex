<?php

namespace Ilex\Base\Model\Feature\Database;

use \Ilex\Base\Model\Feature\Database\MongoDBCollection;

/**
 * Class BaseCollection
 * Base class of collection feature models of Ilex.
 * @package Ilex\Base\Model\Feature\Database
 */
abstract class BaseCollection extends MongoDBCollection
{
    const METHODS_VISIBILITY = [
        self::V_PROTECTED => [
            'getContent',
            'getInfo',
            'get_id',
        ],
    ];

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