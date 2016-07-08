<?php

namespace Ilex\Base\Model\Log;

use \Ilex\Core\Debug;
use \Ilex\Base\Model\Log\BaseLog;

/**
 * Class RequestLog
 * @package Ilex\Base\Model\Log
 */
final class RequestLog extends BaseLog
{

    /**
     * @TODO: check efficiency
     */
    final public function addRequestLog($class_name, $method_name, $input, $code, $operation_status)
    {
        $content = [
            'Data' => [
                'Class'           => $class_name,
                'Method'          => $method_name,
                'Input'           => $input,
                'Code'            => $code,
                'OperationStatus' => $operation_status,
            ],
            'Info' => [
                'RequestInfo'   => [
                    'RequestMethod' => $_SERVER['REQUEST_METHOD'],
                    'RequestURI'    => $_SERVER['REQUEST_URI'],
                    'RequestTime'   => $_SERVER['REQUEST_TIME'],
                    'QueryString'   => $_SERVER['QUERY_STRING'],
                    'RemoteIP'      => $_SERVER['REMOTE_ADDR'],
                    'RemotePort'    => $_SERVER['REMOTE_PORT'],
                    'ServerPort'    => $_SERVER['SERVER_PORT'],
                ],
                'OperationInfo' => [
                    'TimeUsed'             => Debug::getTimeUsed(Debug::T_MICROSECOND, FALSE),
                    'MemoryUsed'           => Debug::getMemoryUsed(Debug::M_BYTE, FALSE),
                    'ExecutionRecordCount' => Debug::countExecutionRecord(),
                ],
                'SystemVersion' => SYS_VERSION,
            ],
        ];
        $this->loadCollection('Log');
        return $this->LogCollection->addRequestLog($content);
    }
}