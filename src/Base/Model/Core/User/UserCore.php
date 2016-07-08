<?php

namespace Ilex\Base\Model\Core\User;

use \Exception;
use \Ilex\Core\Loader;
use \Ilex\Lib\UserException;
use \Ilex\Base\Model\Core\BaseCore;

/**
 * Class UserCore
 * @package Ilex\Base\Model\Core\User
 */
abstract class UserCore extends BaseCore
{
    const COLLECTION_NAME = 'User';
    const ENTITY_PATH     = 'User/User';

    public function __construct()
    {
        $this->loadCollection(self::ENTITY_PATH);
    }

    final public static function getCurrentUserEntity($token)
    {
        try {
            $user_info = static::parseToken($token);
        } catch (Exception $e) {
            throw new UserException('Invalid token.', $e);
        }
        try {
            $user = Loader::loadCollection(self::ENTITY_PATH)
                ->getTheOnlyOneEntityById($user_info['userId'])
                ->setReadOnly();
            return $user;
        } catch (Exception $e) {
            throw new UserException('Token error or user not exist.', $e);
        }
    }
    
    abstract protected function generateJWT($username);

    abstract protected static function parseToken($jwt);

}