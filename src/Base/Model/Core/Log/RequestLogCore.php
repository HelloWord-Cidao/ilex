<?php

namespace Ilex\Base\Model\Core\Log;

use \Ilex\Core\Context;
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
        return $this->ok;
        $request_log = $this->createEntity()
            ->doNotRollback()
            ->setCode(Kit::ensureIn($code, [ 0, 1, 2, 3 ]))
            ->setRequest(self::generateRequestInfo())
            ->setInput()
            ->setHandlerInfo($class_name, $method_name);
        if (TRUE === Kit::in($code, [ 0, 1 ]))
            $request_log->setResponse($response);
        if (TRUE === Context::isLogin([ ]))
            $request_log->setUserInfo();
        $request_log->setOperationInfo(Kit::len(json_encode($response)))->addToCollection();
    }

    final public static function generateRequestInfo()
    {
        return [
            'RequestMethod'    => $_SERVER['REQUEST_METHOD'],
            'RequestURI'       => Kit::ensureString(Loader::loadInput()->uri()),
            'RequestTimestamp' => $_SERVER['REQUEST_TIME'],
            'RequestTime'      => Kit::fromTimestamp($_SERVER['REQUEST_TIME']),
            'RemoteIP'         => $_SERVER['REMOTE_ADDR'],
            'RemotePort'       => $_SERVER['REMOTE_PORT'],
            // 'QueryString'      => $_SERVER['QUERY_STRING'],
            // 'ServerPort'       => $_SERVER['SERVER_PORT'],
        ];
    }
}