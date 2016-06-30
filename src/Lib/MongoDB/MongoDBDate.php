<?php

namespace Ilex\Lib\MongoDB;

use \MongoDate;

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
    
    private $date;

    final public function __construct($timestamp?)
    {
    }

    final public function __toString()
    {
    }

    final public function toString()
    {
    }

    final public function toDateTime()
    {
    }

    final public function toTimestamp()
    {
        $result = Kit::split(' ', $this->date->__toString());
        return (int)$result[1] + (float)$result[0];
    }

    final public function isEqualTo(MongoDBDate $date)
    {
        return $this->toTimestamp() === $date->toTimestamp();
    }

}
