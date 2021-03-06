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

    const T_LOCK  = 20; // 秒 should be larger than PHP timeout limit?
    const T_SLEEP = 100000; // 0.1秒

    private static $needLock         = NULL;
    private static $isPushed         = FALSE;
    private static $queueId          = NULL;
    private static $pushingTimestamp = NULL;
    private static $isPopped         = FALSE;

    final public function push($need_lock = FALSE)
    {
        self::$needLock = $need_lock;
        $this->ensureNotPushed();
        if (TRUE === $need_lock) {
            self::$queueId = $this
                ->createEntity()
                ->doNotRollback()
                ->setRequestInfo()
                ->setUserInfo()
                ->push(self::$pushingTimestamp = Kit::microTimestampAtNow())
                ->addToCollection()
                ->getId();
        } else {
            self::$pushingTimestamp = Kit::microTimestampAtNow();
        }
        self::$isPushed = TRUE;
    }

    final public function getPushingTimestamp()
    {
        $this->ensurePushed();
        return self::$pushingTimestamp;
    }

    final public function hasItemsAhead()
    {
        return $this
            ->queryItemsAhead()
            ->checkExistEntities();
    }

    final public function getItemsAhead()
    {
        return $this
            ->queryItemsAhead()
            ->getMultiEntities();
    }

    final private function queryItemsAhead()
    {
        $query = $this
            ->createQuery()
            ->isInQueue()
            ->isMe()
            ->isInLock()
            ->pushedBefore(self::$pushingTimestamp)
            ->sortByPushingTime();
        if (TRUE === self::$needLock) $query->idIsNot(self::$queueId);
        return $query;
    }

    final public function pop()
    {
        if (FALSE === self::$isPushed OR FALSE === self::$needLock) return;
        $this
            ->ensureNotPopped()
            ->getTheOnlyOneEntityById(self::$queueId)
            ->doNotRollback()
            ->pop()
            ->updateToCollection();
        self::$isPopped = TRUE;
        $this->popExpiredItems();
    }

    final private function popExpiredItems()
    {
        $this->createQuery()
            ->isNotInQueue()
            ->getMultiEntities()
            ->batch('doNotRollback', FALSE, FALSE)
            ->batch('removeFromCollection');
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