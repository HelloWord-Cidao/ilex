<?php

namespace Ilex\Base\Model\Wrapper;

use \Ilex\Base\Model\Collection\MongoDBCollection;

/**
 * Class EntityWrapper
 * @package Ilex\Base\Model\Wrapper
 */
final class EntityWrapper extends MongoDBCollection
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            'addOneEntityThenGetId',
            'updateTheOnlyOneEntity',
        ],
        self::V_PROTECTED => [
        ],
    ];

    private static $entityWrapperContainer = NULL;

    final public static function getEntityWrapper($collection_name)
    {
        Kit::ensureString($collection_name);
        if (FALSE === isset(self::$entityWrapperContainer))
            self::$entityWrapperContainer = new Container();
        if (TRUE === self::$entityWrapperContainer->has($collection_name)) 
            return self::$entityWrapperContainer[$collection_name];
        else return (self::$entityWrapperContainer->set($collection_name, new static($collection_name)));
    }

    final private function __construct($collection_name)
    {
        parent::__construct($collection_name);
    }

    final protected function addOneEntityThenGetId($document)
    {
        return $this->call('addOne', $document)['_id'];
    }

    final protected function updateTheOnlyOneEntity($_id, $document)
    {
        $criterion = [ '_id' => $_id ];
        return $this->call('updateTheOnlyOne', $criterion, $document, TRUE);
    }
    
}