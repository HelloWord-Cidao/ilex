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
 * @method public static        run(string $APPPATH, string $RUNTIMEPATH)
 * @method public static        initialize(string $APPPATH, string $RUNTIMEPATH)
 * @method public static string getRealPath(string $path)
 * @method public static mixed  resolve(string $method, string $url)
 */
class Autoloader
{
    /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     */
    public static function run($APPPATH, $RUNTIMEPATH)
    {
        // @todo: static:: or self:: ?
        static::initialize($APPPATH, $RUNTIMEPATH);
        // @todo: how to handle the return value?
        static::resolve(
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
        $ILEXPATH    = self::getRealPath(__DIR__);
        $APPPATH     = self::getRealPath($APPPATH);
        $RUNTIMEPATH = self::getRealPath($RUNTIMEPATH);
        Loader::initialize($ILEXPATH, $APPPATH, $RUNTIMEPATH);
        Constant::initialize();
    }

    /**
     * @param string $path
     * @return string
     */
    public static function getRealPath($path)
    {
        if (($_temp = realpath($path)) !== FALSE) {
            $path = $_temp . '/';
        } else {
            $path = rtrim($path, '/') . '/';
        }
        return $path;
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
        include(Loader::APPPATH() . 'config/route.php');
        Kit::log([__METHOD__, ['$Route' => $Route]]);
        // return ob_get_clean();
        return $Route->result();
    }



}