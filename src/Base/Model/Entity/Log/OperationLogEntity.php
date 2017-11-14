<?php

namespace Ilex\Base\Model\Entity\Log;

use \Ilex\Core\Context;
use \Ilex\Core\Debug;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Entity\BaseEntity;

\Ilex\Core\Loader::includeEntity('User/User');
use \HelloWord\Model\Entity\User\UserEntity;

/**
 * Class OperationLogEntity
 * @package Ilex\Base\Model\Entity\Log
 */
final class OperationLogEntity extends BaseEntity
{
    final public function setOperationType($operation_type)
    {
        return $this->setData('OperationType', $operation_type);
    }

    final public function setOperator(UserEntity $operator)
    {
        return $this
            ->setData('Operator', $operator->getAbstract())
            ->buildOneReferenceTo($operator, 'Operator');
    }

    final public function setOperationTime(\MongoDate $time)
    {
        return $this->setData('OperationTime', $time);
    }

    final public function setOperationContext($operation_context)
    {
        Kit::ensureArray($operation_context);
        return $this
            ->setData('OperationContext', $operation_context);
    }
}