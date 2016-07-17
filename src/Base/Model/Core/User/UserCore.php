<?php

namespace Ilex\Base\Model\Core\User;

use \Exception;
use \Ilex\Core\Loader;
use \Ilex\Lib\UserException;
use \Ilex\Base\Model\Core\BaseCore;
use \Ilex\Base\Model\Entity\User\UserEntity;

/**
 * Class UserCore
 * @package Ilex\Base\Model\Core\User
 */
abstract class UserCore extends BaseCore
{
    const COLLECTION_NAME = 'User';
    const ENTITY_PATH     = 'User/User';

    final public static function getCurrentUser($token)
    {
        try {
            $user_info = static::parseToken($token);
        } catch (Exception $e) {
            throw new UserException('Invalid token.', $e);
        }
        try {
            $user = Loader::loadCore(self::ENTITY_PATH)
                ->getTheOnlyOneEntityById($user_info['userId'])
                ->setReadOnly();
            return $user;
        } catch (Exception $e) {
            throw new UserException('Token error or user not exist.', $e);
        }
    }
    
    abstract protected static function generateToken(UserEntity $user);

    abstract protected static function parseToken($token);

}