<?php

namespace Ilex\Base\Model\Wrapper;

use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Collection\MongoDBCollection;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class EntityWrapper
 * @package Ilex\Base\Model\Wrapper
 */
final class EntityWrapper extends MongoDBCollection
{
    // protected static $methodsVisibility = [
    //     self::V_PUBLIC => [
    //         'addOneEntity',
    //         'updateTheOnlyOneEntity',
    //     ],
    //     self::V_PROTECTED => [
    //     ],
    // ];

    private static $entityWrapperContainer = NULL;

    final public static function getInstance($collection_name, $entity_class_name)
    {
        Kit::ensureString($entity_class_name);
        if (FALSE === isset(self::$entityWrapperContainer))
            self::$entityWrapperContainer = new Container();
        if (TRUE === self::$entityWrapperContainer->has($entity_class_name)) 
            return self::$entityWrapperContainer->get($entity_class_name);
        else return (self::$entityWrapperContainer->set($entity_class_name, new static($collection_name)));
    }

    final protected function __construct($collection_name)
    {
        parent::__construct($collection_name);
    }

    final public function addOneEntity(BaseEntity $entity)
    {
        $document = $entity->document();
        return $this->addOne($document)['document'];
    }

    final public function updateTheOnlyOneEntity(BaseEntity $entity)
    {
        $_id = $entity->getId();
        $criterion = [ '_id' => $_id ];
        $document = $entity->document();
        unset($document['_id']);
        $document = $this->updateTheOnlyOne($criterion, $document);
        $document['_id'] = $_id;
        return $document;
    }
    
}