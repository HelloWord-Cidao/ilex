<?php

namespace Ilex\Base\Model\Entity\User;

use \MongoDate;
use \Ilex\Core\Context;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Base\Model\Entity\BaseEntity;

/**
 * Class UserEntity
 * @package Ilex\Base\Model\Entity\User
 */
class UserEntity extends BaseEntity
{

    public function getAbstract()
    {
        return $this->getIdentity() + [
            'Username' => $this->getUsername(),
            'Type'     => $this->getType(),
        ];
    }

    public function setUsername($username)
    {
        Kit::ensureString($username);
        return $this->setInfo('Username', $username);
    }

    final public function getUsername()
    {
        return $this->getInfo('Username');
    }

    public function setPassword($password)
    {
        Kit::ensureString($password);
        return $this->setInfo('Password', $password);
    }

    final public function getPassword()
    {
        return $this->getInfo('Password');
    }

    public function setEmail($email)
    {
        // @TODO: validate
        Kit::ensureString($email);
        return $this->setInfo('Email', $email);
    }

    final public function getEmail()
    {
        return $this->getInfo('Email', FALSE, '');
    }

    final public function loginNow()
    {
        return $this->setInfo('LastLoginTime', Kit::now());
    }

    final public function getLastLoginTimestamp()
    {
        $last_login_time = $this->getInfo('LastLoginTime', FALSE, NULL);
        if (TRUE === is_null($last_login_time)) return 0;
        else return Kit::toTimestamp($last_login_time);
    }
}