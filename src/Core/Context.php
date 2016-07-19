<?php

namespace Ilex\Core;

use \Exception;
use \Ilex\Core\Debug;
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
    private static $currentInstitution     = NULL;
    private static $currentMemorizeMission = NULL;
    private static $hasSetMemorizeMission  = FALSE;
    
    final public static function trySetCurrentUser()
    {
        $token = Loader::loadInput()->input('token');
        if (TRUE === Kit::isString($token) AND '' !== $token) {
            $class_name = Loader::includeCore('User/User');
            try {
                self::$currentUser        = $class_name::getCurrentUser($token);
                self::$currentInstitution = self::$currentUser->getInstitution()->setReadOnly();
            } catch (Exception $e) {
                self::$currentUser        = NULL;
                self::$currentInstitution = NULL;
            }
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
        return self::$currentUser->toProperEntity();
    }

    final public static function myInstitution()
    {
        return self::$currentInstitution;
    }

    final public static function mission()
    {
        if (TRUE === is_null(self::$currentMemorizeMission)) {

        }
    }

    final private static function trySetCurrentMemorizeMission()
    {
        if (FALSE === self::$hasSetMemorizeMission) {
            $class_name = Loader::includeCore('Memorize/MemorizeMission');
            try {
                self::$currentUser        = $class_name::getCurrentUser($token);
                self::$currentInstitution = self::$currentUser->getInstitution()->setReadOnly();
            } catch (Exception $e) {
                self::$currentUser        = NULL;
                self::$currentInstitution = NULL;
            }
        }
    }

}