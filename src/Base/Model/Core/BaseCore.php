<?php

namespace Ilex\Base\Model\Core;

use \Ilex\Core\Context;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class BaseCore
 * Base class of core models of Ilex.
 * @package Ilex\Base\Model\Core
 */
abstract class BaseCore
{
    const S_OK = 'ok';
    
    protected $ok = [ self::S_OK => TRUE ];

    private $queryClassName  = NULL;
    private $entityClassName = NULL;

    final private function includeQuery()
    {
        $this->queryClassName = Kit::ensureString(Loader::includeQuery(Kit::ensureString(static::ENTITY_PATH)));
    }

    final public function createQuery()
    {
        if (TRUE === is_null($this->queryClassName))
            $this->includeQuery();
        $query_class_name = $this->queryClassName;
        return new $query_class_name(Kit::ensureString(static::COLLECTION_NAME, TRUE),
            Kit::ensureString(static::ENTITY_PATH));
    }

    final private function includeEntity()
    {
        $this->entityClassName = Kit::ensureString(Loader::includeEntity(Kit::ensureString(static::ENTITY_PATH)));
    }

    final public function createEntity()
    {
        if (TRUE === is_null($this->entityClassName))
            $this->includeEntity();
        $entity_class_name = $this->entityClassName;
        return new $entity_class_name(Kit::ensureString(static::COLLECTION_NAME, TRUE),
            Kit::ensureString(static::ENTITY_PATH), FALSE);
    }

    // ====================================================================================

    final protected function ensureLogin()
    {
        $user_type_list = func_get_args();
        if (TRUE === Kit::in('Administrator', $user_type_list)) return; // @TODO: CAUTION
        if (FALSE === Context::isLogin($user_type_list))
            throw new UserException('Login failed.');
    }

    final protected function loadCore($path)
    {
        $handler_name = Loader::getHandlerFromPath($path) . 'Core';
        return ($this->$handler_name = Loader::loadCore($path));
    }

    // ====================================================================================

    final public function checkExistsId($id)
    {
        return $this->createQuery()->idIs($id)->checkExistsOnlyOneEntity();
    }

    final public function checkExistsSignature($signature) // @TODO: move to HelloWord?
    {
        return $this->createQuery()->signatureIs($signature)->checkExistsOnlyOneEntity();
    }

    final public function countAll()
    {
        return $this->createQuery()->all()->countEntities();
    }
    
    final public function getAllEntities()
    {
        return $this->createQuery()->all()->getMultiEntities();
    }

    final public function getTheOnlyOneEntityById($id)
    {
        return $this->createQuery()->idIs($id)->getTheOnlyOneEntity();
    }

    final public function getTheOnlyOneEntityByNo($no)
    {
        return $this->createQuery()->noIs($no)->getTheOnlyOneEntity();
    }

    // If $id_list is empty, returns empty bulk.
    final public function getAllEntitiesByIdList($id_list)
    {
        return $this->createQuery()->idIn($id_list)->getMultiEntities();
    }

    final public function getTheOnlyOneEntityBySignature($signature)
    {
        return $this->createQuery()->signatureIs($signature)->getTheOnlyOneEntity();
    }
    
    final public function getAllEntitiesByMultiReference(BaseEntity $entity, $reference_name = NULL)
    {
        return $this->createQuery()->hasMultiReferenceTo($entity, $reference_name)->getMultiEntities();
    }

    final public function getAllEntitiesByOneReference(BaseEntity $entity, $reference_name = NULL)
    {
        return $this->createQuery()->hasOneReferenceTo($entity, $reference_name)->getMultiEntities();
    }

    final public function getAllEntitiesByType($type)
    {
        return $this->createQuery()->typeIs($type)->getMultiEntities();
    }
}