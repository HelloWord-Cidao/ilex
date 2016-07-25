<?php

namespace Ilex\Base\Model\Core\Log;

use \Ilex\Core\Context;
use \Ilex\Core\Debug;
use \Ilex\Base\Model\Core\BaseCore;

/**
 * Class LogCore
 * @package Ilex\Base\Model\Core\Log
 */
final class LogCore extends BaseCore
{
    const COLLECTION_NAME = 'User';
    const ENTITY_PATH     = 'User/User';

    /**
     * @TODO: check efficiency
     */
    final public function addRequestLog($class_name, $method_name, $response)
    {
        $request_log = $this->createEntity()
            ->setUserInfo()
            ->setHandlerInfo($class_name, $method_name)
            ->setRequest()
            ->setInput()
            ->setResponse($response)
            ->setOperationInfo()
            ->addToCollection();
    }
}