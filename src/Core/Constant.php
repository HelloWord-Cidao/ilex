<?php

namespace Ilex\Core;

use \Ilex\Core\Loader;

/**
 * Class Constant
 * The class in charge of initializing const variables.
 * @package Ilex\Core
 * 
 * @method public static initialize()
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
            'SYS_SESSNAME' => 'ILEX_SESSION',
            'ENVIRONMENT'  => 'DEVELOPMENT',
            
            'ENV_HOST'     => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'),
            'ENV_SSL'      => (bool)(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443),
            // 'ENV_HOST_URL' => ENV_SSL ? 'https' : 'http') . '://' . ENV_HOST,

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

            /**
             * -----------------------
             * Tag
             * -----------------------
             */
            'T_IS_ERROR'        => 'T_IS_ERROR',
        ];
        require_once(Loader::APPPATH() . 'Config/Const.php');
        foreach ($constants as $name => $value) {
            if (defined($name) === FALSE) {
                define($name, $value);
            }
        }
    }
}