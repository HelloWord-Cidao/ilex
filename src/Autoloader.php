<?php

namespace Ilex;

use \Ilex\Core\Constant;
use \Ilex\Core\Loader;
use \Ilex\Core\Router;
use \Ilex\Lib\Kit;

/**
 * Class Autoloader
 * @package Ilex
 * 
 * @method final public static        initialize(string $APPPATH, string $RUNTIMEPATH)
 * @method final public static mixed  resolve(string $method, string $url)
 * @method final public static string run(string $APPPATH, string $RUNTIMEPATH)
 */
final class Autoloader
{

    /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     * @return string
     */
    final public static function run($APPPATH, $RUNTIMEPATH, $APPNAME)
    {
        self::initialize($APPPATH, $RUNTIMEPATH, $APPNAME);
        // @todo: how to handle the return value? check other project.
        // If a service controller is called, then it will response the HTTP request and exit before return anything. Other cases unknown.
        return self::resolve(
            $_SERVER['REQUEST_METHOD'], // i.e.  'GET' | 'POST' | 'PUT' | 'DELETE'
            TRUE === isset($_GET['_url']) ? $_GET['_url'] : '/'
        );
    }

    /**
     * @param string $method
     * @param string $url
     * @return mixed
     */
    final public static function resolve($method, $url)
    {
        Kit::log([__METHOD__, [
            'method' => $method,
            'url'    => $url,
        ]]);
        $Router = new Router($method, $url);
        require(Loader::APPPATH() . 'Config/Route.php');
        Kit::log([__METHOD__, ['$Router' => $Router]]);
        return $Router->result();
    }

     /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     */
    final public static function initialize($APPPATH, $RUNTIMEPATH, $APPNAME)
    {
        $APPPATH     = Kit::getRealPath($APPPATH);
        $ILEXPATH    = Kit::getRealPath(__DIR__);
        $RUNTIMEPATH = Kit::getRealPath($RUNTIMEPATH);
        /**
         * Loader::initialize() should be called before Constant::initialize(), 
         * because Loader::APPPATH() is called in Constant::initialize()
         */
        Loader::initialize($ILEXPATH, $APPPATH, $RUNTIMEPATH, $APPNAME);
        Constant::initialize();
    }
}