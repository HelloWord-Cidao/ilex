<?php

namespace Ilex\Core;

use \Exception;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Entity\User\UserEntity;

/**
 * Class Context
 * The class in charge of context variables.
 * @package Ilex\Core
 */
final class Context
{

    private static $currentUser            = NULL;
    private static $currentMemorizeMission = NULL;
    
    final public static function trySetCurrentUserEntity()
    {
        $token = Loader::loadInput()->input('token');
        if (TRUE === Kit::isString($token) AND '' !== $token) {
            $class_name = Loader::includeCore('User/User');
            try {
                self::$currentUser = $class_name::getCurrentUserEntity($token);
            } catch (Exception $e) {
                self::$currentUser = NULL;
            }
        }
    }

    final public static function isLogin()
    {
        return TRUE === isset(self::$currentUser) AND TRUE === (self::$currentUser instanceof UserEntity);
    }

    final public static function user()
    {
        return self::$currentUser;
    }

}