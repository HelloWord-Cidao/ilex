<?php

namespace Ilex;

use \Ilex\Core\Loader;
use \Ilex\Autoloader;
use \Ilex\Lib\Kit;

/**
 * Class Tester
 * @package Ilex
 * 
 * @method public static        boot(string $APPPATH, string $RUNTIMEPATH)
 * @method public static string run(string $url = '/', string $method = 'GET'
 *                                  , array $postData = [], array $getData = [])
 */
final class Tester
{
    /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     */
    public static function boot($APPPATH, $RUNTIMEPATH, $APPNAME)
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
    public static function run($url = '/', $method = 'GET', $postData = [], $getData = [])
    {
        $Input = Loader::model('System/Input');
        $Input->clear();
        $Input->merge('post', $postData);
        $Input->merge('get', $getData);
        // $_SERVER['REQUEST_URI'] =  ENV_HOST . '/' . $url; // @todo: what?
        return Autoloader::resolve($method, $url);
    }
}