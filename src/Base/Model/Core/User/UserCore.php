<?php

namespace Ilex\Base\Model\Core\User;

use \Firebase\JWT\JWT;
use \Exception;
use \Ilex\Lib\UserException;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Core\BaseCore;

/**
 * Class UserCore
 * @package Ilex\Base\Model\Core\User
 */
abstract class UserCore extends BaseCore
{
    // protected static $methodsVisibility = [
    //     self::V_PUBLIC => [
    //     ],
    // ];

    public function __construct()
    {
        $this->loadCollection('User/User');
    }

    abstract protected function generateJWT($username);

    public static function getCurrentUserEntity($token)
    {

        try {
            $user_info = static::parseToken($token);
        } catch (Exception $e) {
            throw new UserException('Invalid token.', $e);
        }
        try {
            $user = Loader::loadCollection('User/User')->getTheOnlyOneEntityById($user_info['userId'])->setReadOnly();
            return $user;
        } catch (Exception $e) {
            throw new UserException('Token error or user not exist.', $e);
        }
    }
    
    abstract protected static function parseToken($jwt);

}