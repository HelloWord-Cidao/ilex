<?php

namespace Ilex\Core;

use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;

/**
 * Class Constant
 * The class in charge of initializing const variables.
 * @package Ilex\Core
 * 
 * @method final public static initialize()
 */
final class Constant
{
    private static $constantMapping = [
        /*
         * -----------------------
         * System
         * -----------------------
         */
        'SYS_SESSNAME' => 'ILEX_SESSION',
        
        // 'ENV_HOST'     => ((TRUE === isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : 'localhost'),
        // 'ENV_SSL'      => (bool)(TRUE === isset($_SERVER['SERVER_PORT']) AND 443 === $_SERVER['SERVER_PORT']),
        // 'ENV_HOST_URL' => TRUE === ENV_SSL ? 'https' : 'http') . '://' . ENV_HOST,

        /*
         * -----------------------
         * Server
         * -----------------------
         */
        'SVR_MONGO_HOST'    => 'localhost',
        'SVR_MONGO_PORT'    => 27017,
        'SVR_MONGO_USER'    => 'admin',
        'SVR_MONGO_PASS'    => 'admin',
        'SVR_MONGO_TIMEOUT' => 2000,
        'SVR_MONGO_DB'      => 'test',
    ];

    public static function initialize()
    {
        $constantMapping = require_once(Loader::APPPATH() . 'Config/Const.php');
        Kit::update(self::$constantMapping, $constantMapping);
        $constantMapping = require_once(Loader::APPPATH() . 'Config/Const-local.php');
        Kit::update(self::$constantMapping, $constantMapping);
        foreach (self::$constantMapping as $name => $value) {
            if (FALSE === defined($name)) define($name, $value);
        }
    }
}