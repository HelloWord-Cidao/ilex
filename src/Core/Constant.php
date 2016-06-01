<?php

namespace Ilex\Core;

use \Ilex\Core\Loader;

/**
 * Class Constant
 * The class in charge of initializing const variables.
 * @package Ilex\Core
 * 
 * @method final public static initialize()
 */
final class Constant
{
    const CONSTANT_LIST = [
        /*
         * -----------------------
         * System
         * -----------------------
         */
        'SYS_SESSNAME' => 'ILEX_SESSION',
        'ENVIRONMENT'  => 'DEVELOPMENT',
        
        'ENV_HOST'     => (TRUE === isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'),
        'ENV_SSL'      => (bool)(TRUE === isset($_SERVER['SERVER_PORT']) AND 443 === $_SERVER['SERVER_PORT']),
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
        require_once(Loader::APPPATH() . 'Config/Const.php');
        foreach (self::CONSTANT_LIST as $name => $value) {
            if (FALSE === defined($name)) define($name, $value);
        }
    }
}