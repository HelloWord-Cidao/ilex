<?php

namespace Ilex\Lib\MongoDB;

use \MongoId;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * Class MongoDBId
 * Encapsulation of basic operations of MongoId class.
 * @package Ilex\Lib\MongoDB
 *
 * @property private MongoId $id
 * 
 * @method final public __construct(MongoId|string $id_or_string)
 * @method final public toMongoId()
 * @method final public __toString()
 * @method final public toString()
 * @method final public isEqualTo(MongoDBId $id)
 */
final class MongoDBId
{
    
    private $id;

    final public function __construct($id_or_string)
    {
        if (TRUE === $id_or_string instanceof MongoDBId)
            $this->id = $id_or_string->toMongoId();
        elseif (TRUE === $id_or_string instanceof MongoId)
            $this->id = $id_or_string;
        elseif (TRUE === MongoId::isValid($id_or_string)) {
            Kit::ensureString($id_or_string);
            try {
                $this->id = new MongoId($id_or_string);
            } catch (Exception $e) {
                throw new UserException("Invalid \$string(${id_or_string}) to construct a MongoId.");
            }
        } else throw new UserException('Invalid $id_or_string.', $id_or_string);
    }

    final public function toMongoId()
    {
        return $this->id;
    }

    final public function __toString()
    {
        return $this->id->__toString();
    }

    final public function toString()
    {
        return $this->__toString();
    }

    final public function isEqualTo($id)
    {
        return $this->toString() === (new MongoDBId($id))->toString();
    }

}
