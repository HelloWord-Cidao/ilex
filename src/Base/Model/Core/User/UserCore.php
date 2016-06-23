<?php

namespace Ilex\Base\Model\Core\User;

use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Core\BaseCore;
use \Firebase\JWT\JWT;

/**
 * Class UserCore
 * @package Ilex\Base\Model\Core\User
 */
class UserCore extends BaseCore
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->loadCollection('User/User');
    }

    public static function getCurrentUserEntity($token)
    {
        $user_info = self::parseToken($token);
        return Loader::loadCollection('User/User')->getTheOnlyOneEntityById($user_info['userId']);
    }

    private static function parseToken($jwt)
    {
        Kit::ensureString($jwt);
        $token = JWT::decode($jwt, JWT_SEC_KEY, array('HS512'));
        return [
            'userId' => $token['data']['userId'],
        ];
    }

}