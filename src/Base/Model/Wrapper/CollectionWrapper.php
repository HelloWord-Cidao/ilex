<?php

namespace Ilex\Base\Model\Wrapper;

use \Ilex\Base\Model\Collection\MongoDBCollection;

/**
 * Class CollectionWrapper
 * @package Ilex\Base\Model\Wrapper
 */
final class CollectionWrapper extends MongoDBCollection
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
        ],
        self::V_PROTECTED => [
        ],
    ];

    private static $collectionWrapperContainer = NULL;

    final public static function getInstance($collection_name)
    {
        Kit::ensureString($collection_name);
        if (FALSE === isset(self::$collectionWrapperContainer))
            self::$collectionWrapperContainer = new Container();
        if (TRUE === self::$collectionWrapperContainer->has($collection_name)) 
            return self::$collectionWrapperContainer[$collection_name];
        else return (self::$collectionWrapperContainer->set($collection_name, new static($collection_name)));
    }

    final private function __construct($collection_name)
    {
        parent::__construct($collection_name);
    }

    'checkExistence',
    'ensureExistence',
    'checkExistsOnlyOnce',
    'ensureExistsOnlyOnce',

    final private function __construct($collection_name)
    {
        parent::__construct($collection_name);
    }
    
    'count',
    'getMulti',
    'getOne',
    'getTheOnlyOne',
    
}