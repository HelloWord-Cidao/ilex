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
 * @method public static        initialize(string $APPPATH, string $RUNTIMEPATH)
 * @method public static mixed  resolve(string $method, string $url)
 * @method public static string run(string $APPPATH, string $RUNTIMEPATH)
 */
class Autoloader
{

    /**
     * @todo check inheritance of Autoloader! static:: or self:: ?
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     * @return string
     */
    public static function run($APPPATH, $RUNTIMEPATH, $APPNAME)
    {
        static::initialize($APPPATH, $RUNTIMEPATH, $APPNAME);
        // @todo: how to handle the return value? check other project.
        // If a service controller is called, then it will response the HTTP request and exit before return anything. Other cases unknown.
        return static::resolve(
            $_SERVER['REQUEST_METHOD'], // i.e.  'GET' | 'POST' | 'PUT' | 'DELETE'
            isset($_GET['_url']) ? $_GET['_url'] : '/'
        );
    }

    /**
     * @param string $method
     * @param string $url
     * @return mixed
     */
    public static function resolve($method, $url)
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
    public static function initialize($APPPATH, $RUNTIMEPATH, $APPNAME)
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