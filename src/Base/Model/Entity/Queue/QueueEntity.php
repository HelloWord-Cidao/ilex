<?php

namespace Ilex\Base\Model\Entity\Queue;

use \Ilex\Core\Context;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Core\Log\RequestLogCore;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class QueueEntity
 * @package Ilex\Base\Model\Entity\Queue
 */
final class QueueEntity extends BaseEntity
{
    final public function setRequestInfo()
    {
        return $this->setData('RequestInfo', RequestLogCore::generateRequestInfo());
    }

    final public function setUserInfo()
    {
        return $this
            ->setData('UserInfo', Context::me()->getAbstract())
            ->buildOneReferenceTo(Context::me(), 'User');
    }

    final public function push($pushing_micro_timestamp)
    {
        return $this
            ->setInfo('PushingTime', Kit::fromTimestamp($pushing_micro_timestamp))
            ->setInfo('PushingTimestamp', $pushing_micro_timestamp)
            ->setMeta('IsInQueue', TRUE);
    }

    final public function pop()
    {
        return $this
            ->setInfo('PoppingTime', Kit::fromTimestamp(Kit::microTimestampAtNow()))
            ->setInfo('PoppingTimestamp', Kit::microTimestampAtNow())
            ->setMeta('IsInQueue', FALSE);
    }
}