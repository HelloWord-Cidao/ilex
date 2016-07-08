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
 *
 * @property private MongoDate $date
 * 
 * @method public __construct()
 * @method public __toString()
 * @method public toString()
 * @method public isEqualTo(MongoDBDate $date)
 */
final class MongoDBDate
{

    final public static function toTimestamp(MongoDate $mongo_date)
    {
        $result = Kit::split(' ', $mongo_date->__toString());
        return (int)$result[1] + (float)$result[0];
    }

    // final public function isEqualTo(MongoDBDate $date)
    // {
    //     return $this->toTimestamp() === $date->toTimestamp();
    // }
    
    final public static function daysAfterNow($days)
    {
        Kit::ensureInt($days);
        return new MongoDate(
            (new DateTime())->add(new DateInterval("P${days}D"))->getTimestamp()
        );
    }

}
