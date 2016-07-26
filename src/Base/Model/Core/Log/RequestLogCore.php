<?php

namespace Ilex\Base\Model\Core\Log;

use \Ilex\Core\Context;
use \Ilex\Core\Debug;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Core\BaseCore;

/**
 * Class RequestLogCore
 * @package Ilex\Base\Model\Core\Log
 */
final class RequestLogCore extends BaseCore
{
    const COLLECTION_NAME = 'Log';
    const ENTITY_PATH     = 'Log/RequestLog';

    /**
     * @TODO: check efficiency
     */
    final public function addRequestLog($class_name, $method_name, $response, $code)
    {
        Kit::ensureIn($code, [ 0, 1, 2, 3 ]);
        $input = Loader::loadInput()->input();
        $uri = $input[0];
        unset($input[0]);
        unset($input['token']);
        unset($input['error']);
        $request_log = $this->createEntity()
            ->doNotRollback()
            ->setCode($code)
            ->setRequest($uri)
            ->setInput($input)
            ->setHandlerInfo($class_name, $method_name);
        if (TRUE === Kit::in($code, [ 0, 1 ]))
            $request_log->setResponse($response);
        if (TRUE === Context::isLogin([ ]))
            $request_log->setUserInfo();
        $request_log->setOperationInfo(Kit::len(json_encode($response)))->addToCollection();
    }
}