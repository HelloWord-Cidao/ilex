<?php

namespace Ilex\Base\Model\Query\User;

use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Query\BaseQuery;

/**
 * Class UserQuery
 * @package Ilex\Base\Model\Query\User
 */
class UserQuery extends BaseQuery
{
    final public function usernameIs($username)
    {
        Kit::ensureString($username);
        return $this->infoFieldIs('Username', $username);
    }

    final public function passwordIs($password)
    {
        Kit::ensureString($password);
        return $this->infoFieldIs('Password', $password);
    }
}