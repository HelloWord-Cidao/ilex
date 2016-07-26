<?php

namespace Ilex\Base\Model\Entity\Log;

use \Ilex\Core\Context;
use \Ilex\Core\Debug;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class RequestLogEntity
 * @package Ilex\Base\Model\Entity\Log
 */
final class RequestLogEntity extends BaseEntity
{

    final public function setCode($code)
    {
        return $this->setMeta('Code', Kit::ensureIn($code, [ 0, 1, 2, 3 ]));
    }

    final public function setRequest($uri)
    {
        return $this->setData('Request', [
            'RequestMethod'    => $_SERVER['REQUEST_METHOD'],
            'RequestURI'       => Kit::ensureString($uri),
            'RequestTimestamp' => $_SERVER['REQUEST_TIME'],
            'RequestTime'      => Kit::fromTimestamp($_SERVER['REQUEST_TIME']),
            // 'QueryString'      => $_SERVER['QUERY_STRING'],
            'RemoteIP'         => $_SERVER['REMOTE_ADDR'],
            'RemotePort'       => $_SERVER['REMOTE_PORT'],
            // 'ServerPort'       => $_SERVER['SERVER_PORT'],
        ]);
    }

    final public function setInput($input)
    {
        return $this->setData('Input', Kit::ensureArray($input));
    }

    final public function setHandlerInfo($class_name, $method_name)
    {
        return $this
            ->setInfo('HandlerInfo', [
                'Class'  => Kit::ensureString($class_name, TRUE, TRUE),
                'Method' => Kit::ensureString($method_name, TRUE, TRUE),
            ]);
    }

    final public function setResponse($response)
    {
        return $this->setData('Response', Kit::ensureArray($response));
    }

    final public function setOperationInfo($size)
    {
        return $this->setInfo('OperationInfo', [
            'Time'   => Debug::getTimeUsed(Debug::T_MICROSECOND, FALSE),
            'Memory' => Debug::getMemoryUsed(Debug::M_BYTE, FALSE),
            'Size'   => Kit::ensureNonNegativeInt($size),
        ]);
    }

    final public function setUserInfo()
    {
        return $this->setInfo('UserInfo', Context::me()->getAbstract());
    }

}