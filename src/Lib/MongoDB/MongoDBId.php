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
 * @method public __construct(MongoId|string $id_or_string)
 * @method public toMongoId()
 * @method public __toString()
 * @method public toString()
 * @method public isEqualTo(MongoDBId $id)
 */
final class MongoDBId
{
    
    private $id;

    final public function __construct($id_or_string)
    {
        if (TRUE === $id_or_string instanceof MongoId)
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

    final public function isEqualTo(MongoDBId $id)
    {
        return $this->toString() === $id->toString();
    }

}