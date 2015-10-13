<?php

namespace Ilex;

use \Ilex\Autoloader;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;

/**
 * Class Tester
 * @package Ilex
 * 
 * @property public static \Ilex\Base\Model\sys\Input $Input
 * 
 * @method public static        boot($APPPATH, $RUNTIMEPATH)
 * @method public static string run($url = '/', $method = 'GET', $postData = [], $getData = [])
 */
class Tester
{
    public static $Input;
    public static $Session;

    /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     */
    public static function boot($APPPATH, $RUNTIMEPATH)
    {
        Autoloader::initialize($APPPATH, $RUNTIMEPATH);
        self::$Input   = Loader::model('sys/Input');
        self::$Session = Loader::model('sys/Session');
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
            'url'      => $url,
            'method'   => $method,
            'postData' => $postData,
            'getData'  => $getData
        ]]);
        self::$Input->clear()->merge('post', $postData)->merge('get', $getData);
        Kit::log([__METHOD__, ['self::$Input' => self::$Input]]);
        $_SERVER['REQUEST_URI'] =  ENV_HOST . '/' . $url;
        return Autoloader::resolve($method, $url);
    }
}