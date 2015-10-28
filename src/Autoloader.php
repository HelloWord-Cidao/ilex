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
 * @method public static string run(string $APPPATH, string $RUNTIMEPATH)
 * @method public static        initialize(string $APPPATH, string $RUNTIMEPATH)
 * @method public static mixed  resolve(string $method, string $url)
 */
class Autoloader
{
    /**
     * @todo check inheritance of Autoloader! static:: or self:: ?
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @return string
     */
    public static function run($APPPATH, $RUNTIMEPATH)
    {
        static::initialize($APPPATH, $RUNTIMEPATH);
        // @todo: how to handle the return value? check other project.
        return static::resolve(
            $_SERVER['REQUEST_METHOD'], // i.e.  'GET' | 'HEAD' | 'POST' | 'PUT'
            isset($_GET['_url']) ? $_GET['_url'] : '/'
        );
    }

    /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     */
    public static function initialize($APPPATH, $RUNTIMEPATH)
    {
        $ILEXPATH    = Kit::getRealPath(__DIR__);
        $APPPATH     = Kit::getRealPath($APPPATH);
        $RUNTIMEPATH = Kit::getRealPath($RUNTIMEPATH);
        /**
         * Loader::initialize() should be called before Constant::initialize(), 
         * because Loader::APPPATH() is called in Constant::initialize()
         */
        Loader::initialize($ILEXPATH, $APPPATH, $RUNTIMEPATH);
        Constant::initialize();
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
            'url'    => $url
        ]]);
        $Router = new Router($method, $url);
        require(Loader::APPPATH() . 'Config/Route.php');
        Kit::log([__METHOD__, ['$Router' => $Router]]);
        return $Router->result();
    }
}