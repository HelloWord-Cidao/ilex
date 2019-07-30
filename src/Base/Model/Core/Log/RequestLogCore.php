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
        $request_log = $this->createEntity()
            ->doNotRollback()
            ->setCode(Kit::ensureIn($code, [ 0, 1, 2, 3 ]))
            ->setRequest(self::generateRequestInfo())
            ->setInput()
            ->setHandlerInfo($class_name, $method_name);
        if (TRUE === Kit::in($code, [ 0, 1 ])) $request_log->setResponse($response);
        if (TRUE === Context::isLogin([ ])) $request_log->setUserInfo();
        $response = json_encode($response);
        if (FALSE === $response) $len = 0;
        else $len = Kit::len($response);
        $request_log
            ->setOperationInfo($len)
            ->doNotRollback()
            ->addToCollection();
        // self::updateUserData();
    }

    final private function updateUserData()
    {
        if (TRUE === Context::isLogin([ ])) {
            $existence = $this->loadCore('User/UserData')->createQuery()
                ->hasOneReferenceTo($me, 'User')
                ->checkExistsOnlyOneEntity();
            if (FALSE === $existence) {
                $this->loadCore('User/UserData')->createEntity()
                    ->buildOneReferenceTo($me, 'User')
                    ->addToCollection();
            }
            $user_data = $this->loadCore('User/UserData')->createQuery()
                ->hasOneReferenceTo($me, 'User')
                ->getTheOnlyOneEntity();

            $me           = Context::me();
            $today        = Kit::todayDateFormat();
            $now          = Kit::now();
            $timestamp    = Kit::timestampAtNow();
            $ip           = $_SERVER['REMOTE_ADDR'];
            $request_data = $user_data->getRequestData();

            if (FALSE === isset($request_data[$today])) $request_data[$today] = [];
            if (FALSE === isset($request_data[$today][$ip])) $request_data[$today][$ip] = [];
            if (0 === Kit::len($request_data[$today][$ip])) {
                $request_data[$today][$ip][] = [
                    'StartTime'      => $now,
                    'StartTimestamp' => $timestamp,
                    'Count'          => 1,
                    'LastTime'       => $now,
                    'LastTimestamp'  => $timestamp,
                ];
            }
            if ($request_data[$today][$ip][-1])
            $user_data->setRequestData($request_data)->updateToCollection();
        }
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