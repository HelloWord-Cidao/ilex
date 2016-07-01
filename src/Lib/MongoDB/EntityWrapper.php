<?php

namespace Ilex\Lib\MongoDB;

use \MongoId;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class EntityWrapper
 * @package Ilex\Lib\MongoDB
 */
final class EntityWrapper extends MongoDBCollection
{

    private static $entityWrapperContainer = NULL;

    final public static function getInstance($collection_name, $entity_path)
    {
        Kit::ensureString($entity_path);
        if (FALSE === isset(self::$entityWrapperContainer))
            self::$entityWrapperContainer = new Container();
        if (TRUE === self::$entityWrapperContainer->has($entity_path)) 
            return self::$entityWrapperContainer->get($entity_path);
        else return (self::$entityWrapperContainer->set($entity_path, new static($collection_name)));
    }

    final protected function __construct($collection_name)
    {
        parent::__construct($collection_name);
    }

    final public function addOneEntity(BaseEntity $entity)
    {
        $document = $entity->document();
        $document = $this->addOne($document)['document'];
        if (FALSE === isset($document['_id']) OR FALSE === $document['_id'] instanceof MongoId)
            throw new UserException('_id is not set or proper in $document.', $document);
        $document['_id'] = new MongoDBId($document['_id']);
        return $document;
    }

    final public function updateTheOnlyOneEntity(BaseEntity $entity)
    {
        $id = $entity->getId();
        if (FALSE === $id instanceof MongoDBId)
            throw new UserException('$id is not proper in $entity.', $entity);
        $criterion = [ '_id' => $id->toMongoId() ];
        $document = $entity->document();
        unset($document['_id']);
        $document = $this->updateTheOnlyOne($criterion, $document);
        $document['_id'] = $id;
        return $document;
    }
    
}