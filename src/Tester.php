<?php

namespace Ilex;

use ReflectionClass;
use \Ilex\Core\Loader;
use \Ilex\Autoloader;
use \Ilex\Lib\Kit;

/**
 * Class Tester
 * @package Ilex
 * 
 * @method final public static        boot($APPPATH, $RUNTIMEPATH)
 * @method final public static string run($url = '/', $method = 'GET', $postData = [], $getData = [])
 */
final class Tester
{
    /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     */
    final public static function boot($APPPATH, $RUNTIMEPATH, $APPNAME)
    {
        Autoloader::initialize($APPPATH, $RUNTIMEPATH, $APPNAME);
        // Now Loader has been initialized by Autoloader::initialize().
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
        $Input = Loader::model('System/Input');
        $Input->clear();
        $Input->merge('post', $postData);
        $Input->merge('get', $getData);
        Kit::log([__METHOD__, ['$Input' => $Input]]);
        // $_SERVER['REQUEST_URI'] =  ENV_HOST . '/' . $url; // @todo: what?
        return Autoloader::resolve($method, $url);
    }
}