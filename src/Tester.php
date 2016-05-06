<?php

namespace Ilex;

use \Ilex\Autoloader;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;

/**
 * Class Tester
 * @package Ilex
 * 
 * @property private static \Ilex\Base\Model\System\Input   $Input
 * @property private static \Ilex\Base\Model\System\Session $Session
 * 
 * @method public static        boot($APPPATH, $RUNTIMEPATH)
 * @method public static string run($url = '/', $method = 'GET', $postData = [], $getData = [])
 */
class Tester
{
    private static $Input;
    private static $Session;

    /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     */
    public static function boot($APPPATH, $RUNTIMEPATH, $APPNAME)
    {
        Autoloader::initialize($APPPATH, $RUNTIMEPATH, $APPNAME);
        // Now Loader has been initialized by Autoloader::initialize().
        // @todo: static or self?
        static::$Input   = Loader::model('System/Input');
        static::$Session = Loader::model('System/Session');
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
        Kit::log([__METHOD__, [
            'getData'  => $getData,
            'method'   => $method,
            'postData' => $postData,
            'url'      => $url,
        ]]);
        static::$Input->clear()->merge('post', $postData)->merge('get', $getData);
        Kit::log([__METHOD__, ['static::$Input' => static::$Input]]);
        // $_SERVER['REQUEST_URI'] =  ENV_HOST . '/' . $url; // @todo: what?
        return Autoloader::resolve($method, $url);
    }
}