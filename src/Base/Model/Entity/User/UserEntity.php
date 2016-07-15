<?php

namespace Ilex\Base\Model\Entity\User;

use \MongoDate;
use \Ilex\Core\Context as c;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class UserEntity
 * @package Ilex\Base\Model\Entity\User
 */
class UserEntity extends BaseEntity
{

    public function setUsername($username)
    {
        Kit::ensureString($username);
        $this->setInfo('Username', $username);
        return $this;
    }

    final public function getUsername()
    {
        return $this->getInfo('Username');
    }

    public function setPassword($password)
    {
        Kit::ensureString($password);
        $this->setInfo('Password', $password);
        return $this;
    }

    final public function getPassword()
    {
        return $this->getInfo('Password');
    }

    final public function loginNow()
    {
        return $this->setInfo('lastLoginTime', new MongoDate());
    }

    final public function isMe()
    {
        return $this->getId()->isEqualTo(c::user()->getId());
    }

    final public function ensureMe()
    {
        if (FALSE === $this->isMe())
            throw new UserException('This user is not me.');
        return $this;
    }

}