<?php

namespace Ilex;

use \Ilex\Core\Constant;
use \Ilex\Core\Loader;
use \Ilex\Core\Route;
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
     * @todo static:: or self:: ?
     * @todo where is the model 'sys/Input' loaded?
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @return string
     */
    public static function run($APPPATH, $RUNTIMEPATH)
    {
        static::initialize($APPPATH, $RUNTIMEPATH);
        // @todo: how to handle the return value?
        return static::resolve(
            $_SERVER['REQUEST_METHOD'], // eg. 'GET' | 'POST' | 'PUT' | 'DELETE'?
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
        // ob_start();
        $Route = new Route($method, $url);
        // @todo: change to CamelCase?
        include(Loader::APPPATH() . 'config/route.php');
        Kit::log([__METHOD__, ['$Route' => $Route]]);
        return $Route->result();
        // return ob_get_clean();
    }
}