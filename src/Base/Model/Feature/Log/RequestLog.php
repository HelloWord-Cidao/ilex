<?php

namespace Ilex\Base\Model\Feature\Log;

use \Ilex\Base\Model\Feature\Log\BaseLog;
/**
 * Class RequestLog
 * Encapsulation of system log, such as HTTP requests, etc.
 * @package Ilex\Base\Model\Feature\Log
 *
 * @method protected array|boolean logRequest(array $input, array $operation_status)
 */
final class RequestLog extends BaseLog
{

    /**
     * @TODO: check efficiency
     * @param array  $input
     * @param array  $operation_status
     * @return array|boolean
     */
    protected function addRequestLog($input, $operation_status)
    {
        $debug_backtrace = debug_backtrace()[1];
        $handler = get_class($debug_backtrace['object']) . '::' . $debug_backtrace['args'][0];
        $log = [
            'Content' => [
                'Handler'         => $handler,
                'Input'           => $input,
                'OperationStatus' => $operation_status,
                'RequestInfo'     => [
                    'RequestMethod' => $_SERVER['REQUEST_METHOD'],
                    'RequestURI'    => $_SERVER['REQUEST_URI'],
                    'RequestTime'   => $_SERVER['REQUEST_TIME'],
                    'QueryString'   => $_SERVER['QUERY_STRING'],
                    'RemoteIP'      => $_SERVER['REMOTE_ADDR'],
                    'RemotePort'    => $_SERVER['REMOTE_PORT'],
                    'ServerPort'    => $_SERVER['SERVER_PORT'],
                ],
            ],
            'Meta' => [
                'Type'          => 'Request',
                'SystemVersion' => SYS_VERSION,
            ],
        ];
        return $this->LogCollection->addLog($log);
    }

}