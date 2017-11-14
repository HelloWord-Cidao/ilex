<?php

namespace Ilex\Base\Model\Core\Log;

use \Ilex\Core\Context;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Core\BaseCore;

/**
 * Class OperationLogCore
 * @package Ilex\Base\Model\Core\Log
 */
final class OperationLogCore extends BaseCore
{
    const COLLECTION_NAME = 'OperationLog';
    const ENTITY_PATH     = 'Log/OperationLog';

    final public function addOperationLog($operation_type, $operation_context)
    {
        Kit::ensureString($operation_type);
        Kit::ensureArray($operation_context);
        return $this->createEntity()
            ->setOperationType($operation_type)
            ->setOperator(Context::me())
            ->setOperationTime(Kit::now())
            ->setOperationContext($operation_context)
            ->addToCollection();
    }
}