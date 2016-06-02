<?php

namespace Ilex\Base\Model\Feature\Log;

use \Ilex\Base\Model\Feature\Log\BaseLog;
/**
 * Class RequestLog
 * @package Ilex\Base\Model\Feature\Log
 */
final class RequestLog extends BaseLog
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            'addRequestLog',
        ],
    ];

    /**
     * @TODO: check efficiency
     */
    protected function addRequestLog($class_name, $method_name, $input, $operation_status)
    {
        $log = [
            'Content' => [
                'Class'           => $class_name,
                'Method'          => $method_name,
                'Input'           => $input,
                'OperationStatus' => $operation_status,
            ],
            'Info'    => [
                'RequestInfo'   => [
                    'RequestMethod' => $_SERVER['REQUEST_METHOD'],
                    'RequestURI'    => $_SERVER['REQUEST_URI'],
                    'RequestTime'   => $_SERVER['REQUEST_TIME'],
                    'QueryString'   => $_SERVER['QUERY_STRING'],
                    'RemoteIP'      => $_SERVER['REMOTE_ADDR'],
                    'RemotePort'    => $_SERVER['REMOTE_PORT'],
                    'ServerPort'    => $_SERVER['SERVER_PORT'],
                ],
                'SystemVersion' => SYS_VERSION,
            ],
        ];
        $this->loadModel('Feature/Database/LogCollection');
        return $this->LogCollection->addRequestLog($log);
    }
}