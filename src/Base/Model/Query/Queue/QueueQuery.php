<?php

namespace Ilex\Base\Model\Query\Queue;

use \Ilex\Core\Context;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Query\BaseQuery;
use \Ilex\Base\Model\Core\Queue\QueueCore;

/**
 * Class QueueQuery
 * @package Ilex\Base\Model\Query\Queue
 */
final class QueueQuery extends BaseQuery
{
    final public function isMe()
    {
        return $this->hasOneReferenceTo(Context::me(), 'User');
    }

    final public function isInLock()
    {
        return $this->isGreaterThanOrEqualTo('Info.PushingTimestamp', Kit::microTimestampAtNow() - QueueCore::T_LOCK);
    }

    final public function isNotInLock()
    {
        return $this->isLessThan('Info.PushingTimestamp', Kit::microTimestampAtNow() - QueueCore::T_LOCK);
    }

    final public function pushedBefore($micro_timestamp)
    {
        return $this->isLessThan('Info.PushingTimestamp', Kit::ensureFloat($micro_timestamp));
    }

    final public function isInQueue()
    {
        return $this->metaFieldIs('IsInQueue', TRUE);
    }

    final public function isNotInQueue()
    {
        return $this->metaFieldIs('IsInQueue', FALSE);
    }

    final public function sortByPushingTime($direction = 1)
    {
        return $this->sortBy('Info.PushingTimestamp', $direction);
    }
}