<?php

namespace Ilex\Base\Model\Core;

use \Ilex\Base\Model\Core\BaseCore;
/**
 * Class LogModel
 * Encapsulation of system log, such as HTTP requests, etc.
 * @package HelloWord\Model\Core
 *
 * @method public               __construct()
 * 
 * @method private array|boolean logRequest(array $log)
 */
class Log extends BaseCore
{
    protected $LogCollection;

    public function __construct()
    {
        $this->loadModel('Database/LogCollection');
    }

    /**
     * @TODO: use debug_backtrace() to generate $handler
     * @param string $handler
     * @param array  $operation_status
     * @param mixed  $post_data
     * @param array  $arguments
     * @return array|boolean
     */
    public function logRequest($operation_status, $arguments, $post_data)
    {
        $debug_backtrace = debug_backtrace()[1];
        $handler = get_class($debug_backtrace['object']) . '::' . $debug_backtrace['args'][0];
        $log = [
            'Content' => [
                'Handler'         => $handler,
                'OperationStatus' => $operation_status,
                'Arguments'       => $arguments,
                'PostData'        => $post_data,
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
                'Type'          => 'HttpRequestLog',
                'SystemVersion' => SYS_VERSION,
            ],
        ];
        return $this->LogCollection->addLog($log);
    }

}