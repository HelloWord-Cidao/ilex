<?php

namespace Ilex;

use \Ilex\Core\Constant;
use \Ilex\Core\Debug;
use \Ilex\Core\Loader;
use \Ilex\Core\Router;
use \Ilex\Lib\Kit;

/**
 * Class Autoloader
 * @package Ilex
 * 
 * @method public static        initialize(string $APPPATH, string $RUNTIMEPATH)
 * @method public static mixed  resolve(string $method, string $url)
 * @method public static mixed  run(string $APPPATH, string $RUNTIMEPATH)
 */
final class Autoloader
{

    /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     * @return mixed
     */
    public static function run($APPPATH, $RUNTIMEPATH, $APPNAME)
    {
        self::initialize($APPPATH, $RUNTIMEPATH, $APPNAME);
        // @todo: how to handle the return value? check other project.
        // If a service controller is called, then it will respond the HTTP request and exit before return anything. Other cases unknown.
        return self::resolve(
            $_SERVER['REQUEST_METHOD'], // i.e.  'GET' | 'POST' | 'PUT' | 'DELETE'
            (TRUE === isset($_GET['_url'])) ? $_GET['_url'] : '/'
        );
    }

    /**
     * @param string $method
     * @param string $url
     * @return mixed
     */
    public static function resolve($method, $url)
    {
        $Router = new Router($method, $url);
        require(Loader::APPPATH() . 'Config/Route.php');
        return $Router->result();
    }

     /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     */
    public static function initialize($APPPATH, $RUNTIMEPATH, $APPNAME)
    {
        $APPPATH     = Kit::getRealPath($APPPATH);
        $ILEXPATH    = Kit::getRealPath(__DIR__ . '/Base/');
        $RUNTIMEPATH = Kit::getRealPath($RUNTIMEPATH);
        /**
         * Loader::initialize() should be called before Constant::initialize(), 
         * because Loader::APPPATH() is called in Constant::initialize()
         */
        session_cache_expire(240);
        date_default_timezone_set('UTC');
        Loader::initialize($ILEXPATH, $APPPATH, $RUNTIMEPATH, $APPNAME);
        Constant::initialize();
        Debug::initialize();
    }
}