<?php

namespace Ilex;

use ReflectionClass;
use \Ilex\Autoloader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Base;

/**
 * Class Tester
 * @package Ilex
 * 
 * @method final public static        boot($APPPATH, $RUNTIMEPATH)
 * @method final public static string run($url = '/', $method = 'GET', $postData = [], $getData = [])
 */
final class Tester extends Base
{
    public static $Input;
    // protected static $Input;

    /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     */
    final public static function boot($APPPATH, $RUNTIMEPATH, $APPNAME)
    {
        Autoloader::initialize($APPPATH, $RUNTIMEPATH, $APPNAME);
        // Now Loader has been initialized by Autoloader::initialize().
        self::loadModel('System/Input');
        var_dump((new ReflectionClass(get_called_class()))->getProperty('Input')->getValue());
    }

    /**
     * @param string $url
     * @param string $method
     * @param array  $postData
     * @param array  $getData
     * @return string
     */
    final public static function run($url = '/', $method = 'GET', $postData = [], $getData = [])
    {
        Kit::log([__METHOD__, [
            'getData'  => $getData,
            'method'   => $method,
            'postData' => $postData,
            'url'      => $url,
        ]]);
        self::$Input->clear()->merge('post', $postData)->merge('get', $getData);
        Kit::log([__METHOD__, ['self::$Input' => self::$Input]]);
        // $_SERVER['REQUEST_URI'] =  ENV_HOST . '/' . $url; // @todo: what?
        return Autoloader::resolve($method, $url);
    }
}