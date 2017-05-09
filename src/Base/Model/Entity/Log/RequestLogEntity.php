<?php

namespace Ilex\Base\Model\Entity\Log;

use \Ilex\Core\Context;
use \Ilex\Core\Debug;
use \Ilex\Core\Loader;
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

    final public function setRequest($request)
    {
        return $this->setData('Request', Kit::ensureArray($request));
    }

    final public function setInput()
    {
        return $this->setData('Input', Loader::loadInput()->cleanInput());
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
        return $this->setData('Response', json_encode(Kit::ensureArray($response)));
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
        return $this
            ->setInfo('UserInfo', Context::me()->getAbstract())
            ->buildOneReferenceTo(Context::me(), 'User');
    }
}