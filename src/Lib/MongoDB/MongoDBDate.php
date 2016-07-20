<?php

namespace Ilex\Lib\MongoDB;

use \DateTime;
use \DateInterval;
use \MongoDate;
use \Ilex\Lib\Kit;

/**
 * Class MongoDBDate
 * Encapsulation of basic operations of MongoDate class.
 * @package Ilex\Lib\MongoDB
 */
final class MongoDBDate
{

    final public static function toTimestamp(MongoDate $mongo_date)
    {
        $result = Kit::split(' ', $mongo_date->__toString());
        return (int)$result[1] + (float)$result[0];
    }

    final public static function fromTimestamp($timestamp)
    {
        Kit::ensureType($timestamp, [ Kit::TYPE_INT, Kit::TYPE_FLOAT ]);
        return new MongoDate($timestamp);
    }

    // final public function isEqualTo(MongoDBDate $date)
    // {
    //     return $this->toTimestamp() === $date->toTimestamp();
    // }
    
    final public static function now()
    {
        return new MongoDate();
    }

    final public static function timestampAtNow()
    {
        return time();
    }

    final public static function daysAfterNow($days)
    {
        Kit::ensureInt($days);
        return new MongoDate(
            (new DateTime())->add(new DateInterval("P${days}D"))->getTimestamp()
        );
    }
}
