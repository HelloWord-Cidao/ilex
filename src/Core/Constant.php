<?php

namespace Ilex\Core;

use \Ilex\Core\Loader;

/**
 * Class Constant
 * The class in charge of initializing const variables.
 * @package Ilex\Core
 * 
 * @method public static function initialize()
 */
class Constant
{
    public static function initialize()
    {
        $constants = [

            /*
             * -----------------------
             * System
             * -----------------------
             */

            'SYS_SESSNAME'          => 'ILEX_SESSION',
            'ENV_HOST'              => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost',

            'ENVIRONMENT'           => 'DEVELOPMENT',

            /*
             * -----------------------
             * Server
             * -----------------------
             */

            'SVR_MONGO_HOST'        => 'localhost',
            'SVR_MONGO_PORT'        => 27017,
            'SVR_MONGO_USER'        => 'admin',
            'SVR_MONGO_PASS'        => 'admin',
            'SVR_MONGO_DB'          => 'test',
            'SVR_MONGO_TIMEOUT'     => 2000,
        ];
        include_once(Loader::APPPATH() . 'config/const.php'); // lower case!
        foreach ($constants as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

}
