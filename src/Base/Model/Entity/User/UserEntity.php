<?php

namespace Ilex\Base\Model\Entity\User;

use \MongoDate;
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

    // final public function setUserInfo($user_info)
    // {
    //     Kit::ensureDict($user_info);
    //     $this->setInfo('UserInfo', $user_info);
    //     return $this;
    // }

    // final public function getUserInfo()
    // {
    //     return $this->getInfo('UserInfo');
    // }

    final public function loginNow()
    {
        return $this->setInfo('lastLoginTime', new MongoDate());
    }
}