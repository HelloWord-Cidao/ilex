<?php

namespace Ilex\Base\Model\Entity\Log;

use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class RequestLogEntity
 * @package Ilex\Base\Model\Entity\Log
 */
final class RequestLogEntity extends BaseEntity
{

    final public function setRequest()
    {
        return $this->setData('Request', [
            'RequestMethod' => $_SERVER['REQUEST_METHOD'],
            'RequestURI'    => $_SERVER['REQUEST_URI'],
            'RequestTime'   => $_SERVER['REQUEST_TIME'],
            'QueryString'   => $_SERVER['QUERY_STRING'],
            'RemoteIP'      => $_SERVER['REMOTE_ADDR'],
            'RemotePort'    => $_SERVER['REMOTE_PORT'],
            'ServerPort'    => $_SERVER['SERVER_PORT'],
        ]);
    }

    final public function setInput()
    {
        return $this->setData('Input', Loader::loadInput()->input());
    }

    final public function setResponse($response)
    {
        return $this->setData('Response', Kit::ensureArray($response));
    }

    final public function setOperationInfo()
    {
        return $this->setInfo('OperationInfo', [
            'TimeUsed'   => Debug::getTimeUsed(Debug::T_MICROSECOND, FALSE),
            'MemoryUsed' => Debug::getMemoryUsed(Debug::M_BYTE, FALSE),
        ]);
    }

    final public function setUserInfo()

    final public function setClassName($class_name)
    {
        return $this->setInfo('ClassName',  Kit::ensureString($class_name));
    }

    final public function setMethodName($method_name)
    {
        return $this->setInfo('MethodName',  Kit::ensureString($method_name));
    }
}