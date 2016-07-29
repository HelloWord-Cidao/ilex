<?php

namespace Ilex\Core;

use \Exception;
use \Ilex\Core\Debug;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Base\Model\Entity\User\UserEntity;

/**
 * Class Context
 * The class in charge of context variables.
 * @package Ilex\Core
 */
final class Context
{

    private static $currentUser            = NULL;
    private static $currentInstitution     = NULL;
    
    final public static function trySetCurrentUser()
    {
        try {
            self::refresh();
        } catch (Exception $e) {
            self::$currentUser        = NULL;
            self::$currentInstitution = NULL;
        }
    }

    final public static function isLogin($user_type_list)
    {
        $result = (TRUE === isset(self::$currentUser) AND TRUE === (self::$currentUser instanceof UserEntity));
        if (FALSE === $result OR 0 === Kit::len($user_type_list)) return $result;
        $current_user_type = self::$currentUser->getType();
        foreach ($user_type_list as $user_type) {
            if ($user_type === self::$currentUser->getType()) return TRUE;
        }
        Debug::monitor('loginFailed', "Wrong user type($current_user_type).");
        return FALSE;
    }

    final public static function me()
    {
        return self::$currentUser;
    }

    final public static function myInstitution()
    {
        return self::$currentInstitution;
    }

    final public static function refresh()
    {
        $token                    = Loader::loadInput()->token();
        $class_name               = Loader::includeCore('User/User');
        self::$currentUser        = $class_name::getCurrentUser(Kit::ensureString($token));
        self::$currentInstitution = self::$currentUser->getInstitution()->setReadOnly();
    }
}