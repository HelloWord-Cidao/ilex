<?php

namespace Ilex\Base\Model\Core\Queue;

use \Ilex\Core\Context;
use \Ilex\Core\Debug;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Base\Model\Core\BaseCore;
use \Ilex\Base\Model\Entity\User\UserEntity;

/**
 * Class QueueCore
 * @package Ilex\Base\Model\Core\Queue
 */
final class QueueCore extends BaseCore
{
    const COLLECTION_NAME = 'Queue';
    const ENTITY_PATH     = 'Queue/Queue';

    const T_LOCK  = 40; // larger than PHP timeout limit
    const T_SLEEP = 100000;

    private static $isPushed         = FALSE;
    private static $queueId          = NULL;
    private static $pushingTimestamp = NULL;
    private static $isPopped         = FALSE;

    final public function push()
    {
        self::$queueId = $this
            ->ensureNotPushed()
            ->createEntity()
            ->doNotRollback()
            ->setRequestInfo()
            ->setUserInfo()
            ->push(self::$pushingTimestamp = Kit::microTimestampAtNow())
            ->addToCollection()
            ->getId();
        self::$isPushed = TRUE;
    }

    final public function popExpiredItems()
    {
        $this->createQuery()
            ->isNotInQueue()
            ->getMultiEntities()
            ->batch('doNotRollback')
            ->batch('removeFromCollection');
    }

    final public function isLocked()
    {
        return $this
            ->ensurePushed()
            ->createQuery()
            ->isInQueue()
            ->isMe()
            ->idIsNot(self::$queueId)
            ->isInLock()
            ->pushedBefore(self::$pushingTimestamp)
            ->checkExistEntities();
    }

    final public function pop()
    {
        if (FALSE === self::$isPushed) return;
        $this
            ->ensureNotPopped()
            ->getTheOnlyOneEntityById(self::$queueId)
            ->doNotRollback()
            ->pop()
            ->updateToCollection();
        self::$isPopped = TRUE;
        $this->popExpiredItems();
    }

    final public function ensurePushed()
    {
        if (FALSE === self::$isPushed)
            throw new UserException('This user has not been pushed to the queue.');
        return $this;
    }

    final public function ensureNotPushed()
    {
        if (TRUE === self::$isPushed)
            throw new UserException('This user has been pushed to the queue.');
        return $this;
    }

    final public function ensurePopped()
    {
        if (FALSE === self::$isPopped)
            throw new UserException('This user has not been popped from the queue.');
        return $this;
    }

    final public function ensureNotPopped()
    {
        if (TRUE === self::$isPopped)
            throw new UserException('This user has been popped from the queue.');
        return $this;
    }
}