<?php

namespace Ilex\Base\Model\Core;

use \Ilex\Base\Model\Core\BaseCore;
/**
 * Class LogModel
 * Encapsulation of system log, such as HTTP requests, etc.
 * @package HelloWord\Model\Core
 *
 * @method public               __construct()
 * @method public array|boolean logGetRequest(string $handler, array $arguments)
 * @method public array|boolean logPostRequest(string $handler, mixed $post_data, array $arguments = NULL)
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
     * @param string $handler
     * @param array  $arguments
     * @return array|boolean
     */
    public function logGetRequest($handler, $arguments)
    {
        $log = [
            'Content' => [
                'Arguments' => $arguments,
                'Handler'   => $handler,
                'Method'    => 'GET',
            ]
        ];
        return $this->logRequest($log);
    }

    /**
     * @param string $handler
     * @param mixed  $post_data
     * @param array  $arguments
     * @return array|boolean
     */
    public function logPostRequest($handler, $post_data, $arguments = NULL)
    {
        $log = [
            'Content' => [
                'Arguments' => $arguments,
                'Handler'   => $handler,
                'Method'    => 'POST',
                'PostData'  => $post_data,
            ]
        ];
        return $this->logRequest($log);
    }

    /**
     * @param array $log
     * @return array|boolean
     */
    private function logRequest($log)
    {
        $request_info = [
            'RequestMethod' => $_SERVER['REQUEST_METHOD'],
            'RequestURI'    => $_SERVER['REQUEST_URI'],
            'RequestTime'   => $_SERVER['REQUEST_TIME'],
            'QueryString'   => $_SERVER['QUERY_STRING'],
            'RemoteIP'      => $_SERVER['REMOTE_ADDR'],
            'RemotePort'    => $_SERVER['REMOTE_PORT'],
            'ServerPort'    => $_SERVER['SERVER_PORT'],
        ];
        $log['Content']['RequestInfo'] = $request_info;
        $log['Meta'] = [
            'Type'          => 'HttpRequestLog',
            'SystemVersion' => SYS_VERSION,
        ];
        return $this->LogCollection->addLog($log);
    }

}